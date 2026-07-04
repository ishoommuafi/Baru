<?php
require_once 'config/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM peminjaman LIKE 'alasan_penolakan'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE peminjaman ADD COLUMN alasan_penolakan TEXT DEFAULT NULL");
        echo "Kolom alasan_penolakan berhasil ditambahkan.\n";
    } else {
        echo "Kolom alasan_penolakan sudah ada.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
