<?php
/**
 * SISTEM PENGHEMATAN CERDAS - Kategori Boros & Saran Hemat
 */

class SmartSavingsSystem extends BaseModel {
    
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    // Method abstract dari BaseModel:
    public function validate() {
        // Validasi untuk sistem penghematan
        return true;
    }
    
    // ==================== KATEGORI BOROS ====================
    
    /**
     * Deteksi kategori pengeluaran boros
     * POLYMORPHISM: Method Overriding dari parent class
     * Return: Array kategori yang boros beserta analisis
     */
    public function detectWastefulCategories($user_id, $month = null, $year = null): array {
        if ($month === null) $month = date('m');
        if ($year === null) $year = date('Y');
        
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    k.id as kategori_id,
                    k.nama_kategori,
                    COALESCE(SUM(t.jumlah_idr), 0) as total_pengeluaran,
                    COUNT(t.id) as frekuensi,
                    AVG(t.jumlah_idr) as rata_rata,
                    MAX(t.jumlah_idr) as transaksi_terbesar,
                    (COALESCE(SUM(t.jumlah_idr), 0) / 
                        (SELECT COALESCE(SUM(jumlah_idr), 0.01) 
                         FROM transaksi 
                         WHERE user_id = ? 
                         AND jenis = 'pengeluaran'
                         AND tanggal_transaksi BETWEEN ? AND ?)) * 100 as persentase_total
                FROM kategori k
                LEFT JOIN transaksi t ON k.id = t.kategori_id 
                    AND t.user_id = ? 
                    AND t.jenis = 'pengeluaran'
                    AND t.tanggal_transaksi BETWEEN ? AND ?
                WHERE k.tipe = 'pengeluaran'
                GROUP BY k.id, k.nama_kategori
                HAVING total_pengeluaran > 0
                ORDER BY total_pengeluaran DESC
            ");
            
            $stmt->execute([$user_id, $start_date, $end_date, $user_id, $start_date, $end_date]);
            $categories = $stmt->fetchAll();
            
            $wasteful_categories = [];
            
            foreach ($categories as $category) {
                $analysis = $this->analyzeCategoryWaste($category);
                
                if ($analysis['is_wasteful']) {
                    $wasteful_categories[] = [
                        'kategori' => $category['nama_kategori'],
                        'total_pengeluaran' => $category['total_pengeluaran'],
                        'frekuensi' => $category['frekuensi'],
                        'rata_rata' => $category['rata_rata'],
                        'transaksi_terbesar' => $category['transaksi_terbesar'],
                        'persentase_total' => $category['persentase_total'],
                        'tingkat_keborosan' => $analysis['waste_level'],
                        'alasan' => $analysis['reason'],
                        'potensi_penghematan' => $analysis['savings_potential'],
                        'saran_hemat' => $analysis['savings_tips']
                    ];
                }
            }
            
            return $wasteful_categories;
            
        } catch (Exception $e) {
            error_log("Error in detectWastefulCategories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analisis kategori untuk menentukan tingkat keborosan
     */
    private function analyzeCategoryWaste($category) {
        $total = $category['total_pengeluaran'];
        $frequency = $category['frekuensi'];
        $average = $category['rata_rata'];
        $max_transaction = $category['transaksi_terbesar'];
        $percentage = $category['persentase_total'];
        
        $is_wasteful = false;
        $waste_level = 'normal';
        $reason = '';
        $savings_potential = 0;
        $savings_tips = [];
        
        // KRITERIA KEBOROSAN:
        
        // 1. Pengeluaran sangat besar (> 2 juta)
        if ($total > 2000000) {
            $is_wasteful = true;
            $waste_level = 'tinggi';
            $reason .= "Total pengeluaran sangat besar (Rp " . number_format($total, 0, ',', '.') . "). ";
            $savings_potential = $total * 0.25; // Potensi hemat 25%
        }
        
        // 2. Frekuensi terlalu sering (> 15x/bulan)
        if ($frequency > 15) {
            $is_wasteful = true;
            $waste_level = $waste_level == 'tinggi' ? 'tinggi' : 'sedang';
            $reason .= "Frekuensi transaksi terlalu sering (" . $frequency . "x/bulan). ";
            $savings_potential += $total * 0.15;
        }
        
        // 3. Rata-rata per transaksi tinggi (> 150rb)
        if ($average > 150000) {
            $is_wasteful = true;
            $waste_level = $waste_level == 'tinggi' ? 'tinggi' : 'sedang';
            $reason .= "Rata-rata per transaksi tinggi (Rp " . number_format($average, 0, ',', '.') . "). ";
            $savings_potential += $total * 0.20;
        }
        
        // 4. Persentase terhadap total pengeluaran tinggi (> 30%)
        if ($percentage > 30) {
            $is_wasteful = true;
            $waste_level = $waste_level == 'tinggi' ? 'tinggi' : 'sedang';
            $reason .= "Menggunakan " . number_format($percentage, 1) . "% dari total pengeluaran. ";
            $savings_potential += $total * 0.10;
        }
        
        // 5. Ada transaksi sangat besar (> 500rb)
        if ($max_transaction > 500000) {
            $is_wasteful = true;
            $waste_level = 'tinggi';
            $reason .= "Ada transaksi sangat besar (Rp " . number_format($max_transaction, 0, ',', '.') . "). ";
            $savings_potential += $max_transaction * 0.30;
        }
        
        if ($is_wasteful) {
            // Generate savings tips berdasarkan kategori
            $savings_tips = $this->generateSavingsTips($category['nama_kategori'], $total, $frequency, $average);
            
            // Cap savings potential maksimal 50% dari total
            $savings_potential = min($savings_potential, $total * 0.5);
        }
        
        return [
            'is_wasteful' => $is_wasteful,
            'waste_level' => $waste_level,
            'reason' => $reason,
            'savings_potential' => $savings_potential,
            'savings_tips' => $savings_tips
        ];
    }
    
    // ==================== SARAN PENGHEMATAN ====================
    
    /**
     * Generate saran penghematan spesifik berdasarkan kategori
     */
    private function generateSavingsTips($category, $total, $frequency, $average) {
        $formatted_total = number_format($total, 0, ',', '.');
        $formatted_avg = number_format($average, 0, ',', '.');
        
        $tips_by_category = [
            'Makanan' => [
                "💡 Total Rp {$formatted_total} ({$frequency}x/bulan) - Rata-rata Rp {$formatted_avg}/transaksi",
                "🍽️ Masak sendiri ketika di kos/rumah",
                "🥡 Bawa Bekal dari kos/rumah👍",
                "📅 Jadwalkan untuk membeli makanan cepat saji jangan setiap hari❌"
            ],
            
            'Transportasi' => [
                "💡 Total Rp {$formatted_total} ({$frequency}x/bulan) - Rata-rata Rp {$formatted_avg}/transaksi",
                "🚌 Pakai Transportasi Umum Lebih Hemat",
                "🚴 Gunakan sepeda untuk jarak dekat sambil berolahraga👍",
                "📱 Manfaatkan diskon di aplikasi yang sedang promo Grab/Gojek "
            ],
            
            'Hiburan' => [
                "💡 Total Pengeluaran Rp {$formatted_total}** ({$frequency}x/bulan) - Rata-rata Rp {$formatted_avg}/transaksi",
                "🎬 Batasi menonton dalam setahun maksimal 3x",
                "🎵 Ikuti Konser yang di adakan kampus agar lebih murah",
                "🏞️ Pergi ke taman/danau untuk refreshing",
                "🎮 Main game di ponsel bersama teman lebih hemat",
                "📚 Perpustakaan untuk Hiburan gratis dengan buku"
            ],
            
            'Belanja' => [
                "💡 Total Rp {$formatted_total} ({$frequency}x/bulan) - Rata-rata Rp {$formatted_avg}/transaksi",
                "⏰ Tunggu sehari sebelum beli barang mahal",
                "🔍 Bandingkan harga online vs offline",
                "🎯 Belanja Sesuai Kebutuhan👍",
                "🏷️ Cari Diskonan Ketika Hari Besar"
            ],
            
            'Kafe/Restoran' => [
                "💡 Total Rp {$formatted_total} ({$frequency}x/bulan) - Rata-rata Rp {$formatted_avg}/transaksi",
                "🍴 Makan di tempat Lebih murah dari delivery + ongkir",
                "🎂 Masak sendiri ketika ada acara spesial, lebih hemat",
                "📱 Selalu cek promo di setiap Cafe/Restoran sebelum pesan",
                "💧 Bawa air minum lebih hemat"
            ],
            
            'Elektronik/Gadget' => [
                "💡 Total Rp {$formatted_total} - Transaksi besar perlu evaluasi",
                "📱 Perbaiki dulu banyak turorial di youtube",
                "🔄 Beli Barang elektronik bekas berkualitas masih bagus",
                "🎯 Upgrade ketika sudah benar-benar perlu?",
                "💳 Jika harus beli, usahakan jangan credit"
            ],
            
            'Pakaian/Fashion' => [
                "💡 Total Rp {$formatted_total} ({$frequency}x/bulan)",
                "👕 Thrift Baju bekas branded berkualitas dengan harga 70% lebih murah",
                "🔄 Kombinasi baju lama, kurangi beli baru",
                "🏷️ Tunggu Diskon saat hari besar ",
                "🎯 High quality, Beli sekali tapi awet"
            ],
            
            'Pendidikan' => [
                "💡 Total Rp {$formatted_total} - Investasi penting tapi bisa dioptimasi",
                "📚 Banyak buku gratis versi digital",
                "👥 Beli buku/materi bersama, bagi biaya",
                "📝 Gunakan perpustakaan kampus, Manfaatkan fasilitas maksimal",
                "🎯 Coba tutorial gratis sebelum bayar kursus"
            ]
        ];
        
        // Default tips untuk kategori lain
        $default_tips = [
            "💡 Total Rp {$formatted_total} ({$frequency}x/bulan) - Rata-rata Rp {$formatted_avg}/transaksi",
            "📊 Analisis pengeluaran dan Catat setiap transaksi detail",
            "🎯 Untuk budget Alokasikan maksimal 20% dari pemasukan untuk kategori ini",
            "⏰ Evaluasi bulanan apakah pengeluaran ini memberikan nilai",
            "💭 Tanya ke diri sendiri 'Apakah ini kebutuhan atau keinginan?'",
            "💰 Jika berhasil hemat, langsung transfer ke tabungan"
        ];
        
        return $tips_by_category[$category] ?? $default_tips;
    }
    
    /**
     * Get personalized savings plan
     */
    public function getPersonalizedSavingsPlan($user_id) {
        $wasteful_categories = $this->detectWastefulCategories($user_id);
        
        if (empty($wasteful_categories)) {
            return [
                'has_waste' => false,
                'message' => '🎉 Pengeluaran Anda sudah efisien! Tidak ada kategori boros yang terdeteksi.',
                'total_potential_savings' => 0,
                'categories' => []
            ];
        }
        
        $total_potential = 0;
        $monthly_savings_plan = [];
        
        foreach ($wasteful_categories as $category) {
            $total_potential += $category['potensi_penghematan'];
            
            $monthly_savings_plan[] = [
                'kategori' => $category['kategori'],
                'current_spending' => $category['total_pengeluaran'],
                'target_spending' => $category['total_pengeluaran'] * 0.7, // Target kurangi 30%
                'monthly_saving' => $category['potensi_penghematan'],
                'tips' => $category['saran_hemat']
            ];
        }
        
        // Sort by saving potential (highest first)
        usort($monthly_savings_plan, function($a, $b) {
            return $b['monthly_saving'] <=> $a['monthly_saving'];
        });
        
        return [
            'has_waste' => true,
            'message' => '💰 Ditemukan ' . count($wasteful_categories) . ' kategori yang bisa dioptimasi!',
            'total_potential_savings' => $total_potential,
            'monthly_savings' => round($total_potential / 12),
            'categories' => $monthly_savings_plan
        ];
    }
    
    // ==================== FUNGSI UTAMA ====================
    
    /**
     * Get complete savings report
     */
    public function getCompleteSavingsReport($user_id) {
        return [
            'waste_analysis' => $this->detectWastefulCategories($user_id),
            'savings_plan' => $this->getPersonalizedSavingsPlan($user_id),
            'tips_by_category' => $this->getAllSavingsTips($user_id)
        ];
    }
    
    /**
     * Get all savings tips categorized
     */
    private function getAllSavingsTips($user_id) {
        $categories = ['Makanan', 'Transportasi', 'Hiburan', 'Belanja', 'Kafe/Restoran'];
        
        $all_tips = [];
        foreach ($categories as $category) {
            $all_tips[$category] = $this->generateSavingsTips($category, 1000000, 10, 100000);
        }
        
        return $all_tips;
    }

    // ========== POLYMORPHISM: METHOD OVERLOADING ==========
    
    /**
     * POLYMORPHISM: Versi 1 - getWastefulCategories dengan parameter lengkap
     * Method overloading untuk interface yang lebih fleksibel
     */
    public function getWastefulCategories($user_id, $month = null, $year = null) {
        return $this->detectWastefulCategories($user_id, $month, $year);
    }
    
    /**
     * POLYMORPHISM: Versi 2 - getWastefulCategories untuk bulan saat ini
     * Hanya butuh user_id
     */
    public function getCurrentMonthWastefulCategories($user_id) {
        return $this->detectWastefulCategories($user_id, date('m'), date('Y'));
    }
    
    /**
     * POLYMORPHISM: Versi 3 - getWastefulCategories untuk bulan tertentu (string)
     * Menggunakan nama bulan
     */
    public function getWastefulCategoriesForMonth($user_id, $month_name) {
        $month_map = [
            'januari' => 1, 'februari' => 2, 'maret' => 3,
            'april' => 4, 'mei' => 5, 'juni' => 6,
            'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'desember' => 12
        ];
        
        $month = $month_map[strtolower($month_name)] ?? date('m');
        return $this->detectWastefulCategories($user_id, $month, date('Y'));
    }
    
    /**
     * POLYMORPHISM: Versi 4 - getPlan dengan parameter berbeda
     * Bisa untuk savings atau budget
     */
    public function getPlan($user_id, $plan_type = 'savings') {
        if ($plan_type === 'savings') {
            return $this->getPersonalizedSavingsPlan($user_id);
        } elseif ($plan_type === 'budget') {
            return $this->getBudgetPlan($user_id);
        } elseif ($plan_type === 'complete') {
            return $this->getCompleteSavingsReport($user_id);
        } else {
            return ['error' => 'Plan type not supported'];
        }
    }
    
    /**
     * Method baru untuk budget plan
     */
    private function getBudgetPlan($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    k.nama_kategori,
                    COALESCE(SUM(b.jumlah_budget), 0) as total_budget,
                    COALESCE(SUM(t.jumlah_idr), 0) as total_pengeluaran,
                    (COALESCE(SUM(t.jumlah_idr), 0) / 
                     NULLIF(SUM(b.jumlah_budget), 0) * 100) as persentase_penggunaan
                FROM kategori k
                LEFT JOIN budget b ON k.id = b.kategori_id AND b.user_id = ?
                LEFT JOIN transaksi t ON k.id = t.kategori_id 
                    AND t.user_id = ? 
                    AND t.jenis = 'pengeluaran'
                WHERE k.tipe = 'pengeluaran'
                GROUP BY k.id, k.nama_kategori
                HAVING total_budget > 0
                ORDER BY total_budget DESC
            ");
            
            $stmt->execute([$user_id, $user_id]);
            $budgets = $stmt->fetchAll();
            
            return [
                'type' => 'budget',
                'total_categories' => count($budgets),
                'data' => $budgets
            ];
        } catch (Exception $e) {
            error_log("Error in getBudgetPlan: " . $e->getMessage());
            return ['type' => 'budget', 'data' => [], 'error' => $e->getMessage()];
        }
    }
}

/**
 * Display wasteful categories in HTML
 */
function displayWastefulCategories($categories) {
    if (empty($categories)) {
        return '<div class="alert alert-success">🎉 Selamat! Tidak ada kategori boros yang terdeteksi.</div>';
    }
    
    $html = '<div class="row">';
    
    foreach ($categories as $category) {
        $badge_color = $category['tingkat_keborosan'] == 'tinggi' ? 'danger' : 
                      ($category['tingkat_keborosan'] == 'sedang' ? 'warning' : 'info');
        
        $html .= '
        <div class="col-md-6 mb-3">
            <div class="card border-' . $badge_color . '">
                <div class="card-header bg-' . $badge_color . ' text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ' . $category['kategori'] . '
                        <span class="badge bg-light text-dark float-end">
                            Boros ' . $category['tingkat_keborosan'] . '
                        </span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">' . $category['alasan'] . '</small>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-6">
                            <small>Total Pengeluaran:</small><br>
                            <strong class="text-danger">Rp ' . number_format($category['total_pengeluaran'], 0, ',', '.') . '</strong>
                        </div>
                        <div class="col-6">
                            <small>Frekuensi:</small><br>
                            <strong>' . $category['frekuensi'] . 'x/bulan</strong>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small>Rata-rata:</small><br>
                            <strong>Rp ' . number_format($category['rata_rata'], 0, ',', '.') . '</strong>
                        </div>
                        <div class="col-6">
                            <small>Potensi Hemat:</small><br>
                            <strong class="text-success">Rp ' . number_format($category['potensi_penghematan'], 0, ',', '.') . '/bulan</strong>
                        </div>
                    </div>
                    
                    <div class="savings-tips">
                        <h6><i class="fas fa-lightbulb me-2"></i>Saran Hemat:</h6>
                        <ul class="list-unstyled mb-0">';
        
        foreach ($category['saran_hemat'] as $tip) {
            $html .= '<li class="mb-1"><small><i class="fas fa-check text-success me-2"></i>' . $tip . '</small></li>';
        }
        
        $html .= '
                        </ul>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    $html .= '</div>';
    
    // Add summary
    $total_potential = array_sum(array_column($categories, 'potensi_penghematan'));
    
    $html .= '
    <div class="alert alert-info mt-3">
        <h6><i class="fas fa-chart-line me-2"></i>Ringkasan Potensi Penghematan</h6>
        <p class="mb-1">Total potensi penghematan: <strong>Rp ' . number_format($total_potential, 0, ',', '.') . '/bulan</strong></p>
        <p class="mb-0">Setara dengan: <strong>Rp ' . number_format($total_potential * 12, 0, ',', '.') . '/tahun</strong> 🎯</p>
    </div>';
    
    return $html;
}

/**
 * Display savings plan in HTML
 */
function displaySavingsPlan($savings_plan) {
    if (!$savings_plan['has_waste']) {
        return '<div class="alert alert-success">' . $savings_plan['message'] . '</div>';
    }
    
    $html = '
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-piggy-bank me-2"></i>Rencana Penghematan Bulanan</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                ' . $savings_plan['message'] . '
                <br>
                <strong>Total potensi hemat: Rp ' . number_format($savings_plan['total_potential_savings'], 0, ',', '.') . '/tahun</strong>
                (Rp ' . number_format($savings_plan['monthly_savings'], 0, ',', '.') . '/bulan)
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Pengeluaran Sekarang</th>
                            <th>Target Penghematan</th>
                            <th>Potensi Hemat/Bulan</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($savings_plan['categories'] as $plan) {
        $saving_percentage = round((1 - ($plan['target_spending'] / $plan['current_spending'])) * 100);
        
        $html .= '
        <tr>
            <td>
                <strong>' . $plan['kategori'] . '</strong>
                <br>
                <small class="text-muted">' . count($plan['tips']) . ' tips hemat</small>
            </td>
            <td class="text-danger">
                Rp ' . number_format($plan['current_spending'], 0, ',', '.') . '
            </td>
            <td>
                Rp ' . number_format($plan['target_spending'], 0, ',', '.') . '
                <br>
                <small class="text-success">(-' . $saving_percentage . '%)</small>
            </td>
            <td class="text-success fw-bold">
                Rp ' . number_format($plan['monthly_saving'], 0, ',', '.') . '
            </td>
        </tr>';
    }
    
    $html .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';
    
    return $html;
}

?>