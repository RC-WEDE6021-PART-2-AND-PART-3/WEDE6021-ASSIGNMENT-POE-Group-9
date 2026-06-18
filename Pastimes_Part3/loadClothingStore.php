<?php
include 'DBConn.php';

$sql = file_get_contents(__DIR__ . '/sql/myClothingStore.sql');
if ($sql === false) {
    die('Could not read SQL file.');
}

if (!$conn->multi_query($sql)) {
    die('Failed to load database: ' . $conn->error);
}

do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pastimes Database Load</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Part 3</div>
    <div class="navlinks">
        <a href="index.php">Customer Login</a>
        <a href="admin_login.php">Admin Login</a>
    </div>
</div>
<div class="container">
    <div class="hero">
        <h1>Database ready</h1>
        <p>All required tables were created and sample data was loaded successfully.</p>
    </div>
    <div class="alert success">Tables loaded: tblUser, tblAdmin, tblClothes, tblCart, tblAddress, tblOrder, tblMessage.</div>
    <div class="card single-card">
        <h3>Demo logins</h3>
        <p><strong>Buyer:</strong> johndoe / john@example.co.za / abc</p>
        <p><strong>Seller:</strong> seller1 / neo@example.co.za / abc</p>
        <p><strong>Admin:</strong> admin / admin@pastimes.co.za / abc</p>
    </div>
</div>
</body>
</html>
