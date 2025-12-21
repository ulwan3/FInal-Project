<?php
require_once '../config/config.php';

$page_title = "Lupa Password";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_username'])) {
        // Step 1: Check if username exists
        $username = $_POST['username'];
        
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Store user ID in session for next step
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_username'] = $user['username'];
            $_SESSION['reset_step'] = 'set_password';
        } else {
            $error = "Username tidak ditemukan!";
        }
        
    } elseif (isset($_POST['set_password'])) {
        // Step 2: Set new password
        if (isset($_SESSION['reset_user_id'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate passwords
            if (empty($new_password) || empty($confirm_password)) {
                $error = "Password tidak boleh kosong!";
            } elseif (strlen($new_password) < 6) {
                $error = "Password minimal 6 karakter!";
            } elseif ($new_password !== $confirm_password) {
                $error = "Password tidak cocok!";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);
                
                // Clear session and show success
                session_destroy();
                $success = "Password berhasil direset! Silakan login dengan password baru Anda.";
                
                // Auto redirect to login after 3 seconds
                header("refresh:3;url=login.php");
            }
        } else {
            $error = "Sesi tidak valid. Silakan mulai dari awal.";
            session_destroy();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-header text-center">
                        <h4>Lupa Password</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!isset($_SESSION['reset_step'])): ?>
                            <!-- STEP 1: Verify Username -->
                            <p class="text-muted mb-4">
                                Masukkan username Anda. Jika username ditemukan, Anda dapat langsung membuat password baru.
                            </p>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <button type="submit" name="check_username" class="btn btn-primary w-100">Verifikasi Username</button>
                            </form>
                            
                        <?php elseif ($_SESSION['reset_step'] === 'set_password'): ?>
                            <!-- STEP 2: Set New Password -->
                            <p class="text-muted mb-4">
                                Username <strong><?php echo htmlspecialchars($_SESSION['reset_username']); ?></strong> ditemukan. Silakan buat password baru Anda.
                            </p>
                            
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php elseif (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <?php if (!isset($success)): ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                        <div class="form-text">Minimal 6 karakter</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                    <button type="submit" name="set_password" class="btn btn-success w-100">Reset Password</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <a href="login.php">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>