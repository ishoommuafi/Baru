<?php
session_start();
require_once 'config/db.php';
require_once 'auth/middleware.php';

// Ambil data gedung
$stmtGedung = $pdo->query("SELECT * FROM gedung ORDER BY kode ASC");
$gedungs = $stmtGedung->fetchAll();

// Ambil data ruangan
$stmtRuangan = $pdo->query("SELECT * FROM ruangan WHERE status = 'aktif' ORDER BY nama ASC");
$semuaRuangan = $stmtRuangan->fetchAll();

// Kelompokkan ruangan berdasarkan gedung_id
$ruanganPerGedung = [];
foreach ($semuaRuangan as $r) {
    $ruanganPerGedung[$r['gedung_id']][] = $r;
}

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA - Daftar Ruangan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .building-tabs { display: flex; gap: 0.625rem; margin-bottom: 1.25rem; overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: none; }
        .building-tabs::-webkit-scrollbar { display: none; }
        .building-tab { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem; border-radius: 9999px; border: 2px solid #E5E7EB; background: #fff; font-size: 0.875rem; font-weight: 700; color: var(--text-light); cursor: pointer; white-space: nowrap; transition: all 0.2s ease; flex-shrink: 0; }
        .building-tab:hover { border-color: var(--secondary); color: var(--secondary); }
        .building-tab.active { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(10, 37, 64, 0.2); }
        .building-tab .tab-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; opacity: 0.6; }
        .building-section { display: none; animation: fadeInUp 0.3s ease; }
        .building-section.active { display: block; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .building-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 1rem 1.25rem; background: linear-gradient(135deg, var(--primary) 0%, #143b66 100%); border-radius: 16px; color: white; }
        .building-header-icon { width: 40px; height: 40px; background: rgba(255, 255, 255, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .building-header-info h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.1rem; }
        .building-header-info p { font-size: 0.78rem; opacity: 0.8; }
    </style>
</head>
<body>

    <header>
        <h1>SIPERKA</h1>
        <p>Sistem Peminjaman Ruang Kampus</p>
    </header>

    <nav class="desktop-nav">
        <a href="index.php" class="active">Beranda</a>
        <a href="status.php">Jadwal Global</a>
        <?php if ($isLoggedIn): ?>
            <a href="history.php">Riwayat Saya</a>
            <a href="auth/logout.php" class="login-btn" style="color:var(--danger); border-color:var(--danger);">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </nav>

    <main>
        <h2 class="section-title">Pilih Gedung</h2>

        <div class="building-tabs" id="buildingTabs">
            <?php foreach ($gedungs as $index => $g): ?>
                <button class="building-tab <?= $index === 0 ? 'active' : '' ?>" id="tab-<?= $g['kode'] ?>" onclick="switchBuilding('<?= $g['kode'] ?>')">
                    <span class="tab-dot"></span> <?= htmlspecialchars($g['nama']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($gedungs as $index => $g): ?>
            <div class="building-section <?= $index === 0 ? 'active' : '' ?>" id="section-<?= $g['kode'] ?>">
                <div class="building-header">
                    <div class="building-header-icon">🏢</div>
                    <div class="building-header-info">
                        <h3><?= htmlspecialchars($g['nama']) ?></h3>
                        <p><?= htmlspecialchars($g['deskripsi']) ?></p>
                    </div>
                </div>

                <section class="card">
                    <?php 
                    $ruangans = $ruanganPerGedung[$g['id']] ?? [];
                    if (empty($ruangans)): ?>
                        <p style="text-align:center; color:var(--text-light); padding:1rem;">Tidak ada ruangan aktif di gedung ini.</p>
                    <?php else: 
                        foreach ($ruangans as $r): ?>
                            <div class="list-item">
                                <div class="item-header">
                                    <div class="item-title"><?= htmlspecialchars($r['nama']) ?></div>
                                    <span class="badge available">Tersedia</span>
                                </div>
                                <div class="item-subtitle">Kapasitas: <?= $r['kapasitas'] ?> Orang • <?= htmlspecialchars($r['fasilitas']) ?></div>
                                <div class="item-actions">
                                    <button class="btn btn-primary btn-small" onclick="window.location.href='booking_form.php?ruangan_id=<?= $r['id'] ?>'">Pinjam</button>
                                </div>
                            </div>
                        <?php endforeach; 
                    endif; ?>
                </section>
            </div>
        <?php endforeach; ?>
    </main>

    <nav class="mobile-nav">
        <div class="mobile-nav-container">
            <a href="index.php" class="mobile-nav-item active">Beranda</a>
            <a href="status.php" class="mobile-nav-item">Jadwal</a>
            <?php if ($isLoggedIn): ?>
            <a href="history.php" class="mobile-nav-item">Riwayat</a>
            <?php endif; ?>
            <a href="<?= $isLoggedIn ? 'auth/logout.php' : 'login.php' ?>" class="mobile-nav-item">Akun</a>
        </div>
    </nav>

    <script>
        function switchBuilding(id) {
            document.querySelectorAll('.building-tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById('tab-' + id).classList.add('active');
            document.querySelectorAll('.building-section').forEach(sec => sec.classList.remove('active'));
            document.getElementById('section-' + id).classList.add('active');
        }
    </script>
</body>
</html>
