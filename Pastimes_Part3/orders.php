<?php
include 'helpers.php';
include 'DBConn.php';
requireUserLogin();
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery']) && $user['role'] === 'seller') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $deliveryStatus = trim($_POST['delivery_status'] ?? 'processing');
    $stmt = $conn->prepare('UPDATE tblOrder o JOIN tblClothes c ON o.clothes_id = c.clothes_id SET o.delivery_status = ? WHERE o.order_id = ? AND c.seller_id = ?');
    $stmt->bind_param('sii', $deliveryStatus, $orderId, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Delivery status updated.');
    header('Location: orders.php');
    exit;
}

$purchaseStmt = $conn->prepare('SELECT o.*, c.title, c.brand, a.street_address, a.city, a.province, a.postal_code FROM tblOrder o JOIN tblClothes c ON o.clothes_id = c.clothes_id LEFT JOIN tblAddress a ON o.delivery_address_id = a.address_id WHERE o.buyer_id = ? ORDER BY o.order_date DESC');
$purchaseStmt->bind_param('i', $user['user_id']);
$purchaseStmt->execute();
$purchases = $purchaseStmt->get_result();

$sales = null;
if ($user['role'] === 'seller') {
    $salesStmt = $conn->prepare('SELECT o.*, c.title, u.full_name AS buyer_name FROM tblOrder o JOIN tblClothes c ON o.clothes_id = c.clothes_id JOIN tblUser u ON o.buyer_id = u.user_id WHERE c.seller_id = ? ORDER BY o.order_date DESC');
    $salesStmt->bind_param('i', $user['user_id']);
    $salesStmt->execute();
    $sales = $salesStmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Orders | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Orders</div>
    <div class="navlinks">
        <a href="dashboard.php">Browse</a>
        <a href="cart.php">Cart</a>
        <a href="messages.php">Messages</a>
        <?php if ($user['role'] === 'seller'): ?><a href="seller_dashboard.php">Seller Dashboard</a><?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <?php renderFlash(); ?>
    <div class="hero small-hero">
        <h1>Order tracking</h1>
        <p>Buyers can monitor purchases and sellers can update delivery progress.</p>
    </div>

    <div class="card">
        <h2>My purchases</h2>
        <table>
            <tr>
                <th>Order #</th>
                <th>Item</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Delivery</th>
                <th>Address</th>
            </tr>
            <?php while ($order = $purchases->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$order['order_id'] ?></td>
                    <td><?= esc($order['title']) ?> (<?= esc($order['brand']) ?>)</td>
                    <td><?= formatPrice($order['total_amount']) ?></td>
                    <td><?= esc($order['payment_status']) ?></td>
                    <td><?= esc($order['delivery_status']) ?></td>
                    <td><?= esc(($order['street_address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['province'] ?? '') . ' ' . ($order['postal_code'] ?? '')) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <?php if ($user['role'] === 'seller' && $sales): ?>
        <div class="card">
            <h2>Sales orders</h2>
            <table>
                <tr>
                    <th>Order #</th>
                    <th>Buyer</th>
                    <th>Item</th>
                    <th>Total</th>
                    <th>Delivery Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($sale = $sales->fetch_assoc()): ?>
                    <tr>
                        <form method="post">
                            <td><?= (int)$sale['order_id'] ?><input type="hidden" name="order_id" value="<?= (int)$sale['order_id'] ?>"></td>
                            <td><?= esc($sale['buyer_name']) ?></td>
                            <td><?= esc($sale['title']) ?></td>
                            <td><?= formatPrice($sale['total_amount']) ?></td>
                            <td>
                                <select name="delivery_status">
                                    <option value="processing" <?= $sale['delivery_status'] === 'processing' ? 'selected' : '' ?>>processing</option>
                                    <option value="packed" <?= $sale['delivery_status'] === 'packed' ? 'selected' : '' ?>>packed</option>
                                    <option value="shipped" <?= $sale['delivery_status'] === 'shipped' ? 'selected' : '' ?>>shipped</option>
                                    <option value="delivered" <?= $sale['delivery_status'] === 'delivered' ? 'selected' : '' ?>>delivered</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn secondary" type="submit" name="update_delivery" value="1">Update</button>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
