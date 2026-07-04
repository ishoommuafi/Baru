<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';
requireAdmin();

// Ambil data gedung untuk dropdown filter
$stmtGedung = $pdo->query("SELECT * FROM gedung ORDER BY kode ASC");
$gedung_list = $stmtGedung->fetchAll();

// Ambil data ruangan beserta gedung untuk form dropdown
$stmtRuangan = $pdo->query("SELECT r.id, r.nama, g.id as gedung_id, g.kode as kode_gedung, g.nama as nama_gedung FROM ruangan r JOIN gedung g ON r.gedung_id = g.id ORDER BY g.kode ASC, r.nama ASC");
$ruangan_list = $stmtRuangan->fetchAll();

// Ambil data jadwal kuliah, diurutkan per gedung > ruangan > hari > jam
$stmtJadwal = $pdo->query("
    SELECT j.*, r.nama as nama_ruangan, g.id as gedung_id, g.kode as kode_gedung, g.nama as nama_gedung
    FROM jadwal_kuliah j
    JOIN ruangan r ON j.ruangan_id = r.id
    JOIN gedung g ON r.gedung_id = g.id
    ORDER BY 
        g.kode ASC,
        r.nama ASC,
        CASE j.hari 
            WHEN 'Senin' THEN 1 
            WHEN 'Selasa' THEN 2 
            WHEN 'Rabu' THEN 3 
            WHEN 'Kamis' THEN 4 
            WHEN 'Jumat' THEN 5 
            WHEN 'Sabtu' THEN 6 
            WHEN 'Minggu' THEN 7 
        END ASC,
        j.jam_mulai ASC
");
$jadwal_list = $stmtJadwal->fetchAll();

// Kelompokkan jadwal berdasarkan Gedung > Ruangan
$jadwalPerGedung = [];
foreach ($jadwal_list as $j) {
    $jadwalPerGedung[$j['gedung_id']]['info'] = [
        'kode' => $j['kode_gedung'],
        'nama' => $j['nama_gedung'],
    ];
    $jadwalPerGedung[$j['gedung_id']]['ruangan'][$j['ruangan_id']]['nama'] = $j['nama_ruangan'];
    $jadwalPerGedung[$j['gedung_id']]['ruangan'][$j['ruangan_id']]['jadwal'][] = $j;
}

$hari_enum = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA Admin - Kelola Jadwal Kuliah</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(10, 37, 64, 0.55); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem; animation: fadeIn 0.2s ease; }
        .modal-overlay.open { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-box { background: #fff; border-radius: 28px; padding: 2rem; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); max-height: 90vh; overflow-y: auto; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .modal-title { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); }
        .modal-close { width: 36px; height: 36px; border-radius: 50%; background: #F3F4F6; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-light); transition: all 0.2s ease; flex-shrink: 0; }
        .modal-close:hover { background: var(--danger-bg); color: var(--danger); }
        .form-group select, .form-group input { width: 100%; padding: 0.85rem 1rem; border: 1px solid #D1D5DB; border-radius: 16px; font-size: 1rem; font-family: 'Inter', sans-serif; transition: all 0.2s ease; outline: none; background: #fff; color: var(--text-dark); }
        .form-group select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236A7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; cursor: pointer; }
        .form-group select:focus, .form-group input:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(0, 118, 255, 0.15); }
        .hari-tag { display: inline-block; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px; background: #E5F0FF; color: var(--secondary); }
        /* Accordion Gedung */
        .gedung-accordion { border: 1.5px solid #E5E7EB; border-radius: 16px; overflow: hidden; margin-bottom: 1rem; }
        .gedung-accordion:last-child { margin-bottom: 0; }
        .gedung-header { display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 1.25rem; background: #F9FAFB; cursor: pointer; user-select: none; transition: background 0.15s; }
        .gedung-header:hover { background: #F1F5FF; }
        .gedung-header-left { display: flex; align-items: center; gap: 0.75rem; }
        .gedung-badge { width: 34px; height: 34px; border-radius: 9px; background: var(--primary); color: #fff; font-size: 0.9rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .gedung-title { font-size: 0.95rem; font-weight: 800; color: var(--text-dark); }
        .gedung-subtitle { font-size: 0.78rem; color: var(--text-light); margin-top: 1px; }
        .gedung-chevron { transition: transform 0.25s ease; color: var(--text-light); }
        .gedung-chevron.open { transform: rotate(180deg); }
        .gedung-body { display: none; padding: 1rem 1.25rem; border-top: 1px solid #F3F4F6; }
        .gedung-body.open { display: block; }
        /* Sub-tabel Ruangan */
        .ruangan-group { margin-bottom: 1.25rem; }
        .ruangan-group:last-child { margin-bottom: 0; }
        .ruangan-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem; }
        .ruangan-label::before { content: ''; display: inline-block; width: 3px; height: 14px; background: var(--secondary); border-radius: 3px; }
        .jadwal-row { display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.85rem; border: 1px solid #F3F4F6; margin-bottom: 0.35rem; transition: background 0.15s; }
        .jadwal-row:last-child { margin-bottom: 0; }
        .jadwal-row:hover { background: #F9FAFB; }
        .jadwal-row-actions { margin-left: auto; display: flex; gap: 0.35rem; flex-shrink: 0; }
    </style>
</head>
<body style="padding-bottom: 2rem;">

    <header class="admin-header">
        <h1>Admin SIPERKA</h1>
        <nav class="admin-nav">
            <a href="admin_dashboard.php" class="btn btn-outline btn-small">Dashboard</a>
            <a href="admin_rooms.php" class="btn btn-outline btn-small">Gedung & Ruangan</a>
            <a href="admin_jadwal.php" class="btn btn-primary btn-small">Jadwal Kuliah</a>
            <a href="admin_bookings.php" class="btn btn-outline btn-small">Peminjaman</a>
            <a href="auth/logout.php" class="btn btn-outline btn-small" style="color: var(--danger); border-color: var(--danger);">Logout</a>
        </nav>
    </header>

    <main style="padding-top: 0;">
        <?php if (isset($_GET['error'])): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                <?php
                if ($_GET['error'] === 'bentrok') echo "Ruangan sudah memiliki jadwal kuliah pada waktu tersebut!";
                elseif ($_GET['error'] === 'jam_tidak_valid') echo "Jam mulai harus lebih awal dari jam selesai!";
                else echo "Terjadi kesalahan sistem!";
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sukses'])): ?>
            <div style="background: #E6F6ED; color: var(--success); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Jadwal kuliah berhasil <?= $_GET['sukses'] === 'tambah' ? 'ditambahkan' : ($_GET['sukses'] === 'hapus' ? 'dihapus' : 'diperbarui') ?>!
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 class="section-title" style="margin-bottom: 0;">Daftar Jadwal Kuliah</h2>
            <button class="btn btn-primary btn-small" onclick="openModalTambah()">+ Tambah Jadwal</button>
        </div>

        <?php if (empty($jadwal_list)): ?>
            <section class="card">
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Belum ada jadwal kuliah yang terdaftar.</p>
            </section>
        <?php else: ?>

            <?php foreach ($jadwalPerGedung as $gedungId => $gedungData): 
                $info    = $gedungData['info'];
                $ruanganList = $gedungData['ruangan'];
                $totalJadwal = array_sum(array_map(fn($r) => count($r['jadwal']), $ruanganList));
                $accordionId = 'accordion-gedung-' . $gedungId;
            ?>
            <div class="gedung-accordion">
                <!-- Header Gedung -->
                <div class="gedung-header" onclick="toggleAccordion('<?= $accordionId ?>')">
                    <div class="gedung-header-left">
                        <div class="gedung-badge"><?= htmlspecialchars($info['kode']) ?></div>
                        <div>
                            <div class="gedung-title"><?= htmlspecialchars($info['nama']) ?></div>
                            <div class="gedung-subtitle"><?= count($ruanganList) ?> Ruangan &bull; <?= $totalJadwal ?> Jadwal</div>
                        </div>
                    </div>
                    <svg class="gedung-chevron open" id="chevron-<?= $accordionId ?>" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>

                <!-- Body: daftar ruangan dan jadwalnya -->
                <div class="gedung-body open" id="<?= $accordionId ?>">
                    <?php foreach ($ruanganList as $ruanganId => $ruanganData): ?>
                    <div class="ruangan-group">
                        <div class="ruangan-label"><?= htmlspecialchars($ruanganData['nama']) ?></div>

                        <?php foreach ($ruanganData['jadwal'] as $j): ?>
                        <div class="jadwal-row">
                            <span class="hari-tag" style="min-width:54px; text-align:center;"><?= htmlspecialchars($j['hari']) ?></span>
                            <span style="font-size:0.8rem; font-weight:700; color:var(--text-dark); white-space:nowrap;">
                                <?= date('H:i', strtotime($j['jam_mulai'])) ?> – <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                            </span>
                            <span style="color:#6B7280;">|</span>
                            <span style="color:var(--text-dark); flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($j['mata_kuliah']) ?></span>
                            <div class="jadwal-row-actions">
                                <button class="btn btn-outline btn-small" onclick='openModalEdit(<?= json_encode($j) ?>)' style="padding: 0.2rem 0.55rem; font-size: 0.78rem; color: var(--secondary); border-color: var(--secondary);">Edit</button>
                                <form action="actions/hapus_jadwal.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?');" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small" style="padding: 0.2rem 0.55rem; font-size: 0.78rem;">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </main>

    <!-- ===== MODAL TAMBAH JADWAL ===== -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-box" role="dialog" aria-modal="true">
            <div class="modal-header">
                <span class="modal-title">Tambah Jadwal Kuliah</span>
                <button class="modal-close" onclick="closeModalTambah()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form action="actions/tambah_jadwal.php" method="POST">
                <!-- Langkah 1: Pilih Gedung -->
                <div class="form-group">
                    <label for="tambah_gedung">Gedung <span style="color:var(--danger)">*</span></label>
                    <select id="tambah_gedung" required>
                        <option value="" disabled selected>— Pilih gedung —</option>
                        <?php foreach ($gedung_list as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['kode']) ?> — <?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Langkah 2: Pilih Ruangan (difilter berdasarkan gedung) -->
                <div class="form-group">
                    <label for="ruangan_id">Ruangan <span style="color:var(--danger)">*</span></label>
                    <select id="ruangan_id" name="ruangan_id" required disabled>
                        <option value="" disabled selected>— Pilih gedung dahulu —</option>
                        <?php foreach ($ruangan_list as $r): ?>
                        <option value="<?= $r['id'] ?>" data-gedung="<?= $r['gedung_id'] ?>" style="display:none;">
                            <?= htmlspecialchars($r['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mata_kuliah">Mata Kuliah <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="mata_kuliah" name="mata_kuliah" placeholder="Contoh: Pemrograman Web" required>
                </div>

                <div class="form-group">
                    <label for="hari">Hari <span style="color:var(--danger)">*</span></label>
                    <select id="hari" name="hari" required>
                        <option value="" disabled selected>— Pilih hari —</option>
                        <?php foreach ($hari_enum as $hari): ?>
                        <option value="<?= $hari ?>"><?= $hari ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label for="jam_mulai">Jam Mulai <span style="color:var(--danger)">*</span></label>
                        <input type="time" id="jam_mulai" name="jam_mulai" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="jam_selesai">Jam Selesai <span style="color:var(--danger)">*</span></label>
                        <input type="time" id="jam_selesai" name="jam_selesai" required>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModalTambah()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL EDIT JADWAL ===== -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-box" role="dialog" aria-modal="true">
            <div class="modal-header">
                <span class="modal-title">Edit Jadwal Kuliah</span>
                <button class="modal-close" onclick="closeModalEdit()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form action="actions/edit_jadwal.php" method="POST">
                <input type="hidden" id="edit_id" name="id">

                <!-- Langkah 1: Pilih Gedung (Edit) -->
                <div class="form-group">
                    <label for="edit_gedung">Gedung <span style="color:var(--danger)">*</span></label>
                    <select id="edit_gedung" required>
                        <option value="" disabled>— Pilih gedung —</option>
                        <?php foreach ($gedung_list as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['kode']) ?> — <?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Langkah 2: Pilih Ruangan (difilter berdasarkan gedung, Edit) -->
                <div class="form-group">
                    <label for="edit_ruangan_id">Ruangan <span style="color:var(--danger)">*</span></label>
                    <select id="edit_ruangan_id" name="ruangan_id" required>
                        <?php foreach ($ruangan_list as $r): ?>
                        <option value="<?= $r['id'] ?>" data-gedung="<?= $r['gedung_id'] ?>" style="display:none;">
                            <?= htmlspecialchars($r['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_mata_kuliah">Mata Kuliah <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="edit_mata_kuliah" name="mata_kuliah" required>
                </div>

                <div class="form-group">
                    <label for="edit_hari">Hari <span style="color:var(--danger)">*</span></label>
                    <select id="edit_hari" name="hari" required>
                        <?php foreach ($hari_enum as $hari): ?>
                        <option value="<?= $hari ?>"><?= $hari ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label for="edit_jam_mulai">Jam Mulai <span style="color:var(--danger)">*</span></label>
                        <input type="time" id="edit_jam_mulai" name="jam_mulai" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="edit_jam_selesai">Jam Selesai <span style="color:var(--danger)">*</span></label>
                        <input type="time" id="edit_jam_selesai" name="jam_selesai" required>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModalEdit()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ================================================================
        // Fungsi filter ruangan berdasarkan gedung yang dipilih
        // ================================================================
        function filterRuanganByGedung(gedungSelectId, ruanganSelectId, gedungId) {
            const ruanganSelect = document.getElementById(ruanganSelectId);
            const options = ruanganSelect.querySelectorAll('option[data-gedung]');

            // Reset ruangan
            ruanganSelect.value = '';
            ruanganSelect.disabled = !gedungId;

            // Tampilkan/sembunyikan opsi ruangan sesuai gedung
            options.forEach(opt => {
                if (opt.dataset.gedung === String(gedungId)) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                    opt.selected = false;
                }
            });

            // Update placeholder
            const placeholder = ruanganSelect.querySelector('option:not([data-gedung])');
            if (placeholder) {
                placeholder.textContent = gedungId ? '— Pilih ruangan —' : '— Pilih gedung dahulu —';
                placeholder.selected = true;
            }
        }

        // ================================================================
        // Accordion Gedung toggle
        // ================================================================
        function toggleAccordion(id) {
            const body    = document.getElementById(id);
            const chevron = document.getElementById('chevron-' + id);
            body.classList.toggle('open');
            chevron.classList.toggle('open');
        }

        // ================================================================
        // Modal TAMBAH
        // ================================================================
        function openModalTambah() {
            // Reset form sebelum dibuka
            document.getElementById('tambah_gedung').value = '';
            filterRuanganByGedung('tambah_gedung', 'ruangan_id', null);
            document.getElementById('modalTambah').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModalTambah() {
            document.getElementById('modalTambah').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalTambah').addEventListener('click', function(e) {
            if (e.target === this) closeModalTambah();
        });

        // Event listener: saat gedung dipilih pada form Tambah
        document.getElementById('tambah_gedung').addEventListener('change', function() {
            filterRuanganByGedung('tambah_gedung', 'ruangan_id', this.value);
        });

        // ================================================================
        // Modal EDIT
        // ================================================================
        function openModalEdit(jadwal) {
            // Cari gedung_id dari ruangan yang dipilih menggunakan data attribute
            const editRuanganSelect = document.getElementById('edit_ruangan_id');
            const matchedOpt = editRuanganSelect.querySelector(`option[value="${jadwal.ruangan_id}"]`);
            const gedungId = matchedOpt ? matchedOpt.dataset.gedung : null;

            // Set gedung dahulu, lalu filter ruangan
            document.getElementById('edit_gedung').value = gedungId || '';
            filterRuanganByGedung('edit_gedung', 'edit_ruangan_id', gedungId);

            // Set nilai ruangan setelah filter
            editRuanganSelect.value = jadwal.ruangan_id;

            // Set field lainnya
            document.getElementById('edit_id').value = jadwal.id;
            document.getElementById('edit_mata_kuliah').value = jadwal.mata_kuliah;
            document.getElementById('edit_hari').value = jadwal.hari;
            document.getElementById('edit_jam_mulai').value = jadwal.jam_mulai.substring(0, 5);
            document.getElementById('edit_jam_selesai').value = jadwal.jam_selesai.substring(0, 5);

            document.getElementById('modalEdit').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModalEdit() {
            document.getElementById('modalEdit').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalEdit').addEventListener('click', function(e) {
            if (e.target === this) closeModalEdit();
        });

        // Event listener: saat gedung dipilih pada form Edit
        document.getElementById('edit_gedung').addEventListener('change', function() {
            filterRuanganByGedung('edit_gedung', 'edit_ruangan_id', this.value);
        });
    </script>
</body>
</html>
