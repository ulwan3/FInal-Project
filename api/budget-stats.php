<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.*, 
            k.nama_kategori,
            COALESCE(SUM(t.jumlah_idr), 0) as total_pengeluaran,
            (b.jumlah_budget - COALESCE(SUM(t.jumlah_idr), 0)) as sisa_budget
        FROM budget b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN transaksi t ON b.kategori_id = t.kategori_id 
            AND t.user_id = ? 
            AND t.jenis = 'pengeluaran'
            AND t.tanggal_transaksi BETWEEN b.tanggal_mulai AND b.tanggal_selesai
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.tanggal_mulai DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $budget_stats = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'budget_stats' => $budget_stats
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>