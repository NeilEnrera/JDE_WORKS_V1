/**
 * My Orders (Personal Center) Scripts
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

    const feedbackModalEl = document.getElementById('feedbackModal');
    if (feedbackModalEl) {
        bootstrap.Modal.getOrCreateInstance(feedbackModalEl).show();
    }
}

let currentOrderToCancel = null;

function confirmCancel(orderID, isCustom = false) {
    currentOrderToCancel = orderID;

    const policyNote = document.getElementById('cancelPolicyNote');
    const policyText = document.getElementById('cancelPolicyText');
    const modalMessage = document.getElementById('cancelModalMessage');

    if (policyNote && policyText && modalMessage) {
        if (isCustom) {
            policyText.innerHTML = "<strong>Note for Custom Orders:</strong> Items are non-refundable once production starts. Confirming now ensures a full refund if production hasn't begun. Late cancellations may incur a fee.";
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

document.addEventListener('DOMContentLoaded', function () {
    // Cancel confirmation button listener
    const confirmBtn = document.getElementById('confirmCancelBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!currentOrderToCancel) return;

            // Hide confirmation modal
            const modalEl = document.getElementById('cancelConfirmModal');
            const confirmModal = bootstrap.Modal.getInstance(modalEl);
            if (confirmModal) confirmModal.hide();

            cancelOrder(currentOrderToCancel);
        });
    }
});
