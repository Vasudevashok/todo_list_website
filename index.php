
<?php
session_start();
require_once "db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid request. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");

        if ($title !== "" && mb_strlen($title) <= 255) {
            $stmt = $pdo->prepare(
                "INSERT INTO tasks (title, description)
                 VALUES (?, ?)"
            );
            $stmt->execute([$title, $description]);
        }

    } elseif ($action === "edit") {
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");

        if ($id && $title !== "" && mb_strlen($title) <= 255) {
            $stmt = $pdo->prepare(
                "UPDATE tasks
                 SET title = ?, description = ?
                 WHERE id = ?"
            );
            $stmt->execute([$title, $description, $id]);
        }

    } elseif ($action === "toggle") {
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE tasks
                 SET is_completed = 1 - is_completed
                 WHERE id = ?"
            );
            $stmt->execute([$id]);
        }

    } elseif ($action === "delete") {
        $id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
        }
    }

    header("Location: index.php");
    exit;
}

$stmt = $pdo->query(
    "SELECT * FROM tasks ORDER BY is_completed ASC, created_at DESC"
);
$tasks = $stmt->fetchAll();

$total = count($tasks);
$completed = 0;

foreach ($tasks as $task) {
    if ((int)$task["is_completed"] === 1) {
        $completed++;
    }
}

$pending = $total - $completed;
$progress = $total ? round($completed / $total * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - My To-Do List</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

<div class="layout">

    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-icon">✓</span> TaskFlow
        </a>

        <p class="nav-heading">WORKSPACE</p>

        <nav>
            <a class="nav-link active" href="#dashboard">▦ Dashboard</a>
            <button class="nav-link filter-control" data-filter="all"
                    type="button">☷ All Tasks</button>
            <button class="nav-link filter-control" data-filter="pending"
                    type="button">◷ Pending</button>
            <button class="nav-link filter-control" data-filter="completed"
                    type="button">✓ Completed</button>
        </nav>

        <div class="sidebar-note">
            <strong>Stay productive!</strong>
            <p>Small steps every day lead to big results.</p>
        </div>
    </aside>

    <main class="main-content" id="dashboard">

        <header class="topbar">
            <div>
                <p class="eyebrow">YOUR PERSONAL WORKSPACE</p>
                <h1>My Dashboard</h1>
                <p class="muted"><?= e(date("l, F j, Y")) ?></p>
            </div>
            <a class="primary-button" href="#add-task">+ New Task</a>
        </header>

        <section class="welcome-banner">
            <p class="eyebrow">YOUR DAILY OVERVIEW</p>
            <h2>Make today count.</h2>
            <p>Organize your work, focus on what matters, and celebrate every completed task.</p>
        </section>

        <section class="stats">
            <article class="stat-card">
                <p>Total Tasks</p>
                <strong><?= $total ?></strong>
                <span>All your tasks</span>
            </article>

            <article class="stat-card">
                <p>Pending</p>
                <strong><?= $pending ?></strong>
                <span>Still to be done</span>
            </article>

            <article class="stat-card">
                <p>Completed</p>
                <strong><?= $completed ?></strong>
                <span>Tasks finished</span>
            </article>
        </section>

        <section class="progress-card">
            <div class="progress-heading">
                <div>
                    <h3>Overall Progress</h3>
                    <p class="muted">Keep going — every task counts!</p>
                </div>
                <strong><?= $progress ?>%</strong>
            </div>

            <div class="progress-track">
                <div class="progress-fill"
                     style="width: <?= $progress ?>%"></div>
            </div>
            <p class="muted"><?= $completed ?> of <?= $total ?> tasks completed</p>
        </section>

        <section class="tasks-section" id="tasks">
            <div class="section-heading">
                <div>
                    <h2>My Tasks</h2>
                    <p class="muted">Manage everything you need to get done.</p>
                </div>
            </div>

            <div class="toolbar">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" type="button">All</button>
                    <button class="filter-tab" data-filter="pending" type="button">Pending</button>
                    <button class="filter-tab" data-filter="completed" type="button">Completed</button>
                </div>

                <input id="task-search" type="search"
                       placeholder="Search tasks..." aria-label="Search tasks">
            </div>

            <div class="task-list" id="task-list">

                <?php foreach ($tasks as $task): ?>
                    <?php $done = (int)$task["is_completed"] === 1; ?>

                    <article class="task-card"
                             data-status="<?= $done ? 'completed' : 'pending' ?>"
                             data-search="<?= e(mb_strtolower($task["title"] . " " . $task["description"])) ?>">

                        <form method="POST" class="toggle-form">
                            <input type="hidden" name="csrf_token"
                                   value="<?= e($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id"
                                   value="<?= (int)$task["id"] ?>">
                            <button class="check-button <?= $done ? 'checked' : '' ?>"
                                    type="submit"
                                    aria-label="Toggle completion"><?= $done ? '✓' : '' ?></button>
                        </form>

                        <div class="task-info">
                            <h3 class="<?= $done ? 'completed-title' : '' ?>">
                                <?= e($task["title"]) ?>
                            </h3>

                            <?php if ($task["description"] !== ""): ?>
                                <p class="muted"><?= nl2br(e($task["description"])) ?></p>
                            <?php endif; ?>

                            <span class="status <?= $done ? 'done' : '' ?>">
                                <?= $done ? 'Completed' : 'Pending' ?>
                            </span>
                        </div>

                        <div class="task-actions">
                            <button class="icon-button edit-trigger"
                                    type="button" title="Edit task">✎</button>

                            <form method="POST" class="delete-form">
                                <input type="hidden" name="csrf_token"
                                       value="<?= e($_SESSION["csrf_token"]) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"
                                       value="<?= (int)$task["id"] ?>">
                                <button class="icon-button delete-button"
                                        type="submit" title="Delete task">⌫</button>
                            </form>
                        </div>

                        <form method="POST" class="edit-form" hidden>
                            <input type="hidden" name="csrf_token"
                                   value="<?= e($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id"
                                   value="<?= (int)$task["id"] ?>">

                            <label>Task title
                                <input name="title" maxlength="255" required
                                       value="<?= e($task["title"]) ?>">
                            </label>

                            <label>Description
                                <textarea name="description" rows="3"><?= e($task["description"]) ?></textarea>
                            </label>

                            <div class="edit-actions">
                                <button class="cancel-edit secondary-button"
                                        type="button">Cancel</button>
                                <button class="primary-button" type="submit">
                                    Save Changes
                                </button>
                            </div>
                        </form>

                    </article>
                <?php endforeach; ?>

                <div id="empty-state" class="empty-state"
                     <?= $total ? 'hidden' : '' ?>>
                    <h3>No tasks yet</h3>
                    <p class="muted">Add your first task below to get started.</p>
                </div>

                <p id="no-results" class="muted" hidden>
                    No matching tasks found.
                </p>
            </div>
        </section>

        <section class="add-task-section" id="add-task">
            <h2>Create a New Task</h2>
            <p class="muted">Write down your next priority.</p>

            <form method="POST" class="add-task-form">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="action" value="add">

                <label for="title">Task title</label>
                <input id="title" name="title" maxlength="255"
                       required placeholder="e.g. Complete my PHP assignment">

                <label for="description">Description (optional)</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Add details about this task..."></textarea>

                <button class="primary-button" type="submit">+ Add Task</button>
            </form>
        </section>

        <footer class="footer">Made for a more productive day. ✦</footer>

    </main>
</div>

</body>
</html>
