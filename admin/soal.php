<?php
require __DIR__ . "/auth.php";

$pesan = "";
$pesanTipe = "success";
$levelPilihan = ['Mudah', 'Sedang', 'Sulit'];

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
    $level = in_array($_POST['level'], $levelPilihan, true) ? $_POST['level'] : 'Mudah';
    $pertanyaan = trim($_POST['pertanyaan']);
    $opsi_a = trim($_POST['opsi_a']);
    $opsi_b = trim($_POST['opsi_b']);
    $opsi_c = trim($_POST['opsi_c']);
    $opsi_d = trim($_POST['opsi_d']);
    $jawaban = trim($_POST['jawaban']);
    $pembahasan = trim($_POST['pembahasan']);

    if ($pertanyaan === '' || $opsi_a === '' || $opsi_b === '' || $opsi_c === '' || $opsi_d === '' || !in_array($jawaban, ['A','B','C','D'], true)) {

        $pesan = "Semua field wajib diisi dan jawaban benar harus dipilih.";
        $pesanTipe = "error";

    } elseif ($id > 0) {

        $sql = "UPDATE soal
                SET kategori_id=?, level=?, pertanyaan=?, opsi_a=?, opsi_b=?, opsi_c=?, opsi_d=?, jawaban=?, pembahasan=?
                WHERE id=?";
        $types = "isssssssssi"; // extra fix below via array-based binding
        $params = [$kategori_id, $level, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban, $pembahasan, $id];
        $types = "i" . str_repeat("s", 8) . "i"; // kategori_id, 8 string fields, id

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $pesan = "Soal berhasil diperbarui.";

    } else {

        $sql = "INSERT INTO soal (kategori_id, level, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, pembahasan)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $params = [$kategori_id, $level, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban, $pembahasan];
        $types = "i" . str_repeat("s", 8); // kategori_id + 8 string fields

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $pesan = "Soal baru berhasil ditambahkan.";

    }

}

/* ===========================
   HAPUS
=========================== */

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];
    $stmt = mysqli_prepare($conn, "DELETE FROM soal WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $pesan = "Soal berhasil dihapus.";

}

/* ===========================
   DATA UNTUK FORM EDIT
=========================== */

$editData = [
    'id' => 0, 'kategori_id' => $kategoriOptions[0]['id'] ?? 0, 'level' => 'Mudah',
    'pertanyaan' => '', 'opsi_a' => '', 'opsi_b' => '', 'opsi_c' => '', 'opsi_d' => '',
    'jawaban' => 'A', 'pembahasan' => ''
];

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM soal WHERE id=?");
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
$filterLevel = isset($_GET['level_filter']) && in_array($_GET['level_filter'], $levelPilihan, true) ? $_GET['level_filter'] : '';

$where = [];
$whereParams = [];
$whereTypes = "";

if ($filterKategori > 0) {
    $where[] = "s.kategori_id = ?";
    $whereTypes .= "i";
    $whereParams[] = $filterKategori;
}

if ($filterLevel !== '') {
    $where[] = "s.level = ?";
    $whereTypes .= "s";
    $whereParams[] = $filterLevel;
}

$sqlList = "
    SELECT s.*, k.nama AS nama_kategori
    FROM soal s
    JOIN kategori k ON k.id = s.kategori_id
";

if (!empty($where)) {
    $sqlList .= " WHERE " . implode(" AND ", $where);
}

$sqlList .= " ORDER BY s.kategori_id, FIELD(s.level,'Mudah','Sedang','Sulit'), s.id";

$stmt = mysqli_prepare($conn, $sqlList);
if (!empty($whereParams)) {
    mysqli_stmt_bind_param($stmt, $whereTypes, ...$whereParams);
}
mysqli_stmt_execute($stmt);
$daftarSoal = mysqli_stmt_get_result($stmt);

$activeMenu = 'soal';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Bank Soal | Admin Quiz Challenge BPS</title>

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

<h1>Bank Soal</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-panel">

<h2><?= $editData['id'] > 0 ? 'Edit Soal' : 'Tambah Soal'; ?></h2>

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
<label>Level</label>
<select name="level" required>
<?php foreach ($levelPilihan as $lvl) { ?>
<option value="<?= $lvl; ?>" <?= $editData['level'] == $lvl ? 'selected' : ''; ?>><?= $lvl; ?></option>
<?php } ?>
</select>
</div>

</div>

<label>Pertanyaan</label>
<textarea name="pertanyaan" required><?= e($editData['pertanyaan']); ?></textarea>

<div class="admin-form-row">

<div>
<label>Opsi A</label>
<input type="text" name="opsi_a" value="<?= e($editData['opsi_a']); ?>" required>
</div>

<div>
<label>Opsi B</label>
<input type="text" name="opsi_b" value="<?= e($editData['opsi_b']); ?>" required>
</div>

</div>

<div class="admin-form-row">

<div>
<label>Opsi C</label>
<input type="text" name="opsi_c" value="<?= e($editData['opsi_c']); ?>" required>
</div>

<div>
<label>Opsi D</label>
<input type="text" name="opsi_d" value="<?= e($editData['opsi_d']); ?>" required>
</div>

</div>

<label>Jawaban Benar</label>
<select name="jawaban" required>
<?php foreach (['A','B','C','D'] as $opt) { ?>
<option value="<?= $opt; ?>" <?= $editData['jawaban'] == $opt ? 'selected' : ''; ?>><?= $opt; ?></option>
<?php } ?>
</select>

<label>Pembahasan</label>
<textarea name="pembahasan"><?= e($editData['pembahasan']); ?></textarea>

<br><br>

<button class="btn" type="submit" name="simpan">
<?= $editData['id'] > 0 ? 'Simpan Perubahan' : 'Tambah Soal'; ?>
</button>

<?php if ($editData['id'] > 0) { ?>
<a class="btn btn-secondary" href="soal.php">Batal</a>
<?php } ?>

</form>

</div>

<div class="admin-panel">

<h2>Daftar Soal</h2>

<form method="GET" class="admin-filter">

<select name="kategori" onchange="this.form.submit()">
<option value="0">Semua Kategori</option>
<?php foreach ($kategoriOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $filterKategori == $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>

<select name="level_filter" onchange="this.form.submit()">
<option value="">Semua Level</option>
<?php foreach ($levelPilihan as $lvl) { ?>
<option value="<?= $lvl; ?>" <?= $filterLevel === $lvl ? 'selected' : ''; ?>><?= $lvl; ?></option>
<?php } ?>
</select>

</form>

<table class="admin-table">

<thead>
<tr>
<th>No</th>
<th>Kategori</th>
<th>Level</th>
<th>Pertanyaan</th>
<th>Jawaban</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php $no = 1; while ($s = mysqli_fetch_assoc($daftarSoal)) { ?>

<tr>

<td><?= $no++; ?></td>

<td><?= e($s['nama_kategori']); ?></td>

<td><span class="badge-level <?= strtolower($s['level']); ?>"><?= e($s['level']); ?></span></td>

<td><?= e($s['pertanyaan']); ?></td>

<td><?= e($s['jawaban']); ?></td>

<td>

<a class="btn btn-sm btn-edit" href="soal.php?edit=<?= $s['id']; ?>">Edit</a>

<a class="btn btn-sm btn-hapus" href="soal.php?hapus=<?= $s['id']; ?>" onclick="return confirm('Yakin hapus soal ini?')">Hapus</a>

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
