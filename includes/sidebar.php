<?php
// Include config untuk mendapatkan fungsi helper
require_once '../config/config.php';

// Cek role user
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse position-fixed">
            <div class="position-sticky pt-3">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <!-- Menu Admin -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>" href="../admin/manage-users.php">
                                <i class="fas fa-users"></i> Kelola Pengguna
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'currency-management.php' ? 'active' : ''; ?>" href="../admin/currency-management.php">
                                <i class="fas fa-exchange-alt"></i> Kurs Mata Uang
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="../admin/reports.php">
                                <i class="fas fa-chart-bar"></i> Laporan Sistem
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="../admin/settings.php">
                                <i class="fas fa-cog"></i> Pengaturan
                            </a>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- Menu Mahasiswa -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="../mahasiswa/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>" href="../mahasiswa/transaksi.php">
                                <i class="fas fa-money-bill-wave"></i> Transaksi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>" href="../mahasiswa/budget.php">
                                <i class="fas fa-chart-pie"></i> Budget
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>" href="../mahasiswa/laporan.php">
                                <i class="fas fa-file-alt"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="../mahasiswa/notifications.php">
                                <i class="fas fa-bell"></i> Notifikasi
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" >