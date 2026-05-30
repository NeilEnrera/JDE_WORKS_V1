/**
 * Appointments Management JavaScript
 * Refactored for Custom Pop Modals
 */

let activeApptFilter = 'all';
let currentApptID = null;
let countdownInterval = null;

function parseAppointmentDateTime(dateStr, timeStr) {
    const timeMatch = timeStr.trim().split(' - ')[0].match(/(\d+):(\d+)\s*(AM|PM)/i);
    if (!timeMatch) return null;
    let [ , hours, minutes, modifier ] = timeMatch;
    hours = parseInt(hours, 10);
    if (modifier.toUpperCase() === 'PM' && hours < 12) hours += 12;
    if (modifier.toUpperCase() === 'AM' && hours === 12) hours = 0;
    
    const [year, month, day] = dateStr.split('-');
    return new Date(year, month - 1, day, hours, parseInt(minutes, 10));
}

// --- POP MODAL HELPERS ---

function showStatusModal(title, message, type = 'success', onConfirm = null, confirmText = 'Okay', hideCancel = false, hideCloseIcon = false, disableAutoClose = false) {
    const modal = document.getElementById('statusModal');
    const iconEl = document.getElementById('statusIcon');
    const titleEl = document.getElementById('statusTitle');
    const subtitleEl = document.getElementById('statusSubtitle');
    const messageEl = document.getElementById('statusMessage');
    const actionsEl = document.getElementById('statusActions');
    const progressWrap = document.getElementById('statusProgressWrap');
    const progressBar = document.getElementById('statusProgressBar');
    const countdown = document.getElementById('statusCountdown');

    if (!modal) return;

    // Clear any previous auto-dismiss timer
    if (modal._autoDismissTimer) { clearInterval(modal._autoDismissTimer); modal._autoDismissTimer = null; }

    titleEl.textContent = title;
    messageEl.textContent = message;
    actionsEl.innerHTML = '';
    if (progressWrap) progressWrap.style.display = 'none';

    const closeBtn = document.getElementById('statusModalClose');
    if (closeBtn) closeBtn.style.display = hideCloseIcon ? 'none' : 'flex';

    const btnBase = 'flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; transition:all 0.2s;';

    if (type === 'success') {
        subtitleEl.textContent = 'Action completed successfully';
        iconEl.style.background = 'rgba(40,167,69,0.1)';
        iconEl.style.borderColor = 'rgba(40,167,69,0.2)';
        iconEl.innerHTML = '<i class="bi bi-check2-circle" style="color:#28a745;"></i>';
        actionsEl.innerHTML = `<button style="${btnBase} background:var(--primary); color:var(--accent);" onclick="closeStatusModal()">${confirmText}</button>`;
        // Auto-dismiss success after 4s unless disabled
        if (!disableAutoClose) {
            _startStatusCountdown(4, modal, progressBar, progressWrap, countdown);
        }
    } else if (type === 'error') {
        subtitleEl.textContent = 'Something went wrong';
        iconEl.style.background = 'rgba(220,53,69,0.1)';
        iconEl.style.borderColor = 'rgba(220,53,69,0.2)';
        iconEl.innerHTML = '<i class="bi bi-exclamation-circle" style="color:#dc3545;"></i>';
        actionsEl.innerHTML = `<button style="${btnBase} background:#dc3545; color:white;" onclick="closeStatusModal()">Close</button>`;
    } else if (type === 'confirm') {
        subtitleEl.textContent = 'Please confirm your action below';
        iconEl.style.background = 'rgba(245,158,11,0.1)';
        iconEl.style.borderColor = 'rgba(245,158,11,0.2)';
        iconEl.innerHTML = '<i class="bi bi-exclamation-triangle" style="color:#f59e0b;"></i>';

        if (!hideCancel) {
            const cancelBtn = document.createElement('button');
            cancelBtn.style.cssText = `${btnBase} background:#f1f5f9; color:#475569;`;
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = closeStatusModal;
            actionsEl.appendChild(cancelBtn);
        }

        const confirmBtn = document.createElement('button');
        confirmBtn.style.cssText = `${btnBase} background:var(--primary); color:var(--accent);`;
        confirmBtn.textContent = confirmText === 'Okay' ? 'Yes, Proceed' : confirmText;
        confirmBtn.onclick = () => { closeStatusModal(); if (onConfirm) onConfirm(); };

        actionsEl.appendChild(confirmBtn);
    }

    modal.classList.add('active');
}

function _startStatusCountdown(seconds, modal, bar, wrap, countEl) {
    if (!bar || !wrap || !countEl) return;
    bar.style.background = 'linear-gradient(90deg, var(--primary), #1e6a99)';
    bar.style.width = '100%';
    bar.style.transition = `width ${seconds}s linear`;
    wrap.style.display = 'block';
    countEl.textContent = seconds;
    // Kick off CSS shrink
    requestAnimationFrame(() => { bar.style.width = '0%'; });
    let remaining = seconds;
    modal._autoDismissTimer = setInterval(() => {
        remaining--;
        countEl.textContent = remaining;
        if (remaining <= 0) { clearInterval(modal._autoDismissTimer); closeStatusModal(); }
    }, 1000);
}


function closeStatusModal() {
    const modal = document.getElementById('statusModal');
    if (modal) modal.classList.remove('active');
}

// --- EXISTING CORE LOGIC ---

function updateAppointmentCounts() {
    const counts = { all: 0, pending: 0, upcoming: 0, accepted: 0, declined: 0, cancelled: 0 };
    const todayObj = new Date();
    const today = todayObj.getFullYear() + '-' + String(todayObj.getMonth() + 1).padStart(2, '0') + '-' + String(todayObj.getDate()).padStart(2, '0');
    
    document.querySelectorAll('#appointmentsTable tbody tr:not(#apptEmptyState)').forEach(tr => {
        const s = tr.dataset.status;
        const d = (tr.dataset.date || '').substring(0, 10);
        counts.all++;
        if (counts[s.toLowerCase()] !== undefined) counts[s.toLowerCase()]++;
        
        if (s === 'Accepted' && d >= today) {
            counts.upcoming++;
        }
    });

    for (const [status, count] of Object.entries(counts)) {
        const el = document.getElementById('count-' + status);
        if (el) el.textContent = count;
    }
}

function applyApptFilters() {
    const termEl = document.getElementById('apptSearchInput');
    const dateFromEl = document.getElementById('apptDateFrom');
    const dateToEl = document.getElementById('apptDateTo');
    const periodEl = document.getElementById('apptPeriodFilter');
    if (!termEl) return;

    const term = termEl.value.toLowerCase();
    const status = activeApptFilter;
    const dateFrom = dateFromEl ? dateFromEl.value : '';
    const dateTo = dateToEl ? dateToEl.value : '';
    const period = periodEl ? periodEl.value : '';
    const today = new Date().toISOString().split('T')[0];
    
    let visibleMain = 0;
    document.querySelectorAll('#appointmentsTable tbody tr:not(#apptEmptyState)').forEach(tr => {
        const name = (tr.dataset.name || '').toLowerCase();
        const rowDate = (tr.dataset.date || '').substring(0, 10);
        const rowPeriod = tr.dataset.period || '';
        const s = tr.dataset.status;

        const matchTerm = !term || name.includes(term);
        let matchStatus = (status === 'all') || (status === 'Upcoming' ? (s === 'Accepted' && rowDate >= today) : (s === status));
        const matchFrom = !dateFrom || rowDate >= dateFrom;
        const matchTo = !dateTo || rowDate <= dateTo;
        const matchPeriod = !period || rowPeriod.includes(period);

        const show = matchTerm && matchStatus && matchFrom && matchTo && matchPeriod;
        tr.style.display = show ? '' : 'none';
        if (show) visibleMain++;
    });

    const emptyStateMain = document.getElementById('apptEmptyState');
    if (emptyStateMain) emptyStateMain.style.display = (visibleMain === 0) ? '' : 'none';

    let visiblePast = 0;
    const pastTermEl = document.getElementById('pastApptSearchInput');
    const pastTerm = pastTermEl ? pastTermEl.value.toLowerCase() : '';
    document.querySelectorAll('#pastAppointmentsTable tbody tr:not(#pastApptEmptyState)').forEach(tr => {
        const name = (tr.dataset.name || '').toLowerCase();
        const matchTerm = !pastTerm || name.includes(pastTerm);
        tr.style.display = matchTerm ? '' : 'none';
        if (matchTerm) visiblePast++;
    });

    const emptyStatePast = document.getElementById('pastApptEmptyState');
    if (emptyStatePast && document.querySelectorAll('#pastAppointmentsTable tbody tr:not(#pastApptEmptyState)').length > 0) {
        emptyStatePast.style.display = (visiblePast === 0) ? '' : 'none';
        if (visiblePast === 0) {
            emptyStatePast.cells[0].textContent = "No past appointments match your filters";
        }
    }

    // Past Container logic
    const pastContainer = document.getElementById('pastAppointmentsContainer');
    if (pastContainer) pastContainer.style.display = (status === 'Upcoming') ? 'block' : 'none';
}

// --- UPDATED ACTION LOGIC ---

function quickUpdateAppt(id, newStatus, message) {
    showStatusModal('Confirm Action', message, 'confirm', () => {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('appointmentID', id);
        fd.append('status', newStatus);

        fetch('appointment_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showStatusModal('Error', data.message || 'Update failed.', 'error');
                }
            })
            .catch(() => showStatusModal('Error', 'Connection to server failed.', 'error'));
    }, 'Yes, Proceed', false, true);
}

function viewAppt(appt) {
    currentApptID = appt.appointmentID;

    // Mapping UI elements
    const fields = {
        'modalApptName': appt.name,
        'modalApptEmail': appt.email,
        'modalApptPhone': appt.phone || 'N/A',
        'modalApptService': appt.serviceType,
        'modalApptDate': appt.appointmentDate,
        'modalApptTime': appt.appointmentTime,
        'modalApptMessage': appt.message || 'No message provided.'
    };

    for (let id in fields) {
        const el = document.getElementById(id);
        if (el) el.textContent = fields[id];
    }

    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }

    const countdownEl = document.getElementById('modalApptCountdown');
    if (countdownEl) {
        if (appt.appointmentStatus !== 'Accepted') {
            countdownEl.style.display = 'none';
        } else {
            const targetDate = parseAppointmentDateTime(appt.appointmentDate, appt.appointmentTime);
            if (targetDate) {
                const updateCountdown = () => {
                    const now = new Date();
                    const diff = targetDate - now;
                    if (diff > 0) {
                        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
                        const mins = Math.floor((diff / 1000 / 60) % 60);
                        
                        let text = "In ";
                        if (days > 0) text += days + "d ";
                        if (hours > 0 || days > 0) text += hours + "h ";
                        text += mins + "m";
                        
                        countdownEl.textContent = text;
                        countdownEl.style.display = 'inline-block';
                        countdownEl.style.background = '#e0e7ff';
                        countdownEl.style.color = '#4338ca';
                    } else if (diff > -3600000) {
                        countdownEl.textContent = "Due Now";
                        countdownEl.style.display = 'inline-block';
                        countdownEl.style.background = '#dcfce7';
                        countdownEl.style.color = '#166534';
                    } else {
                        countdownEl.style.display = 'none';
                        clearInterval(countdownInterval);
                    }
                };
                updateCountdown();
                countdownInterval = setInterval(updateCountdown, 60000);
            } else {
                countdownEl.style.display = 'none';
            }
        }
    }

    // Reset Alert
    const alert = document.getElementById('apptConflictAlert');
    if (alert) alert.style.display = 'none';

    if (appt.appointmentStatus === 'Pending') {
        checkConflicts(appt.appointmentID, appt.appointmentDate, appt.appointmentTime);
    }

    // Fetch Remarks and Staff
    const fd = new FormData();
    fd.append('action', 'get_details');
    fd.append('appointmentID', appt.appointmentID);
    fetch('appointment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const remarksEl = document.getElementById('modalApptInternalRemarks');
                const staffEl = document.getElementById('modalApptStaff');
                if (remarksEl) remarksEl.value = res.data.internalRemarks || '';
                if (staffEl) staffEl.value = res.data.employeeID || '';
            }
        });

    const modalActions = document.getElementById('modalStatusActions');
    const modalFooter = document.getElementById('modalApptActions');
    if (modalActions) {
        modalActions.innerHTML = '';
        if (appt.appointmentStatus === 'Pending') {
            modalActions.innerHTML = `
                <button class="btn-confirm-cancel" onclick="closeApptModal(); quickUpdateAppt(${appt.appointmentID}, 'Declined', 'Reject this appointment?')">Reject</button>
                <button class="btn-confirm-ok" onclick="closeApptModal(); quickUpdateAppt(${appt.appointmentID}, 'Accepted', 'Confirm this appointment?')">Accept</button>
            `;
            if (modalFooter) modalFooter.style.display = '';
        } else {
            modalActions.innerHTML = ``;
            if (modalFooter) modalFooter.style.display = 'none';
        }
    }

    const modal = document.getElementById('apptDetailsModal');
    if (modal) modal.classList.add('active');
}

function saveApptAdvanced() {
    if (!currentApptID) return;

    const btn = document.getElementById('btnSaveApptAdvanced');
    if (btn) btn.disabled = true;

    const remarksEl = document.getElementById('modalApptInternalRemarks');
    const staffEl = document.getElementById('modalApptStaff');

    const fd = new FormData();
    fd.append('action', 'save_advanced');
    fd.append('appointmentID', currentApptID);
    fd.append('internalRemarks', remarksEl ? remarksEl.value : '');
    fd.append('employeeID', staffEl ? staffEl.value : '');

    fetch('appointment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Success modal removed as requested
                closeApptModal();
                location.reload();
            } else {
                showStatusModal('Error', data.message || 'Save failed.', 'error');
            }
        })
        .finally(() => {
            if (btn) btn.disabled = false;
        });
}

function closeApptModal() {
    const modal = document.getElementById('apptDetailsModal');
    if (modal) modal.classList.remove('active');
    currentApptID = null;
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
}

function checkConflicts(id, date, time) {
    const fd = new FormData();
    fd.append('action', 'check_conflicts');
    fd.append('appointmentID', id);
    fd.append('date', date);
    fd.append('time', time);

    fetch('appointment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            const alert = document.getElementById('apptConflictAlert');
            const msg = document.getElementById('conflictMessage');
            if (alert && msg && data.success && data.conflicts.length > 0) {
                const names = data.conflicts.map(c => c.name).join(', ');
                msg.textContent = `Overlaps with: ${names}`;
                alert.style.display = 'block';
            }
        });
}

// --- BLOCKED DATES LOGIC ---

function openBlockedDatesModal() {
    const modal = document.getElementById('blockedDatesModal');
    if (modal) {
        modal.classList.add('active');
        fetchBlockedDates();
    }
}

function closeBlockedDatesModal() {
    const modal = document.getElementById('blockedDatesModal');
    if (modal) modal.classList.remove('active');
}

function fetchBlockedDates() {
    const fd = new FormData();
    fd.append('action', 'get_blocked_dates');

    fetch('appointment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            const list = document.getElementById('blockedDatesList');
            if (!list) return;

            if (res.success && res.data.length > 0) {
                list.innerHTML = res.data.map(item => `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-size: 0.9rem; font-weight: 600; color: var(--primary);">${new Date(item.blockedDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td style="padding: 12px; font-size: 0.85rem; color: #64748b;">${item.reason || '-'}</td>
                        <td style="padding: 12px; text-align: right;">
                            <button onclick="unblockDate(${item.blockedID})" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 1.1rem; padding: 4px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                list.innerHTML = '<tr><td colspan="3" style="padding: 30px; text-align: center; color: #94a3b8; font-size: 0.9rem;">No blocked dates found</td></tr>';
            }
        });
}

function saveBlockedDate() {
    const reasonEl = document.getElementById('blockedReason');

    // Read date reliably from the flatpickr instance's selectedDates array
    let dateValue = '';
    if (window.blockedDatePicker && window.blockedDatePicker.selectedDates.length > 0) {
        const d = window.blockedDatePicker.selectedDates[0];
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        dateValue = `${yyyy}-${mm}-${dd}`;
    } else {
        const dateEl = document.getElementById('newBlockedDate');
        dateValue = dateEl ? dateEl.value : '';
    }

    if (!dateValue) {
        showStatusModal('Error', 'Please select a date to block.', 'error');
        return;
    }

    const reasonValue = reasonEl ? reasonEl.value : '';

    const fdCheck = new FormData();
    fdCheck.append('action', 'check_date_appointments');
    fdCheck.append('date', dateValue);

    fetch('appointment_handler.php', { method: 'POST', body: fdCheck })
        .then(r => r.json())
        .then(data => {
            if (data.success && parseInt(data.count) > 0) {
                showStatusModal(
                    'Warning: Active Appointments',
                    `There ${data.count == 1 ? 'is' : 'are'} ${data.count} existing appointment(s) on this date. Blocking it will automatically notify affected customers and mark appointments as cancelled. Continue?`,
                    'confirm',
                    () => proceedBlockingDate(dateValue, reasonValue)
                );
            } else {
                proceedBlockingDate(dateValue, reasonValue);
            }
        })
        .catch(() => {
            // If check fails, warn the admin and let them decide
            showStatusModal(
                'Confirm Block Date',
                'Could not verify existing appointments. Do you still want to block this date?',
                'confirm',
                () => proceedBlockingDate(dateValue, reasonValue)
            );
        });
}

function proceedBlockingDate(dateValue, reasonValue) {
    const fd = new FormData();
    fd.append('action', 'block_date');
    fd.append('date', dateValue);
    fd.append('reason', reasonValue);

    fetch('appointment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (window.blockedDatePicker) window.blockedDatePicker.clear();
                const reasonEl = document.getElementById('blockedReason');
                if (reasonEl) reasonEl.value = '';
                
                // Real-time updates
                fetchBlockedDates();
                refreshAppointments();
                
                showStatusModal('Success', 'Date blocked. Affected appointments have been cancelled and notifications are being sent.', 'success', null, 'Okay', false, false, true);
                
                // Asynchronously notify affected customers
                if (data.affected && data.affected.length > 0) {
                    notifyAffectedCustomers(data.affected);
                }
            } else {
                showStatusModal('Error', data.message || 'Failed to block date.', 'error');
            }
        });
}

/**
 * Sends notifications one by one to avoid UI/server blocking
 */
function notifyAffectedCustomers(affected) {
    const appointments = [...affected];
    
    const sendNext = () => {
        if (appointments.length === 0) return;
        
        const appt = appointments.shift();
        const fd = new FormData();
        fd.append('action', 'send_blocked_notification');
        fd.append('email', appt.email);
        fd.append('name', appt.name);
        fd.append('date', appt.appointmentDate);
        fd.append('time', appt.appointmentTime);
        fd.append('service', appt.serviceType);

        fetch('appointment_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                console.log(`Notification sent to ${appt.email}:`, res.success);
                sendNext(); // Continue to next
            })
            .catch(err => {
                console.error(`Failed to notify ${appt.email}:`, err);
                sendNext(); // Skip and continue
            });
    };

    sendNext();
}

function unblockDate(id) {
    showStatusModal('Confirm Unblock', 'Are you sure you want to unblock this date?', 'confirm', () => {
        const fd = new FormData();
        fd.append('action', 'unblock_date');
        fd.append('blockedID', id);

        fetch('appointment_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    fetchBlockedDates();
                    if (typeof refreshAppointments === 'function') refreshAppointments();
                } else {
                    showStatusModal('Error', data.message || 'Failed to unblock date.', 'error');
                }
            });
    }, 'Yes, Proceed', false, true);
}

function fetchStaff() {
    const fd = new FormData();
    fd.append('action', 'get_staff');
    fetch('appointment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('modalApptStaff');
                if (select) {
                    while (select.options.length > 1) select.remove(1);
                    data.staff.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.employeeID;
                        opt.textContent = `${s.firstName} ${s.lastName}`;
                        select.appendChild(opt);
                    });
                }
            }
        });
}

// Realtime Synchronization
let latestApptID = null;

function refreshAppointments(isInitial = false) {
    const timestamp = new Date().getTime();
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 1;
    const limit = urlParams.get('limit') || 10;

    fetch(`../../JDE_ADMIN/backend/admin_api.php?action=get_appointments&page=${page}&limit=${limit}&_=${timestamp}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const appts = data.appointments;
                if (!appts || appts.length === 0) return;

                const currentMaxID = Math.max(...appts.map(a => a.appointmentID));
                
                // If not initial load and we have a new ID, show toast
                if (!isInitial && latestApptID !== null && currentMaxID > latestApptID) {
                    if (typeof showStatusModal === 'function') {
                        // Using showStatusModal loosely or a dedicated toast if available
                        const newCount = appts.filter(a => a.appointmentID > latestApptID).length;
                        console.log(`Realtime: Detected ${newCount} new appointments!`);
                    }
                }
                
                latestApptID = currentMaxID;
                renderApptsTable(appts);
                updateAppointmentCounts();
                applyApptFilters(); // Re-apply existing filters to new data
            }
        });
}

function renderApptsTable(appts) {
    const tbody = document.querySelector('#appointmentsTable tbody');
    if (!tbody) return;

    let html = '';
    const todayDate = new Date().toISOString().split('T')[0];

    appts.forEach(appt => {
        const id = appt.appointmentID;
        const date = appt.appointmentDate;
        const time = appt.appointmentTime;
        const name = appt.name;
        const service = appt.serviceType || 'Standard';
        const status = appt.appointmentStatus || 'Pending';
        const statusClass = status.toLowerCase();
        
        html += `
            <tr data-status="${status}" data-date="${date}" data-name="${name.toLowerCase().replace(/"/g, '&quot;')}" data-period="${time.replace(/"/g, '&quot;')}" class="clickable-row" onclick="viewAppt(${JSON.stringify(appt).replace(/"/g, '&quot;')})">
                <td style="font-weight: 800; color: var(--primary);">#APT-${String(id).padStart(3, '0')}</td>
                <td>
                    <div style="font-weight: 600; color: var(--primary); font-size: 0.95rem;">${date}</div>
                    <div style="font-weight: 600; color: #475569; font-size: 0.95rem;">${time}</div>
                </td>
                <td><div style="font-weight: 700; color: var(--secondary);">${name}</div></td>
                <td><span class="status-badge processing">${service}</span></td>
                <td><span class="status-badge ${statusClass}">${status}</span></td>
            </tr>
        `;
    });

    if (appts.length === 0) {
        html = '<tr id="apptEmptyState"><td colspan="5" style="text-align: center;">No appointments found</td></tr>';
    }

    tbody.innerHTML = html;
}

window.refreshDashboardStats = refreshAppointments;

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    refreshAppointments(true); // Populate initially
    fetchStaff();

    document.querySelectorAll('.triage-tab').forEach(tab => {
        // Only attach filtering logic if the tab has a data-filter attribute
        if (tab.dataset.filter) {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.triage-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                activeApptFilter = tab.dataset.filter;
                applyApptFilters();
            });
        }
    });

    ['apptSearchInput', 'pastApptSearchInput'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('keyup', applyApptFilters);
    });
    
    const periodFilter = document.getElementById('apptPeriodFilter');
    if (periodFilter) periodFilter.addEventListener('change', applyApptFilters);

    const apptFlatpickrConfig = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        disableMobile: true,
        monthSelectorType: "dropdown",
        scrollInput: false,
        prevArrow: '<i class="bi bi-chevron-left"></i>',
        nextArrow: '<i class="bi bi-chevron-right"></i>',
        onChange: () => {
            if (typeof applyApptFilters === 'function') applyApptFilters();
        }
    };

    if (document.getElementById('apptDateFrom')) flatpickr("#apptDateFrom", apptFlatpickrConfig);
    if (document.getElementById('apptDateTo')) {
        flatpickr("#apptDateTo", {
            ...apptFlatpickrConfig,
            position: "auto right"
        });
    }

    if (document.getElementById('newBlockedDate')) {
        window.blockedDatePicker = flatpickr("#newBlockedDate", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            disableMobile: true,
            monthSelectorType: "dropdown",
            scrollInput: false,
            static: true,
            position: "below",
            disable: [
                {
                    daysOfWeek: [0] // 0 is Sunday
                }
            ],
            locale: {
                firstDayOfWeek: 1 // Monday
            },
            prevArrow: '<i class="bi bi-chevron-left"></i>',
            nextArrow: '<i class="bi bi-chevron-right"></i>',
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                if (dayElem.dateObj.getDay() === 0) {
                    dayElem.classList.add("flatpickr-disabled");
                    dayElem.style.color = "#ff4757"; // Explicit red for Sunday
                    dayElem.style.opacity = "0.5";
                    dayElem.style.cursor = "not-allowed";
                }
            },
            onReady: function (selectedDates, dateStr, instance) {
                if (instance.calendarContainer) {
                    instance.calendarContainer.style.setProperty('z-index', '99999', 'important');
                }
            },
            onChange: null
        });
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeApptModal();
            closeStatusModal();
            closeBlockedDatesModal();
            closeReminderSettingsModal();
        }
    });
});

/**
 * Reminder Settings
 */
window.openReminderSettingsModal = function() {
    const modal = document.getElementById('reminderSettingsModal');
    if (!modal) return;
    
    // Fetch current settings
    const formData = new FormData();
    formData.append('action', 'get_settings');

    fetch('../backend/appointment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            const input = document.getElementById('reminderTimeInput');
            if (input) input.value = res.data.reminder_time;
        }
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
};

window.closeReminderSettingsModal = function() {
    const modal = document.getElementById('reminderSettingsModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
};

window.saveReminderSettings = function() {
    const time = document.getElementById('reminderTimeInput').value;
    if (!time) {
        alert('Please select a valid time.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'save_settings');
    formData.append('reminder_time', time);

    fetch('../backend/appointment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeReminderSettingsModal();
            // Assuming showStatusModal is defined elsewhere in your JS
            if (window.showStatusModal) {
                showStatusModal('Preferences Saved', 'Reminder settings have been updated successfully!', 'success', null, 'Okay', false, false, true);
            } else {
                alert('Reminder preferences updated successfully!');
            }
        } else {
            alert(res.message || 'Failed to save preferences.');
        }
    });
};



