<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    if ($id) {
        // Pastikan hanya pemilik peminjaman yang bisa membatalkan dan status masih pending
        $stmt = $pdo->prepare("UPDATE peminjaman SET status = 'dibatalkan' WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$id, $user_id]);
    }
    header("Location: ../history.php?sukses_batal=1");
    exit;
} else {
    header("Location: ../history.php");
    exit;
}
