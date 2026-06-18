<?php
include 'helpers.php';
include 'DBConn.php';
requireUserLogin();
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: logout.php');
    exit;
}

$buyerId = (int)$user['user_id'];
$addresses = getUserAddresses($conn, $buyerId);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $street = trim($_POST['street_address'] ?? '');
    $suburb = trim($_POST['suburb'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');

    $stmt = $conn->prepare('INSERT INTO tblAddress (user_id, street_address, suburb, city, province, postal_code) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssss', $buyerId, $street, $suburb, $city, $province, $postal);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Delivery address saved.');
    header('Location: checkout.php');
    exit;
}

$stmt = $conn->prepare('SELECT ct.*, c.title, c.price, c.status FROM tblCart ct JOIN tblClothes c ON ct.clothes_id = c.clothes_id WHERE ct.buyer_id = ?');
$stmt->bind_param('i', $buyerId);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $total += ((float)$row['price'] * (int)$row['quantity']);
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!$cartItems) {
        $error = 'Your cart is empty.';
    } else {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'Card');
        $addressCheck = $conn->prepare('SELECT address_id FROM tblAddress WHERE address_id = ? AND user_id = ? LIMIT 1');
        $addressCheck->bind_param('ii', $addressId, $buyerId);
        $addressCheck->execute();
        $validAddress = $addressCheck->get_result()->fetch_assoc();
        $addressCheck->close();

        if (!$validAddress) {
            $error = 'Please select a valid delivery address.';
        } else {
            foreach ($cartItems as $item) {
                if ($item['status'] !== 'approved') {
                    continue;
                }
                $amount = (float)$item['price'] * (int)$item['quantity'];
                $notes = 'Payment method: ' . $paymentMethod;
                $order = $conn->prepare('INSERT INTO tblOrder (buyer_id, clothes_id, quantity, total_amount, payment_status, delivery_status, delivery_address_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $paymentStatus = 'paid';
                $deliveryStatus = 'processing';
                $order->bind_param('iiidssis', $buyerId, $item['clothes_id'], $item['quantity'], $amount, $paymentStatus, $deliveryStatus, $addressId, $notes);
                $order->execute();
                $order->close();

                $update = $conn->prepare("UPDATE tblClothes SET status = 'sold' WHERE clothes_id = ?");
                $update->bind_param('i', $item['clothes_id']);
                $update->execute();
                $update->close();
            }
            $clear = $conn->prepare('DELETE FROM tblCart WHERE buyer_id = ?');
            $clear->bind_param('i', $buyerId);
            $clear->execute();
            $clear->close();

            setFlash('success', 'Order placed successfully. The admin and sellers can now track delivery progress.');
            header('Location: orders.php');
            exit;
        }
    }
}

$addresses = getUserAddresses($conn, $buyerId);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes</div>
    <div class="navlinks">
        <a href="dashboard.php">Browse</a>
        <a href="cart.php">Cart</a>
        <a href="orders.php">Orders</a>
        <a href="messages.php">Messages</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container two-column">
    <div>
        <div class="hero small-hero">
            <h1>Checkout</h1>
            <p>Capture a delivery address, choose a payment method and confirm your order.</p>
        </div>
        <?php renderFlash(); ?>
        <?php if ($error): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>
        <form method="post">
            <h3>Saved delivery addresses</h3>
            <?php if (!$addresses): ?>
                <p>No address saved yet. Add one below first.</p>
            <?php else: ?>
                <?php foreach ($addresses as $address): ?>
                    <label class="radio-card">
                        <input type="radio" name="address_id" value="<?= (int)$address['address_id'] ?>" required>
                        <?= esc($address['street_address']) ?>, <?= esc($address['suburb']) ?>, <?= esc($address['city']) ?>, <?= esc($address['province']) ?>, <?= esc($address['postal_code']) ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>

            <label>Payment method</label>
            <select name="payment_method">
                <option>Card</option>
                <option>EFT</option>
                <option>Cash on Delivery</option>
            </select>

            <br><br>
            <button class="btn" type="submit" name="place_order" value="1">Place Order</button>
        </form>

        <form method="post">
            <h3>Add delivery address</h3>
            <label>Street address</label>
            <input name="street_address" required>
            <label>Suburb</label>
            <input name="suburb" required>
            <label>City</label>
            <input name="city" required>
            <label>Province</label>
            <input name="province" required>
            <label>Postal code</label>
            <input name="postal_code" required>
            <br><br>
            <button class="btn secondary" type="submit" name="save_address" value="1">Save Address</button>
        </form>
    </div>

    <div class="card sticky-card">
        <h3>Order summary</h3>
        <?php if (!$cartItems): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
                <div class="summary-line">
                    <span><?= esc($item['title']) ?> x <?= (int)$item['quantity'] ?></span>
                    <strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
                </div>
            <?php endforeach; ?>
            <hr>
            <div class="summary-line total-line">
                <span>Total</span>
                <strong><?= formatPrice($total) ?></strong>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
