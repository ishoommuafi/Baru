<?php
session_start();
require_once 'config/db.php';

$isLoggedIn = isset($_SESSION['user_id']);

$stmt = $pdo->query("
    SELECT p.*, r.nama as nama_ruangan, u.nama as nama_peminjam
    FROM peminjaman p 
    JOIN ruangan r ON p.ruangan_id = r.id 
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'disetujui' AND p.tanggal >= CURRENT_DATE()
    ORDER BY p.tanggal ASC, p.waktu_mulai ASC
");
$jadwal = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA - Status Peminjaman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>SIPERKA</h1>
        <p>Sistem Peminjaman Ruang Kampus</p>
    </header>

    <nav class="desktop-nav">
        <a href="index.php">Beranda</a>
        <a href="status.php" class="active">Jadwal Global</a>
        <?php if ($isLoggedIn): ?>
            <a href="history.php">Riwayat Saya</a>
            <a href="auth/logout.php" class="login-btn" style="color:var(--danger); border-color:var(--danger);">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </nav>

    <main>
        <section class="card">
            <h2 class="section-title">Jadwal Ruangan Disetujui (Mendatang)</h2>
            
            <?php if (empty($jadwal)): ?>
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Belum ada jadwal peminjaman ruangan yang disetujui.</p>
            <?php else: ?>
                <?php foreach ($jadwal as $item): 
                    $isToday = ($item['tanggal'] === date('Y-m-d'));
                    $now = date('H:i:s');
                    $isOngoing = $isToday && ($now >= $item['waktu_mulai'] && $now <= $item['waktu_selesai']);
                    $badgeClass = $isOngoing ? 'unavailable' : 'pending';
                    $badgeLabel = $isOngoing ? 'Berlangsung' : 'Akan Datang';
                ?>
                <div class="list-item">
                    <div class="item-header">
                        <div class="item-title"><?= htmlspecialchars($item['nama_ruangan']) ?></div>
                        <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                    </div>
                    <div class="item-subtitle">
                        Peminjam: <?= htmlspecialchars($item['nama_peminjam']) ?><br>
                        Kegiatan: <?= htmlspecialchars($item['nama_kegiatan']) ?><br>
                        Tanggal: <?= date('d M Y', strtotime($item['tanggal'])) ?><br>
                        Waktu: <?= date('H:i', strtotime($item['waktu_mulai'])) ?> - <?= date('H:i', strtotime($item['waktu_selesai'])) ?> WIB
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <nav class="mobile-nav">
        <div class="mobile-nav-container">
            <a href="index.php" class="mobile-nav-item">Beranda</a>
            <a href="status.php" class="mobile-nav-item active">Jadwal</a>
            <?php if ($isLoggedIn): ?>
                <a href="history.php" class="mobile-nav-item">Riwayat</a>
            <?php endif; ?>
            <a href="<?= $isLoggedIn ? 'auth/logout.php' : 'login.php' ?>" class="mobile-nav-item">Akun</a>
        </div>
    </nav>

</body>
</html>
