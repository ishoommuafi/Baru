<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';
requireAdmin();

// Ringkasan
$totalRuangan = $pdo->query("SELECT COUNT(*) FROM ruangan")->fetchColumn();
$menunggu = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'pending'")->fetchColumn();
$terpakai = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'disetujui' AND tanggal = CURRENT_DATE()")->fetchColumn();

// Aktivitas terbaru
$stmt = $pdo->query("
    SELECT p.*, r.nama as nama_ruangan, u.nama as nama_peminjam
    FROM peminjaman p 
    JOIN ruangan r ON p.ruangan_id = r.id 
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC LIMIT 5
");
$terbaru = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA Admin - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="padding-bottom: 2rem;">

    <header class="admin-header">
        <h1>Admin SIPERKA</h1>
        <nav class="admin-nav">
            <a href="admin_dashboard.php" class="btn btn-primary btn-small">Dashboard</a>
            <a href="admin_rooms.php" class="btn btn-outline btn-small">Kelola Ruangan</a>
            <a href="admin_bookings.php" class="btn btn-outline btn-small">Peminjaman</a>
            <a href="auth/logout.php" class="btn btn-outline btn-small" style="color: var(--danger); border-color: var(--danger);">Logout</a>
        </nav>
    </header>

    <main style="padding-top: 0;">
        <h2 class="section-title">Ringkasan Sistem</h2>
        
        <div class="grid-cards">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                </div>
                <div class="stat-details">
                    <h3><?= $totalRuangan ?></h3>
                    <p>Total Ruangan</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #FFF4E5; color: var(--warning);">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div class="stat-details">
                    <h3><?= $menunggu ?></h3>
                    <p>Menunggu Persetujuan</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <div class="stat-details">
                    <h3><?= $terpakai ?></h3>
                    <p>Terpakai Hari Ini</p>
                </div>
            </div>
        </div>

        <section class="card">
            <h2 class="section-title">Aktivitas Terbaru</h2>
            <?php if (empty($terbaru)): ?>
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Belum ada aktivitas terbaru.</p>
            <?php else: ?>
                <?php foreach ($terbaru as $item): 
                    $badgeClass = '';
                    $statusLabel = '';
                    if ($item['status'] === 'pending') { $badgeClass = 'pending'; $statusLabel = 'Menunggu'; }
                    if ($item['status'] === 'disetujui') { $badgeClass = 'available'; $statusLabel = 'Disetujui'; }
                    if ($item['status'] === 'ditolak') { $badgeClass = 'unavailable'; $statusLabel = 'Ditolak'; }
                ?>
                <div class="list-item">
                    <div class="item-header">
                        <div class="item-title"><?= htmlspecialchars($item['nama_peminjam']) ?></div>
                        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    </div>
                    <div class="item-subtitle">
                        Kegiatan: <?= htmlspecialchars($item['nama_kegiatan']) ?><br>
                        Ruangan: <?= htmlspecialchars($item['nama_ruangan']) ?><br>
                        Waktu: <?= date('d M Y', strtotime($item['tanggal'])) ?>, <?= date('H:i', strtotime($item['waktu_mulai'])) ?> - <?= date('H:i', strtotime($item['waktu_selesai'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
