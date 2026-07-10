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

<link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . "/assets/admin.css"); ?>">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="admin-login-box">

<i class="fa-solid fa-user-shield" style="font-size:40px;color:#1565C0;"></i>

<h2>Admin Panel</h2>
<p>Quiz Challenge BPS</p>

<?php if ($error != "") { ?>
<div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
<?php } ?>

<form method="POST">

<input type="text" name="username" placeholder="Username" required autofocus>

<input type="password" name="password" placeholder="Password" required>

<button class="btn" type="submit" name="login">Masuk</button>

</form>

</div>

</body>

</html>
