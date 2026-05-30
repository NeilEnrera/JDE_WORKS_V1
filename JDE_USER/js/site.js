(function () {
  var prev = document.querySelector('.product-scroller .prev');
  var next = document.querySelector('.product-scroller .next');
  var track = document.querySelector('.product-track');
  var dotsBox = document.querySelector('.product-dots');
  if (!prev || !next || !track) return;
  var step = 320;
  function recalcStep() {
    var first = track.querySelector('.product-item');
    if (!first) return;
    var styles = window.getComputedStyle(track);
    var gap = parseInt(styles.columnGap || styles.gap || '24', 10) || 24;
    step = first.offsetWidth + gap;
  }
  recalcStep();

  function updateArrows() {
    var maxScroll = track.scrollWidth - track.clientWidth - 2;
    prev.disabled = track.scrollLeft <= 0;
    next.disabled = track.scrollLeft >= maxScroll;
    prev.style.opacity = prev.disabled ? 0.4 : 1;
    next.style.opacity = next.disabled ? 0.4 : 1;
    prev.style.pointerEvents = prev.disabled ? 'none' : 'auto';
    next.style.pointerEvents = next.disabled ? 'none' : 'auto';
  }

  function buildDots() {
    if (!dotsBox) return;
    dotsBox.innerHTML = '';
    var items = track.querySelectorAll('.product-item');
    items.forEach(function (_, i) {
      var dot = document.createElement('span');
      dot.className = 'product-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', function () {
        var offset = i * step;
        track.scrollTo({ left: offset, behavior: 'smooth' });
      });
      dotsBox.appendChild(dot);
    });
  }

  function updateDots() {
    if (!dotsBox) return;
    var dots = dotsBox.querySelectorAll('.product-dot');
    var index = Math.round(track.scrollLeft / step);
    dots.forEach(function (d, i) { d.classList.toggle('active', i === index); });
  }

  prev.addEventListener('click', function () { track.scrollBy({ left: -step, behavior: 'smooth' }); });
  next.addEventListener('click', function () { track.scrollBy({ left: step, behavior: 'smooth' }); });
  track.addEventListener('scroll', function () { updateArrows(); updateDots(); }, { passive: true });
  window.addEventListener('resize', function () {
    recalcStep();
    updateArrows();
    buildDots();
    updateDots();
  });
  buildDots();
  updateArrows();
  updateDots();
})();

(function () {
  var sections = ['Services', 'Products', 'About', 'Contact'].map(function (id) {
    return document.getElementById(id);
  });
  var links = Array.prototype.slice.call(document.querySelectorAll('.navbar-nav .nav-link'));
  if (sections.length === 0 || links.length === 0) return;
  function onScroll() {
    var scrollPos = window.scrollY + 120;
    var currentIndex = 0;
    sections.forEach(function (sec, idx) {
      if (sec && sec.offsetTop <= scrollPos) currentIndex = idx;
    });
    links.forEach(function (a) { a.classList.remove('active'); });
    if (links[currentIndex]) links[currentIndex].classList.add('active');
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

// Global site behaviors
(function () {
  // Unified Logout Handler
  document.addEventListener('click', function (e) {
    var link = e.target.closest('.logout-link');
    if (!link) return;

    // Prevent interference with the actual logout process inside the modal
    if (link.id === 'logoutConfirmBtn' || link.closest('.modal-footer')) return;

    var modalEl = document.getElementById('logoutModal') || document.getElementById('logoutConfirmModal');
    if (modalEl) {
      e.preventDefault();
      try {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      } catch (err) {
        console.warn('Bootstrap modal error, falling back to direct logout:', err);
        window.location.href = 'logout.php';
      }
    }
  }, false);

  // Guest Access Restrictions
  document.addEventListener('click', function (e) {
    // Selectors for final submission actions (require login)
    var finalActionSelectors = [
      '.btn-checkout',           // Cart page "Proceed to Checkout"
      '.submit-btn',            // Appointment form button
      '#sendMessage',           // Chat send button
      '.personal-nav-link:not(.logout-link)', // Profile navigation links
      '#startExploringBtn'      // Wishlist "Start Exploring" button
    ];

    var target = e.target.closest(finalActionSelectors.join(','));
    if (target && !window.isLoggedIn) {
      e.preventDefault();
      e.stopPropagation();

      var message = 'Please log in to proceed.';
      if (target.classList.contains('btn-checkout')) message = 'Please log in to complete your purchase.';
      if (target.classList.contains('submit-btn')) message = 'Please log in to book your appointment.';
      if (target.id === 'sendMessage') message = 'Please log in to send a message.';
      if (target.id === 'startExploringBtn' || target.classList.contains('btn-premium-action')) {
         message = 'Please log in to start exploring and saving your favorite pieces.';
      }

      var modalEl = document.getElementById('loginRequiredModal');
      if (modalEl) {
        var msgEl = document.getElementById('loginRequiredMessage');
        if (msgEl) msgEl.textContent = message;
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      } else {
        alert(message);
        window.location.href = 'login.php';
      }
    }
  }, true); // Use capture to intercept before other handlers

  // Enable hover for profile and cart menus
  document.querySelectorAll('.dropdown-hover').forEach(function (dd) {
    var toggle = dd.querySelector('[data-bs-toggle="dropdown"]') ||
      dd.querySelector('#profileMenuButton') ||
      dd.querySelector('#cartDropdown') ||
      dd.querySelector('a[aria-label="Cart"]');

    if (!toggle) return;
    var dropdown = bootstrap.Dropdown.getOrCreateInstance(toggle);

    // Hover logic: Show on enter, Hide on leave
    var hideTimeout;

    // Hover logic: Show on enter, Hide on leave (with delay for stability)
    dd.addEventListener('mouseenter', function () {
      clearTimeout(hideTimeout);
      if (!toggle.classList.contains('show')) {
        dropdown.show();
      }
    });

    dd.addEventListener('mouseleave', function () {
      hideTimeout = setTimeout(function () {
        dropdown.hide();
      }, 300);
    });

    // Keep menu open while hovering menu content
    var menu = dd.querySelector('.dropdown-menu');
    if (menu) {
      menu.addEventListener('mouseenter', function () {
        clearTimeout(hideTimeout);
      });
      menu.addEventListener('mouseleave', function () {
        hideTimeout = setTimeout(function () {
          dropdown.hide();
        }, 300);
      });
    }
  });
})();

// Redundant localStorage badge updater removed in favor of js/notifications.js unified system



// Universal utility to show notifications (toast style)
function showNotification(message, type) {
  // Remove existing notification if any
  var existingNotification = document.querySelector('.cart-notification');
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create notification element
  var notification = document.createElement('div');
  notification.className = 'cart-notification cart-notification-' + type;
  notification.innerHTML = message;

  // Add to body
  document.body.appendChild(notification);

  // Show notification
  setTimeout(function () {
    notification.classList.add('show');
  }, 10);

  // Hide after 4 seconds (slightly longer for guidance)
  setTimeout(function () {
    notification.classList.remove('show');
    setTimeout(function () {
      notification.remove();
    }, 300);
  }, 4000);
}

window.showNotification = showNotification;
