<?php
$page_title = "My Cart";
include('../includes/db.php');
session_start();

// Handle cart updates/actions before including header to prevent "headers already sent"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Fetch product stock first
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_stock = $stmt->fetchColumn();

    if ($product_stock !== false) {
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            if ($action === 'add') {
                // Check if already in database cart
                $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($exists) {
                    $new_qty = $exists['quantity'] + $quantity;
                    if ($new_qty > $product_stock) $new_qty = $product_stock;
                    $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $upd->execute([$new_qty, $exists['id']]);
                } else {
                    if ($quantity > $product_stock) $quantity = $product_stock;
                    $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $ins->execute([$user_id, $product_id, $quantity]);
                }
                header("Location: ../index.php?msg=added#shop-section");
                exit();

            } elseif ($action === 'update') {
                if ($quantity <= 0) {
                    $del = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $del->execute([$user_id, $product_id]);
                } else {
                    if ($quantity > $product_stock) $quantity = $product_stock;
                    $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $upd->execute([$quantity, $user_id, $product_id]);
                }
                header("Location: cart.php");
                exit();

            } elseif ($action === 'remove') {
                $del = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $del->execute([$user_id, $product_id]);
                header("Location: cart.php");
                exit();
            }

        } else {
            // Guest Session Cart logic
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if ($action === 'add') {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                if ($_SESSION['cart'][$product_id] > $product_stock) {
                    $_SESSION['cart'][$product_id] = $product_stock;
                }
                header("Location: ../index.php?msg=added#shop-section");
                exit();

            } elseif ($action === 'update') {
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    if ($quantity > $product_stock) $quantity = $product_stock;
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                header("Location: cart.php");
                exit();

            } elseif ($action === 'remove') {
                unset($_SESSION['cart'][$product_id]);
                header("Location: cart.php");
                exit();
            }
        }
    }
}

// Fetch Cart Data
$cart_items = [];
$subtotal = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT c.quantity, p.id as product_id, p.name, p.price, p.category, p.stock 
                            FROM cart c JOIN products p ON c.product_id = p.id 
                            WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch products based on session array keys
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
        $stmt = $conn->prepare("SELECT id as product_id, name, price, category, stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($_SESSION['cart']));
        $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($db_products as $p) {
            $p_id = $p['product_id'];
            $qty = $_SESSION['cart'][$p_id];
            $p['quantity'] = $qty;
            $cart_items[] = $p;
        }
    }
}

// Calculate total
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Fixed metrics
$tax_rate = 0.05; // 5% agricultural subsidy tax
$tax = $subtotal * $tax_rate;
$shipping = $subtotal > 100 || $subtotal == 0 ? 0.00 : 5.00; // Free shipping over $100
$grand_total = $subtotal + $tax + $shipping;

include('../includes/header.php');
?>

<div class="main-container">
    <h2 class="section-title">🛒 Your Farming Cart</h2>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
            <span style="font-size: 4rem;">🌾</span>
            <h3 style="margin-top: 1rem; margin-bottom: 0.5rem;">Your cart is currently empty</h3>
            <p style="color: var(--text-light); margin-bottom: 1.5rem;">Browse our agricultural products catalog and add fertilizers, crop seeds, or tools.</p>
            <a href="../index.php" class="btn btn-primary">🌱 Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Left Side: List of Items -->
            <div class="cart-items-panel">
                <?php foreach ($cart_items as $item): ?>
                    <?php
                    $emoji = '📦';
                    if ($item['category'] === 'Seeds') $emoji = '🌱';
                    elseif ($item['category'] === 'Fertilizers') $emoji = '🧪';
                    elseif ($item['category'] === 'Pesticides') $emoji = '🛢️';
                    elseif ($item['category'] === 'Tools') $emoji = '🔧';
                    ?>
                    <div class="cart-item-row">
                        <!-- Product Icon -->
                        <div class="cart-item-thumb">
                            <span><?= $emoji; ?></span>
                        </div>

                        <!-- Product Info -->
                        <div class="cart-item-info">
                            <h4 class="cart-item-title"><?= htmlspecialchars($item['name']); ?></h4>
                            <span class="badge badge-category" style="margin-top: 0.25rem; display: inline-block; font-size: 0.7rem;"><?= htmlspecialchars($item['category']); ?></span>
                        </div>

                        <!-- Quantity Stepper Controls -->
                        <div class="cart-quantity-control">
                            <form method="POST" action="cart.php" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="quantity" value="<?= $item['quantity'] - 1; ?>">
                                <button type="submit" class="cart-quantity-btn">-</button>
                            </form>
                            
                            <span class="cart-quantity-val"><?= $item['quantity']; ?></span>
                            
                            <form method="POST" action="cart.php" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="quantity" value="<?= $item['quantity'] + 1; ?>">
                                <button type="submit" class="cart-quantity-btn" <?= $item['quantity'] >= $item['stock'] ? 'disabled style="color: #ccc; cursor: not-allowed;"' : ''; ?>>+</button>
                            </form>
                        </div>

                        <!-- Price Info -->
                        <div class="cart-price-block">
                            <span class="cart-item-total-price">$<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                            <div class="cart-item-unit-price">$<?= number_format($item['price'], 2); ?> each</div>
                        </div>

                        <!-- Delete Button -->
                        <form method="POST" action="cart.php" style="display: inline; margin-left: 0.5rem;">
                            <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-outline" style="padding: 0.4rem 0.6rem; border-color: #f87171; color: #ef4444;" title="Remove item">🗑️</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right Side: Order Summary -->
            <div class="cart-summary-panel">
                <h3 class="summary-title">Harvest Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?= number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (5% Agricultural GST)</span>
                    <span>$<?= number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?= $shipping == 0 ? '<span style="color: var(--success-color); font-weight: 600;">FREE</span>' : '$' . number_format($shipping, 2); ?></span>
                </div>
                <?php if ($shipping > 0): ?>
                    <p style="font-size: 0.75rem; color: var(--text-light); margin-bottom: 1rem;">Add <strong>$<?= number_format(100 - $subtotal, 2); ?></strong> more to get free shipping!</p>
                <?php endif; ?>

                <div class="summary-row summary-row-total">
                    <span>Grand Total</span>
                    <span>$<?= number_format($grand_total, 2); ?></span>
                </div>

                <div style="margin-top: 1.5rem;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn btn-primary btn-full">Proceed to Checkout ➔</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-accent btn-full">🔑 Login to Checkout</a>
                        <p style="text-align: center; font-size: 0.8rem; color: var(--text-light); margin-top: 0.5rem;">You must log in as a farmer to specify shipping coordinates.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
include('../includes/footer.php');
?>
