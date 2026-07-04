<?php

/**
 * Pastikan user sudah login.
 * Jika belum, redirect ke halaman login.
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Pastikan user yang login adalah admin.
 * Jika mahasiswa biasa, redirect ke beranda mahasiswa.
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }
}
