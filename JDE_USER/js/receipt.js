/**
 * Digital Receipt Functionality
 * Handles PDF generation using html2pdf.js
 */
function downloadPDF(orderIdFormatted) {
    const element = document.getElementById('printableReceipt');
    if (!element) return;

    // Force element to be visible for capture
    const originalStyle = element.style.display;
    element.style.setProperty('display', 'block', 'important');

    // Ensure we start from the absolute top
    window.scrollTo(0, 0);

    const opt = {
        margin: [2, 2, 2, 2], // Minimal margins to maximize space
        filename: 'JDE_Works_POS_Receipt_' + orderIdFormatted + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2,
            useCORS: true,
            logging: false,
            letterRendering: true,
            scrollY: 0,
            y: 0
        },
        jsPDF: { unit: 'mm', format: [100, 280], orientation: 'portrait' }
    };

    // Use Promise to ensure we hide it AFTER capture starts
    html2pdf().set(opt).from(element).toPdf().get('pdf').then(function (pdf) {
        // PDF is ready, restore visibility
        element.style.display = originalStyle;
    }).save();
}

/**
 * Handle Cart Clearing after successful order
 */
document.addEventListener('DOMContentLoaded', function() {
    // Look for customer ID provided by the view
    const wrapper = document.querySelector('.receipt-wrapper');
    if (!wrapper) return;

    const customerId = wrapper.dataset.orderCustomerId;
    
    // Clear localStorage cart
    if (customerId && customerId !== 'null') {
        localStorage.removeItem(`jde_persistent_cart_${customerId}`);
    }
    
    // Always clear guest and legacy keys just in case
    localStorage.removeItem('jde_persistent_cart_guest');
    localStorage.removeItem('jde_persistent_cart');
    
    // Trigger a sync event for other tabs to update their UI
    localStorage.setItem('jde_cart_updated', Date.now());
    
    // If the unified notification system is loaded, refresh badges
    if (typeof window.refreshNotificationBadges === 'function') {
        window.refreshNotificationBadges(true);
    }

    // --- REAL-TIME STATUS POLLING ---
    const orderID = wrapper.dataset.orderId;
    
    // If we have an order ID, start polling
    if (orderID) {
        startStatusPolling(orderID);
    }
});

/**
 * startStatusPolling — Periodically checks for order status updates
 * @param {number} orderID 
 */
function startStatusPolling(orderID) {
    console.log(`JDE Real-time: Monitoring Order #${orderID}...`);
    
    let lastStatus = null;
    let lastPaymentStatus = null;

    const poll = () => {
        fetch(`../backend/get_order_status.php?order_id=${orderID}&t=${Date.now()}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const currentStatus = data.orderStatus;
                    const currentPaymentStatus = data.paymentStatus;

                    // Detect changes
                    if (lastStatus !== null && (currentStatus !== lastStatus || currentPaymentStatus !== lastPaymentStatus)) {
                        console.log(`JDE Real-time: Change detected! ${lastStatus} -> ${currentStatus}`);
                        
                        // Update UI Elements
                        updateReceiptUI(data);
                        
                        // Show a subtle notification if possible
                        if (typeof window.showNotification === 'function') {
                            window.showNotification('Order Updated', `Your order is now ${currentStatus}.`, 'success');
                        }
                    }

                    lastStatus = currentStatus;
                    lastPaymentStatus = currentPaymentStatus;
                }
            })
            .catch(err => console.error('Polling error:', err));
    };

    // Initial check
    poll();
    
    // Set interval (every 10 seconds for near real-time without excessive load)
    setInterval(poll, 10000);
}

/**
 * updateReceiptUI — Updates DOM elements with new order data
 * @param {Object} data 
 */
function updateReceiptUI(data) {
    // 1. Update Status Text
    const statusLabel = document.querySelector('.order-status');
    if (statusLabel) {
        statusLabel.textContent = data.orderStatus;
        // Optionally update classes if needed (JDE doesn't seem to use specific classes for each status in receipt view)
    }

    // 2. Update Success Banner
    const bannerTitle = document.querySelector('.success-banner h2');
    const bannerText = document.querySelector('.success-banner p');
    if (bannerTitle && bannerText) {
        if (data.paymentStatus.toLowerCase() === 'approved' || data.orderStatus.toLowerCase() !== 'pending') {
            bannerTitle.textContent = 'Order Processing!';
            bannerText.textContent = "We're getting your items ready for tailoring.";
        }
    }

    // 3. Update Balance
    const balanceSpan = document.querySelector('.summary-row span.text-danger.fw-bold');
    const balanceNote = document.querySelector('.summary-row span.text-muted.small.italic');
    if (balanceSpan) {
        balanceSpan.textContent = '₱' + data.paymentBalance.toLocaleString(undefined, { minimumFractionDigits: 2 });
    }
    if (balanceNote && data.paymentStatus.toLowerCase() === 'approved') {
        balanceNote.parentElement.style.display = 'none';
    }

    // 4. Update POS-Style Footer
    const posStatus = document.querySelector('.pos-footer p:first-child');
    if (posStatus) {
        posStatus.textContent = 'Payment Status: ' + data.paymentStatus.toUpperCase();
    }
}
