let checkoutLocationPicker;
document.addEventListener('DOMContentLoaded', function () {
    // Initialize location picker for checkout
    if (typeof PhLocationPicker !== 'undefined') {

        // Patch prototype BEFORE construction so the override fires when init() → loadProvinces() runs
        if (window._checkoutDefaultAddress) {
            const _originalLoadProvinces = PhLocationPicker.prototype.loadProvinces;
            PhLocationPicker.prototype.loadProvinces = async function () {
                const result = await _originalLoadProvinces.call(this);
                // Restore original method so future manual calls aren't affected
                PhLocationPicker.prototype.loadProvinces = _originalLoadProvinces;
                // Set the province dropdown then cascade via selectSavedAddress
                if (this.provinceSelect && window._checkoutDefaultAddress.province) {
                    this.provinceSelect.value = window._checkoutDefaultAddress.province;
                }
                await selectSavedAddress(window._checkoutDefaultAddress);
                return result;
            };
        }

        checkoutLocationPicker = new PhLocationPicker({
            provinceId: 'checkout-province',
            cityId: 'checkout-city',
            barangayId: 'checkout-barangay'
        });
    }
    // Distribution Method Selection Logic
    const distributionInputs = document.querySelectorAll('input[name="distribution_method"]');
    const shippingSection = document.getElementById('shippingSection');
    const pickupSection = document.getElementById('pickupSection');
    const distributionOptions = document.querySelectorAll('.distribution-option');

    if (distributionInputs.length > 0) {
        distributionInputs.forEach(input => {
            input.addEventListener('change', function () {
                // Update UI selection highlights
                distributionOptions.forEach(opt => opt.classList.remove('selected'));
                this.closest('.distribution-option').classList.add('selected');

                // Update Summary Calculation
                const shippingFeeEl = document.getElementById('checkout-shipping-fee');
                const totalPriceEl = document.getElementById('checkout-total-price');
                const subtotalTextEl = document.querySelector('.summary-calculations .discounted-subtotal');
                const subtotalText = subtotalTextEl ? subtotalTextEl.innerText.replace(/[^0-9.]/g, '') : '0';
                const subtotal = parseFloat(subtotalText);

                if (this.value === 'pickup') {
                    shippingSection.classList.add('d-none');
                    pickupSection.classList.remove('d-none');
                    if (shippingFeeEl) shippingFeeEl.innerText = 'N/A';
                    if (totalPriceEl) totalPriceEl.innerText = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 });

                    // Remove required from shipping fields
                    shippingSection.querySelectorAll('input, select').forEach(field => {
                        field.required = false;
                    });
                    // Add required to pickup fields, conditionally for pickup_date
                    pickupSection.querySelectorAll('input').forEach(field => {
                        if (field.name === 'pickup_date') {
                            field.required = !window._hasCustomItems;
                        }
                    });
                } else {
                    shippingSection.classList.remove('d-none');
                    pickupSection.classList.add('d-none');

                    // Add required back to shipping fields
                    shippingSection.querySelectorAll('input, select').forEach(field => {
                        field.required = true;
                    });
                    // Remove required from pickup fields
                    pickupSection.querySelectorAll('input').forEach(field => {
                        field.required = false;
                    });

                    // Recalculate Shipping Fee dynamically
                    updateShippingFee();
                }
            });
        });
    }

    // New: Dynamic Shipping Fee Listener for Address Fields
    const addressSelectors = ['checkout-province', 'checkout-city', 'checkout-barangay'];
    addressSelectors.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function () {
                const distMethod = document.querySelector('input[name="distribution_method"]:checked').value;
                if (distMethod === 'delivery') {
                    updateShippingFee();
                }
            });
        }
    });

    // Trigger initial calculation if delivery is selected on load
    const initialDist = document.querySelector('input[name="distribution_method"]:checked');
    if (initialDist && initialDist.value === 'delivery') {
        updateShippingFee();
    }

    // Listener for Street Address change
    const streetInput = document.querySelector('input[name="address"]');
    if (streetInput) {
        streetInput.addEventListener('blur', function () {
            const distMethod = document.querySelector('input[name="distribution_method"]:checked').value;
            if (distMethod === 'delivery') {
                updateShippingFee();
            }
        });
    }

    // Generic Form Validation - Shake effect on error
    const formsToValidate = document.querySelectorAll('.checkout-form, .payment-form');
    formsToValidate.forEach(form => {
        form.addEventListener('submit', function (e) {
            // Only validate elements that are visible and required
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                // Ensure the field is currently visible before validating
                if (field.offsetParent !== null) {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid', 'shake');
                        isValid = false;
                        setTimeout(() => field.classList.remove('shake'), 500);
                    } else {
                        field.classList.remove('is-invalid');
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (window.showNotification) {
                    window.showNotification('Please fill in all required fields.', 'error');
                }
            }
        });
    });

    // Initialize Flatpickr for pickup_date
    if (typeof flatpickr !== 'undefined') {
        flatpickr("input[name='pickup_date']", {
            minDate: "today",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            disableMobile: true, // Forces the styled desktop version on mobile for consistency
            disable: [
                function(date) {
                    // Disable Sundays (0 is Sunday, 1 is Monday, etc.)
                    return (date.getDay() === 0);
                }
            ]
        });
    }
});

let currentDeletingIndex = -1;

/**
 * Toggles the global deletion popover relative to the clicked button
 */
function toggleDeletePopover(event, button, index) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const popover = document.getElementById('globalDeletePopover');
    const confirmBtn = document.getElementById('confirmDeleteBtn');

    if (!popover || !confirmBtn) return;

    // If clicking the same button and popover is visible, close it
    if (currentDeletingIndex === index && !popover.classList.contains('d-none')) {
        closeAllPopovers();
        return;
    }

    currentDeletingIndex = index;
    confirmBtn.onclick = () => {
        closeAllPopovers();
        changeCheckoutQty(null, null, index, 0, true);
    };

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
    if (popover) {
        popover.classList.add('d-none');
    }
    currentDeletingIndex = -1;
}

// Quantity Change Handler for Checkout Summary
function changeCheckoutQty(event, element, index, newQty, confirmed = false) {
    if (newQty < 0) return;

    // If 0, confirm removal using custom popover
    if (newQty === 0 && !confirmed) {
        toggleDeletePopover(event, element, index);
        return;
    }

    // Show loading state if needed
    const selector = document.querySelector(`.order-item-mini[data-index="${index}"] .checkout-qty-selector`);
    if (selector) selector.style.opacity = '0.5';

    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&index=${index}&quantity=${newQty}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // We reload because multiple totals and session data change.
                // In a more complex app, we'd update DOM nodes individually.
                location.reload();
            } else {
                if (window.showNotification) {
                    window.showNotification(data.message || 'Error updating order', 'error');
                }
                if (selector) selector.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (selector) selector.style.opacity = '1';
        });
}

async function selectSavedAddress(addr) {
    if (!addr) return;

    // 1. Immediately close the modal so the user sees the form right away
    const modalEl = document.getElementById('addressSelectionModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
    }

    // Helper to ensure a specific option exists in a select (for instant display)
    const ensureOption = (select, val) => {
        if (!select || !val) return;
        let exists = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === val) { exists = true; break; }
        }
        if (!exists) {
            const opt = document.createElement('option');
            opt.value = val;
            opt.text = val.toUpperCase();
            select.appendChild(opt);
        }
        select.value = val;
        select.disabled = false;
    };

    // 2. Fill text/hidden fields IMMEDIATELY
    const addressInput = document.querySelector('input[name="address"]');
    const zipInput = document.querySelector('input[name="zip"]');
    const phoneInput = document.querySelector('input[name="phone"]');

    if (addressInput) addressInput.value = addr.addressLine || '';
    if (zipInput) zipInput.value = addr.zip || '';
    if (phoneInput) phoneInput.value = addr.receiverPhone || '';

    // 3. Handle Selects for Province/City/Barangay INSTANTLY
    const provinceSelect = document.getElementById('checkout-province');
    const citySelect = document.getElementById('checkout-city');
    const barangaySelect = document.getElementById('checkout-barangay');

    if (provinceSelect) {
        provinceSelect.value = addr.province || '';
    }

    // Fake the selection immediately so user sees it without delay
    if (citySelect && addr.city) ensureOption(citySelect, addr.city);
    if (barangaySelect && addr.barangay) ensureOption(barangaySelect, addr.barangay);

    // 4. Trigger background loads quietly without blocking
    if (provinceSelect && checkoutLocationPicker) {
        // Load cities first (background)
        checkoutLocationPicker.loadCities().then(() => {
            if (citySelect && addr.city) citySelect.value = addr.city;
            // Load barangays next (background)
            checkoutLocationPicker.loadBarangays().then(() => {
                if (barangaySelect && addr.barangay) barangaySelect.value = addr.barangay;

                // FINAL CALCULATION: After all fields are synced
                updateShippingFee();
            });
        });
    } else {
        // Just in case location picker is not ready
        updateShippingFee();
    }
}

async function updateShippingFee() {
    const provinceEl = document.getElementById('checkout-province');
    const cityEl = document.getElementById('checkout-city');
    const barangayEl = document.getElementById('checkout-barangay');
    const streetEl = document.querySelector('input[name="address"]');

    if (!provinceEl || !cityEl) return;

    const province = provinceEl.value;
    const city = cityEl.value;
    const barangay = barangayEl ? barangayEl.value : '';
    const address = streetEl ? streetEl.value : '';

    const feeEl = document.getElementById('checkout-shipping-fee');
    const totalEl = document.getElementById('checkout-total-price');

    // Find the subtotal text
    const summaryCalculations = document.querySelector('.summary-calculations');
    if (!summaryCalculations) return;
    const subtotalTextEl = summaryCalculations.querySelector('.discounted-subtotal');
    if (!subtotalTextEl) return;

    const subtotalText = subtotalTextEl.innerText.replace(/[^0-9.]/g, '');
    const subtotal = parseFloat(subtotalText);

    if (!province || !city) {
        if (feeEl) feeEl.innerText = '₱0.00';
        if (totalEl) totalEl.innerText = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
        return;
    }

    if (feeEl) feeEl.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary" role="status"></span> Calculating...';

    try {
        const response = await fetch(`../backend/shipping_api.php?province=${encodeURIComponent(province)}&city=${encodeURIComponent(city)}&barangay=${encodeURIComponent(barangay)}&address=${encodeURIComponent(address)}`);
        const data = await response.json();

        if (data.success) {
            const fee = data.shipping_fee;
            const originalFee = data.original_shipping_fee;
            const isFree = data.is_free_shipping;

            if (feeEl) {
                if (isFree && originalFee > 0) {
                    feeEl.innerHTML = `<span class="text-success fw-bold">FREE</span> <del class="text-muted ms-1">₱${originalFee.toLocaleString('en-US', { minimumFractionDigits: 0 })}</del>`;
                } else {
                    feeEl.innerText = '₱' + fee.toLocaleString('en-US', { minimumFractionDigits: 2 });
                }
            }
            if (totalEl) totalEl.innerText = '₱' + (subtotal + fee).toLocaleString('en-US', { minimumFractionDigits: 2 });
        } else {
            if (feeEl) feeEl.innerText = '₱0.00 (Pending)';
        }
    } catch (error) {
        console.error('Shipping API Error:', error);
        if (feeEl) feeEl.innerText = '₱0.00 (Network error)';
    }
}
