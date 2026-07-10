<?php
require __DIR__ . "/auth.php";

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
$totalKategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM kategori"))['c'];
$totalSoal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM soal"))['c'];
$totalAttempt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM hasil_quiz"))['c'];

$soalPerKategori = mysqli_query($conn, "
    SELECT k.nama, k.id,
        SUM(CASE WHEN s.level='Mudah' THEN 1 ELSE 0 END) AS mudah,
        SUM(CASE WHEN s.level='Sedang' THEN 1 ELSE 0 END) AS sedang,
        SUM(CASE WHEN s.level='Sulit' THEN 1 ELSE 0 END) AS sulit
    FROM kategori k
    LEFT JOIN soal s ON s.kategori_id = k.id
    GROUP BY k.id
    ORDER BY k.id
");

$activeMenu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | Quiz Challenge BPS</title>

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

<h1>Dashboard</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<div class="admin-stats">

<div class="admin-stat-box">
<h2><?= (int) $totalUsers; ?></h2>
<p>Total Pemain</p>
</div>

<div class="admin-stat-box">
<h2><?= (int) $totalKategori; ?></h2>
<p>Kategori Materi</p>
</div>

<div class="admin-stat-box">
<h2><?= (int) $totalSoal; ?></h2>
<p>Total Soal</p>
</div>

<div class="admin-stat-box">
<h2><?= (int) $totalAttempt; ?></h2>
<p>Quiz Dikerjakan</p>
</div>

</div>

<div class="admin-panel">

<h2>Jumlah Soal per Kategori &amp; Level</h2>

<table class="admin-table">

<thead>
<tr>
<th>Kategori</th>
<th>Mudah</th>
<th>Sedang</th>
<th>Sulit</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php while ($row = mysqli_fetch_assoc($soalPerKategori)) { ?>

<tr>
<td><?= e($row['nama']); ?></td>
<td><?= (int) $row['mudah']; ?></td>
<td><?= (int) $row['sedang']; ?></td>
<td><?= (int) $row['sulit']; ?></td>
<td><a class="btn btn-sm" href="soal.php?kategori=<?= $row['id']; ?>">Kelola Soal</a></td>
</tr>

<?php } ?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>
