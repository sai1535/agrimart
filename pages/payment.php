<?php
$page_title = "Secure Payment Gateway";
include('../includes/db.php');
session_start();

// Ensure farmer has checking session data active
if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout_data'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$checkout = $_SESSION['checkout_data'];
$error_message = '';

// Handle final order creation when mock payment is authorized
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_order'])) {
    try {
        $conn->beginTransaction();

        // 1. Generate unique invoice payment ID and insert Order
        $payment_id = 'PAY-AGRI-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, payment_status, tracking_status, shipping_address, phone, payment_id) VALUES (?, ?, ?, 'Paid', 'Order Placed', ?, ?, ?)");
        $stmt_order->execute([
            $user_id,
            $checkout['grand_total'],
            $checkout['payment_method'],
            $checkout['address'],
            $checkout['phone'],
            $payment_id
        ]);
        $order_id = $conn->lastInsertId();

        // 2. Retrieve user's cart items
        $stmt_cart = $conn->prepare("SELECT c.quantity, p.id as product_id, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt_cart->execute([$user_id]);
        $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

        // PreparedStatement for items and stock update
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($cart_items as $item) {
            // Confirm stock sufficiency
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Product stock depleted during checkout processing.");
            }

            // Record purchased item
            $stmt_item->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);

            // Deduct stock
            $stmt_stock->execute([
                $item['quantity'],
                $item['product_id']
            ]);
        }

        // 3. Wipe cart
        $stmt_clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_clear->execute([$user_id]);

        $conn->commit();

        // Remove checkout session parameters
        unset($_SESSION['checkout_data']);

        header("Location: order_status.php?order_id=" . $order_id . "&success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Transaction aborted: " . $e->getMessage();
    }
}

include('../includes/header.php');
?>

<div class="main-container">
    <div class="form-card" style="max-width: 500px; margin: 2rem auto;">
        <h2 class="form-title">🛡️ Payment Portal</h2>
        <p class="form-subtitle">AgriMart Secure Payment Gateway</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Order pricing display badge -->
        <div style="background-color: var(--primary-light); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; border: 1px solid rgba(46, 125, 50, 0.15);">
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary-color); letter-spacing: 0.5px; text-transform: uppercase;">Amount to Transfer</span>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary-hover); font-family: var(--font-heading); margin: 0.25rem 0;">$<?= number_format($checkout['grand_total'], 2); ?></div>
            <span style="font-size: 0.85rem; color: var(--text-light);">Payment Protocol: <strong><?= htmlspecialchars($checkout['payment_method']); ?></strong></span>
        </div>

        <form id="paymentForm" method="POST" action="">
            <input type="hidden" name="process_order" value="1">

            <!-- Card Payment Display -->
            <?php if ($checkout['payment_method'] === 'Card'): ?>
                <!-- Interactive visual credit card mockup -->
                <div class="card-visual">
                    <div class="card-visual-chip"></div>
                    <div class="card-visual-number" id="cardNoVisual">•••• •••• •••• ••••</div>
                    <div class="card-visual-row">
                        <div>
                            <div style="font-size: 0.5rem; opacity: 0.8;">CARD HOLDER</div>
                            <div id="cardNameVisual" style="font-size: 0.85rem; font-weight: 600;">FARMER ACCOUNT</div>
                        </div>
                        <div>
                            <div style="font-size: 0.5rem; opacity: 0.8;">EXPIRES</div>
                            <div id="cardExpiryVisual" style="font-size: 0.85rem; font-weight: 600;">MM/YY</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="card_num">Card Number</label>
                    <input type="text" class="form-control" id="card_num" placeholder="1234 5678 1234 5678" maxlength="19" required oninput="updateCardNo(this)">
                </div>

                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="form-label" for="expiry">Expiry Date</label>
                        <input type="text" class="form-control" id="expiry" placeholder="MM/YY" maxlength="5" required oninput="updateCardExpiry(this)">
                    </div>
                    <div>
                        <label class="form-label" for="cvv">CVV Code</label>
                        <input type="password" class="form-control" id="cvv" placeholder="123" maxlength="3" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="holder">Cardholder Name</label>
                    <input type="text" class="form-control" id="holder" placeholder="Enter Full Name" required oninput="updateCardName(this)">
                </div>

            <!-- UPI Payment Display -->
            <?php elseif ($checkout['payment_method'] === 'UPI'): ?>
                <div class="upi-qr-wrapper">
                    <!-- Visual QR scan simulation -->
                    <div class="qr-code-box">
                        <svg width="150" height="150" viewBox="0 0 100 100" style="color: var(--primary-color);">
                            <!-- Outer brackets -->
                            <path d="M 10 30 L 10 10 L 30 10" fill="none" stroke="currentColor" stroke-width="4"/>
                            <path d="M 70 10 L 90 10 L 90 30" fill="none" stroke="currentColor" stroke-width="4"/>
                            <path d="M 90 70 L 90 90 L 70 90" fill="none" stroke="currentColor" stroke-width="4"/>
                            <path d="M 30 90 L 10 90 L 10 70" fill="none" stroke="currentColor" stroke-width="4"/>
                            <!-- QR Pattern block shapes -->
                            <rect x="20" y="20" width="15" height="15" fill="currentColor"/>
                            <rect x="25" y="25" width="5" height="5" fill="white"/>
                            <rect x="65" y="20" width="15" height="15" fill="currentColor"/>
                            <rect x="70" y="25" width="5" height="5" fill="white"/>
                            <rect x="20" y="65" width="15" height="15" fill="currentColor"/>
                            <rect x="25" y="70" width="5" height="5" fill="white"/>
                            <!-- Tiny blocks -->
                            <rect x="45" y="30" width="10" height="10" fill="currentColor"/>
                            <rect x="60" y="45" width="10" height="15" fill="currentColor"/>
                            <rect x="45" y="60" width="15" height="10" fill="currentColor"/>
                            <rect x="40" y="45" width="10" height="10" fill="currentColor"/>
                            <rect x="75" y="65" width="5" height="5" fill="currentColor"/>
                            <rect x="70" y="75" width="10" height="5" fill="currentColor"/>
                        </svg>
                    </div>
                    <p style="font-size: 0.95rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem;">Scan QR with Bhim, GPay, PhonePe, or Paytm</p>
                    <p style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 1.5rem; line-height: 1.4;">Open your mobile UPI app, scan the barcode, authorize the payment, and then click the confirm button below.</p>
                </div>

            <!-- Net Banking Display -->
            <?php else: ?>
                <div class="form-group">
                    <label class="form-label" for="bank">Select Agricultural Bank Portal</label>
                    <select class="form-control" id="bank" required>
                        <option value="">-- Choose Your Bank --</option>
                        <option value="SBI">State Bank of India (Kisan Portal)</option>
                        <option value="HDFC">HDFC Bank Rural Banking</option>
                        <option value="ICICI">ICICI Bank Agri-Services</option>
                        <option value="NABARD">NABARD Partner Cooperative Bank</option>
                        <option value="PNB">Punjab National Bank (Agricultural Dev.)</option>
                    </select>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 1.5rem;">Upon confirming, you will be redirected to the secure farming gateway of your bank for account authorization.</p>
            <?php endif; ?>

            <button type="button" class="btn btn-primary btn-full" onclick="startSimulatedPayment()" style="font-size: 1.1rem; padding: 0.75rem;">
                🛡️ Authorize Payment & Order
            </button>
        </form>
    </div>
</div>

<!-- Multi-state full-screen loader simulation -->
<div class="payment-processing-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <h3 id="loadingText" style="font-family: var(--font-heading); color: var(--primary-hover); font-weight: 700;">Initiating transaction...</h3>
    <p style="font-size: 0.85rem; color: var(--text-light); margin-top: 0.5rem;">Securing connection. Do not close this window or refresh.</p>
</div>

<script>
    // Functions to update credit card visualizer dynamically
    function updateCardNo(el) {
        let v = el.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let matches = v.match(/\d{4,16}/g);
        let match = matches && matches[0] || '';
        let parts = [];
        for (let i = 0, len = match.length; i < len; i += 4) {
            parts.push(match.substring(i, i + 4));
        }
        if (parts.length > 0) {
            el.value = parts.join(' ');
            document.getElementById('cardNoVisual').innerText = el.value;
        } else {
            el.value = v;
            document.getElementById('cardNoVisual').innerText = v || '•••• •••• •••• ••••';
        }
    }
    
    function updateCardExpiry(el) {
        let v = el.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (v.length >= 2) {
            el.value = v.substring(0,2) + '/' + v.substring(2,4);
            document.getElementById('cardExpiryVisual').innerText = el.value;
        } else {
            el.value = v;
            document.getElementById('cardExpiryVisual').innerText = v || 'MM/YY';
        }
    }

    function updateCardName(el) {
        document.getElementById('cardNameVisual').innerText = el.value.toUpperCase() || 'FARMER ACCOUNT';
    }

    // Trigger simulation timeline on button click
    function startSimulatedPayment() {
        const form = document.getElementById('paymentForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const overlay = document.getElementById('loadingOverlay');
        const text = document.getElementById('loadingText');
        
        overlay.classList.add('active');

        const simulationSteps = [
            { delay: 0, text: "Connecting to secure banking processor..." },
            { delay: 1500, text: "Authorizing $<?= number_format($checkout['grand_total'], 2); ?> transaction..." },
            { delay: 3000, text: "Routing funds through agricultural clearinghouse..." },
            { delay: 4500, text: "Authenticating OTP/Signature validation..." },
            { delay: 5800, text: "Transaction Approved! Registering order with AgriMart server..." }
        ];

        simulationSteps.forEach(step => {
            setTimeout(() => {
                text.innerText = step.text;
            }, step.delay);
        });

        // Submit form after simulation completes
        setTimeout(() => {
            form.submit();
        }, 7000);
    }
</script>

<?php
include('../includes/footer.php');
?>
