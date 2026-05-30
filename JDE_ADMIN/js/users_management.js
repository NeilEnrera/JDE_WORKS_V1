/**
 * User Management JavaScript
 * Connected to users_handler.php
 */

let currentEditId = null;
let currentEditType = null;

function getUserTypeName(userTypeID) {
    if (userTypeID == 1) return 'Admin';
    if (userTypeID == 2) return 'Employee';
    if (userTypeID == 3) return 'Customer';
    return 'Unknown';
}

document.addEventListener('DOMContentLoaded', function () {
    attachDropdownListeners();

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    // Filter by user type via new dropdown buttons
    const customerBtn = document.querySelector('.filter-tab[data-type="3"]');
    const staffBtn = document.getElementById('staffDropdownBtn');
    const staffItems = document.querySelectorAll('.filter-dropdown-item');
    const staffDropdown = document.querySelector('.filter-tab-dropdown');

    if (staffBtn && staffDropdown) {
        staffBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            staffDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!staffDropdown.contains(e.target)) {
                staffDropdown.classList.remove('open');
            }
        });
    }

    if (customerBtn) {
        customerBtn.addEventListener('click', function () {
            customerBtn.classList.add('active');
            if (staffBtn) {
                staffBtn.classList.remove('active');
                staffBtn.innerHTML = 'Staff <i class="bi bi-chevron-down" style="font-size: 10px; margin-left: 2px;"></i>';
            }
            if (staffItems) staffItems.forEach(item => item.classList.remove('active'));
            applyFilters();
        });
    }

    if (staffItems) {
        staffItems.forEach(item => {
            item.addEventListener('click', function (e) {
                e.stopPropagation();
                if (customerBtn) customerBtn.classList.remove('active');
                if (staffBtn) {
                    staffBtn.classList.add('active');
                    staffBtn.innerHTML = this.textContent + ' <i class="bi bi-chevron-down" style="font-size: 10px; margin-left: 2px;"></i>';
                    staffDropdown.classList.remove('open');
                }
                staffItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                applyFilters();
            });
        });
    }

    // Default to only seeing Customers initially
    applyFilters();
});

function applyFilters() {
    let typeValue = '3';
    const activeStaff = document.querySelector('.filter-dropdown-item.active');
    const activeCustomer = document.querySelector('.filter-tab[data-type="3"].active');

    if (activeStaff) {
        typeValue = activeStaff.getAttribute('data-type');
    } else if (activeCustomer) {
        typeValue = '3';
    }

    const searchTerm = document.getElementById('searchInput') ? document.getElementById('searchInput').value.toLowerCase() : '';

    const rows = document.querySelectorAll('#usersTable tbody tr:not(#userEmptyState)');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const userType = row.getAttribute('data-user-type');

        const matchSearch = text.includes(searchTerm);
        const matchType = typeValue === 'all' || userType === typeValue;

        if (matchSearch && matchType) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const emptyState = document.getElementById('userEmptyState');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? '' : 'none';
    }
}

function attachDropdownListeners() {
    const actionTriggers = document.querySelectorAll('.action-trigger');

    actionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const dropdown = this.closest('.action-dropdown');

            document.querySelectorAll('.action-dropdown.active').forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('active');
                }
            });

            if (dropdown) dropdown.classList.toggle('active');
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.action-dropdown')) {
            document.querySelectorAll('.action-dropdown.active').forEach(d => {
                d.classList.remove('active');
            });
        }
    });

    document.querySelectorAll('.action-item').forEach(item => {
        item.addEventListener('click', function () {
            const dropdown = this.closest('.action-dropdown');
            if (dropdown) dropdown.classList.remove('active');
        });
    });
}

function viewUser(id, userTypeID) {
    const fd = new FormData();
    fd.append('action', 'get_user');
    fd.append('userID', id);
    fd.append('userTypeID', userTypeID);

    fetch('users_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const user = res.data;
                const idEl = document.getElementById('viewUserId');
                const nameEl = document.getElementById('viewUserName');
                const firstEl = document.getElementById('viewFirstName');
                const lastEl = document.getElementById('viewLastName');
                const midEl = document.getElementById('viewMiddleName');
                const bdayEl = document.getElementById('viewBirthday');
                const genderEl = document.getElementById('viewGender');
                const typeEl = document.getElementById('viewUserType');
                const emailEl = document.getElementById('viewEmail');
                const phoneEl = document.getElementById('viewPhone');
                const createdEl = document.getElementById('viewCreatedBy');
                const updatedEl = document.getElementById('viewUpdatedBy');

                if (idEl) idEl.value = '#USER-' + String(user.id).padStart(3, '0');
                if (nameEl) nameEl.value = user.userName;
                if (firstEl) firstEl.value = user.firstName;
                if (lastEl) lastEl.value = user.lastName;
                if (midEl) midEl.value = user.middleName || 'N/A';
                if (bdayEl) bdayEl.value = user.birthday;
                if (genderEl) genderEl.value = user.gender === 'M' ? 'Male' : 'Female';
                if (typeEl) typeEl.value = getUserTypeName(user.userTypeID);
                if (emailEl) emailEl.value = user.email;
                if (phoneEl) phoneEl.value = user.phoneNumber;
                if (createdEl) createdEl.value = user.createdBy || 'Unknown';
                if (updatedEl) updatedEl.value = user.updatedBy || 'Not yet updated';

                const modal = document.getElementById('viewModal');
                if (modal) modal.classList.add('active');
            } else {
                alert(res.message);
            }
        });
}

function closeViewModal() {
    const modal = document.getElementById('viewModal');
    if (modal) modal.classList.remove('active');
}

function openAddModal() {
    const fields = ['UserName', 'Password', 'FirstName', 'LastName', 'MiddleName', 'Birthday', 'Gender', 'UserType', 'Email', 'Phone'];
    fields.forEach(f => {
        const el = document.getElementById('add' + f);
        if (el) el.value = '';
    });

    const modal = document.getElementById('addModal');
    if (modal) modal.classList.add('active');
}

function closeAddModal() {
    const modal = document.getElementById('addModal');
    if (modal) modal.classList.remove('active');
}

function saveNewUser() {
    const userName = document.getElementById('addUserName').value.trim();
    const password = document.getElementById('addPassword').value.trim();
    const firstName = document.getElementById('addFirstName').value.trim();
    const lastName = document.getElementById('addLastName').value.trim();
    const middleName = document.getElementById('addMiddleName').value.trim();
    const birthday = document.getElementById('addBirthday').value;
    const gender = document.getElementById('addGender').value;
    const userTypeID = parseInt(document.getElementById('addUserType').value);
    const email = document.getElementById('addEmail').value.trim();
    const phoneNumber = document.getElementById('addPhone').value.trim();

    if (!userName || !password || !firstName || !lastName || !birthday || !gender || !userTypeID || !email || !phoneNumber) {
        if (typeof showToast === 'function') {
            showToast('Warning', 'Please fill in all required fields!', 'warning');
        } else {
            alert('Please fill in all required fields!');
        }
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_user');
    fd.append('userName', userName);
    fd.append('password', password);
    fd.append('firstName', firstName);
    fd.append('lastName', lastName);
    fd.append('middleName', middleName);
    fd.append('birthday', birthday);
    fd.append('gender', gender);
    fd.append('userTypeID', userTypeID);
    fd.append('email', email);
    fd.append('phoneNumber', phoneNumber);

    const btn = document.querySelector('#addModal .btn-save');
    const ogHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = 'Saving...'; }

    fetch('users_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeAddModal();
                if (typeof showToast === 'function') {
                    showToast('Success', `User added successfully!`, 'success');
                } else {
                    alert(`User added successfully!`);
                }
                setTimeout(() => location.reload(), 1000);
            } else {
                alert(res.message);
            }
        }).finally(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = ogHtml; }
        });
}

function editUser(id, userTypeID) {
    const fd = new FormData();
    fd.append('action', 'get_user');
    fd.append('userID', id);
    fd.append('userTypeID', userTypeID);    
    if (userTypeID == 3) {
        alert("Customers cannot be modified from this panel.");
        return;
    }

    fetch('users_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const user = res.data;
                currentEditId = id;
                currentEditType = userTypeID;

                const idEl = document.getElementById('editUserId');
                const nameEl = document.getElementById('editUserName');
                const firstEl = document.getElementById('editFirstName');
                const lastEl = document.getElementById('editLastName');
                const midEl = document.getElementById('editMiddleName');
                const bdayEl = document.getElementById('editBirthday');
                const genderEl = document.getElementById('editGender');
                const typeEl = document.getElementById('editUserType');
                const emailEl = document.getElementById('editEmail');
                const phoneEl = document.getElementById('editPhone');

                if (idEl) idEl.value = '#USER-' + String(user.id).padStart(3, '0');
                if (nameEl) nameEl.value = user.userName;
                if (firstEl) firstEl.value = user.firstName;
                if (lastEl) lastEl.value = user.lastName;
                if (midEl) midEl.value = user.middleName || '';
                if (bdayEl) bdayEl.value = user.birthday;
                if (genderEl) genderEl.value = user.gender;
                if (typeEl) {
                    typeEl.value = user.userTypeID;
                    typeEl.disabled = true; // Prevent changing user type safely
                }
                if (emailEl) emailEl.value = user.email;
                if (phoneEl) phoneEl.value = user.phoneNumber;

                const modal = document.getElementById('editModal');
                if (modal) modal.classList.add('active');
            } else {
                alert(res.message);
            }
        });
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) modal.classList.remove('active');
    currentEditId = null;
    currentEditType = null;
}

function saveUserUpdate() {
    if (currentEditId) {
        const userName = document.getElementById('editUserName').value.trim();
        const firstName = document.getElementById('editFirstName').value.trim();
        const lastName = document.getElementById('editLastName').value.trim();
        const middleName = document.getElementById('editMiddleName').value.trim();
        const birthday = document.getElementById('editBirthday').value;
        const gender = document.getElementById('editGender').value;
        const email = document.getElementById('editEmail').value.trim();
        const phoneNumber = document.getElementById('editPhone').value.trim();

        if (!userName || !firstName || !lastName || !birthday || !gender || !email || !phoneNumber) {
            alert('Please fill in all required fields!');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'update_user');
        fd.append('userID', currentEditId);
        fd.append('userTypeID', currentEditType);
        fd.append('userName', userName);
        fd.append('firstName', firstName);
        fd.append('lastName', lastName);
        fd.append('middleName', middleName);
        fd.append('birthday', birthday);
        fd.append('gender', gender);
        fd.append('email', email);
        fd.append('phoneNumber', phoneNumber);

        const btn = document.querySelector('#editModal .btn-save');
        const ogHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = 'Saving...'; }

        fetch('users_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    closeEditModal();
                    if (typeof showToast === 'function') {
                        showToast('Updated', `User updated successfully!`, 'info');
                    } else {
                        alert(`User updated successfully!`);
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(res.message);
                }
            }).finally(() => {
                if (btn) { btn.disabled = false; btn.innerHTML = ogHtml; }
            });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.filter-tab');
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#usersTableBody tr:not(#userEmptyState)');
    const emptyState = document.getElementById('userEmptyState');

    function applyFilters() {
        const activeTab = document.querySelector('.filter-tab.active');
        const selectedType = activeTab ? activeTab.getAttribute('data-type') : '3'; // Default to Customers (3)
        const searchTerm = searchInput.value.toLowerCase();
        
        let visibleCount = 0;

        rows.forEach(row => {
            const rowType = row.getAttribute('data-user-type');
            const rowText = row.innerText.toLowerCase();

            // Check Tab condition
            const matchesTab = (selectedType === 'all' || rowType === selectedType);
            
            // Check Search condition
            const matchesSearch = rowText.includes(searchTerm);

            // Row is visible ONLY if it matches BOTH
            if (matchesTab && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle "No Users Found" message
        if (visibleCount === 0) {
            emptyState.style.display = 'table-row';
        } else {
            emptyState.style.display = 'none';
        }
    }

    // Listener for Tab clicks
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            applyFilters(); // Re-run filtering
        });
    });

    // Listener for Search typing
    searchInput.addEventListener('input', applyFilters);

    // Run once on load to ensure only "Customers" (default) are shown
    applyFilters();
});



function showStatusModal(title, message, type = 'success') {
    const modal = document.getElementById('statusModal');
    const iconEl = document.getElementById('statusIcon');
    const titleEl = document.getElementById('statusTitle');
    const messageEl = document.getElementById('statusMessage');

    // Update Text
    titleEl.textContent = title;
    messageEl.textContent = message;

    // Update Icon & Color based on type
    if (type === 'success') {
        iconEl.innerHTML = '<i class="bi bi-check-circle" style="color: #28a745;"></i>';
    } else if (type === 'error') {
        iconEl.innerHTML = '<i class="bi bi-x-circle" style="color: #dc3545;"></i>';
    } else if (type === 'warning') {
        iconEl.innerHTML = '<i class="bi bi-exclamation-triangle" style="color: #ffc107;"></i>';
    }

    // Show the modal
    modal.classList.add('active');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
}

function saveUserUpdate() {
    if (!currentEditId) return;

    const fields = {
        userName: document.getElementById('editUserName').value.trim(),
        firstName: document.getElementById('editFirstName').value.trim(),
        lastName: document.getElementById('editLastName').value.trim(),
        middleName: document.getElementById('editMiddleName').value.trim(),
        birthday: document.getElementById('editBirthday').value,
        gender: document.getElementById('editGender').value,
        email: document.getElementById('editEmail').value.trim(),
        phoneNumber: document.getElementById('editPhone').value.trim()
    };

    // Validation check using our new modal
    if (!fields.userName || !fields.firstName || !fields.lastName || !fields.birthday || !fields.email || !fields.phoneNumber) {
        showStatusModal('Missing Info', 'Please fill in all required fields.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'update_user');
    fd.append('userID', currentEditId);
    fd.append('userTypeID', currentEditType);
    for (const key in fields) { fd.append(key, fields[key]); }

    const btn = document.querySelector('#editModal .btn-save');
    if (btn) { btn.disabled = true; btn.innerHTML = 'Saving...'; }

    fetch('users_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeEditModal();
                showStatusModal('Success!', 'User updated successfully.', 'success');
                setTimeout(() => location.reload(), 1500); // Reload after seeing the pop modal
            } else {
                showStatusModal('Update Failed', res.message, 'error');
            }
        })
        .catch(err => {
            showStatusModal('System Error', 'Could not connect to server.', 'error');
        })
        .finally(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Save Changes'; }
        });
}