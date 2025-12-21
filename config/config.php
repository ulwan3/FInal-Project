<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
require_once 'env.php';

// Konfigurasi dari .env
define('SITE_NAME', Env::get('SITE_NAME', 'Keuangan Mahasiswa'));
define('SITE_URL', Env::get('SITE_URL', 'http://localhost/keuangan-mahasiswa'));
define('CURRENCY_API_KEY', Env::get('CURRENCY_API_KEY'));
define('CURRENCY_API_URL', Env::get('CURRENCY_API_URL'));
define('VAPID_PUBLIC_KEY', Env::get('VAPID_PUBLIC_KEY'));
define('VAPID_PRIVATE_KEY', Env::get('VAPID_PRIVATE_KEY'));
define('APP_ENV', Env::get('APP_ENV', 'production'));
define('APP_DEBUG', Env::get('APP_DEBUG', 'false') === 'true');

// Include database
require_once 'database.php';

// Fungsi helper
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMahasiswa(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa';
}

function redirect($url): never {
    header("Location: $url");
    exit;
}

function showError($message): void {
    echo "<div class='alert alert-danger'>$message</div>";
}

function showSuccess($message): void {
    echo "<div class='alert alert-success'>$message</div>";
}

// Debug function

abstract class BaseModel {
    protected $pdo;
    
    public function __construct($pdo = null) {
        global $pdo; // Gunakan global $pdo jika tidak disediakan
        $this->pdo = $pdo ?? $GLOBALS['pdo'] ?? null;
        
        if (!$this->pdo) {
            throw new Exception("Database connection not available");
        }
    }
}

?>