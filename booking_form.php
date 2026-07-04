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

// ================================================================
// Ambil jadwal kuliah ruangan ini, dikelompokkan per hari
// ================================================================
$stmtJadwal = $pdo->prepare("
    SELECT hari, jam_mulai, jam_selesai, mata_kuliah
    FROM jadwal_kuliah
    WHERE ruangan_id = ?
    ORDER BY 
        CASE hari 
            WHEN 'Senin'  THEN 1 
            WHEN 'Selasa' THEN 2 
            WHEN 'Rabu'   THEN 3 
            WHEN 'Kamis'  THEN 4 
            WHEN 'Jumat'  THEN 5 
            WHEN 'Sabtu'  THEN 6 
            WHEN 'Minggu' THEN 7 
        END ASC,
        jam_mulai ASC
");
$stmtJadwal->execute([$ruangan_id]);
$jadwalRows = $stmtJadwal->fetchAll();

// Kelompokkan jadwal per hari
$jadwalPerHari = [];
foreach ($jadwalRows as $row) {
    $jadwalPerHari[$row['hari']][] = $row;
}
$hariUrutan = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
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
        <section class="card page-intro" style="margin-bottom: 1rem;">
            <div class="page-intro-title">Ajukan peminjaman dengan mudah</div>
            <p>Isi form di bawah, cek jadwal kuliah, dan kirim permohonan dalam hitungan menit.</p>
        </section>

        <!-- ===== JADWAL KULIAH ===== -->
        <?php if (!empty($jadwalPerHari)): ?>
        <section class="card" style="margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.25rem;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #FFF4E5; display:flex; align-items:center; justify-content:center; font-size: 1.1rem; flex-shrink:0;">📅</div>
                <div>
                    <h3 style="margin: 0; font-size: 1rem; color: var(--text-dark);">Jadwal Kuliah Ruangan Ini</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-light);">Pilih tanggal di form bawah untuk otomatis melihat jadwal hari tersebut</p>
                </div>
            </div>

            <!-- Tab Hari -->
            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 1rem;">
                <?php $firstTab = true; foreach ($hariUrutan as $hari): if (!isset($jadwalPerHari[$hari])) continue; ?>
                <button
                    class="tab-btn"
                    onclick="showTab('<?= $hari ?>')"
                    id="tab-<?= $hari ?>"
                    style="padding: 0.4rem 0.9rem; border-radius: 9999px; border: 2px solid <?= $firstTab ? 'var(--primary)' : '#E5E7EB' ?>; background: <?= $firstTab ? 'var(--primary)' : '#fff' ?>; color: <?= $firstTab ? '#fff' : 'var(--text-light)' ?>; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                ><?= $hari ?></button>
                <?php $firstTab = false; endforeach; ?>
            </div>

            <!-- Konten per Hari -->
            <?php $firstContent = true; foreach ($hariUrutan as $hari): if (!isset($jadwalPerHari[$hari])) continue; ?>
            <div id="jadwal-content-<?= $hari ?>" class="jadwal-content" style="display: <?= $firstContent ? 'block' : 'none' ?>;">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($jadwalPerHari[$hari] as $j): ?>
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; background: #FFF8F0; border-left: 3px solid var(--warning); border-radius: 8px;">
                        <div style="flex-shrink:0; font-size:0.78rem; font-weight:800; color: var(--warning); white-space: nowrap;">
                            <?= date('H:i', strtotime($j['jam_mulai'])) ?> – <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                        </div>
                        <div style="width: 1px; height: 28px; background: #FFD199; flex-shrink:0;"></div>
                        <div style="font-size:0.875rem; color: var(--text-dark); font-weight: 500;">
                            <?= htmlspecialchars($j['mata_kuliah']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $firstContent = false; endforeach; ?>

            <p style="margin-top: 1rem; margin-bottom: 0; font-size: 0.78rem; color: var(--text-light); text-align: center;">
                Peminjaman pada waktu di atas akan otomatis ditolak sistem.
            </p>
        </section>
        <?php endif; ?>

        <!-- ===== FORM PEMINJAMAN ===== -->
        <section class="card">
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #E5E7EB;">
                <div class="stat-icon blue" style="width: 48px; height: 48px; border-radius: 12px; display:flex; align-items:center; justify-content:center;">🚪</div>
                <div>
                    <h2 style="font-size: 1.1rem; color: var(--primary);"><?= htmlspecialchars($ruangan['nama']) ?></h2>
                    <p style="font-size: 0.85rem; color: var(--text-light);">Kapasitas: <?= $ruangan['kapasitas'] ?> Orang • <?= htmlspecialchars($ruangan['nama_gedung']) ?></p>
                </div>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php
                    if ($_GET['error'] === 'jadwal_kuliah')     echo "Ruangan sedang digunakan untuk kegiatan perkuliahan.";
                    elseif ($_GET['error'] === 'peminjaman_overlap') echo "Ruangan sudah dipinjam pada waktu tersebut.";
                    elseif ($_GET['error'] === 'konflik')       echo "Jadwal bertabrakan! Ruangan sudah dipakai di waktu tersebut.";
                    ?>
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
            <a href="history.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-2.05-5.05L13 10h7V3l-2.2 2.2A8.96 8.96 0 0 0 12 3Z"/></svg>
                <span>Riwayat</span>
            </a>
            <a href="login.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.33 0-6 1.79-6 4v2h12v-2c0-2.21-2.67-4-6-4Z"/></svg>
                <span>Akun</span>
            </a>
        </div>
    </nav>

    <script>
        // ================================================================
        // Tab switching untuk jadwal kuliah
        // ================================================================
        function showTab(hari) {
            document.querySelectorAll('.jadwal-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.style.background    = '#fff';
                btn.style.color         = 'var(--text-light)';
                btn.style.borderColor   = '#E5E7EB';
            });
            const content = document.getElementById('jadwal-content-' + hari);
            if (content) content.style.display = 'block';
            const activeBtn = document.getElementById('tab-' + hari);
            if (activeBtn) {
                activeBtn.style.background  = 'var(--primary)';
                activeBtn.style.color       = '#fff';
                activeBtn.style.borderColor = 'var(--primary)';
            }
        }

        // ================================================================
        // Otomatis pindah ke tab hari yang sesuai saat user memilih tanggal
        // ================================================================
        const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        document.getElementById('tanggal').addEventListener('change', function () {
            if (!this.value) return;
            const parts = this.value.split('-');
            const d     = new Date(parts[0], parts[1] - 1, parts[2]);
            const hari  = namaHari[d.getDay()];
            if (document.getElementById('tab-' + hari)) {
                showTab(hari);
                // Scroll lembut ke jadwal agar user langsung melihat
                document.querySelector('.jadwal-content[style*="block"]')
                    ?.closest('section')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    </script>

</body>
</html>
