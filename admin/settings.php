<?php
$page_title = "Pengaturan Sistem";
require_once '../config/config.php';

// Cek apakah user sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

        <div class="card mt-4">
            <div class="card-header">
                <h5>Informasi Sistem</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Versi Aplikasi:</span>
                        <strong>1.0.0</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>PHP Version:</span>
                        <strong><?php echo phpversion(); ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Database:</span>
                        <strong>MySQL</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Server Software:</span>
                        <strong><?php echo $_SERVER['SERVER_SOFTWARE']; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>