<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

/**
 * Sparkline/line-chart SVG geometri dipakai ulang dari dashboard.php supaya konsisten.
 */
function buildLineChartGeometry($data, $labels, $chartW = 640, $chartH = 220) {
    $padL = 40; $padR = 14; $padT = 28; $padB = 30;
    $plotW = $chartW - $padL - $padR;
    $plotH = $chartH - $padT - $padB;

    $max = max(4, max($data));
    $niceMax = (int) (ceil(($max * 1.15) / 100) * 100);
    if ($niceMax <= 0) $niceMax = 100;

    $n = count($data);
    $stepX = $n > 1 ? $plotW / ($n - 1) : 0;

    $points = [];
    foreach ($data as $i => $v) {
        $x = round($padL + $i * $stepX, 2);
        $y = round($padT + $plotH - ($v / $niceMax) * $plotH, 2);
        $points[] = ['x' => $x, 'y' => $y, 'total' => $v, 'label' => $labels[$i]];
    }
    $polyline = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $points));
    $areaPolyline = "{$padL}," . ($padT + $plotH) . " " . $polyline . " " . ($padL + $plotW) . "," . ($padT + $plotH);

    $gridLines = [];
    for ($g = 0; $g <= 4; $g++) {
        $nilai = (int) round($niceMax * $g / 4);
        $y = round($padT + $plotH - ($nilai / $niceMax) * $plotH, 2);
        $gridLines[] = ['y' => $y, 'nilai' => $nilai];
    }

    return compact('chartW', 'chartH', 'padL', 'padR', 'padT', 'padB', 'plotW', 'plotH', 'points', 'polyline', 'areaPolyline', 'gridLines');
}

$progress = getProgress($conn, $user_id);
$persenSelesai = getPersenSelesai($progress);
$rekomendasi = getRekomendasi($conn, $user_id, 70, 5);
$soalStats = getSoalStats($conn);

/* ===========================
   Materi selesai & progress per kategori
=========================== */

$totalMateri = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];

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

$xpPerKategoriMap = [];
$stmt = mysqli_prepare($conn, "SELECT kategori_id, SUM(xp) s FROM hasil_quiz WHERE user_id=? GROUP BY kategori_id");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $xpPerKategoriMap[$row['kategori_id']] = (int) $row['s'];
}

$kategoriWarnaUser = ['#1565C0', '#1baf7a', '#eb6834'];
$kategoriBannerKartu = [
    'Statistika Dasar' => 'qc-illus-statistika.jpg',
    'Pengenalan BPS' => 'qc-illus-bps.jpg',
    'Indikator Statistik' => 'qc-illus-indikator.jpg',
];

$totalQuizSemua = 0;
$totalQuizDikerjakan = 0;
$targetBerikutnya = null;

$kategoriProgressCard = [];
$idx = 0;
foreach ($progress as $kategoriId => $kat) {
    $totalMateriKat = $totalMateriPerKategoriMap[$kategoriId] ?? 0;
    $selesaiKat = $selesaiPerKategoriMap[$kategoriId] ?? 0;

    $levelSelesai = 0;
    foreach ($kat['levels'] as $lvl => $best) {
        $totalQuizSemua++;
        if ($best !== null) $totalQuizDikerjakan++;
        if ($best !== null && $best >= 70) $levelSelesai++;

        if ($targetBerikutnya === null && ($best === null || $best < 70)) {
            $soalCount = $soalStats['kategori'][$kategoriId]['soal_per_level'][$lvl] ?? 0;
            $targetBerikutnya = [
                'kategori_id' => $kategoriId,
                'kategori_nama' => $kat['nama'],
                'level' => $lvl,
                'reward_xp' => $soalCount * 10,
            ];
        }
    }

    $kategoriProgressCard[] = [
        'id' => $kategoriId,
        'nama' => $kat['nama'],
        'icon' => $kat['icon'],
        'warna' => $kategoriWarnaUser[$idx % 3],
        'levels' => $kat['levels'],
        'persen' => round(($levelSelesai / 3) * 100),
        'total_xp' => $xpPerKategoriMap[$kategoriId] ?? 0,
    ];
    $idx++;
}
$totalQuizBelum = $totalQuizSemua - $totalQuizDikerjakan;

if ($targetBerikutnya !== null) {
    $xpPotensial = (int) $user['xp'] + $targetBerikutnya['reward_xp'];
    foreach (getBadgeCatalog() as $b) {
        if ($user['xp'] < $b['min_xp'] && $xpPotensial >= $b['min_xp']) {
            $targetBerikutnya['reward_badge'] = true;
            break;
        }
    }
}

/* ===========================
   Rata-rata skor quiz & streak
=========================== */

$stmt = mysqli_prepare($conn, "SELECT AVG(score) a FROM hasil_quiz WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$rataSkorRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['a'];
$rataSkor = $rataSkorRow !== null ? (int) round($rataSkorRow) : 0;

$streakUser = getUserStreak($conn, $user_id);

/* ===========================
   Badge & level
=========================== */

$badgeCatalog = getBadgeCatalog();
$badges = getBadges($user['xp']);
$totalBadge = count($badges);

$levelSaatIni = (int) $user['level'];
$xpDalamLevel = (int) $user['xp'] % 100;
$xpButuhLagi = 100 - $xpDalamLevel;
$xpKebutuhanLevel = $levelSaatIni * 100;

/* ===========================
   XP mingguan (7 hari terakhir) untuk grafik
=========================== */

$xpQuizPerTanggal = [];
$stmt = mysqli_prepare($conn, "
    SELECT DATE(tanggal) AS tgl, SUM(xp) AS total_xp
    FROM hasil_quiz
    WHERE user_id = ? AND tanggal >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(tanggal)
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $xpQuizPerTanggal[$row['tgl']] = (int) $row['total_xp'];
}

$xpMateriPerTanggal = [];
$stmt = mysqli_prepare($conn, "
    SELECT DATE(selesai_pada) AS tgl, SUM(xp_diberikan) AS total_xp
    FROM materi_progress
    WHERE user_id = ? AND status='selesai' AND selesai_pada >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(selesai_pada)
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $xpMateriPerTanggal[$row['tgl']] = (int) $row['total_xp'];
}

$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$labelHari = [];
$dataXpMingguan = [];
$xpMingguIni = 0;
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHari[] = $namaHari[date('w', strtotime($tgl))];
    $xpHariIni = ($xpQuizPerTanggal[$tgl] ?? 0) + ($xpMateriPerTanggal[$tgl] ?? 0);
    $dataXpMingguan[] = $xpHariIni;
    $xpMingguIni += $xpHariIni;
}

$chart = buildLineChartGeometry($dataXpMingguan, $labelHari);

/* ===========================
   Riwayat aktivitas (real, per user)
=========================== */

$stmt = mysqli_prepare($conn, "
    SELECT tipe, judul, icon, warna, dibuat_pada
    FROM activity_log
    WHERE user_id = ? AND tipe IN ('quiz_selesai', 'level_up', 'badge_earned', 'materi_selesai')
    ORDER BY dibuat_pada DESC
    LIMIT 6
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$riwayatAktivitas = [];
while ($row = mysqli_fetch_assoc($res)) {
    $row['waktu_relatif'] = waktuRelatif($row['dibuat_pada']);
    $riwayatAktivitas[] = $row;
}

$activeMenu = 'progress';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Progress Belajar | StatQuest BPS</title>

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

<div class="section-title">
<div>
<h2>Progress Belajar 🎉</h2>
<p class="page-subtitle">Terus semangat! Kamu sedang berada di jalur yang tepat.</p>
</div>
</div>

<!-- ================= HERO: TOTAL MATERI DIKUASAI ================= -->

<div class="progress-hero-card">
<div class="progress-hero-text">
<h3>Total Materi Dikuasai</h3>
<div class="progress-bar">
<div class="progress-fill" style="width:<?= $persenSelesai; ?>%"></div>
</div>
<small><?= $persenSelesai; ?>% dari seluruh kategori &amp; level selesai dicoba</small>
</div>
<div class="progress-hero-trophy">
<img src="assets/img/sidebar-mascot-trophy.png" alt="">
</div>
</div>

<!-- ================= KARTU STATISTIK ================= -->

<section class="dash-stats">

<div class="stat-card-u" style="background:#1565C01A;border-top-color:#1565C0">
<div class="stat-card-icon-u blue"><i class="fa-solid fa-book"></i></div>
<p class="stat-card-label-u">Materi Diselesaikan</p>
<h2><?= $materiSelesaiCount; ?> / <?= (int) $totalMateri; ?></h2>
<span class="stat-trend-u flat blue-text"><?= $totalMateri > 0 ? (int) round(($materiSelesaiCount / $totalMateri) * 100) : 0; ?>%</span>
</div>

<div class="stat-card-u" style="background:#1baf7a1A;border-top-color:#1baf7a">
<div class="stat-card-icon-u green"><i class="fa-solid fa-chart-simple"></i></div>
<p class="stat-card-label-u">Rata-rata Skor Quiz</p>
<h2><?= $rataSkor; ?>%</h2>
</div>

<div class="stat-card-u" style="background:#7C3AED1A;border-top-color:#7C3AED">
<div class="stat-card-icon-u purple"><i class="fa-solid fa-star"></i></div>
<p class="stat-card-label-u">Total XP</p>
<h2><?= (int) $user['xp']; ?></h2>
</div>

<div class="stat-card-u" style="background:#eb68341A;border-top-color:#eb6834">
<div class="stat-card-icon-u orange"><i class="fa-solid fa-fire"></i></div>
<p class="stat-card-label-u">Streak Saat Ini</p>
<h2><?= $streakUser; ?> Hari</h2>
</div>

</section>

<!-- ================= RINGKASAN PROGRESS + TARGET BERIKUTNYA ================= -->

<section class="dash-chart-row">

<div class="dash-panel dash-progress-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-chart-pie"></i> Ringkasan Progress</h2>
</div>

<div class="dash-progress-body">

<div class="dash-progress-ring" style="background:conic-gradient(#1565C0 0% <?= $persenSelesai; ?>%, #E3EBFB <?= $persenSelesai; ?>% 100%)">
<div class="dash-progress-hole"><span><?= $persenSelesai; ?>%</span></div>
</div>

<div class="dash-progress-info">
<b>Total Selesai</b>
<ul class="donut-legend">
<li><span class="legend-dot" style="background:#1565C0"></span><span class="legend-name">Materi Selesai</span><span class="legend-value"><?= $totalQuizDikerjakan; ?> (<?= $persenSelesai; ?>%)</span></li>
<li><span class="legend-dot" style="background:#E3EBFB"></span><span class="legend-name">Belum Dicoba / Belum Selesai</span><span class="legend-value"><?= $totalQuizBelum; ?> (<?= 100 - $persenSelesai; ?>%)</span></li>
</ul>
</div>

</div>

</div>

<div class="dash-panel target-berikutnya-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-bullseye"></i> Target Berikutnya</h2>
</div>

<?php if ($targetBerikutnya !== null): ?>
<div class="target-next-body">
<div class="target-next-icon"><i class="fa-solid fa-bullseye"></i></div>
<div class="target-next-info">
<span class="target-next-label">Selesaikan</span>
<b><?= e($targetBerikutnya['kategori_nama']); ?></b>
<span class="badge <?= strtolower($targetBerikutnya['level']); ?>"><?= e($targetBerikutnya['level']); ?></span>
</div>
</div>
<div class="target-next-reward">
<span>Reward:</span>
<b><i class="fa-solid fa-star"></i> +<?= $targetBerikutnya['reward_xp']; ?> XP</b>
<?php if (!empty($targetBerikutnya['reward_badge'])): ?>
<b><i class="fa-solid fa-award"></i> +1 Badge</b>
<?php endif; ?>
</div>
<a href="quiz.php?kategori=<?= $targetBerikutnya['kategori_id']; ?>&level=<?= urlencode($targetBerikutnya['level']); ?>" class="btn-lanjut-belajar-full">Lanjut Belajar</a>
<?php else: ?>
<div class="target-next-selesai">
<i class="fa-solid fa-circle-check"></i>
<p>Semua level sudah kamu selesaikan. Mantap!</p>
</div>
<?php endif; ?>

</div>

</section>

<!-- ================= PROGRESS PER KATEGORI ================= -->

<div class="section-title">
<h2>Progress per Kategori</h2>
</div>

<div class="dash-materi-list progress-kategori-list">

<?php foreach ($kategoriProgressCard as $kat): ?>
<div class="dash-materi-card progress-kategori-card" style="border-top-color:<?= $kat['warna']; ?>">

<div class="progress-kategori-head">
<?php if (!empty($kategoriBannerKartu[$kat['nama']])): ?>
<div class="progress-kategori-icon">
<img src="assets/img/<?= e($kategoriBannerKartu[$kat['nama']]); ?>" alt="<?= e($kat['nama']); ?>">
</div>
<?php endif; ?>
<b class="progress-kategori-nama"><?= e($kat['nama']); ?></b>
<span class="progress-kategori-persen" style="background:<?= $kat['warna']; ?>1A;color:<?= $kat['warna']; ?>"><?= $kat['persen']; ?>%</span>
</div>

<div class="progress-bar progress-kategori-bar">
<div class="progress-fill" style="width:<?= $kat['persen']; ?>%;background:<?= $kat['warna']; ?>"></div>
</div>

<div class="progress-kategori-levels">
<?php foreach ($kat['levels'] as $lvl => $best):
    $statusClass = $best === null ? 'st-pending' : ($best >= 70 ? 'st-success' : 'st-process');
    $statusIcon = $best === null ? 'fa-clock' : ($best >= 70 ? 'fa-circle-check' : 'fa-rotate-right');
?>
<div class="progress-kategori-level-row">
<span class="level-status-icon <?= $statusClass; ?>"><i class="fa-solid <?= $statusIcon; ?>"></i></span>
<span class="level-row-name <?= $statusClass; ?>"><?= e($lvl); ?></span>
<span class="level-row-status">
<?php if ($best === null): ?>
Belum Dicoba
<?php elseif ($best >= 70): ?>
Selesai
<?php else: ?>
Perlu Diulang
<?php endif; ?>
</span>
<span class="level-row-score"><?= $best !== null ? $best : '-'; ?></span>
</div>
<?php endforeach; ?>
</div>

<div class="progress-kategori-footer">
<span><i class="fa-solid fa-star"></i> Total XP <b><?= $kat['total_xp']; ?> XP</b></span>
<a href="materi.php" class="progress-kategori-detail">Detail Progress <i class="fa-solid fa-arrow-right"></i></a>
</div>

</div>
<?php endforeach; ?>

</div>

<!-- ================= XP MINGGUAN + BADGE + LEVEL ================= -->

<section class="dash-chart-row progress-bottom-row">

<div class="dash-panel dash-chart-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-chart-line"></i> XP Mingguan</h2>
<span class="chart-range-pill">7 Hari</span>
</div>

<svg viewBox="0 0 <?= $chart['chartW']; ?> <?= $chart['chartH']; ?>" class="line-chart-svg" role="img" aria-label="Grafik XP mingguan">

<?php foreach ($chart['gridLines'] as $gl) { ?>
<line x1="<?= $chart['padL']; ?>" y1="<?= $gl['y']; ?>" x2="<?= $chart['padL'] + $chart['plotW']; ?>" y2="<?= $gl['y']; ?>" class="chart-grid"/>
<text x="<?= $chart['padL'] - 10; ?>" y="<?= $gl['y'] + 4; ?>" class="chart-axis-label" text-anchor="end"><?= $gl['nilai']; ?></text>
<?php } ?>

<polyline points="<?= e($chart['areaPolyline']); ?>" class="chart-area-fill"/>
<polyline points="<?= e($chart['polyline']); ?>" class="chart-line-u"/>

<?php foreach ($chart['points'] as $p) { ?>
<text x="<?= $p['x']; ?>" y="<?= $p['y'] - 12; ?>" class="chart-value-label" text-anchor="middle"><?= $p['total']; ?></text>
<circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="4" class="chart-dot-u">
<title><?= e($p['label']); ?>: <?= $p['total']; ?> XP</title>
</circle>
<circle cx="<?= $p['x']; ?>" cy="<?= $p['y']; ?>" r="13" class="chart-dot-hit">
<title><?= e($p['label']); ?>: <?= $p['total']; ?> XP</title>
</circle>
<text x="<?= $p['x']; ?>" y="<?= $chart['padT'] + $chart['plotH'] + 20; ?>" class="chart-axis-label" text-anchor="middle"><?= e($p['label']); ?></text>
<?php } ?>

</svg>

<div class="chart-panel-footer">Total XP Minggu Ini <b><?= $xpMingguIni; ?> XP</b></div>

</div>

<div class="dash-panel dash-achievement-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-trophy"></i> Badge yang Diperoleh</h2>
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

<div class="dash-panel level-saat-ini-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-shield-halved"></i> Level Saat Ini</h2>
</div>

<div class="level-saat-ini-body">
<div class="level-shield"><span><?= $levelSaatIni; ?></span></div>
<b class="level-xp-text"><?= (int) $user['xp']; ?> / <?= $xpKebutuhanLevel; ?> XP</b>
<div class="progress-bar level-progress-bar">
<div class="progress-fill" style="width:<?= $xpDalamLevel; ?>%"></div>
</div>
<p>Menuju Level <?= $levelSaatIni + 1; ?>: Butuh <?= $xpButuhLagi; ?> XP lagi</p>
</div>

</div>

</section>

<!-- ================= CTA BANNER ================= -->

<div class="progress-cta-banner">
<img src="assets/img/hero-girl-mascot.jpg" alt="" class="progress-cta-mascot">
<div class="progress-cta-text">
<b>Kamu hebat!</b>
<p>Pertahankan semangatmu dan raih peringkat tertinggi.</p>
</div>
<a href="materi.php" class="btn-lanjut-belajar-full progress-cta-btn"><i class="fa-solid fa-play"></i> Lanjut Belajar</a>
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

<!-- ================= RIWAYAT AKTIVITAS ================= -->

<div class="dash-panel riwayat-aktivitas-panel">

<div class="chart-panel-head">
<h2><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Aktivitas</h2>
</div>

<?php if (empty($riwayatAktivitas)): ?>
<p class="aktivitas-kosong">Belum ada aktivitas. Yuk mulai quiz atau materi pertamamu!</p>
<?php else: ?>
<div class="aktivitas-list">
<?php foreach ($riwayatAktivitas as $a): ?>
<div class="aktivitas-item">
<span class="aktivitas-icon <?= e($a['warna']); ?>"><i class="fa-solid <?= e($a['icon']); ?>"></i></span>
<div class="aktivitas-text">
<p><?= e($a['judul']); ?></p>
<span><?= e($a['waktu_relatif']); ?></span>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<?php require __DIR__ . "/../inc/footer.php"; ?>

</main>

</div>

</body>

</html>
