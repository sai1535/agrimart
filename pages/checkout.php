<?php
$page_title = "Checkout";
include('../includes/db.php');
session_start();

// Redirect to login if user session is not active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $conn->prepare("SELECT c.quantity, p.id as product_id, p.name, p.price, p.category 
                        FROM cart c JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: ../index.php");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.05;
$shipping = $subtotal > 100 ? 0.00 : 5.00;
$grand_total = $subtotal + $tax + $shipping;

// Retrieve user's default details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$farmer = $stmt->fetch(PDO::FETCH_ASSOC);

$error_message = '';

if (isset($_POST['submit_checkout'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    if (empty($name) || empty($phone) || empty($address) || empty($payment_method)) {
        $error_message = "Please complete all fields and select a payment method.";
    } else {
        // Save details in checkout session
        $_SESSION['checkout_data'] = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'payment_method' => $payment_method,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'grand_total' => $grand_total
        ];
        
        header("Location: payment.php");
        exit();
    }
}

include('../includes/header.php');
?>

<div class="main-container">
    <h2 class="section-title">🌾 Secure Order Checkout</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="cart-layout">
            <!-- Left Side: Shipping & Payment Method Info -->
            <div class="cart-items-panel" style="padding: 2rem;">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">1. Shipping Information</h3>
                
                <div class="form-group">
                    <label class="form-label" for="name">Recipient Name</label>
                    <input class="form-control" type="text" id="name" name="name" 
                           value="<?= htmlspecialchars($farmer['name'] ?? $farmer['username']); ?>" placeholder="Enter name" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Farmer Mobile Number (for delivery alerts)</label>
                    <input class="form-control" type="tel" id="phone" name="phone" 
                           value="<?= htmlspecialchars($farmer['phone'] ?? ''); ?>" placeholder="10-digit mobile number" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Delivery Address / Farm Location</label>
                    <textarea class="form-control" id="address" name="address" rows="3" 
                              placeholder="Complete address (Village, Mandal/Town, District, State)" required><?= htmlspecialchars($farmer['address'] ?? ''); ?></textarea>
                </div>

                <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">2. Payment Method</h3>
                
                <div class="payment-methods-grid">
                    <!-- UPI payment option -->
                    <label class="payment-method-card" id="card-upi">
                        <input type="radio" name="payment_method" value="UPI" style="display: none;" required>
                        <div class="payment-method-icon">📱</div>
                        <div class="payment-method-name">UPI / QR Code</div>
                    </label>

                    <!-- Debit/Credit card payment option -->
                    <label class="payment-method-card" id="card-card">
                        <input type="radio" name="payment_method" value="Card" style="display: none;">
                        <div class="payment-method-icon">💳</div>
                        <div class="payment-method-name">Card Payment</div>
                    </label>

                    <!-- Net Banking option -->
                    <label class="payment-method-card" id="card-net">
                        <input type="radio" name="payment_method" value="Net Banking" style="display: none;">
                        <div class="payment-method-icon">🏛️</div>
                        <div class="payment-method-name">Net Banking</div>
                    </label>
                </div>
            </div>

            <!-- Right Side: Order Review Summary -->
            <div class="cart-summary-panel">
                <h3 class="summary-title">Review Items</h3>
                <div style="max-height: 180px; overflow-y: auto; margin-bottom: 1.5rem;">
                    <?php foreach ($cart_items as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem; border-bottom: 1px dashed var(--border-color); padding-bottom: 0.4rem;">
                            <span style="font-weight: 500;"><?= htmlspecialchars($item['name']); ?> <strong style="color: var(--text-light);">x<?= $item['quantity']; ?></strong></span>
                            <span style="font-family: monospace; font-weight: 600;">$<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row" style="font-size: 0.9rem;">
                    <span>Cart Subtotal</span>
                    <span>$<?= number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row" style="font-size: 0.9rem;">
                    <span>Agri Tax (5% GST)</span>
                    <span>$<?= number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row" style="font-size: 0.9rem;">
                    <span>Shipping Fee</span>
                    <span><?= $shipping == 0 ? '<span style="color: var(--success-color);">FREE</span>' : '$' . number_format($shipping, 2); ?></span>
                </div>

                <div class="summary-row summary-row-total" style="margin-top: 0.5rem; padding-top: 0.5rem;">
                    <span>Grand Total</span>
                    <span>$<?= number_format($grand_total, 2); ?></span>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" name="submit_checkout" class="btn btn-primary btn-full" style="font-size: 1.05rem; padding: 0.75rem;">
                        Proceed to Payment ➔
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Visual toggle for payment method cards
    const cards = document.querySelectorAll('.payment-method-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all
            cards.forEach(c => c.classList.remove('active'));
            // Add to selected one
            this.classList.add('active');
            // Check the internal radio button
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
</script>

<?php
include('../includes/footer.php');
?>
