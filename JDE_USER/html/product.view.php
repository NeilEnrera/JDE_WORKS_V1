<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products - JDE Works of Our Hands</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/product.css">
</head>

<body>
    <?php include '../backend/navbar.php'; ?>

    <main class="products-page">
        <div class="container">
            <!-- Page Header -->
            <div class="hero-stage">
                <div class="hero-glass-container">
                    <header class="products-header-modern">
                        <div class="header-main-content">
                            <div class="title-group">
                                <span class="hero-badge">PREMIUM COLLECTION</span>
                                <h1 class="products-title-new">PRODUCTS</h1>
                                <p class="products-subtitle">Exquisite craftsmanship in bespoke upholstery and
                                    tailoring.</p>
                            </div>
                        </div>
                    </header>
                </div>
                <div class="hero-decorative-text">JDE WORKS</div>
                <div class="hero-shimmer"></div>
            </div>


            <!-- Products Layout: Sidebar + Main Content -->
            <div class="products-layout">
                <!-- Sidebar with Search and Product List -->
                <aside class="product-sidebar" id="productSidebar">
                    <!-- Search Bar in Sidebar -->
                    <div class="sidebar-search-container">
                        <div class="sidebar-search-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" id="productSearch" class="sidebar-search-input"
                                placeholder="Search products..." aria-label="Search products">
                        </div>
                    </div>



                    <div class="filter-section">
                        <div class="filter-header" data-filter="type">
                            <h3 class="filter-title">TYPE</h3>
                            <svg class="filter-toggle" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="18 15 12 9 6 15"></polyline>
                            </svg>
                        </div>
                        <div class="filter-content" id="typeFilter">
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="polo" data-filter-type="type">
                                <span>Polo <span class="filter-count" data-count="polo"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="trouser" data-filter-type="type">
                                <span>Trousers <span class="filter-count" data-count="trouser"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="blouse" data-filter-type="type">
                                <span>Blouses <span class="filter-count" data-count="blouse"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="skirt" data-filter-type="type">
                                <span>Skirts <span class="filter-count" data-count="skirt"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="pants" data-filter-type="type">
                                <span>Pants <span class="filter-count" data-count="pants"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="set" data-filter-type="type">
                                <span>Uniform Sets <span class="filter-count" data-count="set"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="suit" data-filter-type="type">
                                <span>Suits <span class="filter-count" data-count="suit"></span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" class="filter-checkbox" value="t-shirt" data-filter-type="type">
                                <span>T-shirts <span class="filter-count" data-count="t-shirt"></span>
                            </label>
                        </div>
                        <div class="filter-section">
                            <div class="filter-header" data-filter="fit">
                                <h3 class="filter-title">FIT TYPE</h3>
                                <svg class="filter-toggle" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15"></polyline>
                                </svg>
                            </div>
                            <div class="filter-content" id="fitFilter">
                                <label class="filter-item">
                                    <input type="checkbox" class="filter-checkbox" value="regular"
                                        data-filter-type="fit">
                                    <span>Regular Fit</span>
                                </label>
                                <label class="filter-item">
                                    <input type="checkbox" class="filter-checkbox" value="loose" data-filter-type="fit">
                                    <span>Loose</span>
                                </label>
                                <label class="filter-item">
                                    <input type="checkbox" class="filter-checkbox" value="oversized"
                                        data-filter-type="fit">
                                    <span>Oversized</span>
                                </label>
                                <label class="filter-item">
                                    <input type="checkbox" class="filter-checkbox" value="slim" data-filter-type="fit">
                                    <span>Slim Fit</span>
                                </label>
                                <label class="filter-item">
                                    <input type="checkbox" class="filter-checkbox" value="skinny"
                                        data-filter-type="fit">
                                    <span>Skinny</span>
                                </label>
                            </div>
                        </div>

                        <div class="filter-section">
                            <div class="filter-header" data-filter="price">
                                <h3 class="filter-title">PRICE RANGE (PHP)</h3>
                                <svg class="filter-toggle" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15"></polyline>
                                </svg>
                            </div>
                            <div class="filter-content" id="priceRangeFilter">
                                <div class="price-slider-wrapper">
                                    <div class="price-value">₱<span id="priceRangeValue">5000</span></div>
                                    <input type="range" id="priceRange" min="300" max="5000" value="5000"
                                        class="price-slider">
                                    <div class="slider-labels">
                                        <span>₱300</span>
                                        <span>₱5000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button class="clear-filters-btn" id="clearFilters">Clear All Filters</button>
                </aside>

                <!-- Main Products Content -->
                <div class="products-main">
                    <!-- No Results Message -->
                    <div id="noResults" class="no-results" style="display: none;">
                        <p>No products found matching your search and filters.</p>
                    </div>

                    <!-- Products Grid -->
                    <section class="products-section" data-section="all">
                        <div class="products-grid" id="allProductsGrid">
                            <?php foreach ($products as $prod): ?>
                                <div class="product-item" id="product-<?php echo $prod['slug']; ?>"
                                    data-id="<?php echo $prod['productID']; ?>"
                                    data-name="<?php echo strtolower($prod['productName']); ?>"
                                    data-category="<?php echo htmlspecialchars($prod['categoryName']); ?>"
                                    data-collection="<?php echo htmlspecialchars($prod['collection'] ?? ''); ?>"
                                    data-type="<?php echo htmlspecialchars($prod['type'] ?? ''); ?>"
                                    data-fit="<?php echo htmlspecialchars($prod['fitType'] ?? ''); ?>"
                                    data-price="<?php echo $prod['price']; ?>" data-sku="<?php echo $prod['slug']; ?>"
                                    data-id="<?php echo $prod['productID']; ?>"
                                    data-size-stocks='<?php echo htmlspecialchars($prod['sizeStocks'] ?: "{}"); ?>'
                                    data-img="<?php echo resolveProductPath($prod['productImage']); ?>"
                                    data-img2="<?php echo !empty($prod['productImage2']) ? resolveProductPath($prod['productImage2']) : ''; ?>"
                                    data-img3="<?php echo !empty($prod['productImage3']) ? resolveProductPath($prod['productImage3']) : ''; ?>"
                                    data-img4="<?php echo !empty($prod['productImage4']) ? resolveProductPath($prod['productImage4']) : ''; ?>"
                                    data-description="<?php echo htmlspecialchars($prod['description']); ?>"
                                    data-rating="<?php echo $prod['avg_rating'] ?: 0; ?>"
                                    data-reviews="<?php echo $prod['total_reviews'] ?: 0; ?>"
                                    onclick="orderProduct('<?php echo $prod['slug']; ?>')">

                                    <div class="product-badge ready">
                                        <?php echo htmlspecialchars($prod['categoryName']); ?>
                                    </div>

                                    <div class="product-image-wrapper">
                                        <div class="product-image"
                                            style="background: url('<?php echo resolveProductPath($prod['productImage']); ?>') center/cover no-repeat;">
                                            <?php if (empty($prod['productImage'])): ?>
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
                                        <h3 class="product-name"><?php echo htmlspecialchars($prod['productName']); ?></h3>
                                        <p class="product-description">
                                            <?php echo htmlspecialchars(substr($prod['description'] ?? '', 0, 50)) . '...'; ?>
                                        </p>
                                        <div class="product-footer">
                                            <p class="product-price">₱<?php echo number_format($prod['price'], 2); ?></p>
                                            <div class="product-actions">
                                                <button
                                                    class="wishlist-btn <?php echo in_array($prod['slug'], $wishlist) ? 'active' : ''; ?>"
                                                    onclick="toggleWishlist(this); event.stopPropagation();"
                                                    data-sku="<?php echo $prod['slug']; ?>" aria-label="Add to Wishlist">
                                                    <i class="bi bi-bookmark-plus"></i>
                                                </button>
                                                <button class="order-button-new"
                                                    onclick="triggerQuickAdd(this); event.stopPropagation();"
                                                    aria-label="Add to Cart">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Pagination Controls -->
                    <div id="paginationContainer" class="pagination-container"></div>

                </div>
    </main>

    <!-- Quick Add Modal -->
    <div id="quickAddModal" class="quick-modal">
        <div class="quick-modal-content">
            <span class="quick-modal-close" onclick="closeQuickAddModal()">&times;</span>
            <div class="quick-modal-grid">
                <!-- Left: Image Gallery -->
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

                <!-- Right: Product details -->
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

    <!-- Site Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JDE Works of Our Hands. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Quick Modal Lightbox -->
    <div id="quickAddLightbox" class="lightbox-modal" onclick="closeQuickLightbox(event)">
        <span class="lightbox-close" onclick="closeQuickLightbox(event)">&times;</span>
        <button class="lightbox-nav prev" onclick="navigateQuickLightbox(-1, event)">
            <i class="bi bi-chevron-left"></i>
        </button>

        <div class="lightbox-card">
            <div class="lightbox-image-wrapper">
                <div class="lightbox-accent"></div>
                <img id="quickLightboxImg" src="" alt="Fullscreen Preview" />
            </div>
        </div>

        <button class="lightbox-nav next" onclick="navigateQuickLightbox(1, event)">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <style>
        /* Premium Card-Style Lightbox */
        .lightbox-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            /* Neutral black instead of blue */
            backdrop-filter: blur(15px);
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .lightbox-modal.active {
            display: flex;
            opacity: 1;
        }

        .lightbox-card {
            background: #f5f2eb;
            /* Cream/Beige background */
            padding: 4.5rem;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            max-width: 90vw;
            max-height: 85vh;
            z-index: 10000;
            transform: scale(0.9);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .lightbox-modal.active .lightbox-card {
            transform: scale(1);
        }

        .lightbox-image-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-accent {
            position: absolute;
            width: 100%;
            height: 100%;
            background: #d6b25e;
            /* Gold accent */
            transform: translate(25px, 25px);
            z-index: 1;
            border-radius: 4px;
        }

        .lightbox-image-wrapper img {
            position: relative;
            z-index: 10;
            max-width: 100%;
            max-height: 65vh;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .lightbox-close {
            position: absolute;
            top: 30px;
            right: 40px;
            color: #fff;
            font-size: 2.2rem;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10010;
            background: rgba(0, 0, 0, 0.2);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .lightbox-close:hover {
            background: #fff;
            color: #052b47;
            transform: rotate(90deg);
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            border: none;
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10005;
            font-size: 1.4rem;
        }

        .lightbox-nav:hover {
            background: #d6b25e;
            color: #052b47;
            transform: translateY(-50%) scale(1.1);
        }

        .lightbox-nav.prev {
            left: 40px;
        }

        .lightbox-nav.next {
            right: 40px;
        }

        @media (max-width: 768px) {
            .lightbox-card {
                padding: 2.5rem;
            }

            .lightbox-accent {
                transform: translate(15px, 15px);
            }

            .lightbox-nav {
                width: 44px;
                height: 44px;
            }

            .lightbox-nav.prev {
                left: 15px;
            }

            .lightbox-nav.next {
                right: 15px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/quick-add.js?v=<?php echo time(); ?>"></script>
    <script src="../js/wishlist.js?v=<?php echo time(); ?>"></script>
    <script src="../js/product.js?v=<?php echo time(); ?>"></script>
</body>

</html>