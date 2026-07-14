<?php
// File: logout.php (root)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua data sesi
session_unset();
session_destroy();

// Kembali ke halaman utama
header('Location: index.php');
exit();
?>