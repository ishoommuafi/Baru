<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode']);
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($kode) || empty($nama)) {
        header("Location: ../admin_rooms.php?error=empty_gedung");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO gedung (kode, nama, deskripsi) VALUES (?, ?, ?)");
        $stmt->execute([strtoupper($kode), $nama, $deskripsi]);
        header("Location: ../admin_rooms.php?sukses=tambah_gedung");
        exit;
    } catch (PDOException $e) {
        // Cek jika kode sudah ada (unique constraint)
        if ($e->getCode() == 23000) {
            header("Location: ../admin_rooms.php?error=kode_exists");
        } else {
            header("Location: ../admin_rooms.php?error=system");
        }
        exit;
    }
}
header("Location: ../admin_rooms.php");
exit;
