/**
 * Products Management JavaScript
 * Extracted from products.view.php
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
document.addEventListener('DOMContentLoaded', () => {
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

    // Initial status counts for orders
    if (typeof updateStatusCounts === 'function') {
        updateStatusCounts();
    }

    // Make tables sortable
    if (typeof makeTableSortable === 'function') {
        makeTableSortable('productsTable');
        makeTableSortable('ordersTable');
    }

    // Initial product filtering to hide archives
    if (typeof applyProdFilters === 'function') {
        applyProdFilters();
    }

    // Flatpickr initialization
    const flatpickrConfig = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        disableMobile: true,
        monthSelectorType: "dropdown",
        scrollInput: false,
        onChange: () => {
            if (typeof applyOrderFilters === 'function') applyOrderFilters();
        }
    };

    // Orders filters
    if (document.getElementById('orderDateFrom')) flatpickr("#orderDateFrom", flatpickrConfig);
    if (document.getElementById('orderDateTo')) {
        flatpickr("#orderDateTo", { ...flatpickrConfig, position: "auto right" });
    }

    // Size Guide Dynamic Fields Listeners
    const prodTypeSelect = document.getElementById('prodType');
    const prodCatSelect = document.getElementById('prodCat');

    if (prodTypeSelect) {
        prodTypeSelect.addEventListener('change', updateSizeGuideFields);
    }
    if (prodCatSelect) {
        prodCatSelect.addEventListener('change', updateSizeGuideFields);
    }
});







// =====================================================================
// 2. IMAGE PREVIEW (multi-slot)
// =====================================================================
let imageSlots = [
    { file: null, path: '' },
    { file: null, path: '' },
    { file: null, path: '' },
    { file: null, path: '' }
];
const defaultPlacerholder = '../../JDE_USER/assets/img/logojd.png';

function triggerSlotUpload(slot) {
    const inputId = slot === 1 ? 'prodImage' : 'prodImage' + slot;
    document.getElementById(inputId).click();
}

function previewImage(input, slot) {
    const slotIndex = slot - 1;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        imageSlots[slotIndex].file = file;
        imageSlots[slotIndex].path = ''; // Clear path if new file chosen

        const reader = new FileReader();
        const previewId = slot === 1 ? 'imagePreview' : 'imagePreview' + slot;
        reader.onload = e => {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    updateSlotUI();
}

function handleBulkUpload(input) {
    if (!input.files || input.files.length === 0) return;

    const newFiles = Array.from(input.files);
    let fileIdx = 0;

    // First pass: fill truly empty slots
    for (let i = 0; i < 4 && fileIdx < newFiles.length; i++) {
        if (!imageSlots[i].file && (!imageSlots[i].path || imageSlots[i].path === '')) {
            imageSlots[i].file = newFiles[fileIdx++];
            imageSlots[i].path = '';
        }
    }

    // Second pass: fill slots that have "default.jpg" if any (though we usually have empty path)
    // Actually our state uses empty path for default.

    updateSlotUI();
    input.value = ''; // Reset bulk input
}

function removeSlotImage(slot) {
    const idx = slot - 1;
    imageSlots[idx].file = null;
    imageSlots[idx].path = '';
    updateSlotUI();
}

function moveSlotImage(slot, direction) {
    const idx = slot - 1;
    const targetIdx = idx + direction;

    if (targetIdx < 0 || targetIdx >= 4) return;

    // Swap
    const temp = imageSlots[idx];
    imageSlots[idx] = imageSlots[targetIdx];
    imageSlots[targetIdx] = temp;

    updateSlotUI();
}

function updateSlotUI() {
    imageSlots.forEach((slot, i) => {
        const slotNum = i + 1;
        const previewId = slotNum === 1 ? 'imagePreview' : 'imagePreview' + slotNum;
        const hiddenId = slotNum === 1 ? 'currentImagePath' : 'currentImagePath' + slotNum;
        const img = document.getElementById(previewId);
        const hidden = document.getElementById(hiddenId);

        if (slot.file) {
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; };
            reader.readAsDataURL(slot.file);
        } else if (slot.path) {
            img.src = '../../JDE_USER/' + slot.path.replace('../', '');
        } else {
            img.src = defaultPlacerholder;
        }

        if (hidden) hidden.value = slot.path || '';
    });
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

    document.getElementById('prodType').value = '';
    document.getElementById('prodFitType').value = '';
    document.getElementById('prodGender').value = '';

    // Reset image slots state
    imageSlots = [
        { file: null, path: '' },
        { file: null, path: '' },
        { file: null, path: '' },
        { file: null, path: '' }
    ];
    updateSlotUI();

    // Reset Size Stocks UI
    document.querySelectorAll('.size-checkbox').forEach(cb => {
        cb.checked = false;
        const cleanId = cb.id.replace('check-', '');
        const qtyInput = document.getElementById('qty-' + cleanId);
        const priceInput = document.getElementById('price-' + cleanId);
        const item = document.getElementById('size-item-' + cleanId);
        const card = document.getElementById('measurements-card-' + cleanId);

        if (qtyInput) { qtyInput.disabled = true; qtyInput.value = 0; }
        if (priceInput) { priceInput.disabled = true; priceInput.value = ''; }

        ['neck-', 'shoulder-', 'chest-', 'waist-', 'hips-', 'sleeve-', 'length-', 'pantsLength-', 'thigh-', 'crotch-'].forEach(prefix => {
            const el = document.getElementById(prefix + cleanId);
            if (el) { el.disabled = true; el.value = ''; }
        });


        if (item) item.classList.remove('active');
        if (card) { card.style.display = 'none'; }
    });


    const sizeGuideSection = document.getElementById('sizeGuideSection');
    if (sizeGuideSection) sizeGuideSection.style.display = 'none';

    document.getElementById('sizeStocksJson').value = '{}';
    document.getElementById('prodStock').value = 0;
    document.getElementById('prodSize').value = 'None';
    document.getElementById('prodPrice').value = '';

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

    document.getElementById('prodType').value = prod.type || '';
    document.getElementById('prodFitType').value = prod.fitType || '';
    document.getElementById('prodGender').value = prod.gender || '';
    document.getElementById('prodDesc').value = prod.description;

    // Load paths into state
    imageSlots = [
        { file: null, path: prod.productImage || '' },
        { file: null, path: prod.productImage2 || '' },
        { file: null, path: prod.productImage3 || '' },
        { file: null, path: prod.productImage4 || '' }
    ];
    updateSlotUI();

    // Reset and Load Size Stocks UI
    const sizeStr = prod.sizeStocks || '{}';
    const sizeStocks = typeof sizeStr === 'string' ? JSON.parse(sizeStr) : sizeStr;
    document.querySelectorAll('.size-checkbox').forEach(cb => {
        const label = cb.dataset.size;
        const cleanId = cb.id.replace('check-', '');
        const qtyInput = document.getElementById('qty-' + cleanId);
        const priceInput = document.getElementById('price-' + cleanId);
        const item = document.getElementById('size-item-' + cleanId);

        if (sizeStocks && sizeStocks[label] !== undefined) {
            const entry = sizeStocks[label];
            const qty = (typeof entry === 'object') ? (entry.qty || 0) : entry;
            const price = (typeof entry === 'object') ? (entry.price || '') : '';
            const measurements = (typeof entry === 'object') ? (entry.measurements || {}) : {};

            cb.checked = true;
            if (qtyInput) { qtyInput.disabled = false; qtyInput.value = qty; }
            if (priceInput) { priceInput.disabled = false; priceInput.value = price; }

            // Populating measurement fields
            const fields = ['neck', 'shoulder', 'chest', 'waist', 'hips', 'sleeve', 'length', 'pantsLength', 'thigh', 'crotch'];
            fields.forEach(f => {
                const el = document.getElementById(f + '-' + cleanId);
                if (el) {
                    el.disabled = false;
                    el.value = (measurements[f] !== undefined && measurements[f] !== null) ? measurements[f] : '';
                }
            });



            if (item) item.classList.add('active');
            const card = document.getElementById('measurements-card-' + cleanId);
            if (card) card.style.display = 'block';
        } else {
            cb.checked = false;
            if (qtyInput) { qtyInput.disabled = true; qtyInput.value = 0; }
            if (priceInput) { priceInput.disabled = true; priceInput.value = ''; }

            ['neck-', 'shoulder-', 'chest-', 'waist-', 'hips-', 'sleeve-', 'length-', 'pantsLength-', 'thigh-', 'crotch-'].forEach(prefix => {
                const el = document.getElementById(prefix + cleanId);
                if (el) { el.disabled = true; el.value = ''; }
            });



            if (item) item.classList.remove('active');
            const card = document.getElementById('measurements-card-' + cleanId);
            if (card) card.style.display = 'none';
        }
    });

    // Handle Size Guide Section Visibility
    const hasActiveSizes = Array.from(document.querySelectorAll('.size-checkbox')).some(cb => cb.checked);
    const sizeGuideSection = document.getElementById('sizeGuideSection');
    if (sizeGuideSection) sizeGuideSection.style.display = hasActiveSizes ? 'block' : 'none';

    document.getElementById('sizeStocksJson').value = JSON.stringify(sizeStocks || {});

    updateSizeGuideFields(); // Set fields based on loaded type
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

    document.getElementById('detailProdGender').textContent = prod.gender || '-';

    document.getElementById('detailProdPrice').textContent = '\u20b1' + parseFloat(prod.price).toLocaleString(undefined, { minimumFractionDigits: 2 });
    // Format Stocks Display
    const sizeStocks = prod.sizeStocks ? (typeof prod.sizeStocks === 'string' ? JSON.parse(prod.sizeStocks) : prod.sizeStocks) : {};
    let stockDisplay = '';
    const activeSizes = Object.entries(sizeStocks);
    if (activeSizes.length > 0) {
        stockDisplay = activeSizes.map(([s, entry]) => {
            const qty = (typeof entry === 'object') ? (entry.qty || 0) : entry;
            return `<span class="qty-badge">${s}: ${qty}</span>`;
        }).join(' ');
    } else {
        stockDisplay = prod.stocks > 0 ? `<span class="qty-badge">Total: ${prod.stocks}</span>` : '<span class="stock-badge low">Out of Stock</span>';
    }
    document.getElementById('detailProdStock').innerHTML = stockDisplay;
    document.getElementById('detailProdSize').textContent = prod.size || 'N/A';

    document.getElementById('detailProdType').textContent = prod.type || 'N/A';
    document.getElementById('detailProdFitType').textContent = prod.fitType || 'N/A';
    document.getElementById('detailProdDesc').textContent = prod.description || 'No description provided.';

    const defaultImg = '../../JDE_USER/assets/img/logojd.png';
    const makeImgSrc = (path) => path ? '../../JDE_USER/' + path.replace('../', '') : null;

    // Collect all available images
    const allImages = [
        prod.productImage, prod.productImage2, prod.productImage3, prod.productImage4
    ].map(makeImgSrc).filter(Boolean);
    if (allImages.length === 0) allImages.push(defaultImg);

    // Set main image to first
    const mainImg = document.getElementById('detailProdImage');
    mainImg.src = allImages[0];

    // Build thumbnails gallery
    const thumbsContainer = document.getElementById('galleryThumbs');
    if (thumbsContainer) {
        thumbsContainer.innerHTML = '';
        allImages.forEach((src, i) => {
            const thumb = document.createElement('div');
            thumb.className = 'gallery-thumb' + (i === 0 ? ' active' : '');
            thumb.innerHTML = `<img src="${src}" alt="Image ${i + 1}" onerror="this.src='${defaultImg}'">`;
            thumb.addEventListener('click', () => {
                mainImg.src = src;
                thumbsContainer.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
            thumbsContainer.appendChild(thumb);
        });
    }

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
    const defaultImg = '../../JDE_USER/assets/img/logojd.png';
    ['imagePreview', 'imagePreview2', 'imagePreview3', 'imagePreview4'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.src = defaultImg;
    });
    ['currentImagePath', 'currentImagePath2', 'currentImagePath3', 'currentImagePath4'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    document.getElementById('prodType').value = '';
    document.getElementById('prodFitType').value = '';
    if (productModal) productModal.classList.remove('active');
}

// --- RESTOCK MODAL FUNCTIONS ---
function openRestockModal(prod) {
    const modal = document.getElementById('restockModal');
    const nameEl = document.getElementById('restockProdName');
    const idInput = document.getElementById('restockProdID');
    const sizeSelect = document.getElementById('restockSize');
    const qtyInput = document.getElementById('restockQty');
    const imgEl = document.getElementById('restockProdImg');
    const curQtyEl = document.getElementById('restockCurrentQty');

    idInput.value = prod.productID;
    nameEl.textContent = prod.productName;
    qtyInput.value = '';

    // Set Product Image
    const imgSrc = prod.productImage
        ? '../../JDE_USER/' + prod.productImage.replace('../', '')
        : '../../JDE_USER/assets/img/logojd.png';
    imgEl.src = imgSrc;

    // Populate sizes
    sizeSelect.innerHTML = '';
    const sizeStocks = prod.sizeStocks ? (typeof prod.sizeStocks === 'string' ? JSON.parse(prod.sizeStocks) : prod.sizeStocks) : {};
    const sizes = Object.keys(sizeStocks);

    if (sizes.length > 0) {
        document.getElementById('restockSizeGroup').style.display = 'block';
        sizes.forEach(size => {
            const opt = document.createElement('option');
            opt.value = size;
            const qty = (typeof sizeStocks[size] === 'object') ? (sizeStocks[size].qty || 0) : sizeStocks[size];
            opt.textContent = size + ' (Current: ' + qty + ')';
            opt.dataset.qty = qty;
            sizeSelect.appendChild(opt);
        });
        sizeSelect.required = true;

        // Initial qty display
        const firstOpt = sizeSelect.options[0];
        curQtyEl.textContent = firstOpt.dataset.qty;
    } else {
        document.getElementById('restockSizeGroup').style.display = 'none';
        sizeSelect.required = false;
        curQtyEl.textContent = prod.stocks;
    }

    // Update qty display when size changes
    sizeSelect.onchange = function () {
        const selectedOpt = sizeSelect.options[sizeSelect.selectedIndex];
        if (selectedOpt) {
            curQtyEl.textContent = selectedOpt.dataset.qty;
        }
    };

    modal.classList.add('active');
}

function closeRestockModal() {
    const modal = document.getElementById('restockModal');
    if (modal) modal.classList.remove('active');
}

let _restockFormSubmitting = false;
if (restockForm) {
    restockForm.onsubmit = function (e) {
        e.preventDefault();
        if (_restockFormSubmitting) return;
        _restockFormSubmitting = true;

        const saveBtn = this.querySelector('button[type="submit"]');
        const originalBtnHTML = saveBtn ? saveBtn.innerHTML : '';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Restocking...';
        }

        const formData = new FormData(this);
        const idempotencyToken = Date.now() + '-' + Math.random().toString(36).slice(2, 9);
        formData.append('idempotency_token', idempotencyToken);

        fetch('../../JDE_USER/backend/product.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Stock Updated', `Successfully added ${formData.get('quantity')} items.`, 'success');
                    updateProductRow(data.product);
                    closeRestockModal();
                } else {
                    showToast('Error', data.message || 'Restock failed.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error', 'Connection failed.', 'danger');
            })
            .finally(() => {
                _restockFormSubmitting = false;
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalBtnHTML;
                }
            });
    };
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
                        updateProductRow(data.product);
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
            fetch('order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Order Archived', 'Order moved to archives.', 'success');
                        const row = document.querySelector(`#ordersTable tr[data-row-id="${id}"]`);
                        if (row) row.remove();
                        // Assuming applyOrderFilters() exists and can re-evaluate the table
                        if (typeof applyOrderFilters === 'function') applyOrderFilters();
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
                        updateProductRow(data.product);
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
                        const row = document.querySelector(`#productsTable tr[data-row-id="${id}"]`);
                        if (row) row.remove();
                        applyProdFilters();
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

// Guard flag — prevents duplicate submissions from rapid clicking
let _productFormSubmitting = false;

if (productForm) {
    productForm.onsubmit = function (e) {
        e.preventDefault();

        // --- SPAM CLICK PROTECTION ---
        if (_productFormSubmitting) return;
        _productFormSubmitting = true;

        // Lock the submit button visually
        const saveBtn = this.querySelector('button[type="submit"]');
        const originalBtnHTML = saveBtn ? saveBtn.innerHTML : '';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
        }

        const formData = new FormData(this);

        // Attach a one-time idempotency token so the backend can detect duplicate requests
        const idempotencyToken = Date.now() + '-' + Math.random().toString(36).slice(2, 9);
        formData.append('idempotency_token', idempotencyToken);

        // Overwrite standard file inputs with our state-managed images
        formData.delete('productImage');
        formData.delete('productImage2');
        formData.delete('productImage3');
        formData.delete('productImage4');
        formData.delete('currentImage');
        formData.delete('currentImage2');
        formData.delete('currentImage3');
        formData.delete('currentImage4');

        imageSlots.forEach((slot, i) => {
            const num = i + 1;
            const fieldName = num === 1 ? 'productImage' : 'productImage' + num;
            const currentField = num === 1 ? 'currentImage' : 'currentImage' + num;

            if (slot.file) {
                formData.append(fieldName, slot.file);
            }
            formData.append(currentField, slot.path || '');
        });

        fetch('../../JDE_USER/backend/product.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showToast('Success!', 'Product saved.', 'success');
                        updateProductRow(data.product);
                        productModal.classList.remove('active');
                    } else {
                        showToast('Error', data.message || 'Operation failed.', 'danger');
                    }
                } catch (e) {
                    console.error('JSON Parse Error. Server returned:', text);
                    showToast('Error', 'An unexpected error occurred.', 'danger');
                }
            })
            .catch((err) => {
                console.error('Fetch Error:', err);
                showToast('Error', 'Connection failed.', 'danger');
            })
            .finally(() => {
                // Always unlock so the admin can retry on error
                _productFormSubmitting = false;
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalBtnHTML;
                }
            });
    };
}

// Inline stock editing disabled as per user request
/*
document.querySelectorAll('.inline-stock').forEach(badge => {
    ... 
});
*/

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
        const isActive = parseInt(row.getAttribute('data-active')) === 1;

        const categoryMatch = (category === 'all' || rowCategory === category);
        const archiveMatch = showArchives ? !isActive : isActive;
        const searchMatch = (rowName.includes(searchTerm) || rowId.includes(searchTerm));

        if (categoryMatch && searchMatch && archiveMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    const emptyState = document.getElementById('prodEmptyState');
    if (emptyState) {
        const hasVisibleRows = Array.from(rows).some(r => r.id !== 'prodEmptyState' && r.style.display !== 'none');
        emptyState.style.display = hasVisibleRows ? 'none' : '';
    }
}

function updateProductRow(prod) {
    const tbody = document.querySelector('#productsTable tbody');

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

    const gender = prod.gender || '-';

    let stockHTML = '<div class="size-stocks-display">';
    const sizeStocks = prod.sizeStocks ? (typeof prod.sizeStocks === 'string' ? JSON.parse(prod.sizeStocks) : prod.sizeStocks) : {};
    const activeSizes = Object.entries(sizeStocks);

    const threshold = parseInt(prod.stockThreshold) || 5;

    if (activeSizes.length > 0) {
        stockHTML += activeSizes.map(([s, entry]) => {
            const qty = (typeof entry === 'object') ? (entry.qty || 0) : entry;
            let statusClass = 'in';
            let icon = 'bi-check-circle-fill';

            if (qty <= 0) {
                statusClass = 'out';
                icon = 'bi-x-circle-fill';
            } else if (qty <= threshold) {
                statusClass = 'critical';
                icon = 'bi-lightning-fill';
            } else if (qty < threshold * 2) {
                statusClass = 'low';
                icon = 'bi-exclamation-triangle-fill';
            }

            return `<span class="qty-pill ${statusClass}"><i class="bi ${icon}"></i><strong>${s}:</strong> ${qty}</span>`;
        }).join('');
    } else {
        const totalStock = parseInt(prod.stocks) || 0;
        let statusClass = 'in';
        let icon = 'bi-check-circle-fill';

        if (totalStock <= 0) {
            statusClass = 'out';
            icon = 'bi-x-circle-fill';
        } else if (totalStock <= threshold) {
            statusClass = 'critical';
            icon = 'bi-lightning-fill';
        } else if (totalStock < threshold * 2) {
            statusClass = 'low';
            icon = 'bi-exclamation-triangle-fill';
        }

        stockHTML += `<span class="qty-pill ${statusClass}"><i class="bi ${icon}"></i><strong>Total:</strong> ${totalStock}</span>`;
    }
    stockHTML += '</div>';


    // Use a temporary div to parse HTML but prevent ID collisions if any
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
        <td class="text-center-cell">
            ${stockHTML}
        </td>
        <td class="text-center-cell">
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
                        <button class="action-item" onclick="openRestockModal(${JSON.stringify(prod).replace(/"/g, '&quot;')})">
                            <i class="bi bi-box-seam"></i> Restock
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

    // Update data attributes
    row.setAttribute('data-row-id', prod.productID);
    row.setAttribute('data-category', prod.categoryName);
    row.setAttribute('data-active', prod.isActive);
    row.className = prod.isActive == 0 ? 'archived-row' : '';


    applyProdFilters();
}

function toggleArchives() {
    showArchives = !showArchives;
    const btn = document.getElementById('toggleArchivesBtn');

    // Maintain 'View Archive' label. Toggle icon and active class for state.
    if (showArchives) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="bi bi-trash3-fill"></i> View Archive';
    } else {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="bi bi-trash3"></i> View Archive';
    }

    applyProdFilters();
}

const prodSearchInput = document.getElementById('prodSearchInput');
if (prodSearchInput) prodSearchInput.addEventListener('keyup', applyProdFilters);

const prodCatFilter = document.getElementById('prodCategoryFilter');
if (prodCatFilter) prodCatFilter.addEventListener('change', applyProdFilters);

let activeOrderTriageFilter = 'all';



function updateStatusCounts() {
    const counts = { all: 0, Unpaid: 0, Paid: 0, Processing: 0, Shipped: 0, 'Ready to Deliver': 0, Completed: 0, Cancelled: 0 };
    document.querySelectorAll('#ordersTable tbody tr:not(#orderEmptyState)').forEach(tr => {
        counts.all++;
        const s = tr.dataset.status;
        if (s === 'Delivered') {
            counts['Completed']++;
        } else if (counts[s] !== undefined) {
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
        const matchStatus = status === 'all' ||
            tr.dataset.status === status ||
            (status === 'Completed' && tr.dataset.status === 'Delivered');
        const rowDate = tr.dataset.date || '';
        const matchFrom = !dateFrom || rowDate >= dateFrom;
        const matchTo = !dateTo || rowDate <= dateTo;
        const show = matchTerm && matchStatus && matchFrom && matchTo;
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

const orderDateFrom = document.getElementById('orderDateFrom');
if (orderDateFrom) orderDateFrom.addEventListener('change', applyOrderFilters);

const orderDateTo = document.getElementById('orderDateTo');
if (orderDateTo) orderDateTo.addEventListener('change', applyOrderFilters);

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
const orderStatusModal = document.getElementById('orderStatusModal');
let currentUpdateOrderID = null;

// Show/hide cancel reason textarea
const newStatusSelect = document.getElementById('newStatus');
if (newStatusSelect) {
    newStatusSelect.addEventListener('change', function () {
        const cancelGroup = document.getElementById('cancelReasonGroup');
        const proofGroup = document.getElementById('proofOfDeliveryGroup');
        const isDelivery = window.currentViewingOrder &&
            window.currentViewingOrder.distributionMethod &&
            window.currentViewingOrder.distributionMethod.toLowerCase() === 'delivery';

        if (cancelGroup) cancelGroup.style.display = this.value === 'Cancelled' ? '' : 'none';
        if (proofGroup) proofGroup.style.display = (isDelivery && (this.value === 'Delivered' || this.value === 'Completed')) ? '' : 'none';
    });
}

function quickUpdateStatus(id, newStatus, message) {
    showConfirmModal('Quick Status Update', message, () => {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('orderID', id);
        fd.append('status', newStatus);
        fd.append('cancelReason', '');

        fetch('order_handler.php', { method: 'POST', body: fd })
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

function updateOrderStatus(id, currentStatus) {
    currentUpdateOrderID = id;
    const select = document.getElementById('newStatus');
    const group = document.getElementById('cancelReasonGroup');
    const input = document.getElementById('cancelReasonInput');

    if (!select) return;

    // Standard statuses
    const deliveryFlow = ['Pending', 'Paid', 'Processing', 'Awaiting Balance', 'Out for delivery', 'Completed'];
    const pickupFlow = ['Pending', 'Paid', 'Processing', 'Awaiting Balance', 'Ready for Pick Up', 'Completed'];
    
    // Check distribution method from the currently viewing cached order
    const order = window.currentViewingOrder;
    const isPickUp = order && order.distributionMethod && (order.distributionMethod.toLowerCase().includes('pick') || order.distributionMethod.toLowerCase().includes('pickup'));
    const flow = isPickUp ? pickupFlow : deliveryFlow;
    
    const options = [];
    flow.forEach(s => {
        options.push({ value: s, label: s });
    });

    // Add special statuses
    if (!options.find(o => o.value === 'Cancelled')) options.push({ value: 'Cancelled', label: 'Cancelled' });
    if (!options.find(o => o.value === 'Return Items')) options.push({ value: 'Return Items', label: 'Return Items' });
    if (!options.find(o => o.value === 'Unpaid')) options.push({ value: 'Unpaid', label: 'Unpaid' });

    select.innerHTML = '';
    options.forEach(opt => {
        const option = document.createElement('option');
        option.value = opt.value;
        option.textContent = opt.label;
        select.appendChild(option);
    });

    select.value = currentStatus;
    if (group) group.style.display = currentStatus === 'Cancelled' ? '' : 'none';
    if (input) input.value = '';

    const proofGroup = document.getElementById('proofOfDeliveryGroup');
    const isDelivery = !isPickUp;
    if (proofGroup) proofGroup.style.display = (isDelivery && (currentStatus === 'Delivered' || currentStatus === 'Completed')) ? '' : 'none';
    document.getElementById('proofOfDeliveryInput').value = '';

    if (orderStatusModal) orderStatusModal.classList.add('active');
}

function closeOrderStatusModal() {
    if (orderStatusModal) orderStatusModal.classList.remove('active');
    currentUpdateOrderID = null;
}

const saveStatusBtn = document.getElementById('saveStatusBtn');
if (saveStatusBtn) {
    saveStatusBtn.addEventListener('click', () => {
        if (!currentUpdateOrderID) return;
        const status = document.getElementById('newStatus').value;
        const reason = document.getElementById('cancelReasonInput')?.value || '';
        const proofFile = document.getElementById('proofOfDeliveryInput')?.files[0];

        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('orderID', currentUpdateOrderID);
        fd.append('status', status);
        fd.append('cancelReason', reason);
        if (proofFile) {
            fd.append('proofOfDelivery', proofFile);
        }

        fetch('order_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeOrderStatusModal();
                    const successModal = document.getElementById('statusSuccessModal');
                    if (successModal) {
                        successModal.classList.add('active');
                    } else {
                        // Fallback simply alerts and reloads if the modal HTML is missing
                        alert('Order status updated successfully.');
                        location.reload();
                    }
                } else {
                    showToast('Error', data.message || 'Failed.', 'danger');
                }
            })
            .catch(() => showToast('Error', 'An unexpected error occurred.', 'danger'));
    });
}

// View Order Modal + Status Timeline
const viewOrderModal = document.getElementById('viewOrderModal');
const statusSteps = ['Unpaid', 'Paid', 'Processing', 'Shipped', 'Ready to Deliver', 'Completed'];

function updateTimeline(currentStatus) {
    const isCancelled = currentStatus === 'Cancelled';
    document.querySelectorAll('#orderTimeline .timeline-step').forEach(step => {
        const s = step.dataset.step;
        step.classList.remove('done', 'current', 'cancelled');
        if (isCancelled) {
            step.classList.add('cancelled');
        } else {
            const ci = statusSteps.indexOf(currentStatus);
            const si = statusSteps.indexOf(s);
            if (si < ci) step.classList.add('done');
            else if (si === ci) step.classList.add('current');
        }
    });
}

function viewOrder(order) {
    window.currentViewingOrder = order; // Cache for other modals
    // Ensure clicks on buttons don't trigger row click
    if (event && event.target.closest('.action-trigger, .btn-row-action')) return;

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
    if (priceEl) priceEl.textContent = '₱' + parseFloat(order.totalPrice).toLocaleString(undefined, { minimumFractionDigits: 2 });

    updateTimeline(order.orderStatus);

    // Set up modal action buttons
    const updateBtn = document.getElementById('modalUpdateBtn');
    const deleteBtn = document.getElementById('modalDeleteBtn');

    if (updateBtn) {
        updateBtn.onclick = (e) => {
            e.stopPropagation();
            updateOrderStatus(order.orderID, order.orderStatus);
        };
    }

    if (deleteBtn) {
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            confirmDeleteOrder(order.orderID, order.customerName);
        };
    }

    const tbody = document.querySelector('#viewOrderItemsTable tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading items...</td></tr>';

        const fd = new FormData();
        fd.append('action', 'get_details');
        fd.append('orderID', order.orderID);

        fetch('order_handler.php', { method: 'POST', body: fd })
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
                                <div class="info-group">
                                     <label>Stocks</label>
                                     <p id="detailProdStock"></p>
                                 </div>
                                 <div class="info-group">
                                     <label>Size</label>
                                     <p id="detailProdSize"></p>
                                 </div>
                                <div class="item-details">
                                    <span class="item-name">${item.productName || (item.isCustom ? 'Custom Tailoring' : 'Product Item')}</span>
                                    <span class="item-meta">${item.categoryName || 'General'}</span>
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
                            <td class="col-qty"><span class="qty-badge">${item.quantity}</span></td>
                            <td class="col-total fw-bold">₱${(item.price * item.quantity).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Populate Payment Info
                    const paymentArea = document.getElementById('paymentDetailsArea');
                    const verifyGroup = document.getElementById('verifyActionsGroup');
                    const statusBadge = document.getElementById('paymentStatusBadge');

                    // Store receipt path for the verify modal to use
                    window._currentOrderPayment = data.payment || null;
                    window._currentOrderData = order;

                    if (data.payment) {
                        const payMeth = document.getElementById('viewPaymentMethod');
                        const payStat = document.getElementById('viewPaymentStatus');
                        const refNum = document.getElementById('viewReferenceNumber');

                        if (payMeth) payMeth.textContent = data.payment.method || 'N/A';

                        const isGcash = data.payment.method && data.payment.method.toLowerCase().includes('gcash');
                        const payStatus = (data.payment.status || '').toLowerCase();
                        const gcashGroup = document.getElementById('gcashInfoGroup');

                        if (gcashGroup) gcashGroup.style.display = isGcash ? '' : 'none';
                        if (refNum) refNum.textContent = data.payment.reference || 'N/A';

                        // Payment status — styled text with colour class
                        if (payStat) {
                            payStat.textContent = data.payment.status || 'N/A';
                            payStat.className = 'pdr-value payment-status-text ' + payStatus;
                        }

                        // Status Badge in header
                        if (statusBadge && isGcash) {
                            statusBadge.className = 'payment-status-badge ' + payStatus;
                            statusBadge.style.display = 'inline-flex';
                            const icons = { pending: '⏳', approved: '✅', rejected: '❌', paid: '✅', unpaid: '⏳' };
                            statusBadge.textContent = (icons[payStatus] || '💳') + ' ' + (data.payment.status || 'Unknown');
                        } else if (statusBadge) {
                            statusBadge.style.display = 'none';
                        }

                        // Show "Verify Payment" CTA for GCash when payment is not yet approved/rejected
                        // DB stores 'Unpaid' for unverified GCash payments (not 'Pending')
                        const needsVerification = isGcash && (payStatus === 'pending' || payStatus === 'unpaid');
                        if (verifyGroup) {
                            verifyGroup.style.display = needsVerification ? '' : 'none';
                        }

                        // Receipt thumbnail — always show in Order Details for any GCash with a receipt
                        const receiptThumb = document.getElementById('orderReceiptThumb');
                        const receiptThumbGroup = document.getElementById('orderReceiptThumbGroup');
                        if (isGcash && data.payment.receipt) {
                            const rPath = data.payment.receipt.replace('../', '');
                            if (receiptThumb) {
                                receiptThumb.src = `../../JDE_USER/${rPath}`;
                                receiptThumb.onerror = function () {
                                    if (this.src.indexOf('/backend/') === -1) {
                                        this.src = `../../JDE_USER/backend/${rPath}`;
                                    } else {
                                        this.src = '../../JDE_USER/assets/img/logojd.png';
                                    }
                                };
                            }
                            if (receiptThumbGroup) receiptThumbGroup.style.display = '';
                        } else {
                            if (receiptThumbGroup) receiptThumbGroup.style.display = 'none';
                        }

                        if (paymentArea) paymentArea.style.display = '';
                    } else {
                        if (paymentArea) paymentArea.style.display = 'none';
                        if (verifyGroup) verifyGroup.style.display = 'none';
                        if (statusBadge) statusBadge.style.display = 'none';
                    }

                    // Populate Proof of Delivery
                    const proofArea = document.getElementById('proofOfDeliveryArea');
                    const proofImg = document.getElementById('viewProofImage');
                    if (order.proofOfDelivery) {
                        if (proofImg) proofImg.src = '../../JDE_USER/' + order.proofOfDelivery;
                        if (proofArea) proofArea.style.display = '';
                    } else {
                        if (proofArea) proofArea.style.display = 'none';
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Failed to load items.</td></tr>';
                }
            })
            .catch(() => { tbody.innerHTML = '<tr><td colspan="4" class="text-center">An error occurred.</td></tr>'; });
    }

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
    if (amtEl) amtEl.textContent = '₱' + parseFloat(order.totalPrice).toLocaleString(undefined, { minimumFractionDigits: 2 });
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
        const rPath = payment.receipt.replace('../', '');
        imgEl.src = `../../JDE_USER/${rPath}`;
        imgEl.onerror = function () {
            if (this.src.indexOf('/backend/') === -1) {
                this.src = `../../JDE_USER/backend/${rPath}`;
            } else {
                this.src = '../../JDE_USER/assets/img/logojd.png';
            }
        };
    } else if (imgEl) {
        imgEl.src = '../../JDE_USER/assets/img/logojd.png';
    }

    modal.classList.add('active');
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
    const adminNote = document.getElementById('pvmAdminNote')?.value?.trim() || '';

    showConfirmModal(
        `${label} Payment`,
        `Are you sure you want to ${label.toLowerCase()} the GCash payment for Order #ORD-${String(currentVerifyOrderID).padStart(3, '0')}?`,
        () => {
            const fd = new FormData();
            fd.append('action', 'verify_payment');
            fd.append('orderID', currentVerifyOrderID);
            fd.append('decision', decision);
            fd.append('adminNote', adminNote);

            fetch('order_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closePaymentVerifyModal();
                        const msg = decision === 'approved'
                            ? 'Payment approved! Order marked as Paid.'
                            : 'Payment rejected. Order set back to Unpaid.';
                        showToast(decision === 'approved' ? 'Payment Approved ✅' : 'Payment Rejected ❌', msg,
                            decision === 'approved' ? 'success' : 'danger');
                        setTimeout(() => location.reload(), 1600);
                    } else {
                        showToast('Error', data.message || 'Action failed.', 'danger');
                    }
                })
                .catch(() => showToast('Error', 'An unexpected error occurred.', 'danger'));
        }
    );
}

// =====================================================================
// RECEIPT LIGHTBOX
// =====================================================================
function openReceiptLightbox() {
    // Support: open from either the Order Details modal or the Payment Verify modal
    const pvmImg = document.getElementById('pvmReceiptImage');
    const orderImg = document.getElementById('viewReceiptImage');
    const src = (pvmImg && pvmImg.src && !pvmImg.src.endsWith('logojd.png'))
        ? pvmImg.src
        : orderImg?.src;
    if (!src) return;
    const lb = document.getElementById('receiptLightbox');
    const img = document.getElementById('lightboxReceiptImg');
    const dl = document.getElementById('lightboxDownload');
    if (img) img.src = src;
    if (dl) dl.href = src;
    if (lb) lb.classList.add('active');
    document.body.style.overflow = 'hidden'; // prevent scroll behind
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
// ACTION DROPDOWN TOGGLE
// =====================================================================
let _activeDropdown = null;
let _activeMenu = null;

function closeAllActionMenus() {
    if (_activeMenu) {
        _activeMenu.remove();
        _activeMenu = null;
    }
    if (_activeDropdown) {
        _activeDropdown.classList.remove('active');
        _activeDropdown = null;
    }
}

function positionMenu(trigger, menu) {
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

        if (_activeDropdown === dropdown) {
            closeAllActionMenus();
            return;
        }

        closeAllActionMenus();

        if (dropdown && originalMenu) {
            const portalMenu = originalMenu.cloneNode(true);
            portalMenu.classList.add('action-menu-portal');
            document.body.appendChild(portalMenu);

            dropdown.classList.add('active');
            _activeDropdown = dropdown;
            _activeMenu = portalMenu;
            positionMenu(trigger, portalMenu);
        }
    } else if (!e.target.closest('.action-menu-portal')) {
        closeAllActionMenus();
    }
});

document.addEventListener('scroll', closeAllActionMenus, true);
window.addEventListener('resize', closeAllActionMenus);


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

function toggleSizeInput(cleanId) {
    const check = document.getElementById('check-' + cleanId);
    const qtyInput = document.getElementById('qty-' + cleanId);
    const priceInput = document.getElementById('price-' + cleanId);
    const item = document.getElementById('size-item-' + cleanId);

    // Measurement inputs
    const mInputs = document.querySelectorAll(`#measurements-card-${cleanId} .m-field input`);


    const card = document.getElementById('measurements-card-' + cleanId);

    if (check.checked) {
        qtyInput.disabled = false;
        qtyInput.value = qtyInput.value || 0;
        if (priceInput) { priceInput.disabled = false; }

        // Only enable fields that are currently visible (filtered by type)
        mInputs.forEach(input => {
            if (input && input.parentElement.style.display !== 'none') {
                input.disabled = false;
            } else if (input) {
                input.disabled = true;
                input.value = '';
            }
        });

        item.classList.add('active');
        if (card) {
            card.style.display = 'block';
            card.classList.add('animate-appear');
        }
        qtyInput.focus();
    } else {
        qtyInput.disabled = true;
        if (priceInput) { priceInput.disabled = true; priceInput.value = ''; }
        mInputs.forEach(input => { if (input) { input.disabled = true; input.value = ''; } });
        item.classList.remove('active');
        if (card) card.style.display = 'none';
    }

    // Toggle main section visibility
    const hasChecked = Array.from(document.querySelectorAll('.size-checkbox')).some(cb => cb.checked);
    const section = document.getElementById('sizeGuideSection');
    if (section) {
        if (hasChecked) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    }

    updateSizeStocks();
}

/**
 * Dynamically toggles visibility of measurement fields based on Product Category or Type
 */
function updateSizeGuideFields() {
    const typeSelect = document.getElementById('prodType');
    const catSelect = document.getElementById('prodCat');
    if (!typeSelect || !catSelect) return;

    const type = typeSelect.value.toLowerCase();
    const catText = catSelect.options[catSelect.selectedIndex]?.text.toLowerCase() || '';

    console.log('[SizeGuide Debug] Type:', type, '| Category:', catText);

    // Map specific types/categories to general visibility groups
    let category = 'full'; // Default fallback

    // 1. Priority: Check Category Name (most definitive in JDE Admin)
    if (catText.includes('full set') || catText.includes('suit')) {
        category = 'full';
    } else if (catText.includes('upper')) {
        category = 'upper';
    } else if (catText.includes('lower')) {
        category = 'lower';
    }
    // 2. Secondary: Check Type select value (specific items)
    else if (['polo', 'blouse', 't-shirt'].includes(type)) {
        category = 'upper';
    } else if (['trouser', 'skirt', 'pants'].includes(type)) {
        category = 'lower';
    } else if (['suit'].includes(type) || type.includes('uniform')) {
        category = 'full';
    } else if (type === "") {
        // If everything is empty, show all fields by default
        category = 'full';
    }


    console.log('[SizeGuide Debug] Resolved Group:', category);

    // Select all measurement fields across all size cards
    const allFields = document.querySelectorAll('.m-field[data-m-type]');
    allFields.forEach(field => {
        const supportedGroups = field.dataset.mType || '';
        const input = field.querySelector('input');

        // Check if current mapped category is in the field's supported list
        if (supportedGroups.includes(category)) {
            field.style.display = 'flex';

            // Re-evaluate disabled state based on size checkbox
            const cleanId = input.id.split('-').pop();
            const check = document.getElementById('check-' + cleanId);
            if (check && check.checked) {
                input.disabled = false;
            } else {
                input.disabled = true;
            }
        } else {
            field.style.display = 'none';
            if (input) {
                input.disabled = true;
                input.value = ''; // Clear hidden field data to prevent saving irrelevant metrics
            }
        }
    });
}




function updateSizeStocks() {
    const sizeStocks = {};
    let totalStock = 0;
    let lowestPrice = null;
    const sizeItems = document.querySelectorAll('.size-item');

    sizeItems.forEach(item => {
        const check = item.querySelector('.size-checkbox');
        const qtyInput = item.querySelector('.size-qty-input');
        const priceInput = item.querySelector('.size-price-input');

        const cleanId = check.id.replace('check-', '');

        // Measurements
        const m = {};
        const fields = ['neck', 'shoulder', 'chest', 'waist', 'hips', 'sleeve', 'length', 'pantsLength', 'thigh', 'crotch'];
        fields.forEach(f => {
            const val = document.getElementById(f + '-' + cleanId)?.value || '';
            m[f] = (val !== "") ? parseFloat(val) : null;
        });

        const label = check.dataset.size;

        if (check.checked) {
            const qty = parseInt(qtyInput.value) || 0;
            const price = parseFloat(priceInput ? priceInput.value : 0) || 0;

            sizeStocks[label] = {
                qty,
                price,
                measurements: m
            };


            totalStock += qty;
            if (price > 0 && (lowestPrice === null || price < lowestPrice)) {
                lowestPrice = price;
            }
        }
    });

    document.getElementById('sizeStocksJson').value = JSON.stringify(sizeStocks);
    document.getElementById('prodStock').value = totalStock;

    // Auto-set the global price field to the lowest size price
    if (lowestPrice !== null) {
        const prodPriceInput = document.getElementById('prodPrice');
        if (prodPriceInput) prodPriceInput.value = lowestPrice.toFixed(2);
    }

    // Summary label for size if multiple
    const activeSizes = Object.keys(sizeStocks);
    document.getElementById('prodSize').value = activeSizes.length > 0 ? activeSizes.join(', ') : 'None';
}

