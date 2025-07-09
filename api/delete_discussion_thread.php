<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$threadId = $_POST['thread_id'] ?? '';
$userId   = $_SESSION['user_id'] ?? null;

if (empty($threadId) || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $result = deleteDiscussionThread($threadId, $userId);
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unable to delete thread']);
    }
} catch (Exception $e) {
    error_log('Delete discussion thread API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
