<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: mahasiswa/dashboard.php");
    }
    exit;
} else {
    header("Location: auth/login.php");
    exit;
}
?>