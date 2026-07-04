<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruangan_id = (int)$_POST['ruangan_id'];
    $mata_kuliah = trim($_POST['mata_kuliah']);
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    if ($ruangan_id && $mata_kuliah && $hari && $jam_mulai && $jam_selesai) {
        if (strtotime($jam_mulai) >= strtotime($jam_selesai)) {
            header("Location: ../admin_jadwal.php?error=jam_tidak_valid");
            exit;
        }

        // Cek overlap
        $stmtCek = $pdo->prepare("
            SELECT id FROM jadwal_kuliah 
            WHERE ruangan_id = ? AND hari = ?
            AND (jam_mulai < ? AND jam_selesai > ?)
        ");
        $stmtCek->execute([
            $ruangan_id, $hari, 
            $jam_selesai, $jam_mulai
        ]);

        if ($stmtCek->rowCount() > 0) {
            // Bentrok
            header("Location: ../admin_jadwal.php?error=bentrok");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO jadwal_kuliah (ruangan_id, mata_kuliah, hari, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ruangan_id, $mata_kuliah, $hari, $jam_mulai, $jam_selesai]);

        header("Location: ../admin_jadwal.php?sukses=tambah");
        exit;
    }
}
header("Location: ../admin_jadwal.php");
exit;
