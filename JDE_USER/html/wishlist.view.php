<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Wishlist - JDE Works of Our Hands</title>

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
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/product.css">
    <link rel="stylesheet" href="../css/wishlist.css?v=<?php echo time(); ?>">

    <main class="wishlist-page">
        <div class="container wishlist-container">

            <?php if (empty($wishlist_items)): ?>
                <div class="empty-wishlist text-center py-5 mt-5">
                    <div class="empty-wishlist-hero">
                        <div class="empty-icon-wrapper">
                            <i class="bi bi-bookmark-heart"></i>
                        </div>
                        <h3>Your wishlist is longing for items</h3>
                        <p>Browse our curated collection of premade and custom uniforms to find your perfect fit.</p>
                        <a href="product.php" class="btn btn-premium-action" id="startExploringBtn">Start Exploring</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="wishlist-header mt-5">
                    <div class="wishlist-title-group">
                        <h2>My Wishlist</h2>
                        <span class="wishlist-count">
                            <?php echo count($wishlist_items); ?> Items Saved
                        </span>
                    </div>
                    <div class="wishlist-actions-top">
                        <button class="btn btn-bulk-add" onclick="addAllToCart()">
                            <i class="bi bi-bag-plus me-2"></i>ADD ALL TO CART
                        </button>
                    </div>
                </div>

                <div class="products-main">
                    <section class="products-section">
                        <div class="products-grid">
                            <?php
                            $delay = 0;
                            foreach ($wishlist_items as $product):
                                $product_slug = $product['sku'];
                                $redirection_url = "ordering.php?product=" . urlencode($product_slug);
                                $display_img = resolveProductPath($product['image']);
                                $category = isset($product['category']) ? strtolower($product['category']) : '';
                                $badge_class = 'ready';
                                $badge_text = 'Pre-made';

                                if (strpos($category, 'custom') !== false) {
                                    $badge_class = 'custom';
                                    $badge_text = 'Custom';
                                } elseif (strpos($product['name'], 'Set') !== false || strpos($product['name'], 'Business') !== false) {
                                    $badge_class = 'best';
                                    $badge_text = 'Premium';
                                }
                                ?>
                                <div class="product-item" id="product-<?php echo $product_slug; ?>"
                                    data-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                    data-price="<?php echo $product['price']; ?>" data-sku="<?php echo $product['sku']; ?>"
                                    data-img="<?php echo $display_img; ?>"
                                    data-img2="<?php echo $product['image2'] ? resolveProductPath($product['image2']) : ''; ?>"
                                    data-img3="<?php echo $product['image3'] ? resolveProductPath($product['image3']) : ''; ?>"
                                    data-img4="<?php echo $product['image4'] ? resolveProductPath($product['image4']) : ''; ?>"
                                    data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                    data-category="<?php echo htmlspecialchars($product['category'] ?? 'Collection'); ?>"
                                    data-size-stocks='<?php echo htmlspecialchars($product['sizeStocks'] ?: "{}"); ?>'
                                    data-rating="<?php echo $product['avg_rating'] ?: 0; ?>"
                                    data-reviews="<?php echo $product['total_reviews'] ?: 0; ?>"
                                    data-slug="<?php echo $product_slug; ?>" style="transition-delay: <?php echo $delay; ?>ms"
                                    onclick="window.location.href='<?php echo $redirection_url; ?>'">

                                    <div class="product-badge <?php echo $badge_class; ?>">
                                        <?php echo $badge_text; ?>
                                    </div>
                                    <div class="product-image-wrapper">
                                        <div class="product-image"
                                            style="background: url('<?php echo $display_img; ?>') center/cover no-repeat;">
                                            <?php if (empty($product['image'])): ?>
                                                <svg class="product-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path
                                                        d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                                                    <line x1="7" y1="7" x2="7.01" y2="7" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h3>
                                        <p class="product-description">
                                            <?php echo htmlspecialchars($product['description']); ?>
                                        </p>
                                        <div class="product-footer">
                                            <p class="product-price">₱
                                                <?php echo number_format($product['price']); ?>
                                            </p>
                                            <div class="product-actions">
                                                <button class="wishlist-btn active"
                                                    onclick="toggleWishlist(this); event.stopPropagation();"
                                                    data-sku="<?php echo $product['sku']; ?>" aria-label="Remove from Wishlist">
                                                    <i class="bi bi-bookmark-fill"></i>
                                                </button>
                                                <button class="order-button-new"
                                                    onclick="triggerQuickAdd(this); event.stopPropagation();"
                                                    aria-label="Add to Cart">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="9" cy="21" r="1"></circle>
                                                        <circle cx="20" cy="21" r="1"></circle>
                                                        <path
                                                            d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $delay += 100;
                            endforeach;
                            ?>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </div>

        <!-- Confirmation Modal -->
        <div class="modal fade premium-modal" id="confirmBulkModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header border-0 pb-0 justify-content-center">
                        <div class="premium-modal-icon mb-0 mt-3">
                            <i class="bi bi-cart-plus"></i>
                        </div>
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center pt-3 pb-4">
                        <h5 class="modal-title fw-bold mb-2">Add All to Cart</h5>
                        <p id="confirmBulkBody" class="text-muted mb-0">Add all saved items to your cart?</p>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-premium-action rounded-pill px-4" id="executeBulkBtn">Add
                            All</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Add Modal -->
        <div id="quickAddModal" class="quick-modal">
            <div class="quick-modal-content">
                <span class="quick-modal-close" onclick="closeQuickAddModal()">&times;</span>
                <div class="quick-modal-grid">
                    <div class="quick-modal-gallery">
                        <div class="quick-thumbnails">
                            <img src="" alt="Thumbnail" class="q-thumb active">
                            <img src="" alt="Thumbnail" class="q-thumb">
                            <img src="" alt="Thumbnail" class="q-thumb">
                        </div>
                        <div class="quick-main-image">
                            <img src="" alt="Product Image" id="quickMainImg">
                        </div>
                    </div>

                    <div class="quick-modal-info">
                        <h2 id="quickModalName">Product Name</h2>
                        <div class="quick-modal-subtitle">
                            <p class="quick-modal-sku">SKU: <span id="quickModalSku"></span></p>
                            <a href="javascript:void(0)" id="quickModalViewDetails" class="view-details-link">View full
                                details</a>
                        </div>

                        <div class="quick-modal-rating">
                            <div class="stars">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                            </div>
                            <span class="rating-text">(0 Reviews)</span>
                        </div>

                        <div class="quick-modal-price">
                            <span class="current-price" id="quickModalPrice">₱0.00</span>
                        </div>

                        <div class="quick-modal-meta">
                            <p><strong>Category:</strong> <span id="quickModalCategory">Category</span></p>
                            <p id="quickModalDescription">Product description goes here.</p>
                            <p><strong>Status:</strong> <span class="stock-status">In Stock</span></p>
                        </div>

                        <div class="quick-modal-selectors">
                            <div class="selector-group">
                                <label>Size:</label>
                                <div class="quick-sizes">
                                    <button type="button" class="q-size-btn active">S</button>
                                    <button type="button" class="q-size-btn">M</button>
                                    <button type="button" class="q-size-btn">L</button>
                                    <button type="button" class="q-size-btn">XL</button>
                                </div>
                            </div>

                            <div class="selector-group">
                                <label>Quantity:</label>
                                <div class="quick-qty">
                                    <button type="button" onclick="updateQuickQty(-1)">-</button>
                                    <input type="number" id="quickQtyInput" value="1" min="1" readonly>
                                    <button type="button" onclick="updateQuickQty(1)">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="quick-modal-actions">
                            <button class="btn-quick-add" onclick="processQuickAdd()">
                                <i class="bi bi-cart-plus me-2"></i>ADD TO CART
                            </button>
                            <button class="btn-quick-wishlist" id="modalWishlistBtn" onclick="toggleWishlist(this)"
                                data-sku="" aria-label="Add to wishlist">
                                <i class="bi bi-bookmark-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
</body>

</html>
<script src="../js/quick-add.js?v=<?php echo time(); ?>"></script>
<script src="../js/wishlist.js?v=<?php echo time(); ?>"></script>