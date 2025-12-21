<?php
session_start();
$page_title = "Profile Pengguna";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    // Hanya eksekusi jika BUKAN form password
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$nama_lengkap, $email, $user_id])) {
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $success = "Profile berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui profile!";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $user_id]);
            $success_password = "Password berhasil diubah!";
        } else {
            $error_password = "Password baru tidak cocok!";
        }
    } else {
        $error_password = "Password saat ini salah!";
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Profile Pengguna</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Informasi Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo $user['username']; ?>" readonly>
                        <div class="form-text">Username tidak dapat diubah</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                               value="<?php echo $user['nama_lengkap']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal Daftar</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($user['tanggal_daftar'])); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Ubah Password</h5>
            </div>
            <div class="card-body">
                <?php if (isset($success_password)): ?>
                <div class="alert alert-success"><?php echo $success_password; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_password)): ?>
                <div class="alert alert-danger"><?php echo $error_password; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">Ubah Password</button>
                </form>
            </div>
        </div>
        
        <!-- Account Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Statistik Akun</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $total_transactions = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT MAX(tanggal_transaksi) FROM transaksi WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $last_transaction = $stmt->fetchColumn();
                ?>
                
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Total Transaksi:</span>
                        <strong><?php echo $total_transactions; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Transaksi Terakhir:</span>
                        <strong><?php echo $last_transaction ? date('d/m/Y', strtotime($last_transaction)) : 'Belum ada'; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Login Terakhir:</span>
                        <strong><?php echo $user['terakhir_login'] ? date('d/m/Y H:i', strtotime($user['terakhir_login'])) : 'Belum ada'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>