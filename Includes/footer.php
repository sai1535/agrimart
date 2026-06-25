    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h4>🌾 AgriMart Corp.</h4>
                <p>Empowering farmers and agricultural communities worldwide with quality seeds, organic fertilizers, secure weedicides, and direct crop-related product matching.</p>
                <p><strong>Support Hotline:</strong> 1800-456-FARM (3276)</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= $base_url; ?>index.php">Browse Products</a></li>
                    <li><a href="<?= $base_url; ?>pages/cart.php">Shopping Cart</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?= $base_url; ?>pages/dashboard.php">Farmer Dashboard</a></li>
                        <li><a href="<?= $base_url; ?>pages/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= $base_url; ?>pages/login.php">Farmer Login</a></li>
                        <li><a href="<?= $base_url; ?>pages/register.php">Register Account</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Crop Product Guide</h4>
                <p>Select your crop category (Paddy, Wheat, Cotton, Vegetables) on our shop homepage to discover curated lists of suitable seeds, NPK fertilizers, and pesticide treatments recommended for maximizing crop yield.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y'); ?> AgriMart E-Commerce. All rights reserved. Designed for sustainable farming.</p>
        </div>
    </footer>
</body>
</html>
