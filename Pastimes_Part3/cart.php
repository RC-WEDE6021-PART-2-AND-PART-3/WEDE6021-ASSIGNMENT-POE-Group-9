<?php
include 'helpers.php';
include 'DBConn.php';
requireUserLogin();
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] ?? [] as $cartId => $qty) {
            $cartId = (int)$cartId;
            $qty = max(1, (int)$qty);
            $stmt = $conn->prepare('UPDATE tblCart SET quantity = ? WHERE cart_id = ? AND buyer_id = ?');
            $stmt->bind_param('iii', $qty, $cartId, $user['user_id']);
            $stmt->execute();
            $stmt->close();
        }
        setFlash('success', 'Cart updated successfully.');
    }

    if (isset($_POST['remove_item'])) {
        $cartId = (int)($_POST['remove_item'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM tblCart WHERE cart_id = ? AND buyer_id = ?');
        $stmt->bind_param('ii', $cartId, $user['user_id']);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Item removed from cart.');
    }

    header('Location: cart.php');
    exit;
}

$stmt = $conn->prepare("SELECT ct.*, c.title, c.price, c.brand, c.image_path, c.status FROM tblCart ct JOIN tblClothes c ON ct.clothes_id = c.clothes_id WHERE ct.buyer_id = ? ORDER BY ct.added_at DESC");
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += ((float)$row['price'] * (int)$row['quantity']);
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes</div>
    <div class="navlinks">
        <a href="dashboard.php">Continue Shopping</a>
        <a href="orders.php">Orders</a>
        <a href="messages.php">Messages</a>
        <?php if ($user['role'] === 'seller'): ?><a href="seller_dashboard.php">Seller Dashboard</a><?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <?php renderFlash(); ?>
    <div class="hero small-hero">
        <h1>Your shopping cart</h1>
        <p>Edit cart items, remove unwanted products, then proceed to checkout or continue shopping.</p>
    </div>
    <?php if (!$items): ?>
        <div class="card single-card">
            <h3>Your cart is empty</h3>
            <p>You can return to the catalogue and keep shopping.</p>
            <a class="btn" href="dashboard.php">Browse Items</a>
        </div>
    <?php else: ?>
        <form method="post">
            <table>
                <tr>
                    <th>Item</th>
                    <th>Brand</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= esc($item['title']) ?></td>
                        <td><?= esc($item['brand']) ?></td>
                        <td><?= esc($item['status']) ?></td>
                        <td><?= formatPrice($item['price']) ?></td>
                        <td><input type="number" min="1" name="quantities[<?= (int)$item['cart_id'] ?>]" value="<?= (int)$item['quantity'] ?>"></td>
                        <td><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                        <td>
                            <button class="btn secondary" name="update_cart" value="1" type="submit">Update</button>
                            <button class="btn danger" type="submit" name="remove_item" value="<?= (int)$item['cart_id'] ?>" onclick="return confirm('Remove this item from the cart?')">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </form>
        <div class="checkout-bar">
            <h3>Total: <?= formatPrice($total) ?></h3>
            <div class="actions">
                <a class="btn secondary" href="dashboard.php">Continue Shopping</a>
                <a class="btn" href="checkout.php">Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
