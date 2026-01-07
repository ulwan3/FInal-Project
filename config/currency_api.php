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

            if (!$data || !isset($data['conversion_rates'])) {
                throw new Exception("Invalid API response format: " . $response);
            }

            $rates = [];
            foreach ($target_currencies as $currency) {
                if (isset($data['conversion_rates'][$currency])) {
                    $rates[$currency] = $data['conversion_rates'][$currency];
                }
            }

            return [
                'success' => true,
                'rates' => $rates,
                'base_currency' => $data['base_code'] ?? $base_currency,
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

// Utility function untuk update kurs dari API (mengambil daftar mata uang dari DB)
function updateCurrencyRatesFromAPI() {
    global $pdo;

    $currency_api = new CurrencyAPI();

    // Ambil daftar mata uang aktif dari database
    try {
        $stmt = $pdo->query("SELECT kode_uang FROM mata_uang WHERE status_aktif = 1");
        $dbCurrencies = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Gagal membaca daftar mata uang dari database: ' . $e->getMessage(),
            'updated_count' => 0
        ];
    }

    if (empty($dbCurrencies)) {
        return [
            'success' => false,
            'error' => 'Tidak ada mata uang aktif untuk diupdate',
            'updated_count' => 0
        ];
    }

    // Pastikan IDR selalu ada untuk perhitungan terhadap IDR
    if (!in_array('IDR', $dbCurrencies)) {
        $dbCurrencies[] = 'IDR';
    }

    $result = $currency_api->getRates('USD', $dbCurrencies);

    if ($result['success']) {
        $rates = $result['rates'];
        $updated_count = 0;

        // Pastikan kita punya nilai USD->IDR; gunakan fallback jika perlu
        $usd_to_idr = isset($rates['IDR']) && $rates['IDR'] != 0 ? $rates['IDR'] : 15000;

        foreach ($dbCurrencies as $currency) {
            $currency = strtoupper($currency);

            if ($currency === 'IDR') {
                $idr_rate = 1;
            } else {
                if (empty($rates[$currency]) || $rates[$currency] == 0) {
                    // Jika API tidak memberikan rate untuk mata uang ini, gunakan fallback konservatif
                    $idr_rate = $usd_to_idr;
                } else {
                    // API mengembalikan 1 USD = X currency
                    // Maka 1 currency = USD->IDR / (1 USD -> currency)
                    $idr_rate = $usd_to_idr / $rates[$currency];
                }
            }

            $stmt = $pdo->prepare(
                "INSERT INTO mata_uang (kode_uang, nama_uang, nilai_kurs_terhadap_idr, tanggal_update, status_aktif) \n                VALUES (?, ?, ?, NOW(), 1)\n                ON DUPLICATE KEY UPDATE \n                nilai_kurs_terhadap_idr = VALUES(nilai_kurs_terhadap_idr),\n                tanggal_update = VALUES(tanggal_update)"
            );

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