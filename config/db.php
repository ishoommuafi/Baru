<?php
/**
 * SIPERKA - Koneksi Database (PDO)
 * 
 * Konfigurasi untuk Laragon default:
 * Host: localhost | User: root | Password: (kosong)
 */

$DB_HOST = 'localhost';
$DB_NAME = 'siperka';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    
    // Set PDO options
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan yang ramah
    die("
    <div style='font-family: sans-serif; padding: 2rem; text-align: center;'>
        <h2 style='color: #dc3545;'>Koneksi Database Gagal</h2>
        <p>Pastikan MySQL di Laragon sudah berjalan dan database <b>$DB_NAME</b> telah dibuat.</p>
        <p style='color: #666; font-size: 0.9em;'>Error detail: " . $e->getMessage() . "</p>
    </div>
    ");
}
