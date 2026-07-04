<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';
requireAdmin();

// gedung
$stmtGedung = $pdo->query("SELECT * FROM gedung ORDER BY kode ASC");
$gedungs = $stmtGedung->fetchAll();

// ruangan
$stmt = $pdo->query("
    SELECT r.*, g.kode as kode_gedung, g.nama as nama_gedung 
    FROM ruangan r 
    JOIN gedung g ON r.gedung_id = g.id 
    ORDER BY g.kode ASC, r.nama ASC
");
$ruangan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA Admin - Kelola Ruangan</title>
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
        .form-group select { width: 100%; padding: 0.85rem 1rem; border: 1px solid #D1D5DB; border-radius: 16px; font-size: 1rem; font-family: 'Inter', sans-serif; transition: all 0.2s ease; outline: none; background: #fff; color: var(--text-dark); appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236A7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; cursor: pointer; }
        .form-group select:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(0, 118, 255, 0.15); }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.25rem; }
        .checkbox-chip { display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; border: 2px solid #E5E7EB; border-radius: 9999px; font-size: 0.82rem; font-weight: 600; color: var(--text-light); cursor: pointer; transition: all 0.2s ease; user-select: none; }
        .checkbox-chip input[type="checkbox"] { display: none; }
        .checkbox-chip:has(input:checked) { border-color: var(--secondary); background: #E5F0FF; color: var(--secondary); }
        .building-filter-bar { display: flex; gap: 0.5rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: none; }
        .building-filter-bar::-webkit-scrollbar { display: none; }
        .filter-chip { padding: 0.45rem 1rem; border-radius: 9999px; border: 2px solid #E5E7EB; background: #fff; font-size: 0.82rem; font-weight: 700; color: var(--text-light); cursor: pointer; white-space: nowrap; transition: all 0.2s ease; flex-shrink: 0; }
        .filter-chip.active { background: var(--primary); border-color: var(--primary); color: #fff; }
        .room-building-tag { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 6px; background: #E5F0FF; color: var(--secondary); margin-left: 0.5rem; vertical-align: middle; }
        .room-building-tag.b { background: #FFF4E5; color: var(--warning); }
        .room-building-tag.c { background: #E6F6ED; color: var(--success); }
    </style>
</head>
<body style="padding-bottom: 2rem;">

    <header class="admin-header">
        <h1>Admin SIPERKA</h1>
        <nav class="admin-nav">
            <a href="admin_dashboard.php" class="btn btn-outline btn-small">Dashboard</a>
            <a href="admin_rooms.php" class="btn btn-primary btn-small">Gedung & Ruangan</a>
            <a href="admin_jadwal.php" class="btn btn-outline btn-small">Jadwal Kuliah</a>
            <a href="admin_bookings.php" class="btn btn-outline btn-small">Peminjaman</a>
            <a href="auth/logout.php" class="btn btn-outline btn-small" style="color: var(--danger); border-color: var(--danger);">Logout</a>
        </nav>
    </header>

    <main style="padding-top: 0;">
        <?php if (isset($_GET['error'])): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                <?php
                if ($_GET['error'] === 'empty_gedung') echo "Data gedung tidak boleh kosong!";
                elseif ($_GET['error'] === 'kode_exists') echo "Kode gedung sudah digunakan!";
                elseif ($_GET['error'] === 'gedung_in_use') echo "Gedung tidak dapat dihapus karena masih memiliki ruangan!";
                else echo "Terjadi kesalahan sistem!";
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sukses']) && strpos($_GET['sukses'], 'gedung') !== false): ?>
            <div style="background: #E6F6ED; color: var(--success); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Gedung berhasil <?= str_replace('_gedung', '', $_GET['sukses']) === 'tambah' ? 'ditambahkan' : (str_replace('_gedung', '', $_GET['sukses']) === 'hapus' ? 'dihapus' : 'diperbarui') ?>!
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 class="section-title" style="margin-bottom: 0;">Daftar Gedung</h2>
            <button class="btn btn-primary btn-small" onclick="openModalGedung()">+ Tambah Gedung</button>
        </div>
        
        <section class="card" style="margin-bottom: 2rem;">
            <?php if (empty($gedungs)): ?>
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Belum ada gedung terdaftar.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #F3F4F6;">
                                <th style="padding: 0.75rem; color: var(--text-light);">Kode</th>
                                <th style="padding: 0.75rem; color: var(--text-light);">Nama Gedung</th>
                                <th style="padding: 0.75rem; color: var(--text-light);">Deskripsi</th>
                                <th style="padding: 0.75rem; color: var(--text-light); width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gedungs as $g): ?>
                            <tr style="border-bottom: 1px solid #F3F4F6;">
                                <td style="padding: 0.75rem; font-weight: bold;"><?= htmlspecialchars($g['kode']) ?></td>
                                <td style="padding: 0.75rem;"><?= htmlspecialchars($g['nama']) ?></td>
                                <td style="padding: 0.75rem; color: var(--text-light);"><?= htmlspecialchars($g['deskripsi']) ?></td>
                                <td style="padding: 0.75rem; display: flex; gap: 0.5rem;">
                                    <button class="btn btn-outline btn-small" onclick='openEditModalGedung(<?= json_encode($g) ?>)' style="padding: 0.2rem 0.5rem; font-size: 0.8rem; color: var(--secondary); border-color: var(--secondary);">Edit</button>
                                    <form action="actions/hapus_gedung.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus gedung ini?');" style="margin:0;">
                                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-small" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 class="section-title" style="margin-bottom: 0;">Daftar Ruangan</h2>
            <button class="btn btn-primary btn-small" onclick="openModal()">+ Tambah Ruangan</button>
        </div>

        <?php if (isset($_GET['sukses']) && $_GET['sukses'] === 'tambah'): ?>
            <div style="background: #E6F6ED; color: var(--success); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Ruangan berhasil ditambahkan!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sukses']) && $_GET['sukses'] === 'hapus'): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Ruangan berhasil dihapus!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sukses']) && $_GET['sukses'] === 'edit'): ?>
            <div style="background: #E6F6ED; color: var(--success); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
                Ruangan berhasil diperbarui!
            </div>
        <?php endif; ?>

        <div class="building-filter-bar">
            <button class="filter-chip active" id="filter-semua" onclick="filterRooms('semua')">Semua</button>
            <?php foreach ($gedungs as $g): ?>
            <button class="filter-chip" id="filter-<?= strtolower($g['kode']) ?>" onclick="filterRooms('<?= strtolower($g['kode']) ?>')"><?= htmlspecialchars($g['nama']) ?></button>
            <?php endforeach; ?>
        </div>

        <section class="card" id="roomList">
            <?php foreach ($ruangan as $r): 
                $gedungLower = strtolower($r['kode_gedung']);
                $tagClass = '';
                if ($gedungLower === 'b') $tagClass = 'b';
                if ($gedungLower === 'c') $tagClass = 'c';
                $badgeClass = $r['status'] === 'aktif' ? 'available' : 'unavailable';
            ?>
            <div class="list-item" data-building="<?= $gedungLower ?>">
                <div class="item-header">
                    <div class="item-title"><?= htmlspecialchars($r['nama']) ?> <span class="room-building-tag <?= $tagClass ?>">Gedung <?= $r['kode_gedung'] ?></span></div>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($r['status']) ?></span>
                </div>
                <div class="item-subtitle">Kapasitas: <?= $r['kapasitas'] ?> Orang • <?= htmlspecialchars($r['fasilitas']) ?></div>
                <div class="item-actions">
                    <button class="btn btn-outline btn-small" onclick='openEditModal(<?= json_encode($r) ?>)' style="margin-right: 0.5rem; color: var(--secondary); border-color: var(--secondary);">Edit</button>
                    <form action="actions/hapus_ruangan.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus ruangan ini?');" style="margin:0; display:inline-block;">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
    </main>

    <!-- ===== MODAL TAMBAH RUANGAN ===== -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <span class="modal-title" id="modalTitle">Tambah Ruangan Baru</span>
                <button class="modal-close" onclick="closeModal()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form action="actions/tambah_ruangan.php" method="POST">
                <div class="form-group">
                    <label for="gedung">Gedung <span style="color:var(--danger)">*</span></label>
                    <select id="gedung" name="gedung" required>
                        <option value="" disabled selected>— Pilih gedung —</option>
                        <?php foreach ($gedungs as $g): ?>
                        <option value="<?= strtolower($g['kode']) ?>"><?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="namaRuangan">Nama Ruangan <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="namaRuangan" name="namaRuangan" placeholder="Contoh: Ruang Kelas A104" required>
                </div>

                <div class="form-group">
                    <label for="kapasitas">Kapasitas (Orang) <span style="color:var(--danger)">*</span></label>
                    <input type="number" id="kapasitas" name="kapasitas" placeholder="Contoh: 40" min="1" required>
                </div>

                <div class="form-group">
                    <label>Fasilitas Tersedia</label>
                    <div class="checkbox-group">
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Proyektor"> Proyektor</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="AC"> AC</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Sound System"> Sound System</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Whiteboard"> Whiteboard</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="PC"> PC</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="LAN"> LAN</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Smart Board"> Smart Board</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Panggung"> Panggung</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="statusRuangan">Status</label>
                    <select id="statusRuangan" name="statusRuangan">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Simpan Ruangan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL EDIT RUANGAN ===== -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
            <div class="modal-header">
                <span class="modal-title" id="modalEditTitle">Edit Ruangan</span>
                <button class="modal-close" onclick="closeEditModal()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <form action="actions/edit_ruangan.php" method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_gedung">Gedung <span style="color:var(--danger)">*</span></label>
                    <select id="edit_gedung" name="gedung" required>
                        <?php foreach ($gedungs as $g): ?>
                        <option value="<?= strtolower($g['kode']) ?>"><?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_namaRuangan">Nama Ruangan <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="edit_namaRuangan" name="namaRuangan" required>
                </div>

                <div class="form-group">
                    <label for="edit_kapasitas">Kapasitas (Orang) <span style="color:var(--danger)">*</span></label>
                    <input type="number" id="edit_kapasitas" name="kapasitas" min="1" required>
                </div>

                <div class="form-group">
                    <label>Fasilitas Tersedia</label>
                    <div class="checkbox-group" id="edit_fasilitas_group">
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Proyektor"> Proyektor</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="AC"> AC</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Sound System"> Sound System</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Whiteboard"> Whiteboard</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="PC"> PC</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="LAN"> LAN</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Smart Board"> Smart Board</label>
                        <label class="checkbox-chip"><input type="checkbox" name="fasilitas[]" value="Panggung"> Panggung</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_statusRuangan">Status</label>
                    <select id="edit_statusRuangan" name="statusRuangan">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL TAMBAH GEDUNG ===== -->
    <div class="modal-overlay" id="modalTambahGedung">
        <div class="modal-box" role="dialog" aria-modal="true">
            <div class="modal-header">
                <span class="modal-title">Tambah Gedung Baru</span>
                <button class="modal-close" onclick="closeModalGedung()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <form action="actions/tambah_gedung.php" method="POST">
                <div class="form-group">
                    <label for="kodeGedung">Kode Gedung <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="kodeGedung" name="kode" placeholder="Contoh: A, B, C, FAI" required maxlength="10">
                </div>
                <div class="form-group">
                    <label for="namaGedung">Nama Gedung <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="namaGedung" name="nama" placeholder="Contoh: Gedung Kuliah Bersama 1" required>
                </div>
                <div class="form-group">
                    <label for="deskripsiGedung">Deskripsi</label>
                    <input type="text" id="deskripsiGedung" name="deskripsi" placeholder="Informasi tambahan">
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModalGedung()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Simpan Gedung</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL EDIT GEDUNG ===== -->
    <div class="modal-overlay" id="modalEditGedung">
        <div class="modal-box" role="dialog" aria-modal="true">
            <div class="modal-header">
                <span class="modal-title">Edit Gedung</span>
                <button class="modal-close" onclick="closeEditModalGedung()" aria-label="Tutup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <form action="actions/edit_gedung.php" method="POST">
                <input type="hidden" id="edit_idGedung" name="id">
                <div class="form-group">
                    <label for="edit_kodeGedung">Kode Gedung <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="edit_kodeGedung" name="kode" required maxlength="10">
                </div>
                <div class="form-group">
                    <label for="edit_namaGedung">Nama Gedung <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="edit_namaGedung" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="edit_deskripsiGedung">Deskripsi</label>
                    <input type="text" id="edit_deskripsiGedung" name="deskripsi">
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModalGedung()" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTambah').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            document.getElementById('modalTambah').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalTambah').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        function openEditModal(room) {
            document.getElementById('edit_id').value = room.id;
            document.getElementById('edit_gedung').value = room.kode_gedung.toLowerCase();
            document.getElementById('edit_namaRuangan').value = room.nama;
            document.getElementById('edit_kapasitas').value = room.kapasitas;
            document.getElementById('edit_statusRuangan').value = room.status;

            // Handle checkboxes
            const fasilitasStr = room.fasilitas || "";
            const fasilitasArr = fasilitasStr.split(',').map(f => f.trim());
            
            const checkboxes = document.querySelectorAll('#edit_fasilitas_group input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = fasilitasArr.includes(cb.value);
            });

            document.getElementById('modalEdit').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeEditModal() {
            document.getElementById('modalEdit').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalEdit').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });

        // Modal Gedung Functions
        function openModalGedung() {
            document.getElementById('modalTambahGedung').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModalGedung() {
            document.getElementById('modalTambahGedung').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalTambahGedung').addEventListener('click', function(e) {
            if (e.target === this) closeModalGedung();
        });

        function openEditModalGedung(gedung) {
            document.getElementById('edit_idGedung').value = gedung.id;
            document.getElementById('edit_kodeGedung').value = gedung.kode;
            document.getElementById('edit_namaGedung').value = gedung.nama;
            document.getElementById('edit_deskripsiGedung').value = gedung.deskripsi;
            document.getElementById('modalEditGedung').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeEditModalGedung() {
            document.getElementById('modalEditGedung').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('modalEditGedung').addEventListener('click', function(e) {
            if (e.target === this) closeEditModalGedung();
        });

        function filterRooms(building) {
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            document.getElementById('filter-' + building).classList.add('active');

            document.querySelectorAll('#roomList .list-item').forEach(item => {
                if (building === 'semua' || item.dataset.building === building) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>

</body>
</html>
