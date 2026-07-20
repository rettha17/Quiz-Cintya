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

<title>Admin Login | Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/admin-login.css?v=<?= filemtime(__DIR__ . "/assets/admin-login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="admin-auth-page">
<div class="admin-auth-main">

<div class="admin-auth-hero">
<img src="assets/img/admin-hero-gradient.jpg" alt="Admin Panel Quiz Challenge Statistik BPS. Kelola Semua dengan Mudah dan Efisien! Kelola quiz, soal, pengguna, dan laporan dengan mudah dan efisien." class="admin-hero-img">
</div>

<div class="admin-auth-panel">
<div class="admin-login-card">

<div class="admin-card-icon"><img src="assets/img/admin-logo-icon-gradient-v2.png" alt="Admin Panel"></div>


<h2>Admin <span>Panel</span></h2>
<p class="admin-card-subtitle">Quiz Challenge Statistik BPS</p>

<div class="admin-divider"><i class="fa-solid fa-star"></i></div>

<?php if ($error != "") { ?>
<div class="error"><?= htmlspecialchars($error); ?></div>
<?php } ?>

<form method="POST">

<div class="input-group">
<span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
<input type="text" name="username" placeholder="Username" required autofocus>
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
<a href="#" class="forgot-link" onclick="return false;">Lupa password?</a>
</div>

<button type="submit" name="login" class="btn-masuk">
<i class="fa-solid fa-shield-halved"></i> Masuk
</button>

</form>

<div class="admin-note">
<span class="admin-note-icon"><i class="fa-solid fa-shield-halved"></i></span>
<div>
<b>Akses hanya untuk admin terdaftar</b>
<span>Pastikan Anda menjaga kerahasiaan akun.</span>
</div>
<i class="fa-solid fa-lock admin-note-lock"></i>
</div>

</div>
</div>

</div>

<div class="admin-feature-strip">
<img src="assets/img/admin-feature-strip-gradient.jpg" alt="Aman & Terpercaya, Manajemen Lengkap, Laporan Real-time, Dukung Prestasi">
</div>

</div>

<script>
(function(){
    var toggle = document.getElementById("togglePassword");
    var input = document.getElementById("passwordInput");
    if (!toggle || !input) return;
    toggle.addEventListener("click", function(){
        var isHidden = input.type === "password";
        input.type = isHidden ? "text" : "password";
        toggle.innerHTML = isHidden
            ? '<i class="fa-solid fa-eye-slash"></i>'
            : '<i class="fa-solid fa-eye"></i>';
    });
})();
</script>

</body>

</html>
