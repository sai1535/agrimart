<?php
$page_title = "Farmer Dashboard";
include('../includes/db.php');
session_start();

// Ensure farmer session is active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve farmer user details
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$farmer = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Calculate metrics
// 1. Total spent
$stmt_spent = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ?");
$stmt_spent->execute([$user_id]);
$total_spent = (float)$stmt_spent->fetchColumn();

// 2. Total orders
$stmt_count = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt_count->execute([$user_id]);
$total_orders = (int)$stmt_count->fetchColumn();

// 3. Active shipments
$stmt_active = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND tracking_status != 'Delivered'");
$stmt_active->execute([$user_id]);
$active_orders = (int)$stmt_active->fetchColumn();

// Retrieve orders history list, including product names concatenated
$stmt_orders = $conn->prepare("SELECT o.*, GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as item_details 
                               FROM orders o 
                               LEFT JOIN order_items oi ON o.id = oi.order_id 
                               LEFT JOIN products p ON oi.product_id = p.id 
                               WHERE o.user_id = ? 
                               GROUP BY o.id 
                               ORDER BY o.id DESC");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

include('../includes/header.php');
?>

<div class="main-container">
    <h2 class="section-title">🚜 Farmer Dashboard</h2>
    <p style="color: var(--text-light); margin-top: -1rem; margin-bottom: 2rem;">Manage your agricultural orders, check shipment status, and update your farming coordinates.</p>

    <!-- Farmer Profile Card -->
    <div style="background-color: white; border-radius: 12px; border: 1px solid var(--border-color); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 0.5rem;">
        <h3 style="color: var(--primary-color); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; font-size: 1.25rem;">👨‍🌾 Farmer Profile</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 0.5rem;">
            <div>
                <p><strong>Name:</strong> <?= htmlspecialchars($farmer['name'] ?? $farmer['username']); ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($farmer['email']); ?></p>
            </div>
            <div>
                <p><strong>Phone:</strong> <?= htmlspecialchars($farmer['phone'] ?? 'Not specified'); ?></p>
                <p><strong>Farm/Delivery Address:</strong> <?= htmlspecialchars($farmer['address'] ?? 'Not specified'); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Metrics Row -->
    <div class="dashboard-grid">
        <!-- Metric: Total Orders -->
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <span class="stat-label">Total Orders</span>
                <span class="stat-value"><?= $total_orders; ?></span>
            </div>
        </div>

        <!-- Metric: Active Deliveries -->
        <div class="stat-card">
            <div class="stat-icon">🚚</div>
            <div class="stat-info">
                <span class="stat-label">Active Shipments</span>
                <span class="stat-value"><?= $active_orders; ?></span>
            </div>
        </div>

        <!-- Metric: Spending -->
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <span class="stat-label">Total Spent</span>
                <span class="stat-value">$<?= number_format($total_spent, 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Orders History -->
    <div class="orders-table-card">
        <h3 style="font-size: 1.25rem; color: var(--text-dark); margin-bottom: 1rem;">📜 Your Order History</h3>
        
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 2rem 0; color: var(--text-light);">
                <p style="font-size: 1rem;">You have not placed any orders yet.</p>
                <a href="../index.php" class="btn btn-primary" style="margin-top: 1rem; font-size: 0.85rem;">Browse Shop</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date Placed</th>
                            <th>Products Ordered</th>
                            <th>Total Amount</th>
                            <th>Payment</th>
                            <th>Delivery Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-weight: 700; font-family: monospace;">#<?= 1000 + $order['id']; ?></td>
                                <td style="font-size: 0.85rem; color: var(--text-light);"><?= date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td style="max-width: 300px; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($order['item_details'] ?? ''); ?>">
                                    <?= htmlspecialchars($order['item_details'] ?? 'N/A'); ?>
                                </td>
                                <td style="font-weight: 700; color: var(--primary-color);">$<?= number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-paid">Paid</span>
                                </td>
                                <td>
                                    <?php
                                    $status = $order['tracking_status'];
                                    $class = 'status-pending';
                                    if ($status === 'Shipped') $class = 'status-shipped';
                                    elseif ($status === 'Delivered') $class = 'status-delivered';
                                    ?>
                                    <span class="status-badge <?= $class; ?>"><?= htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                    <a href="order_status.php?order_id=<?= $order['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; border-color: var(--primary-color); color: var(--primary-color);">
                                        📍 Track Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include('../includes/footer.php');
?>
