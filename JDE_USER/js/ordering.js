// ordering.js - behavior for the ordering/product details page

function selectSize(label, btn) {
  window.selectedSizeLabel = label; // Track selected size globally
  document.querySelectorAll('.sizes button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Update Stock Status display
  const sizeData = window.currentSizeStocks ? window.currentSizeStocks[label] : null;
  const qty = sizeData ? (typeof sizeData === 'object' ? (sizeData.qty || 0) : sizeData) : 0;

  const stockEl = document.getElementById('productStock');
  const qtyInput = document.getElementById('quantity');
  
  if (stockEl) {
    if (qty > 0) {
      stockEl.textContent = qty + ' items in stock (' + label + ')';
      stockEl.className = 'stock in-stock text-success';
    } else {
      stockEl.textContent = 'Out of Stock';
      stockEl.className = 'stock out-of-stock text-danger';
    }
  }

  // Frontend Fix: Set max attribute on quantity input
  if (qtyInput) {
    qtyInput.max = 999; // Allow oversized input for pre-orders
  }

  // Update displayed price from the button's data-price attribute or fallback to base price
  const price = parseFloat(btn.dataset.price || 0);
  const finalPrice = (price > 0) ? price : (window.currentBasePrice || 0);
  const priceEl = document.getElementById('productPrice');
  if (priceEl) {
    priceEl.textContent = '\u20b1' + finalPrice.toLocaleString('en-PH', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
  }

  // If Size Guide drawer is open, refresh it to highlight/filter correctly
  const drawer = document.getElementById('sizeGuideDrawer');
  if (drawer && drawer.classList.contains('show')) {
    renderDynamicSizeChart();
    highlightSelectedSizeInGuide();
  }

  // Update the context banner in the Size Guide
  updateSizeBanner(label);
}

function updateSizeBanner(label) {
    const banner = document.getElementById('sgSelectedBanner');
    const bannerText = document.getElementById('sgSelectedBannerText');
    if (!banner || !bannerText) return;

    if (label) {
        bannerText.textContent = 'Showing: ' + label;
        banner.style.display = 'flex';
        // Also populate metric cards if dynamic data is available
        populateMetricCards(label);
    } else {
        banner.style.display = 'none';
        const metricCards = document.getElementById('sg-metric-cards');
        if (metricCards) metricCards.style.display = 'none';
    }
}

function populateMetricCards(label) {
    const metricCards = document.getElementById('sg-metric-cards');
    if (!metricCards) return;

    const stocks = window.currentSizeStocks || {};
    const entry = stocks[label];
    if (!entry || typeof entry !== 'object' || !entry.measurements) {
        metricCards.style.display = 'none';
        return;
    }

    const m = entry.measurements;
    const hasSomeData = Object.values(m).some(v => v !== null && v !== undefined);
    if (!hasSomeData) {
        metricCards.style.display = 'none';
        return;
    }

    const fmt = v => (v !== null && v !== undefined) ? v + '"' : '—';
    document.getElementById('sg-mc-chest').textContent    = fmt(m.chest);
    document.getElementById('sg-mc-waist').textContent    = fmt(m.waist);
    document.getElementById('sg-mc-length').textContent   = fmt(m.length);
    document.getElementById('sg-mc-shoulder').textContent = fmt(m.shoulder);
    document.getElementById('sg-mc-sleeve').textContent   = fmt(m.sleeve);

    metricCards.style.display = 'grid';
}

function clearSizeFilter() {
    window.selectedSizeLabel = null;
    // Deselect size buttons on the page
    document.querySelectorAll('.sizes button').forEach(b => b.classList.remove('active'));
    // Hide banner and metric cards
    updateSizeBanner(null);
    // Re-render chart (show all sizes)
    renderDynamicSizeChart();
    applyFilteringToStaticTable();
}

function highlightSelectedSizeInGuide() {
    if (!window.selectedSizeLabel) return;
    const row = document.querySelector(`.size-table tr[data-size="${window.selectedSizeLabel}"]`);
    if (row) {
        document.querySelectorAll('.size-table tr').forEach(r => r.classList.remove('highlight-row', 'table-primary'));
        row.classList.add('highlight-row', 'table-primary');
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// --- Customization modal ---
function openCustomization() {
  var modal = document.getElementById('customizationModal');
  if (!modal) return;
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

// --- Size Guide Side Drawer ---
function openSizeGuide() {
  var drawer = document.getElementById('sizeGuideDrawer');
  var overlay = document.getElementById('sizeGuideDrawerOverlay');
  if (!drawer || !overlay) return;

  renderDynamicSizeChart(); // Render data from product state

  drawer.classList.add('show');
  overlay.style.display = 'block';
  setTimeout(() => overlay.style.opacity = '1', 10);
  document.body.style.overflow = 'hidden';

  // Show/update the selected-size banner
  updateSizeBanner(window.selectedSizeLabel || null);

  // Highlight the row for selected size
  setTimeout(highlightSelectedSizeInGuide, 120);
}

function renderDynamicSizeChart() {
    const container = document.getElementById('product-measurements');
    const stocks = window.currentSizeStocks || {};
    const sizes = Object.entries(stocks);
    
    // Check if any size has measurement data
    const hasMeasurements = sizes.some(([label, data]) => data.measurements && Object.values(data.measurements).some(v => v !== null));
    
    if (!hasMeasurements) {
        // Apply filtering to the static table if dynamic measurements aren't available
        applyFilteringToStaticTable();
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-bordered size-table mb-2 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>SIZE</th>
                        <th>Chest</th>
                        <th>Waist</th>
                        <th>Length</th>
                        <th>Shoulder</th>
                        <th>Sleeve</th>
                    </tr>
                </thead>
                <tbody>
    `;

    sizes.forEach(([label, data]) => {
        // If a size is selected, we only show that size (Filtering behavior)
        if (window.selectedSizeLabel && label !== window.selectedSizeLabel) {
            return;
        }

        const m = data.measurements || {};
        const isSelected = label === window.selectedSizeLabel;
        html += `
            <tr data-size="${label}" class="${isSelected ? 'highlight-row table-primary' : ''}">
                <td class="fw-bold">${label}</td>
                <td>${(m.chest !== null && m.chest !== undefined) ? m.chest + '"' : '-'}</td>
                <td>${(m.waist !== null && m.waist !== undefined) ? m.waist + '"' : '-'}</td>
                <td>${(m.length !== null && m.length !== undefined) ? m.length + '"' : '-'}</td>
                <td>${(m.shoulder !== null && m.shoulder !== undefined) ? m.shoulder + '"' : '-'}</td>
                <td>${(m.sleeve !== null && m.sleeve !== undefined) ? m.sleeve + '"' : '-'}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
        <p class="text-muted" style="font-size: 0.75rem;">* Measurements provided specifically for this product. All values in inches.</p>
    `;

    container.innerHTML = html;
}

function applyFilteringToStaticTable() {
    // Filter both Product Measurements and Body Measurements
    const allTables = document.querySelectorAll('.size-table');
    
    allTables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const size = row.getAttribute('data-size');
            if (window.selectedSizeLabel) {
                // If the row has a data-size, check if it matches
                if (size) {
                    if (size === window.selectedSizeLabel) {
                        row.style.display = '';
                        row.classList.add('highlight-row', 'table-primary');
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    // If no data-size on row, maybe try to match the first column text
                    const firstCell = row.cells[0]?.textContent?.trim();
                    if (firstCell === window.selectedSizeLabel || firstCell.includes(window.selectedSizeLabel)) {
                        row.style.display = '';
                        row.classList.add('highlight-row', 'table-primary');
                    } else {
                        row.style.display = 'none';
                    }
                }
            } else {
                row.style.display = '';
                row.classList.remove('highlight-row', 'table-primary');
            }
        });
    });
}

function closeSizeGuide() {
  var drawer = document.getElementById('sizeGuideDrawer');
  var overlay = document.getElementById('sizeGuideDrawerOverlay');
  if (!drawer || !overlay) return;
  drawer.classList.remove('show');
  overlay.style.opacity = '0';
  setTimeout(() => {
    overlay.style.display = 'none';
    document.body.style.overflow = '';
  }, 300);
}

function switchSizeTab(btn, contentId) {
  // Remove active class from all tabs
  var tabs = document.querySelectorAll('.size-tab');
  tabs.forEach(t => t.classList.remove('active'));

  // Add active class to clicked tab
  btn.classList.add('active');

  // Hide all content
  var contents = document.querySelectorAll('.size-tab-content');
  contents.forEach(c => c.classList.remove('active'));

  // Show target content
  document.getElementById(contentId).classList.add('active');
  
  // Re-apply filtering to the newly shown tab
  renderDynamicSizeChart();
}

// Close modals when clicking outside the content
window.addEventListener('click', function (e) {
  var customizationModal = document.getElementById('customizationModal');
  var drawerOverlay = document.getElementById('sizeGuideDrawerOverlay');

  if (e.target === customizationModal) {
    closeCustomization();
  }
  if (e.target === drawerOverlay) {
    closeSizeGuide();
  }
});

// --- Quantity controls ---
function increaseQuantity() {
  var input = document.getElementById('quantity');
  if (!input) return;
  var current = parseInt(input.value || '1', 10);
  var max = parseInt(input.max || '999', 10);
  if (current < max) {
    input.value = current + 1;
  }
}

function decreaseQuantity() {
  var input = document.getElementById('quantity');
  if (!input) return;
  var current = parseInt(input.value || '1', 10);
  var min = parseInt(input.min || '1', 10);
  if (current > min) {
    input.value = current - 1;
  }
}

// --- Cart Management ---
var cartItems = [];

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', function () {
  loadCartItems();
  setupCartEventListeners();
  checkProductLoaded();
  ensureCartIconClickable();

  // Manual input validation for quantity
  const qtyInput = document.getElementById('quantity');
  if (qtyInput) {
    qtyInput.addEventListener('change', function() {
      const max = parseInt(this.max || '999');
      const val = parseInt(this.value || '1');
      if (val > max) {
        this.value = max;
      }
      if (val < 1) this.value = 1;
    });
  }

  // Check product loading status periodically
  var checkInterval = setInterval(function () {
    if (checkProductLoaded()) {
      clearInterval(checkInterval);
    }
  }, 500);

  // Stop checking after 10 seconds
  setTimeout(function () {
    clearInterval(checkInterval);
  }, 10000);
});

// Ensure cart icon is clickable
function ensureCartIconClickable() {
  var cartLinks = document.querySelectorAll('a[aria-label="Cart"], .cart-icon-link');
  cartLinks.forEach(function (link) {
    // Ensure link is clickable
    link.style.pointerEvents = 'auto';
    link.style.cursor = 'pointer';
    link.style.zIndex = '100';

    // Add click handler as backup
    link.addEventListener('click', function (e) {
      // If cart.php doesn't exist, show alert
      var href = this.getAttribute('href');
      if (href === 'cart.php') {
        // Link should work, but add fallback
        console.log('Cart icon clicked, navigating to cart.php');
      }
    });
  });
}

function checkProductLoaded() {
  var productNameEl = document.getElementById('productName');
  var addToCartBtn = document.querySelector('.btn.add-to-cart');

  if (!productNameEl || !addToCartBtn) {
    return false;
  }

  var isLoaded = productNameEl.textContent !== 'Loading...' &&
    productNameEl.textContent.trim() !== '' &&
    productNameEl.textContent !== '-';

  if (isLoaded) {
    addToCartBtn.disabled = false;
    addToCartBtn.style.opacity = '1';
    addToCartBtn.style.cursor = 'pointer';
    addToCartBtn.style.pointerEvents = 'auto';
    return true;
  } else {
    // Don't disable button - let user try, validation will handle it
    addToCartBtn.disabled = false;
    addToCartBtn.style.opacity = '1';
    addToCartBtn.style.cursor = 'pointer';
    addToCartBtn.style.pointerEvents = 'auto';
    return false;
  }
}

function setupCartEventListeners() {
  // No cart dropdown on ordering page - cart icon links to cart.php
}

function loadCartItems() {
  // Load cart items from session/localStorage or initialize empty
  // For now, we'll use a simple array. You can integrate with PHP session later.
  // Update cart badge when page loads using unified system
  if (typeof window.refreshNotificationBadges === 'function') {
    window.refreshNotificationBadges();
  }
}

// function updateCartBadgeGlobal() removed - now using js/notifications.js

function saveCartItems() {
  // localStorage.setItem('cartItems', JSON.stringify(cartItems)); removed
  // Update notification badges using unified system
  if (typeof window.refreshNotificationBadges === 'function') {
    window.refreshNotificationBadges();
  }
}

function addToCart(isPreOrder = false) {
  try {
    // Validate product is loaded
    var productNameEl = document.getElementById('productName');
    var productPriceEl = document.getElementById('productPrice');

    if (!productNameEl || productNameEl.textContent === 'Loading...' || productNameEl.textContent.trim() === '') {
      if (typeof showNotification === 'function') {
        showNotification('Please wait for product to load', 'warning');
      } else {
        alert('Please wait for product to load');
      }
      return;
    }

    var productName = productNameEl.textContent.trim();
    var productPrice = productPriceEl ? parseFloat(productPriceEl.textContent.replace(/[₱,]/g, '')) : 0;

    // Validate price
    if (isNaN(productPrice) || productPrice <= 0) {
      if (typeof showNotification === 'function') {
        showNotification('Invalid product price. Please refresh the page.', 'error');
      } else {
        alert('Invalid product price. Please refresh the page.');
      }
      return;
    }

    var quantity = document.getElementById('quantity') ? parseInt(document.getElementById('quantity').value) : 1;
    var size = getSelectedSize();

    // Validate size is selected
    if (!size || size === '') {
      if (typeof showNotification === 'function') {
        showNotification('Please select a size', 'warning');
      } else {
        alert('Please select a size');
      }
      return;
    }

    var productImage = document.getElementById('productImage') ? document.getElementById('productImage').src : 'assets/img/unifrom.jpeg';
    var productCategory = document.getElementById('productCategory') ? document.getElementById('productCategory').textContent : '';

    // Prepare data
    var cartItem = {
      id: window.currentProductID || null,
      name: productName,
      price: productPrice,
      quantity: quantity,
      size: size,
      color: 'Blue', // Default color as per original logic
      image: productImage,
      category: productCategory,
      custom: false,
      preOrder: isPreOrder
    };

    // Stock validation
    var sizeData = window.currentSizeStocks ? window.currentSizeStocks[size] : null;
    var availableQty = sizeData ? (typeof sizeData === 'object' ? (sizeData.qty || 0) : sizeData) : 0;
    
    if (!isPreOrder && quantity > availableQty) {
      if (typeof showNotification === 'function') {
        showNotification(`Only ${availableQty} items available in stock. For larger quantities, please use Pre-Order.`, 'warning');
      } else {
        alert(`Only ${availableQty} items available in stock.`);
      }
      return;
    }

    // Send to server
    fetch('cart_handler.php?action=add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(cartItem)
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (typeof showNotification === 'function') {
            showNotification('Item added to cart!', 'success');
          } else {
            alert('Item added to cart!');
          }

          // Update badge using unified system
          if (typeof window.updateCartBadge === 'function') {
            window.updateCartBadge(data.count);
          }


          // Refresh Hover Preview UI
          if (typeof window.refreshCartUI === 'function') {
            window.refreshCartUI();
          }

          // Animate cart icon badge
          animateCartIcon();
        } else {
          alert('Failed to add to cart: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Error adding to cart:', error);
        alert('Error connecting to server.');
      });

  } catch (error) {
    console.error('Error adding to cart:', error);
    alert('Error adding item to cart. Please try again.');
  }
}

// Animate cart icon when item is added
function animateCartIcon() {
  var cartIcon = document.querySelector('a[aria-label="Cart"], .cart-icon-link');
  var cartBadge = document.getElementById('navbarCartBadge');

  if (cartIcon) {
    // Add pulse animation
    cartIcon.style.animation = 'none';
    setTimeout(function () {
      cartIcon.style.animation = 'cartPulse 0.5s ease';
    }, 10);
  }

  if (cartBadge && cartBadge.textContent !== '0') {
    // Animate badge
    cartBadge.style.transform = 'scale(1.3)';
    cartBadge.style.transition = 'transform 0.3s ease';
    setTimeout(function () {
      cartBadge.style.transform = 'scale(1)';
    }, 300);
  }
}

function addToCartWithCustomization() {
  // Validate product is loaded
  var productNameEl = document.getElementById('productName');
  var productPriceEl = document.getElementById('productPrice');

  if (!productNameEl || productNameEl.textContent === 'Loading...' || productNameEl.textContent.trim() === '') {
    showNotification('Please wait for product to load', 'warning');
    return;
  }

  var productName = productNameEl.textContent.trim();
  var productPrice = productPriceEl ? parseFloat(productPriceEl.textContent.replace(/[₱,]/g, '')) : 0;

  // Validate price
  if (isNaN(productPrice) || productPrice <= 0) {
    showNotification('Invalid product price. Please refresh the page.', 'error');
    return;
  }

  var quantity = document.getElementById('quantity') ? parseInt(document.getElementById('quantity').value) : 1;
  var productImage = document.getElementById('productImage') ? document.getElementById('productImage').src : 'assets/img/unifrom.jpeg';
  var productCategory = document.getElementById('productCategory') ? document.getElementById('productCategory').textContent : '';

  // Collect Customization Data
  var customizationData = {};
  var inputs = document.querySelectorAll('#customizationModal input, #customizationModal textarea');
  inputs.forEach(function (input) {
    if (input.name) {
      customizationData[input.name] = input.value;
    }
  });

  // Create custom cart item
  var cartItem = {
    id: window.currentProductID || null,
    name: productName + ' (Custom)',
    price: productPrice + 100, // Automatic customization surcharge
    quantity: quantity,
    size: 'Custom',
    color: 'Custom',
    image: productImage,
    category: productCategory,
    custom: true,
    details: customizationData
  };

  // Send to server
  fetch('cart_handler.php?action=add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(cartItem)
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showNotification('Custom item added to cart!', 'success');
        if (typeof window.updateCartBadge === 'function') {
          window.updateCartBadge(data.count);
        }


        // Refresh Hover Preview UI
        if (typeof window.refreshCartUI === 'function') {
          window.refreshCartUI();
        }

        closeCustomization();
        animateCartButton();
      } else {
        alert('Failed to add custom item: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Error adding custom item:', error);
      alert('Error connecting to server.');
    });
}

function getSelectedSize() {
  var sizeButtons = document.querySelectorAll('.sizes button.active');
  if (sizeButtons.length > 0) {
    return sizeButtons[0].textContent.trim();
  }
  return null; // Return null if no size selected
}

// showNotification moved to site.js

function animateCartButton() {
  var cartButton = document.querySelector('.btn.add-to-cart');
  if (cartButton) {
    cartButton.style.transform = 'scale(0.95)';
    setTimeout(function () {
      cartButton.style.transform = '';
    }, 200);
  }
}

function renderCartItems() {
  // Cart rendering removed - no dropdown on ordering page
  // Items are saved to localStorage and can be viewed on cart.php
}

function createCartItemElement(item, index) {
  var div = document.createElement('div');
  div.className = 'cart-item';
  div.setAttribute('data-item-id', item.id);

  var colorMap = {
    'Blue': '#1e3a8a',
    'Black': '#000',
    'Gray': '#666',
    'Custom': '#999'
  };

  var colorValue = colorMap[item.color] || '#1e3a8a';

  div.innerHTML = `
    <label class="cart-checkbox-label">
      <input type="checkbox" class="cart-checkbox cart-item-checkbox" ${item.selected ? 'checked' : ''} onchange="toggleCartItem(${index}, this.checked)">
      <span class="cart-checkbox-custom"></span>
    </label>
    <img src="${item.image}" alt="${item.name}" class="cart-item-image" onerror="this.src='assets/img/unifrom.jpeg'">
    <div class="cart-item-details">
      <div class="cart-item-header">
        <span class="cart-item-name">${item.name}</span>
        <button class="cart-item-delete" onclick="removeCartItem(${index})" aria-label="Delete item">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </button>
      </div>
      <div class="cart-item-variant">
        <span class="cart-item-color" style="background-color: ${colorValue}"></span>
        <span>${item.color} / ${item.size}</span>
      </div>
      <div class="cart-item-popularity">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
        </svg>
        <span>200+ sold</span>
      </div>
      <div class="cart-item-footer">
        <span class="cart-item-price">₱${item.price.toLocaleString()}</span>
        <div class="cart-item-quantity">
          <span class="cart-item-quantity-label">Qty:</span>
          <select class="cart-quantity-select" onchange="updateCartItemQuantity(${index}, this.value)">
            ${generateQuantityOptions(item.quantity)}
          </select>
        </div>
      </div>
    </div>
  `;

  return div;
}

function generateQuantityOptions(currentQty) {
  var options = '';
  for (var i = 1; i <= 10; i++) {
    options += `<option value="${i}" ${i === currentQty ? 'selected' : ''}>${i}</option>`;
  }
  return options;
}

function toggleCartItem(index, selected) {
  if (cartItems[index]) {
    cartItems[index].selected = selected;
    saveCartItems();
    updateCartSummary();
  }
}

function toggleSelectAll(selected) {
  cartItems.forEach(function (item) {
    item.selected = selected;
  });
  saveCartItems();
  renderCartItems();
  updateCartSummary();
}

function toggleSellerItems(selected) {
  cartItems.forEach(function (item) {
    if (item.seller === 'JDE Works') {
      item.selected = selected;
    }
  });
  saveCartItems();
  renderCartItems();
  updateCartSummary();
}

function updateCartItemQuantity(index, quantity) {
  if (cartItems[index]) {
    cartItems[index].quantity = parseInt(quantity);
    saveCartItems();
    updateCartSummary();
  }
}

function removeCartItem(index) {
  if (confirm('Remove this item from cart?')) {
    cartItems.splice(index, 1);
    saveCartItems();
    renderCartItems();
    updateCartSummary();
  }
}

function updateCartSummary() {
  var selectedItems = cartItems.filter(function (item) { return item.selected; });
  var total = selectedItems.reduce(function (sum, item) {
    return sum + (item.price * item.quantity);
  }, 0);

  var totalCount = cartItems.reduce(function (sum, item) {
    return sum + item.quantity;
  }, 0);

  var totalPriceEl = document.getElementById('cartTotalPrice');
  if (totalPriceEl) {
    totalPriceEl.textContent = '₱' + total.toLocaleString();
  }

  var cartItemCountEl = document.getElementById('cartItemCount');
  if (cartItemCountEl) {
    cartItemCountEl.textContent = totalCount;
  }

  // Update navbar cart badge
  var navbarCartBadge = document.getElementById('navbarCartBadge');
  if (navbarCartBadge) {
    navbarCartBadge.textContent = totalCount;
    navbarCartBadge.style.display = totalCount > 0 ? 'block' : 'none';
  }

  // Update select all checkbox
  var selectAllCheckbox = document.getElementById('selectAllCheckbox');
  if (selectAllCheckbox) {
    var allSelected = cartItems.length > 0 && cartItems.every(function (item) { return item.selected; });
    selectAllCheckbox.checked = allSelected;
  }
}

function toggleSelectAll(selected) {
  cartItems.forEach(function (item) {
    item.selected = selected;
  });
  saveCartItems();
  renderCartItems();
  updateCartSummary();
}

function toggleSellerItems(selected) {
  cartItems.forEach(function (item) {
    if (item.seller === 'JDE Works') {
      item.selected = selected;
    }
  });
  saveCartItems();
  renderCartItems();
  updateCartSummary();
}

// Cart dropdown removed - cart icon links directly to cart.php page

// --- Cart / appointment actions ---
function bookAppointmentWithMeasurements() {
  alert('Book appointment with measurements is not yet connected to the server.');
}

// Expose functions globally for inline onclick attributes
window.openCustomization = openCustomization;
window.closeCustomization = closeCustomization;
window.openSizeGuide = openSizeGuide;
window.closeSizeGuide = closeSizeGuide;
window.switchSizeTab = switchSizeTab;
window.increaseQuantity = increaseQuantity;
window.decreaseQuantity = decreaseQuantity;
window.addToCart = addToCart;
window.addToCartWithCustomization = addToCartWithCustomization;
window.bookAppointmentWithMeasurements = bookAppointmentWithMeasurements;
window.clearSizeFilter = clearSizeFilter;
// Cart dropdown functions removed - cart icon links to cart.php