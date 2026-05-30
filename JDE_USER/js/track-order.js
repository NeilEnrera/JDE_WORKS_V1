/**
 * Track Order Page Scripts
 */

/**
 * Generic feedback modal handler
 */
function showFeedbackModal(title, message, isError = false) {
    const icon = document.getElementById('feedbackIcon');
    const titleEl = document.getElementById('feedbackTitle');
    const msgEl = document.getElementById('feedbackMessage');

    if (!icon || !titleEl || !msgEl) return;

    if (isError) {
        icon.className = 'bi bi-exclamation-circle-fill';
        icon.style.color = '#dc3545';
    } else {
        icon.className = 'bi bi-check-circle-fill';
        icon.style.color = '#198754';
    }

    titleEl.textContent = title;
    msgEl.textContent = message;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('feedbackModal')).show();
}

/**
 * Proof of Delivery Lightbox
 */
function openProofModal(src) {
    const modalImg = document.getElementById('proofModalImg');
    const modal = document.getElementById('proofModal');
    if (!modalImg || !modal) return;
    
    modalImg.src = src;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeProofModal() {
    const modal = document.getElementById('proofModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

/**
 * Cancellation Logic
 */
let currentOrderToCancel = null;

function confirmCancel(orderID, isCustom = false) {
    console.log("Confirming cancellation for order:", orderID, "isCustom:", isCustom);
    currentOrderToCancel = orderID;
    
    const policyNote = document.getElementById('cancelPolicyNote');
    const policyText = document.getElementById('cancelPolicyText');
    const modalMessage = document.getElementById('cancelModalMessage');

    if (policyNote && policyText && modalMessage) {
        if (isCustom) {
            policyText.innerHTML = "<strong>Note for Custom Orders:</strong> Bespoke items are non-refundable once production starts. Confirming now ensures a full refund if production hasn't begun. Late cancellations may incur a fee.";
            policyNote.classList.remove('d-none');
            modalMessage.innerHTML = "Are you sure you want to cancel this <strong>customized order</strong>?";
        } else {
            policyNote.classList.add('d-none');
            modalMessage.innerHTML = "Are you sure you want to cancel this order? This action cannot be undone once confirmed.";
        }
    }

    const modalEl = document.getElementById('cancelConfirmModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else {
        // Fallback if modal is missing for some reason
        if (confirm('Are you sure you want to cancel this order?')) {
            cancelOrder(orderID);
        }
    }
}

function cancelOrder(orderID) {
    const formData = new FormData();
    formData.append('action', 'cancel_order');
    formData.append('orderID', orderID);

    fetch('customer_order_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedbackModal('Order Cancelled', 'Your order has been cancelled successfully. Any items have been returned to stock.', false);
            setTimeout(() => location.reload(), 2000);
        } else {
            showFeedbackModal('Cancellation Failed', data.message, true);
        }
    })
    .catch(err => {
        console.error(err);
        showFeedbackModal('Error', 'An unexpected error occurred. Please try again later.', true);
    });
}

/**
 * Event Listeners initialization
 */
document.addEventListener('DOMContentLoaded', function () {
    // Review form handler
    const reviewForm = document.getElementById('trackOrderReviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'submit_bulk_reviews');

            fetch('review_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFeedbackModal('Thank You!', 'Reviews submitted successfully! We appreciate your feedback.');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).hide();
                } else {
                    showFeedbackModal('Oops!', 'Error: ' + data.message, true);
                }
            })
            .catch(err => {
                console.error(err);
                showFeedbackModal('Error', 'An unexpected error occurred. Please try again later.', true);
            });
        });
    }

    // Proof modal click backdrop to close
    const proofModal = document.getElementById('proofModal');
    if (proofModal) {
        proofModal.addEventListener('click', function (e) {
            if (e.target === this) closeProofModal();
        });
    }

    // Escape key handling for proof modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeProofModal();
    });

    // Auto-open review modal if 'rate' parameter is present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('rate')) {
        const reviewModalEl = document.getElementById('reviewModal');
        if (reviewModalEl) {
            const modal = new bootstrap.Modal(reviewModalEl);
            modal.show();
        }
    }

    // Cancel confirmation button listener
    const confirmBtn = document.getElementById('confirmCancelBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!currentOrderToCancel) return;

            const modalEl = document.getElementById('cancelConfirmModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            cancelOrder(currentOrderToCancel);
        });
    }
});
