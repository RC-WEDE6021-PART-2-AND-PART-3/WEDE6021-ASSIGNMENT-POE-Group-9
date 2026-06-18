<?php
include 'helpers.php';
include 'DBConn.php';
requireUserLogin();
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? 'General enquiry');
    $messageText = trim($_POST['message_text'] ?? '');
    $relatedOrderId = (int)($_POST['related_order_id'] ?? 0);
    $relatedClothesId = (int)($_POST['related_clothes_id'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO tblMessage (sender_role, sender_id, receiver_role, receiver_id, related_order_id, related_clothes_id, subject, message_text) VALUES ('user', ?, 'admin', 1, ?, ?, ?, ?)");
    $stmt->bind_param('iiiss', $user['user_id'], $relatedOrderId, $relatedClothesId, $subject, $messageText);
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Message sent to the administrator.');
    header('Location: messages.php');
    exit;
}

$stmt = $conn->prepare("SELECT m.*, 
    CASE WHEN m.sender_role = 'admin' THEN a.full_name ELSE su.full_name END AS sender_name,
    CASE WHEN m.receiver_role = 'admin' THEN ra.full_name ELSE ru.full_name END AS receiver_name
    FROM tblMessage m
    LEFT JOIN tblUser su ON m.sender_role = 'user' AND m.sender_id = su.user_id
    LEFT JOIN tblAdmin a ON m.sender_role = 'admin' AND m.sender_id = a.admin_id
    LEFT JOIN tblUser ru ON m.receiver_role = 'user' AND m.receiver_id = ru.user_id
    LEFT JOIN tblAdmin ra ON m.receiver_role = 'admin' AND m.receiver_id = ra.admin_id
    WHERE (m.sender_role = 'user' AND m.sender_id = ?) OR (m.receiver_role = 'user' AND m.receiver_id = ?)
    ORDER BY m.created_at DESC");
$stmt->bind_param('ii', $user['user_id'], $user['user_id']);
$stmt->execute();
$messages = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Messages | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Messages</div>
    <div class="navlinks">
        <a href="dashboard.php">Browse</a>
        <a href="cart.php">Cart</a>
        <a href="orders.php">Orders</a>
        <?php if ($user['role'] === 'seller'): ?><a href="seller_dashboard.php">Seller Dashboard</a><?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container two-column">
    <div>
        <?php renderFlash(); ?>
        <div class="hero small-hero">
            <h1>Communication centre</h1>
            <p>Customers and sellers can message the administrator about items, orders and delivery problems.</p>
        </div>
        <form method="post">
            <h2>Send message to admin</h2>
            <input type="hidden" name="send_message" value="1">
            <label>Subject</label>
            <input name="subject" required>
            <label>Related order ID (optional)</label>
            <input type="number" name="related_order_id">
            <label>Related clothing ID (optional)</label>
            <input type="number" name="related_clothes_id">
            <label>Message</label>
            <textarea name="message_text" required></textarea>
            <br><br>
            <button class="btn" type="submit">Send Message</button>
        </form>
    </div>
    <div class="card">
        <h2>Conversation history</h2>
        <?php if ($messages->num_rows === 0): ?>
            <p>No messages yet.</p>
        <?php else: ?>
            <?php while ($message = $messages->fetch_assoc()): ?>
                <div class="message-box">
                    <div class="message-meta">
                        <strong><?= esc($message['sender_name'] ?? 'Unknown') ?></strong>
                        <span>to <?= esc($message['receiver_name'] ?? 'Unknown') ?></span>
                    </div>
                    <p><strong>Subject:</strong> <?= esc($message['subject']) ?></p>
                    <p><?= nl2br(esc($message['message_text'])) ?></p>
                    <small>Order ID: <?= (int)$message['related_order_id'] ?> | Item ID: <?= (int)$message['related_clothes_id'] ?> | <?= esc($message['created_at']) ?></small>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
