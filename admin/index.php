<?php
require __DIR__ . "/auth.php";
require __DIR__ . "/../inc/functions.php";

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
$totalKategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM kategori"))['c'];
$totalSoal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM soal"))['c'];
$totalAttempt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM hasil_quiz"))['c'];

$soalPerKategoriResult = mysqli_query($conn, "
    SELECT k.nama, k.id,
        SUM(CASE WHEN s.level='Mudah' THEN 1 ELSE 0 END) AS mudah,
        SUM(CASE WHEN s.level='Sedang' THEN 1 ELSE 0 END) AS sedang,
        SUM(CASE WHEN s.level='Sulit' THEN 1 ELSE 0 END) AS sulit
    FROM kategori k
    LEFT JOIN soal s ON s.kategori_id = k.id
    GROUP BY k.id
    ORDER BY k.id
");
$soalPerKategori = [];
while ($row = mysqli_fetch_assoc($soalPerKategoriResult)) {
    $row['total'] = (int) $row['mudah'] + (int) $row['sedang'] + (int) $row['sulit'];
    $soalPerKategori[] = $row;
}

// Aktivitas 7 hari terakhir dipakai untuk badge tren kartu statistik & grafik quiz harian.
$usersMingguIni = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY
"))['c'];

$quizMingguIni = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM hasil_quiz WHERE tanggal >= NOW() - INTERVAL 7 DAY
"))['c'];

$quizHarianResult = mysqli_query($conn, "
    SELECT DATE(tanggal) d, COUNT(*) c
    FROM hasil_quiz
    WHERE tanggal >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(tanggal)
");
$quizHarianMap = [];
while ($row = mysqli_fetch_assoc($quizHarianResult)) {
    $quizHarianMap[$row['d']] = (int) $row['c'];
}

$userHarianResult = mysqli_query($conn, "
    SELECT DATE(created_at) d, COUNT(*) c
    FROM users
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
");
$userHarianMap = [];
while ($row = mysqli_fetch_assoc($userHarianResult)) {
    $userHarianMap[$row['d']] = (int) $row['c'];
}

$soalHarianResult = mysqli_query($conn, "
    SELECT DATE(dibuat_pada) d, COUNT(*) c
    FROM activity_log
    WHERE tipe='soal_baru' AND dibuat_pada >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(dibuat_pada)
");
$soalHarianMap = [];
while ($row = mysqli_fetch_assoc($soalHarianResult)) {
    $soalHarianMap[$row['d']] = (int) $row['c'];
}

$hariLabel = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$quizHarian = [];
$userHarian = [];
$soalHarian = [];
$kategoriHarian = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHari = $hariLabel[date('N', strtotime($tgl)) - 1];
    $quizHarian[] = ['label' => $labelHari, 'total' => $quizHarianMap[$tgl] ?? 0];
    $userHarian[] = ['label' => $labelHari, 'total' => $userHarianMap[$tgl] ?? 0];
    $soalHarian[] = ['label' => $labelHari, 'total' => $soalHarianMap[$tgl] ?? 0];
    $kategoriHarian[] = ['label' => $labelHari, 'total' => $totalKategori];
}
$quizHarianMax = max(1, max(array_column($quizHarian, 'total')));

// Geometri grafik garis "Quiz Dikerjakan (7 Hari Terakhir)".
$chartW = 640;
$chartH = 220;
$padL = 34;
$padR = 14;
$padT = 28;
$padB = 34;
$plotW = $chartW - $padL - $padR;
$plotH = $chartH - $padT - $padB;

$niceMax = max(4, (int) (ceil(($quizHarianMax * 1.25) / 2) * 2));
$gridSteps = 5;

$lineN = count($quizHarian);
$lineStepX = $plotW / ($lineN - 1);
$linePoints = [];
foreach ($quizHarian as $i => $d) {
    $x = round($padL + $i * $lineStepX, 1);
    $y = round($padT + $plotH - ($d['total'] / $niceMax) * $plotH, 1);
    $linePoints[] = ['x' => $x, 'y' => $y, 'label' => $d['label'], 'total' => $d['total']];
}
$linePolyline = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $linePoints));
$lineAreaPolyline = "{$padL}," . ($padT + $plotH) . " " . $linePolyline . " " . ($padL + $plotW) . "," . ($padT + $plotH);

// Warna & ikon kategori terikat urutan kategori (bukan siklik) supaya konsisten & aman-buta warna.
$kategoriWarna = ['#2a78d6', '#4a3aa7', '#1baf7a'];
$kategoriIkon = ['fa-chart-column', 'fa-landmark', 'fa-arrow-trend-up'];
$totalSoalUntukDonut = max(1, (int) $totalSoal);
$donutStops = [];
$sudutMulai = 0;
foreach ($soalPerKategori as $i => $row) {
    $persen = round(($row['total'] / $totalSoalUntukDonut) * 100);
    $sudutAkhir = $sudutMulai + ($row['total'] / $totalSoalUntukDonut) * 360;
    $donutStops[] = sprintf('%s %.2fdeg %.2fdeg', $kategoriWarna[$i % 3], $sudutMulai, $sudutAkhir);
    $soalPerKategori[$i]['persen'] = $persen;
    $soalPerKategori[$i]['warna'] = $kategoriWarna[$i % 3];
    $soalPerKategori[$i]['ikon'] = $kategoriIkon[$i % 3];
    // Posisi label persen di tengah lingkaran donut (radius 32%), dari titik tengah slice.
    $sudutTengah = deg2rad($sudutMulai + ($sudutAkhir - $sudutMulai) / 2 - 90);
    $soalPerKategori[$i]['label_x'] = round(50 + cos($sudutTengah) * 32, 2);
    $soalPerKategori[$i]['label_y'] = round(50 + sin($sudutTengah) * 32, 2);
    $sudutMulai = $sudutAkhir;
}
$donutGradient = 'conic-gradient(' . implode(', ', $donutStops) . ')';

/* ===========================
   Total XP diberikan (quiz + materi) & tren mingguan
=========================== */

$xpDariQuiz = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(xp),0) t FROM hasil_quiz"))['t']);
$xpDariMateri = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(xp_diberikan),0) t FROM materi_progress"))['t']);
$totalXpDiberikan = $xpDariQuiz + $xpDariMateri;

$xpMingguIni = (int) (mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(xp),0) t FROM hasil_quiz WHERE tanggal >= NOW() - INTERVAL 7 DAY
"))['t']) + (int) (mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(xp_diberikan),0) t FROM materi_progress WHERE selesai_pada >= NOW() - INTERVAL 7 DAY
"))['t']);

$xpHarianResult = mysqli_query($conn, "
    SELECT DATE(tanggal) d, SUM(xp) t FROM hasil_quiz
    WHERE tanggal >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(tanggal)
");
$xpHarianMap = [];
while ($row = mysqli_fetch_assoc($xpHarianResult)) {
    $xpHarianMap[$row['d']] = (int) $row['t'];
}
$xpHarian = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $xpHarian[] = ['label' => $hariLabel[date('N', strtotime($tgl)) - 1], 'total' => $xpHarianMap[$tgl] ?? 0];
}

/* ===========================
   Progress per kategori: soal, quiz dikerjakan, porsi aktivitas
=========================== */

$quizPerKategoriResult = mysqli_query($conn, "
    SELECT kategori_id, COUNT(*) c FROM hasil_quiz GROUP BY kategori_id
");
$quizPerKategoriMap = [];
while ($row = mysqli_fetch_assoc($quizPerKategoriResult)) {
    $quizPerKategoriMap[$row['kategori_id']] = (int) $row['c'];
}
$totalQuizUntukProgress = max(1, (int) $totalAttempt);

foreach ($soalPerKategori as $i => $row) {
    $quizKat = $quizPerKategoriMap[$row['id']] ?? 0;
    $soalPerKategori[$i]['quiz_dikerjakan'] = $quizKat;
    $soalPerKategori[$i]['progress_persen'] = (int) round(($quizKat / $totalQuizUntukProgress) * 100);
}

/* ===========================
   Distribusi tingkat kesulitan soal (donut kedua)
=========================== */

$totalMudah = (int) array_sum(array_column($soalPerKategori, 'mudah'));
$totalSedang = (int) array_sum(array_column($soalPerKategori, 'sedang'));
$totalSulit = (int) array_sum(array_column($soalPerKategori, 'sulit'));

$levelData = [
    ['nama' => 'Mudah', 'total' => $totalMudah, 'warna' => '#1baf7a'],
    ['nama' => 'Sedang', 'total' => $totalSedang, 'warna' => '#eda100'],
    ['nama' => 'Sulit', 'total' => $totalSulit, 'warna' => '#e34948'],
];

$levelStops = [];
$sudutMulai = 0;
foreach ($levelData as $i => $row) {
    $persen = round(($row['total'] / $totalSoalUntukDonut) * 100);
    $sudutAkhir = $sudutMulai + ($row['total'] / $totalSoalUntukDonut) * 360;
    $levelStops[] = sprintf('%s %.2fdeg %.2fdeg', $row['warna'], $sudutMulai, $sudutAkhir);
    $sudutMulai = $sudutAkhir;
    $levelData[$i]['persen'] = $persen;
}
$levelGradient = 'conic-gradient(' . implode(', ', $levelStops) . ')';

/* ===========================
   Rata-rata skor pengguna (gauge)
=========================== */

$skorRow = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT AVG(score) rata, MAX(score) tertinggi, MIN(score) terendah FROM hasil_quiz
"));
$skorRata = $skorRow['rata'] !== null ? (int) round($skorRow['rata']) : 0;
$skorTertinggi = $skorRow['tertinggi'] !== null ? (int) $skorRow['tertinggi'] : 0;
$skorTerendah = $skorRow['terendah'] !== null ? (int) $skorRow['terendah'] : 0;

$skorLabel = 'Belum Ada Data';
if ($skorRow['rata'] !== null) {
    $skorLabel = $skorRata >= 80 ? 'Sangat Baik' : ($skorRata >= 60 ? 'Baik' : 'Perlu Ditingkatkan');
}

/* ===========================
   Performa minggu ini & aktivitas terbaru
=========================== */

$performaMingguan = getWeeklyPerformance($conn);
$aktivitasTerbaru = getRecentActivity($conn, 8);

// Gridline horizontal grafik garis (5 baris: 0..niceMax).
$gridLines = [];
for ($g = 0; $g <= 4; $g++) {
    $nilai = (int) round(($niceMax / 4) * $g);
    $y = round($padT + $plotH - ($nilai / $niceMax) * $plotH, 1);
    $gridLines[] = ['nilai' => $nilai, 'y' => $y];
}

$activeMenu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | StatQuest BPS</title>

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

<div class="admin-topbar admin-topbar-v2">

<div class="admin-topbar-greeting">
<h1>Selamat datang, <?= e($admin['nama']); ?>! <span class="wave">👋</span></h1>
<p>Kelola konten, pengguna, dan pantau perkembangan StatQuest BPS.</p>
</div>

<div class="admin-topbar-right">

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

<div class="admin-clock-widget">
<i class="fa-solid fa-calendar-days"></i>
<div>
<h4 id="adminTanggal">&nbsp;</h4>
<p id="adminJam">&nbsp;</p>
</div>
</div>

<div class="admin-notif-wrap" id="adminNotifWrap">

<button type="button" class="admin-notif-bell" id="adminNotifBell" aria-label="Notifikasi">
<i class="fa-solid fa-bell"></i>
<?php if (count($aktivitasTerbaru) > 0) { ?>
<span class="admin-notif-badge"><?= count($aktivitasTerbaru) > 9 ? '9+' : count($aktivitasTerbaru); ?></span>
<?php } ?>
</button>

<div class="admin-notif-dropdown" id="adminNotifDropdown">

<div class="admin-notif-dropdown-head">Notifikasi</div>

<div class="admin-notif-list">

<?php if (empty($aktivitasTerbaru)) { ?>

<div class="admin-notif-empty">Belum ada aktivitas.</div>

<?php } else { foreach ($aktivitasTerbaru as $a) { ?>

<div class="admin-notif-item">
<span class="admin-notif-icon <?= e($a['warna']); ?>"><i class="fa-solid <?= e($a['icon']); ?>"></i></span>
<div>
<p><?= e($a['judul']); ?></p>
<span><?= e($a['waktu_relatif']); ?></span>
</div>
</div>

<?php } } ?>

</div>

</div>

</div>

</div>

</div>

<div class="admin-stats admin-stats-v2">

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
<span class="stat-card-label">Total Pemain</span>
</div>
<h2><?= (int) $totalUsers; ?></h2>
<?php if ($usersMingguIni > 0) { ?>
<span class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> <?= $usersMingguIni; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend flat">Belum ada minggu ini</span>
<?php } ?>
</div>
<div class="stat-card-spark"><?= renderSparkline($userHarian, '#2a78d6'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon violet"><i class="fa-solid fa-layer-group"></i></div>
<span class="stat-card-label">Kategori Materi</span>
</div>
<h2><?= (int) $totalKategori; ?></h2>
<span class="stat-trend flat">Semua aktif</span>
</div>
<div class="stat-card-spark"><?= renderSparkline($kategoriHarian, '#4a3aa7'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon aqua"><i class="fa-solid fa-file-lines"></i></div>
<span class="stat-card-label">Total Soal</span>
</div>
<h2><?= (int) $totalSoal; ?></h2>
<?php if ($performaMingguan['quiz_dibuat'] > 0) { ?>
<span class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> <?= $performaMingguan['quiz_dibuat']; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend flat">Belum ada minggu ini</span>
<?php } ?>
</div>
<div class="stat-card-spark"><?= renderSparkline($soalHarian, '#1baf7a'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon orange"><i class="fa-solid fa-gamepad"></i></div>
<span class="stat-card-label">Quiz Dikerjakan</span>
</div>
<h2><?= (int) $totalAttempt; ?></h2>
<?php if ($quizMingguIni > 0) { ?>
<span class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> <?= $quizMingguIni; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend flat">Belum ada minggu ini</span>
<?php } ?>
</div>
<div class="stat-card-spark"><?= renderSparkline($quizHarian, '#eb6834'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon yellow"><i class="fa-solid fa-bolt"></i></div>
<span class="stat-card-label">Total XP Diberikan</span>
</div>
<h2><?= (int) $totalXpDiberikan; ?></h2>
<?php if ($xpMingguIni > 0) { ?>
<span class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> <?= $xpMingguIni; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend flat">Belum ada minggu ini</span>
<?php } ?>
</div>
<div class="stat-card-spark"><?= renderSparkline($xpHarian, '#eda100'); ?></div>
</div>

</div>

<div class="admin-panel performa-panel">

<div class="performa-head">
<div class="performa-icon"><i class="fa-solid fa-trophy"></i></div>
<div>
<h3>Performa Minggu Ini</h3>
<p>Terus tingkatkan kualitas quiz dan materi untuk mencetak generasi cerdas statistik Indonesia!</p>
</div>
</div>

<div class="performa-metrics">

<div class="performa-metric">
<span class="performa-metric-icon blue"><i class="fa-solid fa-plus"></i></span>
<h4><?= (int) $performaMingguan['quiz_dibuat']; ?></h4>
<p>Soal Dibuat</p>
</div>

<div class="performa-metric">
<span class="performa-metric-icon green"><i class="fa-solid fa-user-plus"></i></span>
<h4><?= (int) $performaMingguan['pengguna_baru']; ?></h4>
<p>Pengguna Baru</p>
</div>

<div class="performa-metric">
<span class="performa-metric-icon purple"><i class="fa-solid fa-bullseye"></i></span>
<h4><?= (int) $performaMingguan['rata_akurasi']; ?>%</h4>
<p>Rata-rata Akurasi</p>
</div>

<div class="performa-metric">
<span class="performa-metric-icon orange"><i class="fa-solid fa-star"></i></span>
<h4><?= (int) $performaMingguan['streak_terpanjang']; ?></h4>
<p>Streak Terpanjang</p>
</div>

</div>

</div>

<div class="chart-row chart-row-2">

<div class="admin-panel chart-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-chart-column"></i> Quiz Dikerjakan <span>(7 Hari Terakhir)</span></h2>
<span class="chart-range-pill">7 Hari</span>
</div>

<svg viewBox="0 0 <?= $chartW; ?> <?= $chartH; ?>" class="line-chart-svg" role="img" aria-label="Grafik quiz dikerjakan 7 hari terakhir">

<?php foreach ($gridLines as $gl) { ?>
<line x1="<?= $padL; ?>" y1="<?= $gl['y']; ?>" x2="<?= $padL + $plotW; ?>" y2="<?= $gl['y']; ?>" class="chart-grid"/>
<text x="<?= $padL - 10; ?>" y="<?= $gl['y'] + 4; ?>" class="chart-axis-label" text-anchor="end"><?= $gl['nilai']; ?></text>
<?php } ?>

<polyline points="<?= e($lineAreaPolyline); ?>" class="chart-area"/>
<polyline points="<?= e($linePolyline); ?>" class="chart-line"/>

<?php foreach ($linePoints as $p) { ?>
<text x="<?= $p['x']; ?>" y="<?= $p['y'] - 12; ?>" class="chart-value-label" text-anchor="middle"><?= $p['total']; ?></text>
<circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="4" class="chart-dot">
<title><?= e($p['label']); ?>: <?= $p['total']; ?> quiz</title>
</circle>
<circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="13" class="chart-dot-hit">
<title><?= e($p['label']); ?>: <?= $p['total']; ?> quiz</title>
</circle>
<text x="<?= $p['x']; ?>" y="<?= $padT + $plotH + 20; ?>" class="chart-axis-label" text-anchor="middle"><?= e($p['label']); ?></text>
<?php } ?>

</svg>

</div>

<div class="admin-panel donut-panel">

<h2><i class="fa-solid fa-gear"></i> Distribusi Soal per Kategori</h2>

<div class="donut-body">

<div class="donut-visual">
<div class="donut-ring" style="background:<?= $donutGradient; ?>">
<div class="donut-hole"></div>
<?php foreach ($soalPerKategori as $row) { if ($row['total'] <= 0) continue; ?>
<span class="donut-percent-label" style="left:<?= $row['label_x']; ?>%;top:<?= $row['label_y']; ?>%;"><?= $row['persen']; ?>%</span>
<?php } ?>
</div>
</div>

<ul class="donut-legend">
<?php foreach ($soalPerKategori as $row) { ?>
<li>
<span class="legend-dot" style="background:<?= $row['warna']; ?>"></span>
<span class="legend-name"><?= e($row['nama']); ?></span>
<span class="legend-value"><?= $row['total']; ?> soal</span>
</li>
<?php } ?>
</ul>

</div>

</div>

</div>

<div class="admin-panel progress-kategori-panel">

<div class="chart-panel-head">
<h2>Progress per Kategori</h2>
<a href="kategori.php" class="chart-link">Kelola Semua Kategori <i class="fa-solid fa-arrow-right"></i></a>
</div>

<div class="progress-kategori-list">

<?php foreach ($soalPerKategori as $row) { ?>

<div class="progress-kategori-card">
<div class="progress-kategori-top">
<span class="progress-kategori-icon" style="background:<?= $row['warna']; ?>"><i class="fa-solid <?= $row['ikon']; ?>"></i></span>
<b><?= e($row['nama']); ?></b>
</div>
<div class="progress-kategori-stats-row">
<div class="progress-kategori-stat">
<span>Soal</span>
<b><?= $row['total']; ?></b>
</div>
<div class="progress-kategori-stat">
<span>Quiz Dikerjakan</span>
<b><?= $row['quiz_dikerjakan']; ?></b>
</div>
</div>
<div class="progress-kategori-bar-track">
<div class="progress-kategori-bar-fill" style="width:<?= $row['progress_persen']; ?>%;background:<?= $row['warna']; ?>"></div>
</div>
<div class="progress-kategori-bottom">
<b style="color:<?= $row['warna']; ?>"><?= $row['progress_persen']; ?>%</b>
<span class="progress-kategori-badge"><i class="fa-solid fa-circle"></i> Aktif</span>
</div>
</div>

<?php } ?>

</div>

</div>

<div class="chart-row chart-row-3">

<div class="admin-panel table-panel">

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

<?php foreach ($soalPerKategori as $row) { ?>

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

<div class="admin-panel donut-panel">

<h2>Distribusi Tingkat Kesulitan Soal</h2>

<div class="donut-body donut-body-stack">

<div class="donut-visual donut-visual-sm">
<div class="donut-ring" style="background:<?= $levelGradient; ?>">
<div class="donut-hole"></div>
</div>
</div>

<ul class="donut-legend">
<?php foreach ($levelData as $row) { ?>
<li>
<span class="legend-dot" style="background:<?= $row['warna']; ?>"></span>
<span class="legend-name"><?= e($row['nama']); ?></span>
<span class="legend-value"><?= $row['total']; ?> soal (<?= $row['persen']; ?>%)</span>
</li>
<?php } ?>
</ul>

</div>

</div>

<div class="admin-panel gauge-panel">

<h2>Rata-rata Skor Pengguna</h2>

<div class="gauge-visual">
<div class="gauge-ring" style="background:conic-gradient(#2a78d6 <?= $skorRata; ?>%, #e1e0d9 <?= $skorRata; ?>%)">
<div class="gauge-hole">
<b><?= $skorRata; ?>%</b>
<span><?= e($skorLabel); ?></span>
</div>
</div>
</div>

<ul class="gauge-stats">
<li><span>Skor Tertinggi</span><b><?= $skorTertinggi; ?>%</b></li>
<li><span>Skor Terendah</span><b><?= $skorTerendah; ?>%</b></li>
<li><span>Total Rata-rata</span><b><?= $skorRata; ?>%</b></li>
</ul>

</div>

</div>

<div class="admin-panel aktivitas-panel">

<h2><i class="fa-solid fa-clock-rotate-left"></i> Aktivitas Terbaru</h2>

<?php if (empty($aktivitasTerbaru)) { ?>

<p class="aktivitas-kosong">Belum ada aktivitas tercatat.</p>

<?php } else { ?>

<div class="aktivitas-list">

<?php foreach (array_slice($aktivitasTerbaru, 0, 6) as $a) { ?>

<div class="aktivitas-item">
<span class="aktivitas-icon <?= e($a['warna']); ?>"><i class="fa-solid <?= e($a['icon']); ?>"></i></span>
<div class="aktivitas-text">
<p><?= e($a['judul']); ?></p>
<span><?= e($a['waktu_relatif']); ?></span>
</div>
</div>

<?php } ?>

</div>

<?php } ?>

</div>

<?php require __DIR__ . "/partials/footer.php"; ?>

</main>

</div>

<script>
(function(){

    var hariNama = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
    var bulanNama = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    var elTanggal = document.getElementById("adminTanggal");
    var elJam = document.getElementById("adminJam");

    function updateDateTime(){
        var now = new Date();
        var tanggal = hariNama[now.getDay()] + ", " + now.getDate() + " " + bulanNama[now.getMonth()] + " " + now.getFullYear();
        var jam = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit" });

        if (elTanggal) elTanggal.textContent = tanggal;
        if (elJam) elJam.textContent = jam + " WIB";
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);

    var notifWrap = document.getElementById("adminNotifWrap");
    var notifBell = document.getElementById("adminNotifBell");
    var notifDropdown = document.getElementById("adminNotifDropdown");

    if (notifBell && notifDropdown) {
        notifBell.addEventListener("click", function(e){
            e.stopPropagation();
            notifDropdown.classList.toggle("show");
        });

        document.addEventListener("click", function(e){
            if (!notifWrap.contains(e.target)) {
                notifDropdown.classList.remove("show");
            }
        });
    }

})();
</script>

</body>

</html>
