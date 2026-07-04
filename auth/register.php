<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi kosong
    if (empty($nama) || empty($username) || empty($password) || empty($password_confirm)) {
        header("Location: ../register.php?error=empty");
        exit;
    }

    // Validasi kecocokan password
    if ($password !== $password_confirm) {
        header("Location: ../register.php?error=mismatch");
        exit;
    }

    // Cek ketersediaan username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        header("Location: ../register.php?error=exists");
        exit;
    }

    // Hash password & Insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'mahasiswa'; // Default role
    
    try {
        $stmtInsert = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$nama, $username, $hashed_password, $role]);
        
        // Redirect ke login dengan pesan sukses
        header("Location: ../login.php?sukses=register");
        exit;
    } catch (PDOException $e) {
        header("Location: ../register.php?error=system");
        exit;
    }
} else {
    header("Location: ../register.php");
    exit;
}
