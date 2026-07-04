<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';
requireAdmin();

$stmt = $pdo->query("
    SELECT p.*, r.nama as nama_ruangan, u.nama as nama_peminjam
    FROM peminjaman p 
    JOIN ruangan r ON p.ruangan_id = r.id 
    JOIN users u ON p.user_id = u.id
    ORDER BY CASE WHEN p.status = 'pending' THEN 0 ELSE 1 END, p.created_at DESC
");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA Admin - Peminjaman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="padding-bottom: 2rem;">

    <header class="admin-header">
        <h1>Admin SIPERKA</h1>
        <nav class="admin-nav">
            <a href="admin_dashboard.php" class="btn btn-outline btn-small">Dashboard</a>
            <a href="admin_rooms.php" class="btn btn-outline btn-small">Kelola Ruangan</a>
            <a href="admin_bookings.php" class="btn btn-primary btn-small">Peminjaman</a>
            <a href="auth/logout.php" class="btn btn-outline btn-small" style="color: var(--danger); border-color: var(--danger);">Logout</a>
        </nav>
    </header>

    <main style="padding-top: 0;">
        <h2 class="section-title">Permintaan Peminjaman</h2>
        
        <?php if (isset($_GET['sukses']) && $_GET['sukses'] === 'setuju'): ?>
            <div style="background: #E6F6ED; color: var(--success); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Peminjaman berhasil disetujui.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sukses']) && $_GET['sukses'] === 'tolak'): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Peminjaman berhasil ditolak.
            </div>
        <?php endif; ?>

        <section class="card">
            <?php if (empty($bookings)): ?>
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Tidak ada data peminjaman.</p>
            <?php else: ?>
                <?php foreach ($bookings as $item): 
                    $badgeClass = '';
                    $statusLabel = '';
                    if ($item['status'] === 'pending') { $badgeClass = 'pending'; $statusLabel = 'Menunggu Persetujuan'; }
                    if ($item['status'] === 'disetujui') { $badgeClass = 'available'; $statusLabel = 'Disetujui'; }
                    if ($item['status'] === 'ditolak') { $badgeClass = 'unavailable'; $statusLabel = 'Ditolak'; }
                ?>
                <div class="list-item">
                    <div class="item-header">
                        <div class="item-title"><?= htmlspecialchars($item['nama_ruangan']) ?></div>
                        <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    </div>
                    <div class="item-subtitle" style="margin-top: 0.5rem; color: var(--text-dark);">
                        <strong>Peminjam:</strong> <?= htmlspecialchars($item['nama_peminjam']) ?><br>
                        <strong>Kegiatan:</strong> <?= htmlspecialchars($item['nama_kegiatan']) ?><br>
                        <strong>Peserta:</strong> <?= $item['jumlah_peserta'] ?> Orang<br>
                        <strong>Waktu:</strong> <?= date('d M Y', strtotime($item['tanggal'])) ?>, <?= date('H:i', strtotime($item['waktu_mulai'])) ?> - <?= date('H:i', strtotime($item['waktu_selesai'])) ?> WIB
                    </div>
                    
                    <?php if ($item['status'] === 'pending'): ?>
                    <div class="item-actions" style="margin-top: 1rem; display:flex; gap:0.5rem;">
                        <form action="actions/setujui_peminjaman.php" method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-success btn-small">Setujui</button>
                        </form>
                        <form action="actions/tolak_peminjaman.php" method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Tolak</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
