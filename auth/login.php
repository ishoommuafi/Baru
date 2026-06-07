<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=empty");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        // Password correct
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama'] = $user['nama'];

        if ($user['role'] === 'admin') {
            header("Location: ../admin_dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        // Password incorrect
        header("Location: ../login.php?error=invalid");
        exit;
    }
} else {
    // If accessed not via POST
    header("Location: ../login.php");
    exit;
}
