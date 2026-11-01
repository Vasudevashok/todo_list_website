
document.addEventListener("DOMContentLoaded", function () {
    const taskCards = Array.from(
        document.querySelectorAll(".task-card")
    );

    const filterButtons = document.querySelectorAll("[data-filter]");
    const searchInput = document.getElementById("task-search");
    const emptyState = document.getElementById("empty-state");
    const noResults = document.getElementById("no-results");

    let currentFilter = "all";

    // Filter tasks and apply the search term.
    function updateTasks() {
        const searchTerm = searchInput.value
            .trim()
            .toLowerCase();

        let visibleCount = 0;

        taskCards.forEach(function (card) {
            const status = card.dataset.status;
            const searchableText = card.dataset.search || "";

            const matchesFilter =
                currentFilter === "all" ||
                status === currentFilter;

            const matchesSearch =
                searchableText.includes(searchTerm);

            const visible = matchesFilter && matchesSearch;

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        // Highlight the active filter.
        filterButtons.forEach(function (button) {
            const active =
                button.dataset.filter === currentFilter;

            button.classList.toggle("active", active);
            button.setAttribute("aria-pressed", String(active));
        });

        // Show the correct empty-state message.
        if (taskCards.length === 0) {
            emptyState.hidden = false;
            noResults.hidden = true;
        } else {
            emptyState.hidden = true;
            noResults.hidden = visibleCount > 0;
        }
    }

    // Handle sidebar and task-list filter buttons.
    filterButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            currentFilter = button.dataset.filter;
            updateTasks();

            if (button.classList.contains("filter-control")) {
                document.getElementById("tasks")
                    .scrollIntoView({ behavior: "smooth" });
            }
        });
    });

    // Search by title or description.
    searchInput.addEventListener("input", updateTasks);

    // Open or close the edit form.
    document.querySelectorAll(".edit-trigger").forEach(
        function (button) {
            button.addEventListener("click", function () {
                const card = button.closest(".task-card");
                const form = card.querySelector(".edit-form");

                document.querySelectorAll(".edit-form").forEach(
                    function (otherForm) {
                        if (otherForm !== form) {
                            otherForm.hidden = true;
                        }
                    }
                );

                form.hidden = !form.hidden;

                if (!form.hidden) {
                    form.querySelector('input[name="title"]').focus();
                }
            });
        }
    );

    // Cancel editing.
    document.querySelectorAll(".cancel-edit").forEach(
        function (button) {
            button.addEventListener("click", function () {
                button.closest(".edit-form").hidden = true;
            });
        }
    );

    // Confirm before deleting a task.
    document.querySelectorAll(".delete-form").forEach(
        function (form) {
            form.addEventListener("submit", function (event) {
                const confirmed = window.confirm(
                    "Are you sure you want to delete this task?"
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }
    );

    // Set initial filter state.
    updateTasks();
});
