<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['subscription']) || !isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $subscription = json_encode($input['subscription']);
    $user_id = $input['user_id'];
    
    // Simpan subscription ke database
    $stmt = $pdo->prepare("
        INSERT INTO push_subscriptions (user_id, subscription_data, created_at) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        subscription_data = VALUES(subscription_data),
        updated_at = NOW()
    ");
    
    $stmt->execute([$user_id, $subscription]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Save subscription error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>