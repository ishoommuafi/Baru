<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    if (empty($id)) {
        header("Location: ../admin_rooms.php");
        exit;
    }

    try {
        // Cek apakah ada ruangan yang menggunakan gedung ini
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM ruangan WHERE gedung_id = ?");
        $stmtCek->execute([$id]);
        $count = $stmtCek->fetchColumn();

        if ($count > 0) {
            header("Location: ../admin_rooms.php?error=gedung_in_use");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM gedung WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ../admin_rooms.php?sukses=hapus_gedung");
        exit;
    } catch (PDOException $e) {
        header("Location: ../admin_rooms.php?error=system");
        exit;
    }
}
header("Location: ../admin_rooms.php");
exit;
