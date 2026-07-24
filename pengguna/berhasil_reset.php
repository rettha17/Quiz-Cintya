<?php
session_start();
require_once __DIR__ . "/../koneksi.php";

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

function asset($path) {
    return $path . "?v=" . filemtime(__DIR__ . "/" . $path);
}

// Halaman ini hanya boleh muncul sekali setelah reset password sukses.
if (empty($_SESSION['reset_done'])) {
    header("Location: login.php");
    exit;
}
unset($_SESSION['reset_done']);
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Password Berhasil Diubah | StatQuest BPS</title>

<link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . "/assets/css/login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="login-page login-page-quest">

<div class="login-main">

<div class="login-hero">

<div class="hero-bg-deco" aria-hidden="true">

<span class="hero-blob hero-blob-1"></span>
<span class="hero-blob hero-blob-2"></span>
<span class="hero-dots"></span>

<svg class="hero-bg-svg" viewBox="0 0 640 720" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">

<g class="hero-clouds">
<ellipse cx="90" cy="70" rx="46" ry="18"/>
<ellipse cx="126" cy="60" rx="34" ry="15"/>
<ellipse cx="540" cy="110" rx="52" ry="20"/>
<ellipse cx="500" cy="122" rx="30" ry="13"/>
</g>

<g class="hero-building">
<rect x="470" y="330" width="70" height="160" rx="4"/>
<rect x="546" y="290" width="56" height="200" rx="4"/>
<rect x="410" y="380" width="54" height="110" rx="4"/>
<rect x="484" y="350" width="10" height="12"/>
<rect x="484" y="374" width="10" height="12"/>
<rect x="484" y="398" width="10" height="12"/>
<rect x="502" y="350" width="10" height="12"/>
<rect x="502" y="374" width="10" height="12"/>
<rect x="502" y="398" width="10" height="12"/>
</g>

<g class="hero-bars">
<rect x="30" y="560" width="34" height="90" rx="4"/>
<rect x="74" y="520" width="34" height="130" rx="4"/>
<rect x="118" y="480" width="34" height="170" rx="4"/>
<rect x="162" y="440" width="34" height="210" rx="4"/>
</g>

<g class="hero-pie">
<circle cx="560" cy="600" r="60"/>
<path d="M560,600 L560,540 A60,60 0 0 1 611,630 Z"/>
</g>

<polyline class="hero-line" points="20,300 90,260 150,290 220,220 290,250 360,180" fill="none"/>
<circle class="hero-line-dot" cx="360" cy="180" r="6"/>

<g class="hero-circles">
<circle cx="600" cy="250" r="26"/>
<circle cx="40" cy="480" r="16"/>
<circle cx="200" cy="620" r="10"/>
</g>

<g class="hero-shapes">
<polygon class="hero-shape-triangle" points="440,470 480,540 400,540"/>
<rect class="hero-shape-square" x="560" y="440" width="46" height="46" rx="8" transform="rotate(18 583 463)"/>
</g>

</svg>

<span class="hero-float-circle hfc-1"></span>
<span class="hero-float-circle hfc-2"></span>

</div>

<div class="quest-hero-topbar">
<img src="assets/img/logo-bps-transparent.png" alt="">
<div><b>BADAN PUSAT STATISTIK</b><span>Mencatat Indonesia</span></div>
</div>

<img src="<?= asset("assets/img/login-badge-icon.png"); ?>" alt="" class="quest-hero-badge">

<div class="quest-title-with-mascot">
<div>
<h1 class="quest-hero-title">StatQuest <span>BPS</span></h1>
<p class="quest-hero-tagline">Jelajah Statistik Indonesia</p>
</div>
<img src="<?= asset("assets/img/mascot-boy-hoodie.png"); ?>" alt="Maskot StatQuest BPS" class="quest-hero-title-mascot">
</div>

<p class="quest-hero-desc">Password akunmu sudah berhasil diperbarui. Sekarang kamu bisa masuk kembali dan melanjutkan petualangan belajar statistikmu.</p>

<ul class="quest-hero-bullets">
<li><i class="fa-solid fa-book-open"></i> Materi Interaktif</li>
<li><i class="fa-solid fa-bullseye"></i> Quiz Menantang</li>
<li><i class="fa-solid fa-trophy"></i> XP &amp; Badge</li>
</ul>

</div>

<div class="login-panel">

<div class="login-card reset-success-card">

<span class="reset-success-icon"><i class="fa-solid fa-circle-check"></i></span>

<h2>Password Berhasil Diubah</h2>
<p class="reset-success-desc">Password akunmu berhasil diperbarui. Silakan login menggunakan password barumu.</p>

<a href="login.php" class="btn-masuk quest-btn-masuk">
<i class="fa-solid fa-right-to-bracket"></i> Login Sekarang
</a>

</div>

</div>

</div>

<div class="login-bottom-strip quest-bottom-strip">

<p class="quest-strip-copyright">&copy; <?= date("Y"); ?> Badan Pusat Statistik. All rights reserved.</p>

</div>

</div>

</body>

</html>
