<?php
require __DIR__ . "/auth.php";

$pesan = "";
$pesanTipe = "success";

if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($nama === '' || $username === '') {

        $pesan = "Nama dan Username wajib diisi.";
        $pesanTipe = "error";

    } else {

        $stmt = mysqli_prepare($conn, "SELECT id FROM admin WHERE username=? AND id<>?");
        mysqli_stmt_bind_param($stmt, "si", $username, $admin_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_get_result($stmt)->num_rows > 0) {

            $pesan = "Username sudah dipakai admin lain.";
            $pesanTipe = "error";

        } elseif (!empty($password)) {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "UPDATE admin SET nama=?, username=?, password=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssi", $nama, $username, $hash, $admin_id);
            mysqli_stmt_execute($stmt);

            $admin['nama'] = $nama;
            $admin['username'] = $username;
            $pesan = "Profil berhasil diperbarui.";

        } else {

            $stmt = mysqli_prepare($conn, "UPDATE admin SET nama=?, username=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $username, $admin_id);
            mysqli_stmt_execute($stmt);

            $admin['nama'] = $nama;
            $admin['username'] = $username;
            $pesan = "Profil berhasil diperbarui.";

        }

    }

}

$activeMenu = '';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Profil | Admin Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . "/assets/admin.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="admin-container">

<?php require __DIR__ . "/partials/nav.php"; ?>

<main class="admin-content">

<div class="page-watermark">
<div class="wm-inner">
<img src="../pengguna/assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<div class="admin-topbar">

<h1>Edit Profil</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-panel" style="max-width:520px;">

<h2><i class="fa-solid fa-user-shield"></i> Akun Administrator</h2>

<form method="POST" class="admin-form">

<label>Nama Lengkap</label>
<input type="text" name="nama" value="<?= e($admin['nama']); ?>" required>

<label>Username</label>
<input type="text" name="username" value="<?= e($admin['username']); ?>" required>

<label>Password Baru</label>
<input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti password">

<div style="margin-top:20px;display:flex;gap:12px;">
<button type="submit" name="simpan" class="btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
<a href="index.php"><button type="button" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Batal</button></a>
</div>

</form>

</div>

</main>

</div>

</body>

</html>
