<?php
include 'helpers.php';
include 'DBConn.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $hash = md5($password);

    $stmt = $conn->prepare('SELECT * FROM tblAdmin WHERE username = ? AND email = ? AND password_hash = ? LIMIT 1');
    $stmt->bind_param('sss', $username, $email, $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if ($admin) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $message = 'Invalid administrator login details.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Part 3 Admin</div>
    <div class="navlinks">
        <a href="index.php">Customer Login</a>
    </div>
</div>
<div class="container narrow">
    <?php if ($message): ?><div class="alert error"><?= esc($message) ?></div><?php endif; ?>
    <form method="post">
        <h2>Administrator Login</h2>
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <br><br>
        <button type="submit" class="btn">Login as Admin</button>
        <div class="demo-box"><strong>Demo admin:</strong> admin / admin@pastimes.co.za / abc</div>
    </form>
</div>
</body>
</html>
