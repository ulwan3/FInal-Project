<?php
// Pastikan session sudah start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include config untuk mendapatkan fungsi helper
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Keuangan Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <?php if (isset($page_css)): ?>
    <link href="../assets/css/<?php echo $page_css; ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    
    <div class="main-wrapper">
        <!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <!-- Logo/Brand -->
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-wallet"></i> Keuangan Mahasiswa
        </a>
        
        <!-- ========================
             HAMBURGER MENU TOGGLE
             (Hanya tampil di mobile)
        ======================== -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMobileContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- ========================
             DESKTOP MENU 
             (Hanya tampil di desktop)
        ======================== -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto d-none d-lg-flex"> <!-- Hanya desktop -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <!-- Avatar User -->
                        <div class="user-avatar bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                             style="width: 35px; height: 35px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <!-- Info User -->
                        <div class="user-info text-start">
                            <div class="user-name fw-bold"><?php echo $_SESSION['nama_lengkap']; ?></div>
                            <div class="user-role small opacity-50">
                                <?php echo ucfirst($_SESSION['role']); ?>
                            </div>
                        </div>
                    </a>
                    <!-- Dropdown Menu Desktop -->
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            <strong><?php echo $_SESSION['nama_lengkap']; ?></strong><br>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../<?php echo $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/profile.php'; ?>">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        
        <!-- ========================
             MOBILE MENU CONTENT
             (Hanya tampil di mobile)
        ======================== -->
        <div class="collapse navbar-collapse" id="navbarMobileContent">
            <div class="navbar-nav mt-3">
                
                <!-- USER INFO CARD di Mobile -->
                <div class="card mb-3 bg-dark text-white">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">

                            <!-- Avatar -->
                            <div class="user-avatar bg-white text-primary rounded-circle
                                        d-flex align-items-center justify-content-center me-3"
                                style="width: 45px; height: 45px;">
                                <i class="fas fa-user fs-5"></i>
                            </div>

                            <!-- Info -->
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo $_SESSION['nama_lengkap']; ?></div>
                                <div class="small opacity-75">
                                    <i class="fas fa-user-tag me-1"></i>
                                    <?php echo ucfirst($_SESSION['role']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <!-- MENU ADMIN untuk Mobile -->
                <a class="nav-link text-white py-2" href="../admin/dashboard.php">
                    <i class="fas fa-tachometer-alt me-3"></i>Dashboard Admin
                </a>
                
                <a class="nav-link text-white py-2" href="../admin/manage-users.php">
                    <i class="fas fa-users me-3"></i>Kelola Pengguna
                </a>
                
                <a class="nav-link text-white py-2" href="../admin/currency-management.php">
                    <i class="fas fa-exchange-alt me-3"></i>Kurs Mata Uang
                </a>
                
                <a class="nav-link text-white py-2" href="../admin/reports.php">
                    <i class="fas fa-chart-bar me-3"></i>Laporan Sistem
                </a>
                
                <a class="nav-link text-white py-2" href="../admin/settings.php">
                    <i class="fas fa-cog me-3"></i>Pengaturan
                </a>
                
                <?php else: ?>
                <!-- MENU MAHASISWA untuk Mobile -->
                <a class="nav-link text-white py-2" href="../mahasiswa/dashboard.php">
                    <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                </a>
                
                <a class="nav-link text-white py-2" href="../mahasiswa/transaksi.php">
                    <i class="fas fa-money-bill-wave me-3"></i>Transaksi
                </a>
                
                <a class="nav-link text-white py-2" href="../mahasiswa/budget.php">
                    <i class="fas fa-chart-pie me-3"></i>Budget
                </a>
                
                <a class="nav-link text-white py-2" href="../mahasiswa/laporan.php">
                    <i class="fas fa-file-alt me-3"></i>Laporan
                </a>
                
                <a class="nav-link text-white py-2" href="../mahasiswa/notifications.php">
                    <i class="fas fa-bell me-3"></i>Notifikasi
                </a>
                
                <?php endif; ?>
                
                <!-- Menu Profile dan Logout (umum untuk semua role) -->
                <a class="nav-link text-white py-2" href="../<?php echo $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/profile.php'; ?>">
                    <i class="fas fa-user-circle me-3"></i>Profile
                </a>
                
                <hr class="text-white-50 my-2">
                
                <a class="nav-link text-danger py-2" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>

        <div class="container-fluid">
            <div class="row">