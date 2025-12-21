<?php
$page_title = "Dashboard Mahasiswa";

// Include config terlebih dahulu
require_once '../config/config.php';

// Cek apakah user sudah login dan role mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Include notification system SETELAH $user_id didefinisikan
require_once '../includes/notification-system.php';

$notificationSystem = new NotificationSystem($pdo);

// Setelah include notification-system.php
require_once '../includes/smart-savings-system.php';

$savingsSystem = new SmartSavingsSystem($pdo);
$wasteful_categories = $savingsSystem->detectWastefulCategories($user_id);
$savings_plan = $savingsSystem->getPersonalizedSavingsPlan($user_id);

// Panggil getAllNotifications() bukan generateAutomaticNotifications()
$notifications = $notificationSystem->getAllNotifications($user_id);
$savingsSuggestions = $notificationSystem->getSavingsSuggestions($user_id);


// POLYMORPHISM: Menggunakan method overload
$notificationSystem->markAsReadWithUserId(123, $user_id); // Versi 2

// Get total transactions
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah_idr ELSE 0 END), 0) as total_pemasukan,
        COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' THEN jumlah_idr ELSE 0 END), 0) as total_pengeluaran
    FROM transaksi 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get recent transactions
$stmt = $pdo->prepare("
    SELECT t.*, k.nama_kategori, m.kode_uang 
    FROM transaksi t 
    LEFT JOIN kategori k ON t.kategori_id = k.id 
    LEFT JOIN mata_uang m ON t.mata_uang_id = m.id 
    WHERE t.user_id = ? 
    ORDER BY t.tanggal_transaksi DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();

// Get current exchange rates
$stmt = $pdo->prepare("SELECT kode_uang, nama_uang, nilai_kurs_terhadap_idr FROM mata_uang WHERE status_aktif = 1");
$stmt->execute();
$exchange_rates = $stmt->fetchAll();
?>

<!-- Content Header -->
<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-tachometer-alt me-3"></i>Dashboard</h1>
            <p class="mb-0 text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! 👋</p>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row fade-in">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">TOTAL TRANSAKSI</h5>
                        <h2><?php echo $stats['total_transaksi']; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">TOTAL PEMASUKAN</h5>
                        <h2>Rp <?php echo number_format($stats['total_pemasukan'], 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #e63946, #f72585);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">TOTAL PENGELUARAN</h5>
                        <h2>Rp <?php echo number_format($stats['total_pengeluaran'], 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #ff9e00, #ff6b00);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">SALDO</h5>
                        <h2>Rp <?php echo number_format($stats['total_pemasukan'] - $stats['total_pengeluaran'], 0, ',', '.'); ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Section -->
<div class="row fade-in">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifikasi & Peringatan</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="test-notification">
                    <i class="fas fa-bell me-1"></i>Notifikasi
                </button>
            </div>
            <div class="card-body">
                <div data-user-id="<?php echo $user_id; ?>">
                    <?php echo displayNotifications($notifications); ?>
                </div>
                
                <?php if (!empty($savingsSuggestions)): ?>
                <div class="mt-4">
                    <h6><i class="fas fa-lightbulb me-2"></i>Saran Penghematan</h6>
                    <?php foreach ($savingsSuggestions as $suggestion): ?>
                    <div class="alert alert-info mt-2">
                        <strong><?php echo $suggestion['category']; ?></strong><br>
                        <?php echo $suggestion['suggestion']; ?><br>
                        <small class="text-muted">
                            Potensi penghematan: Rp <?php echo number_format($suggestion['savings_potential'], 0, ',', '.'); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <!-- Exchange Rates -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Kurs Mata Uang</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mata Uang</th>
                                <th class="text-end">Kurs ke IDR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exchange_rates as $rate): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $rate['kode_uang']; ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $rate['nama_uang']; ?></small>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    <?php echo number_format($rate['nilai_kurs_terhadap_idr'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Transaksi Terbaru</h5>
                <a href="transaksi.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye me-1"></i>Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_transactions) > 0): ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($transaction['tanggal_transaksi'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo $transaction['nama_kategori']; ?></span>
                                    </td>
                                    <td class="text-truncate" style="max-width: 200px;" title="<?php echo $transaction['deskripsi']; ?>">
                                        <?php echo $transaction['deskripsi'] ?: '-'; ?>
                                    </td>
                                    <td class="text-end fw-bold <?php echo $transaction['jenis'] == 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($transaction['jumlah'], 2, ',', '.'); ?>
                                        <?php echo $transaction['kode_uang']; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['jenis'] == 'pemasukan' ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $transaction['jenis'] == 'pemasukan' ? 'arrow-down' : 'arrow-up'; ?> me-1"></i>
                                            <?php echo ucfirst($transaction['jenis']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada transaksi</p>
                                        <a href="transaksi.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Tambah Transaksi Pertama
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Wasteful Categories Section -->
<div class="row fade-in mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-search-dollar me-2"></i>Analisis Pengeluaran Boros</h5>
                <span class="badge bg-warning">
                    <?php echo count($wasteful_categories); ?> Kategori Perlu Dioptimasi
                </span>
            </div>
            <div class="card-body">
                <?php 
                // PASTIKAN FUNGSI INI SUDAH TERDEFINISI
                if (function_exists('displayWastefulCategories')) {
                    echo displayWastefulCategories($wasteful_categories);
                } else {
                    echo '<div class="alert alert-danger">Fungsi displayWastefulCategories tidak ditemukan!</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Savings Plan Section -->
<div class="row fade-in mt-4">
    <div class="col-12">
        <?php 
        if (function_exists('displaySavingsPlan')) {
            echo displaySavingsPlan($savings_plan);
        } else {
            echo '<div class="alert alert-danger">Fungsi displaySavingsPlan tidak ditemukan!</div>';
        }
        ?>
    </div>
</div>

<!-- Tambahkan script web push -->
<script src="../assets/js/web-push.js"></script>

<?php require_once '../includes/footer.php'; ?>