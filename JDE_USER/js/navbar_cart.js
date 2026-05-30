document.addEventListener('DOMContentLoaded', function () {
    // Event delegation for cart preview controls
    document.addEventListener('click', function (e) {
        // Handle Quantity Increase
        if (e.target.closest('.qty-plus')) {
            e.preventDefault();
            e.stopPropagation();
            const btn = e.target.closest('.qty-plus');
            const index = btn.dataset.index;
            const currentQty = parseInt(btn.dataset.qty);
            updateCartQuantity(index, currentQty + 1);
        }

        // Handle Quantity Decrease
        if (e.target.closest('.qty-minus')) {
            e.preventDefault();
            e.stopPropagation();
            const btn = e.target.closest('.qty-minus');
            const index = btn.dataset.index;
            const currentQty = parseInt(btn.dataset.qty);
            if (currentQty > 1) {
                updateCartQuantity(index, currentQty - 1);
            } else {
                removeCartItem(index);
            }
        }

        // Handle Item Removal
        if (e.target.closest('.cart-remove-mini')) {
            e.preventDefault();
            e.stopPropagation();
            const btn = e.target.closest('.cart-remove-mini');
            const index = btn.dataset.index;
            removeCartItem(index);
        }

        // Handle Checkbox Toggle
        if (e.target.classList.contains('item-checkbox')) {
            e.stopPropagation();
            const index = e.target.dataset.index;
            const isChecked = e.target.checked;
            toggleCartItemSelection(index, isChecked);
        }
    });

    /**
     * Toggle selection via AJAX
     */
    function toggleCartItemSelection(index, isSelected) {
        const formData = new FormData();
        formData.append('action', 'toggle_selection');
        formData.append('index', index);
        formData.append('selected', isSelected ? 1 : 0);

        fetch('cart_handler.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    refreshCartUI();
                    // If we are on the cart page, we should reload to keep consistency
                    if (window.location.pathname.includes('cart.php')) {
                        location.reload();
                    }
                }
            })
            .catch(error => console.error('Error toggling selection:', error));
    }

    /**
     * Update quantity via AJAX
     */
    function updateCartQuantity(index, quantity) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('index', index);
        formData.append('quantity', quantity);

        fetch('cart_handler.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartKey = window.currentUserId ? `jde_persistent_cart_${window.currentUserId}` : 'jde_persistent_cart_guest';
                    if (data.items && data.items.length > 0) {
                        localStorage.setItem(cartKey, JSON.stringify(data.items));
                    }
                    refreshCartUI();
                }
            })
            .catch(error => console.error('Error updating cart:', error));
    }

    /**
     * Remove item via AJAX
     */
    function removeCartItem(index) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('index', index);

        fetch('cart_handler.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartKey = window.currentUserId ? `jde_persistent_cart_${window.currentUserId}` : 'jde_persistent_cart_guest';
                    if (data.items && data.items.length > 0) {
                        localStorage.setItem(cartKey, JSON.stringify(data.items));
                    } else {
                        localStorage.removeItem(cartKey);
                        localStorage.removeItem('jde_persistent_cart');
                    }
                    refreshCartUI();
                }
            })
            .catch(error => console.error('Error removing item:', error));
    }
});

/**
 * Re-render the cart preview items and footer
 */
function renderCartPreview(items, count) {
    const containers = document.querySelectorAll('.cart-items-preview');
    const footers = document.querySelectorAll('.cart-footer-mini');

    containers.forEach(container => {
        if (count === 0 || !items || items.length === 0) {
            container.innerHTML = `
                <div class="cart-empty-state text-center py-5">
                    <i class="bi bi-cart-x fs-1 text-muted opacity-50 mb-3"></i>
                    <p class="text-muted">Your cart is empty</p>
                </div>`;

            footers.forEach(f => f.style.display = 'none');
            return;
        }

        let html = '';
        let selectedTotal = 0;
        let selectedCount = 0;

        items.forEach((item, index) => {
            const isSelected = item.selected !== false;
            if (isSelected) {
                selectedTotal += item.price * item.quantity;
                selectedCount++;
            }
            
            html += `
                <div class="cart-item-mini-new p-3 d-flex align-items-start border-bottom ${!isSelected ? 'opacity-50' : ''}">
                    <div class="cart-item-check me-2 pt-1">
                        <input type="checkbox" class="item-checkbox" ${isSelected ? 'checked' : ''} data-index="${index}">
                    </div>
                    <div class="cart-item-img-container me-3">
                        <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="rounded shadow-sm">
                    </div>
                    <div class="cart-item-details-new flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="cart-item-name-new mb-1 text-truncate pe-2">${escapeHtml(item.name)}</h6>
                        </div>
                        <div class="cart-item-meta-new mb-2">
                            <span class="text-muted small">${escapeHtml(item.variation || item.size || 'Default')}</span>
                            <div class="qty-selector-new mt-1 d-inline-flex align-items-center">
                                <span class="qty-label me-1 small text-muted">Qty:</span>
                                <div class="qty-control d-flex align-items-center bg-light rounded-pill px-2 py-1">
                                    <button class="qty-btn qty-minus" data-index="${index}" data-qty="${item.quantity}">−</button>
                                    <span class="qty-value mx-2 fw-bold" style="font-size: 13px;">${item.quantity}</span>
                                    <button class="qty-btn qty-plus" data-index="${index}" data-qty="${item.quantity}">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="cart-item-price-new fw-bold text-dark fs-5">₱${numberFormat(item.price)}</div>
                            <button class="btn btn-sm text-muted cart-remove-mini p-0" data-index="${index}">
                                <i class="bi bi-trash fs-5"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
        });

        container.innerHTML = html;

        footers.forEach(footer => {
            footer.style.display = 'block';
            const totalVal = footer.querySelector('.cart-total-value');
            const viewBtn = footer.querySelector('.btn-view-cart');
            if (totalVal) totalVal.textContent = `₱${numberFormat(selectedTotal)}`;
            if (viewBtn) viewBtn.textContent = `VIEW CART (${count})`;
        });
    });
}

/**
 * Global helpers for cart UI
 */
window.refreshCartUI = function() {
    if (typeof window.refreshNotificationBadges === 'function') {
        // Pass triggerSync=true and allowRestore=false
        window.refreshNotificationBadges(true, false);
    }
};

window.renderCartPreview = renderCartPreview;

/**
 * Helper to escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Helper to format numbers with commas and 2 decimals
 */
function numberFormat(num) {
    return parseFloat(num).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
