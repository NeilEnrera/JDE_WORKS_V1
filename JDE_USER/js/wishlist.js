/**
 * Wishlist functionality for wishlist.php
 * Handles item removal with animations and synchronization with navbar badges.
 */

function toggleWishlist(button) {
    // IMPORTANT: Stop propagation to prevent parent card click (redirection)
    if (event) {
        event.stopPropagation();
    }

    if (!window.isLoggedIn) {
        const modalEl = document.getElementById('loginRequiredModal');
        if (modalEl) {
            const msgEl = document.getElementById('loginRequiredMessage');
            if (msgEl) msgEl.textContent = 'Please log in to save items to your wishlist.';
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            window.location.href = 'login.php';
        }
        return;
    }

    const sku = button.getAttribute('data-sku');
    if (!sku) return;

    // Visual feedback (optimistic update)
    button.classList.toggle('active');

    // Find the parent product item for potential removal
    const productItem = button.closest('.product-item');

    fetch('wishlist.php?action=toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ productId: sku })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Synchronize all buttons with same SKU (including modal)
                const allButtons = document.querySelectorAll(`.wishlist-btn[data-sku="${sku}"], #modalWishlistBtn[data-sku="${sku}"]`);
                allButtons.forEach(btn => {
                    if (data.added) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });

                // If the item was removed (not added), we remove it from the wishlist view
                if (!data.added) {
                    if (productItem) {
                        // Fade out animation
                        productItem.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                        productItem.style.opacity = '0';
                        productItem.style.transform = 'scale(0.9)';

                        setTimeout(() => {
                            productItem.remove();

                            // Check if wishlist is now empty
                            const remainingItems = document.querySelectorAll('.product-item');
                            if (remainingItems.length === 0) {
                                showEmptyWishlist();
                            }
                        }, 400);
                    }
                }

                // Update navbar badges using unified system (trigger sync for other tabs)
                if (typeof window.refreshNotificationBadges === 'function') {
                    window.refreshNotificationBadges(true);
                }

                // Show toast notification only when added
                if (data.added && typeof window.showNotification === 'function') {
                    window.showNotification('Added to favorites', 'success');
                }
            } else {
                // Revert UI on error
                button.classList.toggle('active');
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data.message || 'Error updating wishlist', 'error');
                }
            }
        })
        .catch(error => {
            button.classList.toggle('active');
            console.error('Error toggling wishlist:', error);
            if (typeof window.showNotification === 'function') {
                window.showNotification('Network error. Please try again.', 'error');
            }
        });
}


/**
 * Displays the empty wishlist state when no items remain.
 */
function showEmptyWishlist() {
    const container = document.querySelector('.wishlist-container');
    if (container) {
        container.innerHTML = `
            <div class="empty-wishlist text-center py-5">
                <div class="empty-wishlist-hero">
                    <div class="empty-icon-wrapper">
                        <i class="bi bi-bookmark-heart"></i>
                    </div>
                    <h3>Your wishlist is longing for items</h3>
                    <p>Browse our curated collection of premade and custom uniforms to find your perfect fit.</p>
                    <a href="product.php" class="btn btn-premium-action">Start Exploring</a>
                </div>
            </div>
        `;
    }
}

// Staggered Animation Trigger
document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.product-item');
    setTimeout(() => {
        items.forEach(item => {
            item.classList.add('appeared');
        });
    }, 100);
});

// Add All To Cart Logic
function addAllToCart() {
    const items = document.querySelectorAll('.product-item');
    if (items.length === 0) return;

    const confirmModalEl = document.getElementById('confirmBulkModal');
    const confirmBody = document.getElementById('confirmBulkBody');
    const executeBtn = document.getElementById('executeBulkBtn');

    if (!confirmModalEl || !confirmBody || !executeBtn) return;

    confirmBody.textContent = 'Add all ' + items.length + ' items to your cart?';

    const modal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
    modal.show();

    executeBtn.onclick = function () {
        modal.hide();

        const promises = [];
        items.forEach(item => {
            const sku = item.getAttribute('data-sku');
            const name = item.getAttribute('data-name');
            const price = item.getAttribute('data-price');
            const img = item.getAttribute('data-img');

            const payload = {
                id: sku,
                name: name,
                price: price,
                image: img,
                size: 'S', // Default size for bulk add
                quantity: 1
            };

            promises.push(
                fetch('cart_handler.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).then(res => res.json())
            );
        });

        Promise.all(promises).then(results => {
            const successCount = results.filter(r => r.success).length;
            if (typeof window.refreshNotificationBadges === 'function') {
                window.refreshNotificationBadges(true);
            }
            if (typeof window.showNotification === 'function') {
                window.showNotification(`Successfully added ${successCount} items to cart.`, 'success');
            }
        }).catch(err => {
            console.error('Bulk add error:', err);
            if (typeof window.showNotification === 'function') {
                window.showNotification('Error adding some items to cart.', 'error');
            }
        });
    };
}
