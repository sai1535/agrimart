<?php
$page_title = "Online Agricultural Store";
include('includes/header.php'); // Include header (connects to database and handles session)

// Categories and crops lists
$categories = ['Seeds', 'Fertilizers', 'Pesticides', 'Tools'];
$crop_types = ['Paddy', 'Wheat', 'Cotton', 'Vegetables', 'General'];

// Read filters from GET query parameters
$active_category = isset($_GET['category']) ? $_GET['category'] : '';
$active_crop = isset($_GET['crop_type']) ? $_GET['crop_type'] : '';

// Build Query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($active_category)) {
    $query .= " AND category = ?";
    $params[] = $active_category;
}

if (!empty($active_crop)) {
    $query .= " AND crop_type = ?";
    $params[] = $active_crop;
}

$query .= " ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there is an alert message (e.g. from cart operations)
$alert_msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $alert_msg = "Item successfully added to your cart!";
    } elseif ($_GET['msg'] === 'insufficient_stock') {
        $alert_msg = "Requested quantity exceeds available stock.";
    }
}
?>

<div class="main-container">
    <!-- Hero Agricultural Banner -->
    <div class="hero-banner">
        <div class="hero-content">
            <span class="hero-tagline">Farmer Direct E-Commerce</span>
            <h1 class="hero-title">Sow the Best, Reap the Finest</h1>
            <p class="hero-description">Get premium fertilizers like Urea & Gromor, organic & systemic pesticides, crop seeds, and heavy-duty farming equipment. Filter by crop type to find products suitable for your farming needs.</p>
            <a href="#shop-section" class="btn btn-accent">🌱 Browse Products</a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($alert_msg)): ?>
        <div class="alert alert-success">
            <span>✅ <?= htmlspecialchars($alert_msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Store Section -->
    <div id="shop-section" style="scroll-margin-top: 120px;">
        <h2 class="section-title">🚜 Crop & Product Catalog</h2>
        
        <!-- Interactive Filtering Controls -->
        <div class="filter-wrapper">
            <div class="filter-row">
                <!-- Filter by Product Category -->
                <div class="filter-group">
                    <span class="filter-label">Product Type:</span>
                    <div class="filter-pills">
                        <a href="?category=<?= urlencode(''); ?>&crop_type=<?= urlencode($active_crop); ?>#shop-section" 
                           class="filter-pill <?= empty($active_category) ? 'active' : ''; ?>">All Categories</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="?category=<?= urlencode($cat); ?>&crop_type=<?= urlencode($active_crop); ?>#shop-section" 
                               class="filter-pill <?= $active_category === $cat ? 'active' : ''; ?>"><?= htmlspecialchars($cat); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Filter by Crop Type -->
                <div class="filter-group">
                    <span class="filter-label">Crop Suitability:</span>
                    <div class="filter-pills">
                        <a href="?category=<?= urlencode($active_category); ?>&crop_type=<?= urlencode(''); ?>#shop-section" 
                           class="filter-pill <?= empty($active_crop) ? 'active' : ''; ?>">All Crops</a>
                        <?php foreach ($crop_types as $crop): ?>
                            <a href="?category=<?= urlencode($active_category); ?>&crop_type=<?= urlencode($crop); ?>#shop-section" 
                               class="filter-pill <?= $active_crop === $crop ? 'active' : ''; ?>"><?= htmlspecialchars($crop); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Listing Grid -->
        <div class="product-list">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem; background: white; border-radius: 12px; border: 1px solid var(--border-color);">
                    <p style="font-size: 1.2rem; color: var(--text-light); font-weight: 500;">No farming products match your selected filters.</p>
                    <a href="index.php#shop-section" class="btn btn-primary" style="margin-top: 1rem;">Reset Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <!-- Badges for Crop & Category -->
                        <div class="product-badge-group">
                            <span class="badge badge-crop">🌾 <?= htmlspecialchars($p['crop_type']); ?></span>
                            <span class="badge badge-category"><?= htmlspecialchars($p['category']); ?></span>
                        </div>

                        <!-- Image Container with unique SVGs/Icons depending on category -->
                        <div class="product-image-container">
                            <div class="product-image-placeholder">
                                <?php
                                $emoji = '📦';
                                if ($p['category'] === 'Seeds') $emoji = '🌱';
                                elseif ($p['category'] === 'Fertilizers') $emoji = '🧪';
                                elseif ($p['category'] === 'Pesticides') $emoji = '🛢️';
                                elseif ($p['category'] === 'Tools') $emoji = '🔧';
                                ?>
                                <span style="font-size: 3.5rem;"><?= $emoji; ?></span>
                                <div class="product-image-text"><?= htmlspecialchars($p['category']); ?></div>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="product-card-body">
                            <h3 class="product-title"><?= htmlspecialchars($p['name']); ?></h3>
                            <p class="product-desc"><?= htmlspecialchars($p['description']); ?></p>

                            <!-- Stock Status Indicator -->
                            <div class="stock-indicator <?= $p['stock'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                                <?= $p['stock'] > 0 ? '✔️ In Stock (' . $p['stock'] . ' bags/units)' : '❌ Sold Out'; ?>
                            </div>

                            <!-- Price and Buy form -->
                            <div class="product-footer">
                                <span class="product-price">$<?= number_format($p['price'], 2); ?></span>
                                
                                <?php if ($p['stock'] > 0): ?>
                                    <form method="POST" action="pages/cart.php" style="display: flex; gap: 0.4rem; align-items: center;">
                                        <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                                        <input type="hidden" name="action" value="add">
                                        
                                        <!-- Quantity picker -->
                                        <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock']; ?>" 
                                               class="form-control" style="width: 55px; padding: 0.3rem; height: 35px; text-align: center; font-weight: 700;" required>
                                        
                                        <button type="submit" class="btn btn-primary" style="height: 35px; padding: 0 0.8rem; font-size: 0.85rem;">🛒 Add</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-outline" style="height: 35px; font-size: 0.85rem; cursor: not-allowed;" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include('includes/footer.php');
?>