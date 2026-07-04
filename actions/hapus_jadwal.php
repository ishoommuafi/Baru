<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM jadwal_kuliah WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ../admin_jadwal.php?sukses=hapus");
        exit;
    }
}
header("Location: ../admin_jadwal.php");
exit;
