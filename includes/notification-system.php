<?php
/**
 * Sistem Notifikasi Web Push dan Alert
 */

class NotificationSystem extends BaseModel implements NotificationInterface{
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
    
    // TAMBAHKAN method abstract yang wajib:
    protected function validate() {
        // Implementation untuk validasi
        return true;
    }
    
    // ================= NOTIFIKASI OTOMATIS =================
    
    /**
     * Generate semua notifikasi otomatis untuk user (HANYA JIKA PERLU)
     */
    public function generateAutomaticNotifications($user_id) {
        // Cek kapan terakhir generate notifikasi untuk user ini
        $last_generated = $this->getLastNotificationGeneration($user_id);
        $today = date('Y-m-d');
        
        // Jika sudah generate hari ini, skip (kecuali untuk notifikasi urgent)
        if ($last_generated === $today) {
            return []; // Sudah generate hari ini, tidak perlu generate lagi
        }
        
        $notifications = [];
        
        // 1. Notifikasi kategori boros (hanya jika ada perubahan)
        $notifications = array_merge($notifications, $this->checkOverspendingCategories($user_id));
        
        // 2. Notifikasi jatuh tempo pembayaran (selalu cek)
        $notifications = array_merge($notifications, $this->checkDuePayments($user_id));
        
        // 3. Notifikasi budget hampir habis (hanya jika ada perubahan)
        $notifications = array_merge($notifications, $this->checkBudgetUsage($user_id));
        
        // 4. Notifikasi transaksi tidak biasa (hanya jika ada transaksi baru)
        $notifications = array_merge($notifications, $this->checkUnusualSpending($user_id));
        
        // Hanya simpan jika ada notifikasi baru
        if (!empty($notifications)) {
            $this->saveNotificationsToDatabase($user_id, $notifications);
            $this->updateLastNotificationGeneration($user_id, $today);
        }
        
        return $notifications;
    }
    
    /**
     * Cek kapan terakhir generate notifikasi
     */
    private function getLastNotificationGeneration($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT last_notification_generation 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            return $result['last_notification_generation'] ?? null;
        } catch (Exception $e) {
            error_log("Error getting last notification generation: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update timestamp terakhir generate notifikasi
     */
    private function updateLastNotificationGeneration($user_id, $date) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET last_notification_generation = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$date, $user_id]);
        } catch (Exception $e) {
            error_log("Error updating last notification generation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 1. NOTIFIKASI: Kategori pengeluaran melebihi budget
     * Hanya generate jika belum ada notifikasi serupa hari ini
     */
    public function checkOverspendingCategories($user_id, $month = null, $year = null) {
        if ($month === null) $month = date('m');
        if ($year === null) $year = date('Y');
        
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        $today = date('Y-m-d');
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    k.id as kategori_id,
                    k.nama_kategori,
                    COALESCE(SUM(t.jumlah_idr), 0) as total_pengeluaran,
                    COALESCE(b.jumlah_budget, 0) as jumlah_budget,
                    CASE 
                        WHEN b.jumlah_budget > 0 THEN (COALESCE(SUM(t.jumlah_idr), 0) / b.jumlah_budget * 100)
                        ELSE 0 
                    END as persentase
                FROM kategori k
                LEFT JOIN transaksi t ON k.id = t.kategori_id 
                    AND t.user_id = ? 
                    AND t.jenis = 'pengeluaran'
                    AND t.tanggal_transaksi BETWEEN ? AND ?
                LEFT JOIN budget b ON k.id = b.kategori_id 
                    AND b.user_id = ? 
                    AND ? BETWEEN b.tanggal_mulai AND b.tanggal_selesai
                WHERE k.tipe = 'pengeluaran'
                GROUP BY k.id, k.nama_kategori, b.jumlah_budget
                HAVING total_pengeluaran > 0
                ORDER BY total_pengeluaran DESC
            ");
            
            $stmt->execute([$user_id, $start_date, $end_date, $user_id, $end_date]);
            $categories = $stmt->fetchAll();
            
            $notifications = [];
            foreach ($categories as $category) {
                if ($category['jumlah_budget'] > 0) {
                    // Cek apakah sudah ada notifikasi untuk kategori ini hari ini
                    $already_notified = $this->hasSimilarNotificationToday(
                        $user_id, 
                        'budget', 
                        $category['kategori_id'],
                        $category['persentase']
                    );
                    
                    if (!$already_notified) {
                        if ($category['persentase'] >= 90) {
                            $notifications[] = [
                                'type' => 'danger',
                                'title' => '🚨 Budget Terlampaui!',
                                'message' => "Pengeluaran " . $category['nama_kategori'] . " sudah mencapai " . 
                                            number_format($category['persentase'], 1) . "% dari budget (Rp " . 
                                            number_format($category['total_pengeluaran'], 0, ',', '.') . ")",
                                'category' => $category['nama_kategori'],
                                'amount' => $category['total_pengeluaran'],
                                'related_module' => 'budget',
                                'related_id' => $category['kategori_id']
                            ];
                        } elseif ($category['persentase'] >= 70) {
                            $notifications[] = [
                                'type' => 'warning',
                                'title' => '⚠️ Budget Hampir Habis',
                                'message' => "Pengeluaran " . $category['nama_kategori'] . " sudah " . 
                                            number_format($category['persentase'], 1) . "% dari budget",
                                'category' => $category['nama_kategori'],
                                'amount' => $category['total_pengeluaran'],
                                'related_module' => 'budget',
                                'related_id' => $category['kategori_id']
                            ];
                        }
                    }
                }
                
                // Notifikasi untuk pengeluaran besar tanpa budget
                if ($category['total_pengeluaran'] > 1000000 && $category['jumlah_budget'] == 0) {
                    $already_notified = $this->hasSimilarNotificationToday(
                        $user_id, 
                        'budget_suggestion', 
                        $category['kategori_id']
                    );
                    
                    if (!$already_notified) {
                        $notifications[] = [
                            'type' => 'info',
                            'title' => '💡 Pertimbangkan Budget',
                            'message' => "Pengeluaran " . $category['nama_kategori'] . " cukup besar: Rp " . 
                                        number_format($category['total_pengeluaran'], 0, ',', '.') . 
                                        ". Pertimbangkan untuk membuat budget.",
                            'category' => $category['nama_kategori'],
                            'amount' => $category['total_pengeluaran'],
                            'related_module' => 'budget',
                            'related_id' => $category['kategori_id']
                        ];
                    }
                }
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in checkOverspendingCategories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 2. NOTIFIKASI: Jatuh tempo pembayaran
     * Untuk pembayaran, selalu generate (tapi cek duplikat)
     */
    public function checkDuePayments($user_id) {
        $today = date('Y-m-d');
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    title,
                    amount,
                    payment_type,
                    due_day,
                    CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-', due_day) as due_date
                FROM recurring_payments 
                WHERE user_id = ? 
                AND is_active = 1
                AND due_day BETWEEN DAY(CURDATE()) AND DAY(DATE_ADD(CURDATE(), INTERVAL 7 DAY))
            ");
            
            $stmt->execute([$user_id]);
            $due_payments = $stmt->fetchAll();
            
            $notifications = [];
            foreach ($due_payments as $payment) {
                $due_date = $payment['due_date'];
                $days_left = floor((strtotime($due_date) - strtotime($today)) / (60 * 60 * 24));
                
                if ($days_left >= 0) {
                    // Cek apakah sudah ada notifikasi untuk pembayaran ini hari ini
                    $already_notified = $this->hasSimilarNotificationToday(
                        $user_id, 
                        'payment', 
                        $payment['id']
                    );
                    
                    if (!$already_notified) {
                        $urgency = $days_left <= 2 ? 'danger' : 'warning';
                        
                        $notifications[] = [
                            'type' => $urgency,
                            'title' => '📅 Jatuh Tempo Pembayaran',
                            'message' => $payment['title'] . " jatuh tempo dalam " . $days_left . " hari - Rp " . 
                                        number_format($payment['amount'], 0, ',', '.'),
                            'due_date' => $due_date,
                            'amount' => $payment['amount'],
                            'days_left' => $days_left,
                            'related_module' => 'payment',
                            'related_id' => $payment['id']
                        ];
                    }
                }
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in checkDuePayments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 3. NOTIFIKASI: Budget hampir habis (75% usage)
     */
    public function checkBudgetUsage($user_id) {
        $today = date('Y-m-d');
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    b.id,
                    k.nama_kategori,
                    b.jumlah_budget,
                    COALESCE(SUM(t.jumlah_idr), 0) as total_pengeluaran,
                    (COALESCE(SUM(t.jumlah_idr), 0) / b.jumlah_budget * 100) as persentase
                FROM budget b
                JOIN kategori k ON b.kategori_id = k.id
                LEFT JOIN transaksi t ON b.kategori_id = t.kategori_id 
                    AND t.user_id = ? 
                    AND t.jenis = 'pengeluaran'
                    AND t.tanggal_transaksi BETWEEN b.tanggal_mulai AND b.tanggal_selesai
                WHERE b.user_id = ?
                AND ? BETWEEN b.tanggal_mulai AND b.tanggal_selesai
                GROUP BY b.id, k.nama_kategori, b.jumlah_budget
                HAVING persentase BETWEEN 75 AND 89
            ");
            
            $stmt->execute([$user_id, $user_id, $today]);
            $budgets = $stmt->fetchAll();
            
            $notifications = [];
            foreach ($budgets as $budget) {
                // Cek apakah sudah ada notifikasi untuk budget ini hari ini
                $already_notified = $this->hasSimilarNotificationToday(
                    $user_id, 
                    'budget_usage', 
                    $budget['id']
                );
                
                if (!$already_notified) {
                    $notifications[] = [
                        'type' => 'warning',
                        'title' => '💰 Budget Hampir Habis',
                        'message' => "Budget " . $budget['nama_kategori'] . " sudah terpakai " . 
                                    number_format($budget['persentase'], 1) . "%. Sisa: Rp " . 
                                    number_format($budget['jumlah_budget'] - $budget['total_pengeluaran'], 0, ',', '.'),
                        'category' => $budget['nama_kategori'],
                        'amount' => $budget['total_pengeluaran'],
                        'related_module' => 'budget',
                        'related_id' => $budget['id']
                    ];
                }
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in checkBudgetUsage: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 4. NOTIFIKASI: Pengeluaran tidak biasa
     * Hanya generate jika ada transaksi BARU hari ini
     */
    public function checkUnusualSpending($user_id) {
        $current_month = date('Y-m');
        $last_month = date('Y-m', strtotime('-1 month'));
        $today = date('Y-m-d');
        
        try {
            // Cek dulu apakah ada transaksi baru hari ini
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as new_transactions
                FROM transaksi 
                WHERE user_id = ? 
                AND jenis = 'pengeluaran'
                AND DATE(tanggal_transaksi) = ?
            ");
            
            $stmt->execute([$user_id, $today]);
            $new_transactions = $stmt->fetch()['new_transactions'];
            
            // Jika tidak ada transaksi baru hari ini, skip
            if ($new_transactions == 0) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    k.nama_kategori,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? THEN t.jumlah_idr ELSE 0 END), 0) as current_month,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? THEN t.jumlah_idr ELSE 0 END), 0) as last_month
                FROM kategori k
                LEFT JOIN transaksi t ON k.id = t.kategori_id 
                    AND t.user_id = ? 
                    AND t.jenis = 'pengeluaran'
                    AND (DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ? OR DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ?)
                WHERE k.tipe = 'pengeluaran'
                GROUP BY k.id, k.nama_kategori
                HAVING current_month > (last_month * 1.5) AND current_month > 200000
            ");
            
            $stmt->execute([$current_month, $last_month, $user_id, $current_month, $last_month]);
            $unusual_spending = $stmt->fetchAll();
            
            $notifications = [];
            foreach ($unusual_spending as $spending) {
                if ($spending['last_month'] > 0) {
                    $increase = (($spending['current_month'] - $spending['last_month']) / $spending['last_month']) * 100;
                    
                    // Cek apakah sudah ada notifikasi untuk kategori ini bulan ini
                    $already_notified = $this->hasSimilarNotificationThisMonth(
                        $user_id, 
                        'unusual_spending', 
                        $spending['nama_kategori']
                    );
                    
                    if (!$already_notified) {
                        $notifications[] = [
                            'type' => 'info',
                            'title' => '📈 Peningkatan Pengeluaran',
                            'message' => "Pengeluaran " . $spending['nama_kategori'] . " meningkat " . 
                                        number_format($increase, 0) . "% dari bulan lalu",
                            'category' => $spending['nama_kategori'],
                            'amount' => $spending['current_month'],
                            'related_module' => 'transaction',
                            'related_id' => null
                        ];
                    }
                }
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in checkUnusualSpending: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cek apakah sudah ada notifikasi serupa hari ini
     */
    private function hasSimilarNotificationToday($user_id, $module, $related_id, $persentase = null) {
        try {
            $sql = "
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? 
                AND related_module = ? 
                AND related_id = ? 
                AND DATE(created_at) = CURDATE()
            ";
            
            $params = [$user_id, $module, $related_id];
            
            if ($persentase !== null) {
                $sql .= " AND message LIKE ?";
                $params[] = '%' . number_format($persentase, 1) . '%';
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking similar notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cek apakah sudah ada notifikasi serupa bulan ini
     */
    private function hasSimilarNotificationThisMonth($user_id, $module, $category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? 
                AND related_module = ? 
                AND message LIKE ? 
                AND YEAR(created_at) = YEAR(CURDATE()) 
                AND MONTH(created_at) = MONTH(CURDATE())
            ");
            
            $stmt->execute([$user_id, $module, '%' . $category . '%']);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking similar notification this month: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Simpan notifikasi ke database
     */
    private function saveNotificationsToDatabase($user_id, $notifications) {
        try {
            $insert_stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_module, related_id, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 3 DAY))
            ");
            
            foreach ($notifications as $notification) {
                $insert_stmt->execute([
                    $user_id,
                    $notification['title'],
                    $notification['message'],
                    $notification['type'],
                    $notification['related_module'] ?? null,
                    $notification['related_id'] ?? null
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error saving notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ambil notifikasi dari database
     */
    public function getNotificationsFromDatabase($user_id, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY 
                    CASE type 
                        WHEN 'danger' THEN 1
                        WHEN 'warning' THEN 2  
                        WHEN 'info' THEN 3
                        ELSE 4
                    END,
                    created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Tandai notifikasi sebagai sudah dibaca
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    // ================= SARAN PENGHEMATAN =================
    
    /**
     * Generate saran penghematan otomatis (HANYA 1x per bulan)
     */
    public function getSavingsSuggestions($user_id, $month = null, $year = null) {
        if ($month === null) $month = date('m');
        if ($year === null) $year = date('Y');
        
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        try {
            // Cek apakah sudah generate saran bulan ini
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? 
                AND title LIKE '%Saran Penghematan%'
                AND YEAR(created_at) = YEAR(CURDATE()) 
                AND MONTH(created_at) = MONTH(CURDATE())
            ");
            
            $stmt->execute([$user_id]);
            $already_generated = $stmt->fetch()['count'] > 0;
            
            if ($already_generated) {
                return []; // Sudah generate saran bulan ini
            }
            
            // Analisis pola pengeluaran
            $stmt = $this->pdo->prepare("
                SELECT 
                    k.nama_kategori,
                    SUM(t.jumlah_idr) as total_pengeluaran,
                    COUNT(t.id) as frekuensi,
                    AVG(t.jumlah_idr) as rata_rata
                FROM transaksi t
                JOIN kategori k ON t.kategori_id = k.id
                WHERE t.user_id = ? 
                    AND t.jenis = 'pengeluaran'
                    AND t.tanggal_transaksi BETWEEN ? AND ?
                GROUP BY k.id, k.nama_kategori
                ORDER BY total_pengeluaran DESC
                LIMIT 3  // Hanya 3 kategori teratas
            ");
            
            $stmt->execute([$user_id, $start_date, $end_date]);
            $top_categories = $stmt->fetchAll();
            
            $suggestions = [];
            
            foreach ($top_categories as $category) {
                if ($category['total_pengeluaran'] > 300000) {
                    $savings_potential = $category['total_pengeluaran'] * 0.15;
                    
                    $suggestions[] = [
                        'category' => $category['nama_kategori'],
                        'current_spending' => $category['total_pengeluaran'],
                        'savings_potential' => $savings_potential,
                        'suggestion' => $this->generateSuggestion($category['nama_kategori'], $category['rata_rata'], $category['frekuensi'])
                    ];
                }
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log("Error in getSavingsSuggestions: " . $e->getMessage());
            return [];
        }
    }
    
    private function generateSuggestion($category, $average, $frequency) {
        $formatted_average = number_format($average, 0, ',', '.');
        
        $suggestions = [
            'Makanan' => "Dengan rata-rata Rp " . $formatted_average . " per transaksi (" . $frequency . "x/bulan), coba: • Masak sendiri 2x lebih sering • Bawa bekal • Kurangi delivery",
                         
            'Transportasi' => "Pengeluaran transportasi: Rp " . $formatted_average . " per transaksi. Saran: • Gunakan transportasi umum 3x/minggu • Carpool dengan teman",
                            
            'Hiburan' => $frequency . "x transaksi hiburan/bulan. Alternatif hemat: • Event kampus gratis • Taman kota • Game night di rumah",
                        
            'Belanja' => "Rata-rata Rp " . $formatted_average . " per belanja. Tips: • Buat daftar belanja • Tunggu 24 jam sebelum beli • Cari diskon",
                        
            'Pendidikan' => "Investasi pendidikan Rp " . $formatted_average . ". Hemat dengan: • E-book gratis • Perpustakaan kampus • Video tutorial online"
        ];
        
        return $suggestions[$category] ?? 
               "Evaluasi pengeluaran " . $category . ". Rata-rata Rp " . $formatted_average . " per transaksi (" . $frequency . "x/bulan). Cari alternatif yang lebih hemat.";
    }
    
    // ================= FUNGSI UTAMA =================
    
    /**
     * Gabungkan semua notifikasi (dari database + generated)
     */
    public function getAllNotifications($user_id) {
        // Generate notifikasi otomatis (dengan kontrol duplikat)
        $new_notifications = $this->generateAutomaticNotifications($user_id);
        
        // Ambil dari database
        return $this->getNotificationsFromDatabase($user_id);
    }

    // ========== POLYMORPHISM: METHOD OVERLOADING ==========
    public function markAsReadWithUserId($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
}

interface NotificationInterface {
    public function generateAutomaticNotifications($user_id);
    public function getNotificationsFromDatabase($user_id, $limit);
    public function markAsRead($notification_id, $user_id);
    public function getSavingsSuggestions($user_id);
}

// Helper function untuk menampilkan notifikasi
function displayNotifications($notifications, $max_display = 5) {
    if (empty($notifications)) {
        return '<div class="alert alert-info">🎉 Tidak ada notifikasi saat ini. Keuangan Anda sehat!</div>';
    }
    
    $html = '';
    $count = 0;
    
    foreach ($notifications as $notification) {
        if ($count >= $max_display) break;
        
        $icon = '';
        $alert_class = '';
        
        switch ($notification['type']) {
            case 'danger':
                $icon = 'fa-exclamation-triangle';
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $icon = 'fa-exclamation-circle';
                $alert_class = 'alert-warning';
                break;
            case 'info':
                $icon = 'fa-info-circle';
                $alert_class = 'alert-info';
                break;
            case 'success':
                $icon = 'fa-check-circle';
                $alert_class = 'alert-success';
                break;
            default:
                $icon = 'fa-bell';
                $alert_class = 'alert-secondary';
        }
        
        $read_class = $notification['is_read'] ? 'opacity-75' : '';
        $notification_id = $notification['id'] ?? 0;
        
        $html .= "
            <div class='alert {$alert_class} alert-dismissible fade show mb-2 {$read_class}' data-notification-id='{$notification_id}'>
                <i class='fas {$icon} me-2'></i>
                <strong>{$notification['title']}</strong><br>
                {$notification['message']}
                <div class='mt-1'>
                    <small class='text-muted'>
                        " . date('d/m H:i', strtotime($notification['created_at'])) . "
                        " . (!$notification['is_read'] ? '<span class="badge bg-primary ms-2">Baru</span>' : '') . "
                    </small>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>
        ";
        
        $count++;
    }
    
    // Tombol lihat semua notifikasi
    if (count($notifications) > $max_display) {
        $html .= '
            <div class="text-center mt-3">
                <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-list me-1"></i>Lihat Semua Notifikasi
                </a>
            </div>
        ';
    }
    
    return $html;
}
?>