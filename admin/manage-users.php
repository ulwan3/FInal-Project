<?php
$page_title = "Kelola Pengguna";
require_once '../config/config.php';

// Cek apakah user sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Tangani Tindakan
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    if ($_GET['action'] == 'delete' && $user_id != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $success = "Pengguna berhasil dihapus!";
    } elseif ($_GET['action'] == 'reset') {
        $new_password = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_password, $user_id]);
        $success = "Password berhasil direset ke 'password123'!";
    }
}

// Dapatkan semua pengguna
$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Pengguna</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5>Daftar Pengguna</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="usersTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>NIM</th>
                        <th>Program Studi</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['nama_lengkap']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo $user['nim'] ?: '-'; ?></td>
                        <td><?php echo $user['program_studi'] ?: '-'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($user['tanggal_daftar'])); ?></td>
                        <td>

                           <div class="btn-group btn-group-sm">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>                           
                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger"
                                onclick="return confirm('Hapus pengguna ini?')" title="Hapus Pengguna">
                                <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-user-check"></i></span>
                                    <?php endif; ?>
                                    </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>