<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT p.*, r.nama as nama_ruangan 
    FROM peminjaman p 
    JOIN ruangan r ON p.ruangan_id = r.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA - Riwayat Peminjaman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>SIPERKA</h1>
        <p>Sistem Peminjaman Ruang Kampus</p>
    </header>

    <nav class="desktop-nav">
        <a href="index.php">Beranda</a>
        <a href="status.php">Jadwal Global</a>
        <a href="history.php" class="active">Riwayat Saya</a>
        <a href="auth/logout.php" class="login-btn" style="color:var(--danger); border-color:var(--danger);">Logout</a>
    </nav>

    <main>
        <?php if (isset($_GET['sukses'])): ?>
            <div style="background: #E6F6ED; color: var(--success); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Peminjaman berhasil diajukan dan sedang menunggu persetujuan Admin!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sukses_batal'])): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Peminjaman berhasil dibatalkan.
            </div>
        <?php endif; ?>

        <section class="card">
            <h2 class="section-title">Riwayat Peminjaman Saya</h2>
            
            <?php if (empty($riwayat)): ?>
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Belum ada riwayat peminjaman.</p>
            <?php else: ?>
                <?php foreach ($riwayat as $item): 
                    $badgeClass = '';
                    $statusLabel = '';
                    if ($item['status'] === 'pending') { $badgeClass = 'pending'; $statusLabel = 'Menunggu'; }
                    if ($item['status'] === 'disetujui') { $badgeClass = 'available'; $statusLabel = 'Disetujui'; }
                    if ($item['status'] === 'ditolak') { $badgeClass = 'unavailable'; $statusLabel = 'Ditolak'; }
                    if ($item['status'] === 'dibatalkan') { $badgeClass = 'unavailable'; $statusLabel = 'Dibatalkan'; }
                ?>
                <div class="list-item">
                    <div class="item-header">
                        <div class="item-title"><?= htmlspecialchars($item['nama_ruangan']) ?></div>
                        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    </div>
                    <div class="item-subtitle">
                        <?= htmlspecialchars($item['nama_kegiatan']) ?><br>
                        Tanggal: <?= date('d M Y', strtotime($item['tanggal'])) ?><br>
                        Waktu: <?= date('H:i', strtotime($item['waktu_mulai'])) ?> - <?= date('H:i', strtotime($item['waktu_selesai'])) ?> WIB
                        <?php if ($item['status'] === 'ditolak' && !empty($item['alasan_penolakan'])): ?>
                            <div style="margin-top: 0.5rem; padding: 0.5rem; background: var(--danger-bg); border-left: 3px solid var(--danger); border-radius: 4px; font-size: 0.85rem;">
                                <strong>Alasan Penolakan:</strong> <?= htmlspecialchars($item['alasan_penolakan']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($item['status'] === 'pending'): ?>
                    <div class="item-actions">
                        <form action="actions/batal_peminjaman.php" method="POST" onsubmit="return confirm('Yakin ingin membatalkan peminjaman ini?');" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-small" style="color: var(--danger); border-color: var(--danger);">Batalkan</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <nav class="mobile-nav">
        <div class="mobile-nav-container">
            <a href="index.php" class="mobile-nav-item">Beranda</a>
            <a href="status.php" class="mobile-nav-item">Jadwal</a>
            <a href="history.php" class="mobile-nav-item active">Riwayat</a>
            <a href="auth/logout.php" class="mobile-nav-item">Akun</a>
        </div>
    </nav>

</body>
</html>
