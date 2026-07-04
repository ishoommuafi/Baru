<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_gedung = strtoupper($_POST['gedung'] ?? '');
    $nama = trim($_POST['namaRuangan'] ?? '');
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    $fasilitas_arr = $_POST['fasilitas'] ?? [];
    $fasilitas = is_array($fasilitas_arr) ? implode(', ', $fasilitas_arr) : '-';
    if (empty($fasilitas)) $fasilitas = '-';
    $status = $_POST['statusRuangan'] ?? 'aktif';

    if ($kode_gedung && $nama && $kapasitas > 0) {
        $stmtG = $pdo->prepare("SELECT id FROM gedung WHERE kode = ?");
        $stmtG->execute([$kode_gedung]);
        $gedung_id = $stmtG->fetchColumn();

        if ($gedung_id) {
            $stmt = $pdo->prepare("INSERT INTO ruangan (gedung_id, nama, kapasitas, fasilitas, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$gedung_id, $nama, $kapasitas, $fasilitas, $status]);
        }
    }
    header("Location: ../admin_rooms.php?sukses=tambah");
    exit;
} else {
    header("Location: ../admin_rooms.php");
    exit;
}
