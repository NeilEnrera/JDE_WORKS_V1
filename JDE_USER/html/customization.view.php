<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customize Measurements - JDE Works of Our Hands</title>

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
    $activePage = 'products';
    include '../backend/navbar.php';
    ?>

    <!-- Page Specific Assets -->
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ordering.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="../css/customization.css?v=<?php echo time(); ?>" />

    <main class="customization-page">
        <div class="customization-header">
            <h1 class="customization-title">Customize Measurements</h1>
        </div>

        <div class="customization-shell">
            <div class="customization-layout">
                <!-- Left: form -->
                <div class="customization-form">
                    <div class="customization-form-top">
                        <div class="customization-form-kicker">
                            <?php
                            $cat = $currentProduct['category'] ?? '';
                            $isUpper = (stripos($cat, 'Upper') !== false) || (stripos($cat, 'Full Set') !== false);
                            $isLower = (stripos($cat, 'Lower') !== false) || (stripos($cat, 'Full Set') !== false);

                            if (stripos($cat, 'Men') !== false)
                                echo 'Uniform for Men';
                            elseif (stripos($cat, 'Women') !== false)
                                echo 'Uniform for Women';
                            else
                                echo 'Uniform';
                            ?>
                        </div>
                        <div class="customization-form-product">
                            <?php echo htmlspecialchars($currentProduct['name']); ?>
                        </div>
                    </div>

                    <div class="measurement-fields">
                        <?php if ($isUpper): ?>
                            <div class="measurement-grid">
                                <div class="m-unit">
                                    <label for="neck">Neck (in) <span class="text-danger">*</span></label>
                                    <div class="input-box"><input type="number" id="neck" name="neck"
                                            placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                </div>
                                <div class="m-unit">
                                    <label for="shoulder">Shoulder (in) <span class="text-danger">*</span></label>
                                    <div class="input-box"><input type="number" id="shoulder" name="shoulder"
                                            placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                </div>
                            </div>

                            <label for="chest">Chest (in) <span class="text-danger">*</span></label>
                            <div class="input-box"><input type="number" id="chest" name="chest"
                                    placeholder="Enter Measurement" min="0" step="0.1" required></div>
                        <?php endif; ?>

                        <?php if ($isUpper || $isLower): ?>
                            <div class="measurement-grid">
                                <div class="m-unit">
                                    <label for="waist">Waist (in) <span class="text-danger">*</span></label>
                                    <div class="input-box"><input type="number" id="waist" name="waist"
                                            placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                </div>
                                <?php if ($isLower): ?>
                                    <div class="m-unit">
                                        <label for="hips">Hips (in) <span class="text-danger">*</span></label>
                                        <div class="input-box"><input type="number" id="hips" name="hips"
                                                placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($isUpper || $isLower): ?>
                            <div class="measurement-grid">
                                <?php if ($isUpper): ?>
                                    <div class="m-unit">
                                        <label for="sleeve">Sleeve (in) <span class="text-danger">*</span></label>
                                        <div class="input-box"><input type="number" id="sleeve" name="sleeve"
                                                placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($isLower): ?>
                                    <div class="m-unit">
                                        <label for="pants-length">Pants Length (in) <span class="text-danger">*</span></label>
                                        <div class="input-box"><input type="number" id="pants-length" name="pants-length"
                                                placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($isLower): ?>
                            <div class="measurement-grid">
                                <div class="m-unit">
                                    <label for="thigh">Thigh (in) <span class="text-danger">*</span></label>
                                    <div class="input-box"><input type="number" id="thigh" name="thigh"
                                            placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                </div>
                                <div class="m-unit">
                                    <label for="crotch">Crotch (in) <span class="text-danger">*</span></label>
                                    <div class="input-box"><input type="number" id="crotch" name="crotch"
                                            placeholder="Enter Measurement" min="0" step="0.1" required></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" placeholder="Type here..."></textarea>

                    <div class="form-buttons">
                        <a href="appointment.php" class="btn btn-appointment">Book An Appointment</a>
                        <button class="btn add-to-cart" type="button" onclick="saveCustomization()">Save</button>
                    </div>
                </div>

                <!-- Middle + Right -->
                <div class="customization-preview">
                    <div class="customization-preview-card">
                        <img id="product-preview" src="<?php echo $currentProduct['image']; ?>"
                            alt="<?php echo $currentProduct['name']; ?>" class="customization-preview-image">
                    </div>
                    <p class="preview-caption"><?php echo htmlspecialchars($currentProduct['name']); ?></p>

                    <div class="customization-summary-card mt-3">
                        <div class="summary-item">
                            <span class="summary-label">Suggested Size:</span>
                            <span class="summary-value" id="suggested-size">--</span>
                        </div>
                        <div class="summary-item" id="base-price-row" style="display: none;">
                            <span class="summary-label">Base Price:</span>
                            <span class="summary-value" id="base-price">--</span>
                        </div>
                        <div class="summary-item" id="plus-size-surcharge-row" style="display: none;">
                            <span class="summary-label">Plus Size Surcharge:</span>
                            <span class="summary-value" id="plus-size-surcharge">--</span>
                        </div>
                        <div class="summary-item" id="custom-fee-row" style="display: none;">
                            <span class="summary-label">Customization Fee:</span>
                            <span class="summary-value">+₱100</span>
                        </div>
                        <hr class="summary-divider" id="summary-divider" style="display: none;">
                        <div class="summary-item total" id="total-price-row" style="display: none;">
                            <span class="summary-label">Total Price:</span>
                            <span class="summary-value" id="total-price">--</span>
                        </div>
                        <div id="summary-placeholder" class="text-center text-muted py-2 small">
                            Enter measurements to see calculation
                        </div>
                        <div id="surcharge-note" class="alert alert-info py-2 px-3 mt-2 small" style="display: none; border-left: 4px solid var(--primary-gold);"></div>
                        <div id="stock-note" class="alert alert-warning py-2 px-3 mt-2 small" style="display: none; border-left: 4px solid #ffc107;"></div>
                    </div>
                </div>

                <div class="customization-side">
                    <div class="size-guide">
                        <button type="button" class="btn-link p-0 text-decoration-none fw-bold"
                            style="color: var(--primary-gold); border: none; background: transparent; display: flex; align-items: center; gap: 0.5rem; font-size: 1.5rem;"
                            onclick="openSizeGuide()">
                            <i class="bi bi-info-circle" style="font-size: 1.6rem;"></i> Size Guide
                        </button>
                        <ul>
                            <li>NECK</li>
                            <li>SHOULDER</li>
                            <li>CHEST</li>
                            <li>WAIST</li>
                            <li>HIP</li>
                            <li>LENGTH</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        window.__CUSTOMIZATION_PRODUCT__ = <?php echo json_encode([
            'id' => $currentProduct['id'],
            'slug' => $productId,
            'name' => $currentProduct['name'],
            'category' => $currentProduct['category'],
            'price' => $currentProduct['price'],
            'image' => $currentProduct['image'],
            'sizeStocks' => json_decode($currentProduct['sizeStocks'], true),
            'isUpper' => $isUpper,
            'isLower' => $isLower
        ]); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
</body>

</html>

<!-- Page Specific Scripts -->
<script src="../js/customization.js?v=<?php echo time(); ?>"></script>

<!-- Size Guide Side Drawer -->
<div class="drawer-overlay" id="sizeGuideOverlay" onclick="closeSizeGuide()"></div>
<div id="sizeGuideModal" class="side-drawer">
    <div class="drawer-header">
        <h3>How to Measure</h3>
        <button class="drawer-close" onclick="closeSizeGuide()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="drawer-body">
        <!-- Appointment Guidance Card -->
        <div class="appointment-helper-card">
            <div class="appointment-helper-content">
                <h5><i class="bi bi-question-circle"></i> Unsure how to measure?</h5>
                <p>Don't worry! You can book an appointment with our professional tailors to get your exact
                    measurements taken in person.</p>
            </div>
        </div>

        <div class="how-to-measure-section mb-0">
            <p class="text-muted small mb-4 text-center">Follow these instructions to get accurate measurements for
                your custom uniform.</p>

            <h5 class="fw-bold mb-3 border-bottom pb-2">Upper Body</h5>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Neck.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Neck Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Neck:</strong> Measure around the base of your neck,
                        inserting a finger or two for comfort.</div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Shoulder.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Shoulder Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Shoulder:</strong> Measure from the edge of one shoulder
                        socket to the other across the back.</div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Chest.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Chest Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Chest:</strong> Measure around the fullest part of your
                        chest, keeping the tape horizontal.</div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Waist.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Waist Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Waist:</strong> Measure around your natural waistline,
                        usually aligned with your belly button.</div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Hips.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Hips Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Hips:</strong> Measure around the fullest part of your hips.
                    </div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Sleeve Length.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Sleeve Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Sleeve Length:</strong> Measure from the shoulder seam down
                        to your desired length.</div>
                </div>
            </div>

            <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Lower Body</h5>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Pants Length.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Pants Length Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Pants Length:</strong> Measure from your waist down to where
                        you want the pants to end.</div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Thigh.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Thigh Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Thigh:</strong> Measure around the fullest part of your
                        thigh.</div>
                </div>
            </div>

            <div class="measurement-item mb-4">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-3">
                        <img src="../assets/img/Crotch.png" onerror="this.src='../assets/img/measurrement.png'"
                            alt="Crotch Measurement" class="img-fluid rounded border"
                            onclick="openImagePreview(this.src, this.alt)" style="cursor: pointer;">
                    </div>
                    <div class="col-8 col-sm-9"><strong>Crotch:</strong> Measure from the front waist center,
                        between legs, to the back waist center.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal (Lightbox) -->
<div id="imagePreviewModal" class="modal" style="display: none; background-color: rgba(0,0,0,0.9); z-index: 1100;">
    <span class="close" onclick="closeImagePreview()"
        style="color: #fff; opacity: 1; position: absolute; top: 20px; right: 30px; font-size: 40px; cursor: pointer; z-index: 1200;">&times;</span>
    <img class="modal-content" id="previewImage"
        style="margin: auto; display: block; width: 80%; max-width: 700px; background: transparent; border: none; box-shadow: none;">
    <div id="caption"
        style="margin: auto; display: block; width: 80%; max-width: 700px; text-align: center; color: #ccc; padding: 10px 0; height: 150px;">
    </div>
</div>