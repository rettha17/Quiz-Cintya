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

// Avatar gratis: kategori "kartun" (avatar-01..09 + kartun-01..12, wajah kartun),
// "kucing" (cat-01..10, stiker kucing), dan "lainnya" (hewan & ikon) digabung
// jadi satu daftar untuk grid, difilter di sisi klien lewat tab kategori.
$daftarAvatar = [];
for ($i = 1; $i <= 9; $i++) {
    $file = sprintf('avatar-%02d.jpg', $i);
    $daftarAvatar[] = ['file' => $file, 'kategori' => 'kartun', 'zoom' => $avatarZoom[$i] ?? 1];
}
for ($i = 1; $i <= 12; $i++) {
    $file = sprintf('kartun-%02d.jpg', $i);
    $daftarAvatar[] = ['file' => $file, 'kategori' => 'kartun', 'zoom' => 1];
}
for ($i = 1; $i <= 10; $i++) {
    $file = sprintf('cat-%02d.jpg', $i);
    $daftarAvatar[] = ['file' => $file, 'kategori' => 'kucing', 'zoom' => 1.4];
}
for ($i = 1; $i <= 21; $i++) {
    $file = sprintf('lainnya-%02d.jpg', $i);
    $daftarAvatar[] = ['file' => $file, 'kategori' => 'lainnya', 'zoom' => 1];
}

function avatarZoomUntuk($file, $daftarAvatar) {
    foreach ($daftarAvatar as $a) {
        if ($a['file'] === $file) return $a['zoom'];
    }
    return 1;
}

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

    } elseif ($avatarPilihan !== '' && preg_match('/^(avatar-0[1-9]|kartun-(0[1-9]|1[0-2])|cat-(0[1-9]|10)|lainnya-(0[1-9]|1[0-9]|2[0-1]))\.jpg$/', $avatarPilihan)) {

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

<div class="edit-profil-page">

<div class="edit-profil-heading">
<h1>Edit Profil</h1>
<p>Kelola informasi profil dan avatar kamu</p>
</div>

<?php if($pesan!=""){ ?>

<div class="success-box">

<i class="fa-solid fa-circle-check"></i>

<?= e($pesan); ?>

</div>

<?php } ?>

<form method="POST" enctype="multipart/form-data">

<!-- AVATAR -->

<div class="avatar-card">

<div class="avatar-card-head">
<h2>Avatar Profil</h2>
<p>Pilih foto atau avatar untuk ditampilkan di profil kamu.</p>
</div>

<?php
$fotoSaatIni = !empty($user['foto']) ? $user['foto'] : 'default.png';
$previewZoom = '';
if (preg_match('#^avatars/(.+)$#', $fotoSaatIni, $m)) {
    $z = avatarZoomUntuk($m[1], $daftarAvatar);
    if ($z != 1) $previewZoom = 'transform:scale(' . $z . ');';
}

$tabAktif = 'kucing';
if (preg_match('#^avatars/(avatar-0[1-9]|kartun-\d\d)\.jpg$#', $fotoSaatIni)) {
    $tabAktif = 'kartun';
} elseif (preg_match('#^avatars/lainnya-\d\d\.jpg$#', $fotoSaatIni)) {
    $tabAktif = 'lainnya';
}
?>

<div class="avatar-preview-wrap">

<div class="avatar-frame">
<img
id="preview"
src="assets/img/<?= e($fotoSaatIni); ?>"
style="<?= $previewZoom; ?>">
</div>

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
class="link-hapus-foto"
onclick="return confirm('Hapus foto profil dan kembalikan ke default?')">
<i class="fa-solid fa-trash"></i>
Hapus Foto
</button>

<?php } ?>

</div>

<input type="hidden" name="avatar_pilihan" id="avatarPilihan" value="">

<hr class="avatar-divider">

<div class="avatar-gallery-v2">

<h3>Pilih Avatar Gratis</h3>
<p>Klik avatar untuk memilih</p>

<div class="avatar-tabs">
<button type="button" class="avatar-tab<?= $tabAktif === 'kucing' ? ' active' : ''; ?>" data-tab="kucing" onclick="pilihTab('kucing', this)">
<i class="fa-solid fa-cat"></i> Kucing
</button>
<button type="button" class="avatar-tab<?= $tabAktif === 'kartun' ? ' active' : ''; ?>" data-tab="kartun" onclick="pilihTab('kartun', this)">
<i class="fa-solid fa-face-smile"></i> Kartun
</button>
<button type="button" class="avatar-tab<?= $tabAktif === 'lainnya' ? ' active' : ''; ?>" data-tab="lainnya" onclick="pilihTab('lainnya', this)">
<i class="fa-solid fa-shapes"></i> Lainnya
</button>
<button type="button" class="avatar-tab" data-tab="campuran" onclick="pilihTab('campuran', this)">
<i class="fa-solid fa-star"></i> Campuran
</button>
</div>

<div class="avatar-grid-v2" id="avatarGrid">

<?php foreach ($daftarAvatar as $i => $a):
    $isActive = ($user['foto'] === "avatars/{$a['file']}");
    $tampil = ($a['kategori'] === $tabAktif);
?>

<button
type="button"
class="avatar-option-v2<?= $isActive ? ' selected' : ''; ?>"
data-avatar="<?= $a['file']; ?>"
data-kategori="<?= $a['kategori']; ?>"
style="<?= $tampil ? '' : 'display:none;'; ?>"
onclick="pilihAvatar('<?= $a['file']; ?>', <?= $a['zoom']; ?>, this)">
<img src="assets/img/avatars/<?= $a['file']; ?>" alt="Avatar <?= $i + 1; ?>" style="transform:scale(<?= $a['zoom']; ?>);">
<span class="avatar-check"><i class="fa-solid fa-check"></i></span>
</button>

<?php endforeach; ?>

</div>

<div class="avatar-info-note">
<i class="fa-solid fa-circle-info"></i>
<span>Avatar yang kamu pilih akan ditampilkan di profil dan leaderboard.</span>
</div>

</div>

</div>

<!-- AKUN -->

<div class="account-card">

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

</div>

<div class="btn-group">

<a href="profil.php">

<button
type="button"
class="secondary">

Batal

</button>

</a>

<button
type="submit"
name="simpan">

<i class="fa-solid fa-floppy-disk"></i>

Simpan Perubahan

</button>

</div>

</form>

</div>

<?php require __DIR__ . "/../inc/footer.php"; ?>

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

        document.querySelectorAll(".avatar-option-v2").forEach(function(el){
            el.classList.remove("selected");
        });

    }

});

function pilihAvatar(file, zoom, btn){

    preview.src = "assets/img/avatars/" + file;
    preview.style.transform = "scale(" + zoom + ")";
    avatarPilihan.value = file;
    foto.value = "";

    document.querySelectorAll(".avatar-option-v2").forEach(function(el){
        el.classList.remove("selected");
    });

    btn.classList.add("selected");

}

function pilihTab(kategori, tabBtn){

    document.querySelectorAll(".avatar-tab").forEach(function(t){
        t.classList.remove("active");
    });
    tabBtn.classList.add("active");

    document.querySelectorAll(".avatar-option-v2").forEach(function(opt){
        const tampil = (kategori === "campuran") || (opt.dataset.kategori === kategori);
        opt.style.display = tampil ? "" : "none";
    });

}

</script>

</body>
</html>
