<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

try {
    // Get transaction data for charts
    $stmt = $pdo->prepare("
        SELECT 
            DATE(tanggal_transaksi) as date,
            jenis,
            SUM(jumlah_idr) as amount
        FROM transaksi 
        WHERE user_id = ? 
        AND tanggal_transaksi BETWEEN ? AND ?
        GROUP BY DATE(tanggal_transaksi), jenis
        ORDER BY date
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $transaction_data = $stmt->fetchAll();
    
    // Get category breakdown
    $stmt = $pdo->prepare("
        SELECT 
            k.nama_kategori,
            t.jenis,
            SUM(t.jumlah_idr) as total
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        WHERE t.user_id = ? 
        AND t.tanggal_transaksi BETWEEN ? AND ?
        GROUP BY k.nama_kategori, t.jenis
        ORDER BY t.jenis, total DESC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $category_data = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'transaction_data' => $transaction_data,
        'category_data' => $category_data
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>