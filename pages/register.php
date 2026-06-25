<?php
$page_title = "Farmer Registration";
include('../includes/db.php');  // Database connection
session_start();

// If already logged in, redirect to store
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error_message = '';

if (isset($_POST['register'])) {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email) || empty($username) || empty($password) || empty($name) || empty($phone) || empty($address)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email address format.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if the email or username already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $error_message = "Email or Username is already registered!";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'farmer'; // Role is farmer

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password, $name, $phone, $address, $role]);

            // Log the user in after successful registration
            $_SESSION['user_id'] = $conn->lastInsertId();
            header("Location: ../index.php"); // Redirect to the homepage
            exit();
        }
    }
}

include('../includes/header.php');
?>

<div class="main-container">
    <div class="form-card">
        <h2 class="form-title">Farmer Register</h2>
        <p class="form-subtitle">Join AgriMart today to order urea, gromor, pesticides, seeds, and track your shipments.</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" placeholder="e.g. Ramesh Kumar" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" placeholder="e.g. ramesh123" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="e.g. ramesh@farm.com" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-control" type="tel" id="phone" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="e.g. 9876543210" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Delivery Address / Farm Location</label>
                <textarea class="form-control" id="address" name="address" placeholder="Enter Village, Mandal, District and State for deliveries" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" placeholder="Create password" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Retype password" required>
            </div>

            <button type="submit" name="register" class="btn btn-primary btn-full">Register Account</button>
        </form>

        <div class="form-footer-link">
            Already have an account? <a href="login.php">Farmer Login</a>
        </div>
    </div>
</div>

<?php
include('../includes/footer.php');
?>