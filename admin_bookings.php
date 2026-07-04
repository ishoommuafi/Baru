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
    <style>
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(10, 37, 64, 0.55); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem; animation: fadeIn 0.2s ease; }
        .modal-overlay.open { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-box { background: #fff; border-radius: 28px; padding: 2rem; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .modal-title { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); }
        .modal-close { width: 36px; height: 36px; border-radius: 50%; background: #F3F4F6; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-light); transition: all 0.2s ease; }
        .modal-close:hover { background: var(--danger-bg); color: var(--danger); }
        .form-group textarea { width: 100%; padding: 0.85rem 1rem; border: 1px solid #D1D5DB; border-radius: 16px; font-size: 1rem; font-family: 'Inter', sans-serif; resize: vertical; min-height: 100px; }
        .form-group textarea:focus { border-color: var(--secondary); outline: none; }
    </style>
</head>
<body style="padding-bottom: 2rem;">

    <header class="admin-header">
        <h1>Admin SIPERKA</h1>
        <nav class="admin-nav">
            <a href="admin_dashboard.php" class="btn btn-outline btn-small">Dashboard</a>
            <a href="admin_rooms.php" class="btn btn-outline btn-small">Gedung & Ruangan</a>
            <a href="admin_jadwal.php" class="btn btn-outline btn-small">Jadwal Kuliah</a>
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
                        <button type="button" class="btn btn-danger btn-small" onclick="openTolakModal(<?= $item['id'] ?>)">Tolak</button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- Modal Tolak Peminjaman -->
    <div class="modal-overlay" id="modalTolak">
        <div class="modal-box" role="dialog" aria-modal="true">
            <div class="modal-header">
                <span class="modal-title">Tolak Peminjaman</span>
                <button class="modal-close" onclick="closeTolakModal()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <form action="actions/tolak_peminjaman.php" method="POST">
                <input type="hidden" name="id" id="tolak_id">
                <div class="form-group">
                    <label for="alasan_penolakan">Alasan Penolakan (Opsional)</label>
                    <textarea name="alasan_penolakan" id="alasan_penolakan" placeholder="Contoh: Ruangan akan digunakan untuk ujian..."></textarea>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeTolakModal()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-danger" style="flex:2;">Konfirmasi Tolak</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTolakModal(id) {
            document.getElementById('tolak_id').value = id;
            document.getElementById('modalTolak').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeTolakModal() {
            document.getElementById('modalTolak').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalTolak').addEventListener('click', function(e) {
            if (e.target === this) closeTolakModal();
        });
    </script>

</body>
</html>
