/**
 * Shared Quick Add Modal Logic
 * Used by product.php and wishlist.php
 */

// Global State for current modal
window.currentQuickSizeStocks = {};
window.quickModalGalleryImages = [];
window.currentQuickLightboxIndex = 0;

// Redirect to ordering page with product ID as parameter
function orderProduct(productId) {
    window.location.href = 'ordering.php?product=' + encodeURIComponent(productId);
}

// Quick Add Modal Logic
function triggerQuickAdd(button) {
    const item = button.closest('.product-item');
    if (!item) return;

    // Extract data from attributes
    const name = item.getAttribute('data-name');
    const price = item.getAttribute('data-price');
    window.currentQuickBasePrice = parseFloat(price || 0);
    
    const sku = item.getAttribute('data-sku');
    const img = item.getAttribute('data-img');
    const desc = item.getAttribute('data-description');
    const category = item.getAttribute('data-category');
    const databaseId = item.getAttribute('data-id');
    const slug = item.id ? item.id.replace('product-', '') : (item.getAttribute('data-sku') || '');

    // Populate Modal
    document.getElementById('quickModalName').textContent = name;
    document.getElementById('quickModalSku').textContent = sku;
    document.getElementById('quickModalPrice').textContent = '₱' + window.currentQuickBasePrice.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('quickModalDescription').textContent = desc || 'No description provided.';
    document.getElementById('quickModalCategory').textContent = category;
    document.getElementById('quickMainImg').src = img;
    
    // Update Rating and Reviews (New Dynamic Logic)
    const rating = parseFloat(item.getAttribute('data-rating') || 0);
    const reviews = parseInt(item.getAttribute('data-reviews') || 0);
    
    const ratingContainer = document.querySelector('.quick-modal-rating');
    const starsContainer = ratingContainer ? ratingContainer.querySelector('.stars') : null;
    const ratingText = ratingContainer ? ratingContainer.querySelector('.rating-text') : null;

    if (starsContainer && ratingText) {
        starsContainer.innerHTML = ''; // Clear existing stars
        
        // Create 5 stars based on rating
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('i');
            if (i <= Math.round(rating)) {
                star.className = 'bi bi-star-fill text-warning';
            } else {
                star.className = 'bi bi-star text-warning';
            }
            starsContainer.appendChild(star);
        }
        
        // Update review count text
        if (reviews > 0) {
            ratingText.textContent = `(${reviews} ${reviews === 1 ? 'Review' : 'Reviews'})`;
        } else {
            ratingText.textContent = '(New Product - No reviews yet)';
            // Reset stars to outline if no reviews
            starsContainer.querySelectorAll('.bi-star-fill').forEach(s => {
                s.className = 'bi bi-star text-warning';
            });
        }
    }

    // Set View Details Link
    document.getElementById('quickModalViewDetails').onclick = () => orderProduct(slug);

    // Store database ID in modal
    const quickModal = document.getElementById('quickAddModal');
    if (quickModal) quickModal.setAttribute('data-current-id', databaseId);

    // Initialize Wishlist Button in Modal
    const modalWishlistBtn = document.getElementById('modalWishlistBtn');
    if (modalWishlistBtn) {
        modalWishlistBtn.setAttribute('data-sku', sku);
        // Sync active state from the main grid button
        const gridWishBtn = item.querySelector('.wishlist-btn');
        if (gridWishBtn) {
            modalWishlistBtn.classList.toggle('active', gridWishBtn.classList.contains('active'));
        }
    }

    // Set thumbnails (Dynamic)
    const img2 = item.getAttribute('data-img2');
    const img3 = item.getAttribute('data-img3');
    const img4 = item.getAttribute('data-img4');
    const imageList = [img, img2, img3, img4].filter(src => src && src.trim() !== '');
    window.quickModalGalleryImages = imageList;
    window.currentQuickLightboxIndex = 0;

    const thumbContainer = document.querySelector('.quick-thumbnails');
    if (thumbContainer) {
        thumbContainer.innerHTML = '';
        imageList.forEach((src, index) => {
            const thumb = document.createElement('img');
            thumb.src = src;
            thumb.className = 'q-thumb' + (index === 0 ? ' active' : '');
            thumb.onclick = function() { 
                switchQuickThumb(this); 
                window.currentQuickLightboxIndex = index;
            };
            thumbContainer.appendChild(thumb);
        });
    }

    // Set Main Image click for Lightbox
    const mainImg = document.getElementById('quickMainImg');
    if(mainImg) {
        mainImg.style.cursor = 'zoom-in';
        mainImg.onclick = () => openQuickLightbox(window.currentQuickLightboxIndex);
    }

    // Multi-Size Stock Logic (New)
    const sizeStocksStr = item.getAttribute('data-size-stocks') || '{}';
    const sizeStocks = JSON.parse(sizeStocksStr);
    window.currentQuickSizeStocks = sizeStocks;

    const sizeContainer = document.querySelector('.quick-sizes');
    if (sizeContainer) {
        sizeContainer.innerHTML = '';
        const sizes = Object.keys(sizeStocks);
        if (sizes.length === 0) {
            sizeContainer.innerHTML = '<span class="text-danger fw-bold">Out of Stock</span>';
        } else {
            sizes.forEach((label) => {
                const data = sizeStocks[label];
                const qty = (typeof data === 'object') ? (data.qty || 0) : data;
                const price = (typeof data === 'object') ? (data.price || 0) : 0;

                const sBtn = document.createElement('button');
                sBtn.type = 'button';
                sBtn.className = 'q-size-btn';
                sBtn.textContent = label;
                sBtn.setAttribute('data-price', price); // Store price in button data

                if (qty <= 0) {
                    sBtn.classList.add('out-of-stock-size');
                    // Do not disable, allow for Pre-Orders
                }
                sBtn.onclick = () => selectQuickSize(label, sBtn);
                sizeContainer.appendChild(sBtn);
            });

            // Auto-select first available
            const firstAvailable = sizes.find(s => {
                const d = sizeStocks[s];
                return (typeof d === 'object') ? (d.qty > 0) : (d > 0);
            });
            if (firstAvailable) {
                const firstBtn = Array.from(sizeContainer.querySelectorAll('.q-size-btn')).find(b => b.textContent === firstAvailable);
                if (firstBtn) selectQuickSize(firstAvailable, firstBtn);
            }
        }
    }

    // Show Modal
    if (quickModal) {
        quickModal.style.display = 'flex';
        setTimeout(() => quickModal.classList.add('active'), 10);
    }
    document.body.style.overflow = 'hidden';

    // Manual input validation
    const qtyInput = document.getElementById('quickQtyInput');
    if (qtyInput) {
        qtyInput.onchange = function() {
            const max = parseInt(this.max || '999');
            const val = parseInt(this.value || '1');
            if (val > max) {
                this.value = max;
            }
            if (val < 1) this.value = 1;
        };
    }
}

function closeQuickAddModal() {
    const modal = document.getElementById('quickAddModal');
    if (!modal) return;
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 400);
}

function selectQuickSize(label, btn) {
    if (btn && btn.classList.contains('out-of-stock')) return;
    document.querySelectorAll('.q-size-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const data = window.currentQuickSizeStocks[label] || 0;
    const qty = (typeof data === 'object') ? (data.qty || 0) : data;
    
    // Logic like ordering page: use price from button if available, else modal default
    let price = parseFloat(btn.getAttribute('data-price') || 0);
    const finalPrice = (price > 0) ? price : (window.currentQuickBasePrice || 0);
    
    if (document.getElementById('quickModalPrice')) {
        document.getElementById('quickModalPrice').textContent = '₱' + finalPrice.toLocaleString('en-PH', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }

    const statusEl = document.querySelector('.stock-status');
    const qtyInput = document.getElementById('quickQtyInput');
    
    if (statusEl) {
        if (qty > 0) {
            statusEl.textContent = qty + ' items in stock (' + label + ')';
            statusEl.className = 'stock-status in-stock text-success';
        } else {
            statusEl.textContent = 'Out of Stock';
            statusEl.className = 'stock-status out-of-stock text-danger';
        }
    }

    // Frontend: Set max attribute, but we will allow exceeding it for Pre-Orders
    if (qtyInput) {
        qtyInput.max = qty > 0 ? qty : 999;
        // If normal stock is 0, we still allow input for pre-orders
    }
}

function updateQuickQty(delta) {
    const input = document.getElementById('quickQtyInput');
    if (!input) return;
    
    let current = parseInt(input.value || '1', 10);
    let max = parseInt(input.max || '999', 10);
    let val = current + delta;
    
    if (val < 1) val = 1;
    // We allow exceeding max for Pre-Order capability in the UI
    input.value = val;
}

function processQuickAdd(isPreOrder = false) {
    const name = document.getElementById('quickModalName').textContent;
    const activeSizeBtn = document.querySelector('.q-size-btn.active');
    if (!activeSizeBtn) {
        if (typeof window.showNotification === 'function') showNotification('Please select a size', 'warning');
        return;
    }
    const size = activeSizeBtn.textContent;
    const qty = parseInt(document.getElementById('quickQtyInput').value);
    
    // Check if normal Add to Cart is being used for out-of-stock item
    const sizeStocks = window.currentQuickSizeStocks || {};
    const stockData = sizeStocks[size];
    const availableQty = (typeof stockData === 'object') ? (stockData.qty || 0) : stockData;
    
    if (!isPreOrder && qty > availableQty) {
        if (typeof window.showNotification === 'function') {
            showNotification(`Only ${availableQty} items available in stock. Use PRE-ORDER for larger quantities.`, 'warning');
        }
        return;
    }

    const priceText = document.getElementById('quickModalPrice').textContent;
    const price = parseFloat(priceText.replace('₱', '').replace(/,/g, ''));
    const image = document.getElementById('quickMainImg').src;
    const sku = document.getElementById('quickModalSku').textContent;
    const category = document.getElementById('quickModalCategory').textContent;
    const databaseId = document.getElementById('quickAddModal').getAttribute('data-current-id');

    const productData = {
        action: 'add',
        id: databaseId || sku,
        name: name,
        price: price,
        quantity: qty,
        size: size,
        image: image,
        sku: sku,
        category: category,
        custom: false,
        preOrder: isPreOrder
    };

    // Determine which button to animate
    const btnClass = isPreOrder ? '.btn-quick-preorder' : '.btn-quick-add';
    const btn = document.querySelector(btnClass);
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'ADDING...';

    fetch('cart_handler.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(productData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof window.updateCartBadge === 'function') window.updateCartBadge(data.count);
            if (typeof window.refreshCartUI === 'function') window.refreshCartUI();
            if (typeof window.showNotification === 'function') showNotification('Item added to cart!', 'success');
            if (typeof animateCartIcon === 'function') animateCartIcon();

            btn.textContent = 'ADDED!';
            btn.style.background = '#2ecc71';
            setTimeout(() => {
                closeQuickAddModal();
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    btn.style.background = '';
                }, 500);
            }, 800);
        } else {
            if (typeof window.showNotification === 'function') showNotification('Error: ' + data.message, 'error');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Lightbox
function openQuickLightbox(index = 0) {
    if (!window.quickModalGalleryImages || window.quickModalGalleryImages.length === 0) return;
    window.currentQuickLightboxIndex = index;
    const img = document.getElementById('quickLightboxImg');
    const lb = document.getElementById('quickAddLightbox');
    if (img && lb) {
        img.src = window.quickModalGalleryImages[index];
        lb.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeQuickLightbox(e) {
    const lb = document.getElementById('quickAddLightbox');
    if (!lb) return;
    if (e.target.id === 'quickAddLightbox' || e.target.classList.contains('lightbox-close')) {
        lb.classList.remove('active');
        if (!document.getElementById('quickAddModal').classList.contains('active')) {
            document.body.style.overflow = '';
        }
    }
}

function navigateQuickLightbox(dir, e) {
    if (e) e.stopPropagation();
    window.currentQuickLightboxIndex += dir;
    if (window.currentQuickLightboxIndex < 0) window.currentQuickLightboxIndex = window.quickModalGalleryImages.length - 1;
    if (window.currentQuickLightboxIndex >= window.quickModalGalleryImages.length) window.currentQuickLightboxIndex = 0;
    
    document.getElementById('quickLightboxImg').src = window.quickModalGalleryImages[window.currentQuickLightboxIndex];
    const thumbs = Array.from(document.querySelectorAll('.q-thumb'));
    if (thumbs[window.currentQuickLightboxIndex]) switchQuickThumb(thumbs[window.currentQuickLightboxIndex]);
}

function animateCartIcon() {
    const cartIcon = document.querySelector('a[aria-label="Cart"], .cart-icon-link');
    const cartBadge = document.getElementById('navbarCartBadge');
    if (cartIcon) {
        cartIcon.style.animation = 'none';
        setTimeout(() => { cartIcon.style.animation = 'cartPulse 0.5s ease'; }, 10);
    }
    if (cartBadge && cartBadge.textContent !== '0') {
        cartBadge.style.transform = 'scale(1.3)';
        setTimeout(() => { cartBadge.style.transform = 'scale(1)'; }, 300);
    }
}

function switchQuickThumb(thumb) {
    document.querySelectorAll('.q-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
    document.getElementById('quickMainImg').src = thumb.src;
}

// Listeners
window.addEventListener('click', (e) => {
    if (e.target === document.getElementById('quickAddModal')) closeQuickAddModal();
});

document.addEventListener('keydown', (e) => {
    const lb = document.getElementById('quickAddLightbox');
    if (lb && lb.classList.contains('active')) {
        if (e.key === 'ArrowLeft') navigateQuickLightbox(-1);
        if (e.key === 'ArrowRight') navigateQuickLightbox(1);
        if (e.key === 'Escape') closeQuickLightbox({ target: lb });
    }
});
