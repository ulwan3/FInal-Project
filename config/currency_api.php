<?php
/**
 * Currency API Configuration - Menggunakan ExchangeRate-API
 */

class CurrencyAPI {
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = CURRENCY_API_KEY;
        $this->api_url = CURRENCY_API_URL;
    }

    
    
    public function getRates($base_currency = 'USD', $target_currencies = ['USD', 'EUR', 'SGD', 'JPY', 'GBP']) {
        try {
            $url = "{$this->api_url}/{$this->api_key}/latest/{$base_currency}";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'KeuanganMahasiswa/1.0'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                throw new Exception("cURL Error: " . curl_error($ch));
            }
            
            if ($http_code !== 200) {
                throw new Exception("HTTP Error: " . $http_code . " - " . $response);
            }
            
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (!$data || $data['result'] !== 'success' || !isset($data['conversion_rates'])) {
                throw new Exception("Invalid API response format: " . $response);
            }
            
            $rates = [];
            foreach ($target_currencies as $currency) {
                if (isset($data['conversion_rates'][$currency])) {
                    // API memberikan rate dari base currency ke target currency
                    // Untuk IDR, kita perlu menyesuaikan
                    if ($base_currency === 'USD') {
                        // 1 USD = X IDR, jadi rate IDR = conversion_rates[IDR]
                        if ($currency === 'IDR') {
                            $rates[$currency] = $data['conversion_rates']['IDR'];
                        } else {
                            // Untuk currency lain, kita hitung melalui USD
                            $rates[$currency] = $data['conversion_rates'][$currency];
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'rates' => $rates,
                'base_currency' => $data['base_code'] ?? 'USD',
                'last_updated' => $data['time_last_update_unix'] ?? time()
            ];
            
        } catch (Exception $e) {
            error_log("Currency API Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'rates' => $this->getFallbackRates($target_currencies)
            ];
        }
    }
    
    private function getFallbackRates($currencies) {
        $fallback_rates = [
            'USD' => 15000,
            'EUR' => 16000,
            'SGD' => 11000,
            'JPY' => 100,
            'GBP' => 19000,
            'IDR' => 1
        ];
        
        $rates = [];
        foreach ($currencies as $currency) {
            $rates[$currency] = $fallback_rates[$currency] ?? 1;
        }
        
        return $rates;
    }
}

// Utility function untuk update kurs dari API
function updateCurrencyRatesFromAPI() {
    global $pdo;
    
    $currency_api = new CurrencyAPI();
    $currencies = ['USD', 'EUR', 'SGD', 'JPY', 'GBP', 'IDR'];
    
    $result = $currency_api->getRates('USD', $currencies);
    
    if ($result['success']) {
        $rates = $result['rates'];
        $updated_count = 0;
        
        foreach ($rates as $currency => $rate) {
            if ($currency === 'IDR') {
                // IDR adalah base, jadi nilai tetap 1
                $idr_rate = 1;
            } else {
                // Rate dari API: 1 USD = X Currency
                // Kita butuh: 1 Currency = Y IDR
                // Jadi: Y = rates[IDR] / rates[Currency]
                $idr_rate = $rates['IDR'] / $rate;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO mata_uang (kode_uang, nama_uang, nilai_kurs_terhadap_idr, tanggal_update, status_aktif) 
                VALUES (?, ?, ?, NOW(), 1)
                ON DUPLICATE KEY UPDATE 
                nilai_kurs_terhadap_idr = VALUES(nilai_kurs_terhadap_idr),
                tanggal_update = VALUES(tanggal_update)
            ");
            
            $currency_name = getCurrencyName($currency);
            if ($stmt->execute([$currency, $currency_name, $idr_rate])) {
                $updated_count++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Berhasil update {$updated_count} kurs mata uang",
            'updated_count' => $updated_count,
            'rates' => $rates
        ];
        
    } else {
        return [
            'success' => false,
            'error' => "Gagal update kurs dari API: " . $result['error'],
            'updated_count' => 0
        ];
    }
}

function getCurrencyName($code) {
    $names = [
        'USD' => 'Dollar Amerika',
        'EUR' => 'Euro',
        'SGD' => 'Dollar Singapura',
        'JPY' => 'Yen Jepang',
        'GBP' => 'Pound Sterling',
        'IDR' => 'Rupiah Indonesia'
    ];
    
    return $names[$code] ?? $code;
}

function getCurrentRates() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT kode_uang, nilai_kurs_terhadap_idr FROM mata_uang WHERE status_aktif = 1");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>