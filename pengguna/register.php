<?php
session_start();
require_once __DIR__ . "/../koneksi.php";

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

    } elseif (strlen($password) < 6) {

        $pesan = "Password minimal 6 karakter!";

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

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Register | Quiz Challenge BPS</title>

<link rel="stylesheet"
href="assets/css/login.css?v=<?= filemtime(__DIR__ . "/assets/css/login.css"); ?>">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="login-box">

<div class="logo">

<i class="fa-solid fa-user-plus"></i>

<h2>Daftar Akun</h2>

<p>Quiz Challenge BPS</p>

</div>

<?php

if($pesan!=""){

echo "<div class='error'>" . htmlspecialchars($pesan) . "</div>";

}

?>

<form method="POST">

<div class="input-group">

<i class="fa-solid fa-user"></i>

<input
type="text"
name="nama"
placeholder="Nama Lengkap"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-envelope"></i>

<input
type="email"
name="email"
placeholder="Email"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-lock"></i>

<input
type="password"
name="password"
placeholder="Password"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-lock"></i>

<input
type="password"
name="konfirmasi"
placeholder="Konfirmasi Password"
required>

</div>

<button
type="submit"
name="register">

Daftar

</button>

</form>

<div class="register">

Sudah punya akun?

<a href="login.php">

Login

</a>

</div>

</div>

</body>

</html>
