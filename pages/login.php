<?php
$page_title = "Farmer Login";
include('../includes/db.php');  // Include the database connection
session_start();

// If already logged in, redirect to store
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error_message = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        // Prepare the SQL query to find user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id']; // Store user ID in session

            // Merge guest session cart into database cart if exists
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $prod_id => $qty) {
                    // Check if the product already exists in the farmer's database cart
                    $stmt_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt_cart->execute([$user['id'], $prod_id]);
                    $cart_item = $stmt_cart->fetch(PDO::FETCH_ASSOC);

                    if ($cart_item) {
                        // Update quantity (limit by stock is handled later, but let's accumulate)
                        $new_qty = $cart_item['quantity'] + $qty;
                        $stmt_upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                        $stmt_upd->execute([$new_qty, $cart_item['id']]);
                    } else {
                        // Insert new cart item
                        $stmt_ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                        $stmt_ins->execute([$user['id'], $prod_id, $qty]);
                    }
                }
                // Clear session cart
                unset($_SESSION['cart']);
            }

            header("Location: ../index.php"); // Redirect to the main page
            exit();
        } else {
            // Invalid login
            $error_message = "Invalid email or password.";
        }
    }
}

include('../includes/header.php');
?>

<div class="main-container">
    <div class="form-card">
        <h2 class="form-title">Farmer Login</h2>
        <p class="form-subtitle">Access your account to manage orders, browse farm supplies, and track deliveries.</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="e.g. name@domain.com" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" name="login" class="btn btn-primary btn-full">Login</button>
        </form>

        <div class="form-footer-link">
            New to AgriMart? <a href="register.php">Register as Farmer</a>
        </div>
    </div>
</div>

<?php
include('../includes/footer.php');
?>