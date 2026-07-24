<?php
session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/../inc/functions.php";

function asset($path) {
    return $path . "?v=" . filemtime(__DIR__ . "/" . $path);
}

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

$registrasiTerbuka = getSetting($conn, 'registrasi_terbuka', '1') === '1';

$pesan = "";

if (isset($_POST['register']) && !$registrasiTerbuka) {

    $pesan = "Pendaftaran akun baru sedang ditutup sementara.";

} elseif (isset($_POST['register'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $pesan = "Format email tidak valid!";

    } elseif ($password !== $konfirmasi) {

        $pesan = "Konfirmasi password tidak sama!";

    } elseif (strlen($password) < 8) {

        $pesan = "Password minimal 8 karakter!";

    } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {

        $pesan = "Password harus mengandung huruf dan angka!";

    } elseif ($password === $email) {

        $pesan = "Password tidak boleh sama dengan email!";

    } else {

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {

            $pesan = "Email sudah digunakan!";

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $ins = mysqli_prepare($conn, "
                INSERT INTO users (nama, email, password, foto, level, xp)
                VALUES (?, ?, ?, 'default.png', 1, 0)
            ");
            mysqli_stmt_bind_param($ins, "sss", $nama, $email, $hash);
            mysqli_stmt_execute($ins);

            $userBaruId = mysqli_insert_id($conn);
            logAktivitas($conn, 'user_register', e($nama) . ' berhasil mendaftar sebagai pengguna baru.', 'fa-user-plus', 'green', $userBaruId);

            echo "<script>
            alert('Registrasi Berhasil!');
            window.location='login.php';
            </script>";
            exit;

        }

    }

}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Daftar Akun | StatQuest BPS</title>

<link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . "/assets/css/login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="login-page login-page-quest register-page-quest">

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

<h1 class="quest-hero-title">StatQuest <span>BPS</span></h1>
<p class="reg-hero-tagline">Jelajah Statistik Indonesia</p>

<p class="quest-hero-desc">Platform pembelajaran interaktif dari BPS untuk belajar statistik dengan cara yang menyenangkan dan penuh tantangan.</p>

<p class="reg-hero-invite"><i class="fa-solid fa-star"></i> Yuk, daftar sekarang dan mulai petualangan serumu menjelajahi dunia statistik bersama StatQuest BPS!</p>

<div class="quest-hero-stage reg-hero-stage">
<img src="<?= asset("assets/img/mascot-boy.png"); ?>" alt="Maskot StatQuest BPS laki-laki" class="quest-mascot quest-mascot-boy">
<img src="<?= asset("assets/img/mascot-girl.png"); ?>" alt="Maskot StatQuest BPS perempuan" class="quest-mascot quest-mascot-girl">
</div>

<div class="reg-feature-card">

<div class="reg-feature-card-title">Apa yang bisa kamu lakukan di StatQuest BPS?</div>

<div class="reg-feature-grid">

<div class="reg-feature-item">
<span class="quest-feature-icon qfi-blue"><i class="fa-solid fa-book-open"></i></span>
<span>Materi Interaktif</span>
</div>

<div class="reg-feature-item">
<span class="quest-feature-icon qfi-orange"><i class="fa-solid fa-bullseye"></i></span>
<span>Tantangan Menarik</span>
</div>

<div class="reg-feature-item">
<span class="quest-feature-icon qfi-cyan"><i class="fa-solid fa-list-check"></i></span>
<span>Kuis Interaktif</span>
</div>

<div class="reg-feature-item">
<span class="quest-feature-icon qfi-gold"><i class="fa-solid fa-trophy"></i></span>
<span>Achievement &amp; Badge</span>
</div>

<div class="reg-feature-item">
<span class="quest-feature-icon qfi-green"><i class="fa-solid fa-ranking-star"></i></span>
<span>Leaderboard</span>
</div>

<div class="reg-feature-item">
<span class="quest-feature-icon qfi-teal"><i class="fa-solid fa-certificate"></i></span>
<span>Sertifikat Digital</span>
</div>

</div>

</div>

<div class="reg-banner">
<span class="reg-banner-icon"><i class="fa-solid fa-shield-halved"></i></span>
<div class="reg-banner-text">
<b>Terus belajar dan raih prestasi terbaikmu!</b>
<span>Jadilah Generasi Cerdas Statistik!</span>
</div>
</div>

</div>

<div class="login-panel">

<div class="login-card">

<img src="<?= asset("assets/img/login-badge-icon.png"); ?>" alt="" class="quest-card-badge">

<h2>Daftar Akun</h2>
<p class="register-card-subtitle">StatQuest BPS</p>

<div class="login-divider"><i class="fa-solid fa-star"></i></div>

<?php if ($pesan != "") { ?>

<div class="error"><?= htmlspecialchars($pesan); ?></div>

<?php } ?>

<?php if (!$registrasiTerbuka) { ?>

<div class="error">Pendaftaran akun baru sedang ditutup sementara. Silakan coba lagi nanti.</div>

<?php } else { ?>

<form method="POST">

<div class="input-group">
<i class="fa-solid fa-user"></i>
<input type="text" name="nama" placeholder="Nama Lengkap" required>
</div>

<div class="input-group" id="emailGroup">
<i class="fa-solid fa-envelope"></i>
<input type="email" name="email" id="emailInput" placeholder="Email" required>
</div>
<p class="field-hint" id="emailHint"></p>

<div class="input-group">
<i class="fa-solid fa-lock"></i>
<input type="password" name="password" id="passwordInput" placeholder="Password" required>
<button type="button" class="toggle-password" data-target="passwordInput" aria-label="Tampilkan password">
<i class="fa-solid fa-eye"></i>
</button>
</div>

<div class="input-group" id="konfirmasiGroup">
<i class="fa-solid fa-lock"></i>
<input type="password" name="konfirmasi" id="konfirmasiInput" placeholder="Konfirmasi Password" required>
<button type="button" class="toggle-password" data-target="konfirmasiInput" aria-label="Tampilkan password">
<i class="fa-solid fa-eye"></i>
</button>
</div>
<p class="field-hint" id="konfirmasiHint"></p>

<div class="password-requirements">
<div class="password-requirements-title"><i class="fa-solid fa-shield-halved"></i> Password harus memenuhi:</div>
<ul>
<li id="reqLength"><i class="fa-solid fa-circle-check"></i> Minimal 8 karakter</li>
<li id="reqAlnum"><i class="fa-solid fa-circle-check"></i> Mengandung huruf dan angka</li>
<li id="reqNotEmail"><i class="fa-solid fa-circle-check"></i> Tidak boleh sama dengan email</li>
</ul>
</div>

<button type="submit" name="register" class="btn-masuk quest-btn-masuk">
<i class="fa-solid fa-user-plus"></i> Daftar Sekarang
</button>

</form>

<div class="login-or"><span>atau daftar dengan</span></div>

<a href="google_login.php" class="quest-google-btn">
<svg viewBox="0 0 18 18" aria-hidden="true">
<path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84c-.21 1.13-.84 2.09-1.8 2.73v2.27h2.91c1.7-1.57 2.69-3.88 2.69-6.64z"/>
<path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.91-2.27c-.81.54-1.84.86-3.05.86-2.34 0-4.32-1.58-5.03-3.71H.96v2.34C2.44 15.98 5.48 18 9 18z"/>
<path fill="#FBBC05" d="M3.97 10.7A5.4 5.4 0 0 1 3.68 9c0-.59.1-1.17.29-1.7V4.96H.96A9 9 0 0 0 0 9c0 1.45.35 2.83.96 4.04l3.01-2.34z"/>
<path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l3.01 2.34C4.68 5.16 6.66 3.58 9 3.58z"/>
</svg>
Lanjutkan dengan Google
</a>

<?php } ?>

<div class="quest-register-cta">
<img src="<?= asset("assets/img/mascot-girl.png"); ?>" alt="" class="quest-register-cta-img reg-cta-img-girl">
<div class="quest-register-text">
<b>Sudah punya akun?</b>
<span>Masuk sekarang dan lanjutkan petualangan belajar statistikmu!</span>
</div>
<a href="login.php" class="quest-register-btn"><i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang <i class="fa-solid fa-arrow-right"></i></a>
</div>

</div>

</div>

</div>

<div class="login-bottom-strip quest-bottom-strip">

<p class="quest-strip-copyright">&copy; <?= date("Y"); ?> Badan Pusat Statistik. All rights reserved.</p>

</div>

</div>

<script>
(function(){
    document.querySelectorAll(".toggle-password").forEach(function(btn){
        btn.addEventListener("click", function(){
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            var isHidden = input.type === "password";
            input.type = isHidden ? "text" : "password";
            btn.innerHTML = isHidden
                ? '<i class="fa-solid fa-eye-slash"></i>'
                : '<i class="fa-solid fa-eye"></i>';
        });
    });
})();

(function(){
    var emailInput = document.getElementById("emailInput");
    var passwordInput = document.getElementById("passwordInput");
    var reqLength = document.getElementById("reqLength");
    var reqAlnum = document.getElementById("reqAlnum");
    var reqNotEmail = document.getElementById("reqNotEmail");

    function setReq(li, met){
        li.classList.toggle("req-met", met);
        li.classList.toggle("req-unmet", !met);
        li.querySelector("i").className = met
            ? "fa-solid fa-circle-check"
            : "fa-solid fa-circle-xmark";
    }

    function validasi(){
        var password = passwordInput.value;
        var email = emailInput.value.trim();

        setReq(reqLength, password.length >= 8);
        setReq(reqAlnum, /[a-zA-Z]/.test(password) && /[0-9]/.test(password));
        setReq(reqNotEmail, password !== "" && password !== email);
    }

    passwordInput.addEventListener("input", validasi);
    emailInput.addEventListener("input", validasi);
})();

(function(){
    var emailInput = document.getElementById("emailInput");
    var emailGroup = document.getElementById("emailGroup");
    var emailHint = document.getElementById("emailHint");
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function cekEmail(){
        var email = emailInput.value.trim();

        if (email === "") {
            emailGroup.classList.remove("has-error");
            emailHint.classList.remove("show");
            return;
        }

        var valid = emailPattern.test(email);
        emailGroup.classList.toggle("has-error", !valid);
        emailHint.textContent = valid ? "" : "Format email tidak valid, contoh: nama@email.com";
        emailHint.classList.toggle("show", !valid);
    }

    emailInput.addEventListener("input", cekEmail);
    emailInput.addEventListener("blur", cekEmail);
})();

(function(){
    var passwordInput = document.getElementById("passwordInput");
    var konfirmasiInput = document.getElementById("konfirmasiInput");
    var konfirmasiGroup = document.getElementById("konfirmasiGroup");
    var konfirmasiHint = document.getElementById("konfirmasiHint");

    function cekKonfirmasi(){
        var konfirmasi = konfirmasiInput.value;

        if (konfirmasi === "") {
            konfirmasiGroup.classList.remove("has-error");
            konfirmasiHint.classList.remove("show");
            return;
        }

        var cocok = konfirmasi === passwordInput.value;
        konfirmasiGroup.classList.toggle("has-error", !cocok);
        konfirmasiHint.textContent = cocok ? "" : "Konfirmasi password harus sama dengan password!";
        konfirmasiHint.classList.toggle("show", !cocok);
    }

    passwordInput.addEventListener("input", cekKonfirmasi);
    konfirmasiInput.addEventListener("input", cekKonfirmasi);
    konfirmasiInput.addEventListener("blur", cekKonfirmasi);
})();
</script>

</body>

</html>
