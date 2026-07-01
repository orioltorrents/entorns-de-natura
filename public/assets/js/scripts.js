document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-target]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            let row = document.getElementById(targetId);
            if (!row) {
                row = button.closest('tr')?.nextElementSibling;
            }

            if (row) {
                const isOpen = row.classList.contains('open');
                document.querySelectorAll('.student-editor-row.open').forEach((openRow) => {
                    openRow.classList.remove('open');
                });
                if (!isOpen) {
                    row.classList.add('open');
                }
            }
        });
    });
});
