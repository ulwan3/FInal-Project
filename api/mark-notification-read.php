<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$notification_id = $_GET['id'] ?? null;

if (!$notification_id || !is_numeric($notification_id)) {
    http_response_code(400);
    exit;
}

require_once '../includes/notification-system.php';

$notificationSystem = new NotificationSystem($pdo);
$success = $notificationSystem->markAsRead($notification_id, $_SESSION['user_id']);

if ($success) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
?>