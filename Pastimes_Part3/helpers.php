<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function esc($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function requireUserLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function requireAdminLogin(): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: admin_login.php');
        exit;
    }
}

function getCurrentUser(mysqli $conn): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT * FROM tblUser WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $stmt->close();
    return $user;
}

function ensureUploadsDir(): string {
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function saveUploadedImage(string $fieldName): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $tmp = $_FILES[$fieldName]['tmp_name'];
    $mime = mime_content_type($tmp);
    if (!isset($allowed[$mime])) {
        return null;
    }

    $extension = $allowed[$mime];
    $filename = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetDir = ensureUploadsDir();
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($tmp, $targetPath)) {
        return null;
    }

    return 'uploads/' . $filename;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) {
        return;
    }
    $type = $flash['type'] === 'error' ? 'error' : 'success';
    echo '<div class="alert ' . esc($type) . '">' . esc($flash['message']) . '</div>';
}

function formatPrice($amount): string {
    return 'R' . number_format((float)$amount, 2);
}

function cartItemCount(mysqli $conn, int $userId): int {
    $stmt = $conn->prepare('SELECT COALESCE(SUM(quantity),0) AS total_items FROM tblCart WHERE buyer_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($result['total_items'] ?? 0);
}

function getUserAddresses(mysqli $conn, int $userId): array {
    $stmt = $conn->prepare('SELECT * FROM tblAddress WHERE user_id = ? ORDER BY address_id DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt->close();
    return $addresses;
}

function itemImageOrPlaceholder(?string $path): string {
    if ($path && file_exists(__DIR__ . '/' . $path)) {
        return $path;
    }
    return '';
}
?>
