<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $gedung_kode = $_POST['gedung'] ?? '';
    $nama = trim($_POST['namaRuangan'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    $fasilitas_arr = $_POST['fasilitas'] ?? [];
    $status = $_POST['statusRuangan'] ?? 'aktif';
    
    if (empty($id) || empty($gedung_kode) || empty($nama) || $kapasitas <= 0) {
        header("Location: ../admin_rooms.php?error=empty");
        exit;
    }

    $fasilitas_str = implode(', ', $fasilitas_arr);

    // Dapatkan ID Gedung berdasarkan kode (a, b, c)
    $stmtGedung = $pdo->prepare("SELECT id FROM gedung WHERE LOWER(kode) = ?");
    $stmtGedung->execute([strtolower($gedung_kode)]);
    $gedung = $stmtGedung->fetch();

    if ($gedung) {
        $gedung_id = $gedung['id'];
        
        $stmt = $pdo->prepare("UPDATE ruangan SET gedung_id = ?, nama = ?, kapasitas = ?, fasilitas = ?, status = ? WHERE id = ?");
        $stmt->execute([$gedung_id, $nama, $kapasitas, $fasilitas_str, $status, $id]);
        
        header("Location: ../admin_rooms.php?sukses=edit");
        exit;
    } else {
        header("Location: ../admin_rooms.php?error=gedung_invalid");
        exit;
    }
} else {
    header("Location: ../admin_rooms.php");
    exit;
}
