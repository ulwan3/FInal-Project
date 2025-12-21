<?php
$page_title = "Notifikasi Saya";
require_once '../config/config.php';

// Cek apakah user sudah login dan role mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle mark as read - HARUS SEBELUM include header/sidebar
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    require_once '../includes/notification-system.php';
    $notificationSystem = new NotificationSystem($pdo);
    $notificationSystem->markAsRead($_GET['mark_read'], $_SESSION['user_id']);
    header("Location: notifications.php");
    exit;
}

// Handle mark all as read - HARUS SEBELUM include header/sidebar
if (isset($_GET['mark_all_read'])) {
    require_once '../includes/notification-system.php';
    $notificationSystem = new NotificationSystem($pdo);
    $notifications = $notificationSystem->getNotificationsFromDatabase($_SESSION['user_id'], 100);
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $notificationSystem->markAsRead($notification['id'], $_SESSION['user_id']);
        }
    }
    header("Location: notifications.php");
    exit;
}

// SEKARANG baru include header dan sidebar
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];
require_once '../includes/notification-system.php';

$notificationSystem = new NotificationSystem($pdo);
$notifications = $notificationSystem->getNotificationsFromDatabase($user_id, 50);
$unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
?>

<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-bell me-3"></i>Notifikasi Saya</h1>
            <p class="mb-0 text-muted">Kelola semua pemberitahuan dan peringatan keuangan</p>
        </div>
        <div class="btn-toolbar">
            <?php if ($unread_count > 0): ?>
            <a href="?mark_all_read=1" class="btn btn-success me-2">
                <i class="fas fa-check-double me-2"></i>Tandai Semua Dibaca
            </a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row fade-in">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Semua Notifikasi</h5>
                <span class="badge bg-<?php echo $unread_count > 0 ? 'danger' : 'success'; ?>">
                    <?php echo $unread_count; ?> Belum Dibaca
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak Ada Notifikasi</h5>
                        <p class="text-muted">Semua notifikasi akan muncul di sini</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-warning'; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-<?php 
                                            echo $notification['type'] == 'danger' ? 'exclamation-triangle' : 
                                                 ($notification['type'] == 'warning' ? 'exclamation-circle' : 
                                                 ($notification['type'] == 'success' ? 'check-circle' : 'info-circle')); 
                                        ?> text-<?php echo $notification['type']; ?> me-2"></i>
                                        <h6 class="mb-0"><?php echo $notification['title']; ?></h6>
                                        <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-primary ms-2">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1"><?php echo $notification['message']; ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                        <?php if ($notification['related_module']): ?>
                                        • <i class="fas fa-link me-1"></i><?php echo ucfirst($notification['related_module']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="ms-3">
                                    <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['id']; ?>" 
                                       class="btn btn-sm btn-outline-success" 
                                       title="Tandai sudah dibaca">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Dibaca</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>