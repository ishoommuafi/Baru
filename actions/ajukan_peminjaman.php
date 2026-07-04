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
        // Konversi tanggal ke hari
        $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariPeminjaman = $namaHari[date('w', strtotime($tanggal))];

        // Validasi 1: Cek jadwal kuliah (Prioritas Utama)
        $stmtJadwal = $pdo->prepare("
            SELECT id FROM jadwal_kuliah 
            WHERE ruangan_id = ? AND hari = ?
            AND (jam_mulai < ? AND jam_selesai > ?)
        ");
        $stmtJadwal->execute([$ruangan_id, $hariPeminjaman, $waktu_selesai, $waktu_mulai]);

        if ($stmtJadwal->rowCount() > 0) {
            header("Location: ../booking_form.php?ruangan_id=$ruangan_id&error=jadwal_kuliah");
            exit;
        }

        // Validasi 2: Cek peminjaman disetujui (overlap)
        $stmtCek = $pdo->prepare("
            SELECT id FROM peminjaman 
            WHERE ruangan_id = ? AND tanggal = ? AND status = 'disetujui'
            AND (waktu_mulai < ? AND waktu_selesai > ?)
        ");
        $stmtCek->execute([$ruangan_id, $tanggal, $waktu_selesai, $waktu_mulai]);

        if ($stmtCek->rowCount() > 0) {
            // Ada jadwal bentrok
            header("Location: ../booking_form.php?ruangan_id=$ruangan_id&error=peminjaman_overlap");
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
