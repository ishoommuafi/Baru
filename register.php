<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPERKA - Registrasi Akun</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>SIPERKA</h1>
        <p>Sistem Peminjaman Ruang Kampus</p>
    </header>

    <main>
        <section class="card page-intro" style="max-width: 420px; margin: 1rem auto 0;">
            <div class="page-intro-title">Buat akun SIPERKA</div>
            <p>Daftar sekarang untuk mulai mengajukan peminjaman ruangan.</p>
        </section>
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-icon" style="background: var(--primary); color: #fff;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                </div>
                <h2>Daftar Akun Baru</h2>
                <p>Silakan isi data untuk mendaftar akun SIPERKA.</p>
                
                <?php if (isset($_GET['error'])): ?>
                <div style="background: var(--danger-bg); color: var(--danger); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; font-weight: 600;">
                    <?php 
                    if ($_GET['error'] === 'empty') echo "Semua kolom harus diisi.";
                    elseif ($_GET['error'] === 'mismatch') echo "Password dan konfirmasi password tidak cocok.";
                    elseif ($_GET['error'] === 'exists') echo "NIM/Username sudah terdaftar.";
                    else echo "Terjadi kesalahan sistem.";
                    ?>
                </div>
                <?php endif; ?>

                <form action="auth/register.php" method="POST">
                    <div class="form-group">
                        <label for="nama" style="text-align: left;">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="form-group">
                        <label for="username" style="text-align: left;">NIM / NIDN / Username</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan ID/NIM Anda" required>
                    </div>
                    <div class="form-group">
                        <label for="password" style="text-align: left;">Password</label>
                        <input type="password" id="password" name="password" placeholder="Buat password" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm" style="text-align: left;">Konfirmasi Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required>
                    </div>
                    
                    <div class="auth-actions" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">Daftar Akun</button>
                    </div>
                    
                    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
                        <span style="color: var(--text-light);">Sudah punya akun?</span> 
                        <a href="login.php" style="color: var(--secondary); font-weight: 600; text-decoration: none;">Masuk di sini</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <div class="mobile-nav-container">
            <a href="index.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Beranda
            </a>
            <a href="status.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Jadwal
            </a>
            <a href="history.php" class="mobile-nav-item">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Riwayat
            </a>
            <a href="login.php" class="mobile-nav-item active">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Akun
            </a>
        </div>
    </nav>

</body>
</html>
