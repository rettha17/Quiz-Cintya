<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$progress = getProgress($conn, $user_id);
$persenSelesai = getPersenSelesai($progress);
$rekomendasi = getRekomendasi($conn, $user_id, 70, 5);

$activeMenu = 'progress';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Progress Belajar | Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<?php require_once __DIR__ . "/../inc/nav.php"; ?>

<!-- ================= CONTENT ================= -->

<main class="content">

<div class="page-watermark">
<div class="wm-inner">
<img src="assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="section-title">

<h2>Progress Belajar</h2>

</div>

<div class="progress-summary-card">

<h3>Total Materi Dikuasai</h3>

<div class="progress-bar">

<div class="progress-fill" style="width:<?= $persenSelesai; ?>%"></div>

</div>

<small><?= $persenSelesai; ?>% dari seluruh kategori &amp; level selesai dicoba</small>

</div>

<div class="progress-grid">

<?php foreach ($progress as $kategoriId => $kat): ?>

<div class="progress-item-card">

<div class="card-top">

<div class="icon green">

<i class="fa-solid <?= e($kat['icon']); ?>"></i>

</div>

<h3><?= e($kat['nama']); ?></h3>

</div>

<table class="progress-table">

<thead>

<tr>

<th>Level</th>

<th>Status</th>

<th>Skor Terbaik</th>

</tr>

</thead>

<tbody>

<?php foreach ($kat['levels'] as $lvl => $best): ?>

<tr>

<td><span class="badge <?= strtolower($lvl); ?>"><?= $lvl; ?></span></td>

<td>

<?php if ($best === null): ?>
<span class="pending">Belum Dicoba</span>
<?php elseif ($best >= 70): ?>
<span class="success">Selesai</span>
<?php else: ?>
<span class="process">Perlu Diulang</span>
<?php endif; ?>

</td>

<td><?= $best !== null ? $best : '-'; ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endforeach; ?>

</div>

<?php if (!empty($rekomendasi)): ?>

<section class="activity">

<div class="section-title">

<h2>📚 Rekomendasi Materi Dipelajari Kembali</h2>

</div>

<table>

<thead>

<tr>

<th>Kategori</th>

<th>Level</th>

<th>Skor Terbaik</th>

<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php foreach ($rekomendasi as $r): ?>

<tr>

<td><?= e($r['nama']); ?></td>

<td><?= e($r['level']); ?></td>

<td><?= $r['best_score'] !== null ? $r['best_score'] : 'Belum dicoba'; ?></td>

<td>

<a href="quiz.php?kategori=<?= $r['kategori_id'] ?>&level=<?= urlencode($r['level']) ?>">

<button>Pelajari Lagi</button>

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</section>

<?php endif; ?>

</main>

</div>

</body>

</html>
