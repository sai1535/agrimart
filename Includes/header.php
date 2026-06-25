<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/db.php';

// Detect whether the current script is in the pages directory to resolve asset paths
$is_subpage = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$base_url = $is_subpage ? '../' : '';

// Count cart items
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = (int)$stmt->fetchColumn();
} else {
    // Session fallback for guest cart
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) {
            $cart_count += $qty;
        }
    }
}

// Fetch user info if logged in
$user_name = '';
$user_role = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($current_user) {
        $user_name = $current_user['name'] ?? $current_user['username'] ?? $current_user['email'];
        $user_role = $current_user['role'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . " - AgriMart" : "AgriMart - Agricultural E-Commerce"; ?></title>
    <link rel="stylesheet" href="<?= $base_url; ?>style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="<?= $base_url; ?>index.php" class="logo-container">
                <span class="logo-icon">🌾</span>
                <span class="logo-text">Agri<span>Mart</span></span>
            </a>
            <nav>
                <a href="<?= $base_url; ?>index.php">🌱 Browse Shop</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $base_url; ?>pages/dashboard.php">🚜 Farmer Dashboard</a>
                    <a href="<?= $base_url; ?>pages/cart.php" class="cart-link">
                        🛒 Cart <span class="cart-count-badge" id="cart-count"><?= $cart_count; ?></span>
                    </a>
                    <span class="user-badge">👨‍🌾 <?= htmlspecialchars($user_name); ?></span>
                    <a href="<?= $base_url; ?>pages/logout.php" class="logout-button">Logout</a>
                <?php else: ?>
                    <a href="<?= $base_url; ?>pages/cart.php" class="cart-link">
                        🛒 Cart <span class="cart-count-badge" id="cart-count"><?= $cart_count; ?></span>
                    </a>
                    <a href="<?= $base_url; ?>pages/login.php" class="nav-btn nav-btn-outline">Farmer Login</a>
                    <a href="<?= $base_url; ?>pages/register.php" class="nav-btn nav-btn-filled">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
