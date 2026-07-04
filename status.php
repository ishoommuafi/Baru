<?php
session_start();
require_once 'config/db.php';

$isLoggedIn = isset($_SESSION['user_id']);

$stmt = $pdo->query("
    SELECT p.*, r.nama as nama_ruangan, u.nama as nama_peminjam
    FROM peminjaman p 
    JOIN ruangan r ON p.ruangan_id = r.id 
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'disetujui' AND p.tanggal >= CURRENT_DATE()
    ORDER BY p.tanggal ASC, p.waktu_mulai ASC
");
$jadwal = $stmt->fetchAll();

// Persiapkan data untuk FullCalendar
$events = [];
foreach ($jadwal as $item) {
    $events[] = [
        'title' => $item['nama_ruangan'] . ' (' . $item['nama_peminjam'] . ')',
        'start' => $item['tanggal'] . 'T' . $item['waktu_mulai'],
        'end' => $item['tanggal'] . 'T' . $item['waktu_selesai'],
        'color' => 'var(--primary)',
        'description' => $item['nama_kegiatan']
    ];
}
$eventsJson = json_encode($events);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA - Status Peminjaman</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        #calendar {
            background: #fff;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .fc-event { cursor: pointer; }
        .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700; color: var(--text-dark); }
        .fc-button-primary { background-color: var(--primary) !important; border-color: var(--primary) !important; }
        .fc-button-primary:not(:disabled).fc-button-active, .fc-button-primary:not(:disabled):active { background-color: var(--secondary) !important; border-color: var(--secondary) !important; }
    </style>
</head>
<body>

    <header>
        <h1>SIPERKA</h1>
        <p>Sistem Peminjaman Ruang Kampus</p>
    </header>

    <nav class="desktop-nav">
        <a href="index.php">Beranda</a>
        <a href="status.php" class="active">Jadwal Global</a>
        <?php if ($isLoggedIn): ?>
            <a href="history.php">Riwayat Saya</a>
            <a href="auth/logout.php" class="login-btn" style="color:var(--danger); border-color:var(--danger);">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </nav>

    <main>
        <section class="card page-intro">
            <div class="page-intro-title">Jadwal ruang yang terorganisir</div>
            <p>Lihat peminjaman yang sudah disetujui dan pantau ketersediaan ruangan dari satu tampilan.</p>
        </section>

        <div id="calendar"></div>

        <section class="card">
            <h2 class="section-title">Daftar Peminjaman Mendatang</h2>
            
            <?php if (empty($jadwal)): ?>
                <p style="text-align:center; color:var(--text-light); padding:1rem;">Belum ada jadwal peminjaman ruangan yang disetujui.</p>
            <?php else: ?>
                <?php foreach ($jadwal as $item): 
                    $isToday = ($item['tanggal'] === date('Y-m-d'));
                    $now = date('H:i:s');
                    $isOngoing = $isToday && ($now >= $item['waktu_mulai'] && $now <= $item['waktu_selesai']);
                    $badgeClass = $isOngoing ? 'unavailable' : 'pending';
                    $badgeLabel = $isOngoing ? 'Berlangsung' : 'Akan Datang';
                ?>
                <div class="list-item">
                    <div class="item-header">
                        <div class="item-title"><?= htmlspecialchars($item['nama_ruangan']) ?></div>
                        <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                    </div>
                    <div class="item-subtitle">
                        Peminjam: <?= htmlspecialchars($item['nama_peminjam']) ?><br>
                        Kegiatan: <?= htmlspecialchars($item['nama_kegiatan']) ?><br>
                        Tanggal: <?= date('d M Y', strtotime($item['tanggal'])) ?><br>
                        Waktu: <?= date('H:i', strtotime($item['waktu_mulai'])) ?> - <?= date('H:i', strtotime($item['waktu_selesai'])) ?> WIB
                    </div>
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
            <a href="status.php" class="mobile-nav-item active">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10v2H7zM5 6h14v2H5zM4 10h16v10H4z"/></svg>
                <span>Jadwal</span>
            </a>
            <?php if ($isLoggedIn): ?>
                <a href="history.php" class="mobile-nav-item">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-2.05-5.05L13 10h7V3l-2.2 2.2A8.96 8.96 0 0 0 12 3Z"/></svg>
                    <span>Riwayat</span>
                </a>
            <?php endif; ?>
            <a href="<?= $isLoggedIn ? 'auth/logout.php' : 'login.php' ?>" class="mobile-nav-item">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.33 0-6 1.79-6 4v2h12v-2c0-2.21-2.67-4-6-4Z"/></svg>
                <span>Akun</span>
            </a>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hari Ini',
                    month: 'Bulan',
                    week: 'Minggu',
                    day: 'Hari'
                },
                events: <?= $eventsJson ?>,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: false,
                height: 'auto',
                eventClick: function(info) {
                    alert('Kegiatan: ' + info.event.extendedProps.description + '\nRuangan: ' + info.event.title + '\nWaktu: ' + info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + ' - ' + (info.event.end ? info.event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'Selesai'));
                }
            });
            calendar.render();
        });
    </script>

</body>
</html>
