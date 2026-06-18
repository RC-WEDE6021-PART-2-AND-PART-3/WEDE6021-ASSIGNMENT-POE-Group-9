<?php
include 'helpers.php';
include 'DBConn.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT user_id FROM tblUser WHERE username = ? OR email = ? LIMIT 1');
        $check->bind_param('ss', $username, $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            $error = 'That username or email already exists.';
        } else {
            $status = $role === 'seller' ? 'pending' : 'verified';
            $hash = md5($password);
            $stmt = $conn->prepare('INSERT INTO tblUser (full_name, username, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssss', $fullName, $username, $email, $phone, $hash, $role, $status);
            if ($stmt->execute()) {
                $message = $role === 'seller'
                    ? 'Seller account created. Your seller profile is pending administrator approval, but you can already submit clothing requests.'
                    : 'Customer account created successfully. You can now log in.';
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes</div>
    <div class="navlinks">
        <a href="index.php">Login</a>
    </div>
</div>
<div class="container narrow">
    <?php if ($message): ?><div class="alert success"><?= esc($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>
    <form method="post">
        <h2>Create Account</h2>
        <label>Full name</label>
        <input name="full_name" required>

        <label>Username</label>
        <input name="username" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Phone number</label>
        <input name="phone" required>

        <label>Register as</label>
        <select name="role">
            <option value="customer">Customer / Buyer</option>
            <option value="seller">Seller</option>
        </select>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm password</label>
        <input type="password" name="confirm_password" required>

        <br><br>
        <button class="btn">Register</button>
    </form>
</div>
</body>
</html>
