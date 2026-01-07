<?php

// PASTIKAN SESSION START DIPANGGIL
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CACHE CONTROL UNTUK MENCEGAH BROWSER CACHE
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function checkAdmin() {
    checkAuth();
    
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini!";
        header("Location: ../mahasiswa/dashboard.php");
        exit;
    }
}

function checkMahasiswa() {
    checkAuth();
    
    if ($_SESSION['role'] !== 'mahasiswa') { // Sesuaikan dengan 'mahasiswa'
        $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini!";
        header("Location: ../admin/dashboard.php");
        exit;
    }
}

// Check if user is logged in and redirect if necessary
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Check if user is not logged in and redirect if necessary
function requireGuest() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../mahasiswa/dashboard.php");
        }
        exit;
    }
}

// Get current user info
function getCurrentUser() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    -
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Update last login time
function updateLastLogin($user_id) {
    global $pdo;
    
    $pdo->prepare("UPDATE users SET terakhir_login = NOW() WHERE id = ?")
        ->execute([$user_id]);
}
?>