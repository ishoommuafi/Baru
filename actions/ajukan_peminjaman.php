<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $ruangan_id = $_POST['ruangan_id'] ?? null;
    $nama_kegiatan = trim($_POST['kegiatan'] ?? '');
    $tanggal = $_POST['tanggal'] ?? null;
    $waktu_mulai = $_POST['waktu_mulai'] ?? null;
    $waktu_selesai = $_POST['waktu_selesai'] ?? null;
    $jumlah_peserta = (int)($_POST['peserta'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($ruangan_id && $nama_kegiatan && $tanggal && $waktu_mulai && $waktu_selesai && $jumlah_peserta > 0) {
        // Cek konflik (sederhana)
        $stmtCek = $pdo->prepare("
            SELECT id FROM peminjaman 
            WHERE ruangan_id = ? AND tanggal = ? AND status != 'ditolak'
            AND (
                (waktu_mulai <= ? AND waktu_selesai >= ?) OR
                (waktu_mulai <= ? AND waktu_selesai >= ?) OR
                (? <= waktu_mulai AND ? >= waktu_selesai)
            )
        ");
        $stmtCek->execute([
            $ruangan_id, $tanggal, 
            $waktu_mulai, $waktu_mulai, 
            $waktu_selesai, $waktu_selesai, 
            $waktu_mulai, $waktu_selesai
        ]);

        if ($stmtCek->rowCount() > 0) {
            // Ada jadwal bentrok
            header("Location: ../booking_form.php?ruangan_id=$ruangan_id&error=konflik");
            exit;
        }

        // Insert
        $stmt = $pdo->prepare("INSERT INTO peminjaman (user_id, ruangan_id, nama_kegiatan, tanggal, waktu_mulai, waktu_selesai, jumlah_peserta, keterangan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $ruangan_id, $nama_kegiatan, $tanggal, $waktu_mulai, $waktu_selesai, $jumlah_peserta, $keterangan]);
        
        header("Location: ../history.php?sukses=1");
        exit;
    }
    
    header("Location: ../index.php?error=data_tidak_lengkap");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
