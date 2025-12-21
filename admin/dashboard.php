<?php
$page_title = "Dashboard Admin";

// Include config terlebih dahulu
require_once '../config/config.php';

// Cek apakah user sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Dapatkan Statistik
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_mahasiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
$total_transactions = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
$total_currencies = $pdo->query("SELECT COUNT(*) FROM mata_uang WHERE status_aktif = 1")->fetchColumn();

// Dapatkan Pengguna Terkini
$recent_users = $pdo->query("SELECT username, nama_lengkap, tanggal_daftar, role FROM users ORDER BY tanggal_daftar DESC LIMIT 5")->fetchAll();

// Dapatkan status sistem
$last_currency_update = $pdo->query("SELECT MAX(tanggal_update) FROM mata_uang")->fetchColumn();
?>

<!-- Content Header -->
<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-tachometer-alt me-3"></i>Dashboard Admin</h1>
            <p class="mb-0 text-muted">Overview sistem dan statistik pengguna 📊</p>
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
                        <h5 class="card-title">TOTAL PENGGUNA</h5>
                        <h2><?php echo $total_users; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users"></i>
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
                        <h5 class="card-title">TOTAL MAHASISWA</h5>
                        <h2><?php echo $total_mahasiswa; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-graduate"></i>
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
                        <h5 class="card-title">TOTAL TRANSAKSI</h5>
                        <h2><?php echo $total_transactions; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exchange-alt"></i>
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
                        <h5 class="card-title">MATA UANG</h5>
                        <h2><?php echo $total_currencies; ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in">
    <!-- System Info -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <span class="text-muted">Update Kurs Terakhir:</span>
                        <strong class="text-primary"><?php echo $last_currency_update ? date('d/m/Y H:i', strtotime($last_currency_update)) : 'Belum ada'; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <span class="text-muted">Total Kategori:</span>
                        <strong class="text-primary"><?php echo $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn(); ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <span class="text-muted">Waktu Server:</span>
                        <strong class="text-primary"><?php echo date('d/m/Y H:i:s'); ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <span class="text-muted">Status Sistem:</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pengguna Terkini -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Pengguna Terbaru</h5>
                <a href="manage-users.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-users me-1"></i>Kelola
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $user['nama_lengkap']; ?></div>
                                            <small class="text-muted">@<?php echo $user['username']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($user['tanggal_daftar'])); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>