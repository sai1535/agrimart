<?php
$page_title = "Track Order";
include('../includes/db.php');
session_start();

// Redirect to login if user session is not active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order details
$stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt_order->execute([$order_id, $user_id]);
$order = $stmt_order->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: dashboard.php");
    exit();
}

// Handle simulated status advancement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['advance_status'])) {
    $current_status = $order['tracking_status'];
    $next_status = 'Order Placed';
    
    if ($current_status === 'Order Placed') {
        $next_status = 'Processing';
    } elseif ($current_status === 'Processing') {
        $next_status = 'Shipped';
    } elseif ($current_status === 'Shipped') {
        $next_status = 'Delivered';
    } else {
        $next_status = 'Order Placed'; // Reset for demo looping
    }

    $stmt_upd = $conn->prepare("UPDATE orders SET tracking_status = ? WHERE id = ?");
    $stmt_upd->execute([$next_status, $order_id]);
    
    header("Location: order_status.php?order_id=" . $order_id);
    exit();
}

// Fetch order items purchased
$stmt_items = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.category 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Map status to progress bar percentage and active steps
$status = $order['tracking_status'];
$progress_width = 0;
$step_active = [1 => false, 2 => false, 3 => false, 4 => false];
$step_completed = [1 => false, 2 => false, 3 => false, 4 => false];

if ($status === 'Order Placed') {
    $progress_width = 0;
    $step_active[1] = true;
} elseif ($status === 'Processing') {
    $progress_width = 33;
    $step_completed[1] = true;
    $step_active[2] = true;
} elseif ($status === 'Shipped') {
    $progress_width = 66;
    $step_completed[1] = true;
    $step_completed[2] = true;
    $step_active[3] = true;
} elseif ($status === 'Delivered') {
    $progress_width = 100;
    $step_completed[1] = true;
    $step_completed[2] = true;
    $step_completed[3] = true;
    $step_completed[4] = true;
}

include('../includes/header.php');
?>

<div class="main-container">
    <!-- Success Banner if redirected from checkout -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <span>🎉 <strong>Congratulations!</strong> Your order has been placed successfully and payment is processed.</span>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 class="section-title" style="margin-bottom: 0.25rem;">📍 Tracking Order #<?= 1000 + $order['id']; ?></h2>
            <p style="color: var(--text-light); font-size: 0.9rem;">Invoice Reference: <strong><?= htmlspecialchars($order['payment_id']); ?></strong></p>
        </div>
        
        <!-- Simulate Status Transition Controller for testing -->
        <form method="POST" action="">
            <button type="submit" name="advance_status" class="btn btn-outline" style="border-color: var(--accent-hover); color: var(--accent-hover); font-weight: 700;">
                🔄 Simulate Next Dispatch Step
            </button>
        </form>
    </div>

    <!-- Visual Tracking Progress Stepper -->
    <div style="background-color: white; border-radius: 12px; border: 1px solid var(--border-color); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
        <div class="tracking-stepper">
            <!-- Active green progress path bar -->
            <div class="tracking-progress-bar" style="width: <?= $progress_width; ?>%;"></div>
            
            <!-- Step 1: Placed -->
            <div class="step-item <?= $step_completed[1] ? 'completed' : ($step_active[1] ? 'active' : ''); ?>">
                <div class="step-circle">1</div>
                <div class="step-label">Order Placed</div>
                <div class="step-date">Step Completed</div>
            </div>

            <!-- Step 2: Processing -->
            <div class="step-item <?= $step_completed[2] ? 'completed' : ($step_active[2] ? 'active' : ''); ?>">
                <div class="step-circle">2</div>
                <div class="step-label">Processing</div>
                <div class="step-date">Quality Checking</div>
            </div>

            <!-- Step 3: Shipped -->
            <div class="step-item <?= $step_completed[3] ? 'completed' : ($step_active[3] ? 'active' : ''); ?>">
                <div class="step-circle">3</div>
                <div class="step-label">Dispatched</div>
                <div class="step-date">In Logistics</div>
            </div>

            <!-- Step 4: Delivered -->
            <div class="step-item <?= $step_completed[4] ? 'completed' : ($step_active[4] ? 'active' : ''); ?>">
                <div class="step-circle">4</div>
                <div class="step-label">Delivered</div>
                <div class="step-date">At Farm Location</div>
            </div>
        </div>
    </div>

    <div class="cart-layout">
        <!-- Left Side: Order Items List (Receipt) -->
        <div class="cart-items-panel">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Invoice Receipt Summary</h3>
            
            <?php foreach ($order_items as $item): ?>
                <?php
                $emoji = '📦';
                if ($item['category'] === 'Seeds') $emoji = '🌱';
                elseif ($item['category'] === 'Fertilizers') $emoji = '🧪';
                elseif ($item['category'] === 'Pesticides') $emoji = '🛢️';
                elseif ($item['category'] === 'Tools') $emoji = '🔧';
                ?>
                <div class="cart-item-row" style="padding: 1rem 0;">
                    <div class="cart-item-thumb" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <span><?= $emoji; ?></span>
                    </div>
                    <div class="cart-item-info">
                        <h4 style="font-size: 0.95rem; font-weight: 600;"><?= htmlspecialchars($item['name']); ?></h4>
                        <span style="font-size: 0.75rem; color: var(--text-light);">Category: <?= htmlspecialchars($item['category']); ?></span>
                    </div>
                    <div style="text-align: right; min-width: 100px;">
                        <span style="font-weight: 700; font-size: 0.95rem;">$<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
                        <div style="font-size: 0.75rem; color: var(--text-light);">$<?= number_format($item['price'], 2); ?> x <?= $item['quantity']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Right Side: Shipping & Cost Breakdown Details -->
        <div class="cart-summary-panel">
            <h3 class="summary-title">Delivery Coordinates</h3>
            
            <div style="font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">
                <p><strong>Recipient Phone:</strong><br><?= htmlspecialchars($order['phone']); ?></p>
                <p style="margin-top: 0.75rem;"><strong>Shipping Destination:</strong><br><?= htmlspecialchars($order['shipping_address']); ?></p>
                <p style="margin-top: 0.75rem;"><strong>Payment Method:</strong><br><?= htmlspecialchars($order['payment_method']); ?></p>
                <p style="margin-top: 0.75rem;"><strong>Transaction Status:</strong><br><span class="status-badge status-paid">Transaction Approved (Paid)</span></p>
            </div>

            <h3 class="summary-title" style="margin-top: 1.5rem; font-size: 1.1rem;">Financial Breakdown</h3>
            <div class="summary-row" style="font-size: 0.85rem;">
                <span>Total Amount Transfered:</span>
                <span style="font-weight: 800; font-size: 1.1rem; color: var(--primary-color);">$<?= number_format($order['total_amount'], 2); ?></span>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <a href="dashboard.php" class="btn btn-outline btn-full">⬅️ Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php
include('../includes/footer.php');
?>
