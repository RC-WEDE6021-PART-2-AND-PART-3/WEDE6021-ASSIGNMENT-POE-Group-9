<?php
include 'helpers.php';
include 'DBConn.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $full = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $passwordHash = md5($_POST['password'] ?? 'abc');
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'] ?? 'verified';
        $stmt = $conn->prepare('INSERT INTO tblUser (full_name, username, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssss', $full, $username, $email, $phone, $passwordHash, $role, $status);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'User added successfully.');
    }

    if (isset($_POST['update_user'])) {
        $id = (int)($_POST['user_id'] ?? 0);
        $full = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'] ?? 'verified';
        $stmt = $conn->prepare('UPDATE tblUser SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?');
        $stmt->bind_param('sssssi', $full, $email, $phone, $role, $status, $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'User updated successfully.');
    }

    if (isset($_POST['add_clothing'])) {
        $sellerId = (int)($_POST['seller_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $size = trim($_POST['size'] ?? '');
        $condition = trim($_POST['item_condition'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $status = $_POST['status'] ?? 'approved';
        $imagePath = saveUploadedImage('item_image');
        $stmt = $conn->prepare('INSERT INTO tblClothes (seller_id, title, category, brand, size, item_condition, description, price, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssssdss', $sellerId, $title, $category, $brand, $size, $condition, $description, $price, $imagePath, $status);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Clothing item added successfully.');
    }

    if (isset($_POST['update_clothing'])) {
        $id = (int)($_POST['clothes_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $size = trim($_POST['size'] ?? '');
        $condition = trim($_POST['item_condition'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $status = $_POST['status'] ?? 'approved';
        $stmt = $conn->prepare('UPDATE tblClothes SET title = ?, brand = ?, category = ?, size = ?, item_condition = ?, price = ?, status = ? WHERE clothes_id = ?');
        $stmt->bind_param('sssssdsi', $title, $brand, $category, $size, $condition, $price, $status, $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Clothing item updated successfully.');
    }

    if (isset($_POST['update_order'])) {
        $id = (int)($_POST['order_id'] ?? 0);
        $payment = $_POST['payment_status'] ?? 'pending';
        $delivery = $_POST['delivery_status'] ?? 'processing';
        $stmt = $conn->prepare('UPDATE tblOrder SET payment_status = ?, delivery_status = ? WHERE order_id = ?');
        $stmt->bind_param('ssi', $payment, $delivery, $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Order updated successfully.');
    }

    if (isset($_POST['reply_message'])) {
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? 'Admin reply');
        $messageText = trim($_POST['message_text'] ?? '');
        $relatedOrderId = (int)($_POST['related_order_id'] ?? 0);
        $relatedClothesId = (int)($_POST['related_clothes_id'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO tblMessage (sender_role, sender_id, receiver_role, receiver_id, related_order_id, related_clothes_id, subject, message_text) VALUES ('admin', ?, 'user', ?, ?, ?, ?, ?)");
        $stmt->bind_param('iiiiss', $_SESSION['admin_id'], $receiverId, $relatedOrderId, $relatedClothesId, $subject, $messageText);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Reply sent successfully.');
    }

    header('Location: admin_dashboard.php');
    exit;
}

if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    $stmt = $conn->prepare('DELETE FROM tblUser WHERE user_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'User deleted successfully.');
    header('Location: admin_dashboard.php');
    exit;
}

if (isset($_GET['delete_clothing'])) {
    $id = (int)$_GET['delete_clothing'];
    $stmt = $conn->prepare('DELETE FROM tblClothes WHERE clothes_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Clothing item deleted successfully.');
    header('Location: admin_dashboard.php');
    exit;
}

$stats = [
    'pending_sellers' => ($conn->query("SELECT COUNT(*) total FROM tblUser WHERE role='seller' AND status='pending'")->fetch_assoc()['total'] ?? 0),
    'pending_listings' => ($conn->query("SELECT COUNT(*) total FROM tblClothes WHERE status='pending'")->fetch_assoc()['total'] ?? 0),
    'active_listings' => ($conn->query("SELECT COUNT(*) total FROM tblClothes WHERE status='approved'")->fetch_assoc()['total'] ?? 0),
    'sold_items' => ($conn->query("SELECT COUNT(*) total FROM tblClothes WHERE status='sold'")->fetch_assoc()['total'] ?? 0),
    'orders' => ($conn->query("SELECT COUNT(*) total FROM tblOrder")->fetch_assoc()['total'] ?? 0),
];

$users = $conn->query('SELECT * FROM tblUser ORDER BY user_id DESC');
$sellers = $conn->query("SELECT user_id, full_name FROM tblUser WHERE role='seller' ORDER BY full_name");
$clothes = $conn->query('SELECT c.*, u.full_name FROM tblClothes c JOIN tblUser u ON c.seller_id = u.user_id ORDER BY c.created_at DESC');
$orders = $conn->query('SELECT o.*, c.title, u.full_name AS buyer_name FROM tblOrder o JOIN tblClothes c ON o.clothes_id = c.clothes_id JOIN tblUser u ON o.buyer_id = u.user_id ORDER BY o.order_date DESC');
$messages = $conn->query("SELECT m.*, 
    CASE WHEN m.sender_role='admin' THEN a.full_name ELSE su.full_name END AS sender_name,
    CASE WHEN m.receiver_role='admin' THEN ra.full_name ELSE ru.full_name END AS receiver_name,
    ru.user_id AS target_user_id
    FROM tblMessage m
    LEFT JOIN tblUser su ON m.sender_role='user' AND m.sender_id = su.user_id
    LEFT JOIN tblAdmin a ON m.sender_role='admin' AND m.sender_id = a.admin_id
    LEFT JOIN tblUser ru ON m.receiver_role='user' AND m.receiver_id = ru.user_id
    LEFT JOIN tblAdmin ra ON m.receiver_role='admin' AND m.receiver_id = ra.admin_id
    ORDER BY m.created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Admin Dashboard</div>
    <div class="navlinks">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <?php renderFlash(); ?>
    <div class="hero">
        <h1>Welcome, <?= esc($_SESSION['admin_name']) ?></h1>
        <p>Manage users, clothing, orders and communication between sellers and buyers.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h3><?= (int)$stats['pending_sellers'] ?></h3><p>Pending sellers</p></div>
        <div class="stat-card"><h3><?= (int)$stats['pending_listings'] ?></h3><p>Pending listings</p></div>
        <div class="stat-card"><h3><?= (int)$stats['active_listings'] ?></h3><p>Active listings</p></div>
        <div class="stat-card"><h3><?= (int)$stats['sold_items'] ?></h3><p>Sold items</p></div>
        <div class="stat-card"><h3><?= (int)$stats['orders'] ?></h3><p>Total orders</p></div>
    </div>

    <div class="two-column-layout">
        <form method="post">
            <h2>Add user</h2>
            <input type="hidden" name="add_user" value="1">
            <label>Full name</label><input name="full_name" required>
            <label>Username</label><input name="username" required>
            <label>Email</label><input type="email" name="email" required>
            <label>Phone</label><input name="phone" required>
            <label>Password</label><input type="password" name="password" required>
            <label>Role</label>
            <select name="role">
                <option value="customer">customer</option>
                <option value="seller">seller</option>
            </select>
            <label>Status</label>
            <select name="status">
                <option value="verified">verified</option>
                <option value="pending">pending</option>
                <option value="rejected">rejected</option>
            </select>
            <br><br>
            <button class="btn" type="submit">Add User</button>
        </form>

        <form method="post" enctype="multipart/form-data">
            <h2>Add clothing</h2>
            <input type="hidden" name="add_clothing" value="1">
            <label>Seller</label>
            <select name="seller_id" required>
                <?php while ($seller = $sellers->fetch_assoc()): ?>
                    <option value="<?= (int)$seller['user_id'] ?>"><?= esc($seller['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <label>Title</label><input name="title" required>
            <label>Category</label><input name="category" required>
            <label>Brand</label><input name="brand" required>
            <label>Size</label><input name="size" required>
            <label>Condition</label><input name="item_condition" required>
            <label>Description</label><textarea name="description" required></textarea>
            <label>Price</label><input type="number" step="0.01" name="price" required>
            <label>Status</label>
            <select name="status">
                <option value="approved">approved</option>
                <option value="pending">pending</option>
                <option value="hidden">hidden</option>
                <option value="sold">sold</option>
                <option value="rejected">rejected</option>
            </select>
            <label>Image</label><input type="file" name="item_image" accept="image/*">
            <br><br>
            <button class="btn secondary" type="submit">Add Clothing</button>
        </form>
    </div>

    <div class="card admin-table-card">
        <h2>Manage users</h2>
        <table>
            <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr>
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <form method="post">
                        <td><?= (int)$u['user_id'] ?><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>"><input type="hidden" name="update_user" value="1"></td>
                        <td><input name="full_name" value="<?= esc($u['full_name']) ?>"></td>
                        <td><?= esc($u['username']) ?></td>
                        <td><input name="email" value="<?= esc($u['email']) ?>"></td>
                        <td><input name="phone" value="<?= esc($u['phone']) ?>"></td>
                        <td>
                            <select name="role">
                                <option value="customer" <?= $u['role']==='customer' ? 'selected' : '' ?>>customer</option>
                                <option value="seller" <?= $u['role']==='seller' ? 'selected' : '' ?>>seller</option>
                            </select>
                        </td>
                        <td>
                            <select name="status">
                                <option value="verified" <?= $u['status']==='verified' ? 'selected' : '' ?>>verified</option>
                                <option value="pending" <?= $u['status']==='pending' ? 'selected' : '' ?>>pending</option>
                                <option value="rejected" <?= $u['status']==='rejected' ? 'selected' : '' ?>>rejected</option>
                            </select>
                        </td>
                        <td class="actions">
                            <button class="btn secondary" type="submit">Update</button>
                            <a class="btn danger" href="?delete_user=<?= (int)$u['user_id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </form>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="card clothing-manager-card">
        <h2>Manage clothing</h2>
        <p class="section-note">Review seller requests, approve listings, edit clothing details and delete items without horizontal scrolling.</p>

        <div class="clothing-admin-list">
            <?php while ($item = $clothes->fetch_assoc()): ?>
                <form method="post" class="clothing-admin-item">
                    <input type="hidden" name="clothes_id" value="<?= (int)$item['clothes_id'] ?>">
                    <input type="hidden" name="update_clothing" value="1">

                    <div class="item-topline">
                        <div>
                            <span class="item-id">#<?= (int)$item['clothes_id'] ?></span>
                            <strong><?= esc($item['title']) ?></strong>
                            <small>Seller: <?= esc($item['full_name']) ?></small>
                        </div>
                        <span class="badge <?= $item['status']==='pending' ? 'pending' : '' ?>"><?= esc($item['status']) ?></span>
                    </div>

                    <div class="clothing-edit-grid">
                        <div>
                            <label>Title</label>
                            <input name="title" value="<?= esc($item['title']) ?>">
                        </div>

                        <div>
                            <label>Category</label>
                            <input name="category" value="<?= esc($item['category']) ?>">
                        </div>

                        <div>
                            <label>Brand</label>
                            <input name="brand" value="<?= esc($item['brand']) ?>">
                        </div>

                        <div>
                            <label>Size</label>
                            <input name="size" value="<?= esc($item['size']) ?>">
                        </div>

                        <div>
                            <label>Condition</label>
                            <input name="item_condition" value="<?= esc($item['item_condition']) ?>">
                        </div>

                        <div>
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" value="<?= esc($item['price']) ?>">
                        </div>

                        <div>
                            <label>Status</label>
                            <select name="status">
                                <option value="approved" <?= $item['status']==='approved' ? 'selected' : '' ?>>approved</option>
                                <option value="pending" <?= $item['status']==='pending' ? 'selected' : '' ?>>pending</option>
                                <option value="hidden" <?= $item['status']==='hidden' ? 'selected' : '' ?>>hidden</option>
                                <option value="sold" <?= $item['status']==='sold' ? 'selected' : '' ?>>sold</option>
                                <option value="rejected" <?= $item['status']==='rejected' ? 'selected' : '' ?>>rejected</option>
                            </select>
                        </div>

                        <div class="clothing-actions">
                            <label>Actions</label>
                            <div class="actions">
                                <button class="btn secondary" type="submit">Update</button>
                                <a class="btn danger" href="?delete_clothing=<?= (int)$item['clothes_id'] ?>" onclick="return confirm('Delete this clothing item?')">Delete</a>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="card admin-table-card">
        <h2>Manage orders</h2>
        <table>
            <tr><th>Order #</th><th>Buyer</th><th>Item</th><th>Total</th><th>Payment</th><th>Delivery</th><th>Action</th></tr>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <form method="post">
                        <td><?= (int)$order['order_id'] ?><input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>"><input type="hidden" name="update_order" value="1"></td>
                        <td><?= esc($order['buyer_name']) ?></td>
                        <td><?= esc($order['title']) ?></td>
                        <td><?= formatPrice($order['total_amount']) ?></td>
                        <td>
                            <select name="payment_status">
                                <option value="pending" <?= $order['payment_status']==='pending' ? 'selected' : '' ?>>pending</option>
                                <option value="paid" <?= $order['payment_status']==='paid' ? 'selected' : '' ?>>paid</option>
                                <option value="failed" <?= $order['payment_status']==='failed' ? 'selected' : '' ?>>failed</option>
                            </select>
                        </td>
                        <td>
                            <select name="delivery_status">
                                <option value="processing" <?= $order['delivery_status']==='processing' ? 'selected' : '' ?>>processing</option>
                                <option value="packed" <?= $order['delivery_status']==='packed' ? 'selected' : '' ?>>packed</option>
                                <option value="shipped" <?= $order['delivery_status']==='shipped' ? 'selected' : '' ?>>shipped</option>
                                <option value="delivered" <?= $order['delivery_status']==='delivered' ? 'selected' : '' ?>>delivered</option>
                            </select>
                        </td>
                        <td><button class="btn secondary" type="submit">Update</button></td>
                    </form>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="card">
        <h2>Communication with buyers and sellers</h2>
        <?php while ($message = $messages->fetch_assoc()): ?>
            <div class="message-box">
                <div class="message-meta">
                    <strong><?= esc($message['sender_name'] ?? 'Unknown') ?></strong>
                    <span>to <?= esc($message['receiver_name'] ?? 'Unknown') ?></span>
                </div>
                <p><strong>Subject:</strong> <?= esc($message['subject']) ?></p>
                <p><?= nl2br(esc($message['message_text'])) ?></p>
                <small>Order ID: <?= (int)$message['related_order_id'] ?> | Item ID: <?= (int)$message['related_clothes_id'] ?> | <?= esc($message['created_at']) ?></small>

                <?php $replyToUserId = $message['sender_role'] === 'user' ? (int)$message['sender_id'] : (int)$message['receiver_id']; ?>
                <?php if ($replyToUserId > 0): ?>
                    <form method="post" class="reply-form">
                        <input type="hidden" name="reply_message" value="1">
                        <input type="hidden" name="receiver_id" value="<?= $replyToUserId ?>">
                        <input type="hidden" name="related_order_id" value="<?= (int)$message['related_order_id'] ?>">
                        <input type="hidden" name="related_clothes_id" value="<?= (int)$message['related_clothes_id'] ?>">
                        <label>Reply subject</label>
                        <input name="subject" value="Re: <?= esc($message['subject']) ?>">
                        <label>Reply</label>
                        <textarea name="message_text" required></textarea>
                        <br>
                        <button class="btn secondary" type="submit">Send Reply</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>
