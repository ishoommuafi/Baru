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
        <section class="card page-intro">
            <div class="page-intro-title">Riwayat peminjaman Anda</div>
            <p>Semua pengajuan, status, dan alasan penolakan tersimpan rapi di sini.</p>
        </section>

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
            <a href="index.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 8v11h16V8l-8-5Zm0 2.2 6 3.75V18H6V8.95l6-3.75Z"/></svg>
                <span>Beranda</span>
            </a>
            <a href="status.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10v2H7zM5 6h14v2H5zM4 10h16v10H4z"/></svg>
                <span>Jadwal</span>
            </a>
            <a href="history.php" class="mobile-nav-item active">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-2.05-5.05L13 10h7V3l-2.2 2.2A8.96 8.96 0 0 0 12 3Z"/></svg>
                <span>Riwayat</span>
            </a>
            <a href="auth/logout.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.33 0-6 1.79-6 4v2h12v-2c0-2.21-2.67-4-6-4Z"/></svg>
                <span>Akun</span>
            </a>
        </div>
    </nav>

</body>
</html>
