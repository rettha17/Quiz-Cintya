<?php
session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/../inc/functions.php";

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

$pesan = "";

if (isset($_POST['register'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    if ($password !== $konfirmasi) {

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

<title>Daftar Akun | Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . "/assets/css/login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="login-page">

<div class="login-main">

<div class="login-hero">

<img src="assets/img/register-hero-full.jpeg" alt="Bergabunglah Sekarang! Belajar Seru, Tantangan Menarik, Raih Prestasi bersama QCSB." class="hero-panel-img">

</div>

<div class="login-panel">

<div class="login-card">

<div class="register-icon-wrap">
<span class="register-icon-leaf register-icon-leaf-left"><i class="fa-solid fa-leaf"></i></span>
<div class="register-icon-circle"><i class="fa-solid fa-user-plus"></i></div>
<span class="register-icon-leaf register-icon-leaf-right"><i class="fa-solid fa-leaf"></i></span>
</div>

<h2>Daftar Akun</h2>
<p class="register-card-subtitle">Quiz Challenge Statistik BPS</p>

<div class="login-divider"><i class="fa-solid fa-star"></i></div>

<?php if ($pesan != "") { ?>

<div class="error"><?= htmlspecialchars($pesan); ?></div>

<?php } ?>

<form method="POST">

<div class="input-group">
<i class="fa-solid fa-user"></i>
<input type="text" name="nama" placeholder="Nama Lengkap" required>
</div>

<div class="input-group">
<i class="fa-solid fa-envelope"></i>
<input type="email" name="email" id="emailInput" placeholder="Email" required>
</div>

<div class="input-group">
<i class="fa-solid fa-lock"></i>
<input type="password" name="password" id="passwordInput" placeholder="Password" required>
<button type="button" class="toggle-password" data-target="passwordInput" aria-label="Tampilkan password">
<i class="fa-solid fa-eye"></i>
</button>
</div>

<div class="input-group">
<i class="fa-solid fa-lock"></i>
<input type="password" name="konfirmasi" id="konfirmasiInput" placeholder="Konfirmasi Password" required>
<button type="button" class="toggle-password" data-target="konfirmasiInput" aria-label="Tampilkan password">
<i class="fa-solid fa-eye"></i>
</button>
</div>

<div class="password-requirements">
<div class="password-requirements-title"><i class="fa-solid fa-shield-halved"></i> Password harus memenuhi:</div>
<ul>
<li id="reqLength"><i class="fa-solid fa-circle-check"></i> Minimal 8 karakter</li>
<li id="reqAlnum"><i class="fa-solid fa-circle-check"></i> Mengandung huruf dan angka</li>
<li id="reqNotEmail"><i class="fa-solid fa-circle-check"></i> Tidak boleh sama dengan email</li>
</ul>
</div>

<button type="submit" name="register" class="btn-masuk">
<i class="fa-solid fa-user-plus"></i> Daftar Sekarang
</button>

</form>

<p class="login-link-text">Sudah punya akun? <a href="login.php">Login</a></p>

</div>

</div>

</div>

<img src="assets/img/register-feature-strip.jpeg" alt="Edukasi Berkualitas, Game Interaktif, Level dan Peringkat, Kompetisi Sehat" class="login-features-img">

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
</script>

</body>

</html>
