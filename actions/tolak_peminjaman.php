<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $alasan = trim($_POST['alasan_penolakan'] ?? '');
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE peminjaman SET status = 'ditolak', alasan_penolakan = ? WHERE id = ?");
        $stmt->execute([$alasan, $id]);
    }
    header("Location: ../admin_bookings.php?sukses=tolak");
    exit;
} else {
    header("Location: ../admin_bookings.php");
    exit;
}
