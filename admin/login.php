<?php
session_start();
require __DIR__ . "/../koneksi.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE username=?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $adminRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($adminRow && password_verify($password, $adminRow['password'])) {

        $_SESSION['admin_id'] = $adminRow['id'];
        $_SESSION['admin_nama'] = $adminRow['nama'];

        header("Location: index.php");
        exit;

    } else {

        $error = "Username atau Password salah!";

    }

}
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login | StatQuest BPS</title>

<link rel="stylesheet" href="assets/admin-login.css?v=<?= filemtime(__DIR__ . "/assets/admin-login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="admin-auth-page">

<span class="admin-dot-pattern" aria-hidden="true"></span>

<div class="admin-topbar">

<div class="admin-topbar-logo">
<img src="assets/img/logo-bps-transparent.png" alt="">
<div class="admin-topbar-logo-text">
<span>BADAN PUSAT STATISTIK</span>
<small>Mencatat Indonesia</small>
</div>
</div>

</div>

<div class="admin-auth-main">

<div class="admin-auth-hero">

<div class="hero-text">

<span class="hero-pill"><i class="fa-solid fa-shield-halved"></i> ADMIN PANEL</span>

<h1 class="hero-title">StatQuest <span>BPS</span></h1>
<p class="hero-tagline">Jelajah Statistik Indonesia</p>
<span class="hero-underline"></span>

<p class="hero-desc">Kelola materi pembelajaran, bank soal, kategori, pengguna, dan pantau perkembangan StatQuest BPS dalam satu dashboard.</p>

<ul class="hero-feature-list">

<li>
<span class="hero-feature-icon hero-feature-icon-blue"><i class="fa-solid fa-book-open"></i></span>
<div><b>Kelola Materi</b><span>Tambah dan edit materi pembelajaran.</span></div>
</li>

<li>
<span class="hero-feature-icon hero-feature-icon-teal"><i class="fa-solid fa-list-check"></i></span>
<div><b>Bank Soal</b><span>Kelola lebih dari 95 soal berdasarkan kategori dan level.</span></div>
</li>

<li>
<span class="hero-feature-icon hero-feature-icon-purple"><i class="fa-solid fa-users"></i></span>
<div><b>Manajemen Pengguna</b><span>Pantau perkembangan peserta secara real-time.</span></div>
</li>

<li>
<span class="hero-feature-icon hero-feature-icon-orange"><i class="fa-solid fa-chart-column"></i></span>
<div><b>Dashboard Statistik</b><span>Lihat grafik aktivitas, skor, dan laporan.</span></div>
</li>

<li>
<span class="hero-feature-icon hero-feature-icon-teal2"><i class="fa-solid fa-shield-halved"></i></span>
<div><b>Keamanan Data</b><span>Login aman khusus administrator.</span></div>
</li>

</ul>

<div class="hero-stat-grid">

<div class="hero-stat-card hero-stat-blue">
<i class="fa-solid fa-book-open"></i>
<b>95+</b>
<span>Total Soal</span>
</div>

<div class="hero-stat-card hero-stat-green">
<i class="fa-solid fa-table-cells"></i>
<b>3</b>
<span>Kategori</span>
</div>

<div class="hero-stat-card hero-stat-purple">
<i class="fa-solid fa-layer-group"></i>
<b>3</b>
<span>Level Quiz</span>
</div>

<div class="hero-stat-card hero-stat-orange">
<i class="fa-solid fa-arrow-trend-up"></i>
<b>Real-time</b>
<span>Report</span>
</div>

</div>

</div>

<div class="hero-mock">

<div class="hero-mock-pc">
<div class="hero-mock-frame">
<div class="hero-mock-cam"></div>
<div class="hero-mock-screen">

<div class="hero-mock-body">

<div class="hero-mock-sidebar">
<span class="hero-mock-sidebar-dot"></span>
<span class="hero-mock-nav is-active"><i class="fa-solid fa-house"></i></span>
<span class="hero-mock-nav"><i class="fa-solid fa-table-cells"></i></span>
<span class="hero-mock-nav"><i class="fa-solid fa-file-lines"></i></span>
<span class="hero-mock-nav"><i class="fa-solid fa-users"></i></span>
<span class="hero-mock-nav"><i class="fa-solid fa-chart-column"></i></span>
</div>

<div class="hero-mock-content">

<div class="hero-mock-screen-bar">
<b>Dashboard</b>
</div>

<div class="hero-mock-stats hero-mock-stats-4">
<div class="hero-mock-stat hero-mock-stat-blue">
<i class="fa-solid fa-users"></i>
<span>Pengguna</span>
<b>1.256</b>
<small><i class="fa-solid fa-arrow-trend-up"></i> 15%</small>
</div>
<div class="hero-mock-stat hero-mock-stat-green">
<i class="fa-solid fa-file-lines"></i>
<span>Total Soal</span>
<b>95</b>
<small><i class="fa-solid fa-arrow-trend-up"></i> 12%</small>
</div>
<div class="hero-mock-stat hero-mock-stat-purple">
<i class="fa-solid fa-circle-check"></i>
<span>Dikerjakan</span>
<b>2.340</b>
<small><i class="fa-solid fa-arrow-trend-up"></i> 18%</small>
</div>
<div class="hero-mock-stat hero-mock-stat-orange">
<i class="fa-solid fa-star"></i>
<span>Rata Skor</span>
<b>78%</b>
<small><i class="fa-solid fa-arrow-trend-up"></i> 8%</small>
</div>
</div>

<div class="hero-mock-charts">
<div class="hero-mock-chart">
<span>Grafik Aktivitas Quiz <i class="fa-solid fa-chevron-down"></i></span>
<svg viewBox="0 0 120 46" preserveAspectRatio="none" class="hero-mock-line">
<defs>
<linearGradient id="lineFade" x1="0" y1="0" x2="0" y2="1">
<stop offset="0%" stop-color="#1565C0" stop-opacity=".25"/>
<stop offset="100%" stop-color="#1565C0" stop-opacity="0"/>
</linearGradient>
</defs>
<polyline points="0,38 18,26 36,31 54,14 72,20 90,7 120,16 120,46 0,46" fill="url(#lineFade)" stroke="none"/>
<polyline points="0,38 18,26 36,31 54,14 72,20 90,7 120,16" fill="none" stroke="#1565C0" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
</div>
<div class="hero-mock-chart hero-mock-chart-donut">
<span>Distribusi Kategori</span>
<div class="hero-mock-donut-row">
<div class="hero-mock-donut"></div>
<ul class="hero-mock-donut-legend">
<li><span class="dot dot-blue"></span><span class="hero-mock-donut-label">Statistika</span><b>40%</b></li>
<li><span class="dot dot-purple"></span><span class="hero-mock-donut-label">Pengenalan</span><b>35%</b></li>
<li><span class="dot dot-orange"></span><span class="hero-mock-donut-label">Indikator</span><b>25%</b></li>
</ul>
</div>
</div>
</div>

</div>

</div>

</div>
</div>
<div class="hero-mock-stand">
<div class="hero-mock-neck"></div>
<div class="hero-mock-base"></div>
</div>
</div>
<div class="hero-mock-shadow"></div>

<span class="hero-decor-plant">
<i class="fa-solid fa-seedling"></i>
<span class="hero-decor-pot"></span>
</span>

<span class="hero-decor-blocks">
<span class="block block-1"></span>
<span class="block block-2"></span>
<span class="block block-3"></span>
</span>

</div>

</div>

<div class="admin-auth-panel">

<div class="admin-login-card">

<span class="card-badge-sticker"><i class="fa-solid fa-star"></i></span>

<img src="assets/img/login-badge-icon.png" alt="" class="admin-card-badge">

<h2>Admin <span>Login</span></h2>
<p class="admin-card-subtitle">StatQuest BPS</p>

<div class="admin-divider"><i class="fa-solid fa-star"></i></div>

<p class="admin-card-desc">Masuk menggunakan akun administrator untuk mengelola seluruh sistem StatQuest BPS Jelajah Statistik Indonesia.</p>

<?php if ($error != "") { ?>
<div class="error"><?= htmlspecialchars($error); ?></div>
<?php } ?>

<form method="POST">

<div class="input-group">
<span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
<input type="text" name="username" placeholder="Email Administrator" required autofocus>
</div>

<div class="input-group">
<span class="input-icon"><i class="fa-solid fa-lock"></i></span>
<input type="password" name="password" id="passwordInput" placeholder="Password" required>
<button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password">
<i class="fa-solid fa-eye"></i>
</button>
</div>

<div class="admin-options">
<label class="remember-me">
<input type="checkbox" name="ingat" checked>
Ingat saya
</label>
</div>

<button type="submit" name="login" class="btn-masuk">
<i class="fa-solid fa-paper-plane"></i>
<span>Masuk ke Dashboard</span>
</button>

</form>

<div class="admin-note">
<span class="admin-note-icon"><i class="fa-solid fa-lock"></i></span>
<div>
<b>Akses Administrator</b>
<p>Dashboard ini hanya dapat diakses oleh admin Badan Pusat Statistik.</p>
</div>
</div>

</div>
</div>

</div>

<footer class="admin-dark-footer">

<svg class="footer-skyline" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true">
<g fill="none" stroke="rgba(255,255,255,.18)" stroke-width="2">
<rect x="20" y="40" width="30" height="50"/>
<rect x="60" y="55" width="22" height="35"/>
<rect x="95" y="25" width="34" height="65"/>
<line x1="112" y1="25" x2="112" y2="8"/>
<rect x="140" y="50" width="26" height="40"/>
<rect x="180" y="35" width="28" height="55"/>
<rect x="220" y="60" width="20" height="30"/>
<rect x="1180" y="55" width="24" height="35"/>
<rect x="1215" y="30" width="30" height="60"/>
<rect x="1255" y="48" width="22" height="42"/>
<rect x="1290" y="20" width="34" height="70"/>
<line x1="1307" y1="20" x2="1307" y2="4"/>
<rect x="1335" y="52" width="26" height="38"/>
<rect x="1375" y="38" width="28" height="52"/>
</g>
</svg>

<p>&copy; <?= date("Y"); ?> Badan Pusat Statistik. All rights reserved.</p>

<div class="footer-brand">
<b>StatQuest <span>BPS</span></b>
<span>Jelajah Statistik Indonesia</span>
</div>

</footer>

</div>

<script>
(function(){
    var toggle = document.getElementById("togglePassword");
    var input = document.getElementById("passwordInput");
    if (toggle && input) {
        toggle.addEventListener("click", function(){
            var isHidden = input.type === "password";
            input.type = isHidden ? "text" : "password";
            toggle.innerHTML = isHidden
                ? '<i class="fa-solid fa-eye-slash"></i>'
                : '<i class="fa-solid fa-eye"></i>';
        });
    }
})();
</script>

</body>

</html>
