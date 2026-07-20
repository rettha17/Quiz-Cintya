<?php
session_start();
require_once __DIR__ . "/../koneksi.php";

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

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

    if ($cocok) {

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

<title>Login | Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . "/assets/css/login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="login-page">

<div class="login-main">

<div class="login-hero">

<img src="assets/img/hero-panel-full-v2.png" alt="QCSB - Quiz Challenge Statistik BPS. Jelajah Statistik Indonesia. Belajar, Bermain, Berprestasi." class="hero-panel-img">

</div>

<div class="login-panel">

<div class="login-card">

<div class="login-card-icon-wrap">
<span class="icon-leaf"><i class="fa-solid fa-leaf"></i></span>
<div class="login-card-icon-ring">
<div class="login-card-icon"><img src="assets/img/hero-login-icon.jpeg" alt=""></div>
</div>
<span class="icon-sparkle"><i class="fa-solid fa-sparkle"></i></span>
</div>

<h2>Selamat Datang!</h2>
<p>Masuk untuk melanjutkan perjalananmu di dunia statistik.</p>

<div class="login-divider"><i class="fa-solid fa-star"></i></div>

<?php if($error!=""){ ?>

<div class="error">

<?= htmlspecialchars($error); ?>

</div>

<?php } ?>

<form method="POST">

<div class="input-group">

<i class="fa-solid fa-user"></i>

<input
type="email"
name="email"
placeholder="Username atau Email"
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

<a href="#" class="forgot-link" onclick="return false;">Lupa password?</a>

</div>

<button
type="submit"
name="login"
class="btn-masuk">

<i class="fa-solid fa-right-to-bracket"></i> Masuk

</button>

</form>

<div class="login-or"><span>atau</span></div>

<a href="register.php" class="btn-daftar">
<i class="fa-solid fa-user-plus"></i> Daftar Akun Baru
</a>

<div class="panel-feature-row">

<div class="panel-feature-item">
<span class="panel-feature-icon feature-icon-blue">
<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
</span>
<b>Aman</b>
<span>Data terjaga keamanannya</span>
</div>

<div class="panel-feature-item">
<span class="panel-feature-icon feature-icon-orange">
<svg viewBox="0 0 24 24" fill="#fff"><path d="M6 4h12v2h2.5a.5.5 0 0 1 .5.5v.5c0 2.8-1.9 5-4.3 5.6A5.4 5.4 0 0 1 13 15.8V18h3v2H8v-2h3v-2.2a5.4 5.4 0 0 1-3.7-3.2C5 12 3.1 9.8 3.1 7v-.5a.5.5 0 0 1 .5-.5H6V4zm-2 4c0 1.6.9 2.9 2.1 3.4A9 9 0 0 1 6 8V6H4v2zm14 0V6h-2v2a9 9 0 0 1-.1 3.4C19.1 10.9 20 9.6 20 8z"/></svg>
</span>
<b>Kompetitif</b>
<span>Raih skor tertinggi dan ranking terbaik</span>
</div>

<div class="panel-feature-item">
<span class="panel-feature-icon feature-icon-green">
<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5c2.5-1 5.5-1 8 0v14c-2.5-1-5.5-1-8 0V5z"/><path d="M20 5c-2.5-1-5.5-1-8 0v14c2.5-1 5.5-1 8 0V5z"/></svg>
</span>
<b>Edukatif</b>
<span>Belajar statistik dengan seru</span>
</div>

</div>

</div>

<div class="login-panel-footer">
<div class="login-panel-footer-logo">
<svg viewBox="0 0 40 40" aria-hidden="true">
<polyline points="5,22 13,16 20,19 32,7" fill="none" stroke="#FFA726" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
<polyline points="25,7 32,7 32,14" fill="none" stroke="#FFA726" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
<rect x="5" y="23" width="5" height="8" rx="1.5" fill="#fff"/>
<rect x="13" y="19" width="5" height="12" rx="1.5" fill="#fff"/>
<rect x="21" y="15" width="5" height="16" rx="1.5" fill="#fff"/>
<rect x="29" y="8" width="5" height="23" rx="1.5" fill="#fff"/>
<rect x="5" y="33" width="29" height="2.5" rx="1.25" fill="#fff"/>
</svg>
<span>QCSB</span>
</div>
<p>Quiz Challenge Statistik BPS &middot; Badan Pusat Statistik</p>
</div>

<span class="panel-deco panel-deco-sparkle-1"><i class="fa-solid fa-sparkle"></i></span>
<span class="panel-deco panel-deco-sparkle-2"><i class="fa-solid fa-sparkle"></i></span>
<span class="panel-deco panel-deco-leaf"><i class="fa-solid fa-leaf"></i></span>
<span class="panel-deco panel-deco-arrow"><i class="fa-solid fa-arrow-trend-up"></i></span>
<svg class="panel-deco-trend" viewBox="0 0 140 90" aria-hidden="true">
<polyline points="5,80 35,55 65,65 100,20 135,10" fill="none" stroke="rgba(255,255,255,.35)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<circle cx="35" cy="55" r="2.5" fill="rgba(255,255,255,.55)"/>
<circle cx="65" cy="65" r="2.5" fill="rgba(255,255,255,.55)"/>
<circle cx="100" cy="20" r="2.5" fill="rgba(255,255,255,.55)"/>
</svg>

</div>

</div>

<div class="login-bottom-strip">

<div class="strip-features">

<div class="strip-feature-item">
<span class="strip-feature-icon strip-icon-cyan"><i class="fa-solid fa-lightbulb"></i></span>
<div class="strip-feature-text"><b>Kuis Interaktif</b><span>Soal menarik seputar statistik</span></div>
</div>

<div class="strip-feature-item">
<span class="strip-feature-icon strip-icon-green"><i class="fa-solid fa-chart-simple"></i></span>
<div class="strip-feature-text"><b>Level &amp; Peringkat</b><span>Tantang diri dan raih skor tertinggi</span></div>
</div>

<div class="strip-feature-item">
<span class="strip-feature-icon strip-icon-orange"><i class="fa-solid fa-graduation-cap"></i></span>
<div class="strip-feature-text"><b>Belajar Menyenangkan</b><span>Belajar statistik jadi lebih seru</span></div>
</div>

<div class="strip-feature-item">
<span class="strip-feature-icon strip-icon-purple"><i class="fa-solid fa-people-group"></i></span>
<div class="strip-feature-text"><b>Kompetisi Sehat</b><span>Bersaing secara sehat dengan pemain lain</span></div>
</div>

</div>

<div class="strip-cta">
<span class="strip-cta-leaf"><i class="fa-solid fa-leaf"></i></span>
<div class="strip-cta-icon"><i class="fa-solid fa-trophy"></i></div>
<div class="strip-cta-text">
<b>Jadilah Juara Statistik!</b>
<span>Tunjukkan pengetahuanmu dan raih peringkat terbaik.</span>
</div>
<span class="strip-cta-sparkle"><i class="fa-solid fa-sparkle"></i></span>
</div>

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
