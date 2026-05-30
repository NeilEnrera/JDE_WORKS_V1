<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/admin-management.css?v=<?php echo time(); ?>">
    <title><?php echo $pageTitle ?? 'Management Hub'; ?></title>
</head>

<div class="admin-layout">
    <?php include __DIR__ . '/fragments/sidebar.php'; ?>

    <div class="main-content">
        <!-- Breadcrumb Navigation -->


        <div class="container">
            <div class="page-header">
                <div class="header-info">
                    <h2>Products Inventory</h2>
                    <p>Manage store products, categories, and stock levels</p>
                </div>
                <!-- Action button mounting point -->
                <div id="headerActions">
                    <button class="btn-add-product" onclick="openAddModal()">
                        <i class="bi bi-plus-circle"></i> Add New Product
                    </button>
                </div>
            </div>

            <!-- CONTENT SECTIONS -->

            <!-- Inventory Section -->
            <div id="inventorySection" class="tab-content active">



                <div class="filters-bar">
                    <div class="filter-group">
                        <label>Category:</label>
                        <select id="prodCategoryFilter" class="filter-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['categoryName']; ?>"><?php echo $cat['categoryName']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" id="prodSearchInput" placeholder="Search products...">
                    </div>
                    <div class="filter-actions">
                        <button class="btn-view-archives" id="toggleArchivesBtn" onclick="toggleArchives()">
                            <i class="bi bi-trash3"></i> View Archive
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-wrapper">
                        <table id="productsTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-col="0">ID <span class="sort-icon">↕</span></th>
                                    <th>Image</th>
                                    <th data-col="2">Product Name</th>
                                    <th data-col="3">Category</th>
                                    <th class="text-center-cell">Gender</th>
                                    <th class="text-center-cell">Size</th>
                                    <th class="text-center-cell">Stock Status</th>
                                    <th class="text-center-cell">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $prod): ?>
                                    <tr data-row-id="<?php echo $prod['productID']; ?>"
                                        data-category="<?php echo $prod['categoryName']; ?>"
                                        data-active="<?php echo $prod['isActive']; ?>"
                                        data-date="<?php echo $prod['dateCreated'] ? date('Y-m-d', strtotime($prod['dateCreated'])) : ''; ?>"
                                        class="<?php echo $prod['isActive'] == 0 ? 'archived-row' : ''; ?>">
                                        <td>#P-<?php echo str_pad($prod['productID'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <div class="prod-img-preview">
                                                <img src="<?php echo resolveProductPath($prod['productImage']); ?>"
                                                    alt="Product" loading="lazy"
                                                    onerror="this.src='../../JDE_USER/assets/img/logojd.png'">
                                            </div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($prod['productName']); ?></strong></td>
                                        <td><span class="category-tag"><?php echo $prod['categoryName']; ?></span></td>
                                        <td class="text-center-cell">
                                            <?php echo !empty($prod['gender']) ? htmlspecialchars($prod['gender']) : '-'; ?>
                                        </td>
                                        <td class="text-center-cell"><?php echo $prod['size']; ?></td>
                                        <td class="text-center-cell">
                                            <div class="size-stocks-display">
                                                <?php
                                                $sizeStocks = !empty($prod['sizeStocks']) ? json_decode($prod['sizeStocks'], true) : [];
                                                $threshold = (int) ($prod['stockThreshold'] ?? 5);

                                                if (!empty($sizeStocks)):
                                                    foreach ($sizeStocks as $size => $entry):
                                                        $qty = is_array($entry) ? ($entry['qty'] ?? 0) : $entry;

                                                        if ($qty <= 0) {
                                                            $statusClass = 'out';
                                                            $icon = 'bi-x-circle-fill';
                                                        } elseif ($qty <= $threshold) {
                                                            $statusClass = 'critical';
                                                            $icon = 'bi-lightning-fill';
                                                        } elseif ($qty < ($threshold * 2)) {
                                                            $statusClass = 'low';
                                                            $icon = 'bi-exclamation-triangle-fill';
                                                        } else {
                                                            $statusClass = 'in';
                                                            $icon = 'bi-check-circle-fill';
                                                        }
                                                        ?>
                                                        <span class="qty-pill <?php echo $statusClass; ?>">
                                                            <i class="bi <?php echo $icon; ?>"></i>
                                                            <strong><?php echo $size; ?>:</strong> <?php echo $qty; ?>
                                                        </span>
                                                    <?php endforeach;
                                                else:
                                                    $stock = (int) ($prod['stocks'] ?? 0);
                                                    if ($stock <= 0) {
                                                        $statusClass = 'out';
                                                        $icon = 'bi-x-circle-fill';
                                                    } elseif ($stock <= $threshold) {
                                                        $statusClass = 'critical';
                                                        $icon = 'bi-lightning-fill';
                                                    } elseif ($stock < ($threshold * 2)) {
                                                        $statusClass = 'low';
                                                        $icon = 'bi-exclamation-triangle-fill';
                                                    } else {
                                                        $statusClass = 'in';
                                                        $icon = 'bi-check-circle-fill';
                                                    }
                                                    ?>
                                                    <span class="qty-pill <?php echo $statusClass; ?>">
                                                        <i class="bi <?php echo $icon; ?>"></i>
                                                        <strong>Total:</strong> <?php echo $stock; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td class="text-center-cell">
                                            <div class="action-dropdown">
                                                <button class="action-trigger"><i
                                                        class="bi bi-three-dots-vertical"></i></button>
                                                <div class="action-menu">
                                                    <button class="action-item"
                                                        onclick="viewProduct(<?php echo htmlspecialchars(json_encode($prod)); ?>)">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </button>
                                                    <?php if ($prod['isActive'] == 1): ?>
                                                        <button class="action-item"
                                                            onclick="editProduct(<?php echo htmlspecialchars(json_encode($prod)); ?>)">
                                                            <i class="bi bi-pencil"></i> Edit Product
                                                        </button>
                                                        <button class="action-item"
                                                            onclick="openRestockModal(<?php echo htmlspecialchars(json_encode($prod)); ?>)">
                                                            <i class="bi bi-box-seam"></i> Restock
                                                        </button>
                                                        <button class="action-item delete"
                                                            onclick="confirmDelete(<?php echo $prod['productID']; ?>, '<?php echo addslashes($prod['productName']); ?>')">
                                                            <i class="bi bi-archive"></i> Archive Product
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="action-item restore"
                                                            onclick="restoreProduct(<?php echo $prod['productID']; ?>, '<?php echo addslashes($prod['productName']); ?>')">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Restore Product
                                                        </button>
                                                        <button class="action-item delete-permanent"
                                                            onclick="confirmPermanentDelete(<?php echo $prod['productID']; ?>, '<?php echo addslashes($prod['productName']); ?>')">
                                                            <i class="bi bi-trash-fill"></i> Delete Permanently
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="prodEmptyState" class="empty-state-row" style="display:none">
                                    <td colspan="8"><i class="bi bi-inbox"></i> No products match your search.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- --- PAGINATION CONTROLS --- -->
                <?php if ($totalRows > $limit): ?>
                <div class="pagination-container mt-4">
                    <div class="pagination-info">
                        Showing <?php echo count($products); ?> of <?php echo $totalRows; ?> total products
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>" class="pagination-btn">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        <?php else: ?>
                            <button class="pagination-btn disabled" disabled>
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1): ?>
                                <a href="?page=1&limit=<?php echo $limit; ?>" class="pagination-number">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"
                                    class="pagination-number <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>"
                                    class="pagination-number"><?php echo $totalPages; ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>" class="pagination-btn">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <button class="pagination-btn disabled" disabled>
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- MODALS -->

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal-overlay">
        <div class="modal landscape">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Product</h3>
                <button class="modal-close" onclick="closeProductModal()">&times;</button>
            </div>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="prodAction" name="action" value="add">
                <input type="hidden" id="prodID" name="productID" value="">
                <input type="hidden" id="currentImagePath" name="currentImage" value="">
                <input type="hidden" id="prodPrice" name="price" value="0">
                <div class="modal-body">
                    <div class="modal-landscape-content">
                        <!-- Left: Image Preview Area -->
                        <div class="modal-landscape-image">
                            <div class="form-group mb-0">
                                <label class="fw-bold">Product Images (up to 4)</label>
                                <div class="multi-image-container" id="multiImageManager">

                                    <!-- Main Slot -->
                                    <div class="image-slot main" id="slot-1">
                                        <div class="image-preview-container" id="preview-1"
                                            onclick="triggerSlotUpload(1)">
                                            <img id="imagePreview" src="../../JDE_USER/assets/img/logojd.png"
                                                alt="Preview">
                                        </div>
                                        <div class="slot-actions">
                                            <button type="button" class="btn-slot-action move-right"
                                                onclick="moveSlotImage(1, 1)" title="Move Right"><i
                                                    class="bi bi-arrow-right"></i></button>
                                            <button type="button" class="btn-slot-action remove"
                                                onclick="removeSlotImage(1)" title="Remove"><i
                                                    class="bi bi-x"></i></button>
                                        </div>
                                        <span class="slot-label">Main Image</span>
                                        <input type="file" name="productImage" id="prodImage" class="d-none"
                                            accept="image/*" onchange="previewImage(this, 1)">
                                        <input type="hidden" id="currentImagePath" name="currentImage" value="">
                                    </div>

                                    <!-- Secondary Slots -->
                                    <div class="secondary-slots">
                                        <?php for ($i = 2; $i <= 4; $i++): ?>
                                            <div class="image-slot" id="slot-<?php echo $i; ?>">
                                                <div class="image-preview-container" id="preview-<?php echo $i; ?>"
                                                    onclick="triggerSlotUpload(<?php echo $i; ?>)">
                                                    <img id="imagePreview<?php echo $i; ?>"
                                                        src="../../JDE_USER/assets/img/logojd.png" alt="Preview">
                                                </div>
                                                <div class="slot-actions">
                                                    <button type="button" class="btn-slot-action move-left"
                                                        onclick="moveSlotImage(<?php echo $i; ?>, -1)" title="Move Left"><i
                                                            class="bi bi-arrow-left"></i></button>
                                                    <?php if ($i < 4): ?>
                                                        <button type="button" class="btn-slot-action move-right"
                                                            onclick="moveSlotImage(<?php echo $i; ?>, 1)" title="Move Right"><i
                                                                class="bi bi-arrow-right"></i></button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn-slot-action remove"
                                                        onclick="removeSlotImage(<?php echo $i; ?>)" title="Remove"><i
                                                            class="bi bi-x"></i></button>
                                                </div>
                                                <input type="file" name="productImage<?php echo $i; ?>"
                                                    id="prodImage<?php echo $i; ?>" class="d-none" accept="image/*"
                                                    onchange="previewImage(this, <?php echo $i; ?>)">
                                                <input type="hidden" id="currentImagePath<?php echo $i; ?>"
                                                    name="currentImage<?php echo $i; ?>" value="">
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="image-help-text">Click slots to change, or use Bulk Upload to select multiple.
                                </p>
                            </div>
                        </div>

                        <!-- Right: Product Details Grid -->
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Product Name *</label>
                                <input type="text" name="productName" id="prodName" required>
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="categoryID" id="prodCat" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['categoryID']; ?>">
                                            <?php echo $cat['categoryName']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Gender *</label>
                                <select name="gender" id="prodGender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Men">Men</option>
                                    <option value="Women">Women</option>
                                    <option value="Unisex">Unisex</option>
                                </select>
                            </div>


                            <div class="form-group full">
                                <label class="fw-bold mb-2">Size & Inventory Management *</label>
                                <div class="size-inventory-grid">
                                    <?php
                                    $sizeLabels = ['3XS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL (2XL)', '3XL - 6XL', 'One Size'];
                                    foreach ($sizeLabels as $label):
                                        $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $label);
                                        ?>
                                        <div class="size-item" id="size-item-<?php echo $cleanId; ?>">
                                            <div class="size-header">
                                                <input type="checkbox" class="size-checkbox"
                                                    id="check-<?php echo $cleanId; ?>" data-size="<?php echo $label; ?>"
                                                    onchange="toggleSizeInput('<?php echo $cleanId; ?>')">
                                                <label class="size-label"
                                                    for="check-<?php echo $cleanId; ?>"><?php echo $label; ?></label>
                                            </div>
                                            <div class="size-qty-wrapper">
                                                <div class="input-group-row mb-2">
                                                    <div class="input-col">
                                                        <label class="small text-muted mb-0">Qty</label>
                                                        <input type="text" class="size-qty-input"
                                                            id="qty-<?php echo $cleanId; ?>" value="0" disabled
                                                            placeholder="Qty"
                                                            oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateSizeStocks()">
                                                    </div>
                                                    <div class="input-col">
                                                        <label class="small text-muted mb-0">Unit Price (₱)</label>
                                                        <input type="number" class="size-price-input"
                                                            id="price-<?php echo $cleanId; ?>" min="0" step="0.01" disabled
                                                            placeholder="Unit Price" oninput="updateSizeStocks()">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="sizeStocks" id="sizeStocksJson" value="{}">
                                <input type="hidden" name="stocks" id="prodStock" value="0">
                                <input type="hidden" name="size" id="prodSize" value="Various">
                                <p class="image-help-text mt-2">Check the sizes available for this product and enter
                                    their respective quantities.</p>
                            </div>

                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" id="prodType">
                                    <option value="">None</option>
                                    <option value="polo">Polo </option>
                                    <option value="trouser">Trousers</option>
                                    <option value="blouse">Blouses</option>
                                    <option value="skirt">Skirts</option>
                                    <option value="pants">Pants</option>
                                    <option value="suit">Suits</option>
                                    <option value="t-shirt">T-shirts</option>
                                </select>


                            </div>
                            <div class="form-group">
                                <label>Fit Type</label>
                                <select name="fitType" id="prodFitType">
                                    <option value="">None</option>
                                    <option value="regular">Regular Fit</option>
                                    <option value="loose">Loose</option>
                                    <option value="oversized">Oversized</option>
                                    <option value="slim">Slim Fit</option>
                                    <option value="skinny">Skinny</option>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Description</label>
                                <textarea name="description" id="prodDesc" rows="3"></textarea>
                            </div>

                            <!-- DYNAMIC SIZE GUIDE SECTION -->
                            <div class="form-group full mt-4" id="sizeGuideSection" style="display: none;">
                                <label class="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-rulers text-accent"></i> Size Guide Measurements (Inches)
                                </label>
                                <div id="sizeGuideCardsContainer" class="size-guide-cards-grid">
                                    <?php foreach ($sizeLabels as $label): 
                                        $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $label);
                                    ?>
                                        <div class="size-guide-card" id="measurements-card-<?php echo $cleanId; ?>" style="display: none;">
                                            <div class="card-header">
                                                <span class="size-name"><?php echo $label; ?></span>
                                                <span class="unit-tag">Inches</span>
                                            </div>
                                            <div class="measurement-grid">
                                                <div class="m-field" data-m-type="upper full">
                                                    <label>Neck</label>
                                                    <input type="number" class="size-neck-input" id="neck-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="upper full">
                                                    <label>Shoulder</label>
                                                    <input type="number" class="size-shoulder-input" id="shoulder-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="upper full">
                                                    <label>Chest (Bust)</label>
                                                    <input type="number" class="size-chest-input" id="chest-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="lower full">
                                                    <label>Waist</label>
                                                    <input type="number" class="size-waist-input" id="waist-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="lower full">
                                                    <label>Hips</label>
                                                    <input type="number" class="size-hips-input" id="hips-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="upper full">
                                                    <label>Sleeve</label>
                                                    <input type="number" class="size-sleeve-input" id="sleeve-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="upper full">
                                                    <label>Shirt Length</label>
                                                    <input type="number" class="size-length-input" id="length-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="lower full">
                                                    <label>Pants Length</label>
                                                    <input type="number" class="size-pantsLength-input" id="pantsLength-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="lower full">
                                                    <label>Thigh</label>
                                                    <input type="number" class="size-thigh-input" id="thigh-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                                <div class="m-field" data-m-type="lower full">
                                                    <label>Crotch</label>
                                                    <input type="number" class="size-crotch-input" id="crotch-<?php echo $cleanId; ?>" step="0.1" placeholder="0.0" disabled oninput="updateSizeStocks()">
                                                </div>
                                            </div>
                                        </div>


                                    <?php endforeach; ?>

                                </div>
                                <p class="image-help-text mt-3">Provide precise measurements for each selected size to help customers find their perfect fit.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-save-product">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Product Details Modal (Read-only) -->
    <div id="viewProductModal" class="modal-overlay">
        <div class="modal landscape detail-view">
            <div class="modal-header">
                <h3>Product Details</h3>
                <button class="modal-close" onclick="closeViewProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-landscape-content">
                    <!-- Left side: Image and Badge Info -->
                    <div class="modal-landscape-image">
                        <div class="product-gallery-container">
                            <div class="gallery-thumbnails" id="galleryThumbs">
                                <!-- Dynamic thumbs go here -->
                            </div>
                            <div class="detail-image-wrapper">
                                <img id="detailProdImage" src="../../JDE_USER/assets/img/logojd.png" alt="Product">
                            </div>
                        </div>
                        <div class="detail-badge-group">
                            <div class="info-group">
                                <label>Product ID</label>
                                <p id="detailProdID" class="text-accent fw-bold"></p>
                            </div>
                            <div class="info-group">
                                <label>Created By</label>
                                <p id="detailCreatedBy"></p>
                            </div>
                            <div class="info-group">
                                <label>Updated By</label>
                                <p id="detailUpdatedBy"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Right side: Info Grid -->
                    <div class="detail-info-grid">
                        <div class="info-group full">
                            <label>Product Name</label>
                            <h2 id="detailProdName"></h2>
                        </div>

                        <div class="info-group">
                            <label>Category</label>
                            <p id="detailProdCat"></p>
                        </div>
                        <div class="info-group">
                            <label>Gender</label>
                            <p id="detailProdGender"></p>
                        </div>

                        <div class="info-group">
                            <label>Price</label>
                            <p id="detailProdPrice" class="price-text text-accent fw-bold"></p>
                        </div>

                        <div class="info-group">
                            <label>Stocks</label>
                            <p id="detailProdStock"></p>
                        </div>
                        <div class="info-group">
                            <label>Size</label>
                            <p id="detailProdSize"></p>
                        </div>


                        <div class="info-group">
                            <label>Type</label>
                            <p id="detailProdType"></p>
                        </div>

                        <div class="info-group full">
                            <label>Fit Type</label>
                            <p id="detailProdFitType"></p>
                        </div>

                        <div class="info-group full mt-3">
                            <label>Description</label>
                            <p id="detailProdDesc" class="description-text"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="editFromViewBtn" class="btn-save-product">Edit Product</button>
            </div>
        </div>
    </div>





    <!-- Restock Modal -->
    <div id="restockModal" class="modal-overlay">
        <div class="modal restock-theme" style="max-width: 500px;">
            <div class="modal-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h3>Restock Inventory</h3>
                        <p>Adjust stock levels for specific sizes</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeRestockModal()">&times;</button>
            </div>
            <form id="restockForm">
                <input type="hidden" id="restockProdID" name="productID">
                <input type="hidden" name="action" value="restock">
                <div class="modal-body p-0">
                    <!-- Product Preview Card -->
                    <div class="restock-product-card">
                        <div class="product-thumb">
                            <img id="restockProdImg" src="../../JDE_USER/assets/img/logojd.png" alt="Product">
                        </div>
                        <div class="product-info">
                            <h4 id="restockProdName">Product Name</h4>
                            <div id="restockCurrentBadge" class="stock-badge in-stock">
                                <i class="bi bi-check-circle-fill"></i> <span id="restockCurrentQty">0</span> In Stock
                            </div>
                        </div>
                    </div>

                    <div class="restock-controls">
                        <div class="form-group" id="restockSizeGroup">
                            <label>Target Size</label>
                            <div class="custom-select-wrapper">
                                <i class="bi bi-rulers"></i>
                                <select name="size" id="restockSize" required>
                                    <!-- Dynamic sizes -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Restock Amount</label>
                            <div class="quantity-input-wrapper">
                                <div class="qty-icon">
                                    <i class="bi bi-plus-lg"></i>
                                </div>
                                <input type="text" name="quantity" id="restockQty" required
                                    placeholder="Enter quantity to add"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <span class="qty-unit">PCS</span>
                            </div>
                            <p class="image-help-text text-start">Quantity will be added to existing size stock.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer restock-footer">
                    <button type="submit" class="btn btn-save-product">
                        <i class="bi bi-check2-circle"></i> Confirm Restock
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="modal-overlay">
        <div class="confirm-modal-content"
            style="max-width: 450px; border-radius: 24px; padding: 0; overflow: hidden; width: 100%;">
            <div
                style="background: #f8fafc; padding: 22px 28px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="confirm-modal-icon"
                        style="width: 40px; height: 40px; flex-shrink: 0; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-exclamation-triangle" style="color:#f59e0b; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h4 id="confirmTitle"
                            style="margin: 0; color: var(--primary); font-size: 1.05rem; font-weight: 800; letter-spacing: -0.3px;">
                            Confirm Action</h4>
                        <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.75rem; font-weight: 500;">Please
                            confirm your action below</p>
                    </div>
                </div>
            </div>
            <div style="padding: 24px 28px; background: #fff;">
                <p id="confirmMessage" style="margin: 0; color: #475569; font-size: 0.9rem; line-height: 1.65;"></p>
            </div>
            <div class="confirm-modal-footer"
                style="padding: 16px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 10px;">
                <button id="confirmCancelBtn" class="btn-confirm-cancel"
                    style="flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; transition:all 0.2s; background:#f1f5f9; color:#475569;">CANCEL</button>
                <button id="confirmOkBtn" class="btn-confirm-ok"
                    style="flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; transition:all 0.2s; background:var(--primary); color:var(--accent);">YES,
                    PROCEED</button>
            </div>
        </div>
    </div>



    <?php include __DIR__ . '/fragments/scripts.php'; ?>

    <script src="../js/products.js?v=<?= time(); ?>"></script>
</div> <!-- main-content -->
</div> <!-- admin-layout -->
</body>

</html>