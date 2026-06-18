<?php
include 'helpers.php';
include 'DBConn.php';
requireUserLogin();
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($user['role'] !== 'seller') {
    setFlash('error', 'Only seller accounts can access the seller dashboard.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_listing'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $condition = trim($_POST['item_condition'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $imagePath = saveUploadedImage('item_image');
    $status = 'pending';

    $stmt = $conn->prepare('INSERT INTO tblClothes (seller_id, title, category, brand, size, item_condition, description, price, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssssdss', $user['user_id'], $title, $category, $brand, $size, $condition, $description, $price, $imagePath, $status);
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Sell request sent successfully. The admin can now approve, update or reject the listing.');
    header('Location: seller_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_listing'])) {
    $listingId = (int)($_POST['clothes_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $condition = trim($_POST['item_condition'] ?? '');
    $statusOverride = $_POST['status'] ?? 'pending';

    $stmt = $conn->prepare('UPDATE tblClothes SET title = ?, brand = ?, description = ?, price = ?, size = ?, item_condition = ?, status = ? WHERE clothes_id = ? AND seller_id = ?');
    $stmt->bind_param('sssdsssii', $title, $brand, $description, $price, $size, $condition, $statusOverride, $listingId, $user['user_id']);
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Listing updated successfully.');
    header('Location: seller_dashboard.php');
    exit;
}

if (isset($_GET['delete'])) {
    $listingId = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM tblClothes WHERE clothes_id = ? AND seller_id = ?');
    $stmt->bind_param('ii', $listingId, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Listing removed successfully.');
    header('Location: seller_dashboard.php');
    exit;
}

$listingsStmt = $conn->prepare('SELECT * FROM tblClothes WHERE seller_id = ? ORDER BY created_at DESC');
$listingsStmt->bind_param('i', $user['user_id']);
$listingsStmt->execute();
$listingsResult = $listingsStmt->get_result();
$listings = [];
while ($row = $listingsResult->fetch_assoc()) {
    $listings[] = $row;
}
$listingsStmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Seller Dashboard | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes Seller</div>
    <div class="navlinks">
        <a href="dashboard.php">Browse</a>
        <a href="cart.php">Cart</a>
        <a href="orders.php">Orders</a>
        <a href="messages.php">Messages</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <?php renderFlash(); ?>
    <div class="hero">
        <h1>Seller dashboard</h1>
        <p>Submit sell requests with brand, description and image upload. Approved items will appear on the public catalogue.</p>
        <div class="alert <?= $user['status'] === 'verified' ? 'success' : 'warning' ?> inline-alert">
            Seller verification status: <strong><?= esc($user['status']) ?></strong>
        </div>
    </div>

    <div class="two-column-layout">
        <form method="post" enctype="multipart/form-data">
            <h2>Send request to sell clothing</h2>
            <input type="hidden" name="submit_listing" value="1">
            <label>Title</label>
            <input name="title" required>
            <label>Category</label>
            <input name="category" required>
            <label>Brand</label>
            <input name="brand" required>
            <label>Size</label>
            <input name="size" required>
            <label>Condition</label>
            <select name="item_condition">
                <option>Excellent</option>
                <option>Good</option>
                <option>Fair</option>
            </select>
            <label>Description</label>
            <textarea name="description" required></textarea>
            <label>Price</label>
            <input type="number" name="price" step="0.01" required>
            <label>Image</label>
            <input type="file" name="item_image" accept="image/*">
            <br><br>
            <button class="btn" type="submit">Submit Sell Request</button>
        </form>

        <div class="card">
            <h2>My listings</h2>
            <?php if (!$listings): ?>
                <p>No listings submitted yet.</p>
            <?php else: ?>
                <?php foreach ($listings as $listing): ?>
                    <div class="listing-box">
                        <div class="listing-header">
                            <h3><?= esc($listing['title']) ?></h3>
                            <span class="badge <?= $listing['status'] === 'pending' ? 'pending' : '' ?>"><?= esc($listing['status']) ?></span>
                        </div>
                        <form method="post">
                            <input type="hidden" name="update_listing" value="1">
                            <input type="hidden" name="clothes_id" value="<?= (int)$listing['clothes_id'] ?>">
                            <label>Title</label>
                            <input name="title" value="<?= esc($listing['title']) ?>">
                            <label>Brand</label>
                            <input name="brand" value="<?= esc($listing['brand']) ?>">
                            <label>Description</label>
                            <textarea name="description"><?= esc($listing['description']) ?></textarea>
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" value="<?= esc($listing['price']) ?>">
                            <label>Size</label>
                            <input name="size" value="<?= esc($listing['size']) ?>">
                            <label>Condition</label>
                            <input name="item_condition" value="<?= esc($listing['item_condition']) ?>">
                            <label>Status</label>
                            <select name="status">
                                <option value="pending" <?= $listing['status'] === 'pending' ? 'selected' : '' ?>>pending</option>
                                <option value="approved" <?= $listing['status'] === 'approved' ? 'selected' : '' ?>>approved</option>
                                <option value="hidden" <?= $listing['status'] === 'hidden' ? 'selected' : '' ?>>hidden</option>
                                <option value="sold" <?= $listing['status'] === 'sold' ? 'selected' : '' ?>>sold</option>
                                <option value="rejected" <?= $listing['status'] === 'rejected' ? 'selected' : '' ?>>rejected</option>
                            </select>
                            <div class="actions">
                                <button class="btn secondary" type="submit">Update</button>
                                <a class="btn danger" href="?delete=<?= (int)$listing['clothes_id'] ?>" onclick="return confirm('Delete this listing?')">Delete</a>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
