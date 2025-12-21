<?php
require_once '../config/config.php';
require_once '../config/currency_api.php';

header('Content-Type: application/json');

try {
    // Get current rates from database
    $rates = getCurrentRates();
    
    echo json_encode([
        'success' => true,
        'rates' => $rates,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>