<?php
session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/../inc/functions.php";

if (getSetting($conn, 'maintenance_mode', '0') === '1') {
    die("
    <h2 align='center' style='margin-top:100px;font-family:sans-serif;'>
    <i class='fa-solid fa-screwdriver-wrench'></i> StatQuest BPS sedang dalam pemeliharaan.
    <br>Silakan coba lagi beberapa saat lagi.
    </h2>
    ");
}

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

function asset($path) {
    return $path . "?v=" . filemtime(__DIR__ . "/" . $path);
}

$error = "";
$sukses = "";

if (isset($_GET['google_error'])) {
    $error = $_GET['google_error'] === 'nonaktif'
        ? "Akun kamu telah dinonaktifkan. Hubungi admin untuk info lebih lanjut."
        : "Login dengan Google gagal, silakan coba lagi.";
}

if (isset($_GET['reset'])) {
    $sukses = "Password berhasil diubah. Silakan login dengan password barumu.";
}

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $cocok = false;

    if ($user) {

        if (password_verify($password, $user['password'])) {

            $cocok = true;

        } elseif ($user['password'] === md5($password)) {

            // Akun lama yang masih memakai md5: terima sekali, lalu upgrade ke hash yang aman.
            $cocok = true;

            $hashBaru = password_hash($password, PASSWORD_DEFAULT);
            $up = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($up, "si", $hashBaru, $user['id']);
            mysqli_stmt_execute($up);

        }

    }

    if ($cocok && $user['status'] === 'nonaktif') {

        $error = "Akun kamu telah dinonaktifkan. Hubungi admin untuk info lebih lanjut.";

    } elseif ($cocok) {

        $_SESSION['id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];

        header("Location: dashboard.php");
        exit;

    } else {

        $error = "Email atau Password salah!";

    }

}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | StatQuest BPS</title>

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

<h1 class="quest-hero-title">StatQuest <span>BPS</span></h1>
<p class="quest-hero-tagline">Jelajah Statistik Indonesia</p>

<p class="quest-hero-desc">StatQuest BPS merupakan aplikasi pembelajaran interaktif berbasis kuis yang membantu pengguna mengenal statistik dan data BPS melalui materi, tantangan, dan permainan edukatif.</p>

<ul class="quest-hero-bullets">
<li><i class="fa-solid fa-book-open"></i> Materi Interaktif</li>
<li><i class="fa-solid fa-bullseye"></i> Quiz Menantang</li>
<li><i class="fa-solid fa-trophy"></i> XP &amp; Badge</li>
</ul>

<div class="quest-hero-stage">

<img src="<?= asset("assets/img/icon-chart.png"); ?>" alt="" class="quest-float qf-chart">
<img src="<?= asset("assets/img/icon-pie.png"); ?>" alt="" class="quest-float qf-pie">
<img src="<?= asset("assets/img/icon-compass.png"); ?>" alt="" class="quest-float qf-compass">

<img src="<?= asset("assets/img/mascot-boy.png"); ?>" alt="Maskot StatQuest BPS laki-laki" class="quest-mascot quest-mascot-boy">
<img src="<?= asset("assets/img/mascot-girl.png"); ?>" alt="Maskot StatQuest BPS perempuan" class="quest-mascot quest-mascot-girl">

</div>

</div>

<div class="login-panel">

<div class="login-card">

<img src="<?= asset("assets/img/login-badge-icon.png"); ?>" alt="" class="quest-card-badge">

<div class="quest-title-row">
<h2>Login Pengguna</h2>
<p>Masuk untuk melanjutkan petualangan belajarmu</p>
</div>

<div class="login-divider"><i class="fa-solid fa-star"></i></div>

<?php if($error!=""){ ?>

<div class="error">

<?= htmlspecialchars($error); ?>

</div>

<?php } ?>

<?php if($sukses!=""){ ?>

<div class="success">

<?= htmlspecialchars($sukses); ?>

</div>

<?php } ?>

<form method="POST">

<div class="input-group">

<i class="fa-solid fa-user"></i>

<input
type="email"
name="email"
placeholder="Email atau Username"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-lock"></i>

<input
type="password"
name="password"
id="passwordInput"
placeholder="Password"
required>

<button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password">
<i class="fa-solid fa-eye"></i>
</button>

</div>

<div class="login-options">

<label class="remember-me">
<input type="checkbox" name="ingat">
Ingat saya
</label>

<a href="lupa_password.php" class="forgot-link">Lupa password?</a>

</div>

<button
type="submit"
name="login"
class="btn-masuk quest-btn-masuk">

<i class="fa-solid fa-right-to-bracket"></i> Masuk

</button>

</form>

<div class="login-or"><span>atau masuk dengan</span></div>

<a href="google_login.php" class="quest-google-btn">
<svg viewBox="0 0 18 18" aria-hidden="true">
<path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84c-.21 1.13-.84 2.09-1.8 2.73v2.27h2.91c1.7-1.57 2.69-3.88 2.69-6.64z"/>
<path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.91-2.27c-.81.54-1.84.86-3.05.86-2.34 0-4.32-1.58-5.03-3.71H.96v2.34C2.44 15.98 5.48 18 9 18z"/>
<path fill="#FBBC05" d="M3.97 10.7A5.4 5.4 0 0 1 3.68 9c0-.59.1-1.17.29-1.7V4.96H.96A9 9 0 0 0 0 9c0 1.45.35 2.83.96 4.04l3.01-2.34z"/>
<path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l3.01 2.34C4.68 5.16 6.66 3.58 9 3.58z"/>
</svg>
Login dengan Google
</a>

<div class="quest-register-cta">
<img src="<?= asset("assets/img/mascot-boy.png"); ?>" alt="" class="quest-register-cta-img">
<div class="quest-register-text">
<b>Belum punya akun?</b>
<span>Daftar sekarang dan mulai petualangan belajar statistik yang seru!</span>
</div>
<a href="register.php" class="quest-register-btn"><i class="fa-solid fa-user-plus"></i> Daftar Akun <i class="fa-solid fa-arrow-right"></i></a>
</div>

</div>

</div>

</div>

<div class="quest-features-section">

<div class="quest-features-badge"><span>Apa yang bisa kamu lakukan di StatQuest BPS?</span></div>

<div class="quest-features-grid">

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-blue"><i class="fa-solid fa-book-open"></i></span>
<h4>Materi Interaktif</h4>
<p>Pelajari statistik dari dasar hingga lanjutan dengan materi resmi BPS.</p>
</div>

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-orange"><i class="fa-solid fa-bullseye"></i></span>
<h4>Tantangan</h4>
<p>Selesaikan tantangan harian untuk menguji pemahamanmu.</p>
</div>

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-cyan"><i class="fa-solid fa-list-check"></i></span>
<h4>Kuis Interaktif</h4>
<p>Jawab soal kuis dan tingkatkan kemampuan statistikmu.</p>
</div>

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-gold"><i class="fa-solid fa-trophy"></i></span>
<h4>Achievement &amp; Badge</h4>
<p>Buka pencapaian baru dan koleksi badge eksklusif.</p>
</div>

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-purple"><i class="fa-solid fa-star"></i></span>
<h4>XP &amp; Level</h4>
<p>Dapatkan XP setiap menyelesaikan materi dan naik level.</p>
</div>

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-green"><i class="fa-solid fa-ranking-star"></i></span>
<h4>Leaderboard</h4>
<p>Lihat peringkatmu dan bersaing sehat dengan peserta lain.</p>
</div>

<div class="quest-feature-card">
<span class="quest-feature-icon qfi-teal"><i class="fa-solid fa-certificate"></i></span>
<h4>Sertifikat Digital</h4>
<p>Selesaikan seluruh pembelajaran dan dapatkan sertifikat resmi BPS.</p>
</div>

</div>

</div>

<div class="login-bottom-strip quest-bottom-strip">

<p class="quest-strip-copyright">&copy; <?= date("Y"); ?> Badan Pusat Statistik. All rights reserved.</p>

</div>

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
