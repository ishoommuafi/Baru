<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $ruangan_id = (int)$_POST['ruangan_id'];
    $mata_kuliah = trim($_POST['mata_kuliah']);
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    if ($id && $ruangan_id && $mata_kuliah && $hari && $jam_mulai && $jam_selesai) {
        if (strtotime($jam_mulai) >= strtotime($jam_selesai)) {
            header("Location: ../admin_jadwal.php?error=jam_tidak_valid");
            exit;
        }

        // Cek overlap (exclude diri sendiri)
        $stmtCek = $pdo->prepare("
            SELECT id FROM jadwal_kuliah 
            WHERE ruangan_id = ? AND hari = ? AND id != ?
            AND (jam_mulai < ? AND jam_selesai > ?)
        ");
        $stmtCek->execute([
            $ruangan_id, $hari, $id,
            $jam_selesai, $jam_mulai
        ]);

        if ($stmtCek->rowCount() > 0) {
            // Bentrok
            header("Location: ../admin_jadwal.php?error=bentrok");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE jadwal_kuliah SET ruangan_id = ?, mata_kuliah = ?, hari = ?, jam_mulai = ?, jam_selesai = ? WHERE id = ?");
        $stmt->execute([$ruangan_id, $mata_kuliah, $hari, $jam_mulai, $jam_selesai, $id]);

        header("Location: ../admin_jadwal.php?sukses=edit");
        exit;
    }
}
header("Location: ../admin_jadwal.php");
exit;
