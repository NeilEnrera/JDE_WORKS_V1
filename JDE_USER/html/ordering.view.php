<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Details - JDE Works of Our Hands</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>

<body>
    <?php
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/product.css">
    <link rel="stylesheet" href="../css/ordering.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="../css/reviews.css?v=<?php echo time(); ?>" />

    <script>
        window.currentProductID = <?php echo json_encode($currentProduct['id']); ?>;
        window.currentBasePrice = <?php echo (float) $currentProduct['price']; ?>;
        window.currentSizeStocks = <?php echo ($currentProduct['sizeStocks'] ?: '{}'); ?>;
    </script>

    <!-- Page Title -->
    <div class="page-title">
        <h2>Product Details</h2>
        <p>Explore and customize your perfect uniform</p>
    </div>

    <!-- Product Box -->
    <div class="product-box">
        <!-- Product Image with optional gallery thumbnails -->
        <div class="product-image ordering-image-panel">
            <?php
            $extraImages = array_values(array_filter([
                $currentProduct['image2'] ?? null,
                $currentProduct['image3'] ?? null,
                $currentProduct['image4'] ?? null
            ]));
            if (!empty($extraImages)):
                ?>
                <div class="ordering-thumbnails-vertical">
                    <div class="thumb-wrapper active"
                        onclick="setMainImage(0, '<?php echo htmlspecialchars($currentProduct['image']); ?>'); document.querySelectorAll('.thumb-wrapper').forEach(t=>t.classList.remove('active')); this.classList.add('active');">
                        <img src="<?php echo htmlspecialchars($currentProduct['image']); ?>" class="order-thumb" />
                    </div>
                    <?php $idx = 1;
                    foreach ($extraImages as $extraImg): ?>
                        <div class="thumb-wrapper"
                            onclick="setMainImage(<?php echo $idx; ?>, '<?php echo htmlspecialchars($extraImg); ?>'); document.querySelectorAll('.thumb-wrapper').forEach(t=>t.classList.remove('active')); this.classList.add('active');">
                            <img src="<?php echo htmlspecialchars($extraImg); ?>" class="order-thumb" />
                        </div>
                        <?php $idx++; endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="main-image-container">
                <img src="<?php echo htmlspecialchars($currentProduct['image']); ?>" alt="Product Image"
                    id="productImage" onclick="openLightbox()" style="cursor: zoom-in;" title="Click to enlarge" />
            </div>
        </div>

        <!-- Product Details -->
        <div class="product-details">
            <h1 id="productName">
                <?php echo htmlspecialchars($currentProduct['name']); ?>
            </h1>

            <div class="info-item">
                <label>Category:</label>
                <span id="productCategory">
                    <?php echo htmlspecialchars($currentProduct['category']); ?>
                </span>
            </div>

            <div class="info-item">
                <label>Description:</label>
                <p id="productDescription">
                    <?php echo htmlspecialchars($currentProduct['description']); ?>
                </p>
            </div>

            <div class="price-section">
                <span id="productPrice" class="price">₱
                    <?php echo number_format($currentProduct['price'], 2); ?>
                </span>
            </div>

            <div class="stock-section">
                <label>Available Stock:</label>
                <span id="productStock" class="stock">In Stock</span>
            </div>

            <!-- Size Selection -->
            <div class="size-selection">
                <label class="mb-2">Size:</label>
                <div class="sizes mb-3" id="sizeButtonsContainer">
                    <?php
                    $sizeStocks = json_decode($currentProduct['sizeStocks'] ?: '{}', true);
                    if (empty($sizeStocks)) {
                        echo '<span class="text-danger fw-bold">Out of Stock</span>';
                    } else {
                        foreach ($sizeStocks as $label => $entry) {
                            // Support both old format (int qty) and new format ({qty, price})
                            $qty = is_array($entry) ? ($entry['qty'] ?? 0) : $entry;
                            $price = is_array($entry) ? ($entry['price'] ?? 0) : 0;
                            $disabled = ''; // Allow selecting out of stock for pre-order
                            $active = '';
                            $priceAttr = $price > 0 ? 'data-price="' . $price . '"' : '';
                            $outOfStockAttr = $qty <= 0 ? 'title="Out of stock — use Pre-Order to reserve this size"' : '';
                            echo '<button type="button" class="size-btn ' . $active . ' ' . ($qty <= 0 ? 'out-of-stock-preorder' : '') . '" ' . $disabled . ' ' . $priceAttr . ' ' . $outOfStockAttr . ' onclick="selectSize(\'' . $label . '\', this)">' . $label . '</button>';
                        }
                    }
                    ?>
                </div>
                <button type="button" class="btn-size-guide-link" onclick="openSizeGuide()">
                    <span class="size-guide-icon-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12h20"></path>
                            <path d="M5 12v-4"></path>
                            <path d="M9 12v-4"></path>
                            <path d="M13 12v-4"></path>
                            <path d="M17 12v-4"></path>
                            <path d="M21 12v-4"></path>
                        </svg>
                    </span>
                    Size Guide
                </button>
            </div>

            <div class="quantity-section">
                <label for="quantity"><i class="bi bi-arrow-up-down"></i> Quantity:</label>
                <div class="quantity-controls">
                    <button type="button" class="minus" onclick="decreaseQuantity()">−</button>
                    <input type="number" id="quantity" value="1" min="1" max="999" readonly />
                    <button type="button" class="plus" onclick="increaseQuantity()">+</button>
                </div>
            </div>

            <div class="action-buttons">
                <a href="customization.php?product=<?php echo urlencode($productId); ?>" class="btn customize">
                    <i class="bi bi-palette"></i> Customize
                </a>
                <div class="cart-btn-group">
                    <button type="button" class="btn add-to-cart" onclick="addToCart(false)">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                    <button type="button" class="btn btn-outline-gold"
                        onclick="addToCart(true)">
                        <i class="bi bi-calendar-event"></i> Pre-Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Size Guide Side Drawer Overlay -->
    <div id="sizeGuideDrawerOverlay" class="drawer-overlay" onclick="closeSizeGuide()"></div>

    <!-- Size Guide Side Drawer -->
    <div id="sizeGuideDrawer" class="side-drawer sg-drawer">
        <!-- Drawer Header -->
        <div class="sg-drawer-header">
            <div class="sg-header-inner">
                <div>
                    <p class="sg-header-eyebrow">JDE Works</p>
                    <h3 class="sg-header-title">Size Guide</h3>
                </div>
                <button class="sg-close-btn" onclick="closeSizeGuide()" aria-label="Close size guide">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Selected Size Context Banner -->
            <div id="sgSelectedBanner" class="sg-selected-banner" style="display:none;">
                <i class="bi bi-check-circle-fill"></i>
                <span id="sgSelectedBannerText">Showing: Medium</span>
                <button class="sg-banner-clear" onclick="clearSizeFilter()" title="Show all sizes">Show All</button>
            </div>
        </div>

        <!-- Drawer Body -->
        <div class="sg-drawer-body">

            <!-- Tab Navigation -->
            <div class="sg-tabs-wrapper">
                <div class="sg-tabs" role="tablist">
                    <button class="sg-tab active" role="tab" onclick="switchSizeTab(this, 'product-measurements')"
                        aria-selected="true">
                        <i class="bi bi-rulers"></i> Product Fit
                    </button>
                    <button class="sg-tab" role="tab" onclick="switchSizeTab(this, 'body-measurements')"
                        aria-selected="false">
                        <i class="bi bi-person-bounding-box"></i> Body Guide
                    </button>
                </div>
            </div>

            <!-- Product Measurements Panel -->
            <div id="product-measurements" class="size-tab-content active" role="tabpanel">

                <!-- Selected Size Metrics Cards (shown when a size is selected + dynamic data exists) -->
                <div id="sg-metric-cards" class="sg-metric-cards" style="display:none;">
                    <div class="sg-metric-card"><span class="sg-metric-label">Chest</span><span class="sg-metric-value"
                            id="sg-mc-chest">—</span></div>
                    <div class="sg-metric-card"><span class="sg-metric-label">Waist</span><span class="sg-metric-value"
                            id="sg-mc-waist">—</span></div>
                    <div class="sg-metric-card"><span class="sg-metric-label">Length</span><span class="sg-metric-value"
                            id="sg-mc-length">—</span></div>
                    <div class="sg-metric-card"><span class="sg-metric-label">Shoulder</span><span
                            class="sg-metric-value" id="sg-mc-shoulder">—</span></div>
                    <div class="sg-metric-card"><span class="sg-metric-label">Sleeve</span><span class="sg-metric-value"
                            id="sg-mc-sleeve">—</span></div>
                </div>

                <!-- Full Table (rendered dynamically or static below) -->
                <div class="sg-table-wrapper">
                    <?php if ($sizeChartType === 'polo'): ?>
                        <table class="sg-table size-table">
                            <thead>
                                <tr>
                                    <th>SIZE</th>
                                    <th>Width</th>
                                    <th>Length</th>
                                    <th>Shoulder</th>
                                    <th>Arm</th>
                                    <th>Arm Hole</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-size="S">
                                    <td class="sg-size-label">S</td>
                                    <td>30–31</td>
                                    <td>24</td>
                                    <td>15–16</td>
                                    <td>7–7.5</td>
                                    <td>16–17</td>
                                </tr>
                                <tr data-size="M">
                                    <td class="sg-size-label">M</td>
                                    <td>32–33</td>
                                    <td>25</td>
                                    <td>17</td>
                                    <td>8</td>
                                    <td>18</td>
                                </tr>
                                <tr data-size="L">
                                    <td class="sg-size-label">L</td>
                                    <td>34–35</td>
                                    <td>26</td>
                                    <td>18</td>
                                    <td>9</td>
                                    <td>19</td>
                                </tr>
                                <tr data-size="XL">
                                    <td class="sg-size-label">XL</td>
                                    <td>36–37</td>
                                    <td>27</td>
                                    <td>19</td>
                                    <td>9.5</td>
                                    <td>20</td>
                                </tr>
                                <tr data-size="XXL (2XL)">
                                    <td class="sg-size-label">2XL</td>
                                    <td>38–40</td>
                                    <td>28</td>
                                    <td>20</td>
                                    <td>10</td>
                                    <td>21</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table class="sg-table size-table">
                            <thead>
                                <tr>
                                    <th>SIZE</th>
                                    <th>Waist</th>
                                    <th>Length</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-size="S">
                                    <td class="sg-size-label">S</td>
                                    <td>29–30</td>
                                    <td>39</td>
                                </tr>
                                <tr data-size="M">
                                    <td class="sg-size-label">M</td>
                                    <td>31–32</td>
                                    <td>39</td>
                                </tr>
                                <tr data-size="L">
                                    <td class="sg-size-label">L</td>
                                    <td>33–34</td>
                                    <td>39</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <p class="sg-disclaimer">* Product-specific measurements. Values in inches. May vary ±1 in.</p>
            </div>

            <!-- Body Measurements Panel -->
            <div id="body-measurements" class="size-tab-content" role="tabpanel">
                <div class="sg-table-wrapper">
                    <table class="sg-table size-table">
                        <thead>
                            <tr>
                                <th>SIZE</th>
                                <th>Shoulder</th>
                                <th>Bust</th>
                                <th>Waist</th>
                                <th>Hips</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-size="S">
                                <td class="sg-size-label">S</td>
                                <td>16.5</td>
                                <td>34–36</td>
                                <td>28–30</td>
                                <td>34–36</td>
                            </tr>
                            <tr data-size="M">
                                <td class="sg-size-label">M</td>
                                <td>17.5</td>
                                <td>38–40</td>
                                <td>32–34</td>
                                <td>38–40</td>
                            </tr>
                            <tr data-size="L">
                                <td class="sg-size-label">L</td>
                                <td>18.5</td>
                                <td>42–44</td>
                                <td>36–38</td>
                                <td>42–44</td>
                            </tr>
                            <tr data-size="XL">
                                <td class="sg-size-label">XL</td>
                                <td>19.5</td>
                                <td>46–48</td>
                                <td>40–42</td>
                                <td>46–48</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="sg-disclaimer">* These are body measurements, not garment measurements. Measure over underwear
                    only.</p>
            </div>

            <!-- How to Measure Section -->
            <div class="sg-how-to-section">
                <div class="sg-how-to-header">
                    <i class="bi bi-rulers sg-how-to-icon"></i>
                    <h5 class="sg-how-to-title">How to Measure</h5>
                </div>
                <div class="sg-how-to-image-wrapper">
                    <img src="../assets/img/measurrement.png" alt="How to Measure Diagram" class="sg-how-to-image">
                </div>
                <ol class="sg-measure-steps">
                    <li class="sg-measure-step">
                        <span class="sg-step-num">1</span>
                        <div><strong>Shoulder</strong> — Measure straight across from seam to seam at the back.</div>
                    </li>
                    <li class="sg-measure-step">
                        <span class="sg-step-num">2</span>
                        <div><strong>Bust / Chest</strong> — Wrap tape around the fullest part of the chest, under the
                            arms.</div>
                    </li>
                    <li class="sg-measure-step">
                        <span class="sg-step-num">3</span>
                        <div><strong>Length</strong> — From the highest shoulder point straight down to the hem.</div>
                    </li>
                    <li class="sg-measure-step">
                        <span class="sg-step-num">4</span>
                        <div><strong>Sleeve</strong> — From the shoulder seam to the cuff edge.</div>
                    </li>
                </ol>
            </div>

            <!-- Fit Feedback -->
            <div class="sg-fit-feedback">
                <div class="sg-fit-feedback-header">
                    <i class="bi bi-people-fill"></i>
                    <h5>Buyer Fit Feedback</h5>
                </div>
                <div class="sg-fit-bars">
                    <div class="sg-fit-bar-row">
                        <span class="sg-fit-bar-label">Runs Small</span>
                        <div class="sg-fit-bar-track">
                            <div class="sg-fit-bar-fill" style="width:2%"></div>
                        </div>
                        <span class="sg-fit-bar-pct">2%</span>
                    </div>
                    <div class="sg-fit-bar-row sg-fit-bar-row--highlight">
                        <span class="sg-fit-bar-label">True to Size</span>
                        <div class="sg-fit-bar-track">
                            <div class="sg-fit-bar-fill sg-fit-bar-fill--gold" style="width:94%"></div>
                        </div>
                        <span class="sg-fit-bar-pct sg-fit-bar-pct--bold">94%</span>
                    </div>
                    <div class="sg-fit-bar-row">
                        <span class="sg-fit-bar-label">Runs Large</span>
                        <div class="sg-fit-bar-track">
                            <div class="sg-fit-bar-fill" style="width:4%"></div>
                        </div>
                        <span class="sg-fit-bar-pct">4%</span>
                    </div>
                </div>
                <p class="sg-fit-source">Based on verified buyer reviews.</p>
            </div>

        </div><!-- /sg-drawer-body -->
    </div><!-- /side-drawer -->

    <!-- Customization Modal -->
    <div id="customizationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCustomization()" aria-label="Close">&times;</span>
            <h3>Customize Measurements</h3>

            <div class="customization-layout">
                <div class="customization-form">
                    <h4 class="customization-subtitle">Uniform for Women</h4>
                    <label for="neck">Neck (cm)</label>
                    <div class="input-box"><input type="number" id="neck" name="neck" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="shoulder">Shoulder (cm)</label>
                    <div class="input-box"><input type="number" id="shoulder" name="shoulder"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="armhole">Armhole (cm)</label>
                    <div class="input-box"><input type="number" id="armhole" name="armhole"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="bicep">Bicep (cm)</label>
                    <div class="input-box"><input type="number" id="bicep" name="bicep" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="wrist">Wrist (cm)</label>
                    <div class="input-box"><input type="number" id="wrist" name="wrist" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="sleeveLength">Sleeve Length (cm)</label>
                    <div class="input-box"><input type="number" id="sleeveLength" name="sleeveLength"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="chest">Chest (cm)</label>
                    <div class="input-box"><input type="number" id="chest" name="chest" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="waist">Waist (cm)</label>
                    <div class="input-box"><input type="number" id="waist" name="waist" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="hips">Hips (cm)</label>
                    <div class="input-box"><input type="number" id="hips" name="hips" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="shirtLength">Shirt Length (cm)</label>
                    <div class="input-box"><input type="number" id="shirtLength" name="shirtLength"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="crotch">Crotch (cm)</label>
                    <div class="input-box"><input type="number" id="crotch" name="crotch"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="thigh">Thigh (cm)</label>
                    <div class="input-box"><input type="number" id="thigh" name="thigh" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="knee">Knee (cm)</label>
                    <div class="input-box"><input type="number" id="knee" name="knee" placeholder="Enter measurement"
                            min="0" step="0.1" /></div>
                    <label for="legOpening">Leg Opening (cm)</label>
                    <div class="input-box"><input type="number" id="legOpening" name="legOpening"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="pantsLength">Pants Length (cm)</label>
                    <div class="input-box"><input type="number" id="pantsLength" name="pantsLength"
                            placeholder="Enter measurement" min="0" step="0.1" /></div>
                    <label for="upload">Upload Sample Image</label>
                    <div class="input-box"><input type="file" id="upload" name="upload" accept="image/*" /></div>
                    <label for="notes">Additional Notes</label>
                    <textarea id="notes" name="notes" placeholder="Any special instructions..."></textarea>
                </div>

                <div class="customization-preview">
                    <div class="customization-preview-card">
                        <img src="../assets/img/unifrom.jpeg" alt="Uniform Preview"
                            class="customization-preview-image" />
                        <p class="preview-caption">Your School Uniform</p>
                        <p class="preview-caption-sub">Size Guide</p>
                    </div>
                    <div class="size-guide">
                        <h5>Size</h5>
                        <ul>
                            <li>ARMS</li>
                            <li>CHEST</li>
                            <li>WAIST</li>
                            <li>HIP</li>
                            <li>LENGTH</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="form-buttons">
                <button class="btn customize" type="button" onclick="bookAppointmentWithMeasurements()">Book
                    Appointment</button>
                <button class="btn add-to-cart" type="button" onclick="addToCartWithCustomization()">Add to
                    Cart</button>
            </div>
        </div>
    </div>
    .
    <!-- Product Reviews Section -->
    <section class="reviews-section container">
        <div class="reviews-header">
            <div class="reviews-title-group">
                <h2 class="reviews-title">Customer Reviews</h2>
                <p class="reviews-subtitle">What others are saying about this piece</p>
            </div>
        </div>

        <!-- Aggregate Review Summary -->
        <div id="aggregateReviewHeader" class="aggregate-review-header">
            <div class="rating-summary">
                <div class="avg-rating-box">
                    <span id="avgRatingNum" class="avg-rating-num">0.0</span>
                    <div id="avgRatingStars" class="avg-rating-stars"></div>
                    <a href="javascript:void(0)" class="review-policy" onclick="openReviewPolicy()">Review Policy <i
                            class="bi bi-question-circle"></i></a>
                </div>
                <div class="fit-summary">
                    <h6>Overall Fit:</h6>
                    <div class="fit-bars">
                        <div class="fit-bar-group">
                            <span class="fit-label">Small</span>
                            <div class="progress">
                                <div id="fitSmallBar" class="progress-bar" style="width: 0%"></div>
                            </div>
                            <span id="fitSmallPct" class="fit-pct">0%</span>
                        </div>
                        <div class="fit-bar-group">
                            <span class="fit-label">True to Size</span>
                            <div class="progress">
                                <div id="fitTrueBar" class="progress-bar" style="width: 0%"></div>
                            </div>
                            <span id="fitTruePct" class="fit-pct">0%</span>
                        </div>
                        <div class="fit-bar-group">
                            <span class="fit-label">Large</span>
                            <div class="progress">
                                <div id="fitLargeBar" class="progress-bar" style="width: 0%"></div>
                            </div>
                            <span id="fitLargePct" class="fit-pct">0%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Form (Hidden by default) -->
        <div id="reviewFormContainer" class="review-form-container" style="display: none;">
            <form id="productReviewForm" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">
                <div class="rating-input-group">
                    <label>Your Rating</label>
                    <div class="star-rating-input">
                        <input type="radio" id="star5" name="rating" value="5" /><label for="star5"
                            title="5 stars"></label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4"
                            title="4 stars"></label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3"
                            title="3 stars"></label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2"
                            title="2 stars"></label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1"
                            title="1 star"></label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label>How was the fit?</label>
                    <div class="fit-radio-group">
                        <input type="radio" id="fitSmall" name="fit" value="Small" required><label
                            for="fitSmall">Small</label>
                        <input type="radio" id="fitTrue" name="fit" value="True to Size" checked><label
                            for="fitTrue">True to Size</label>
                        <input type="radio" id="fitLarge" name="fit" value="Large"><label for="fitLarge">Large</label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="reviewComment">Your Experience</label>
                    <textarea id="reviewComment" name="comment" class="form-control" rows="4"
                        placeholder="Tell us more about the quality and fit..." required></textarea>
                </div>
                <div class="form-group mb-4">
                    <label for="reviewImage">Upload a Photo (Optional)</label>
                    <div class="upload-wrapper">
                        <input type="file" id="reviewImage" name="review_image" accept="image/*" class="form-control" />
                        <small class="text-muted">Show us how it looks! Max 2MB.</small>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="toggleReviewForm()">Cancel</button>
                    <button type="submit" class="btn btn-submit-review">Post Review</button>
                </div>
            </form>
        </div>

        <div id="reviewsList" class="reviews-list">
            <div class="loading-reviews">
                <div class="spinner-border text-gold" role="status">
                    <span class="visually-hidden">Loading reviews...</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="productLightbox" class="lightbox-modal" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightbox(event)">&times;</span>
        <button class="lightbox-nav prev" onclick="navigateLightbox(-1, event)">&#10094;</button>
        <div class="lightbox-content">
            <img id="lightboxImg" src="" alt="Fullscreen Product Image" />
        </div>
        <button class="lightbox-nav next" onclick="navigateLightbox(1, event)">&#10095;</button>
    </div>

    <!-- Lightbox Script -->
    <script>
        <?php
        $allLightboxImages = [$currentProduct['image']];
        if (!empty($extraImages)) {
            foreach ($extraImages as $img) {
                $allLightboxImages[] = $img;
            }
        }
        ?>
        const galleryImages = <?php echo json_encode($allLightboxImages); ?>;
        let currentLightboxIndex = 0;

        function openLightbox(index = null) {
            if (galleryImages.length === 0) return;
            if (index !== null) currentLightboxIndex = index;

            const lightboxImg = document.getElementById('lightboxImg');
            lightboxImg.src = galleryImages[currentLightboxIndex];
            document.getElementById('productLightbox').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(event) {
            if (event.target.id === 'productLightbox' || event.target.classList.contains('lightbox-close')) {
                document.getElementById('productLightbox').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function navigateLightbox(direction, event) {
            if (event) event.stopPropagation();
            currentLightboxIndex += direction;
            if (currentLightboxIndex < 0) currentLightboxIndex = galleryImages.length - 1;
            if (currentLightboxIndex >= galleryImages.length) currentLightboxIndex = 0;

            document.getElementById('lightboxImg').src = galleryImages[currentLightboxIndex];
            // Also sync the main image smoothly in the background
            syncMainImageAndThumbnails(currentLightboxIndex);
        }

        // Add Keyboard navigation
        document.addEventListener('keydown', function (event) {
            const lightbox = document.getElementById('productLightbox');
            if (lightbox.classList.contains('active')) {
                if (event.key === 'ArrowLeft') navigateLightbox(-1);
                if (event.key === 'ArrowRight') navigateLightbox(1);
                if (event.key === 'Escape') closeLightbox({ target: lightbox });
            }
        });

        function setMainImage(index, src) {
            currentLightboxIndex = index;
            document.getElementById('productImage').src = src;
        }

        function syncMainImageAndThumbnails(index) {
            document.getElementById('productImage').src = galleryImages[index];
            const thumbs = document.querySelectorAll('.thumb-wrapper');
            if (thumbs.length > 0 && thumbs[index]) {
                thumbs.forEach(t => t.classList.remove('active'));
                thumbs[index].classList.add('active');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>

    <!-- Review Policy Modal -->
    <div id="reviewPolicyModal" class="modal">
        <div class="modal-content review-policy-modal">
            <span class="close" onclick="closeReviewPolicy()">&times;</span>
            <div class="modal-header-styled">
                <i class="bi bi-shield-check"></i>
                <h3>Review Policy</h3>
            </div>
            <div class="modal-body">
                <p class="text-center mb-4 text-muted small">To ensure a helpful and safe community for all users, we
                    maintain a standard for all product reviews.</p>

                <div class="policy-item">
                    <i class="bi bi-person-check"></i>
                    <div>
                        <h5>Verified Purchases</h5>
                        <p>Only users who have actually purchased and received the product can leave a review. This
                            ensures all feedback is authentic.</p>
                    </div>
                </div>

                <div class="policy-item">
                    <i class="bi bi-hand-thumbs-up"></i>
                    <div>
                        <h5>Honest Feedback</h5>
                        <p>We encourage both positive and constructive feedback. Please be honest about your experience
                            with the quality, fit, and material.</p>
                    </div>
                </div>

                <div class="policy-item">
                    <i class="bi bi-image"></i>
                    <div>
                        <h5>Appropriate Media</h5>
                        <p>Any photos uploaded must be of the actual product. Avoid uploading personal, blurry, or
                            irrelevant images.</p>
                    </div>
                </div>

                <div class="policy-item">
                    <i class="bi bi-chat-dots"></i>
                    <div>
                        <h5>Respectful Language</h5>
                        <p>Reviews containing profanity, hate speech, or personal attacks will be removed immediately.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeReviewPolicy()">Got it</button>
            </div>
        </div>
    </div>

</body>

</html>

<!-- Page Specific Scripts -->
<script src="../js/ordering.js?v=<?php echo time(); ?>"></script>
<script src="../js/wishlist.js?v=<?php echo time(); ?>"></script>
<script src="../js/product.js?v=<?php echo time(); ?>"></script>
<script src="../js/reviews.js?v=<?php echo time(); ?>"></script>