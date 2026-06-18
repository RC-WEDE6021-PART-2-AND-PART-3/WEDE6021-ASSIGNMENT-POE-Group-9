<?php
include 'helpers.php';
include 'DBConn.php';
requireUserLogin();
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $clothesId = (int)($_POST['clothes_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $check = $conn->prepare("SELECT clothes_id, seller_id FROM tblClothes WHERE clothes_id = ? AND status = 'approved' LIMIT 1");
    $check->bind_param('i', $clothesId);
    $check->execute();
    $item = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$item) {
        setFlash('error', 'This item is no longer available.');
    } elseif ((int)$item['seller_id'] === (int)$user['user_id']) {
        setFlash('error', 'You cannot add your own listing to the cart.');
    } else {
        $existing = $conn->prepare('SELECT cart_id, quantity FROM tblCart WHERE buyer_id = ? AND clothes_id = ? LIMIT 1');
        $existing->bind_param('ii', $user['user_id'], $clothesId);
        $existing->execute();
        $row = $existing->get_result()->fetch_assoc();
        $existing->close();

        if ($row) {
            $newQty = (int)$row['quantity'] + $quantity;
            $update = $conn->prepare('UPDATE tblCart SET quantity = ? WHERE cart_id = ?');
            $update->bind_param('ii', $newQty, $row['cart_id']);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare('INSERT INTO tblCart (buyer_id, clothes_id, quantity) VALUES (?, ?, ?)');
            $insert->bind_param('iii', $user['user_id'], $clothesId, $quantity);
            $insert->execute();
            $insert->close();
        }
        setFlash('success', 'Item added to cart.');
    }
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$brand = trim($_GET['brand'] ?? '');

$sql = "SELECT c.*, u.full_name FROM tblClothes c JOIN tblUser u ON c.seller_id = u.user_id WHERE c.status = 'approved'";
$types = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.category LIKE ? OR c.brand LIKE ? )";
    $like = '%' . $search . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($category !== '') {
    $sql .= ' AND c.category = ?';
    $types .= 's';
    $params[] = $category;
}
if ($brand !== '') {
    $sql .= ' AND c.brand LIKE ?';
    $types .= 's';
    $params[] = '%' . $brand . '%';
}
$sql .= ' ORDER BY c.created_at DESC';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$categories = $conn->query("SELECT DISTINCT category FROM tblClothes WHERE status='approved' ORDER BY category");
$cartCount = cartItemCount($conn, (int)$user['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Browse Clothing | Pastimes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="navbar">
    <div class="brand">Pastimes</div>
    <div class="navlinks">
        <a href="dashboard.php">Browse</a>
        <a href="cart.php">Cart (<?= $cartCount ?>)</a>
        <a href="orders.php">Orders</a>
        <a href="messages.php">Messages</a>
        <?php if ($user['role'] === 'seller'): ?><a href="seller_dashboard.php">Seller Dashboard</a><?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <?php renderFlash(); ?>
    <div class="hero">
        <h1>Browse second-hand clothing</h1>
        <p>Hello, <?= esc($user['full_name']) ?>. Find quality pre-owned fashion, add it to your cart and continue shopping anytime.</p>
        <?php if ($user['role'] === 'seller' && $user['status'] !== 'verified'): ?>
            <div class="alert warning inline-alert">Your seller account is pending admin approval. You can still submit sell requests from the seller dashboard.</div>
        <?php endif; ?>
    </div>

    <form method="get" class="filter-bar">
        <div>
            <label>Search</label>
            <input type="text" name="search" value="<?= esc($search) ?>" placeholder="Search title, brand or description">
        </div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All categories</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= esc($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>><?= esc($cat['category']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Brand</label>
            <input type="text" name="brand" value="<?= esc($brand) ?>" placeholder="e.g. Nike">
        </div>
        <div class="filter-actions">
            <button class="btn" type="submit">Filter</button>
            <a class="btn secondary" href="dashboard.php">Reset</a>
        </div>
    </form>

    <div class="grid">
        <?php if (!$items): ?>
            <div class="card single-card"><h3>No items found</h3><p>Try a different category or search term.</p></div>
        <?php endif; ?>
        <?php foreach ($items as $row): ?>
            <div class="card">
                <?php $image = itemImageOrPlaceholder($row['image_path']); ?>
                <?php if ($image): ?>
                    <img class="product-photo" src="<?= esc($image) ?>" alt="<?= esc($row['title']) ?>">
                <?php else: ?>
                    <div class="product-img">No image uploaded</div>
                <?php endif; ?>
                <span class="badge">Approved</span>
                <h3><?= esc($row['title']) ?></h3>
                <p><?= esc($row['description']) ?></p>
                <p><strong>Category:</strong> <?= esc($row['category']) ?></p>
                <p><strong>Brand:</strong> <?= esc($row['brand']) ?> | <strong>Size:</strong> <?= esc($row['size']) ?></p>
                <p><strong>Condition:</strong> <?= esc($row['item_condition']) ?></p>
                <p><strong>Seller:</strong> <?= esc($row['full_name']) ?></p>
                <div class="price"><?= formatPrice($row['price']) ?></div>
                <form method="post" class="mini-form">
                    <input type="hidden" name="clothes_id" value="<?= (int)$row['clothes_id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn" name="add_to_cart" type="submit">Add to Cart</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
