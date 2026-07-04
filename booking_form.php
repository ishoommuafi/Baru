<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';
requireLogin();

$ruangan_id = $_GET['ruangan_id'] ?? null;
if (!$ruangan_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, g.nama as nama_gedung FROM ruangan r JOIN gedung g ON r.gedung_id = g.id WHERE r.id = ? AND r.status = 'aktif'");
$stmt->execute([$ruangan_id]);
$ruangan = $stmt->fetch();

if (!$ruangan) {
    die("Ruangan tidak ditemukan atau tidak aktif.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA - Form Peminjaman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header style="padding-bottom: 2rem; border-radius: 0 0 24px 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1;">
            <a href="index.php" style="color: white; text-decoration: none;">&larr; Kembali</a>
            <h1 style="font-size: 1.25rem; margin-bottom: 0;">Form Peminjaman</h1>
            <div style="width: 24px;"></div>
        </div>
    </header>

    <main style="padding-top: 1.5rem; max-width: 600px;">
        <section class="card">
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #E5E7EB;">
                <div class="stat-icon blue" style="width: 48px; height: 48px; border-radius: 12px; display:flex; align-items:center; justify-content:center;">🚪</div>
                <div>
                    <h2 style="font-size: 1.1rem; color: var(--primary);"><?= htmlspecialchars($ruangan['nama']) ?></h2>
                    <p style="font-size: 0.85rem; color: var(--text-light);">Kapasitas: <?= $ruangan['kapasitas'] ?> Orang • <?= htmlspecialchars($ruangan['nama_gedung']) ?></p>
                </div>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'konflik'): ?>
                <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                    Jadwal bertabrakan! Ruangan sudah dipakai di waktu tersebut.
                </div>
            <?php endif; ?>

            <form action="actions/ajukan_peminjaman.php" method="POST">
                <input type="hidden" name="ruangan_id" value="<?= $ruangan['id'] ?>">
                
                <div class="form-group">
                    <label for="kegiatan">Nama Kegiatan</label>
                    <input type="text" id="kegiatan" name="kegiatan" placeholder="Contoh: Rapat Koordinasi BEM" required>
                </div>
                
                <div class="form-group">
                    <label for="tanggal">Tanggal Peminjaman</label>
                    <input type="date" id="tanggal" name="tanggal" required min="<?= date('Y-m-d') ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="waktu_mulai">Waktu Mulai</label>
                        <input type="time" id="waktu_mulai" name="waktu_mulai" required>
                    </div>
                    <div class="form-group">
                        <label for="waktu_selesai">Waktu Selesai</label>
                        <input type="time" id="waktu_selesai" name="waktu_selesai" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="peserta">Perkiraan Jumlah Peserta</label>
                    <input type="number" id="peserta" name="peserta" placeholder="Contoh: 30" max="<?= $ruangan['kapasitas'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan Tambahan (Opsional)</label>
                    <input type="text" id="keterangan" name="keterangan" placeholder="Alat tambahan yang diperlukan, dsb.">
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 1rem; width:100%;">Ajukan Peminjaman</button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>
