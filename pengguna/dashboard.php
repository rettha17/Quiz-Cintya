<?php
require __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

/**
 * Sparkline SVG mini-chart untuk kartu statistik (kurva halus + gradasi area).
 * $data: array angka harian.
 */
function renderSparklineUser($data, $color, $width = 160, $height = 52) {
    static $spCounter = 0;
    $n = count($data);
    if ($n < 2) return '';

    $max = max($data);
    $min = min($data);
    $range = max(1, $max - $min);
    $padY = 8;
    $stepX = $width / ($n - 1);
    $pts = [];

    foreach ($data as $i => $v) {
        $x = round($i * $stepX, 2);
        $y = round($padY + ($height - $padY * 2) * (1 - ($v - $min) / $range), 2);
        $pts[] = [$x, $y];
    }

    $path = "M {$pts[0][0]},{$pts[0][1]} ";
    for ($i = 1; $i < $n - 1; $i++) {
        $mx = round(($pts[$i][0] + $pts[$i + 1][0]) / 2, 2);
        $my = round(($pts[$i][1] + $pts[$i + 1][1]) / 2, 2);
        $path .= "Q {$pts[$i][0]},{$pts[$i][1]} {$mx},{$my} ";
    }
    $secondLast = $pts[$n - 2];
    $last = $pts[$n - 1];
    $path .= "Q {$secondLast[0]},{$secondLast[1]} {$last[0]},{$last[1]}";

    $areaPath = $path . " L {$width},{$height} L 0,{$height} Z";
    $gid = 'uSparkGrad' . (++$spCounter);

    return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="sparkline" preserveAspectRatio="none" aria-hidden="true">'
        . '<defs><linearGradient id="' . $gid . '" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0%" stop-color="' . $color . '" stop-opacity="0.35"/>'
        . '<stop offset="100%" stop-color="' . $color . '" stop-opacity="0"/>'
        . '</linearGradient></defs>'
        . '<path d="' . e($areaPath) . '" fill="url(#' . $gid . ')" stroke="none"/>'
        . '<path d="' . e($path) . '" fill="none" stroke="' . $color . '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
}

$progress = getProgress($conn, $user_id);
$persenSelesai = getPersenSelesai($progress);

$totalMateri = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];

$materiPerKategori = mysqli_query($conn, "
    SELECT k.nama, COUNT(m.id) AS total
    FROM kategori k
    LEFT JOIN materi m ON m.kategori_id = k.id
    GROUP BY k.id
    ORDER BY k.id
");

$searchData = [];
foreach ($progress as $kategoriId => $kat) {
    $searchData[] = [
        'id' => $kategoriId,
        'nama' => $kat['nama'],
        'deskripsi' => $kat['deskripsi'],
        'icon' => $kat['icon'],
    ];
}

$materiRows = mysqli_query($conn, "SELECT kategori_id, judul, isi FROM materi ORDER BY kategori_id, urutan, id");
while ($m = mysqli_fetch_assoc($materiRows)) {
    $kat = $progress[$m['kategori_id']] ?? null;
    $snippet = trim(strip_tags($m['isi']));
    if (mb_strlen($snippet) > 100) {
        $snippet = mb_substr($snippet, 0, 100) . '...';
    }

    $searchData[] = [
        'id' => $m['kategori_id'],
        'nama' => $m['judul'],
        'deskripsi' => $snippet,
        'icon' => $kat['icon'] ?? 'fa-book',
    ];
}

$totalQuizSemua = 0;
$totalQuizDikerjakan = 0;
foreach ($progress as $kat) {
    foreach ($kat['levels'] as $best) {
        $totalQuizSemua++;
        if ($best !== null) $totalQuizDikerjakan++;
    }
}
$totalQuizBelum = $totalQuizSemua - $totalQuizDikerjakan;

$badges = getBadges($user['xp']);
$totalBadge = count($badges);
$badgeCatalog = getBadgeCatalog();

/* ===========================
   Materi selesai (real) & progress per kategori (untuk kartu Materi Pembelajaran)
=========================== */

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM materi_progress WHERE user_id=? AND status='selesai'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$materiSelesaiCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

$totalMateriPerKategoriMap = [];
$res = mysqli_query($conn, "SELECT kategori_id, COUNT(*) c FROM materi GROUP BY kategori_id");
while ($row = mysqli_fetch_assoc($res)) {
    $totalMateriPerKategoriMap[$row['kategori_id']] = (int) $row['c'];
}

$selesaiPerKategoriMap = [];
$stmt = mysqli_prepare($conn, "
    SELECT m.kategori_id, COUNT(*) c
    FROM materi_progress mp
    JOIN materi m ON mp.materi_id = m.id
    WHERE mp.user_id=? AND mp.status='selesai'
    GROUP BY m.kategori_id
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $selesaiPerKategoriMap[$row['kategori_id']] = (int) $row['c'];
}

$kategoriWarnaUser = ['#1565C0', '#1baf7a', '#eb6834'];

// Banner ilustrasi kartu kategori (bukan lagi kotak ikon kecil)
$kategoriBannerKartu = [
    'Statistika Dasar' => 'qc-illus-statistika.jpg',
    'Pengenalan BPS' => 'qc-illus-bps.jpg',
    'Indikator Statistik' => 'qc-illus-indikator.jpg',
];
$progressKategoriCard = [];
$idx = 0;
foreach ($progress as $kategoriId => $kat) {
    $totalMateriKat = $totalMateriPerKategoriMap[$kategoriId] ?? 0;
    $selesaiKat = $selesaiPerKategoriMap[$kategoriId] ?? 0;
    $persenKat = $totalMateriKat > 0 ? (int) round(($selesaiKat / $totalMateriKat) * 100) : 0;
    $bintang = round(($persenKat / 100) * 5 * 2) / 2;

    $progressKategoriCard[] = [
        'id' => $kategoriId,
        'nama' => $kat['nama'],
        'icon' => $kat['icon'],
        'deskripsi' => $kat['deskripsi'],
        'warna' => $kategoriWarnaUser[$idx % 3],
        'total' => $totalMateriKat,
        'selesai' => $selesaiKat,
        'persen' => $persenKat,
        'bintang' => $bintang,
    ];
    $idx++;
}

/* ===========================
   Streak pengguna (real, konsisten hari demi hari)
=========================== */

$streakUser = getUserStreak($conn, $user_id);

/* ===========================
   Aktivitas terbaru (3 terakhir)
=========================== */

$stmt = mysqli_prepare($conn, "
    SELECT h.tanggal, h.score, h.xp, k.nama AS kategori, h.level
    FROM hasil_quiz h
    JOIN kategori k ON h.kategori_id = k.id
    WHERE h.user_id = ?
    ORDER BY h.tanggal DESC
    LIMIT 3
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$aktivitas = mysqli_stmt_get_result($stmt);

/* ===========================
   Data harian 7 hari terakhir (XP, quiz, materi) untuk grafik & sparkline kartu
=========================== */

$xpQuizPerTanggal = [];
$stmt = mysqli_prepare($conn, "
    SELECT DATE(tanggal) AS tgl, SUM(xp) AS total_xp, COUNT(*) AS total_c
    FROM hasil_quiz
    WHERE user_id = ? AND tanggal >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(tanggal)
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$quizPerTanggal = [];
while ($row = mysqli_fetch_assoc($res)) {
    $xpQuizPerTanggal[$row['tgl']] = (int) $row['total_xp'];
    $quizPerTanggal[$row['tgl']] = (int) $row['total_c'];
}

$xpMateriPerTanggal = [];
$materiPerTanggal = [];
$stmt = mysqli_prepare($conn, "
    SELECT DATE(selesai_pada) AS tgl, SUM(xp_diberikan) AS total_xp, COUNT(*) AS total_c
    FROM materi_progress
    WHERE user_id = ? AND status='selesai' AND selesai_pada >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(selesai_pada)
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $xpMateriPerTanggal[$row['tgl']] = (int) $row['total_xp'];
    $materiPerTanggal[$row['tgl']] = (int) $row['total_c'];
}

$labelHari = [];
$dataXp = [];
$dataQuizHarian = [];
$dataMateriHarian = [];
$dataStreakHarian = [];
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHari[] = $namaHari[date('w', strtotime($tgl))];
    $xpHariIni = ($xpQuizPerTanggal[$tgl] ?? 0) + ($xpMateriPerTanggal[$tgl] ?? 0);
    $dataXp[] = $xpHariIni;
    $dataQuizHarian[] = $quizPerTanggal[$tgl] ?? 0;
    $dataMateriHarian[] = $materiPerTanggal[$tgl] ?? 0;
    $dataStreakHarian[] = (($quizPerTanggal[$tgl] ?? 0) > 0 || ($materiPerTanggal[$tgl] ?? 0) > 0) ? 1 : 0;
}

$xpMingguIni = 0;
$quizMingguIni = 0;
$materiMingguIni = 0;
foreach ($xpQuizPerTanggal as $v) { $xpMingguIni += $v; }
foreach ($xpMateriPerTanggal as $v) { $xpMingguIni += $v; }
foreach ($quizPerTanggal as $v) { $quizMingguIni += $v; }
foreach ($materiPerTanggal as $v) { $materiMingguIni += $v; }

/* ===========================
   Geometri SVG grafik garis "Grafik Progress Belajar"
=========================== */

$chartW = 640; $chartH = 220; $padL = 40; $padR = 14; $padT = 28; $padB = 30;
$plotW = $chartW - $padL - $padR;
$plotH = $chartH - $padT - $padB;

$xpMax = max(4, max($dataXp));
$niceMax = (int) (ceil(($xpMax * 1.15) / 100) * 100);
if ($niceMax <= 0) $niceMax = 100;

$lineN = count($dataXp);
$stepXChart = $lineN > 1 ? $plotW / ($lineN - 1) : 0;

$linePoints = [];
foreach ($dataXp as $i => $v) {
    $x = round($padL + $i * $stepXChart, 2);
    $y = round($padT + $plotH - ($v / $niceMax) * $plotH, 2);
    $linePoints[] = ['x' => $x, 'y' => $y, 'total' => $v, 'label' => $labelHari[$i]];
}
$linePolyline = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $linePoints));
$lineAreaPolyline = "{$padL}," . ($padT + $plotH) . " " . $linePolyline . " " . ($padL + $plotW) . "," . ($padT + $plotH);

$gridLines = [];
for ($g = 0; $g <= 4; $g++) {
    $nilai = (int) round($niceMax * $g / 4);
    $y = round($padT + $plotH - ($nilai / $niceMax) * $plotH, 2);
    $gridLines[] = ['y' => $y, 'nilai' => $nilai];
}

$jam = (int) date('G');

if ($jam < 12) {
    $sapaan = 'Selamat Pagi';
} elseif ($jam < 15) {
    $sapaan = 'Selamat Siang';
} elseif ($jam < 18) {
    $sapaan = 'Selamat Sore';
} else {
    $sapaan = 'Selamat Malam';
}

$activeMenu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard | StatQuest BPS</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<?php require __DIR__ . "/../inc/nav.php"; ?>

<!-- ================= CONTENT ================= -->

<main class="content">

<div class="page-watermark">
<div class="wm-inner">
<img src="assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<header class="topbar">

<div class="search-box">

<input
type="text"
id="searchInput"
placeholder="Cari Materi atau Quiz..."
autocomplete="off">

<i class="fa-solid fa-magnifying-glass"></i>

<div class="search-results" id="searchResults"></div>

</div>

<div class="top-menu">

<i class="fa-regular fa-bell"></i>

<i class="fa-regular fa-envelope"></i>

<div class="user-clock-widget">
<i class="fa-solid fa-calendar-days"></i>
<div>
<h4 id="userTanggal">&nbsp;</h4>
<p id="userJam">&nbsp;</p>
</div>
</div>

<div class="mini-profile" id="miniProfileWrap">

<img src="assets/img/<?= !empty($user['foto']) ? e($user['foto']) : 'default.png'; ?>">

<div>

<h4><?= e($user['nama']); ?></h4>

<span>Level <?= e($user['level']); ?></span>

</div>

<i class="fa-solid fa-chevron-down mini-profile-caret"></i>

<div class="profile-dropdown mini-profile-dropdown" id="miniProfileDropdown">

<a href="profil.php"><i class="fa-solid fa-user"></i> Lihat Profil</a>

<a href="edit_profil.php"><i class="fa-solid fa-user-pen"></i> Edit Profil</a>

</div>

</div>

</div>

</header>

<!-- ================= GREETING / HERO ================= -->

<div class="dashboard-greeting dash-hero">

<div class="dash-hero-text">

<h1>

<?= $sapaan; ?>,

<?= e($user['nama']); ?>

👋

</h1>

<p>

Selamat datang di

<b>StatQuest BPS : Jelajah Statistik Indonesia</b>

</p>

<div class="dash-hero-actions">
<a href="materi.php" class="btn-hero-primary"><i class="fa-solid fa-play"></i> Mulai Belajar</a>
<a href="kategori.php" class="btn-hero-secondary"><i class="fa-solid fa-gamepad"></i> Lanjut Quiz</a>
</div>

</div>

<div class="dash-hero-mascot">
<img src="assets/img/hero-girl-mascot.jpg" alt="Ilustrasi belajar statistik StatQuest BPS">
</div>

</div>

<!-- ================= KARTU STATISTIK ================= -->

<section class="dash-stats">

<div class="stat-card-u" style="background:#7C3AED1A">
<div class="stat-card-top-u">
<div class="stat-card-icon-u purple"><i class="fa-solid fa-star"></i></div>
<span class="stat-card-label-u">XP Saya</span>
</div>
<h2><?= (int) $user['xp']; ?></h2>
<?php if ($xpMingguIni > 0) { ?>
<span class="stat-trend-u up"><i class="fa-solid fa-arrow-up"></i> <?= $xpMingguIni; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend-u flat">Belum ada minggu ini</span>
<?php } ?>
<div class="stat-card-spark-u"><?= renderSparklineUser($dataXp, '#7C3AED'); ?></div>
</div>

<div class="stat-card-u" style="background:#1565C01A">
<div class="stat-card-icon-u blue"><i class="fa-solid fa-book"></i></div>
<p class="stat-card-label-u">Materi Selesai</p>
<h2><?= $materiSelesaiCount; ?> / <?= (int) $totalMateri; ?></h2>
<span class="stat-trend-u flat blue-text"><?= $totalMateri > 0 ? (int) round(($materiSelesaiCount / $totalMateri) * 100) : 0; ?>%</span>
<div class="stat-card-spark-u"><?= renderSparklineUser($dataMateriHarian, '#1565C0'); ?></div>
</div>

<div class="stat-card-u" style="background:#1baf7a1A">
<div class="stat-card-icon-u green"><i class="fa-solid fa-bullseye"></i></div>
<p class="stat-card-label-u">Quiz Selesai</p>
<h2><?= $totalQuizDikerjakan; ?></h2>
<?php if ($quizMingguIni > 0) { ?>
<span class="stat-trend-u up"><i class="fa-solid fa-arrow-up"></i> <?= $quizMingguIni; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend-u flat">Belum ada minggu ini</span>
<?php } ?>
<div class="stat-card-spark-u"><?= renderSparklineUser($dataQuizHarian, '#1baf7a'); ?></div>
</div>

<div class="stat-card-u" style="background:#eb68341A">
<div class="stat-card-icon-u orange"><i class="fa-solid fa-fire"></i></div>
<p class="stat-card-label-u">Streak</p>
<h2><?= $streakUser; ?> Hari</h2>
<?php if ($streakUser > 0) { ?>
<span class="stat-trend-u badge-orange">Pertahankan!</span>
<?php } else { ?>
<span class="stat-trend-u flat">Mulai hari ini!</span>
<?php } ?>
<div class="stat-card-spark-u"><?= renderSparklineUser($dataStreakHarian, '#eb6834'); ?></div>
</div>

</section>

<!-- ================= GRAFIK + PROGRESS MATERI ================= -->

<section class="dash-chart-row">

<div class="dash-panel dash-chart-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-chart-line"></i> Grafik Progress Belajar <span>(XP 7 Hari Terakhir)</span></h2>
<span class="chart-range-pill">7 Hari</span>
</div>

<svg viewBox="0 0 <?= $chartW; ?> <?= $chartH; ?>" class="line-chart-svg" role="img" aria-label="Grafik XP 7 hari terakhir">

<?php foreach ($gridLines as $gl) { ?>
<line x1="<?= $padL; ?>" y1="<?= $gl['y']; ?>" x2="<?= $padL + $plotW; ?>" y2="<?= $gl['y']; ?>" class="chart-grid"/>
<text x="<?= $padL - 10; ?>" y="<?= $gl['y'] + 4; ?>" class="chart-axis-label" text-anchor="end"><?= $gl['nilai']; ?></text>
<?php } ?>

<polyline points="<?= e($lineAreaPolyline); ?>" class="chart-area-fill"/>
<polyline points="<?= e($linePolyline); ?>" class="chart-line-u"/>

<?php foreach ($linePoints as $p) { ?>
<text x="<?= $p['x']; ?>" y="<?= $p['y'] - 12; ?>" class="chart-value-label" text-anchor="middle"><?= $p['total']; ?></text>
<circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="4" class="chart-dot-u">
<title><?= e($p['label']); ?>: <?= $p['total']; ?> XP</title>
</circle>
<circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="13" class="chart-dot-hit">
<title><?= e($p['label']); ?>: <?= $p['total']; ?> XP</title>
</circle>
<text x="<?= $p['x']; ?>" y="<?= $padT + $plotH + 20; ?>" class="chart-axis-label" text-anchor="middle"><?= e($p['label']); ?></text>
<?php } ?>

</svg>

</div>

<div class="dash-panel dash-progress-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-chart-pie"></i> Progress Materi Dikuasai</h2>
</div>

<div class="dash-progress-body">

<div class="dash-progress-ring" style="background:conic-gradient(#1565C0 0% <?= $persenSelesai; ?>%, #E3EBFB <?= $persenSelesai; ?>% 100%)">
<div class="dash-progress-hole"><span><?= $persenSelesai; ?>%</span></div>
</div>

<div class="dash-progress-info">
<b>Materi Dikuasai</b>
<div class="dash-progress-bar-track">
<div class="dash-progress-bar-fill" style="width:<?= $persenSelesai; ?>%"></div>
</div>
<span class="dash-progress-percent"><?= $persenSelesai; ?>%</span>
<p>Terus tingkatkan belajarmu dan selesaikan semua materi!</p>
<a href="materi.php" class="btn-lihat-materi">Lihat Semua Materi <i class="fa-solid fa-arrow-right"></i></a>
</div>

</div>

</div>

</section>

<!-- ================= MATERI PEMBELAJARAN + ACHIEVEMENT ================= -->

<section class="dash-chart-row dash-bottom-row">

<div class="dash-panel dash-materi-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-book-open"></i> Materi Pembelajaran</h2>
<a href="materi.php" class="chart-link">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
</div>

<div class="dash-materi-list">

<?php foreach ($progressKategoriCard as $kat) { ?>
<div class="dash-materi-card" style="background:<?= $kat['warna']; ?>1A">
<?php if (!empty($kategoriBannerKartu[$kat['nama']])): ?>
<div class="dash-materi-banner">
<img src="assets/img/<?= e($kategoriBannerKartu[$kat['nama']]); ?>" alt="<?= e($kat['nama']); ?>">
</div>
<?php endif; ?>
<div class="dash-materi-top">
<b><?= e($kat['nama']); ?></b>
</div>
<div class="dash-materi-progress-row">
<span>Progres</span>
<b style="color:<?= $kat['warna']; ?>"><?= $kat['persen']; ?>%</b>
</div>
<div class="dash-materi-bar-track">
<div class="dash-materi-bar-fill" style="width:<?= $kat['persen']; ?>%;background:<?= $kat['warna']; ?>"></div>
</div>
<div class="dash-materi-stars">
<?php
$penuh = (int) floor($kat['bintang']);
$setengah = ($kat['bintang'] - $penuh) >= 0.5;
for ($s = 0; $s < 5; $s++) {
    if ($s < $penuh) echo '<i class="fa-solid fa-star"></i>';
    elseif ($s == $penuh && $setengah) echo '<i class="fa-solid fa-star-half-stroke"></i>';
    else echo '<i class="fa-regular fa-star"></i>';
}
?>
<span class="dash-materi-stars-text"><?= $kat['selesai']; ?>/<?= $kat['total']; ?> topik</span>
</div>
<a href="materi.php#materi-<?= $kat['id']; ?>" class="btn-lanjut-belajar" style="background:<?= $kat['warna']; ?>">Lanjut Belajar</a>
<?php if (!empty($kat['deskripsi'])) { ?>
<div class="dash-materi-desc" style="background:<?= $kat['warna']; ?>14"><?= e($kat['deskripsi']); ?></div>
<?php } ?>
</div>
<?php } ?>

</div>

</div>

<div class="dash-panel dash-achievement-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-trophy"></i> Achievement Terbaru</h2>
<a href="achievement.php" class="chart-link">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
</div>

<div class="dash-achievement-grid">
<?php foreach ($badgeCatalog as $b):
    $terbuka = $user['xp'] >= $b['min_xp'];
?>
<div class="dash-badge <?= $terbuka ? 'unlocked' : 'locked'; ?>">
<div class="dash-badge-icon" style="<?= $terbuka ? 'background:' . $b['warna'] . ';' : ''; ?>"><?= $terbuka ? $b['icon'] : '<i class="fa-solid fa-lock"></i>'; ?></div>
<?php if ($terbuka) { ?>
<b><?= e($b['nama']); ?></b>
<span><?= e($b['deskripsi']); ?></span>
<?php } else { ?>
<b>Terkunci</b>
<span><?= (int) $b['min_xp']; ?> XP</span>
<?php } ?>
</div>
<?php endforeach; ?>
</div>

</div>

</section>

<!-- ================= AKTIVITAS ================= -->

<section class="activity">

<div class="section-title">

<h2>Aktivitas Terbaru</h2>

</div>

<table>

<thead>

<tr>

<th>Tanggal</th>

<th>Materi</th>

<th>Status</th>

<th>XP</th>

</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($aktivitas) == 0): ?>

<tr>
<td colspan="4" style="text-align:center;">Belum ada aktivitas. Yuk mulai quiz pertamamu!</td>
</tr>

<?php else: while ($row = mysqli_fetch_assoc($aktivitas)):

    if ($row['score'] >= 70) { $statusClass = "success"; $statusText = "Selesai"; }
    elseif ($row['score'] >= 40) { $statusClass = "process"; $statusText = "Cukup"; }
    else { $statusClass = "pending"; $statusText = "Perlu Diulang"; }
?>

<tr>

<td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>

<td><?= e($row['kategori']); ?> (<?= e($row['level']); ?>)</td>

<td>

<span class="<?= $statusClass; ?>">

<?= $statusText; ?>

</span>

</td>

<td>

+<?= (int) $row['xp']; ?> XP

</td>

</tr>

<?php endwhile; endif; ?>

</tbody>

</table>

</section>

<?php require __DIR__ . "/../inc/footer.php"; ?>

</main>

</div>

<script>

(function(){
    var hariNama = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
    var bulanNama = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    var elTanggal = document.getElementById("userTanggal");
    var elJam = document.getElementById("userJam");

    function updateDateTime(){
        var now = new Date();
        var tanggal = hariNama[now.getDay()] + ", " + now.getDate() + " " + bulanNama[now.getMonth()] + " " + now.getFullYear();
        var jam = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit" });

        if (elTanggal) elTanggal.textContent = tanggal;
        if (elJam) elJam.textContent = jam + " WIB";
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
})();

function setupProfileDropdown(triggerId, dropdownId) {

    const trigger = document.getElementById(triggerId);
    const dropdown = document.getElementById(dropdownId);

    if (!trigger || !dropdown) return;

    trigger.addEventListener("click", function (e) {
        e.stopPropagation();

        document.querySelectorAll(".profile-dropdown.show").forEach(function (el) {
            if (el !== dropdown) el.classList.remove("show");
        });

        dropdown.classList.toggle("show");
    });

}

setupProfileDropdown("miniProfileWrap", "miniProfileDropdown");

document.addEventListener("click", function () {
    document.querySelectorAll(".profile-dropdown.show").forEach(function (el) {
        el.classList.remove("show");
    });
});

/* ===========================
   Pencarian Materi/Kategori
=========================== */

const searchData = <?= json_encode($searchData); ?>;
const searchInput = document.getElementById("searchInput");
const searchResults = document.getElementById("searchResults");

function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}

if (searchInput && searchResults) {

    searchInput.addEventListener("input", function () {

        const keyword = this.value.trim().toLowerCase();

        if (keyword === "") {
            searchResults.classList.remove("show");
            searchResults.innerHTML = "";
            return;
        }

        const hasil = searchData.filter(function (k) {
            return k.nama.toLowerCase().includes(keyword) || k.deskripsi.toLowerCase().includes(keyword);
        });

        if (hasil.length === 0) {

            searchResults.innerHTML = '<div class="search-empty">Tidak ditemukan hasil untuk "' + escapeHtml(searchInput.value) + '"</div>';

        } else {

            searchResults.innerHTML = hasil.map(function (k) {
                return '<a href="materi.php#materi-' + k.id + '" class="search-result-item">' +
                    '<i class="fa-solid ' + k.icon + '"></i>' +
                    '<div><h5>' + escapeHtml(k.nama) + '</h5><p>' + escapeHtml(k.deskripsi) + '</p></div>' +
                    '</a>';
            }).join("");

        }

        searchResults.classList.add("show");

    });

    searchInput.addEventListener("focus", function () {
        if (this.value.trim() !== "") searchResults.classList.add("show");
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".search-box")) {
            searchResults.classList.remove("show");
        }
    });

}

</script>

<script src="assets/js/script.js?v=<?= filemtime(__DIR__ . '/assets/js/script.js'); ?>"></script>

</body>

</html>
