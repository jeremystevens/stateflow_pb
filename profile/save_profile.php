<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../database/init.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$tagline = trim($_POST['tagline'] ?? '');
$website = trim($_POST['website'] ?? '');

if (strlen($tagline) > 100) {
    echo json_encode(['success' => false, 'message' => 'Tagline must be 100 characters or less.']);
    exit;
}

if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid website URL.']);
    exit;
}

$avatarFilename = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error uploading file.']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif'
    ];

    if (!isset($allowed[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/avatars';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $avatarFilename = $userId . '_' . time() . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $avatarFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save avatar.']);
        exit;
    }
}

try {
    if ($avatarFilename) {
        $stmt = $pdo->prepare('UPDATE users SET profile_image = ?, tagline = ?, website = ? WHERE id = ?');
        $stmt->execute([$avatarFilename, $tagline ?: null, $website ?: null, $userId]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET tagline = ?, website = ? WHERE id = ?');
        $stmt->execute([$tagline ?: null, $website ?: null, $userId]);
    }

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
