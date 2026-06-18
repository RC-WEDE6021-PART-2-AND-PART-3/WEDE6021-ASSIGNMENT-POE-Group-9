<?php
include 'helpers.php';
include 'DBConn.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$username = '';
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $hash = md5($password);

    $stmt = $conn->prepare('SELECT * FROM tblUser WHERE username = ? AND email = ? AND password_hash = ? LIMIT 1');
    $stmt->bind_param('sss', $username, $email, $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        setFlash('success', 'Welcome back, ' . $user['full_name'] . '.');
        header('Location: dashboard.php');
        exit;
    } else {
        $message = 'Invalid login details. Please check your username, email and password.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pastimes Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Part 3</div>
    <div class="navlinks">
        <a href="register.php">Register</a>
        <a href="admin_login.php">Admin</a>
        <a href="loadClothingStore.php">Reload DB</a>
    </div>
</div>
<div class="container two-column">
    <div class="hero">
        <h1>Second-hand fashion marketplace</h1>
        <p>Buy, sell and manage second-hand clothing on a clean, mobile-friendly web application.</p>
        <ul class="feature-list">
            <li>Browse approved clothes with categories, brand and price.</li>
            <li>Use the cart and checkout flow, then continue shopping.</li>
            <li>Send selling requests with image uploads and descriptions.</li>
            <li>Chat with the administrator for delivery or item issues.</li>
        </ul>
    </div>

    <form method="post">
        <h2>Customer / Seller Login</h2>
        <?php if ($message): ?>
            <div class="alert error"><?= esc($message) ?></div>
        <?php endif; ?>
        <label>Username</label>
        <input type="text" name="username" required value="<?= esc($username) ?>">

        <label>Email address</label>
        <input type="email" name="email" required value="<?= esc($email) ?>">

        <label>Password</label>
        <input type="password" name="password" required>

        <br><br>
        <button class="btn" type="submit">Login</button>
        <a class="btn secondary" href="register.php">Create Account</a>

        <div class="demo-box">
            <p><strong>Buyer demo:</strong> johndoe / john@example.co.za / abc</p>
            <p><strong>Seller demo:</strong> seller1 / neo@example.co.za / abc</p>
            <p><strong>MD5 sample:</strong> 29ef52e7563626a96cea7f4b4085c124</p>
        </div>
    </form>
</div>
</body>
</html>
