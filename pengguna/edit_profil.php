<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$pesan = "";

// Avatar gratis bawaan dipakai bersama semua pengguna (assets/img/avatars/),
// jadi TIDAK BOLEH dihapus dari disk saat pengguna ganti/hapus foto.
function fotoBolehDihapus($foto) {
    return $foto !== '' && $foto !== 'default.png' && strpos($foto, 'avatars/') !== 0;
}

// Setiap foto avatar punya proporsi lingkaran-ke-frame yang sedikit berbeda
// (diukur per piksel), jadi di-zoom sedikit di sini biar seragam tampilannya.
$avatarZoom = [
    1 => 0.95,
    2 => 1.07,
    3 => 0.97,
    4 => 1.08,
    5 => 1.10,
    6 => 0.91,
    7 => 0.99,
    8 => 0.97,
    9 => 1.04,
];

if (isset($_POST['hapus_foto'])) {

    $folder = __DIR__ . "/assets/img/";

    if (fotoBolehDihapus($user['foto']) && file_exists($folder.$user['foto'])) {
        unlink($folder.$user['foto']);
    }

    $stmt = mysqli_prepare($conn, "UPDATE users SET foto='default.png' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $user['foto'] = 'default.png';
    $pesan = "Foto profil berhasil dihapus.";

} elseif (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $avatarPilihan = trim($_POST['avatar_pilihan'] ?? '');

    // Foto lama
    $foto = $user['foto'];

    // Folder foto profil
    $folder = __DIR__ . "/assets/img/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    // Upload foto baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        $allowed = array("jpg","jpeg","png","gif","webp");

        if (in_array($ext, $allowed)) {

            $namaFile = "user_" . $user_id . "_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $folder.$namaFile)) {

                // Hapus foto lama jika file milik sendiri (bukan avatar bawaan/default)
                if (fotoBolehDihapus($foto) && file_exists($folder.$foto)) {
                    unlink($folder.$foto);
                }

                $foto = $namaFile;

            } else {

                $pesan = "Upload foto gagal.";

            }

        } else {

            $pesan = "Format gambar harus JPG, JPEG, PNG, GIF atau WEBP.";

        }

    } elseif ($avatarPilihan !== '' && preg_match('/^avatar-0[1-9]\.jpg$/', $avatarPilihan)) {

        // Ganti ke avatar gratis bawaan
        if (fotoBolehDihapus($foto) && file_exists($folder.$foto)) {
            unlink($folder.$foto);
        }

        $foto = 'avatars/' . $avatarPilihan;

    }

    if ($pesan == "") {

        if (!empty($password)) {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "
                UPDATE users
                SET nama=?, email=?, password=?, foto=?
                WHERE id=?
            ");
            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $hash, $foto, $user_id);

        } else {

            $stmt = mysqli_prepare($conn, "
                UPDATE users
                SET nama=?, email=?, foto=?
                WHERE id=?
            ");
            mysqli_stmt_bind_param($stmt, "sssi", $nama, $email, $foto, $user_id);

        }

        if (mysqli_stmt_execute($stmt)) {

            $pesan = "Profil berhasil diperbarui.";
            $user['nama'] = $nama;
            $user['email'] = $email;
            $user['foto'] = $foto;

        } else {

            $pesan = "Gagal memperbarui profil.";

        }

    }

}

$activeMenu = 'edit_profil';
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Profil</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<?php require_once __DIR__ . "/../inc/nav.php"; ?>

<main class="content">

<div class="page-watermark">
<div class="wm-inner">
<img src="assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="profile-page edit-mode">

<div class="edit-header">

<i class="fa-solid fa-image icon-watermark"></i>

<h1>
<i class="fa-solid fa-user-pen"></i>
Edit Profil
</h1>

<p>Perbarui foto, nama, email, dan password akun kamu.</p>

</div>

<?php if($pesan!=""){ ?>

<div class="success-box">

<i class="fa-solid fa-circle-check"></i>

<?= e($pesan); ?>

</div>

<?php } ?>

<form method="POST" enctype="multipart/form-data">

<!-- FOTO -->

<div class="edit-photo">

<?php
$fotoSaatIni = !empty($user['foto']) ? $user['foto'] : 'default.png';
$previewZoom = '';
if (preg_match('#^avatars/avatar-0([1-9])\.jpg$#', $fotoSaatIni, $m)) {
    $previewZoom = 'transform:scale(' . ($avatarZoom[(int) $m[1]] ?? 1) . ');';
}
?>

<div class="avatar-frame">
<img
id="preview"
src="assets/img/<?= e($fotoSaatIni); ?>"
style="<?= $previewZoom; ?>">
</div>

<div class="photo-actions">

<label class="upload-btn">

<i class="fa-solid fa-camera"></i>

Pilih Foto

<input
type="file"
id="foto"
name="foto"
accept="image/*"
hidden>

</label>

<?php if (!empty($user['foto']) && $user['foto'] !== 'default.png') { ?>

<button
type="submit"
name="hapus_foto"
class="upload-btn hapus-foto-btn"
onclick="return confirm('Hapus foto profil dan kembalikan ke default?')">

<i class="fa-solid fa-trash"></i>

Hapus Foto

</button>

<?php } ?>

</div>

<input type="hidden" name="avatar_pilihan" id="avatarPilihan" value="">

<div class="avatar-gallery">

<p class="avatar-gallery-label"><i class="fa-solid fa-wand-magic-sparkles"></i> Atau pilih avatar gratis</p>

<div class="avatar-grid">

<?php for ($i = 1; $i <= 9; $i++):
    $avatarFile = sprintf('avatar-%02d.jpg', $i);
    $isActive = ($user['foto'] === "avatars/$avatarFile");
    $zoom = $avatarZoom[$i] ?? 1;
?>

<button
type="button"
class="avatar-option<?= $isActive ? ' selected' : ''; ?>"
data-avatar="<?= $avatarFile; ?>"
onclick="pilihAvatar('<?= $avatarFile; ?>', this)">
<img src="assets/img/avatars/<?= $avatarFile; ?>" alt="Avatar <?= $i; ?>" style="transform:scale(<?= $zoom; ?>);">
</button>

<?php endfor; ?>

</div>

</div>

</div>

<hr>

<label>
<i class="fa-solid fa-user"></i>
Nama Lengkap
</label>

<input
type="text"
name="nama"
value="<?= e($user['nama']); ?>"
required>

<label>
<i class="fa-solid fa-envelope"></i>
Email
</label>

<input
type="email"
name="email"
value="<?= e($user['email']); ?>"
required>

<label>
<i class="fa-solid fa-lock"></i>
Password Baru
</label>

<input
type="password"
name="password"
placeholder="Kosongkan jika tidak ingin mengganti password">

<br>

<div class="btn-group">

<button
type="submit"
name="simpan">

<i class="fa-solid fa-floppy-disk"></i>

Simpan Perubahan

</button>

<a href="profil.php">

<button
type="button"
class="secondary">

<i class="fa-solid fa-arrow-left"></i>

Kembali

</button>

</a>

</div>

</form>

</div>

</main>

</div>

<script>

const foto = document.getElementById("foto");
const preview = document.getElementById("preview");
const avatarPilihan = document.getElementById("avatarPilihan");

foto.addEventListener("change", function(){

    const file = this.files[0];

    if(file){

        preview.src = URL.createObjectURL(file);
        preview.style.transform = "";
        avatarPilihan.value = "";

        document.querySelectorAll(".avatar-option").forEach(function(el){
            el.classList.remove("selected");
        });

    }

});

function pilihAvatar(file, btn){

    preview.src = "assets/img/avatars/" + file;
    preview.style.transform = btn.querySelector("img").style.transform;
    avatarPilihan.value = file;
    foto.value = "";

    document.querySelectorAll(".avatar-option").forEach(function(el){
        el.classList.remove("selected");
    });

    btn.classList.add("selected");

}

</script>

</body>
</html>
