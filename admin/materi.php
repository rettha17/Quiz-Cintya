<?php
require __DIR__ . "/auth.php";
require __DIR__ . "/../inc/functions.php";

$pesan = "";
$pesanTipe = "success";

$daftarKategoriDropdown = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama");
$kategoriOptions = [];
while ($k = mysqli_fetch_assoc($daftarKategoriDropdown)) {
    $kategoriOptions[] = $k;
}

/* ===========================
   SIMPAN (tambah / edit)
=========================== */

if (isset($_POST['simpan'])) {

    $id = (int) $_POST['id'];
    $kategori_id = (int) $_POST['kategori_id'];
    $judul = trim($_POST['judul']);
    $isi = trim($_POST['isi']);
    $urutan = (int) $_POST['urutan'];

    if ($judul === '' || $isi === '') {

        $pesan = "Judul dan Isi materi wajib diisi.";
        $pesanTipe = "error";

    } elseif ($id > 0) {

        $stmt = mysqli_prepare($conn, "UPDATE materi SET kategori_id=?, judul=?, isi=?, urutan=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "issii", $kategori_id, $judul, $isi, $urutan, $id);
        mysqli_stmt_execute($stmt);
        $pesan = "Materi berhasil diperbarui.";

    } else {

        $stmt = mysqli_prepare($conn, "INSERT INTO materi (kategori_id, judul, isi, urutan) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "issi", $kategori_id, $judul, $isi, $urutan);
        mysqli_stmt_execute($stmt);
        logAktivitas($conn, 'materi_baru', 'Admin menambahkan materi baru "' . e($judul) . '".', 'fa-book', 'purple');
        $pesan = "Materi baru berhasil ditambahkan.";

    }

}

/* ===========================
   HAPUS
=========================== */

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];
    $stmt = mysqli_prepare($conn, "DELETE FROM materi WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $pesan = "Materi berhasil dihapus.";

}

/* ===========================
   DATA UNTUK FORM EDIT
=========================== */

$editData = [
    'id' => 0, 'kategori_id' => $kategoriOptions[0]['id'] ?? 0,
    'judul' => '', 'isi' => '', 'urutan' => 0,
];

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM materi WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($found) {
        $editData = $found;
    }

}

/* ===========================
   FILTER LIST
=========================== */

$filterKategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

if ($filterKategori > 0) {

    $stmt = mysqli_prepare($conn, "
        SELECT m.*, k.nama AS nama_kategori
        FROM materi m
        JOIN kategori k ON k.id = m.kategori_id
        WHERE m.kategori_id = ?
        ORDER BY m.urutan, m.id
    ");
    mysqli_stmt_bind_param($stmt, "i", $filterKategori);
    mysqli_stmt_execute($stmt);
    $daftarMateri = mysqli_stmt_get_result($stmt);

} else {

    $daftarMateri = mysqli_query($conn, "
        SELECT m.*, k.nama AS nama_kategori
        FROM materi m
        JOIN kategori k ON k.id = m.kategori_id
        ORDER BY m.kategori_id, m.urutan, m.id
    ");

}

$activeMenu = 'materi';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Materi Pembelajaran | Admin StatQuest BPS</title>

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

<h1>Materi Pembelajaran</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-panel">

<h2><?= $editData['id'] > 0 ? 'Edit Materi' : 'Tambah Materi'; ?></h2>

<form method="POST" class="admin-form">

<input type="hidden" name="id" value="<?= (int) $editData['id']; ?>">

<div class="admin-form-row">

<div>
<label>Kategori</label>
<select name="kategori_id" required>
<?php foreach ($kategoriOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $editData['kategori_id'] == $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>
</div>

<div>
<label>Urutan Tampil</label>
<input type="number" name="urutan" value="<?= (int) $editData['urutan']; ?>" min="0">
</div>

</div>

<label>Judul Materi</label>
<input type="text" name="judul" value="<?= e($editData['judul']); ?>" required>

<label>Isi Materi (boleh pakai tag HTML sederhana seperti &lt;b&gt;, &lt;br&gt;)</label>
<textarea name="isi" rows="6" required><?= e($editData['isi']); ?></textarea>

<br><br>

<button class="btn" type="submit" name="simpan">
<?= $editData['id'] > 0 ? 'Simpan Perubahan' : 'Tambah Materi'; ?>
</button>

<?php if ($editData['id'] > 0) { ?>
<a class="btn btn-secondary" href="materi.php">Batal</a>
<?php } ?>

</form>

</div>

<div class="admin-panel">

<h2>Daftar Materi</h2>

<form method="GET" class="admin-filter">

<select name="kategori" onchange="this.form.submit()">
<option value="0">Semua Kategori</option>
<?php foreach ($kategoriOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $filterKategori == $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>

</form>

<table class="admin-table">

<thead>
<tr>
<th>No</th>
<th>Kategori</th>
<th>Urutan</th>
<th>Judul</th>
<th>Isi</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarMateri) == 0) { ?>

<tr><td colspan="6" style="text-align:center;">Belum ada materi.</td></tr>

<?php } else { $no = 1; while ($m = mysqli_fetch_assoc($daftarMateri)) { ?>

<tr>

<td><?= $no++; ?></td>

<td><?= e($m['nama_kategori']); ?></td>

<td><?= (int) $m['urutan']; ?></td>

<td><?= e($m['judul']); ?></td>

<td><?= e(mb_strimwidth(strip_tags($m['isi']), 0, 80, '...')); ?></td>

<td>

<a class="btn btn-sm btn-edit" href="materi.php?edit=<?= $m['id']; ?>">Edit</a>

<a class="btn btn-sm btn-hapus" href="materi.php?hapus=<?= $m['id']; ?>" onclick="return confirm('Yakin hapus materi ini?')">Hapus</a>

</td>

</tr>

<?php } } ?>

</tbody>

</table>

</div>

<?php require __DIR__ . "/partials/footer.php"; ?>

</main>

</div>

</body>

</html>
