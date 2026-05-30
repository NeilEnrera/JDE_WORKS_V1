document.addEventListener('DOMContentLoaded', function () {
    const messages = document.querySelectorAll('.error-message, .success-message');

    messages.forEach(function (message) {
        // Set a timeout to start the fade out after 3 seconds
        setTimeout(function () {
            message.classList.add('fade-out');

            // Remove the element from the DOM after the animation completes (0.5s)
            setTimeout(function () {
                message.style.display = 'none';
            }, 500);
        }, 3000);
    });
});
