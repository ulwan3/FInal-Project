<?php
/**
 * Currency Updater - Mengupdate kurs mata uang dari API eksternal
 */

require_once '../config/currency_api.php';

function updateCurrencyRates() {
    global $pdo;
    
    // Use the new CurrencyAPI class
    $provider = 'exchangerate'; // Default provider
    $api_key = ''; // Add your API key here if needed
    
function updateCurrencyRates() {
    global $pdo;
    
    // Use the CurrencyAPI class from config
    return updateCurrencyRatesFromAPI();
}

// Simple manual update function
function updateCurrencyRatesManually($rates) {
    global $pdo;
    
    try {
        $updated_count = 0;
        
        foreach ($rates as $currency => $rate) {
            if ($rate > 0 && $currency !== 'IDR') {
                $idr_rate = 1 / $rate;
                
                $stmt = $pdo->prepare("
                    UPDATE mata_uang 
                    SET nilai_kurs_terhadap_idr = ?, tanggal_update = NOW() 
                    WHERE kode_uang = ?
                ");
                
                if ($stmt->execute([$idr_rate, $currency])) {
                    $updated_count++;
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "Berhasil update {$updated_count} kurs mata uang secara manual",
            'updated_count' => $updated_count
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => "Gagal update kurs manual: " . $e->getMessage()
        ];
    }
}

// Function to get current rates from database
function getCurrentCurrencyRates() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT kode_uang, nama_uang, nilai_kurs_terhadap_idr, tanggal_update 
            FROM mata_uang 
            WHERE status_aktif = 1 
            ORDER BY kode_uang
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting currency rates: " . $e->getMessage());
        return [];
    }
}

// Function to check if rates need update (older than 24 hours)
function shouldUpdateRates() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT MAX(tanggal_update) as last_update 
            FROM mata_uang 
            WHERE kode_uang != 'IDR'
        ");
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['last_update']) {
            return true;
        }
        
        $last_update = new DateTime($result['last_update']);
        $now = new DateTime();
        $diff = $now->diff($last_update);
        
        // Update if older than 24 hours
        return $diff->h + ($diff->days * 24) >= 24;
        
    } catch (Exception $e) {
        error_log("Error checking rate update: " . $e->getMessage());
        return true;
    }
}

// Auto-update function that can be called via cron job
function autoUpdateCurrencyRates() {
    if (shouldUpdateRates()) {
        $result = updateCurrencyRates();
        
        // Log the update
        error_log("Auto currency update: " . 
                 ($result['success'] ? 'Success' : 'Failed') . 
                 " - " . ($result['message'] ?? $result['error'] ?? 'Unknown'));
                 
        return $result;
    }
    
    return [
        'success' => true,
        'message' => 'Rates are up to date, no update needed'
    ];
}
}
?>