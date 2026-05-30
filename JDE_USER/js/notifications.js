/**
 * Unified Notification System for Cart and Wishlist
 * Handles real-time badge updates across all pages.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initial fetch to ensure badges are correct on page load
    refreshNotificationBadges();

    // Listen for storage events to sync across tabs
    window.addEventListener('storage', function (e) {
        if (e.key === 'jde_cart_updated' || e.key === 'jde_wishlist_updated') {
            refreshNotificationBadges(false); // Don't trigger sync again
        }
    });

    // Handle back/forward cache restore
    window.addEventListener('pageshow', function(event) {
        refreshNotificationBadges();
    });
});

let isFirstCartSync = true;

/**
 * Fetch current counts from server and update all badges
 * @param {boolean} triggerSync - Whether to trigger storage event for other tabs
 * @param {boolean} allowRestore - Whether to allow restoration from localStorage if server is empty
 */
function refreshNotificationBadges(triggerSync = false, allowRestore = true) {
    const timestamp = new Date().getTime();
    const cartKey = window.currentUserId ? `jde_persistent_cart_${window.currentUserId}` : 'jde_persistent_cart_guest';

    // Fetch Cart Count
    fetch(`cart_handler.php?action=get&t=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                // Handle deletion/cleanup after an order
                if (data.just_ordered) {
                    localStorage.removeItem(cartKey);
                    localStorage.removeItem('jde_persistent_cart'); // Legacy fallback
                }

                if (data.count > 0) {
                    // SERVER HAS ITEMS: Update local storage to match server (truth)
                    localStorage.setItem(cartKey, JSON.stringify(data.items));
                    updateCartBadge(data.count, triggerSync);
                    if (typeof window.renderCartPreview === 'function') {
                        window.renderCartPreview(data.items, data.count);
                    }
                } else {
                    // SERVER IS EMPTY
                    
                    // IF FIRST SYNC: try to restore from localStorage if user is logged in
                    if (isFirstCartSync && allowRestore && window.isLoggedIn && window.currentUserId && !data.just_ordered) {
                        const saved = localStorage.getItem(cartKey);
                        if (saved) {
                            try {
                                const items = JSON.parse(saved);
                                if (items && items.length > 0) {
                                    restoreCartToServer(items);
                                    isFirstCartSync = false;
                                    return; // restoreCartToServer will handle the badge update
                                }
                            } catch (e) {
                                localStorage.removeItem(cartKey);
                            }
                        }
                    }
                    
                    // OTHERWISE: Server is truly empty, or we just finished an order/delete
                    // We MUST clear local storage to stay in sync
                    localStorage.removeItem(cartKey);
                    updateCartBadge(0, triggerSync);
                    if (typeof window.renderCartPreview === 'function') {
                        window.renderCartPreview([], 0);
                    }
                }
            } else {
                updateCartBadge(0, triggerSync);
            }
            isFirstCartSync = false;
        })
        .catch(error => {
            console.error('Error fetching cart count:', error);
            updateCartBadge(0, false);
            isFirstCartSync = false;
        });

    // Fetch Wishlist Count
    fetch(`wishlist.php?action=get&t=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                // Use explicit count from server if available (more reliable)
                const count = (data.wishlistCount !== undefined) ?
                    data.wishlistCount :
                    (data.wishlist ? Object.values(data.wishlist).length : 0);
                updateWishlistBadge(count, triggerSync);
            } else {
                updateWishlistBadge(0, triggerSync);
            }
        })
        .catch(error => {
            console.error('Error fetching wishlist count:', error);
            updateWishlistBadge(0, false);
        });
}

/**
 * Update the Shopping Cart badge
 * @param {number} count 
 * @param {boolean} triggerSync - Whether to trigger storage event for other tabs
 */
function updateCartBadge(count, triggerSync = true) {
    const badges = document.querySelectorAll('#navbarCartBadge, .cart-badge');
    badges.forEach(badge => {
        const displayCount = parseInt(count) || 0;
        if (displayCount > 0) {
            badge.textContent = displayCount;
            badge.classList.remove('d-none');
            badge.style.display = 'block';
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
            badge.classList.add('d-none');
        }
    });

    // Support for legacy classes if any
    document.querySelectorAll('.badge.bg-danger').forEach(badge => {
        if (badge.closest('a[href="cart.php"]')) {
            const displayCount = parseInt(count) || 0;
            if (displayCount > 0) {
                badge.textContent = displayCount;
                badge.style.display = 'block';
                badge.classList.remove('d-none');
            } else {
                badge.style.display = 'none';
                badge.classList.add('d-none');
            }
        }
    });

    if (triggerSync) {
        localStorage.setItem('jde_cart_updated', Date.now());
    }
}

/**
 * Update the Wishlist badge
 * @param {number} count 
 * @param {boolean} triggerSync
 */
function updateWishlistBadge(count, triggerSync = true) {
    const badges = document.querySelectorAll('#navbarWishlistBadge, .wishlist-badge');
    badges.forEach(badge => {
        const displayCount = parseInt(count) || 0;
        if (displayCount > 0) {
            badge.textContent = displayCount;
            badge.classList.remove('d-none');
            badge.style.display = 'block';
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
            badge.classList.add('d-none');
        }
    });

    document.querySelectorAll('.badge.bg-danger').forEach(badge => {
        if (badge.id === 'wishlistBadge' || badge.closest('a[href="wishlist.php"]')) {
            const displayCount = parseInt(count) || 0;
            if (displayCount > 0) {
                badge.textContent = displayCount;
                badge.style.display = 'block';
                badge.classList.remove('d-none');
            } else {
                badge.style.display = 'none';
                badge.classList.add('d-none');
            }
        }
    });

    if (triggerSync) {
        localStorage.setItem('jde_wishlist_updated', Date.now());
    }
}

/**
 * Sync frontend cart to backend session
 */
function restoreCartToServer(items) {
    fetch('cart_handler.php?action=restore', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items: items })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.count, false);
            if (typeof window.renderCartPreview === 'function') {
                window.renderCartPreview(items, data.count);
            }
            localStorage.setItem('jde_cart_updated', Date.now()); 
        }
    })
    .catch(err => console.error('Error in restoreCartToServer:', err));
}

// Expose globally
window.updateCartBadge = updateCartBadge;
window.updateWishlistBadge = updateWishlistBadge;
window.refreshNotificationBadges = refreshNotificationBadges;
window.showNotification = window.showNotification || function (message, type) {
    // Fallback if site.js isn't loaded or doesn't have it
    console.log(`Notification [${type}]: ${message}`);
};
