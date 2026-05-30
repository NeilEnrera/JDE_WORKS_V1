function showPane(paneId, btn) {
    document.querySelectorAll('.profile-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelectorAll('.personal-nav-list .personal-nav-link').forEach(b => b.classList.remove('active'));
    const targetPane = document.getElementById(paneId);
    if (targetPane) targetPane.classList.add('active');
    let activeBtn = btn;
    if (!activeBtn) {
        let btnId = 'nav-' + paneId;
        if (paneId === 'account') btnId = 'nav-overview';
        activeBtn = document.getElementById(btnId);
    }
    if (activeBtn) activeBtn.classList.add('active');

    if (paneId === 'addresses') loadAddresses();

    // Update URL to persist on refresh
    const url = new URL(window.location);
    url.searchParams.set('pane', paneId);
    window.history.replaceState({}, '', url);
}

function openApptModal(id, name, service, date, time, status, note, booked, isBlocked) {
    if (isBlocked) {
        const rescheduleModalEl = document.getElementById('rescheduleWarningModal');
        if (rescheduleModalEl) {
            new bootstrap.Modal(rescheduleModalEl).show();
            return;
        }
    }

    document.getElementById('apptDetailId').textContent = 'Booking #' + id;
    document.getElementById('apptDetailName').textContent = name;
    document.getElementById('apptDetailService').textContent = service;
    document.getElementById('apptDetailDate').textContent = date;
    document.getElementById('apptDetailTime').textContent = time;
    document.getElementById('apptDetailBooked').textContent = booked;

    // Status badge
    const statusMap = {
        'Pending': ['status-pending', 'Pending'],
        'Confirmed': ['status-approve', 'Confirmed'],
        'Cancelled': ['status-reject', 'Cancelled'],
        'Completed': ['status-completed', 'Completed'],
    };
    const [cls, label] = statusMap[status] || ['', status];
    document.getElementById('apptDetailStatus').innerHTML =
        `<span class="status-badge ${cls}">${label}</span>`;

    // Note row visibility
    const noteRow = document.getElementById('apptDetailNoteRow');
    if (note && note.trim()) {
        document.getElementById('apptDetailNote').textContent = note;
        noteRow.style.display = '';
    } else {
        noteRow.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('apptDetailModal')).show();
}

function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }
}

function openReviewModal(slug) {
    // Existing reviews.js should handle the modal logic, 
    // but if it's called from here, ensure it exists.
    if (window.initializeReviewModal) {
        window.initializeReviewModal(slug);
    }
}

function updateFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.innerText = input.files[0].name;
        display.classList.remove('text-muted');
        display.classList.add('text-dark', 'fw-bold');
    }
}

function syncRescheduleDismissal() {
    fetch('appointment_api.php?action=dismiss_reschedule_notice', { method: 'POST' })
        .catch(err => console.error('Failed to sync dismissal:', err));
}

let addressModal;
let locationPicker;
let allAddresses = [];
let profileSuccessModal;
let deleteConfirmModal;
let addressToDelete = null;

function showSuccess(title, message) {
    if (!profileSuccessModal) profileSuccessModal = new bootstrap.Modal(document.getElementById('profileSuccessModal'));
    const successTitle = document.getElementById('profileSuccessModalTitle');
    const successText = document.getElementById('profileSuccessModalText');
    if (successTitle) successTitle.textContent = title;
    if (successText) successText.textContent = message;
    profileSuccessModal.show();
}

function showError(title, message) {
    const errorModal = new bootstrap.Modal(document.getElementById('profileErrorModal'));
    const errorText = document.getElementById('profileErrorModalText');
    if (errorText) errorText.textContent = message;
    errorModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const addrModalEl = document.getElementById('addressModal');
    if (addrModalEl) addressModal = new bootstrap.Modal(addrModalEl);

    profileSuccessModal = new bootstrap.Modal(document.getElementById('profileSuccessModal'));
    deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    
    locationPicker = new PhLocationPicker({
        provinceId: 'province',
        cityId: 'city',
        barangayId: 'barangay'
    });

    // Handle initial modals from PHP
    let activeModalInstance = null;

    if (window.rescheduleNotice) {
        const rescheduleModalEl = document.getElementById('rescheduleWarningModal');
        if (rescheduleModalEl) {
            activeModalInstance = new bootstrap.Modal(rescheduleModalEl);
            activeModalInstance.show();
        }
    }

    if (!activeModalInstance && window.successMessage) {
        const urlParams = new URLSearchParams(window.location.search);
        const pane = urlParams.get('pane');
        const msg = window.successMessage.toLowerCase();
        let title = 'Profile Updated';
        if (pane === 'security' || msg.includes('password')) title = 'Security Updated';
        
        showSuccess(title, window.successMessage);
        activeModalInstance = profileSuccessModal;
    }

    if (!activeModalInstance && window.errorMessage) {
        showError('Application Error', window.errorMessage);
    }
});

function loadAddresses() {
    const container = document.getElementById('address-list-container');
    if (!container) return;
    fetch('address_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allAddresses = data.addresses;
                if (data.addresses.length === 0) {
                    container.innerHTML = `<div class="text-center py-5"><i class="bi bi-geo-alt text-muted" style="font-size: 3rem;"></i><p class="text-muted mt-2">No addresses saved yet.</p></div>`;
                    return;
                }
                container.innerHTML = data.addresses.map(addr => `
                    <div class="address-card${addr.isDefault == 1 ? ' address-card-default' : ''}">
                        <div class="address-card-header">
                            <div>
                                <span class="receiver-info">${addr.receiverName}</span>
                                <span class="receiver-phone">(${addr.receiverPhone})</span>
                                ${addr.isDefault == 1
                                    ? `<span class="badge bg-success ms-2" style="font-size:11px;"><i class="bi bi-check-circle me-1"></i>Default</span>`
                                    : `<a href="javascript:void(0)" class="address-action-link ms-2" style="font-size:12px;" onclick="setDefaultAddress(${addr.addressID})"><i class="bi bi-star me-1"></i>Set as Default</a>`
                                }
                            </div>
                            <div class="address-actions">
                                <a href="javascript:void(0)" class="address-action-link" onclick="editAddress(${addr.addressID})">Edit</a>
                                <a href="javascript:void(0)" class="address-action-link address-action-delete" onclick="prepareDelete(${addr.addressID})">Delete</a>
                            </div>
                        </div>
                        <div class="address-line">
                            ${addr.addressLine}, ${addr.barangay}<br>
                            ${addr.city}, ${addr.province}, ${addr.zip}
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(() => { container.innerHTML = `<div class="alert alert-danger">Error loading addresses.</div>`; });
}

function openAddressModal() {
    editingAddressId = null;
    document.getElementById('addressModalLabel').innerText = 'Add New Address';
    document.getElementById('address-form').reset();
    document.getElementById('address-id').value = '';
    document.getElementById('receiver-name').value = window.customerFullName || 'Customer';
    document.getElementById('province').value = '';
    document.getElementById('city').innerHTML = '<option value="">-- SELECT CITY --</option>';
    document.getElementById('city').disabled = true;
    document.getElementById('barangay').innerHTML = '<option value="">-- SELECT BARANGAY --</option>';
    document.getElementById('barangay').disabled = true;
    addressModal.show();
}

async function editAddress(id) {
    const addr = allAddresses.find(a => a.addressID == id);
    if (!addr) return;

    // Helper to ensure an option exists for instant display
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

    // 1. Fill all text fields IMMEDIATELY
    editingAddressId = addr.addressID;
    document.getElementById('addressModalLabel').innerText = 'Edit Address';
    document.getElementById('address-id').value = addr.addressID;
    document.getElementById('receiver-name').value = customerFullName;
    document.getElementById('receiver-phone').value = addr.receiverPhone || '';
    document.getElementById('address-line').value = addr.addressLine || '';
    document.getElementById('zip').value = addr.zip || '';
    
    // 2. Set cascading values IMMEDIATELY (injecting if necessary)
    const prov = document.getElementById('province');
    const city = document.getElementById('city');
    const brgy = document.getElementById('barangay');
    
    if (prov) prov.value = addr.province || '';
    if (city && addr.city) ensureOption(city, addr.city);
    if (brgy && addr.barangay) ensureOption(brgy, addr.barangay);

    // 3. Show modal INSTANTLY so the user sees the populated data
    addressModal.show();
    document.getElementById('btn-save-address').innerText = 'Update Address';

    // 4. Trigger background loads quietly without blocking the UI
    if (locationPicker) {
        locationPicker.loadCities().then(() => {
            if (city && addr.city) city.value = addr.city;
            locationPicker.loadBarangays().then(() => {
                if (brgy && addr.barangay) brgy.value = addr.barangay;
            });
        });
    }
}

// Profile & Password AJAX Handlers
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Profile Updated', data.message);
                } else {
                    showError('Update Failed', data.message);
                }
            })
            .catch(err => showError('Error', 'An unexpected error occurred.'));
        });
    }

    const passwordForm = document.getElementById('passwordUpdateForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Security Updated', data.message);
                    this.reset(); // Clear passwords on success
                } else {
                    showError('Security Error', data.message);
                    // On error, we DO NOT reset the form, keeping the user's input
                }
            })
            .catch(err => showError('Error', 'An unexpected error occurred.'));
        });
    }
});

document.getElementById('address-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const action = editingAddressId ? 'edit' : 'add';
    const payload = {
        addressID: document.getElementById('address-id').value,
        receiverName: document.getElementById('receiver-name').value,
        receiverPhone: document.getElementById('receiver-phone').value,
        addressLine: document.getElementById('address-line').value,
        barangay: document.getElementById('barangay').value,
        city: document.getElementById('city').value,
        province: document.getElementById('province').value,
        zip: document.getElementById('zip').value,
        isDefault: 0
    };
    fetch(`address_api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(data => {
        if (data.success) { 
            addressModal.hide(); 
            loadAddresses();
            // Sync Home Address in Personal Information if the server returned one
            if (data.syncedAddress) syncProfileAddress(data.syncedAddress);
            showSuccess(editingAddressId ? 'Address Updated' : 'Address Saved', data.message || 'Your address has been saved successfully.');
        }
        else alert(data.message);
    });
});

function prepareDelete(id) {
    addressToDelete = id;
    deleteConfirmModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!addressToDelete) return;
    deleteAddress(addressToDelete);
    deleteConfirmModal.hide();
});

function deleteAddress(id) {
    fetch('address_api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ addressID: id })
    }).then(r => r.json()).then(data => {
        if (data.success) { 
            loadAddresses();
            showSuccess('Address Deleted', 'The address has been removed from your address book.');
        }
        else alert(data.message);
    });
}

/**
 * Sets the chosen address as default AND syncs it to Personal Information.
 */
function setDefaultAddress(id) {
    fetch('address_api.php?action=set_default', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ addressID: id })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            loadAddresses();
            if (data.syncedAddress) syncProfileAddress(data.syncedAddress);
            showSuccess('Default Address Set', 'This address is now your default and has been synced to your Personal Information.');
        } else {
            alert(data.message);
        }
    });
}

/**
 * Updates the Home Address field in the Personal Information form
 * and its read-only display in the Account Information overview.
 */
function syncProfileAddress(addressStr) {
    // 1. Update the Personal Information textarea (Account Settings pane)
    const textarea = document.querySelector('textarea[name="address"]');
    if (textarea) textarea.value = addressStr;

    // 2. Update the read-only overview display (Overview pane)
    const overviewAddr = document.querySelector('#overview .info-item .value:last-child');
    // Safer: find by label text
    const allInfoItems = document.querySelectorAll('#overview .info-item');
    allInfoItems.forEach(item => {
        const label = item.querySelector('label');
        if (label && label.textContent.trim().toLowerCase() === 'address') {
            const val = item.querySelector('.value');
            if (val) val.textContent = addressStr;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const pane = urlParams.get('pane');
    if (pane) {
        const navBtn = document.getElementById('nav-' + pane);
        if (navBtn) showPane(pane, navBtn);
    }
});
function clearNotification(status, element) {
    // Call AJAX to mark all orders of this status as seen
    fetch(`profile.php?action=clear_order_notifs&status=${status}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Find and remove the badge within the clicked element for immediate UI feedback
                const badge = element.querySelector('.count-badge');
                if (badge) {
                    badge.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    badge.style.opacity = '0';
                    badge.style.transform = 'scale(0.5)';
                    setTimeout(() => {
                        badge.remove();
                    }, 300);
                }
            }
        })
        .catch(err => console.error('Error clearing notifications:', err));
}

/* =============================================
   Flatpickr — Birth Date Picker Initialization
   ============================================= */
document.addEventListener('DOMContentLoaded', function () {
    const birthdayInput = document.getElementById('birthdayPicker');
    if (!birthdayInput || typeof flatpickr === 'undefined') return;

    flatpickr(birthdayInput, {
        dateFormat: 'Y-m-d',        // Value submitted to server (MySQL compatible)
        altInput: true,             // Show a human-friendly display
        altFormat: 'F j, Y',        // e.g. "September 24, 2003"
        maxDate: 'today',           // Cannot pick a future birthday
        disableMobile: true,        // Use custom theme on mobile too
        monthSelectorType: 'dropdown',
        yearSelectorType: 'dropdown',
        prevArrow: '<i class="bi bi-chevron-left"></i>',
        nextArrow: '<i class="bi bi-chevron-right"></i>'
    });
});
