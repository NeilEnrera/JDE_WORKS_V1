// customization.js - behavior for customization.php

function collectCustomizationData() {
  var root = document.querySelector('.customization-page') || document;
  var data = {};
  var inputs = root.querySelectorAll('input[type="number"], textarea');
  inputs.forEach(function (el) {
    if (!el.name) return;
    data[el.name] = el.value;
  });
  return data;
}

function updateCartBadgeGlobal() {
  fetch('cart_handler.php?action=count')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      var totalCount = data.count || 0;
      var navbarCartBadge = document.getElementById('navbarCartBadge');
      if (navbarCartBadge) {
        navbarCartBadge.textContent = totalCount;
        navbarCartBadge.style.display = totalCount > 0 ? 'block' : 'none';
      }
    })
    .catch(function () { /* ignore */ });
}

function bookAppointmentWithMeasurements() {
  alert('Book appointment with measurements is not yet connected to the server.');
}

function saveCustomization() {
  const product = window.__CUSTOMIZATION_PRODUCT__;
  if (!product) {
    showNotification('Product info missing. Please go back and try again.', 'error');
    return;
  }

  const details = collectCustomizationData();

  // Validation
  const requiredFields = [];
  if (product.isUpper) requiredFields.push('neck', 'shoulder', 'chest', 'sleeve');
  if (product.isUpper || product.isLower) requiredFields.push('waist');
  if (product.isLower) requiredFields.push('hips', 'pants-length', 'thigh', 'crotch');

  for (let i = 0; i < requiredFields.length; i++) {
    const field = requiredFields[i];
    const val = details[field];
    if (!val || Number(val) <= 0) {
      const label = document.querySelector('label[for="' + field + '"]');
      const labelName = label ? label.textContent.replace(' *', '') : field;
      showNotification('Please enter a valid measurement for ' + labelName + '.', 'error');
      const el = document.getElementById(field);
      if (el) el.focus();
      return;
    }
  }

  const recommendation = getRecommendedSize(product, {
      'chest':       parseFloat(details['chest'])        || 0,
      'waist':       parseFloat(details['waist'])        || 0,
      'pants-length':parseFloat(details['pants-length']) || 0,
      'shoulder':    parseFloat(details['shoulder'])     || 0,
      'hips':        parseFloat(details['hips'])         || 0
  });

  const pricing    = getProductPriceData(product, recommendation.guideKey, recommendation.size);
  const customFee  = 100;
  const finalPrice = pricing.computedPrice + customFee;

  const cartItem = {
    id: product.id,
    name: product.name + ' (Custom)',
    price: finalPrice,
    quantity: 1,
    size: 'Custom (' + recommendation.size + ')',
    color: 'Standard',
    image: product.image || 'assets/img/unifrom.jpeg',
    category: '',
    custom: true,
    details: details
  };

  fetch('cart_handler.php?action=add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(cartItem)
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (typeof window.refreshNotificationBadges === 'function') {
            window.refreshNotificationBadges(true);
        } else {
            updateCartBadgeGlobal();
        }

        if (typeof window.refreshCartUI === 'function') {
            window.refreshCartUI();
        }
        
        if (typeof animateCartIcon === 'function') {
            animateCartIcon();
        }

        showNotification('Customization saved and added to cart!', 'success');
      } else {
        showNotification('Failed to save: ' + (data.message || 'Unknown error'), 'error');
      }
    })
    .catch(err => {
      console.error(err);
      showNotification('Error connecting to server. Please try again.', 'error');
    });
}

window.saveCustomization = saveCustomization;
window.bookAppointmentWithMeasurements = bookAppointmentWithMeasurements;

function animateCartIcon() {
  var cartIcon = document.querySelector('a[aria-label="Cart"], .cart-icon-link');
  var cartBadge = document.getElementById('navbarCartBadge');

  if (cartIcon) {
    cartIcon.style.animation = 'none';
    setTimeout(function () {
      cartIcon.style.animation = 'cartPulse 0.5s ease';
    }, 10);
  }

  if (cartBadge && cartBadge.textContent !== '0') {
    cartBadge.style.transform = 'scale(1.3)';
    cartBadge.style.transition = 'transform 0.3s ease';
    setTimeout(function () {
      cartBadge.style.transform = 'scale(1)';
    }, 300);
  }
}

document.addEventListener('DOMContentLoaded', function () {
  updateCartBadgeGlobal();

  setTimeout(function () {
    if (typeof showNotification === 'function') {
      showNotification('Need help? Check the Size Guide for accurate measurements! 📏', 'success');
    }
  }, 1000);

  var measurementInputs = document.querySelectorAll('.customization-form input[type="number"]');
  var previewCard = document.querySelector('.customization-preview-card');

  if (previewCard && !document.querySelector('.size-feedback-badge')) {
    var badge = document.createElement('div');
    badge.className = 'size-feedback-badge';
    badge.innerHTML = 'FITTING...';
    previewCard.style.position = 'relative';
    previewCard.appendChild(badge);
  }

  measurementInputs.forEach(function (input) {
    input.addEventListener('input', livePreviewUpdate);
  });

  livePreviewUpdate();
});

const SIZE_GUIDES = window.SIZE_GUIDES || {};

function cmToInch(cm) {
    return cm / 2.54;
}

function resolveGuideKey(productObj) {
    const cat  = (productObj.category || '').toLowerCase();
    const name = (productObj.name     || '').toLowerCase();

    if (cat.includes('nursing') || cat.includes('bsn')) return 'Nursing';
    if (cat.includes('allied') || cat.includes('psych') || cat.includes('medtech') || cat.includes('pharmacy') || cat.includes('biology')) return 'Allied Health';
    if (cat.includes('senior high') || cat.includes('shs')) return 'SHS';
    if (cat.includes('physical education') || cat.includes('p.e.') || cat.includes(' pe ') || cat === 'pe') return 'PE';
    if (cat.includes('culinary') || cat.includes('hospitality') || cat.includes('chef')) return 'Culinary';
    if (cat.includes('tourism') || cat.includes('psychology') || cat.includes('bs tourism')) return 'Tourism';

    if (name.includes('nursing') || name.includes('bsn')) return 'Nursing';
    if (name.includes('allied') || name.includes('psych') || name.includes('medtech')) return 'Allied Health';
    if (name.includes('shs') || name.includes('senior high')) return 'SHS';
    if (name.includes(' pe ') || name.includes('physical education')) return 'PE';
    if (name.includes('culinary') || name.includes('chef')) return 'Culinary';
    if (name.includes('tourism')) return 'Tourism';

    return 'Tourism';
}

function getRecommendedSize(productObj, measurements) {
    const guideKey = resolveGuideKey(productObj);
    const config   = SIZE_GUIDES[guideKey];
    if (!config) return { guideKey, size: 'Custom', surcharge: 0 };

    // Define weights for different measurement types
    const weights = {
        'chest': 3.0, 'bust': 3.0, 'tchest': 3.0,
        'waist': 2.5, 'pwaist': 2.5, 'hips': 2.5,
        'shoulder': 1.5, 'neck': 1.0,
        'length': 0.5, 'tlength': 0.5, 'sleeve': 0.5, 'pants-length': 0.5
    };

    let bestSize = config.guide[0];
    let minWeightedDiff = Infinity;

    config.guide.forEach(sizeOption => {
        let totalWeightedDiff = 0;
        let totalWeight = 0;

        for (const [guideAttr, inputId] of Object.entries(config.mapping)) {
            const inputVal = parseFloat(measurements[inputId]);
            if (!isNaN(inputVal) && inputVal > 0) {
                const guideVal = sizeOption[guideAttr];
                const weight   = weights[guideAttr] || 1.0;
                
                let adjustedInput = inputVal;
                // Special case for PE chest mapping if stored as half-chest
                if (guideKey === 'PE' && guideAttr === 'tchest' && inputVal > 30) {
                     adjustedInput = inputVal / 2;
                }

                const diff = Math.abs(adjustedInput - guideVal);
                
                // Penalty for being too small: If input is significantly larger than guide, 
                // increase the weight of this difference to avoid recommending a tight fit.
                const sizePenalty = (adjustedInput > guideVal) ? 1.5 : 1.0;

                totalWeightedDiff += (diff * weight * sizePenalty);
                totalWeight += (weight * sizePenalty);
            }
        }

        if (totalWeight > 0) {
            const avgDiff = totalWeightedDiff / totalWeight;
            if (avgDiff < minWeightedDiff) {
                minWeightedDiff = avgDiff;
                bestSize = sizeOption;
            }
        }
    });

    return { 
        guideKey, 
        size: bestSize.size,
        surcharge: bestSize.surcharge
    };
}

function validateSanity(id, val) {
    const value = parseFloat(val);
    const limits = {
        'neck':         { min: 12, max: 22 },
        'shoulder':     { min: 13, max: 25 },
        'chest':        { min: 30, max: 65 },
        'waist':        { min: 24, max: 60 },
        'hips':         { min: 30, max: 65 },
        'sleeve':       { min: 7,  max: 30 },
        'pants-length': { min: 30, max: 50 },
        'thigh':        { min: 15, max: 35 },
        'crotch':       { min: 20, max: 40 }
    };
    const limit = limits[id];
    if (!limit) return true;
    return value >= limit.min && value <= limit.max;
}

/**
 * Checks for inconsistent proportions (e.g. Chest 50 but Waist 20)
 */
function validateProportions(measurements) {
    const chest = parseFloat(measurements['chest']) || 0;
    const waist = parseFloat(measurements['waist']) || 0;
    const shoulder = parseFloat(measurements['shoulder']) || 0;

    const issues = [];

    if (chest > 0 && waist > 0) {
        const diff = Math.abs(chest - waist);
        if (diff > 20) issues.push('The difference between Chest and Waist seems unusually large.');
    }

    if (shoulder > 0 && chest > 0) {
        // Typical shoulder is 40-50% of chest circumference
        const ratio = shoulder / chest;
        if (ratio < 0.3 || ratio > 0.6) issues.push('Shoulder width seems inconsistent with Chest size.');
    }

    return issues;
}

function getProductPriceData(productObj, guideKey, sizeName) {
    let basePrice = parseFloat(productObj.price);
    
    // Check for size-specific pricing in sizeStocks
    const stocks = productObj.sizeStocks || {};
    const sizeData = stocks[sizeName];
    if (sizeData && typeof sizeData === 'object' && sizeData.price && parseFloat(sizeData.price) > 0) {
        basePrice = parseFloat(sizeData.price);
    }

    const config = SIZE_GUIDES[guideKey];
    if (!config) return { unitBase: basePrice, surcharge: 0, computedPrice: basePrice };

    const sizeConfig = config.guide.find(s => s.size === sizeName);
    const surcharge = sizeConfig ? (sizeConfig.surcharge || 0) : 0;

    return {
        unitBase: basePrice,
        surcharge: surcharge,
        computedPrice: basePrice + surcharge
    };
}

function livePreviewUpdate() {
    const measurements = collectCustomizationData();
    const product = window.__CUSTOMIZATION_PRODUCT__;
    
    if (product) {
        // Check if any numeric input has a value > 0
        let hasInput = false;
        for (const [key, val] of Object.entries(measurements)) {
            const numVal = parseFloat(val);
            if (!isNaN(numVal) && numVal > 0) {
                hasInput = true;
                break;
            }
        }
        
        const sizeEl = document.getElementById('suggested-size');
        const baseRow = document.getElementById('base-price-row');
        const basePriceEl = document.getElementById('base-price');
        const surchargeRow = document.getElementById('plus-size-surcharge-row');
        const surchargeEl = document.getElementById('plus-size-surcharge');
        const customFeeRow = document.getElementById('custom-fee-row');
        const divider = document.getElementById('summary-divider');
        const totalRow = document.getElementById('total-price-row');
        const totalEl = document.getElementById('total-price');
        const placeholder = document.getElementById('summary-placeholder');
        const surchargeNote = document.getElementById('surcharge-note');
        const stockNote = document.getElementById('stock-note');

        if (hasInput) {
            let unusualFields = [];
            
            Object.entries(measurements).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (!el) return;
                
                el.style.borderColor = ''; 
                el.style.boxShadow = '';

                const numVal = parseFloat(val);
                const strVal = String(val).trim();
                
                if (numVal > 0) {
                    const isSingleDigit = strVal.length <= 1;
                    if (!validateSanity(id, val)) {
                        if (!isSingleDigit) {
                            unusualFields.push(id);
                            // Highlight with amber/gold to indicate "Unusual" but not necessarily "Invalid"
                            el.style.borderColor = '#d6b25e'; 
                            el.style.boxShadow = '0 0 8px rgba(214, 178, 94, 0.4)';
                        }
                    }
                }
            });

            const proportionIssues = validateProportions(measurements);
            const recommendation = getRecommendedSize(product, measurements);
            const pricing = getProductPriceData(product, recommendation.guideKey, recommendation.size);
            
            if (sizeEl) {
                sizeEl.textContent = recommendation.size;
                sizeEl.style.color = 'var(--primary-gold)';
                sizeEl.style.fontWeight = 'bold';
            }

            if (surchargeNote) {
                if (recommendation.surcharge > 0) {
                    surchargeNote.innerHTML = `<i class="bi bi-info-circle-fill"></i> +₱${recommendation.surcharge} Plus-Size Surcharge for size ${recommendation.size}`;
                    surchargeNote.style.display = 'block';
                } else {
                    surchargeNote.style.display = 'none';
                }
            }

            if (stockNote) {
                const stocks = product.sizeStocks || {};
                const stockCount = stocks[recommendation.size] || 0;
                if (stockCount <= 0) {
                    stockNote.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> Note: Standard <strong>${recommendation.size}</strong> is out of stock. We will process this as a custom order.`;
                    stockNote.style.display = 'block';
                } else {
                    stockNote.style.display = 'none';
                }
            }

            if (basePriceEl) basePriceEl.textContent = '₱' + pricing.unitBase.toLocaleString();
            if (baseRow) baseRow.style.display = 'flex';
            if (customFeeRow) customFeeRow.style.display = 'flex';
            if (divider) divider.style.display = 'block';
            if (totalRow) totalRow.style.display = 'flex';
            if (placeholder) placeholder.style.display = 'none';

            if (surchargeRow && surchargeEl) {
                if (pricing.surcharge > 0) {
                    surchargeRow.style.display = 'flex';
                    surchargeEl.textContent = '+₱' + pricing.surcharge;
                } else {
                    surchargeRow.style.display = 'none';
                }
            }

            if (totalEl) {
                const customFee = 100; // Consistent with saveCustomization
                const total = pricing.unitBase + customFee + pricing.surcharge;
                totalEl.textContent = '₱' + total.toLocaleString();
            }

            // Warning System for Unusual/Inconsistent Measurements
            const hasIssues = unusualFields.length > 0 || proportionIssues.length > 0;
            if (hasIssues && typeof showNotification === 'function') {
                if (!window.__integrityNotified) {
                    let msg = 'Some measurements seem unusual. ';
                    if (proportionIssues.length > 0) msg += proportionIssues[0];
                    else msg += 'Please double-check the highlighted fields.';
                    
                    showNotification(msg, 'warning');
                    window.__integrityNotified = true;
                    setTimeout(() => window.__integrityNotified = false, 10000); 
                }
            }

        } else {
            if (sizeEl) sizeEl.textContent = '--';
            if (baseRow) baseRow.style.display = 'none';
            if (surchargeRow) surchargeRow.style.display = 'none';
            if (customFeeRow) customFeeRow.style.display = 'none';
            if (divider) divider.style.display = 'none';
            if (totalRow) totalRow.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
            if (surchargeNote) surchargeNote.style.display = 'none';
            if (stockNote) stockNote.style.display = 'none';
        }
    }

    // --- Visual Scaling ---
    const chest    = parseFloat(measurements['chest'])        || 0;
    const waist    = parseFloat(measurements['waist'])        || 0;
    const length   = parseFloat(measurements['pants-length']) || 0;
    const shoulder = parseFloat(measurements['shoulder'])     || 0;
    const hips     = parseFloat(measurements['hips'])         || 0;

    var chestScale = chest > 0 ? 0.85 + (chest / 200) : 1;
    var waistScale = waist > 0 ? 0.85 + (waist / 200) : 1;
    var lengthScale = length > 0 ? 0.9 + (length / 250) : 1;
    var shoulderScale = shoulder > 0 ? 0.95 + (shoulder / 300) : 1;
    var hipsScale = hips > 0 ? 0.9 + (hips / 250) : 1;

    chestScale = Math.min(Math.max(chestScale, 0.9), 1.15);
    waistScale = Math.min(Math.max(waistScale, 0.9), 1.15);
    lengthScale = Math.min(Math.max(lengthScale, 0.95), 1.1);
    shoulderScale = Math.min(Math.max(shoulderScale, 0.98), 1.05);
    hipsScale = Math.min(Math.max(hipsScale, 0.95), 1.08);

    var previewImg = document.querySelector('.customization-preview-image');
    if (previewImg) {
      var hScale = 1;
      if (chest > 0 || waist > 0 || shoulder > 0 || hips > 0) {
        hScale = (chestScale * 0.4) + (waistScale * 0.3) + (shoulderScale * 0.15) + (hipsScale * 0.15);
      }
      previewImg.style.transform = 'scale(' + hScale + ', ' + lengthScale + ')';
    }

    var previewCard = document.querySelector('.customization-preview-card');
    if (previewCard && (chest || waist || length)) {
      previewCard.classList.add('fitting');
      var badge = previewCard.querySelector('.size-feedback-badge');
      if (badge) {
        badge.style.display = 'block';
        badge.innerHTML = 'FITTING...';
      }
      clearTimeout(window.__fittingTimeout);
      window.__fittingTimeout = setTimeout(function () {
        previewCard.classList.remove('fitting');
      }, 1000);
    }
}

function openSizeGuide() {
  var modal = document.getElementById('sizeGuideModal');
  var overlay = document.getElementById('sizeGuideOverlay');
  if (!modal || !overlay) return;
  overlay.style.display = 'block';
  setTimeout(function () { modal.classList.add('show'); overlay.style.opacity = '1'; }, 10);
  document.body.style.overflow = 'hidden';
}

function closeSizeGuide() {
  var modal = document.getElementById('sizeGuideModal');
  var overlay = document.getElementById('sizeGuideOverlay');
  if (!modal || !overlay) return;
  modal.classList.remove('show');
  overlay.style.opacity = '0';
  setTimeout(function () { overlay.style.display = 'none'; document.body.style.overflow = ''; }, 400);
}

function openImagePreview(src, altText) {
  var modal = document.getElementById('imagePreviewModal');
  var modalImg = document.getElementById("previewImage");
  var captionText = document.getElementById("caption");
  if (!modal || !modalImg) return;
  modal.style.display = "block";
  modalImg.src = src;
  if (captionText && altText) captionText.innerHTML = altText;
}

function closeImagePreview() {
  var modal = document.getElementById('imagePreviewModal');
  if (modal) modal.style.display = "none";
}

window.addEventListener('click', function (e) {
  var sizeGuideModal = document.getElementById('sizeGuideModal');
  if (e.target === sizeGuideModal && !sizeGuideModal.classList.contains('side-drawer')) closeSizeGuide();
  var imagePreviewModal = document.getElementById('imagePreviewModal');
  if (e.target === imagePreviewModal) closeImagePreview();
});

window.openSizeGuide = openSizeGuide;
window.closeSizeGuide = closeSizeGuide;
window.openImagePreview = openImagePreview;
window.closeImagePreview = closeImagePreview;
