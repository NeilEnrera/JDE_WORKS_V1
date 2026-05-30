/**
 * Products Management JavaScript
 * Extracted from products.view.php
 */

let currentEditId = null;
let currentDeleteId = null;

// Database Simulation - tbl_product (Moved from inline script)
let products = [
    { productID: 1, productName: 'School Uniform Set', description: 'Complete school uniform with polo and pants', category: 'Clothing', size: 'Large', price: 2250.00, stocks: 27, userID: 1, createdBy: 'Admin', updatedBy: null },
    { productID: 2, productName: 'White Polo', description: 'White long-sleeve polo shirt', category: 'Clothing', size: 'Large', price: 750.00, stocks: 8, userID: 1, createdBy: 'Admin', updatedBy: null },
    { productID: 3, productName: 'White Polo', description: 'White long-sleeve polo shirt', category: 'Clothing', size: 'Medium', price: 650.00, stocks: 21, userID: 1, createdBy: 'Admin', updatedBy: 'Admin' },
    { productID: 4, productName: 'PE Uniform', description: 'Physical education uniform set', category: 'Clothing', size: 'Large', price: 1500.00, stocks: 15, userID: 1, createdBy: 'Admin', updatedBy: null },
    { productID: 5, productName: 'School ID Lanyard', description: 'Official school ID lanyard', category: 'Accessories', size: 'One Size', price: 95.00, stocks: 50, userID: 1, createdBy: 'Admin', updatedBy: null }
];

document.addEventListener('DOMContentLoaded', function () {
    renderProducts();

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Filter by category
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function (e) {
            const filterValue = e.target.value;
            const rows = document.querySelectorAll('#productsTable tbody tr');
            rows.forEach(row => {
                if (filterValue === 'all') {
                    row.style.display = '';
                } else {
                    const categoryCell = row.cells[2];
                    const category = categoryCell ? categoryCell.textContent.trim() : '';
                    row.style.display = category === filterValue ? '' : 'none';
                }
            });
        });
    }
});

// Render products table
function renderProducts() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    products.forEach(product => {
        const row = document.createElement('tr');
        row.setAttribute('data-product-id', product.productID);
        row.innerHTML = `
            <td>#PROD-${String(product.productID).padStart(3, '0')}</td>
            <td>${product.productName}</td>
            <td>${product.category}</td>
            <td>${product.size}</td>
            <td>₱${product.price.toFixed(2)}</td>
            <td>${product.stocks}</td>
            <td>
                <div class="action-dropdown">
                    <button class="action-trigger">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <div class="action-menu">
                        <button class="action-item view" onclick="viewProduct(${product.productID})">
                            <i class="bi bi-eye"></i>
                            <span>View Details</span>
                        </button>
                        <button class="action-item edit" onclick="editProduct(${product.productID})">
                            <i class="bi bi-pencil"></i>
                            <span>Update Product</span>
                        </button>
                        <button class="action-item delete" onclick="confirmDelete(${product.productID})">
                            <i class="bi bi-trash"></i>
                            <span>Delete Product</span>
                        </button>
                    </div>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Re-attach dropdown listeners
    attachDropdownListeners();
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

// View Product
function viewProduct(id) {
    const product = products.find(p => p.productID === id);
    if (product) {
        // Standardized Premium IDs
        const idEl = document.getElementById('detailProdID');
        const nameEl = document.getElementById('detailProdName');
        const descEl = document.getElementById('detailProdDesc');
        const catEl = document.getElementById('detailProdCat');
        const sizeEl = document.getElementById('detailProdSize');
        const priceEl = document.getElementById('detailProdPrice');
        const stockEl = document.getElementById('detailProdStock');
        const createdEl = document.getElementById('detailCreatedBy');
        const updatedEl = document.getElementById('detailUpdatedBy');
        const imgEl = document.getElementById('detailProdImage');

        if (idEl) idEl.textContent = '#PROD-' + String(product.productID).padStart(3, '0');
        if (nameEl) nameEl.textContent = product.productName;
        if (descEl) descEl.textContent = product.description || 'No description provided.';
        if (catEl) catEl.textContent = product.category;
        if (sizeEl) sizeEl.textContent = product.size;
        if (priceEl) priceEl.textContent = '₱' + product.price.toLocaleString(undefined, { minimumFractionDigits: 2 });
        if (stockEl) stockEl.textContent = product.stocks;
        if (createdEl) createdEl.textContent = product.createdBy || 'Admin';
        if (updatedEl) updatedEl.textContent = product.updatedBy || 'Not yet updated';

        if (imgEl) {
            imgEl.src = product.productImage
                ? '../../JDE_USER/' + product.productImage.replace('../', '')
                : '../../JDE_USER/assets/img/logojd.png';
        }

        // Set up Edit button shortcut
        const editBtn = document.getElementById('editFromViewBtn');
        if (editBtn) {
            editBtn.onclick = () => {
                closeViewProductModal();
                editProduct(id);
            };
        }

        const modal = document.getElementById('viewProductModal');
        if (modal) modal.classList.add('active');
    }
}

function closeViewProductModal() {
    const modal = document.getElementById('viewProductModal');
    if (modal) modal.classList.remove('active');
}

// Size Selection Function
function selectSize(modalType, size) {
    const sizeInput = document.getElementById(modalType + 'Size');
    const modalId = modalType === 'add' ? 'addModal' : 'editModal';
    const buttons = document.querySelectorAll(`#${modalId} .size-btn`);

    buttons.forEach(btn => btn.classList.remove('selected'));

    // Find the button that was clicked (since 'event' might be unreliable in external scripts)
    // Actually, event.currentTarget should work if called from onclick="..."
    if (window.event && window.event.currentTarget) {
        window.event.currentTarget.classList.add('selected');
    }

    if (sizeInput) sizeInput.value = size;
}

// Add Product
function openAddModal() {
    const nameEl = document.getElementById('addProductName');
    const descEl = document.getElementById('addDescription');
    const catEl = document.getElementById('addCategory');
    const sizeEl = document.getElementById('addSize');
    const priceEl = document.getElementById('addPrice');
    const stockEl = document.getElementById('addStock');

    if (nameEl) nameEl.value = '';
    if (descEl) descEl.value = '';
    if (catEl) catEl.value = '';
    if (sizeEl) sizeEl.value = '';
    if (priceEl) priceEl.value = '';
    if (stockEl) stockEl.value = '';

    document.querySelectorAll('#addModal .size-btn').forEach(btn => {
        btn.classList.remove('selected');
    });

    const modal = document.getElementById('addModal');
    if (modal) modal.classList.add('active');
}

function closeAddModal() {
    const modal = document.getElementById('addModal');
    if (modal) modal.classList.remove('active');
}

function saveNewProduct() {
    const name = document.getElementById('addProductName').value.trim();
    const description = document.getElementById('addDescription').value.trim();
    const category = document.getElementById('addCategory').value;
    const size = document.getElementById('addSize').value.trim();
    const priceText = document.getElementById('addPrice').value;
    const stockText = document.getElementById('addStock').value;
    const price = parseFloat(priceText);
    const stock = parseInt(stockText);

    if (!name || !category || !size || isNaN(price) || isNaN(stock)) {
        if (typeof showToast === 'function') {
            showToast('Warning', 'Please fill in all required fields!', 'warning');
        } else {
            alert('Please fill in all required fields!');
        }
        return;
    }

    const newProduct = {
        productID: products.length > 0 ? Math.max(...products.map(p => p.productID)) + 1 : 1,
        productName: name,
        description: description,
        category: category,
        size: size,
        price: price,
        stocks: stock,
        userID: 1,
        createdBy: 'Admin',
        updatedBy: null
    };

    products.push(newProduct);
    renderProducts();
    closeAddModal();

    if (typeof showToast === 'function') {
        showToast('Success', `Product "${name}" added successfully!`, 'success');
    } else {
        alert(`Product "${name}" added successfully!`);
    }
}

// Edit Product
function editProduct(id) {
    const product = products.find(p => p.productID === id);
    if (product) {
        currentEditId = id;
        const idEl = document.getElementById('editProductId');
        const nameEl = document.getElementById('editProductName');
        const descEl = document.getElementById('editDescription');
        const catEl = document.getElementById('editCategory');
        const sizeEl = document.getElementById('editSize');
        const priceEl = document.getElementById('editPrice');
        const stockEl = document.getElementById('editStock');

        if (idEl) idEl.value = '#PROD-' + String(product.productID).padStart(3, '0');
        if (nameEl) nameEl.value = product.productName;
        if (descEl) descEl.value = product.description || '';
        if (catEl) catEl.value = product.category;
        if (sizeEl) sizeEl.value = product.size;
        if (priceEl) priceEl.value = product.price;
        if (stockEl) stockEl.value = product.stocks;

        document.querySelectorAll('#editModal .size-btn').forEach(btn => {
            btn.classList.remove('selected');
            if (btn.getAttribute('data-size') === product.size) {
                btn.classList.add('selected');
            }
        });

        const modal = document.getElementById('editModal');
        if (modal) modal.classList.add('active');
    }
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) modal.classList.remove('active');
    currentEditId = null;
}

function saveProductUpdate() {
    if (currentEditId) {
        const product = products.find(p => p.productID === currentEditId);

        if (product) {
            product.productName = document.getElementById('editProductName').value.trim();
            product.description = document.getElementById('editDescription').value.trim();
            product.category = document.getElementById('editCategory').value;
            product.size = document.getElementById('editSize').value.trim();
            product.price = parseFloat(document.getElementById('editPrice').value);
            product.stocks = parseInt(document.getElementById('editStock').value);
            product.updatedBy = 'Admin';

            renderProducts();
            closeEditModal();

            if (typeof showToast === 'function') {
                showToast('Updated', `Product "${product.productName}" updated successfully!`, 'info');
            } else {
                alert(`Product "${product.productName}" updated successfully!`);
            }
        }
    }
}

// Delete Product
function confirmDelete(id) {
    const product = products.find(p => p.productID === id);
    if (product) {
        currentDeleteId = id;
        const nameEl = document.getElementById('deleteProductName');
        if (nameEl) nameEl.textContent = product.productName;

        const modal = document.getElementById('deleteModal');
        if (modal) modal.classList.add('active');
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.remove('active');
    currentDeleteId = null;
}

function deleteProduct() {
    if (currentDeleteId) {
        const productIndex = products.findIndex(p => p.productID === currentDeleteId);

        if (productIndex > -1) {
            const productName = products[productIndex].productName;
            products.splice(productIndex, 1);
            renderProducts();
            closeDeleteModal();

            if (typeof showToast === 'function') {
                showToast('Deleted', `Product "${productName}" has been deleted.`, 'danger');
            } else {
                alert(`Product "${productName}" has been deleted successfully!`);
            }
        }
    }
}
