<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $kode = trim($_POST['kode']);
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($id) || empty($kode) || empty($nama)) {
        header("Location: ../admin_rooms.php?error=empty_gedung");
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE gedung SET kode = ?, nama = ?, deskripsi = ? WHERE id = ?");
        $stmt->execute([strtoupper($kode), $nama, $deskripsi, $id]);
        header("Location: ../admin_rooms.php?sukses=edit_gedung");
        exit;
    } catch (PDOException $e) {
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
