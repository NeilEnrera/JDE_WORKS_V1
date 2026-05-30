document.addEventListener('DOMContentLoaded', function () {
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        const input = button.parentElement.querySelector('input[type="password"], input[type="text"]');
        if (!input) return;

        const icon = button.querySelector('i');

        // Function to update icon visibility
        function updateToggleVisibility() {
            if (input.value.length > 0) {
                button.style.display = 'flex'; // Show icon container
            } else {
                button.style.display = 'none'; // Hide icon container
                // Reset to password type if cleared
                input.type = 'password';
                if (icon) {
                    icon.className = 'fa-solid fa-eye-slash';
                }
            }
        }

        // Initialize visibility on load
        updateToggleVisibility();

        // Update visibility on input
        input.addEventListener('input', updateToggleVisibility);

        button.addEventListener('click', function () {
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.className = 'fa-solid fa-eye';
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.className = 'fa-solid fa-eye-slash';
                }
            }
        });
    });
});
