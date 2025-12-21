<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include config
require_once '../config/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../mahasiswa/dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['email'] = $user['email'];

        
        // Update last login
        $pdo->prepare("UPDATE users SET terakhir_login = NOW() WHERE id = ?")
            ->execute([$user['id']]);
        
        if ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../mahasiswa/dashboard.php");
        }
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Keuangan Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container fade-in">
        <div class="login-card">
            <div class="card-header">
                <h4><i class="fas fa-wallet me-2"></i>Keuangan Mahasiswa</h4>
                <p class="mb-0 mt-2 opacity-75">Masuk ke akun Anda</p>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-user text-primary"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="username" name="username" 
                                   placeholder="Masukkan username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-lock text-primary"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" 
                                   placeholder="Masukkan password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                
                <div class="text-center mt-4">
                     <p class="mb-2">
                         <a href="forgot-password.php" class="text-decoration-none">
                            <i class="fas fa-key me-1"></i>Lupa Password?
                        </a>
                    </p>
                    <p class="text-muted">Belum punya akun? 
                        <a href="register.php" class="text-primary text-decoration-none fw-bold">Daftar di sini</a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-white opacity-75">
                &copy; 2025 Keuangan Mahasiswa. All rights reserved.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>