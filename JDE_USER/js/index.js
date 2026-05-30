/**
 * Index Page Logic
 */

document.addEventListener('DOMContentLoaded', function () {
    // Handle Contact Form Result from URL Parameters
    const urlParams = new URLSearchParams(window.location.search);
    const contactStatus = urlParams.get('contact');
    const reason = urlParams.get('reason');

    if (contactStatus) {
        if (contactStatus === 'success') {
            const successModalEl = document.getElementById('contactSuccessModal');
            if (successModalEl) {
                const successModal = new bootstrap.Modal(successModalEl);
                successModal.show();
            }
        } else {
            let msg = 'Failed to send message. Please try again.';
            if (reason === 'missing_fields') msg = 'Please fill in all required fields.';
            if (reason === 'invalid_email') msg = 'Please enter a valid email address.';
            if (reason === 'email_error') msg = 'There was an error sending the email. Please try again later.';

            if (typeof showNotification === 'function') {
                showNotification(msg, 'error');
            }
        }
    }

    // Product Scroller Logic
    const track = document.querySelector('.product-track');
    const prevBtn = document.querySelector('.scroll-btn.prev');
    const nextBtn = document.querySelector('.scroll-btn.next');
    const dotsContainer = document.querySelector('.product-dots');

    if (track && prevBtn && nextBtn) {
        let scrollAmount = 0;
        const itemWidth = track.firstElementChild.offsetWidth + 20; // width + gap
        const maxScroll = track.scrollWidth - track.clientWidth;

        nextBtn.addEventListener('click', () => {
            if (scrollAmount < maxScroll) {
                scrollAmount += itemWidth;
                track.scrollTo({ left: scrollAmount, behavior: 'smooth' });
            }
        });

        prevBtn.addEventListener('click', () => {
            if (scrollAmount > 0) {
                scrollAmount -= itemWidth;
                track.scrollTo({ left: scrollAmount, behavior: 'smooth' });
            }
        });
    }
});
