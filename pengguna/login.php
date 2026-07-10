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

<div class="login-box">

<div class="logo">

<i class="fa-solid fa-chart-column"></i>

<h2>Quiz Challenge BPS</h2>

<p>Jelajah Statistik Indonesia</p>

</div>

<?php if($error!=""){ ?>

<div class="error">

<?= htmlspecialchars($error); ?>

</div>

<?php } ?>

<form method="POST">

<div class="input-group">

<i class="fa-solid fa-envelope"></i>

<input
type="email"
name="email"
placeholder="Masukkan Email"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-lock"></i>

<input
type="password"
name="password"
placeholder="Masukkan Password"
required>

</div>

<button
type="submit"
name="login">

Masuk

</button>

</form>

<div class="register">

Belum punya akun?

<a href="register.php">

Daftar

</a>

</div>

</div>

</body>

</html>
