/**
 * Shopping Cart Page Interactions
 * Handles quantity updates and item removal with localized popover confirmation
 */

let currentDeletingIndex = -1;
let lastCartData = []; // Store the latest cart state with discounts

/**
 * Toggles the global deletion popover relative to the clicked button
 */
function toggleDeletePopover(event, button, index) {
    event.preventDefault();
    event.stopPropagation();

    const popover = document.getElementById('globalDeletePopover');
    const confirmBtn = document.getElementById('confirmDeleteBtn');

    if (!popover || !confirmBtn) return;

    // If clicking the same button and popover is visible, close it
    if (currentDeletingIndex === index && !popover.classList.contains('d-none')) {
        closeAllPopovers();
        return;
    }

    currentDeletingIndex = index;
    confirmBtn.onclick = () => removeCartItem(index, confirmBtn);

    // Position the popover
    const rect = button.getBoundingClientRect();
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    // Center popover relative to button horizontally, and place below vertically
    popover.style.left = (rect.left + scrollLeft + (rect.width / 2) - 240) + 'px'; // 240 is offset to align arrow
    popover.style.top = (rect.bottom + scrollTop + 15) + 'px';

    popover.classList.remove('d-none');

    // Close when clicking outside
    const handleOutsideClick = (e) => {
        if (!popover.contains(e.target) && e.target !== button && !button.contains(e.target)) {
            closeAllPopovers();
            document.removeEventListener('click', handleOutsideClick);
        }
    };
    document.addEventListener('click', handleOutsideClick);
}

/**
 * Closes the global deletion popover
 */
function closeAllPopovers() {
    const popover = document.getElementById('globalDeletePopover');
    const clearPopover = document.getElementById('clearAllPopover');
    if (popover) {
        popover.classList.add('d-none');
    }
    if (clearPopover) {
        clearPopover.classList.add('d-none');
    }
    currentDeletingIndex = -1;
    currentDeletingIndex = -1;
}

/**
 * Perform the actual removal via AJAX
 */
function removeCartItem(index, confirmButton) {
    const row = document.querySelector(`tr[data-index="${index}"]`);

    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove&index=' + index
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animation for row removal
                if (row) {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.95)';

                    setTimeout(() => {
                        row.remove();

                        // Close popover
                        closeAllPopovers();

                        // CRITICAL: Synchronize DOM indices after removal
                        syncCartIndices();

                        // Update totals in UI based on selected checkboxes
                        updateSelectedTotals();

                        // Synchronize localStorage immediately to prevent race conditions on reload
                        const cartKey = window.currentUserId ? `jde_persistent_cart_${window.currentUserId}` : 'jde_persistent_cart_guest';
                        if (data.items && data.items.length > 0) {
                            localStorage.setItem(cartKey, JSON.stringify(data.items));
                        } else {
                            localStorage.removeItem(cartKey);
                            localStorage.removeItem('jde_persistent_cart');
                        }

                        // Check if cart is empty
                        if (data.count === 0) {
                            location.reload();
                        }
                    }, 400);
                } else {
                    location.reload();
                }
            } else {
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data.message || 'Error removing item', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            location.reload();
        });
}

/**
 * Toggles the clear all popover
 */
function toggleClearAllPopover(event, button) {
    event.preventDefault();
    event.stopPropagation();

    const popover = document.getElementById('clearAllPopover');
    if (!popover) return;

    if (!popover.classList.contains('d-none')) {
        closeAllPopovers();
        return;
    }

    closeAllPopovers(); // close others

    const rect = button.getBoundingClientRect();
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    // Center popover relative to button horizontally, and place below vertically
    popover.style.left = (rect.left + scrollLeft + (rect.width / 2) - 240) + 'px'; // 240 is offset to align arrow
    popover.style.top = (rect.bottom + scrollTop + 15) + 'px';

    popover.classList.remove('d-none');

    const handleOutsideClick = (e) => {
        if (!popover.contains(e.target) && e.target !== button && !button.contains(e.target)) {
            closeAllPopovers();
            document.removeEventListener('click', handleOutsideClick);
        }
    };
    document.addEventListener('click', handleOutsideClick);
}

/**
 * Perform removal of entire cart
 */
function clearAllCartItems() {
    closeAllPopovers();

    // Validate that at least one item is selected
    const checkedItems = document.querySelectorAll('.cart-item-checkbox:checked');
    if (checkedItems.length === 0) {
        if (typeof window.showNotification === 'function') {
            window.showNotification('No items selected for deletion.', 'warning');
        }
        return;
    }

    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear local storage for items being cleared to prevent restoration
                const cartKey = window.currentUserId ? `jde_persistent_cart_${window.currentUserId}` : 'jde_persistent_cart_guest';
                if (data.items && data.items.length > 0) {
                    localStorage.setItem(cartKey, JSON.stringify(data.items));
                } else {
                    localStorage.removeItem(cartKey);
                    localStorage.removeItem('jde_persistent_cart');
                }
                location.reload();
            } else {
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data.message || 'Error clearing items', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            location.reload();
        });
}

/**
 * Re-indexes all cart rows in the DOM to match the updated session array
 */
function syncCartIndices() {
    const rows = document.querySelectorAll('.cart-table tbody tr');
    rows.forEach((row, newIndex) => {
        row.setAttribute('data-index', newIndex);

        // Update the onclick handlers for qty and remove buttons
        const qtyBtns = row.querySelectorAll('.qty-btn');
        const qtyInput = row.querySelector('.qty-input');
        if (qtyBtns.length >= 2 && qtyInput) {
            const currentQty = parseInt(qtyInput.value);
            qtyBtns[0].setAttribute('onclick', `updateCartQuantity(${newIndex}, ${currentQty - 1})`);
            qtyBtns[1].setAttribute('onclick', `updateCartQuantity(${newIndex}, ${currentQty + 1})`);
        }

        const removeBtn = row.querySelector('.btn-remove');
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `toggleDeletePopover(event, this, ${newIndex})`);
        }

        const checkbox = row.querySelector('.cart-item-checkbox');
        if (checkbox) {
            checkbox.setAttribute('data-index', newIndex);
        }
    });
}

/**
 * Handle quantity changes with dynamic UI update
 */
function updateCartQuantity(index, newQuantity) {
    if (newQuantity < 1) {
        // Trigger deletion if quantity is 1 and user clicks minus
        const targetBtn = document.querySelector(`tr[data-index="${index}"] .btn-remove`);
        if (targetBtn) {
            targetBtn.click();
        }
        return;
    }

    const row = document.querySelector(`tr[data-index="${index}"]`);
    const qtyInput = row ? row.querySelector('.qty-input') : null;
    const subtotalCell = row ? row.querySelector('.cart-subtotal') : null;

    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update&index=' + index + '&quantity=' + newQuantity
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update input value with actual quantity from server
                const actualQty = data.quantity;
                if (qtyInput) qtyInput.value = actualQty;

                // Sync current quantity in buttons
                if (row) {
                    const qtyBtns = row.querySelectorAll('.qty-btn');
                    if (qtyBtns.length >= 2) {
                        qtyBtns[0].setAttribute('onclick', `updateCartQuantity(${index}, ${actualQty - 1})`);
                        qtyBtns[1].setAttribute('onclick', `updateCartQuantity(${index}, ${actualQty + 1})`);
                    }
                }

                // Synchronize localStorage immediately
                const cartKey = window.currentUserId ? `jde_persistent_cart_${window.currentUserId}` : 'jde_persistent_cart_guest';
                if (data.items && data.items.length > 0) {
                    localStorage.setItem(cartKey, JSON.stringify(data.items));
                }

                // updateSelectedTotals handles updating the UI entirely
                if (data.items) {
                    lastCartData = data.items;
                }
                updateSelectedTotals();

                // Update navbar badge
                if (typeof window.refreshNotificationBadges === 'function') {
                    window.refreshNotificationBadges(true, false);
                }

            } else {
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data.message || 'Error updating quantity', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

/**
 * Central utility to update totals based on currently checked items
 */
function updateSelectedTotals() {
    let selectedSubtotal = 0;
    let totalDiscount = 0;
    
    const checkedItems = document.querySelectorAll('.cart-item-checkbox:checked');
    const checkoutBtn = document.querySelector('.btn-checkout');

    // 1. Reset all rows first
    document.querySelectorAll('.cart-table tbody tr').forEach(row => {
        const idx = parseInt(row.getAttribute('data-index'));
        const item = lastCartData[idx];
        if (!item) return;

        const totalCell = row.querySelector('[data-label="Total"]');
        if (!totalCell) return;

        const baseSubtotal = item.price * item.quantity;
        const discount = item.discount || { amount: 0, percentage: 0, finalTotal: baseSubtotal };
        
        if (discount.amount > 0) {
            totalCell.innerHTML = `
                <div class="cart-item-total-container">
                    <div class="original-price" style="text-decoration: line-through; color: #6c757d; font-size: 0.9em;">
                        ₱${baseSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                    </div>
                    <div class="discount-badge" style="color: #198754; font-size: 0.85em; font-weight: 600; margin-bottom: 2px;">
                        ${discount.percentage}% Off (-₱${discount.amount.toLocaleString('en-US', { minimumFractionDigits: 2 })})
                    </div>
                    <span class="cart-subtotal fw-bold">₱${discount.finalTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                </div>
            `;
        } else {
            totalCell.innerHTML = `
                <div class="cart-item-total-container">
                    <span class="cart-subtotal fw-bold">₱${baseSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                </div>
            `;
        }

        // If checked, add to grand totals
        const checkbox = row.querySelector('.cart-item-checkbox');
        if (checkbox && checkbox.checked) {
            selectedSubtotal += baseSubtotal;
            totalDiscount += discount.amount;
        }
    });

    let grandTotal = selectedSubtotal - totalDiscount;

    const subtotalText = document.getElementById('cartSubtotalOriginal');
    if (subtotalText) subtotalText.innerText = '₱' + selectedSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2 });

    const discountRow = document.getElementById('cartDiscountRow');
    const discountAmountEl = document.getElementById('cartDiscountAmount');

    if (discountRow) {
        if (totalDiscount > 0) {
            discountRow.style.display = '';
            if (discountAmountEl) discountAmountEl.innerText = '-₱' + totalDiscount.toLocaleString('en-US', { minimumFractionDigits: 2 });
        } else {
            discountRow.style.display = 'none';
        }
    }

    const totalText = document.getElementById('cartTotal');
    if (totalText) totalText.innerText = '₱' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });

    // Toggle checkout button state
    if (checkoutBtn) {
        if (checkedItems.length === 0) {
            checkoutBtn.classList.add('btn-disabled');
            checkoutBtn.style.opacity = '0.5';
            checkoutBtn.style.pointerEvents = 'none';
        } else {
            checkoutBtn.classList.remove('btn-disabled');
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.pointerEvents = 'auto';
        }
    }
}

/**
 * Persists the selection state to the server session
 */
function syncSelectionWithSession(index, isChecked) {
    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_selection&index=${index}&selected=${isChecked ? 1 : 0}`
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to sync selection:', data.message);
            }
        })
        .catch(err => console.error('Error syncing selection:', err));
}

// === Select All / Deselect All logic ===
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllItems');
    if (selectAll) {
        const itemsContainer = document.querySelector('.cart-table tbody');

        selectAll.addEventListener('change', function () {
            const isChecked = this.checked;
            document.querySelectorAll('.cart-item-checkbox').forEach(cb => {
                cb.checked = isChecked;
                const row = cb.closest('tr');
                if (row) {
                    if (isChecked) row.classList.remove('item-deselected');
                    else row.classList.add('item-deselected');
                }
            });
            updateSelectedTotals();
            syncSelectionWithSession('all', isChecked);
        });

        if (itemsContainer) {
            itemsContainer.addEventListener('change', function (e) {
                if (e.target.classList.contains('cart-item-checkbox')) {
                    const all = document.querySelectorAll('.cart-item-checkbox');
                    const checked = document.querySelectorAll('.cart-item-checkbox:checked');
                    selectAll.checked = (all.length === checked.length);
                    selectAll.indeterminate = (checked.length > 0 && checked.length < all.length);

                    const row = e.target.closest('tr');
                    if (row) {
                        if (e.target.checked) row.classList.remove('item-deselected');
                        else row.classList.add('item-deselected');
                    }

                    updateSelectedTotals();
                    syncSelectionWithSession(e.target.dataset.index, e.target.checked);
                }
            });
        }

        // Fetch initial data to populate discounts
        fetch('cart_handler.php?action=get')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    lastCartData = data.items;
                    updateSelectedTotals();
                }
            });
    }
});


