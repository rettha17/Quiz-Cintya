<?php
require __DIR__ . "/auth.php";

$pesan = "";
$pesanTipe = "success";

/* ===========================
   SIMPAN (tambah / edit)
=========================== */

if (isset($_POST['simpan'])) {

    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama']);
    $icon = trim($_POST['icon']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($nama === '' || $icon === '') {

        $pesan = "Nama dan Icon wajib diisi.";
        $pesanTipe = "error";

    } elseif ($id > 0) {

        $stmt = mysqli_prepare($conn, "UPDATE kategori SET nama=?, icon=?, deskripsi=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $icon, $deskripsi, $id);
        mysqli_stmt_execute($stmt);
        $pesan = "Kategori '$nama' berhasil diperbarui.";

    } else {

        $stmt = mysqli_prepare($conn, "INSERT INTO kategori (nama, icon, deskripsi) VALUES (?,?,?)");
        mysqli_stmt_bind_param($stmt, "sss", $nama, $icon, $deskripsi);
        mysqli_stmt_execute($stmt);
        $pesan = "Kategori '$nama' berhasil ditambahkan.";

    }

}

/* ===========================
   HAPUS
=========================== */

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $jumlahSoal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM soal WHERE kategori_id=$id"))['c'];

    if ($jumlahSoal > 0) {

        $pesan = "Kategori tidak bisa dihapus karena masih memiliki $jumlahSoal soal. Hapus soalnya terlebih dahulu.";
        $pesanTipe = "error";

    } else {

        $stmt = mysqli_prepare($conn, "DELETE FROM kategori WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $pesan = "Kategori berhasil dihapus.";

    }

}

/* ===========================
   DATA UNTUK FORM EDIT
=========================== */

$editData = ['id' => 0, 'nama' => '', 'icon' => '', 'deskripsi' => ''];

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM kategori WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($found) {
        $editData = $found;
    }

}

$daftarKategori = mysqli_query($conn, "
    SELECT k.*, COUNT(s.id) AS jumlah_soal
    FROM kategori k
    LEFT JOIN soal s ON s.kategori_id = k.id
    GROUP BY k.id
    ORDER BY k.id
");

$activeMenu = 'kategori';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola Kategori | Admin Quiz Challenge BPS</title>

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

<a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="admin-topbar">

<h1>Kelola Kategori</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-panel">

<h2><?= $editData['id'] > 0 ? 'Edit Kategori' : 'Tambah Kategori'; ?></h2>

<form method="POST" class="admin-form">

<input type="hidden" name="id" value="<?= (int) $editData['id']; ?>">

<div class="admin-form-row">

<div>
<label>Nama Kategori</label>
<input type="text" name="nama" value="<?= e($editData['nama']); ?>" required>
</div>

<div>
<label>Icon (Font Awesome, contoh: fa-chart-simple)</label>
<input type="text" name="icon" value="<?= e($editData['icon']); ?>" required>
</div>

</div>

<label>Deskripsi</label>
<textarea name="deskripsi"><?= e($editData['deskripsi']); ?></textarea>

<br><br>

<button class="btn" type="submit" name="simpan">
<?= $editData['id'] > 0 ? 'Simpan Perubahan' : 'Tambah Kategori'; ?>
</button>

<?php if ($editData['id'] > 0) { ?>
<a class="btn btn-secondary" href="kategori.php">Batal</a>
<?php } ?>

</form>

</div>

<div class="admin-panel">

<h2>Daftar Kategori</h2>

<table class="admin-table">

<thead>
<tr>
<th>No</th>
<th>Nama</th>
<th>Icon</th>
<th>Deskripsi</th>
<th>Jumlah Soal</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php $no = 1; while ($k = mysqli_fetch_assoc($daftarKategori)) { ?>

<tr>

<td><?= $no++; ?></td>

<td><?= e($k['nama']); ?></td>

<td><i class="fa-solid <?= e($k['icon']); ?>"></i> <?= e($k['icon']); ?></td>

<td><?= e($k['deskripsi']); ?></td>

<td><?= (int) $k['jumlah_soal']; ?></td>

<td>

<a class="btn btn-sm btn-edit" href="kategori.php?edit=<?= $k['id']; ?>">Edit</a>

<a class="btn btn-sm btn-hapus" href="kategori.php?hapus=<?= $k['id']; ?>" onclick="return confirm('Yakin hapus kategori ini?')">Hapus</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>
