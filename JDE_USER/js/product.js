// Products page functionality
document.addEventListener('DOMContentLoaded', function () {
  setupHeroAnimations();
  setupProductAnimations();
  setupSearchFunctionality();
  setupFilterSidebar();
  setupFilterFunctionality();
  setupProductListNavigation();
  setupProductListSearch();

  // Initial filter/pagination application
  if (typeof window.applyFilters === 'function') {
    window.applyFilters();
  }
});

// Pagination State
let currentPage = 1;
const itemsPerPage = 10;
let productObserver = null;

/**
 * Handles page navigation
 */
function changePage(page) {
  if (page < 1) return;
  currentPage = page;
  if (typeof window.applyFilters === 'function') {
    window.applyFilters(false); // false means don't reset to page 1
  }

  // Smooth scroll to top of products
  const mainContent = document.querySelector('.products-main');
  if (mainContent) {
    const offset = mainContent.getBoundingClientRect().top + window.pageYOffset - 120;
    window.scrollTo({ top: offset, behavior: 'smooth' });
  }
}
window.changePage = changePage;

/**
 * Setup Product Entrance Animations using a single IntersectionObserver
 */
function setupProductAnimations() {
  if (productObserver) {
    // Re-observe all items if observer already exists
    const items = document.querySelectorAll('.product-item');
    items.forEach(item => productObserver.observe(item));
    return;
  }

  const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
  productObserver = new IntersectionObserver((entries) => {
    let delay = 0;
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const el = entry.target;
        if (!el.classList.contains('animated')) {
          el.style.transitionDelay = (delay * 0.1) + 's';
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
          el.classList.add('animated');
          delay++;
        }
        // Once animated, we can stop observing this specific element
        productObserver.unobserve(el);
      }
    });
  }, observerOptions);

  const items = document.querySelectorAll('.product-item');
  items.forEach(item => {
    item.style.opacity = '0';
    item.style.transform = 'translateY(30px)';
    item.style.transition = 'opacity 0.8s cubic-bezier(0.165, 0.84, 0.44, 1), transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1)';
    productObserver.observe(item);
  });
}

function setupHeroAnimations() {
  const elements = ['.hero-badge', '.products-title-new', '.products-subtitle'];
  elements.forEach((selector, index) => {
    const el = document.querySelector(selector);
    if (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'all 0.8s cubic-bezier(0.165, 0.84, 0.44, 1)';
      setTimeout(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      }, 100 * (index + 1));
    }
  });
}

/**
 * Sidebar Navigation (Scroll to product)
 */
function scrollToProduct(productId) {
  const productElement = document.getElementById('product-' + productId);
  if (productElement) {
    // If element is hidden (on another page), we need to go to that page first
    // This is a future enhancement: would require finding index of product in filtered list
    productElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    productElement.style.boxShadow = '0 0 0 4px rgba(214, 163, 71, 0.5)';
    setTimeout(() => productElement.style.boxShadow = '', 2000);
  }
}
window.scrollToProduct = scrollToProduct;

/**
 * Search Integration
 */
function setupSearchFunctionality() {
  const searchInput = document.getElementById('productSearch');
  if (!searchInput) return;

  searchInput.addEventListener('input', () => {
    if (typeof window.applyFilters === 'function') {
      window.applyFilters(true); // Reset to page 1 on search
    }
  });

  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') e.preventDefault();
  });
}

/**
 * Filter Sidebar Toggle Logic
 */
function setupFilterSidebar() {
  const filterHeaders = document.querySelectorAll('.filter-header');
  filterHeaders.forEach(header => {
    header.addEventListener('click', function () {
      const content = this.nextElementSibling;
      if (content && content.classList.contains('filter-content')) {
        content.classList.toggle('hidden');
        this.classList.toggle('active');
      }
    });
    // Init expanded
    const content = header.nextElementSibling;
    if (content) {
      content.classList.remove('hidden');
      header.classList.add('active');
    }
  });
}

/**
 * Main Filtering Engine
 */
function setupFilterFunctionality() {
  const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
  const clearFiltersBtn = document.getElementById('clearFilters');
  const priceSlider = document.getElementById('priceRange');
  const priceValue = document.getElementById('priceRangeValue');
  const allProductItems = document.querySelectorAll('.product-item');
  const allSections = document.querySelectorAll('.products-section');
  const noResults = document.getElementById('noResults');

  filterCheckboxes.forEach(cb => cb.addEventListener('change', () => applyFilters(true)));

  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', () => {
      filterCheckboxes.forEach(cb => cb.checked = false);
      if (priceSlider) {
        priceSlider.value = 5000;
        if (priceValue) priceValue.textContent = '5000';
      }
      applyFilters(true);
    });
  }

  if (priceSlider) {
    priceSlider.addEventListener('input', function () {
      if (priceValue) priceValue.textContent = this.value;
      applyFilters(true);
    });
  }

  function applyFilters(resetPage = true) {
    if (resetPage) currentPage = 1;

    const activeFilters = {
      type: [],
      fit: [],
      maxPrice: priceSlider ? parseInt(priceSlider.value) : 5000,
      search: document.getElementById('productSearch') ? document.getElementById('productSearch').value.toLowerCase().trim() : ''
    };

    filterCheckboxes.forEach(cb => {
      if (cb.checked) {
        const type = cb.getAttribute('data-filter-type');
        if (activeFilters[type]) activeFilters[type].push(cb.value);
      }
    });

    let matchingItems = [];
    allProductItems.forEach(item => {
      const itemType = item.getAttribute('data-type') || '';
      const itemFit = item.getAttribute('data-fit') || '';
      const itemPrice = parseInt(item.getAttribute('data-price')) || 0;
      const itemName = item.getAttribute('data-name') || '';
      const itemText = item.textContent.toLowerCase();

      const typeMatch = activeFilters.type.length === 0 || activeFilters.type.includes(itemType);
      const fitMatch = activeFilters.fit.length === 0 || activeFilters.fit.includes(itemFit);
      const priceMatch = itemPrice <= activeFilters.maxPrice;
      const searchMatch = activeFilters.search === '' || itemName.includes(activeFilters.search) || itemText.includes(activeFilters.search);

      if (typeMatch && fitMatch && priceMatch && searchMatch) {
        matchingItems.push(item);
      } else {
        item.style.display = 'none';
        item.classList.remove('animated'); // Reset animation state for hidden items
      }
    });

    // Handle Pagination Slice
    const totalMatching = matchingItems.length;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;

    matchingItems.forEach((item, index) => {
      if (index >= startIndex && index < endIndex) {
        item.style.display = '';
        // If it was already animated, we keep it visible. 
        // If not, we set it up for animation.
        if (!item.classList.contains('animated')) {
          item.style.opacity = '0';
          item.style.transform = 'translateY(30px)';
        }
      } else {
        item.style.display = 'none';
        item.classList.remove('animated');
      }
    });

    // Re-observe items for animation
    if (productObserver) {
      matchingItems.forEach(item => {
        if (item.style.display !== 'none' && !item.classList.contains('animated')) {
          productObserver.observe(item);
        }
      });
    }

    // Update Section Visibility
    allSections.forEach(section => {
      const sectionGrid = section.querySelector('.products-grid');
      const hasVisible = matchingItems.some(item => sectionGrid.contains(item));
      const anyFilterActive = activeFilters.type.length > 0 || activeFilters.fit.length > 0 || activeFilters.search !== '';
      section.style.display = (hasVisible || !anyFilterActive) ? '' : 'none';
    });

    // No Results UI
    if (noResults) noResults.style.display = totalMatching === 0 ? 'block' : 'none';

    renderPagination(totalMatching);
  }

  function renderPagination(totalItems) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }

    let html = `
      <button class="pagination-btn prev-next ${currentPage === 1 ? 'disabled' : ''}" 
              onclick="changePage(${currentPage - 1})" aria-label="Previous page">
        <i class="bi bi-chevron-left"></i> Previous
      </button>
    `;

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    if (startPage > 1) {
      html += `<button class="pagination-btn" onclick="changePage(1)" aria-label="Page 1">1</button>`;
      if (startPage > 2) html += `<span class="pagination-ellipsis px-2">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
      html += `
        <button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                onclick="changePage(${i})" aria-label="Page ${i}">${i}</button>
      `;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) html += `<span class="pagination-ellipsis px-2">...</span>`;
      html += `<button class="pagination-btn" onclick="changePage(${totalPages})" aria-label="Page ${totalPages}">${totalPages}</button>`;
    }

    html += `
      <button class="pagination-btn prev-next ${currentPage === totalPages ? 'disabled' : ''}" 
              onclick="changePage(${currentPage + 1})" aria-label="Next page">
        Next <i class="bi bi-chevron-right"></i>
      </button>
    `;

    container.innerHTML = html;
  }

  window.applyFilters = applyFilters;
}

// Stubs for remaining functions
function setupProductListNavigation() { }
function setupProductListSearch() { }
function orderProduct(id) { window.location.href = 'ordering.php?product=' + id; }
