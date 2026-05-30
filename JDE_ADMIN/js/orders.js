/**
 * Orders Management JavaScript
 * Extracted from orders.view.php
 */

// Global Toast System (Fallback to showNotification if available)
function showToast(title, message, type = 'success') {
    console.log(`Toast: [${title}] ${message} (${type})`);

    // Check for existing toast container
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-header">
            <strong>${title}</strong>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
        <div class="toast-body">${message}</div>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Map showNotification to showToast for consistency
window.showToast = showToast;
if (typeof window.showNotification !== 'function') {
    window.showNotification = (msg, type) => showToast('Notification', msg, type);
}

let currentEditId = null;
let currentUpdateOrderID = null;
let orderStatusModal = null; // Global reference for reliability
let viewOrderModal = null; // Global reference for reliability
let allOrdersCache = []; // Global cache for row clicks

function activateTab(target) {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const targetSection = document.getElementById(target + 'Section');

    if (!targetSection) return;

    tabBtns.forEach(b => b.classList.remove('active'));
    document.querySelectorAll('[data-tab="' + target + '"]').forEach(b => b.classList.add('active'));

    tabContents.forEach(c => c.classList.remove('active'));
    targetSection.classList.add('active');

    const headerActions = document.getElementById('headerActions');
    if (headerActions) {
        headerActions.innerHTML = target === 'inventory'
            ? `<button class="btn-add-product" onclick="openAddModal()"><i class="bi bi-plus-circle"></i> Add New Product</button>`
            : '';
    }
    localStorage.setItem('activeManagementTab', target);
}

// Restore last tab on page load
// Realtime Synchronization
let latestOrderID = null;

function refreshOrders(isInitial = false) {
    const timestamp = new Date().getTime();
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 1;
    const limit = urlParams.get('limit') || 10;

    fetch(`../../JDE_ADMIN/backend/admin_api.php?action=get_all_orders&page=${page}&limit=${limit}&_=${timestamp}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const orders = data.orders;
                if (!orders || orders.length === 0) return;

                const currentMaxID = Math.max(...orders.map(o => o.orderID));

                // Track max ID to detect NEW ones
                if (!isInitial && latestOrderID !== null && currentMaxID > latestOrderID) {
                    // Could show toast here too
                    console.log(`Realtime: Detected ${orders.filter(o => o.orderID > latestOrderID).length} new orders!`);
                }

                latestOrderID = currentMaxID;
                window.allOrdersCache = orders; // Update global cache
                renderOrdersTable(orders);

                // --- REAL-TIME MODAL UPDATE ---
                // If an order details modal is open, refresh its data from the updated list
                if (window.currentViewingOrder) {
                    const updatedOrder = orders.find(o => o.orderID === window.currentViewingOrder.orderID);
                    if (updatedOrder) {
                        console.log('Realtime: Updating open order modal details...');
                        // Instead of a full re-viewOrder (which might reset scroll/state), 
                        // we just update the specific fields and re-call timeline/payment logic
                        syncModalWithOrder(updatedOrder);
                    }
                }

                if (typeof updateStatusCounts === 'function') updateStatusCounts();
                if (typeof applyOrderFilters === 'function') applyOrderFilters();
            }
        });
}

/**
 * syncModalWithOrder — Silently updates the open viewOrderModal without closing it
 * @param {Object} order 
 */
function syncModalWithOrder(order) {
    // 1. Update basic text
    const statusBadge = document.getElementById('paymentStatusBadge');
    const payStat = document.getElementById('viewPaymentStatus');
    const verifyGroup = document.getElementById('verifyActionsGroup');
    const modalHeaderStatus = document.querySelector('#viewOrderModal .modal-header .status-badge');

    // 2. Update payment info if changed
    if (payStat) {
        const pOption = (order.paymentOption || 'full').toLowerCase();
        const isFullPay = pOption === 'full';
        const pStatus = (order.paymentStatus || '').toLowerCase();
        const oStatus = (order.orderStatus || '').toLowerCase();
        const isApproved = (pStatus === 'approved' || oStatus === 'paid' || oStatus === 'completed' || oStatus === 'order completed');
        const isCompleted = ['completed', 'order completed', 'delivered', 'picked up'].includes(oStatus);
        
        const mName = (order.methodName || order.paymentMethod || '').toLowerCase();
        const isCOD = mName.includes('cash on delivery');
        const isCOP = mName.includes('cash on pickup') || mName.includes('cash on pick up');
        const isPickUp = (order.distributionMethod || '').toLowerCase().includes('pickup');

        let statusClass = 'pdr-value payment-status-text';
        let paymentStatusText = '';

        if (isFullPay) {
            if (isApproved || isCompleted) {
                statusClass += ' approved';
                paymentStatusText = 'PAID';
            } else {
                if (pStatus === 'pending' || pStatus === 'unpaid' || pStatus === '') {
                    statusClass += ' pending';
                    paymentStatusText = 'Pending Verification';
                } else if (pStatus === 'rejected') {
                    statusClass += ' rejected';
                    paymentStatusText = 'Payment Rejected';
                } else {
                    statusClass += ' ' + pStatus;
                    paymentStatusText = (order.paymentStatus || 'Unpaid').toUpperCase();
                }
            }
        } else {
            // Down Payment
            const isBalanceApproved = (order.balancePaymentStatus || '').toLowerCase() === 'approved';
            
            if (isCompleted || isBalanceApproved) {
                statusClass += ' approved';
                paymentStatusText = isBalanceApproved ? 'FULLY PAID' : 'PAID';
            } else {
                if (oStatus === 'awaiting balance' && (isCOD || isCOP)) {
                    statusClass += ' rejected'; // using red for awaiting cash
                    paymentStatusText = 'To be paid upon ' + (isPickUp ? 'Pickup' : 'Delivery');
                } else {
                    if (isApproved) {
                        statusClass += ' partially-paid';
                        paymentStatusText = 'Partially Paid';
                    } else {
                        statusClass += ' pending';
                        paymentStatusText = 'Pending Verification';
                    }
                }
            }
        }

        payStat.textContent = paymentStatusText;
        payStat.className = statusClass;
    }

    if (statusBadge) {
        const payStatus = (order.paymentStatus || 'unpaid').toLowerCase();
        const isGcash = (order.methodName || order.paymentMethod || '').toLowerCase().includes('gcash');

        if (isGcash) {
            statusBadge.className = 'payment-status-pill ' + payStatus;
            statusBadge.style.display = 'inline-flex';
            const icons = { pending: '⏳', approved: '✅', rejected: '❌', paid: '✅', unpaid: '⏳' };
            statusBadge.innerHTML = `<span class="status-icon">${icons[payStatus] || '💳'}</span> ${(order.paymentStatus || 'Unknown').toUpperCase()}`;
        } else {
            statusBadge.style.display = 'none';
        }

        // Pulse animation for feedback
        statusBadge.classList.add('pulse-success');
        setTimeout(() => statusBadge.classList.remove('pulse-success'), 1000);
    }

    // 3. Update header status badge
    const headerStatus = document.getElementById('viewOrderStatusBadge');
    if (headerStatus) {
        let statusText = order.orderStatus;
        const isCOD = (order.methodName || order.paymentMethod || '').toLowerCase().includes('cash on delivery');
        const isCOP = (order.methodName || order.paymentMethod || '').toLowerCase().includes('cash on pickup') || (order.methodName || order.paymentMethod || '').toLowerCase().includes('cash on pick up');
        const isPickUp = (order.distributionMethod || '').toLowerCase().includes('pickup');

        if (statusText.toLowerCase() === 'awaiting balance' && (isCOD || isCOP)) {
            statusText = isPickUp ? 'To be paid upon Pickup' : 'To be paid upon Delivery';
        }

        headerStatus.textContent = statusText;
        
        // Ensure dynamic labels use the 'awaiting-balance' CSS class for styling
        const isCODLabel = statusText.toLowerCase().includes('to be paid');
        const statusClass = isCODLabel ? 'awaiting-balance cod-awaiting' : order.orderStatus.toLowerCase().replace(/ /g, '-');
        headerStatus.className = 'status-badge ' + statusClass;
        headerStatus.classList.add('pulse-success');
        setTimeout(() => headerStatus.classList.remove('pulse-success'), 1000);
    }

    // Hide/Show verify button based on new status
    if (verifyGroup) {
        const isGcash = (order.methodName || order.paymentMethod || '').toLowerCase().includes('gcash');
        const payStatus = (order.paymentStatus || 'unpaid').toLowerCase();
        const needsVerification = isGcash && (payStatus === 'pending' || payStatus === 'unpaid');
        verifyGroup.style.display = needsVerification ? '' : 'none';
    }

    // 4. Update Balances
    const dpVal = document.getElementById('viewDownpayment');
    const balVal = document.getElementById('viewBalance');
    const isVerified = (order.paymentStatus || '').toLowerCase() === 'approved';
    const isGCash = (order.paymentMethod || order.methodName || '').toLowerCase().includes('gcash');

    if (dpVal && balVal) {
        const downpayment = parseFloat(order.downPaymentAmount || 0);
        const total = parseFloat(order.totalPrice || 0);
        const balance = parseFloat(order.paymentBalance || total);

        const isFinal = ['Completed', 'Order Completed', 'Delivered', 'Picked Up'].includes(order.orderStatus);

        if (isFinal || isVerified) {
            dpVal.textContent = '₱0.00';
            const balToDisplay = isFinal ? 0 : balance;
            balVal.textContent = '₱' + balToDisplay.toLocaleString(undefined, { minimumFractionDigits: 2 });
        } else {
            dpVal.textContent = '₱' + downpayment.toLocaleString(undefined, { minimumFractionDigits: 2 });
            balVal.textContent = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2 });
            balVal.className = 'fw-bold text-danger';
        }
    }

    // 4.5 Update Payment Option
    const payOptEl = document.getElementById('viewPaymentOption');
    if (payOptEl) {
        const option = order.paymentOption || 'full';
        payOptEl.textContent = option === 'full' ? 'Full Payment' : '50% Downpayment';
        payOptEl.className = option === 'full' ? 'fw-bold text-primary' : 'fw-bold text-warning';
    }

    // 5. Update Timeline
    if (typeof updateTimeline === 'function') {
        updateTimeline(order.orderStatus, order.distributionMethod, parseInt(order.isCustom) === 1, order.methodName || order.paymentMethod, order.paymentOption);
    }

    // 6. Update Return/Cancellation info
    const returnArea = document.getElementById('returnInfoArea');
    const cancelArea = document.getElementById('cancelInfoArea');

    if (returnArea) {
        if (order.orderStatus === 'Return Items' && (order.returnReason || order.returnRemarks)) {
            returnArea.style.display = '';
            const viewReturnReason = document.getElementById('viewReturnReason');
            const viewReturnRemarks = document.getElementById('viewReturnRemarks');
            if (viewReturnReason) viewReturnReason.textContent = order.returnReason || 'N/A';
            if (viewReturnRemarks) viewReturnRemarks.textContent = order.returnRemarks || 'N/A';
        } else {
            returnArea.style.display = 'none';
        }
    }

    if (cancelArea) {
        if (order.orderStatus === 'Cancelled' && order.cancelReason) {
            cancelArea.style.display = '';
            const viewCancelReason = document.getElementById('viewCancelReason');
            if (viewCancelReason) viewCancelReason.textContent = order.cancelReason || 'N/A';
        } else {
            cancelArea.style.display = 'none';
        }
    }

    // 6. Update Action Buttons
    const updateBtn = document.getElementById('modalUpdateBtn');
    const deleteBtn = document.getElementById('modalDeleteBtn');

    if (updateBtn) {
        const status = (order.orderStatus || '').toLowerCase();
        // Allow updates unless it is Cancelled or already in Return Items or Completed
        const canUpdate = (status !== 'cancelled' && status !== 'return items' && !status.includes('completed'));

        const payStatus = (order.paymentStatus || '').toLowerCase();
        const bStatus = (order.balancePaymentStatus || '').toLowerCase();
        const isCOD = (order.methodName || order.paymentMethod || '').toLowerCase().includes('cash on delivery');

        // Reset Styles
        updateBtn.disabled = false;
        updateBtn.style.opacity = '1';
        updateBtn.style.cursor = 'pointer';
        updateBtn.style.display = '';
        updateBtn.classList.remove('btn-locked');

        let shouldShowUpdate = true;

        if (!canUpdate) {
            shouldShowUpdate = false;
        } else {
            // ── Stage 1: Pending / Downpayment / Paid ─────────────────────────────
            // ALL payment methods must have initial payment approved before Update Status shows.
            // Phase 1: Initial or Payment-pending states
            if (['pending', 'unpaid', 'order placed', 'downpayment', 'paid'].includes(status)) {
                if (payStatus !== 'approved' && payStatus !== 'paid') {
                    shouldShowUpdate = false;
                }

            // ── Stage 2: Processing / Tailoring ───────────────────────────────────
            // Block ALL methods if customer uploaded an early balance receipt that's still pending.
            } else if (['processing', 'tailoring in progress'].includes(status)) {
                const balanceGroup = document.getElementById('orderBalanceReceiptThumbGroup');
                const hasUploadedBalance = balanceGroup && balanceGroup.style.display !== 'none';
                if (hasUploadedBalance && bStatus === 'pending') {
                    shouldShowUpdate = false;
                }

            // ── Stage 3: Awaiting Balance ──────────────────────────────────────────
            // ALL payment methods (except COD/COP) must have balance verified before proceeding.
            // Handles dynamic labels: "To be paid upon Delivery", "To be paid upon Pickup", etc.
            } else if (['awaiting balance', 'to be paid upon delivery', 'to be paid upon pickup'].includes(status)) {
                const hasNoBalance = parseFloat(order.paymentBalance || 0) <= 0;
                const mName = (order.methodName || order.paymentMethod || '').toLowerCase();
                const isCOD_COP = isCOD || (mName.includes('cash on pickup') || mName.includes('cash on pick up'));
                
                if (bStatus !== 'approved' && !hasNoBalance && !isCOD_COP) {
                    shouldShowUpdate = false;
                }
            }
        }

        if (!shouldShowUpdate) {
            // Hide the button entirely when payment is not yet verified
            updateBtn.style.display = 'none';
        } else {
            updateBtn.style.display = '';
            updateBtn.style.opacity = '1';
            updateBtn.style.cursor = 'pointer';
            updateBtn.title = 'Update order status';
            updateBtn.classList.remove('btn-locked');
            updateBtn.innerHTML = '<i class="bi bi-pencil-square"></i> UPDATE STATUS';
            updateBtn.onclick = (e) => {
                e.stopPropagation();
                updateOrderStatus(order.orderID, order.orderStatus, order.balancePaymentStatus);
            };
        }
    }

    if (deleteBtn) {
        const isFinal = ['Completed', 'Order Completed', 'Delivered', 'Picked Up', 'Cancelled'].includes(order.orderStatus);
        deleteBtn.style.display = isFinal ? 'none' : '';
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            confirmDeleteOrder(order.orderID, order.customerName);
        };
    }

    // 7. Update Proof of Delivery (Real-time)
    const proofArea = document.getElementById('proofOfDeliveryArea');
    const proofImg = document.getElementById('viewProofImage');
    if (proofArea && proofImg) {
        const distMethod = (order.distributionMethod || '').toLowerCase();
        const isDelivery = distMethod === 'delivery';
        const oStatus = (order.orderStatus || '').toLowerCase();
        const isCompleted = (oStatus.includes('completed') || oStatus === 'delivered');
        
        const podVal = (order.proofOfDelivery || '').trim();
        const dirPath = 'assets/img/proofs/';
        const hasValidPath = podVal !== '' && 
                            podVal.toLowerCase() !== 'null' && 
                            podVal.toLowerCase() !== 'undefined' &&
                            podVal.includes(dirPath) && 
                            podVal.length > dirPath.length &&
                            !podVal.includes('default') && 
                            !podVal.includes('placeholder');

        if (isDelivery && isCompleted && hasValidPath) {
            const cleanPath = podVal.replace('../', '');
            // Use cache-buster to prevent stale previews
            proofImg.src = '../../JDE_USER/' + cleanPath + '?t=' + Date.now();
            proofArea.style.display = 'block';
            proofImg.onerror = () => { 
                proofArea.style.display = 'none';
                proofImg.src = ''; 
            };
        } else {
            proofArea.style.display = 'none';
            proofImg.src = ''; 
        }
    }

    // 8. Update cached object
    window.currentViewingOrder = order;
}

function renderOrdersTable(orders) {
    const tbody = document.querySelector('#ordersTable tbody');
    if (!tbody) return;

    let html = '';
    orders.forEach(order => {
        const id = order.orderID;
        const img = order.productImage ? `../../JDE_USER/${order.productImage.replace('../', '')}` : '../../JDE_USER/assets/img/logojd.png';
        const name = order.productName || 'No Items/Custom';
        const customer = order.customerName;
        const date = order.orderDate ? new Date(order.orderDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
        const total = parseFloat(order.totalPrice).toLocaleString(undefined, { minimumFractionDigits: 2 });
        const status = order.orderStatus;
        let displayStatus = status;
        let statusClass = status.toLowerCase().replace(/ /g, '-');
        
        if (status.toLowerCase() === 'awaiting balance') {
            const mName = (order.methodName || order.paymentMethod || '').toLowerCase();
            const isCOD_COP = mName.includes('cash on delivery') || mName.includes('cash on pickup') || mName.includes('cash on pick up');
            
            if (isCOD_COP) {
                statusClass += ' cod-awaiting';
                const dist = (order.distributionMethod || '').toLowerCase();
                const isPickUp = dist.includes('pickup') || dist.includes('pick up');
                displayStatus = isPickUp ? 'To be paid upon Pickup' : 'To be paid upon Delivery';
            }
        }
        // Proof of Payment Badge
        const paymentProofBadge = order.proofOfPayment ? `<div class="payment-method-badge gcash"><i class="bi bi-image"></i> Receipt</div>` : '<span class="text-muted small">Standard</span>';

        // Proof of Delivery Badge (Strict Conditions)
        const distMethod = (order.distributionMethod || '').toLowerCase();
        const isDelivery = distMethod === 'delivery';
        const oStatus = (order.orderStatus || '').toLowerCase();
        const isCompleted = (oStatus === 'completed' || oStatus === 'delivered' || oStatus === 'order completed');
        
        const podVal = (order.proofOfDelivery || '').trim();
        const hasValidPath = podVal !== '' && 
                            podVal.toLowerCase() !== 'null' && 
                            podVal.toLowerCase() !== 'undefined' &&
                            podVal.includes('assets/img/proofs/') && 
                            !podVal.includes('default') && 
                            !podVal.includes('placeholder');

        let deliveryProofBadge = '<span class="text-muted small">-</span>';
        if (isDelivery && isCompleted && hasValidPath) {
            deliveryProofBadge = `
                <div class="payment-method-badge proof-delivery" onclick="event.stopPropagation(); viewOrder(${id})">
                    <i class="bi bi-camera"></i> Proof
                </div>
            `;
        }

        const catName = order.categoryName || '';
        let catRender = '-';
        if (catName.toLowerCase().includes('men') && !catName.toLowerCase().includes('women')) catRender = 'Men';
        else if (catName.toLowerCase().includes('women')) catRender = 'Women';

        const isActive = parseInt(order.isActive) === 1;
        const rowClass = isActive ? 'clickable-row' : 'clickable-row archived-row';

        html += `
            <tr data-row-id="${id}" data-status="${status}" data-active="${order.isActive}" data-date="${order.orderDate ? new Date(order.orderDate).toISOString().split('T')[0] : ''}" class="${rowClass}" onclick="viewOrder(${id})">
                <td>#ORD-${String(id).padStart(3, '0')}</td>
                <td class="text-center-cell">
                    <div class="prod-img-preview"><img src="${img}" alt="Order Product" loading="lazy" onerror="this.src='../../JDE_USER/assets/img/logojd.png'"></div>
                </td>
                <td><strong>${name}</strong></td>
                <td class="text-center-cell">${catRender}</td>
                <td>${customer}</td>
                <td>${date}</td>
                <td class="text-center-cell">₱${total}</td>
                <td class="text-center-cell">${paymentProofBadge}</td>
                <td class="text-center-cell">${deliveryProofBadge}</td>
                <td class="text-center-cell">
                    <span class="status-badge ${statusClass}" style="cursor: pointer;" onclick="event.stopPropagation(); updateOrderStatus(${id}, '${status}', '${order.balancePaymentStatus || ''}')">${displayStatus}</span>
                </td>
            </tr>
        `;
    });

    if (orders.length === 0) {
        html = '<tr id="orderEmptyState" class="empty-state-row"><td colspan="10"><i class="bi bi-inbox"></i> No orders match your filter.</td></tr>';
    }

    tbody.innerHTML = html;
}

window.refreshDashboardStats = refreshOrders;

document.addEventListener('DOMContentLoaded', () => {
    refreshOrders(true); // Initial load
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tab));
    });
    // Check for URL parameters (tab and search)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const searchParam = urlParams.get('search');

    if (tabParam && document.getElementById(tabParam + 'Section')) {
        activateTab(tabParam);
    } else {
        const savedTab = localStorage.getItem('activeManagementTab');
        if (savedTab && document.getElementById(savedTab + 'Section')) {
            activateTab(savedTab);
        }
    }

    if (searchParam) {
        const searchInput = tabParam === 'orders' ? document.getElementById('orderSearchInput') : document.getElementById('prodSearchInput');
        if (searchInput) {
            searchInput.value = searchParam;
            if (tabParam === 'orders') applyOrderFilters();
            else applyProdFilters();
        }
    }

    // Handle orderID from notifications
    const orderIDParam = urlParams.get('orderID');
    if (orderIDParam) {
        const checkExist = setInterval(() => {
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            if (rows.length > 1 || (rows.length === 1 && rows[0].id !== 'orderEmptyState')) {
                const row = document.querySelector(`tr[data-row-id="${orderIDParam}"]`);
                if (row) {
                    row.click();
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.style.backgroundColor = 'rgba(1, 43, 67, 0.05)';
                    setTimeout(() => row.style.backgroundColor = '', 3000);
                }
                clearInterval(checkExist);
            }
        }, 500);
        setTimeout(() => clearInterval(checkExist), 5000);
    }

    // Initial status counts for orders
    if (typeof updateStatusCounts === 'function') {
        updateStatusCounts();
    }

    // Make tables sortable
    if (typeof makeTableSortable === 'function') {
        makeTableSortable('ordersTable');
    }

    // Flatpickr initialization
    const flatpickrConfig = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        disableMobile: true,
        monthSelectorType: "dropdown",
        scrollInput: false,
        prevArrow: '<i class="bi bi-chevron-left"></i>',
        nextArrow: '<i class="bi bi-chevron-right"></i>',
        onChange: () => {
            if (typeof applyOrderFilters === 'function') applyOrderFilters();
        }
    };

    if (document.getElementById('orderDateFrom')) flatpickr("#orderDateFrom", flatpickrConfig);
    if (document.getElementById('orderDateTo')) {
        flatpickr("#orderDateTo", {
            ...flatpickrConfig,
            position: "auto right" // Ensures calendar opens leftward so it's not cut off on the right
        });
    }
});

// =====================================================================
// 2. IMAGE PREVIEW
// =====================================================================
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '../../JDE_USER/assets/img/logojd.png';
    }
}

// =====================================================================
// 3. PRODUCT CRUD MODALS
// =====================================================================
const productModal = document.getElementById('productModal');
const productForm = document.getElementById('productForm');

function openAddModal() {
    if (!productForm) return;
    productForm.reset();
    document.getElementById('prodAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add New Product';
    document.getElementById('imagePreview').src = '../../JDE_USER/assets/img/logojd.png';
    document.getElementById('prodCollection').value = '';
    document.getElementById('prodType').value = '';
    document.getElementById('prodFitType').value = '';
    productModal.classList.add('active');
}

function editProduct(prod) {
    document.getElementById('modalTitle').textContent = 'Modify Product';
    document.getElementById('prodAction').value = 'update';
    document.getElementById('prodID').value = prod.productID;
    document.getElementById('prodName').value = prod.productName;
    document.getElementById('prodCat').value = prod.categoryID;
    document.getElementById('prodPrice').value = prod.price;
    document.getElementById('prodStock').value = prod.stocks;
    document.getElementById('prodSize').value = prod.size;
    document.getElementById('prodCollection').value = prod.collection || '';
    document.getElementById('prodType').value = prod.type || '';
    document.getElementById('prodFitType').value = prod.fitType || '';
    document.getElementById('prodDesc').value = prod.description;
    document.getElementById('currentImagePath').value = prod.productImage || '';
    const preview = document.getElementById('imagePreview');
    preview.src = prod.productImage
        ? '../../JDE_USER/' + prod.productImage.replace('../', '')
        : '../../JDE_USER/assets/img/logojd.png';
    productModal.classList.add('active');
}

function viewProduct(prod) {
    const viewModal = document.getElementById('viewProductModal');
    if (!viewModal) return;

    // Populate fields
    document.getElementById('detailProdID').textContent = '#P-' + String(prod.productID).padStart(3, '0');
    document.getElementById('detailCreatedBy').textContent = prod.creatorName || 'Admin';
    document.getElementById('detailUpdatedBy').textContent = prod.updatedBy || 'Not yet updated';
    document.getElementById('detailProdName').textContent = prod.productName;
    document.getElementById('detailProdCat').textContent = prod.categoryName;

    // Determine gender for detail view
    let gender = '-';
    if (prod.categoryName.toLowerCase().includes('men')) gender = 'Men';
    else if (prod.categoryName.toLowerCase().includes('women')) gender = 'Women';
    document.getElementById('detailProdGender').textContent = gender;

    document.getElementById('detailProdPrice').textContent = '₱' + parseFloat(prod.price).toLocaleString(undefined, { minimumFractionDigits: 2 });
    document.getElementById('detailProdStock').textContent = prod.stocks;
    document.getElementById('detailProdSize').textContent = prod.size;
    document.getElementById('detailProdCollection').textContent = prod.collection || 'N/A';
    document.getElementById('detailProdType').textContent = prod.type || 'N/A';
    document.getElementById('detailProdFitType').textContent = prod.fitType || 'N/A';
    document.getElementById('detailProdDesc').textContent = prod.description || 'No description provided.';

    const img = document.getElementById('detailProdImage');
    img.src = prod.productImage
        ? '../../JDE_USER/' + prod.productImage.replace('../', '')
        : '../../JDE_USER/assets/img/logojd.png';

    // Set up Edit button
    const editBtn = document.getElementById('editFromViewBtn');
    editBtn.onclick = () => {
        closeViewProductModal();
        editProduct(prod);
    };

    viewModal.classList.add('active');
}

function closeViewProductModal() {
    const el = document.getElementById('viewProductModal');
    if (el) el.classList.remove('active');
}

function closeProductModal() {
    if (!productForm) return;
    productForm.reset();
    document.getElementById('imagePreview').src = '../../JDE_USER/assets/img/logojd.png';
    document.getElementById('prodCollection').value = '';
    document.getElementById('prodType').value = '';
    document.getElementById('prodFitType').value = '';
    if (productModal) productModal.classList.remove('active');
}

// --- CUSTOM CONFIRM MODAL HELPERS ---
function confirmDelete(id, name) {
    showConfirmModal(
        "Confirm Archive",
        `Are you sure you want to archive <strong>${name}</strong>? It will be moved to the Trash Bin but preserved for order history.`,
        () => {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('productID', id);

            fetch('../../JDE_USER/backend/product.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Product Archived', `${name} moved to trash.`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error', data.message || 'Archive failed.', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error', 'Connection failed.', 'danger');
                });
        }
    );
}

function confirmDeleteOrder(id, customer) {
    showConfirmModal(
        "Confirm Deletion",
        `Are you sure you want to archive Order #ORD-${String(id).padStart(3, '0')} for ${customer}?`,
        () => {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('orderID', id);
            fetch('../backend/order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Order Archived', 'Order moved to archives.', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error', data.message || 'Action failed.', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error', 'Connection failed.', 'danger');
                });
        }
    );
}

// Restore Product from Archive
function restoreProduct(id, name) {
    showConfirmModal(
        "Restore Product",
        `Are you sure you want to restore <strong>${name}</strong>? It will appear back in the active inventory list.`,
        () => {
            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('productID', id);

            fetch('../../JDE_USER/backend/product.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Product Restored', `${name} is back in active inventory.`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error', data.message || 'Restoration failed.', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error', 'Connection failed.', 'danger');
                });
        }
    );
}

// Permanent Delete
function confirmPermanentDelete(id, name) {
    showConfirmModal(
        "Permanent Deletion",
        `DANGER: You are about to permanently remove <strong>${name}</strong> from the database. This will also remove its image file. <br><br><strong>This action cannot be undone.</strong>`,
        () => {
            const formData = new FormData();
            formData.append('action', 'permanent_delete');
            formData.append('productID', id);

            fetch('../../JDE_USER/backend/product.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Permanently Deleted', 'Product removed from database.', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        // This will happen if the product exists in order items
                        showConfirmModal(
                            "Cannot Delete",
                            data.message,
                            null,
                            true // alert mode
                        );
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error', 'Connection failed.', 'danger');
                });
        }
    );
}

// Global confirm modal helper
function showConfirmModal(title, message, onOk, isAlert = false) {
    const modal = document.getElementById('confirmModal');
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmMessage').innerHTML = message;

    const okBtn = document.getElementById('confirmOkBtn');
    const cancelBtn = document.getElementById('confirmCancelBtn');

    // Reset listener
    const newOk = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);

    if (isAlert) {
        cancelBtn.style.display = 'none';
        newOk.innerText = "Understood";
        newOk.onclick = () => modal.classList.remove('active');
    } else {
        cancelBtn.style.display = '';
        newOk.innerText = "Yes, Proceed";
        newOk.onclick = () => {
            if (onOk) onOk();
            modal.classList.remove('active');
        };
    }

    cancelBtn.onclick = () => modal.classList.remove('active');
    modal.classList.add('active');
}

if (productForm) {
    productForm.onsubmit = function (e) {
        e.preventDefault();
        fetch('../../JDE_USER/backend/product.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Success!', 'Product saved.', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error', data.message || 'Operation failed.', 'danger');
                }
            })
            .catch(() => showToast('Error', 'An unexpected error occurred.', 'danger'));
    };
}

document.querySelectorAll('.inline-stock').forEach(badge => {
    badge.style.cursor = 'pointer';
    badge.addEventListener('click', function () {
        const productID = this.dataset.id;
        const currentVal = parseInt(this.textContent);
        const input = document.createElement('input');
        input.type = 'number';
        input.value = isNaN(currentVal) ? 0 : currentVal;
        input.min = 0;
        input.className = 'inline-stock-input';
        this.replaceWith(input);
        input.focus();
        input.select();

        const save = () => {
            const newStock = parseInt(input.value);
            const fd = new FormData();
            fd.append('action', 'update_stock');
            fd.append('productID', productID);
            fd.append('newStock', newStock);
            fetch('../backend/order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const span = document.createElement('span');
                        span.className = 'stock-badge inline-stock' + (newStock < 5 ? ' low' : '');
                        span.dataset.id = productID;
                        span.title = 'Click to edit stock';
                        span.style.cursor = 'pointer';
                        span.textContent = newStock < 5 ? newStock + ' Low' : newStock;
                        input.replaceWith(span);
                        span.addEventListener('click', save); // Note: updated from arguments.callee
                        showToast('Stock Updated', `New stock: ${newStock}`, 'success');
                    } else {
                        showToast('Error', data.message || 'Failed to update stock.', 'danger');
                        input.replaceWith(badge);
                    }
                });
        };

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') save();
            if (e.key === 'Escape') input.replaceWith(badge);
        });
        input.addEventListener('blur', save);
    });
});

// Re-attach click for dynamically created inline-stock elements
const productsTable = document.getElementById('productsTable');
if (productsTable) {
    productsTable.addEventListener('click', function (e) {
        const badge = e.target.closest('.inline-stock');
        if (badge && badge.tagName === 'SPAN') badge.click();
    });
}

// =====================================================================
// 5. SEARCH + FILTER + EMPTY STATE
// =====================================================================
// --- PRODUCT FILTERING & SEARCH ---
let showArchives = false;

function applyProdFilters() {
    const category = document.getElementById('prodCategoryFilter').value;
    const searchTerm = document.getElementById('prodSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#productsTable tbody tr');

    rows.forEach(row => {
        if (row.id === 'prodEmptyState') return;
        const rowCategory = row.getAttribute('data-category');
        const rowName = row.querySelector('td:nth-child(3)').innerText.toLowerCase();
        const rowId = row.querySelector('td:first-child').innerText.toLowerCase();
        const isActive = row.getAttribute('data-active') === '1';

        const categoryMatch = (category === 'all' || rowCategory === category);
        const searchMatch = (rowName.includes(searchTerm) || rowId.includes(searchTerm));
        const archiveMatch = showArchives ? !isActive : isActive;

        if (categoryMatch && searchMatch && archiveMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    const emptyState = document.getElementById('prodEmptyState');
    if (emptyState) emptyState.style.display = Array.from(rows).some(row => row.style.display !== 'none') ? 'none' : '';
}

function toggleArchives() {
    showArchives = !showArchives;
    const btn = document.getElementById('toggleArchivesBtn');
    if (showArchives) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="bi bi-trash3-fill"></i> View Active';
    } else {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="bi bi-trash3"></i> View Trash';
    }
    applyProdFilters();
}

/**
 * updateProductRow — standard dynamic UI update for real-time inventory
 */
function updateProductRow(prod) {
    const tbody = document.querySelector('#productsTable tbody');
    if (!tbody) return; // Silent return if we are in orders-only view

    // Construct or Select Row
    let row = document.querySelector(`tr[data-row-id="${prod.productID}"]`);
    const isNew = !row;

    if (isNew) {
        row = document.createElement('tr');
        row.setAttribute('data-row-id', prod.productID);
    }

    // Update attributes
    row.setAttribute('data-category', prod.categoryName);
    row.setAttribute('data-active', prod.isActive);
    row.className = parseInt(prod.isActive) === 0 ? 'archived-row' : '';

    const fmtID = '#P-' + String(prod.productID).padStart(3, '0');
    const imgSrc = prod.productImage
        ? '../../JDE_USER/' + prod.productImage.replace('../', '')
        : '../../JDE_USER/assets/img/logojd.png';

    const gender = prod.categoryName.toLowerCase().includes('men') ? 'Men' :
        (prod.categoryName.toLowerCase().includes('women') ? 'Women' : '-');

    const stockClass = parseInt(prod.stocks) < 5 ? 'stock-badge low inline-stock' : 'stock-badge inline-stock';
    const stockText = parseInt(prod.stocks) < 5 ? `${prod.stocks} Low` : prod.stocks;

    const rowContent = `
        <td>${fmtID}</td>
        <td>
            <div class="prod-img-preview">
                <img src="${imgSrc}" alt="Product" loading="lazy" onerror="this.src='../../JDE_USER/assets/img/logojd.png'">
            </div>
        </td>
        <td><strong>${prod.productName}</strong></td>
        <td><span class="category-tag">${prod.categoryName}</span></td>
        <td class="text-center-cell">${gender}</td>
        <td class="text-center-cell">${prod.size}</td>
        <td>₱${parseFloat(prod.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
        <td>
            <span class="${stockClass}" data-id="${prod.productID}" title="Click to edit stock">${stockText}</span>
        </td>
        <td>
            <div class="action-dropdown">
                <button class="action-trigger"><i class="bi bi-three-dots-vertical"></i></button>
                <div class="action-menu">
                    <button class="action-item" onclick="viewProduct(${JSON.stringify(prod).replace(/"/g, '&quot;')})">
                        <i class="bi bi-eye"></i> View Details
                    </button>
                    ${parseInt(prod.isActive) === 1 ? `
                        <button class="action-item" onclick="editProduct(${JSON.stringify(prod).replace(/"/g, '&quot;')})">
                            <i class="bi bi-pencil"></i> Edit Product
                        </button>
                        <button class="action-item delete" onclick="confirmDelete(${prod.productID}, '${prod.productName.replace(/'/g, "\\'")}')">
                            <i class="bi bi-archive"></i> Archive Product
                        </button>
                    ` : `
                        <button class="action-item restore" onclick="restoreProduct(${prod.productID}, '${prod.productName.replace(/'/g, "\\'")}')">
                            <i class="bi bi-arrow-counterclockwise"></i> Restore Product
                        </button>
                        <button class="action-item delete-permanent" onclick="confirmPermanentDelete(${prod.productID}, '${prod.productName.replace(/'/g, "\\'")}')">
                            <i class="bi bi-trash-fill"></i> Delete Permanently
                        </button>
                    `}
                </div>
            </div>
        </td>
    `;

    row.innerHTML = rowContent;

    if (isNew) {
        tbody.prepend(row);
    }

    applyProdFilters();
}

const prodSearchInput = document.getElementById('prodSearchInput');
if (prodSearchInput) prodSearchInput.addEventListener('keyup', applyProdFilters);

const prodCatFilter = document.getElementById('prodCategoryFilter');
if (prodCatFilter) prodCatFilter.addEventListener('change', applyProdFilters);

let activeOrderTriageFilter = 'all';
let showOrderArchives = false;

function toggleOrderArchives() {
    showOrderArchives = !showOrderArchives;
    const btn = document.getElementById('toggleOrderArchiveBtn');
    if (showOrderArchives) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="bi bi-archive-fill"></i> View Active';
    } else {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="bi bi-archive"></i> View Archive';
    }
    applyOrderFilters();
}

function updateStatusCounts() {
    const counts = { all: 0, Unpaid: 0, Paid: 0, Processing: 0, 'Awaiting Balance': 0, 'Out for delivery': 0, 'Ready to Deliver': 0, Completed: 0, Cancelled: 0, 'Return Items': 0 };
    document.querySelectorAll('#ordersTable tbody tr:not(#orderEmptyState)').forEach(tr => {
        const isActive = tr.dataset.active === '1';
        if (!isActive) return;

        counts.all++;
        const s = tr.dataset.status;
        if (s === 'Delivered' || s === 'Completed' || s === 'Picked Up') {
            counts['Completed']++;
        } else if (s === 'Ready to Deliver' || s === 'Ready for Pick Up') {
            counts['Ready to Deliver']++;
        } else if (s === 'Awaiting Balance') {
            counts['Awaiting Balance']++;
        } else if (counts[s] !== undefined || counts[s] === 0) {
            counts[s]++;
        }
    });

    for (const [status, count] of Object.entries(counts)) {
        const id = 'count-' + status.toLowerCase().replace(/\s+/g, '-');
        const el = document.getElementById(id);
        if (el) el.textContent = count;
    }
}

function applyOrderFilters() {
    const termEl = document.getElementById('orderSearchInput');
    const dateFromEl = document.getElementById('orderDateFrom');
    const dateToEl = document.getElementById('orderDateTo');
    if (!termEl) return;

    const term = termEl.value.toLowerCase();
    const status = activeOrderTriageFilter;
    const dateFrom = dateFromEl ? dateFromEl.value : '';
    const dateTo = dateToEl ? dateToEl.value : '';
    let visible = 0;

    document.querySelectorAll('#ordersTable tbody tr:not(#orderEmptyState)').forEach(tr => {
        const matchTerm = !term || tr.innerText.toLowerCase().includes(term);
        const rowStatus = (tr.dataset.status || '').toLowerCase();
        const filterStatus = status.toLowerCase();
        const isMatchExact = rowStatus === filterStatus;
        const isMatchGroup = 
            (filterStatus === 'completed' && (rowStatus.includes('completed') || rowStatus === 'delivered' || rowStatus === 'picked up')) ||
            (filterStatus === 'ready to deliver' && (rowStatus.includes('ready for pick up') || rowStatus === 'ready to deliver'));

        const matchStatus = status === 'all' || isMatchExact || isMatchGroup;
        const rowDate = tr.dataset.date || '';
        const matchFrom = !dateFrom || rowDate >= dateFrom;
        const matchTo = !dateTo || rowDate <= dateTo;

        const isActive = tr.dataset.active === '1';
        const matchActive = showOrderArchives ? !isActive : isActive;

        const show = matchTerm && matchStatus && matchFrom && matchTo && matchActive;
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const emptyState = document.getElementById('orderEmptyState');
    if (emptyState) emptyState.style.display = visible === 0 ? '' : 'none';
}

// Triage Tab Listeners
document.querySelectorAll('.triage-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.triage-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        activeOrderTriageFilter = tab.dataset.filter;
        applyOrderFilters();
    });
});

const orderSearchInput = document.getElementById('orderSearchInput');
if (orderSearchInput) orderSearchInput.addEventListener('keyup', applyOrderFilters);

// Flatpickr initialization for global scope if needed (Cleaning up duplicates)
// (Removed previously duplicated initialization logic)

// =====================================================================
// 6. SORTABLE COLUMNS
// =====================================================================
function makeTableSortable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const state = {};

    table.querySelectorAll('th.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const col = parseInt(th.dataset.col);
            state[col] = !state[col]; // toggle asc/desc
            const asc = state[col];

            // Update sort icons
            table.querySelectorAll('th.sortable .sort-icon').forEach(ic => ic.textContent = '↕');
            th.querySelector('.sort-icon').textContent = asc ? '↑' : '↓';

            const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-state-row)'));
            rows.sort((a, b) => {
                const av = a.cells[col]?.innerText.trim() ?? '';
                const bv = b.cells[col]?.innerText.trim() ?? '';
                const an = parseFloat(av.replace(/[^0-9.-]/g, ''));
                const bn = parseFloat(bv.replace(/[^0-9.-]/g, ''));
                if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
                return asc ? av.localeCompare(bv) : bv.localeCompare(av);
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });
}

// =====================================================================
// 7. ORDER MANAGEMENT (Status modal + View modal)
// =====================================================================
orderStatusModal = document.getElementById('orderStatusModal');

// Show/hide reason textarea and proof group based on status
const newStatusSelect = document.getElementById('newStatus');
if (newStatusSelect) {
    newStatusSelect.addEventListener('change', function () {
        const cancelGroup = document.getElementById('cancelReasonGroup');
        const proofGroup = document.getElementById('proofOfDeliveryGroup');
        const returnGroup = document.getElementById('returnItemsGroup');
        const isDelivery = window.currentViewingOrder &&
            window.currentViewingOrder.distributionMethod &&
            window.currentViewingOrder.distributionMethod.toLowerCase() === 'delivery';

        if (cancelGroup) cancelGroup.style.display = this.value === 'Cancelled' ? '' : 'none';
        if (proofGroup) proofGroup.style.display = (isDelivery && (this.value === 'Delivered' || this.value === 'Completed')) ? '' : 'none';
        if (returnGroup) returnGroup.style.display = this.value === 'Return Items' ? '' : 'none';
    });
}

function quickUpdateStatus(id, newStatus, message) {
    showConfirmModal('Quick Status Update', message, () => {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('orderID', id);
        fd.append('status', newStatus);
        fd.append('cancelReason', '');

        fetch('../backend/order_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', `Order #${id} updated to ${newStatus}`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error', data.message || 'Update failed.', 'danger');
                }
            });
    });
}

/**
 * Returns the correct "balance step" label for the given payment method.
 * COD → "To be paid upon Delivery"
 * COP → "To be paid upon Pickup"
 * Others → "Awaiting Balance"
 */
function getPaymentBalanceLabel(methodName) {
    const m = (methodName || '').toLowerCase();
    if (m.includes('cash on delivery')) return 'To be paid upon Delivery';
    if (m.includes('cash on pickup') || m.includes('cash on pick up')) return 'To be paid upon Pickup';
    return 'Awaiting Balance';
}

function updateOrderStatus(id, currentStatus, balanceStatus = '') {
    console.log('Update Status Triggered:', { id, currentStatus, balanceStatus });

    // --- CRITICAL: Always resolve the CORRECT order from cache by ID ---
    // window.currentViewingOrder may point to a different order (or be null)
    // if the admin clicks the status badge without first opening the modal.
    const freshOrder = (window.allOrdersCache || []).find(o => String(o.orderID) === String(id));
    if (freshOrder) {
        window.currentViewingOrder = freshOrder; // Sync state to the correct order
    } else if (!window.currentViewingOrder || String(window.currentViewingOrder.orderID) !== String(id)) {
        console.warn('updateOrderStatus: Order not in cache, proceeding with limited context.', id);
    }

    // Reset inputs at the START of the update flow to prevent leakage
    const inputs = ['proofOfDeliveryInput', 'cancelReasonInput', 'returnReasonInput', 'returnRemarksInput'];
    inputs.forEach(inputId => {
        const el = document.getElementById(inputId);
        if (el) el.value = '';
    });

    // 2. Fetch order context and build dynamic transitions
    const order = window.currentViewingOrder;
    const methodName = order ? (order.methodName || order.paymentMethod || '') : '';
    const isCOD = methodName.toLowerCase().includes('cash on delivery');
    const isCOP = methodName.toLowerCase().includes('cash on pickup') || methodName.toLowerCase().includes('cash on pick up');
    const balanceLabel = getPaymentBalanceLabel(methodName);
    const payStat = (order ? order.paymentStatus || '' : '').toLowerCase();
    const isInitialPaid = order && (payStat === 'approved' || payStat === 'paid');
    const hasNoBalance = order && parseFloat(order.paymentBalance || 0) <= 0;
    const isBalancePaid = order && ((order.balancePaymentStatus || '').toLowerCase() === 'approved' || hasNoBalance);

    // 1. Build transitions dynamically using the correct label for this method
    const allowedTransitions = {
        'Pending': ['Processing', 'Cancelled'],
        'Unpaid': ['Processing', 'Cancelled'],
        'Downpayment': ['Processing', 'Cancelled'],
        'Paid': ['Processing', 'Cancelled'],
        'Processing': [balanceLabel, 'Out for delivery', 'Ready for Pick Up', 'Cancelled'],
        // All three variants map to the same next steps (handles legacy statuses in DB too)
        'Awaiting Balance': ['Out for delivery', 'Ready for Pick Up', 'Cancelled'],
        'To be paid upon Delivery': ['Out for delivery', 'Ready for Pick Up', 'Cancelled'],
        'To be paid upon Pickup': ['Out for delivery', 'Ready for Pick Up', 'Cancelled'],
        'Out for delivery': ['Completed', 'Return Items', 'Cancelled'],
        'Ready for Pick Up': ['Completed', 'Return Items', 'Cancelled'],
        // Terminal states — only Return Items allowed
        'Completed': ['Return Items'],
        'Order Completed': ['Return Items'],
        'Delivered': ['Return Items'],
        'Picked Up': ['Return Items'],
        'Return Items': ['Cancelled']
    };

    // Enforcement Check for Awaiting Balance — applies to ALL payment methods EXCEPT COD/COP
    // Also covers the dynamic labels "To be paid upon Delivery" / "To be paid upon Pickup"
    const awaitingStatuses = ['awaiting balance', 'to be paid upon delivery', 'to be paid upon pickup'];
    if (awaitingStatuses.includes(currentStatus.toLowerCase()) && !isBalancePaid && !isCOD && !isCOP) {
        showToast('Action Required', 'Please verify and approve the remaining balance payment first.', 'warning');
        return;
    }

    // Enforcement Check for Processing with Early Balance Upload — applies to ALL payment methods
    const bStatus = (order ? order.balancePaymentStatus || '' : '').toLowerCase();
    const balanceGroup = document.getElementById('orderBalanceReceiptThumbGroup');
    const hasUploadedBalance = balanceGroup && balanceGroup.style.display !== 'none';

    if (['Processing', 'Tailoring in Progress'].includes(currentStatus)) {
        if (hasUploadedBalance && bStatus === 'pending') {
            showToast('Action Required', 'An early balance receipt was uploaded. Please verify it before updating the status.', 'warning');
            return;
        }
    }

    currentUpdateOrderID = id;
    const select = document.getElementById('newStatus');
    if (!select) {
        console.error('Update Status Error: Element #newStatus not found.');
        return;
    }

    // 3. Filter options based on current status and payment gates
    // Normalize status for lookup (e.g., 'Order Completed' -> 'Completed')
    const normalizedStatus = currentStatus.trim().replace(/^Order\s+/i, '');
    const lookupKey = Object.keys(allowedTransitions).find(k => k.toLowerCase() === normalizedStatus.toLowerCase()) || currentStatus;
    const possibleNext = allowedTransitions[lookupKey] || [];

    // Clear and rebuild options
    select.innerHTML = `<option value="${currentStatus}" selected>${currentStatus} (Current)</option>`;

    let method = order ? order.distributionMethod : '';
    const isPickUp = method && (method.toLowerCase().includes('pick') || method.toLowerCase().includes('pickup'));
    const isDelivery = !isPickUp;

    possibleNext.forEach(status => {
        // Gate: Cannot move to Processing if initial payment not approved (applies to ALL methods)
        if (status === 'Processing' && !isInitialPaid) {
            return;
        }

        // Gate: Hide delivery/pickup options if balance exists and isn't verified — applies to ALL methods EXCEPT COD/COP
        if (['Out for delivery', 'Ready for Pick Up'].includes(status) && !isBalancePaid && !isCOD && !isCOP) {
            return;
        }

        // Gate: Skip "Awaiting Balance" if balance is already paid (early upload) — applies to ALL methods
        if (status === 'Awaiting Balance' && isBalancePaid) {
            return;
        }

        // Gate: Filter by distribution method
        if (status === 'Out for delivery' && isPickUp) return;
        if (status === 'Ready for Pick Up' && isDelivery) return;

        const opt = document.createElement('option');
        opt.value = status;
        opt.textContent = status;
        select.appendChild(opt);
    });

    const cancelGroup = document.getElementById('cancelReasonGroup');
    const returnGroup = document.getElementById('returnItemsGroup');
    const proofGroup = document.getElementById('proofOfDeliveryGroup');

    if (cancelGroup) cancelGroup.style.display = 'none';
    if (returnGroup) returnGroup.style.display = 'none';
    if (proofGroup) proofGroup.style.display = 'none';

    select.onchange = () => {
        if (cancelGroup) cancelGroup.style.display = select.value === 'Cancelled' ? '' : 'none';
        if (returnGroup) returnGroup.style.display = select.value === 'Return Items' ? '' : 'none';
        if (proofGroup) proofGroup.style.display = (isDelivery && (select.value === 'Delivered' || select.value === 'Completed')) ? '' : 'none';
    };

    if (!orderStatusModal) orderStatusModal = document.getElementById('orderStatusModal');
    if (orderStatusModal) {
        orderStatusModal.classList.add('active');
        // Re-enable save button in case it was disabled by a previous quick update
        const saveBtn = document.getElementById('saveStatusBtn');
        if (saveBtn) saveBtn.disabled = false;
    }
}

/**
 * GLOBAL PERSISTENT HANDLER FOR SAVE STATUS
 * Uses event delegation so it works regardless of DOM load order.
 */
document.addEventListener('click', (e) => {
    if (!e.target.closest('#saveStatusBtn')) return;
    if (!currentUpdateOrderID) {
        console.warn('Save clicked but no currentUpdateOrderID set.');
        return;
    }

    const statusEl = document.getElementById('newStatus');
    if (!statusEl) return;

    const status = statusEl.value;
    const currentStatus = statusEl.options[0]?.value || '';

    const reason = document.getElementById('cancelReasonInput')?.value || '';
    const proofFile = document.getElementById('proofOfDeliveryInput')?.files[0];
    const returnReason = document.getElementById('returnReasonInput')?.value || '';
    const returnRemarks = document.getElementById('returnRemarksInput')?.value || '';

    if (status === 'Return Items' && (!returnReason || !returnRemarks)) {
        showToast('Action Required', 'Please fill in all return details.', 'warning');
        return;
    }

    if (status === 'Cancelled' && !reason) {
        showToast('Action Required', 'Please provide a reason for cancellation.', 'warning');
        return;
    }

    // OPTIONAL Proof of Delivery — admin may upload a photo when completing delivery orders,
    // but it is NOT required. The proof upload field in the modal is shown as a convenience.
    const distMethod = (window.currentViewingOrder?.distributionMethod || '').trim().toLowerCase();
    const isDelivery = distMethod === 'delivery';
    const isTargetFinal = (status === 'Completed' || status === 'Delivered');

    const saveBtn = document.getElementById('saveStatusBtn');

    const performUpdate = () => {
        const orderIDToUpdate = currentUpdateOrderID; // Snapshot BEFORE closing modal
        closeOrderStatusModal(); // Safe to close now — ID is preserved
        if (saveBtn) saveBtn.disabled = true;

        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('orderID', orderIDToUpdate);
        fd.append('status', status);
        fd.append('cancelReason', reason);

        if (status === 'Return Items') {
            fd.append('returnReason', returnReason);
            fd.append('returnRemarks', returnRemarks);
        }
        if (proofFile) fd.append('proofOfDelivery', proofFile);

        fetch('../backend/order_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.currentViewingOrder && window.currentViewingOrder.orderID == orderIDToUpdate) {
                        window.currentViewingOrder.orderStatus = status;
                        // Synchronize absolute truth from server (handles nulls/clearing correctly)
                        window.currentViewingOrder.proofOfDelivery = data.proofOfDelivery || null;
                        syncModalWithOrder(window.currentViewingOrder);
                    }
                    showToast('Success', 'Order status updated successfully');
                    refreshOrders();
                } else {
                    showToast('Error', data.message || 'Update failed.', 'danger');
                }
            })
            .catch(err => {
                console.error('Update error:', err);
                showToast('Error', 'Server connection failed.', 'danger');
            })
            .finally(() => {
                if (saveBtn) saveBtn.disabled = false;
            });
    };

    if (status === 'Cancelled') {
        showConfirmModal('Confirm Cancellation', 'Are you sure you want to cancel this order?', performUpdate);
    } else if (status === 'Return Items' && status !== currentStatus) {
        showConfirmModal('Confirm Return', "Mark this order as 'Return Items'?", performUpdate);
    } else {
        performUpdate();
    }
});

function closeOrderStatusModal() {
    if (!orderStatusModal) orderStatusModal = document.getElementById('orderStatusModal');
    if (orderStatusModal) orderStatusModal.classList.remove('active');
    currentUpdateOrderID = null;
}

// Status change listener to show/hide relevant fields dynamically
const statusSelect = document.getElementById('newStatus');
if (statusSelect) {
    statusSelect.addEventListener('change', (e) => {
        const status = e.target.value;
        const cancelGroup = document.getElementById('cancelReasonGroup');
        const returnGroup = document.getElementById('returnItemsGroup');
        const proofGroup = document.getElementById('proofOfDeliveryGroup');

        const order = window.currentViewingOrder;
        const isPickUp = order && order.distributionMethod && (order.distributionMethod.toLowerCase().includes('pick') || order.distributionMethod.toLowerCase().includes('pickup'));
        const isDelivery = !isPickUp;

        if (cancelGroup) cancelGroup.style.display = status === 'Cancelled' ? '' : 'none';
        if (returnGroup) returnGroup.style.display = status === 'Return Items' ? '' : 'none';
        if (proofGroup) proofGroup.style.display = (isDelivery && (status === 'Delivered' || status === 'Completed')) ? '' : 'none';
    });
}

function updateTimeline(currentStatus, distributionMethod = 'Delivery', isCustom = false, paymentMethod = 'Online Payment', paymentOption = 'half') {
    const isCancelled = currentStatus === 'Cancelled';
    const isPickUp = (distributionMethod && (distributionMethod.toLowerCase().includes('pick') || distributionMethod.toLowerCase().includes('pickup')));
    const isCOD = (paymentMethod && paymentMethod.toLowerCase().includes('cash on delivery'));
    const isCOP = (paymentMethod && (paymentMethod.toLowerCase().includes('cash on pickup') || paymentMethod.toLowerCase().includes('cash on pick up')));
    const isCash = isCOD || isCOP;

    // Normalize status for timeline indexing
    if (currentStatus === 'Delivered' || currentStatus === 'Picked Up' || currentStatus === 'Order Completed') currentStatus = 'Completed';
    if (currentStatus === 'Ready to Deliver') currentStatus = isPickUp ? 'Ready for Pick Up' : 'Out for delivery';

    // Treat Return Items as having completed the standard flow
    let timelineStatus = currentStatus;
    if (currentStatus === 'Return Items') timelineStatus = 'Completed';
    if (currentStatus === 'Downpayment') timelineStatus = 'Paid';
    if (currentStatus === 'Unpaid' || currentStatus === 'Order Placed') timelineStatus = 'Pending';
    
    // Normalize dynamic labels for timeline indexing
    const lowerStatus = (currentStatus || '').toLowerCase();
    if (lowerStatus === 'to be paid upon delivery' || lowerStatus === 'to be paid upon pickup' || lowerStatus === 'to be paid upon pick up') {
        timelineStatus = 'Awaiting Balance';
    }

    // Status definitions for different flows
    const isFullPayment = (paymentOption || '').toLowerCase() === 'full';

    let deliverySteps = ['Pending', 'Paid', 'Processing', 'Awaiting Balance', 'Out for delivery', 'Completed'];
    let pickupSteps = ['Pending', 'Paid', 'Processing', 'Awaiting Balance', 'Ready for Pick Up', 'Completed'];

    if (isFullPayment) {
        deliverySteps = ['Pending', 'Paid', 'Processing', 'Out for delivery', 'Completed'];
        pickupSteps = ['Pending', 'Paid', 'Processing', 'Ready for Pick Up', 'Completed'];
    }

    // Use "Tailoring in Progress" instead of "Processing" for custom orders
    const processingLabel = isCustom ? 'Tailoring in<br>Progress' : 'Order<br>Processing';
    const processingIcon = isCustom ? '<i class="bi bi-scissors"></i>' : '<i class="bi bi-gear"></i>';

    const steps = isPickUp ? pickupSteps : deliverySteps;
    const totalSteps = steps.length;

    // Update labels and visibility based on flow
    const timelineContainer = document.getElementById('orderTimeline');
    if (isPickUp) timelineContainer.classList.add('pickup-flow');
    else timelineContainer.classList.remove('pickup-flow');

    document.getElementById('step-3').querySelector('.step-label').innerHTML = processingLabel;
    document.getElementById('step-3').querySelector('.step-icon').innerHTML = processingIcon;

    // Renaming Step 2 based on payment option
    document.getElementById('step-2').querySelector('.step-label').innerHTML = isFullPayment ? 'Full<br>Payment' : 'Down<br>Payment';

    if (isFullPayment) {
        // Hide step 4 and use step 4 for delivery/pickup instead
        document.getElementById('step-4').style.display = 'none';

        const step4Label = isPickUp ? 'Ready for<br>Pick up' : 'Out for<br>delivery';
        const step4Icon = isPickUp ? '<i class="bi bi-shop"></i>' : '<i class="bi bi-geo-fill"></i>';

        document.getElementById('step-5').querySelector('.step-label').innerHTML = step4Label;
        document.getElementById('step-5').querySelector('.step-icon').innerHTML = step4Icon;

        // Hide step 5 in 5-step flow since step 4 is now the delivery step? 
        // Wait, if 5 steps, then steps 1, 2, 3, 5, 6? 
        // The HTML has IDs step-1 to step-6.
        // If 5 steps: step-1, step-2, step-3, step-5, step-6.
        // And we hide step-4.
    } else {
        document.getElementById('step-4').style.display = '';
        if (isPickUp) {
            document.getElementById('step-4').querySelector('.step-label').innerHTML = isCash ? 'To be paid<br>upon Pickup' : 'Awaiting<br>Balance';
            document.getElementById('step-4').querySelector('.step-icon').innerHTML = '<i class="bi bi-wallet2"></i>';

            document.getElementById('step-5').querySelector('.step-label').innerHTML = 'Ready for<br>Pick up';
            document.getElementById('step-5').querySelector('.step-icon').innerHTML = '<i class="bi bi-shop"></i>';
        } else {
            document.getElementById('step-4').querySelector('.step-label').innerHTML = isCash ? 'To be paid<br>upon Delivery' : 'Awaiting<br>Balance';
            document.getElementById('step-4').querySelector('.step-icon').innerHTML = '<i class="bi bi-wallet2"></i>';

            document.getElementById('step-5').querySelector('.step-label').innerHTML = 'Out for<br>delivery';
            document.getElementById('step-5').querySelector('.step-icon').innerHTML = '<i class="bi bi-geo-fill"></i>';
        }
    }

    document.getElementById('step-6').querySelector('.step-label').innerHTML = 'Order<br>Completed';
    document.getElementById('step-6').querySelector('.step-icon').innerHTML = '<i class="bi bi-patch-check-fill"></i>';

    // Reset All
    document.querySelectorAll('#orderTimeline .step-item').forEach(step => {
        step.classList.remove('active', 'current', 'cancelled', 'unpaid-cod', 'cod-awaiting', 'blue-step');
    });

    if (isCancelled) {
        const span = isPickUp ? 80 : 83.33;
        document.querySelectorAll('#orderTimeline .step-item').forEach(s => s.classList.add('cancelled'));
        document.getElementById('orderProgressLine').style.width = span + '%';
        document.getElementById('orderProgressLine').style.background = '#e74c3c';
    } else {
        document.getElementById('orderProgressLine').style.background = currentStatus === 'Return Items' ? '#6f42c1' : '#28a745';
        const ci = steps.indexOf(timelineStatus);

        const allStepEls = [
            document.getElementById('step-1'),
            document.getElementById('step-2'),
            document.getElementById('step-3'),
            document.getElementById('step-4'),
            document.getElementById('step-5'),
            document.getElementById('step-6')
        ];
        const visibleStepEls = allStepEls.filter(el => el && el.style.display !== 'none');

        visibleStepEls.forEach((step, idx) => {
            if (idx < ci) {
                step.classList.add('active');
                if (currentStatus === 'Return Items') step.classList.add('return-items');
            } else if (idx === ci) {
                step.classList.add('active', 'current');
                if (currentStatus === 'Return Items') step.classList.add('return-items');

                // Add special Cash styling for first step if unpaid
                if (isCash && timelineStatus === 'Pending' && idx === 0) {
                    step.classList.add('unpaid-cod');
                }

            }
            
            // Persistent Red styling for Awaiting Balance step until order is completed
            if (isCash && steps[idx] === 'Awaiting Balance' && timelineStatus !== 'Completed') {
                step.classList.add('cod-awaiting');
            }
        });

        // Calculate line width using gaps
        const span = isPickUp ? 80 : 83.33;
        const percent = (ci / (totalSteps - 1)) * span;
        document.getElementById('orderProgressLine').style.width = percent + '%';
    }
}

function viewOrder(orderOrID) {
    let order;
    if (typeof orderOrID === 'object') {
        order = orderOrID;
    } else {
        order = (window.allOrdersCache || []).find(o => o.orderID == orderOrID);
    }

    if (!order) {
        console.error('viewOrder: Order not found in cache', orderOrID);
        return;
    }

    window.currentViewingOrder = order; // Cache for other modals
    
    // Ensure clicks on buttons don't trigger row click (Safely handle event)
    const e = window.event || (typeof event !== 'undefined' ? event : null);
    if (e && e.target && e.target.closest('.action-trigger, .btn-row-action, .status-badge')) return;

    // Track which order is open for payment verification
    currentVerifyOrderID = order.orderID;

    const idEl = document.getElementById('viewOrderID');
    const nameEl = document.getElementById('viewCustomerName');
    const dateEl = document.getElementById('viewOrderDate');
    const addrEl = document.getElementById('viewShippingAddress');
    const priceEl = document.getElementById('viewTotalPrice');

    if (idEl) idEl.textContent = '#ORD-' + String(order.orderID).padStart(3, '0');
    if (nameEl) nameEl.textContent = order.customerName;
    if (dateEl) dateEl.textContent = order.orderDate || 'N/A';
    if (addrEl) addrEl.textContent = order.shippingAddress || 'N/A';

    // Populate Subtotal, Shipping, and Total
    const subtotalEl = document.getElementById('viewSubtotal');
    const shippingEl = document.getElementById('viewShippingFee');
    const discountAmt = parseFloat(order.discountAmount || 0);
    const totalPrice = parseFloat(order.totalPrice || 0);

    // Snapshot fallback for older orders
    const storedSubtotal = parseFloat(order.subtotal || 0);
    const storedShipping = parseFloat(order.shippingFee || 0);
    
    // If we have stored values, use them. Otherwise, derive them (itemsSubtotal will be calculated below)
    // We'll update these properly after the items fetch completes, but set initial placeholders
    if (subtotalEl) subtotalEl.textContent = storedSubtotal > 0 ? '₱' + storedSubtotal.toLocaleString(undefined, { minimumFractionDigits: 2 }) : 'Loading...';
    if (shippingEl) {
        if (storedShipping > 0) {
            shippingEl.textContent = '₱' + storedShipping.toLocaleString(undefined, { minimumFractionDigits: 2 });
        } else if (order.subtotal > 0) { // If subtotal is stored but shipping is 0, it was FREE
            shippingEl.innerHTML = '<span class="text-success">FREE</span>';
        } else {
            shippingEl.textContent = 'Calculating...';
        }
    }
    if (priceEl) priceEl.textContent = '₱' + totalPrice.toLocaleString(undefined, { minimumFractionDigits: 2 });

    const payOptEl = document.getElementById('viewPaymentOption');
    if (payOptEl) {
        const option = order.paymentOption || 'full';
        payOptEl.textContent = option === 'full' ? 'Full Payment' : '50% Downpayment';
        payOptEl.className = option === 'full' ? 'fw-bold text-primary' : 'fw-bold text-warning';
    }

    // Order Status Badge in header
    const statusBadge = document.getElementById('viewOrderStatusBadge');
    if (statusBadge) {
        statusBadge.textContent = order.orderStatus;
        statusBadge.className = 'status-badge ' + order.orderStatus.toLowerCase().replace(/ /g, '-');
    }

    // Pre-Order UI Updates
    const preOrderBadge = document.getElementById('viewPreOrderBadge');
    const itemsHeader = document.getElementById('orderedItemsHeader');
    if (order.isPreOrder == 1) {
        if (preOrderBadge) preOrderBadge.style.display = 'inline-block';
        if (itemsHeader) itemsHeader.textContent = 'Pre-Ordered Items';
    } else {
        if (preOrderBadge) preOrderBadge.style.display = 'none';
        if (itemsHeader) itemsHeader.textContent = 'Ordered Items';
    }

    // Return Information Area - Populated immediately from cache
    const returnArea = document.getElementById('returnInfoArea');
    const viewReturnReason = document.getElementById('viewReturnReason');
    const viewReturnRemarks = document.getElementById('viewReturnRemarks');
    if (returnArea) {
        if (order.orderStatus === 'Return Items' && (order.returnReason || order.returnRemarks)) {
            returnArea.style.display = '';
            if (viewReturnReason) viewReturnReason.textContent = order.returnReason || 'N/A';
            if (viewReturnRemarks) viewReturnRemarks.textContent = order.returnRemarks || 'N/A';
        } else {
            returnArea.style.display = 'none';
        }
    }

    // Cancellation Information Area
    const cancelArea = document.getElementById('cancelInfoArea');
    const viewCancelReason = document.getElementById('viewCancelReason');
    if (cancelArea) {
        if (order.orderStatus === 'Cancelled' && order.cancelReason) {
            cancelArea.style.display = '';
            if (viewCancelReason) viewCancelReason.textContent = order.cancelReason || 'N/A';
        } else {
            cancelArea.style.display = 'none';
        }
    }

    // Downpayment & Balance logic
    const isGCash = (order.paymentMethod || order.methodName || '').toLowerCase().includes('gcash');
    const dpGroup = document.getElementById('adminDownpaymentGroup');
    const balGroup = document.getElementById('adminBalanceGroup');
    const dpVal = document.getElementById('viewDownpayment');
    const balVal = document.getElementById('viewBalance');
    const isVerified = (order.paymentStatus || '').toLowerCase() === 'approved';
    const discountGroup = document.getElementById('adminDiscountGroup');
    const discountVal = document.getElementById('viewDiscountAmount');

    if (dpGroup && balGroup) {
        dpGroup.style.display = isGCash ? '' : 'none';
        balGroup.style.display = '';

        const downpayment = parseFloat(order.downPaymentAmount || 0);
        const total = parseFloat(order.totalPrice || 0);
        const balance = parseFloat(order.paymentBalance || total);

        const isFinal = ['Completed', 'Delivered', 'Picked Up'].includes(order.orderStatus);

        if (isFinal || isVerified) {
            dpVal.textContent = '₱0.00';
            dpVal.className = 'fw-bold text-success';
            const balToDisplay = isFinal ? 0 : balance;
            balVal.textContent = '₱' + balToDisplay.toLocaleString(undefined, { minimumFractionDigits: 2 });
            balVal.className = balToDisplay <= 0 ? 'fw-bold text-success' : 'fw-bold text-danger';
        } else {
            dpVal.textContent = '₱' + downpayment.toLocaleString(undefined, { minimumFractionDigits: 2 });
            dpVal.className = 'fw-bold text-danger';
            balVal.textContent = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2 });
            balVal.className = 'fw-bold text-danger';
        }
    }

    if (discountGroup && discountVal) {
        const dAmount = parseFloat(order.discountAmount || 0);
        const dPercent = parseInt(order.discountPercentage || 0);
        const finalTotal = parseFloat(order.totalPrice || 0);
        const baseTotal = finalTotal + dAmount;

        if (dAmount > 0) {
            discountGroup.style.display = '';
            const percentText = dPercent > 0 ? ` (${dPercent}%)` : '';
            // If it's a clean integer percentage set by manual adjustment, label it as such
            const labelText = (dPercent > 0 && Number.isInteger(dPercent)) ? 'Discount Applied' : 'Bulk Discount';
            discountGroup.querySelector('label').textContent = `${labelText}${percentText}`;
            discountVal.textContent = '-₱' + dAmount.toLocaleString(undefined, { minimumFractionDigits: 2 });

            // Highlight total price as discounted
            if (priceEl) {
                priceEl.innerHTML = `
                    <span style="text-decoration: line-through; font-size: 0.9rem; color: #64748b; margin-right: 8px;">₱${baseTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                    <span style="color: #28a745;">₱${finalTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                `;
            }
        } else {
            discountGroup.style.display = 'none';
            if (priceEl) priceEl.textContent = '₱' + finalTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
        }
    }

    // Reset receipt visual states before sync to prevent previous order state bleed
    const groupsToReset = [
        'orderReceiptThumbGroup', 'verifyActionsGroup', 'orderRefGroup',
        'orderBalanceReceiptThumbGroup', 'verifyBalanceActionsGroup', 'orderBalanceRefGroup',
        'proofOfDeliveryArea'
    ];
    groupsToReset.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // Synchronize all modal states (Status badges, Timeline, Action Buttons, Balances)
    syncModalWithOrder(order);

    const tbody = document.querySelector('#viewOrderItemsTable tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading items...</td></tr>';

        const fd = new FormData();
        fd.append('action', 'get_details');
        fd.append('orderID', order.orderID);

        fetch('../backend/order_handler.php?t=' + Date.now(), { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    tbody.innerHTML = '';
                    data.items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.className = 'item-row';

                        // Robust image path resolution: Prioritize actual product image even for custom items
                        let imgSrc = '../../JDE_USER/assets/img/logojd.png'; // Global fallback
                        let hasImage = false;

                        if (item.productImage && item.productImage.trim() !== '') {
                            const cleanPath = item.productImage.replace('../', '');
                            imgSrc = '../../JDE_USER/' + cleanPath;
                            hasImage = true;
                        } else if (item.isCustom || (item.productName && item.productName.toLowerCase().includes('custom'))) {
                            // If it's custom and we don't have a product image yet, it might be a truly bespoke item
                            // OR the join just didn't find the base product image.
                            imgSrc = '../../JDE_USER/assets/img/scissor.png';
                        }

                        // Store item data for the measurement modal
                        const itemJson = JSON.stringify(item).replace(/'/g, "&apos;");

                        tr.innerHTML = `
                            <td class="col-img">
                                <div class="prod-img-preview mini ${!item.productImage ? 'custom-placeholder' : ''}">
                                    <img src="${imgSrc}" alt="${item.productName}" onerror="this.src='../../JDE_USER/assets/img/logojd.png'">
                                </div>
                            </td>
                            <td class="col-product">
                                <div class="item-details">
                                    <span class="item-name">${item.productName || (item.isCustom ? 'Custom Tailoring' : 'Product Item')}</span>
                                    <span class="item-meta">${item.categoryName || 'General'}</span>
                                    
                                    <div class="item-classification">
                                        ${item.isCustom ? 
                                            `<span class="badge-custom"><i class="bi bi-scissors"></i> Handcrafted Order</span>` : 
                                            (item.isPreOrder ? 
                                                `<span class="badge-preorder"><i class="bi bi-calendar-event"></i> Pre-Order Item</span>` : 
                                                `<span class="badge-premade"><i class="bi bi-box-seam"></i> Ready Stock</span>`
                                            )
                                        }
                                    </div>

                                    ${item.isCustom ? `
                                        <div class="custom-item-actions">
                                            <button class="btn-row-action primary outline small" style="padding: 4px 10px; font-size: 10px; margin-top: 5px;" onclick='openMeasurementModal(${itemJson})'>
                                                <i class="bi bi-rulers"></i> View Production Card
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                            </td>
                            <td class="col-price">₱${parseFloat(item.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                            <td class="col-size" style="color: #64748b; font-weight: 500;">${(!item.isCustom && item.size) ? item.size : '<span class="text-muted">-</span>'}</td>
                            <td class="col-qty"><span class="qty-badge">${item.quantity}</span></td>
                            <td class="col-total fw-bold">₱${(item.price * item.quantity).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // After items are loaded, if we don't have stored subtotal/shipping, calculate them now
                    const subtotalEl = document.getElementById('viewSubtotal');
                    const shippingEl = document.getElementById('viewShippingFee');
                    
                    let itemsTotal = 0;
                    data.items.forEach(i => { itemsTotal += parseFloat(i.price) * parseInt(i.quantity); });

                    if (subtotalEl && (!order.subtotal || parseFloat(order.subtotal) <= 0)) {
                        subtotalEl.textContent = '₱' + itemsTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
                    }
                    if (shippingEl && (!order.shippingFee || parseFloat(order.shippingFee) <= 0)) {
                         const dAmt = parseFloat(order.discountAmount || 0);
                         const tPrice = parseFloat(order.totalPrice || 0);
                         const derivedFee = (tPrice + dAmt) - itemsTotal;
                         if (derivedFee <= 0) {
                             shippingEl.innerHTML = '<span class="text-success">FREE</span>';
                         } else {
                             shippingEl.textContent = '₱' + derivedFee.toLocaleString(undefined, { minimumFractionDigits: 2 });
                         }
                    }

                    // Populate Payment Info
                    const paymentArea = document.getElementById('paymentDetailsArea');
                    const verifyGroup = document.getElementById('verifyActionsGroup');
                    const statusBadge = document.getElementById('paymentStatusBadge');
                    const receiptThumbGroup = document.getElementById('orderReceiptThumbGroup');

                    window._currentOrderPayment = data.payment || null;
                    window._currentOrderData = order;

                    if (data.payment) {
                        const payMeth = document.getElementById('viewPaymentMethod');
                        const payStat = document.getElementById('viewPaymentStatus');
                        const refNum = document.getElementById('viewReferenceNumber');

                        if (payMeth) payMeth.textContent = data.payment.method || 'N/A';

                        const isGcash = data.payment.method && data.payment.method.toLowerCase().includes('gcash');
                        const receiptThumbGroup = document.getElementById('orderReceiptThumbGroup');

                        console.log('JDE Order Details:', {
                            orderID: data.orderID,
                            isGcash: isGcash,
                            receipt: data.payment.receipt,
                            status: data.payment.status
                        });
                        const payStatus = (data.payment.status || '').toLowerCase();
                        const gcashGroup = document.getElementById('gcashInfoGroup');

                        if (refNum) refNum.textContent = data.payment.reference || 'N/A';
                        // Show GCash info if reference exists or it's GCash
                        if (gcashGroup) gcashGroup.style.display = (data.payment.reference || isGcash) ? '' : 'none';

                        // Payment status — styled text with colour class
                        if (payStat) {
                            const pOption = (order.paymentOption || 'full').toLowerCase();
                            const isFullPay = pOption === 'full';
                            const pStatus = (data.payment.status || '').toLowerCase();
                            const bStatus = (data.payment.balanceStatus || '').toLowerCase();
                            const oStatus = (order.orderStatus || '').toLowerCase();
                            const isApproved = (pStatus === 'approved' || oStatus === 'paid' || oStatus === 'completed');
                            const isBalanceApproved = (bStatus === 'approved');
                            const isCompleted = ['completed', 'order completed', 'delivered', 'picked up'].includes(oStatus);

                            let statusClass = 'pdr-value payment-status-text';
                            let paymentStatusText = '';

                            if (isFullPay) {
                                if (isApproved || isCompleted) {
                                    statusClass += ' approved';
                                    paymentStatusText = 'PAID';
                                } else {
                                    if (pStatus === 'pending' || pStatus === 'unpaid' || pStatus === '') {
                                        statusClass += ' pending';
                                        paymentStatusText = 'Pending Verification';
                                    } else if (pStatus === 'rejected') {
                                        statusClass += ' rejected';
                                        paymentStatusText = 'Payment Rejected';
                                    } else {
                                        statusClass += ' ' + pStatus;
                                        paymentStatusText = (data.payment.status || 'Unpaid').toUpperCase();
                                    }
                                }
                            } else {
                                // Down Payment
                                if (isCompleted || isBalanceApproved) {
                                    statusClass += ' approved';
                                    paymentStatusText = isBalanceApproved ? 'FULLY PAID' : 'PAID';
                                } else {
                                    const mName = (data.payment.method || '').toLowerCase();
                                    const isCOD = mName.includes('cash on delivery');
                                    const isCOP = mName.includes('cash on pickup') || mName.includes('cash on pick up');
                                    const isPickUp = (order.distributionMethod || '').toLowerCase().includes('pickup');

                                    if (oStatus === 'awaiting balance' && (isCOD || isCOP)) {
                                        statusClass += ' rejected';
                                        paymentStatusText = 'To be paid upon ' + (isPickUp ? 'Pickup' : 'Delivery');
                                    } else {
                                        if (isApproved) {
                                            statusClass += ' partially-paid';
                                            paymentStatusText = 'Partially Paid';
                                        } else {
                                            statusClass += ' pending';
                                            paymentStatusText = 'Pending Verification';
                                        }
                                    }
                                }
                            }

                            payStat.textContent = paymentStatusText;
                            payStat.className = statusClass;
                        }

                        // Status Badge in header — show for all if status exists
                        if (statusBadge && payStatus) {
                            const bStatus = (data.payment.balanceStatus || '').toLowerCase();
                            const isBalanceApproved = (bStatus === 'approved');
                            const isDownpayment = (order.paymentOption || 'full').toLowerCase() !== 'full';
                            
                            let badgeClass = payStatus;
                            let badgeLabel = (data.payment.status || 'Unknown').toUpperCase();
                            
                            if (isDownpayment && isBalanceApproved) {
                                badgeClass = 'approved';
                                badgeLabel = 'FULLY PAID';
                            }

                            statusBadge.className = 'payment-status-pill ' + badgeClass;
                            statusBadge.style.display = 'inline-flex';
                            const icons = { pending: '⏳', approved: '✅', rejected: '❌', paid: '✅', unpaid: '⏳' };
                            statusBadge.innerHTML = `<span class="status-icon">${icons[badgeClass] || '💳'}</span> ${badgeLabel}`;
                        } else if (statusBadge) {
                            statusBadge.style.display = 'none';
                        }

                        // Show "Verify Payment" CTA for GCash when payment is not yet approved/rejected
                        // DB stores 'Unpaid' for unverified GCash payments (not 'Pending')
                        const needsVerification = (payStatus === 'pending' || payStatus === 'unpaid' || payStatus === 'unverified') && data.payment.receipt;
                        if (verifyGroup) {
                            verifyGroup.style.display = needsVerification ? '' : 'none';
                        }



                        // Receipt thumbnail — always show in Order Details if a receipt exists
                        if (receiptThumbGroup) {
                            if (data.payment.receipt) {
                                // Multi-path resolution strategy
                                const filename = data.payment.receipt.split(/[\\\/]/).pop();
                                const thumb = document.getElementById('orderReceiptThumb');
                                if (thumb) {
                                    const paths = [
                                        `../../JDE_USER/backend/uploads/receipts/${filename}`,
                                        `../JDE_USER/backend/uploads/receipts/${filename}`,
                                        `../../backend/uploads/receipts/${filename}`,
                                        `uploads/receipts/${filename}`
                                    ];

                                    let attempt = 0;
                                    const tryNext = () => {
                                        if (attempt < paths.length) {
                                            thumb.src = paths[attempt++];
                                        } else {
                                            thumb.src = '../../JDE_USER/assets/img/logojd.png';
                                            console.error('All receipt paths failed for:', filename);
                                        }
                                    };

                                    thumb.onerror = tryNext;
                                    tryNext();
                                }
                                receiptThumbGroup.style.display = '';
                                // Reset label in case it was marked as missing
                                const label = receiptThumbGroup.querySelector('.pdr-label');
                                if (label) label.innerHTML = 'Proof of Payment';
                            } else if (isGcash) {
                                // GCash but no receipt data? Show a placeholder
                                const thumb = document.getElementById('orderReceiptThumb');
                                if (thumb) thumb.src = '../../JDE_USER/assets/img/logojd.png';
                                receiptThumbGroup.style.display = '';
                                const label = receiptThumbGroup.querySelector('.pdr-label');
                                if (label) label.innerHTML = 'Proof of Payment <span style="color:#ef4444; font-size:10px;">(Missing)</span>';
                            } else {
                                receiptThumbGroup.style.display = 'none';
                            }
                        }

                        if (paymentArea) paymentArea.style.display = '';

                        // --- Remaining Balance Receipt Logic ---
                        const balanceReceiptGroup = document.getElementById('orderBalanceReceiptThumbGroup');
                        const verifyBalanceGroup = document.getElementById('verifyBalanceActionsGroup');
                        const balanceRefGroup = document.getElementById('orderBalanceRefGroup');
                        const balanceRefNum = document.getElementById('viewBalanceReferenceNumber');

                        if (data.payment.balanceReceipt) {
                            if (balanceRefGroup) balanceRefGroup.style.display = '';
                            if (balanceRefNum) balanceRefNum.textContent = data.payment.balanceReference || 'N/A';

                            if (balanceReceiptGroup) {
                                const filename = data.payment.balanceReceipt.split(/[\\\/]/).pop();
                                const thumb = document.getElementById('orderBalanceReceiptThumb');
                                if (thumb) {
                                    const paths = [
                                        `../../JDE_USER/backend/uploads/receipts/${filename}`,
                                        `../JDE_USER/backend/uploads/receipts/${filename}`,
                                        `../../backend/uploads/receipts/${filename}`
                                    ];
                                    let attempt = 0;
                                    const tryNext = () => {
                                        if (attempt < paths.length) thumb.src = paths[attempt++];
                                        else thumb.src = '../../JDE_USER/assets/img/logojd.png';
                                    };
                                    thumb.onerror = tryNext;
                                    tryNext();
                                }
                                balanceReceiptGroup.style.display = '';
                            }

                            // Show verify button if balance receipt exists, is NOT yet approved AND NOT rejected
                            const bStatus = (data.payment.balanceStatus || '').toLowerCase();
                            const needsBalanceVerify = bStatus !== 'approved' && bStatus !== 'rejected';
                            if (verifyBalanceGroup) verifyBalanceGroup.style.display = needsBalanceVerify ? '' : 'none';


                            // --- CRITICAL: Sync live balancePaymentStatus back into cached order object ---
                            // This ensures syncModalWithOrder uses fresh DB data for button visibility
                            if (window.currentViewingOrder) {
                                window.currentViewingOrder.balancePaymentStatus = data.payment.balanceStatus || null;
                                // Re-evaluate button state with live payment data
                                syncModalWithOrder(window.currentViewingOrder);
                            }
                        } else {
                            if (balanceReceiptGroup) balanceReceiptGroup.style.display = 'none';
                            if (verifyBalanceGroup) verifyBalanceGroup.style.display = 'none';
                            if (balanceRefGroup) balanceRefGroup.style.display = 'none';

                            // No balance receipt uploaded — ensure button is enabled if otherwise valid
                            if (window.currentViewingOrder) {
                                const bStat = (window.currentViewingOrder.balancePaymentStatus || '').toLowerCase();
                                if (bStat === 'pending' || bStat === 'unpaid' || bStat === '') {
                                    window.currentViewingOrder.balancePaymentStatus = null;
                                    syncModalWithOrder(window.currentViewingOrder);
                                }
                            }
                        }
                    } else {
                        if (paymentArea) paymentArea.style.display = 'none';
                        if (verifyGroup) verifyGroup.style.display = 'none';
                        if (statusBadge) statusBadge.style.display = 'none';
                    }

                    // Proof of Delivery is now handled by syncModalWithOrder(order)
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Failed to load items.</td></tr>';
                }
            })
            .catch((err) => {
                console.error("Order details fetch error:", err);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">An error occurred.</td></tr>';
            });
    }

    if (!viewOrderModal) viewOrderModal = document.getElementById('viewOrderModal');
    if (viewOrderModal) viewOrderModal.classList.add('active');
}

function closeViewOrderModal() {
    if (viewOrderModal) viewOrderModal.classList.remove('active');
}

function viewFullReceipt() {
    const img = document.getElementById('viewReceiptImage');
    if (img && img.src) window.open(img.src, '_blank');
}

// Store current order ID for payment verification
let currentVerifyOrderID = null;

/**
 * openPaymentVerifyModal — opens the dedicated Payment Verification modal
 * and populates it with data from the currently viewed order.
 */
function openPaymentVerifyModal() {
    const modal = document.getElementById('paymentVerifyModal');
    const payment = window._currentOrderPayment;
    const order = window._currentOrderData;
    if (!modal || !payment || !order) return;

    currentVerifyOrderID = order.orderID;

    // Populate modal fields
    const label = document.getElementById('pvmOrderLabel');
    const custEl = document.getElementById('pvmCustomerName');
    const amtEl = document.getElementById('pvmAmount');
    const refEl = document.getElementById('pvmRefNumber');
    const methEl = document.getElementById('pvmMethod');
    const dateEl = document.getElementById('pvmUploadedDate');
    const noteEl = document.getElementById('pvmAdminNote');
    const imgEl = document.getElementById('pvmReceiptImage');

    if (label) label.textContent = '#ORD-' + String(order.orderID).padStart(3, '0');
    if (custEl) custEl.textContent = order.customerName || 'N/A';

    // Use downpayment amount for verification if it exists, otherwise total price
    const displayAmt = (payment.downPayment > 0) ? payment.downPayment : parseFloat(order.totalPrice);
    if (amtEl) amtEl.textContent = '₱' + displayAmt.toLocaleString(undefined, { minimumFractionDigits: 2 });

    if (refEl) refEl.textContent = payment.reference || 'N/A';
    if (methEl) methEl.textContent = payment.method || 'N/A';
    if (dateEl) {
        if (payment.uploadedAt) {
            const d = new Date(payment.uploadedAt);
            dateEl.textContent = d.toLocaleString('en-PH', {
                month: 'short', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit', hour12: true
            });
        } else {
            dateEl.textContent = 'N/A';
        }
    }
    if (noteEl) noteEl.value = '';

    // Receipt image
    if (imgEl && payment.receipt) {
        const filename = payment.receipt.split(/[\\\/]/).pop();
        const paths = [
            `../../JDE_USER/backend/uploads/receipts/${filename}`,
            `../JDE_USER/backend/uploads/receipts/${filename}`,
            `../../backend/uploads/receipts/${filename}`,
            `uploads/receipts/${filename}`
        ];

        let attempt = 0;
        const tryNext = () => {
            if (attempt < paths.length) {
                imgEl.src = paths[attempt++];
                console.log('Trying path:', imgEl.src);
            } else {
                imgEl.src = '../../JDE_USER/assets/img/logojd.png';
                console.error('All receipt paths failed for:', filename);
            }
        };

        imgEl.onerror = tryNext;
        tryNext();
    } else if (imgEl) {
        imgEl.src = '../../JDE_USER/assets/img/logojd.png';
    }

    modal.classList.add('active');

    // Hide verify buttons if already processed
    const actionRow = modal.querySelector('.pvm-action-row');
    if (actionRow) {
        const isProcessed = (payment.status || '').toLowerCase() === 'approved' || (payment.status || '').toLowerCase() === 'rejected';
        actionRow.style.display = isProcessed ? 'none' : 'flex';
    }
}

function closePaymentVerifyModal() {
    const modal = document.getElementById('paymentVerifyModal');
    if (modal) modal.classList.remove('active');
}

/**
 * copyReferenceNumber — copies GCash ref # to clipboard
 */
function copyReferenceNumber() {
    const ref = document.getElementById('viewReferenceNumber')?.textContent?.trim();
    if (!ref || ref === 'N/A') return;
    navigator.clipboard.writeText(ref).then(() => {
        showToast('Copied!', `Reference number ${ref} copied to clipboard.`, 'success');
        const btn = document.querySelector('.btn-copy-ref i');
        if (btn) {
            btn.className = 'bi bi-clipboard-check';
            setTimeout(() => { btn.className = 'bi bi-clipboard'; }, 2000);
        }
    }).catch(() => {
        showToast('Error', 'Could not copy to clipboard.', 'danger');
    });
}

/**
 * verifyPayment — called by Approve / Reject buttons in the Payment Verification modal
 * @param {'approved'|'rejected'} decision
 */
function verifyPayment(decision) {
    if (!currentVerifyOrderID) return;
    const label = decision === 'approved' ? 'Approve' : 'Reject';

    showConfirmModal(
        `${label} Payment`,
        `Are you sure you want to ${label.toLowerCase()} the GCash payment for Order #ORD-${String(currentVerifyOrderID).padStart(3, '0')}?`,
        () => {
            // --- INSTANT REAL-TIME UI UPDATE (Optimistic) ---
            // 1. Hide buttons in the verification modal immediately
            const pvmActionRow = document.querySelector('#paymentVerifyModal .pvm-action-row');
            if (pvmActionRow) pvmActionRow.style.display = 'none';

            // 2. Identify elements in the background "Order Details" modal
            const payStat = document.getElementById('viewPaymentStatus');
            const statusBadge = document.getElementById('paymentStatusBadge');
            const verifyGroup = document.getElementById('verifyActionsGroup');
            const modalUpdateBtn = document.getElementById('modalUpdateBtn');
            const dpVal = document.getElementById('viewDownpayment');

            // 3. Determine correct ORDER STATUS based on paymentOption
            //    half/downpayment orders -> 'Downpayment', full payment -> 'Paid'
            const payOpt = (window.currentViewingOrder?.paymentOption || 'full').toLowerCase();
            const isDownpayment = ['half', 'down', 'downpayment', '50%'].includes(payOpt);
            const optimisticOrderStatus = (decision === 'approved')
                ? (isDownpayment ? 'Downpayment' : 'Paid')
                : 'Unpaid';

            // 4. Payment verification status (Approved / Rejected)
            const newStatus = decision === 'approved' ? 'Approved' : 'Rejected';

            if (payStat) {
                payStat.textContent = newStatus;
                payStat.className = 'pdr-value payment-status-text ' + newStatus.toLowerCase();
            }

            if (statusBadge) {
                const icons = { approved: '✅', rejected: '❌', paid: '✅', unpaid: '⏳', downpayment: '💰' };
                statusBadge.className = 'payment-status-pill ' + newStatus.toLowerCase();
                statusBadge.innerHTML = `<span class="status-icon">${icons[newStatus.toLowerCase()] || '💳'}</span> ${newStatus.toUpperCase()}`;
                statusBadge.style.display = 'inline-flex';
            }

            if (verifyGroup) verifyGroup.style.display = 'none';

            // THE "UPDATE STATUS" BUTTON - SHOW INSTANTLY IF APPROVED
            if (modalUpdateBtn) {
                modalUpdateBtn.style.display = (decision === 'approved') ? '' : 'none';
            }

            if (decision === 'approved' && dpVal) {
                dpVal.textContent = '₱0.00';
                dpVal.className = 'fw-bold text-success';
            }

            // --- SERVER SYNC ---
            const fd = new FormData();
            fd.append('action', 'verify_payment');
            fd.append('orderID', currentVerifyOrderID);
            fd.append('decision', decision);

            fetch('../backend/order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Use server's confirmed order status (Downpayment / Paid / Unpaid)
                        const confirmedOrderStatus = data.newOrderStatus || optimisticOrderStatus;
                        const toastMsg = decision === 'approved'
                            ? `Payment verified. Order marked as ${confirmedOrderStatus}.`
                            : 'Payment rejected. Order set back to Unpaid.';

                        showToast(
                            decision === 'approved' ? 'Payment Approved ✅' : 'Payment Rejected ❌',
                            toastMsg,
                            decision === 'approved' ? 'success' : 'danger'
                        );

                        // Delay background fetch to prevent race condition with DB commit
                        setTimeout(() => {
                            if (typeof refreshOrders === 'function') refreshOrders();
                        }, 1000);

                        // Sync the cached order object with confirmed server state
                        if (window.currentViewingOrder) {
                            window.currentViewingOrder.paymentStatus = newStatus;
                            const currentOStatus = (window.currentViewingOrder.orderStatus || '').toLowerCase();
                            if (decision === 'approved' && ['pending', 'unpaid', 'unverified'].includes(currentOStatus)) {
                                window.currentViewingOrder.orderStatus = confirmedOrderStatus;
                            }
                            if (typeof updateTimeline === 'function') {
                                updateTimeline(window.currentViewingOrder.orderStatus, window.currentViewingOrder.distributionMethod, parseInt(window.currentViewingOrder.isCustom) === 1, window.currentViewingOrder.paymentMethod, window.currentViewingOrder.paymentOption);
                            }
                        }

                        // Close the verification modal after a short delay
                        setTimeout(() => closePaymentVerifyModal(), 1200);
                    } else {
                        // REVERT on failure
                        showToast('Error', data.message || 'Action failed.', 'danger');
                        if (pvmActionRow) pvmActionRow.style.display = 'flex';
                        if (modalUpdateBtn) modalUpdateBtn.style.display = 'none';
                    }
                })
                .catch(() => {
                    showToast('Error', 'An unexpected error occurred.', 'danger');
                    if (pvmActionRow) pvmActionRow.style.display = 'flex';
                });
        }
    );
}

/**
 * openBalanceVerifyModal — opens the Balance Verification modal
 * and populates it with live data from the currently viewed order.
 */
function openBalanceVerifyModal() {
    const modal = document.getElementById('balanceVerifyModal');
    const payment = window._currentOrderPayment;
    const order = window._currentOrderData || window.currentViewingOrder;
    if (!modal || !payment || !order) {
        showToast('Error', 'Balance receipt data not available.', 'danger');
        return;
    }

    // Populate modal fields
    const labelEl = document.getElementById('bvmOrderLabel');
    const custEl = document.getElementById('bvmCustomerName');
    const amtEl = document.getElementById('bvmAmount');
    const refEl = document.getElementById('bvmRefNumber');
    const methEl = document.getElementById('bvmMethod');
    const dateEl = document.getElementById('bvmUploadedDate');
    const imgEl = document.getElementById('bvmReceiptImage');
    const actionRow = modal.querySelector('.pvm-action-row');

    if (labelEl) labelEl.textContent = '#ORD-' + String(order.orderID).padStart(3, '0');
    if (custEl) custEl.textContent = order.customerName || 'N/A';
    if (amtEl) amtEl.textContent = '₱' + parseFloat(order.paymentBalance || order.totalPrice || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
    if (refEl) refEl.textContent = payment.balanceReference || 'N/A';
    if (methEl) methEl.textContent = payment.method || 'N/A';
    if (dateEl) {
        if (payment.balanceUploadedAt) {
            const d = new Date(payment.balanceUploadedAt);
            dateEl.textContent = d.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
        } else {
            dateEl.textContent = 'N/A';
        }
    }

    // Balance receipt image
    if (imgEl && payment.balanceReceipt) {
        const filename = payment.balanceReceipt.split(/[\\\/]/).pop();
        const paths = [
            `../../JDE_USER/backend/uploads/receipts/${filename}`,
            `../JDE_USER/backend/uploads/receipts/${filename}`,
            `../../backend/uploads/receipts/${filename}`
        ];
        let attempt = 0;
        const tryNext = () => {
            if (attempt < paths.length) imgEl.src = paths[attempt++];
            else imgEl.src = '../../JDE_USER/assets/img/logojd.png';
        };
        imgEl.onerror = tryNext;
        tryNext();
    } else if (imgEl) {
        imgEl.src = '../../JDE_USER/assets/img/logojd.png';
    }

    // Control button visibility: hide if already approved
    if (actionRow) {
        const isApproved = (payment.balanceStatus || '').toLowerCase() === 'approved';
        actionRow.style.display = isApproved ? 'none' : 'flex';
    }

    modal.classList.add('active');
}

function closeBalanceVerifyModal() {
    const modal = document.getElementById('balanceVerifyModal');
    if (modal) modal.classList.remove('active');
}

function openBalanceReceiptLightbox() {
    const img = document.getElementById('bvmReceiptImage');
    if (!img || !img.src || img.src.includes('logojd.png')) return;
    const lb = document.getElementById('receiptLightbox');
    const lbImg = document.getElementById('lightboxReceiptImg');
    const dl = document.getElementById('lightboxDownload');
    if (lbImg) lbImg.src = img.src;
    if (dl) dl.href = img.src;
    if (lb) lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * verifyBalancePayment — Approve or Reject the remaining balance payment
 * Called from Approve/Reject buttons inside #balanceVerifyModal
 * @param {'approved'|'rejected'} decision
 */
function verifyBalancePayment(decision) {
    const order = window._currentOrderData || window.currentViewingOrder;
    if (!order) return;
    const orderID = order.orderID;
    const label = decision === 'approved' ? 'Approve' : 'Reject';

    showConfirmModal(
        `${label} Balance Payment`,
        `Are you sure you want to ${label.toLowerCase()} the remaining balance payment for Order #ORD-${String(orderID).padStart(3, '0')}?`,
        () => {
            // Optimistic UI: hide buttons immediately
            const modal = document.getElementById('balanceVerifyModal');
            const actionRow = modal?.querySelector('.pvm-action-row');
            if (actionRow) actionRow.style.display = 'none';

            const fd = new FormData();
            fd.append('action', 'verify_balance');
            fd.append('orderID', orderID);
            fd.append('decision', decision);

            fetch('../backend/order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(
                            decision === 'approved' ? 'Balance Approved ✅' : 'Balance Rejected ❌',
                            decision === 'approved' ? 'Remaining balance verified. Order can proceed.' : 'Balance payment rejected.',
                            decision === 'approved' ? 'success' : 'danger'
                        );

                        // Update cached payment object
                        if (window._currentOrderPayment) {
                            window._currentOrderPayment.balanceStatus = decision === 'approved' ? 'Approved' : 'Rejected';
                        }

                        // Update cached order object
                        if (window.currentViewingOrder) {
                            window.currentViewingOrder.balancePaymentStatus = decision === 'approved' ? 'Approved' : 'Rejected';
                            if (decision === 'approved') {
                                window.currentViewingOrder.paymentBalance = 0;
                            }
                            // Refresh the order details modal UI
                            syncModalWithOrder(window.currentViewingOrder);
                        }

                        // Hide the Verify Balance CTA button in the Order Details modal
                        const verifyBalanceGroup = document.getElementById('verifyBalanceActionsGroup');
                        if (verifyBalanceGroup) verifyBalanceGroup.style.display = 'none';

                        setTimeout(() => {
                            if (typeof refreshOrders === 'function') refreshOrders();
                        }, 1000);

                        // Close balance modal after short delay
                        setTimeout(() => closeBalanceVerifyModal(), 1200);
                    } else {
                        showToast('Error', data.message || 'Action failed.', 'danger');
                        if (actionRow) actionRow.style.display = 'flex'; // Revert
                    }
                })
                .catch(() => {
                    showToast('Error', 'Server error. Please try again.', 'danger');
                    if (actionRow) actionRow.style.display = 'flex'; // Revert
                });
        }
    );
}

// =====================================================================
// RECEIPT LIGHTBOX
// =====================================================================
function openReceiptLightbox() {
    // Support: open from either the Order Details modal or the Payment Verify modal
    const pvmImg = document.getElementById('pvmReceiptImage');
    const orderImg = document.getElementById('orderReceiptThumb');

    const src = (pvmImg && pvmImg.offsetParent !== null && pvmImg.src && !pvmImg.src.includes('logojd.png'))
        ? pvmImg.src
        : orderImg?.src;

    if (!src || src.includes('undefined') || src.includes('logojd.png')) {
        console.warn('JDE Dashboard: No valid receipt image source found to enlarge.');
        return;
    }

    const lb = document.getElementById('receiptLightbox');
    const img = document.getElementById('lightboxReceiptImg');
    const dl = document.getElementById('lightboxDownload');

    if (img) img.src = src;
    if (dl) dl.href = src;
    if (lb) lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReceiptLightbox() {
    const lb = document.getElementById('receiptLightbox');
    if (lb) lb.classList.remove('active');
    document.body.style.overflow = '';
}

// =====================================================================
// 8. KEYBOARD SHORTCUTS
// =====================================================================
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeReceiptLightbox();
        closePaymentVerifyModal();
        closeProductModal();
        closeOrderStatusModal();
        closeViewOrderModal();
        closeViewProductModal();
    }
    // 'N' key opens Add Product when on inventory tab and no input is focused
    if (e.key === 'n' || e.key === 'N') {
        const tag = document.activeElement.tagName;
        if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
            const inventorySec = document.getElementById('inventorySection');
            if (inventorySec && inventorySec.classList.contains('active')) {
                openAddModal();
            }
        }
    }
});

// =====================================================================
// ACTION DROPDOWN TOGGLE (Fixed-Position Smart Flip)
// =====================================================================
let _activeOrderDropdown = null;
let _activeOrderMenu = null;

function closeAllOrderActionMenus() {
    if (_activeOrderMenu) {
        _activeOrderMenu.remove();
        _activeOrderMenu = null;
    }
    if (_activeOrderDropdown) {
        _activeOrderDropdown.classList.remove('active');
        _activeOrderDropdown = null;
    }
}

function positionOrderMenu(trigger, menu) {
    menu.style.position = 'fixed';
    menu.style.zIndex = '99999';
    menu.style.display = 'block';

    const triggerRect = trigger.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const spaceBelow = window.innerHeight - triggerRect.bottom;
    const spaceAbove = triggerRect.top;

    let left = triggerRect.right - menuRect.width;
    if (left < 8) left = 8;

    let top;
    if (spaceBelow >= menuRect.height + 10 || spaceBelow >= spaceAbove) {
        top = triggerRect.bottom + 5;
    } else {
        top = triggerRect.top - menuRect.height - 5;
    }

    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
    menu.style.right = 'auto';
    menu.style.bottom = 'auto';
    menu.style.marginTop = '0';
}

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.action-trigger');
    if (trigger) {
        const dropdown = trigger.closest('.action-dropdown');
        const originalMenu = dropdown ? dropdown.querySelector('.action-menu') : null;

        if (_activeOrderDropdown === dropdown) {
            closeAllOrderActionMenus();
            return;
        }

        closeAllOrderActionMenus();

        if (dropdown && originalMenu) {
            const portalMenu = originalMenu.cloneNode(true);
            portalMenu.classList.add('action-menu-portal');
            document.body.appendChild(portalMenu);

            dropdown.classList.add('active');
            _activeOrderDropdown = dropdown;
            _activeOrderMenu = portalMenu;
            positionOrderMenu(trigger, portalMenu);
        }
    } else if (!e.target.closest('.action-menu-portal')) {
        closeAllOrderActionMenus();
    }
});

document.addEventListener('scroll', closeAllOrderActionMenus, true);
window.addEventListener('resize', closeAllOrderActionMenus);


// =====================================================================
// PRODUCTION CARD / MEASUREMENT MODAL
// =====================================================================
function openMeasurementModal(item) {
    const modal = document.getElementById('measurementModal');
    if (!modal) return;

    // Populate header and basic info
    document.getElementById('mcItemName').textContent = item.productName || 'Custom Item';
    document.getElementById('mcCustomer').textContent = window.currentViewingOrder ? window.currentViewingOrder.customerName : '---';
    document.getElementById('mcOrderID').textContent = window.currentViewingOrder ? '#ORD-' + String(window.currentViewingOrder.orderID).padStart(3, '0') : '---';
    document.getElementById('mcDate').textContent = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    // Populate Grid
    const grid = document.getElementById('mcGrid');
    grid.innerHTML = '';

    if (item.customSpecs) {
        Object.entries(item.customSpecs).forEach(([key, val]) => {
            if (val > 0) {
                const label = key.replace(/([A-Z])/g, ' ').replace(/^./, str => str.toUpperCase());
                const metricDiv = document.createElement('div');
                metricDiv.className = 'spec-metric';
                metricDiv.innerHTML = `<label>${label}</label><span>${val}</span>`;
                grid.appendChild(metricDiv);
            }
        });
    }

    modal.classList.add('active');
}

function closeMeasurementModal() {
    const modal = document.getElementById('measurementModal');
    if (modal) modal.classList.remove('active');
}

// --- End of payment verification logic ---

function openBalanceReceiptLightbox() {
    const img = document.getElementById('bvmReceiptImage');
    if (img && img.src) window.open(img.src, '_blank');
}

// --- Real-time Hook Integration ---
// Hook into the global SSE event bus from scripts.php to auto-update
window.refreshDashboardStats = function () {
    if (typeof refreshOrders === 'function') {
        refreshOrders();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (typeof refreshOrders === 'function') {
        refreshOrders(true);
    }
});



// Helper for toast (adjust if you have a different name)
function showToast(title, message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(title, message, type);
    }
}


/**
 * =====================================================================
 * DISCOUNT OVERRIDE SYSTEM
 * =====================================================================
 */

let allDiscountProducts = [];
let selectedDiscountProduct = null;

function openDiscountAuthModal() {
    document.getElementById('discountAuthModal').style.display = 'flex';
    const passInput = document.getElementById('jdeAuthPass');
    if (passInput) {
        passInput.value = '';
        passInput.focus();
        passInput.style.borderColor = '#f1f5f9';
    }
    const errorEl = document.getElementById('jdeAuthError');
    if (errorEl) {
        errorEl.style.setProperty('display', 'none', 'important');
    }
}

function closeDiscountAuthModal() {
    document.getElementById('discountAuthModal').style.display = 'none';
}

function openDiscountPanel() {
    document.getElementById('discountPanelModal').style.display = 'flex';
    fetchProductsForDiscount();
}

function closeDiscountPanelModal() {
    document.getElementById('discountPanelModal').style.display = 'none';
}

function fetchProductsForDiscount() {
    const list = document.getElementById('discountProdList');
    list.innerHTML = '<div style="text-align:center; padding:20px; color:#94a3b8;"><i class="bi bi-arrow-repeat spin"></i> Loading products...</div>';

    fetch('order_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_all_products'
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allDiscountProducts = data.products;
                renderDiscountProdList(allDiscountProducts);
            }
        });
}

function renderDiscountProdList(products) {
    const list = document.getElementById('discountProdList');
    list.innerHTML = '';

    // Filter by category if selected
    const activeCat = document.querySelector('.cat-chip.active').dataset.cat;
    let filtered = products;
    if (activeCat !== 'all') {
        filtered = products.filter(p => {
            if (activeCat === 'Men') return p.productName.toLowerCase().includes('men');
            if (activeCat === 'Women') return p.productName.toLowerCase().includes('women');
            if (activeCat === 'Bespoke') return !p.productName.toLowerCase().includes('men') && !p.productName.toLowerCase().includes('women');
            return true;
        });
    }

    if (filtered.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8;"><i class="bi bi-search" style="font-size:2rem; display:block; margin-bottom:10px;"></i> No matching products</div>';
        return;
    }

    filtered.forEach(p => {
        const item = document.createElement('div');
        item.className = 'discount-prod-item';
        if (selectedDiscountProduct && selectedDiscountProduct.productID == p.productID) {
            item.classList.add('active');
        }

        const hasOverride = p.customDiscountPercent !== null;
        const imgSrc = p.productImage ? "../../JDE_USER/" + p.productImage.replace("../", "") : "../../JDE_USER/assets/img/logojd.png";

        item.innerHTML = `
            <img src="${imgSrc}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
            <div style="flex: 1; min-width: 0;">
                <p style="margin: 0; font-size: 0.85rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #0b2e46;">${p.productName}</p>
                <p style="margin: 0; font-size: 0.7rem; color: #64748b; font-weight: 600;">ID: #${p.productID} ${hasOverride ? '• <span style="color:#10b981;">Custom</span>' : ''}</p>
            </div>
            ${hasOverride ? '<i class="bi bi-patch-check-fill" style="color: #10b981; font-size: 0.9rem;"></i>' : ''}
        `;

        item.onclick = () => selectProductForDiscount(p);
        list.appendChild(item);
    });
}

function selectProductForDiscount(p) {
    selectedDiscountProduct = p;
    renderDiscountProdList(allDiscountProducts); // Refresh selection highlight

    document.getElementById('discountEmptyState').style.display = 'none';
    document.getElementById('discountConfigArea').style.display = 'block';

    document.getElementById('discountProdName').innerText = p.productName;
    document.getElementById('discountProdID').innerText = `PRODUCT ID: #${p.productID}`;

    const price = parseFloat(p.price || 0);
    document.getElementById('discountProdBasePrice').innerText = `Base Price: ₱${price.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

    const imgSrc = p.productImage ? "../../JDE_USER/" + p.productImage.replace("../", "") : "../../JDE_USER/assets/img/logojd.png";
    document.getElementById('discountProdImg').src = imgSrc;

    document.getElementById('customDiscountPct').value = p.customDiscountPercent || '';
    document.getElementById('customMinQty').value = p.customMinQty || '';

    updateDiscountPreview();
}

function updateDiscountPreview() {
    if (!selectedDiscountProduct) return;

    const basePrice = parseFloat(selectedDiscountProduct.price || 0);
    const pct = parseFloat(document.getElementById('customDiscountPct').value || 0);

    const savings = basePrice * (pct / 100);
    const newPrice = basePrice - savings;

    document.getElementById('prevBasePrice').innerText = `₱${basePrice.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
    document.getElementById('prevSavings').innerText = `-₱${savings.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
    document.getElementById('prevNewPrice').innerText = `₱${newPrice.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
}

function setQuickPct(pct) {
    document.getElementById('customDiscountPct').value = pct;
    updateDiscountPreview();
}

// Category Filtering Init
document.addEventListener('DOMContentLoaded', () => {
    const scroller = document.getElementById('discountCatScroller');
    if (scroller) {
        scroller.addEventListener('click', (e) => {
            const chip = e.target.closest('.cat-chip');
            if (!chip) return;

            document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            if (allDiscountProducts) renderDiscountProdList(allDiscountProducts);
        });
    }

    const searchInput = document.getElementById('discountProdSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allDiscountProducts.filter(p =>
                p.productName.toLowerCase().includes(term) ||
                p.productID.toString().includes(term)
            );
            renderDiscountProdList(filtered);
        });
    }
});

function saveDiscountOverride() {
    if (!selectedDiscountProduct) return;

    const pct = document.getElementById('customDiscountPct').value;
    const minQty = document.getElementById('customMinQty').value;

    if (pct && (pct < 0 || pct > 100)) {
        return Swal.fire('Invalid Input', 'Discount percentage must be between 0 and 100', 'warning');
    }
    if (pct && !minQty) {
        return Swal.fire('Missing Field', 'Please specify a minimum quantity for this discount', 'warning');
    }

    const formData = new URLSearchParams();
    formData.append('action', 'update_discount_rule');
    formData.append('productID', selectedDiscountProduct.productID);
    formData.append('discountPct', pct);
    formData.append('minQty', minQty);

    const btn = document.querySelector('#discountConfigArea .btn-save');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
        btn.disabled = true;
    }

    fetch('order_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                if (data.success) {
                    btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Saved!';
                    btn.style.background = '#10b981';
                    btn.style.color = '#fff';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                    fetchProductsForDiscount();
                } else {
                    btn.innerHTML = originalText;
                    alert('Error: ' + (data.message || 'Failed to update rule'));
                }
            }
        });
}

function resetDiscountRule() {
    if (!selectedDiscountProduct) return;

    const btn = document.querySelector('#discountConfigArea .btn-cancel');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>...';
        btn.disabled = true;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'update_discount_rule');
    formData.append('productID', selectedDiscountProduct.productID);
    formData.append('discountPct', ''); // Null on backend
    formData.append('minQty', '');

    fetch('order_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
            if (data.success) {
                fetchProductsForDiscount();
                document.getElementById('customDiscountPct').value = '';
                document.getElementById('customMinQty').value = '';
            }
        });
}

// Live Search for products
document.getElementById('discountProdSearch').addEventListener('input', function (e) {
    const term = e.target.value.toLowerCase();
    const filtered = allDiscountProducts.filter(p =>
        p.productName.toLowerCase().includes(term) ||
        p.productID.toString().includes(term)
    );
    renderDiscountProdList(filtered);
});

/**
 * =====================================================================
 * ORDER-SPECIFIC DISCOUNT ACTIONS
 * =====================================================================
 */

function applyBulkDiscountToSingle() {
    const order = window.currentViewingOrder;
    if (!order) return;

    Swal.fire({
        title: 'Recalculate Discount?',
        text: "This will refresh the order's total price based on the current product discount rules. Any previous manual adjustments might be overridden.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, Recalculate',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const fd = new FormData();
            fd.append('action', 'recalculate_discount');
            fd.append('orderID', order.orderID);

            return fetch('order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Update failed');
                    return data;
                })
                .catch(error => Swal.showValidationMessage(`Request failed: ${error}`));
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Updated!', 'Order price has been recalculated.', 'success');
            refreshOrders(); // Refresh table
            // Re-fetch details to update open modal
            const fd = new FormData();
            fd.append('action', 'get_order_by_id');
            fd.append('orderID', order.orderID);
            fetch('order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { if (d.success) syncModalWithOrder(d.order); });
        }
    });
}

function openManualDiscountModal() {
    const order = window.currentViewingOrder;
    if (!order) return;

    Swal.fire({
        title: 'Manual Discount Adjustment',
        html: `
            <div style="text-align: left; padding: 10px;">
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Apply a one-time percentage discount to this entire order.</p>
                <div class="form-group">
                    <label style="font-size: 0.75rem; font-weight: 800; color: #475569; display: block; margin-bottom: 5px;">DISCOUNT PERCENTAGE (%)</label>
                    <input type="number" id="manualDiscInput" class="swal2-input" placeholder="e.g. 10" min="0" max="50" style="margin: 0; width: 100%;">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Apply Discount',
        preConfirm: () => {
            const pct = document.getElementById('manualDiscInput').value;
            if (!pct || pct < 0 || pct > 50) {
                Swal.showValidationMessage('Please enter a valid percentage (0-50%)');
                return false;
            }

            const fd = new FormData();
            fd.append('action', 'apply_manual_discount');
            fd.append('orderID', order.orderID);
            fd.append('discountPercentage', pct);

            return fetch('order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Update failed');
                    return data;
                })
                .catch(error => Swal.showValidationMessage(`Request failed: ${error}`));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Success', 'Manual discount applied.', 'success');
            refreshOrders();
            // Sync modal
            const fd = new FormData();
            fd.append('action', 'get_order_by_id');
            fd.append('orderID', order.orderID);
            fetch('order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { if (d.success) syncModalWithOrder(d.order); });
        }
    });
}

function toggleDiscountPassword() {
    const passInput = document.getElementById('jdeAuthPass');
    const toggleIcon = document.getElementById('passToggleIcon');

    // Clear error when starting to type
    if (passInput.type === 'password') {
        passInput.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passInput.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
}

function jdeVerifyAccess() {
    const passInput = document.getElementById('jdeAuthPass');
    const errorEl = document.getElementById('jdeAuthError');
    const password = passInput.value;

    // Reset state before checking
    if (window.authErrorTimeout) clearTimeout(window.authErrorTimeout);
    errorEl.style.setProperty('display', 'none', 'important');
    passInput.style.borderColor = '#f1f5f9';

    // 1. Strict Empty Check
    if (!password || password.trim().length === 0) {
        errorEl.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Password is required.';
        errorEl.style.setProperty('display', 'flex', 'important');
        passInput.style.borderColor = '#ef4444';
        passInput.focus();

        window.authErrorTimeout = setTimeout(() => {
            errorEl.style.setProperty('display', 'none', 'important');
            passInput.style.borderColor = '#f1f5f9';
        }, 3000);
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'verify_admin_password');
    formData.append('password', password);

    fetch('order_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            errorEl.style.setProperty('display', 'none', 'important');
            passInput.style.borderColor = '#f1f5f9';
            
            // Explicitly close Step 1 and open Step 2
            closeDiscountAuthModal();
            openDiscountPanel();
            
            passInput.value = ''; 
        } else {
            // 2. Incorrect Password Failure
            errorEl.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> ' + (data.message || 'Incorrect password. Please try again.');
            errorEl.style.setProperty('display', 'flex', 'important');
            passInput.style.borderColor = '#ef4444';
            passInput.value = '';
            passInput.focus();

            window.authErrorTimeout = setTimeout(() => {
                errorEl.style.setProperty('display', 'none', 'important');
                passInput.style.borderColor = '#f1f5f9';
            }, 3000);
        }
    })
    .catch(err => {
        console.error('Verification error:', err);
        errorEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> System error. Please try again later.';
        errorEl.style.setProperty('display', 'flex', 'important');
    });
}

