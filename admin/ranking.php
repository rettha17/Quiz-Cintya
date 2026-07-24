<?php
require __DIR__ . "/auth.php";
require_once __DIR__ . "/../inc/functions.php";

/* ===========================
   RESET LEADERBOARD (reset xp & level, riwayat kuis/materi/sertifikat tetap ada)
=========================== */

if (isset($_POST['reset_leaderboard'])) {

    mysqli_query($conn, "UPDATE users SET xp = 0, level = 1");
    logAktivitas($conn, 'reset_leaderboard', e($admin['nama']) . ' mereset XP & level seluruh pemain untuk memulai periode leaderboard baru.', 'fa-rotate-left', 'red');

    header("Location: ranking.php?reset=1");
    exit;

}

$pesan = "";
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $pesan = "Leaderboard berhasil direset. XP dan level seluruh pemain dikembalikan ke awal, riwayat pembelajaran tetap tersimpan.";
}

/* ===========================
   FILTER, PENCARIAN, URUTAN & PERIODE
=========================== */

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filterLevel = isset($_GET['filter_level']) ? $_GET['filter_level'] : '';
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$periode = leaderboardPeriodeValid($_GET['periode'] ?? 'sepanjang');
$urutan = in_array($_GET['urutan'] ?? '', ['skor', 'xp', 'level', 'achievement'], true) ? $_GET['urutan'] : 'skor';

$periodeSql = leaderboardPeriodeCondition($periode);

$whereParts = [];
$params = [];
$paramTypes = '';

if ($cari !== '') {
    $whereParts[] = "(u.nama LIKE ? OR u.email LIKE ?)";
    $like = "%$cari%";
    $params[] = $like;
    $params[] = $like;
    $paramTypes .= 'ss';
}

if ($filterLevel === '1-5') {
    $whereParts[] = "u.level BETWEEN 1 AND 5";
} elseif ($filterLevel === '6-10') {
    $whereParts[] = "u.level BETWEEN 6 AND 10";
} elseif ($filterLevel === '11+') {
    $whereParts[] = "u.level >= 11";
}

if ($filterStatus === 'aktif' || $filterStatus === 'nonaktif') {
    $whereParts[] = "u.status = ?";
    $params[] = $filterStatus;
    $paramTypes .= 's';
}

$whereSql = $whereParts ? ("AND " . implode(" AND ", $whereParts)) : "";

// Jumlah badge meniru ambang batas di getBadgeCatalog() (100/300/500/1000 XP) berdasarkan XP total (bukan XP periode).
$badgeCountSql = "
    (CASE WHEN u.xp>=100 THEN 1 ELSE 0 END) +
    (CASE WHEN u.xp>=300 THEN 1 ELSE 0 END) +
    (CASE WHEN u.xp>=500 THEN 1 ELSE 0 END) +
    (CASE WHEN u.xp>=1000 THEN 1 ELSE 0 END)
";

$sortColumn = 'skor_periode';
if ($urutan === 'xp') $sortColumn = 'xp_periode';
elseif ($urutan === 'level') $sortColumn = 'u.level';
elseif ($urutan === 'achievement') $sortColumn = 'badge_count';

$baseSql = "
    FROM users u
    JOIN hasil_quiz h ON h.user_id = u.id
    WHERE 1=1 $periodeSql $whereSql
";

/* ===========================
   EKSPOR CSV (sebelum ada output HTML apa pun)
=========================== */

if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $exportStmt = mysqli_prepare($conn, "
        SELECT u.nama, u.email, u.level, u.status,
            COALESCE(SUM(h.xp),0) AS xp_periode,
            COALESCE(SUM(h.score),0) AS skor_periode,
            $badgeCountSql AS badge_count
        $baseSql
        GROUP BY u.id, u.nama, u.email, u.level, u.status, u.xp
        ORDER BY $sortColumn DESC
    ");
    if ($paramTypes !== '') {
        mysqli_stmt_bind_param($exportStmt, $paramTypes, ...$params);
    }
    mysqli_stmt_execute($exportStmt);
    $exportResult = mysqli_stmt_get_result($exportStmt);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leaderboard_statquest_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Peringkat', 'Nama', 'Email', 'Level', 'XP', 'Skor', 'Achievement', 'Status']);
    $no = 1;
    while ($row = mysqli_fetch_assoc($exportResult)) {
        fputcsv($out, [
            $no++,
            $row['nama'],
            $row['email'],
            (int) $row['level'],
            (int) $row['xp_periode'],
            (int) $row['skor_periode'],
            (int) $row['badge_count'],
            $row['status'] === 'aktif' ? 'Aktif' : 'Nonaktif',
        ]);
    }
    fclose($out);
    exit;

}

/* ===========================
   PAGINASI & DATA TABEL LEADERBOARD
=========================== */

$perPage = 10;

$countStmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT u.id) c $baseSql");
if ($paramTypes !== '') {
    mysqli_stmt_bind_param($countStmt, $paramTypes, ...$params);
}
mysqli_stmt_execute($countStmt);
$totalRows = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'];

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$dataStmt = mysqli_prepare($conn, "
    SELECT u.id, u.nama, u.email, u.foto, u.level, u.status, u.xp AS xp_total,
        COALESCE(SUM(h.xp),0) AS xp_periode,
        COALESCE(SUM(h.score),0) AS skor_periode,
        $badgeCountSql AS badge_count
    $baseSql
    GROUP BY u.id, u.nama, u.email, u.foto, u.level, u.status, u.xp
    ORDER BY $sortColumn DESC
    LIMIT ? OFFSET ?
");
$dataParams = $params;
$dataParams[] = $perPage;
$dataParams[] = $offset;
mysqli_stmt_bind_param($dataStmt, $paramTypes . 'ii', ...$dataParams);
mysqli_stmt_execute($dataStmt);
$daftarRanking = mysqli_stmt_get_result($dataStmt);


$totalMateri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];

/* ===========================
   RINGKASAN STATISTIK
=========================== */

$totalPemain = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

$totalXpPeriode = (int) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(xp),0) t FROM hasil_quiz h WHERE 1=1 $periodeSql
"))['t'];

$rataSkorPeriode = (int) round((float) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(AVG(score),0) v FROM hasil_quiz h WHERE 1=1 $periodeSql
"))['v']);

$topStmt = mysqli_prepare($conn, "
    SELECT u.id, u.nama, u.foto, u.level,
        COALESCE(SUM(h.xp),0) AS xp_periode,
        COALESCE(SUM(h.score),0) AS skor_periode
    FROM users u
    JOIN hasil_quiz h ON h.user_id = u.id
    WHERE 1=1 $periodeSql
    GROUP BY u.id, u.nama, u.foto, u.level
    ORDER BY skor_periode DESC
    LIMIT 1
");
mysqli_stmt_execute($topStmt);
$peringkatPertama = mysqli_fetch_assoc(mysqli_stmt_get_result($topStmt));

/* ===========================
   GRAFIK: DISTRIBUSI LEVEL PEMAIN
=========================== */

$levelBucketResult = mysqli_query($conn, "SELECT LEAST(level,8) AS lvl_bucket, COUNT(*) c FROM users GROUP BY LEAST(level,8)");
$levelBucketMap = [];
while ($row = mysqli_fetch_assoc($levelBucketResult)) {
    $levelBucketMap[(int) $row['lvl_bucket']] = (int) $row['c'];
}
$levelDistribusi = [];
for ($lv = 1; $lv <= 8; $lv++) {
    $levelDistribusi[] = [
        'label' => $lv === 8 ? 'Level 8+' : "Level $lv",
        'total' => $levelBucketMap[$lv] ?? 0,
    ];
}
$levelDistribusiMax = max(1, max(array_column($levelDistribusi, 'total')));

/* ===========================
   GRAFIK: DISTRIBUSI XP PEMAIN
=========================== */

$xpBucketDefs = [
    ['label' => '0 - 99 XP', 'min' => 0, 'max' => 99],
    ['label' => '100 - 299 XP', 'min' => 100, 'max' => 299],
    ['label' => '300 - 499 XP', 'min' => 300, 'max' => 499],
    ['label' => '500 - 999 XP', 'min' => 500, 'max' => 999],
    ['label' => '1000+ XP', 'min' => 1000, 'max' => null],
];
$xpDistribusi = [];
foreach ($xpBucketDefs as $b) {
    if ($b['max'] === null) {
        $c = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE xp >= {$b['min']}"))['c'];
    } else {
        $c = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE xp BETWEEN {$b['min']} AND {$b['max']}"))['c'];
    }
    $xpDistribusi[] = ['label' => $b['label'], 'total' => $c];
}
$xpDistribusiMax = max(1, max(array_column($xpDistribusi, 'total')));

/* ===========================
   GRAFIK: AKTIVITAS MINGGUAN (jumlah kuis dikerjakan, 7 hari terakhir)
=========================== */

$hariLabel = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

$aktivitasResult = mysqli_query($conn, "
    SELECT DATE(tanggal) d, COUNT(*) c FROM hasil_quiz
    WHERE tanggal >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(tanggal)
");
$aktivitasMap = [];
while ($row = mysqli_fetch_assoc($aktivitasResult)) {
    $aktivitasMap[$row['d']] = (int) $row['c'];
}
$aktivitasMingguan = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $aktivitasMingguan[] = [
        'label' => $hariLabel[date('N', strtotime($tgl)) - 1],
        'total' => $aktivitasMap[$tgl] ?? 0,
    ];
}
$aktivitasMingguanMax = max(1, max(array_column($aktivitasMingguan, 'total')));

/* ===========================
   DETAIL PEMAIN TERATAS (statistik lengkap si peringkat pertama)
=========================== */

$detailTeratas = null;
if ($peringkatPertama) {
    $userTeratasId = (int) $peringkatPertama['id'];

    $statusTeratas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, foto, status, xp FROM users WHERE id=$userTeratasId"));
    $usernameTeratas = strtolower(explode('@', $statusTeratas['email'])[0]);
    $badgesTeratas = getBadges((int) $statusTeratas['xp']);
    $quizSelesaiTeratas = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM hasil_quiz WHERE user_id=$userTeratasId"))['c'];
    $materiSelesaiTeratas = materiSelesaiCount($conn, $userTeratasId);

    $detailTeratas = [
        'email' => $statusTeratas['email'],
        'username' => $usernameTeratas,
        'status' => $statusTeratas['status'],
        'badge_count' => count($badgesTeratas),
        'quiz_selesai' => $quizSelesaiTeratas,
        'materi_persen' => $totalMateri > 0 ? round($materiSelesaiTeratas / $totalMateri * 100) : 0,
        'sertifikat' => sertifikatCount($conn, $userTeratasId),
        'riwayat' => riwayatKuisTerakhir($conn, $userTeratasId),
        'totalBadgeTier' => count(getBadgeCatalog()),
    ];
}

$periodeLabel = [
    'hari_ini' => 'Hari Ini',
    'minggu_ini' => 'Minggu Ini',
    'bulan_ini' => 'Bulan Ini',
    'sepanjang' => 'Sepanjang Waktu',
];

$urutanLabel = [
    'skor' => 'Skor Tertinggi',
    'xp' => 'XP Tertinggi',
    'level' => 'Level Tertinggi',
    'achievement' => 'Achievement Terbanyak',
];

$exportQs = $_GET;
$exportQs['export'] = 'csv';
unset($exportQs['page']);
$exportUrl = 'ranking.php?' . http_build_query($exportQs);

$activeMenu = 'ranking';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Leaderboard | Admin StatQuest BPS</title>

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

<div>
<h1><i class="fa-solid fa-trophy lb-trophy-icon"></i> Leaderboard</h1>
<p class="admin-topbar-sub">Pantau peringkat dan performa seluruh pemain StatQuest BPS.</p>
</div>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-success"><?= e($pesan); ?></div>
<?php } ?>

<div class="lb-toolbar">

<a class="btn btn-sm btn-secondary lb-export-btn" href="<?= e($exportUrl); ?>"><i class="fa-solid fa-file-arrow-down"></i> Export CSV</a>

<form method="POST" class="lb-reset-form" onsubmit="return confirm('Reset XP dan level SEMUA pemain ke awal untuk memulai periode leaderboard baru? Riwayat kuis, materi, dan sertifikat TETAP tersimpan, tapi peringkat akan mulai dari nol lagi. Tindakan ini tidak bisa dibatalkan.');">
<button type="submit" name="reset_leaderboard" class="btn btn-sm btn-reset-leaderboard"><i class="fa-solid fa-rotate-left"></i> Reset Leaderboard</button>
</form>

</div>

<div class="admin-stats admin-stats-v2 cols-4">

<div class="stat-card-v2 lb-top-card">
<div class="stat-card-body">
<div class="lb-top-label"><i class="fa-solid fa-crown"></i> Peringkat Pertama</div>
<?php if ($peringkatPertama) { ?>
<div class="lb-top-player">
<img src="../pengguna/assets/img/<?= !empty($peringkatPertama['foto']) ? e($peringkatPertama['foto']) : 'default.png'; ?>" class="lb-top-avatar" alt="">
<div>
<strong><?= e($peringkatPertama['nama']); ?></strong>
<span class="level-badge lb-top-level-badge"><i class="fa-solid fa-star"></i> Level <?= (int) $peringkatPertama['level']; ?></span>
</div>
</div>
<div class="lb-top-stats">
<div><span>Skor</span><strong><?= number_format((int) $peringkatPertama['skor_periode'], 0, ',', '.'); ?></strong></div>
<div><span>XP</span><strong><?= number_format((int) $peringkatPertama['xp_periode'], 0, ',', '.'); ?></strong></div>
</div>
<?php } else { ?>
<p class="text-muted">Belum ada data kuis pada periode ini.</p>
<?php } ?>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
<span class="stat-card-label">Total Pemain</span>
</div>
<h2><?= $totalPemain; ?></h2>
<span class="stat-trend flat">Seluruh pemain terdaftar</span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon purple"><i class="fa-solid fa-bolt"></i></div>
<span class="stat-card-label">Total XP</span>
</div>
<h2><?= number_format($totalXpPeriode, 0, ',', '.'); ?></h2>
<span class="stat-trend flat"><?= $periodeLabel[$periode]; ?></span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon yellow"><i class="fa-solid fa-chart-line"></i></div>
<span class="stat-card-label">Rata-rata Skor</span>
</div>
<h2><?= number_format($rataSkorPeriode, 0, ',', '.'); ?></h2>
<span class="stat-trend flat"><?= $periodeLabel[$periode]; ?></span>
</div>
</div>

</div>

<div class="lb-main-grid">

<div class="admin-panel lb-table-col">

<form method="GET" class="admin-filter user-table-filter">

<div class="user-search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" name="cari" placeholder="Cari nama pemain, username, atau email..." value="<?= e($cari); ?>">
</div>

<select name="filter_level" onchange="this.form.submit()">
<option value="">Semua Level</option>
<option value="1-5" <?= $filterLevel === '1-5' ? 'selected' : ''; ?>>Level 1 - 5</option>
<option value="6-10" <?= $filterLevel === '6-10' ? 'selected' : ''; ?>>Level 6 - 10</option>
<option value="11+" <?= $filterLevel === '11+' ? 'selected' : ''; ?>>Level 11 ke atas</option>
</select>

<select name="filter_status" onchange="this.form.submit()">
<option value="">Semua Status</option>
<option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
<option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
</select>

<select name="periode" onchange="this.form.submit()">
<option value="sepanjang" <?= $periode === 'sepanjang' ? 'selected' : ''; ?>>Sepanjang Waktu</option>
<option value="hari_ini" <?= $periode === 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
<option value="minggu_ini" <?= $periode === 'minggu_ini' ? 'selected' : ''; ?>>Minggu Ini</option>
<option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
</select>

<select name="urutan" onchange="this.form.submit()">
<option value="skor" <?= $urutan === 'skor' ? 'selected' : ''; ?>>Urutkan: Skor Tertinggi</option>
<option value="xp" <?= $urutan === 'xp' ? 'selected' : ''; ?>>Urutkan: XP Tertinggi</option>
<option value="level" <?= $urutan === 'level' ? 'selected' : ''; ?>>Urutkan: Level Tertinggi</option>
<option value="achievement" <?= $urutan === 'achievement' ? 'selected' : ''; ?>>Urutkan: Achievement Terbanyak</option>
</select>

<button class="btn btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>

<?php if ($cari !== '' || $filterLevel !== '' || $filterStatus !== '' || $periode !== 'sepanjang' || $urutan !== 'skor') { ?>
<a class="btn btn-sm btn-secondary" href="ranking.php">Reset</a>
<?php } ?>

</form>

<div class="admin-panel-head">
<h2><i class="fa-solid fa-list-ol"></i> Daftar Leaderboard</h2>
<p class="admin-panel-sub">Diurutkan berdasarkan <?= strtolower($urutanLabel[$urutan]); ?> &middot; <?= $periodeLabel[$periode]; ?>.</p>
</div>

<div class="admin-table-wrap">

<table class="admin-table">

<thead>
<tr>
<th>Peringkat</th>
<th>Avatar</th>
<th>Nama</th>
<th>Level</th>
<th>XP</th>
<th>Skor</th>
<th>Badge</th>
<th>Achievement</th>
<th>Materi</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarRanking) == 0) { ?>

<tr><td colspan="11" style="text-align:center;">Belum ada data leaderboard untuk filter ini.</td></tr>

<?php } else { $no = $offset + 1; while ($row = mysqli_fetch_assoc($daftarRanking)) {
    $badgeLv = levelBadge($row['level']);
    $username = strtolower(explode('@', $row['email'])[0]);
    $badgesEarned = getBadges((int) $row['xp_total']);
    $totalBadgeTier = count(getBadgeCatalog());
    $materiSelesai = materiSelesaiCount($conn, $row['id']);
    $materiPersen = $totalMateri > 0 ? round($materiSelesai / $totalMateri * 100) : 0;
    $riwayat = riwayatKuisTerakhir($conn, $row['id']);
    $jumlahSertifikat = sertifikatCount($conn, $row['id']);
    $peringkatSaatIni = $no++;
?>

<tr>

<td>
<?php if ($peringkatSaatIni === 1) { ?>
<i class="fa-solid fa-crown rank-icon rank-gold" title="Peringkat 1"></i>
<?php } elseif ($peringkatSaatIni === 2) { ?>
<i class="fa-solid fa-medal rank-icon rank-silver" title="Peringkat 2"></i>
<?php } elseif ($peringkatSaatIni === 3) { ?>
<i class="fa-solid fa-medal rank-icon rank-bronze" title="Peringkat 3"></i>
<?php } else { ?>
<?= $peringkatSaatIni; ?>
<?php } ?>
</td>

<td><img src="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>" class="rank-photo" alt=""></td>

<td>
<?= e($row['nama']); ?>
<span class="lb-username">@<?= e($username); ?></span>
</td>

<td>
<span class="level-badge" style="color:<?= $badgeLv['color']; ?>;background:<?= $badgeLv['bg']; ?>;">
<i class="fa-solid fa-star"></i> <?= (int) $row['level']; ?>
</span>
</td>

<td><strong><?= number_format((int) $row['xp_periode'], 0, ',', '.'); ?></strong></td>

<td><strong><?= number_format((int) $row['skor_periode'], 0, ',', '.'); ?></strong></td>

<td>
<div class="lb-badge-row">
<?php if (empty($badgesEarned)) { ?>
<span class="text-muted">-</span>
<?php } else { foreach ($badgesEarned as $b) { ?>
<span class="lb-badge-chip" title="<?= e($b['nama']); ?>"><?= $b['icon']; ?></span>
<?php } } ?>
</div>
</td>

<td><?= count($badgesEarned); ?>/<?= $totalBadgeTier; ?></td>

<td>
<div class="lb-materi-cell">
<span><?= $materiPersen; ?>%</span>
<div class="lb-materi-track"><div class="lb-materi-fill" style="width:<?= $materiPersen; ?>%;"></div></div>
</div>
</td>

<td>
<span class="status-badge status-<?= $row['status']; ?>" style="pointer-events:none;">
<i class="fa-solid fa-circle"></i> <?= $row['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
</span>
</td>

<td class="user-actions">

<button type="button" class="table-icon-btn btn-view"
    data-nama="<?= e($row['nama']); ?>"
    data-username="<?= e($username); ?>"
    data-email="<?= e($row['email']); ?>"
    data-level="<?= (int) $row['level']; ?>"
    data-xp="<?= number_format((int) $row['xp_periode'], 0, ',', '.'); ?>"
    data-skor="<?= number_format((int) $row['skor_periode'], 0, ',', '.'); ?>"
    data-badge="<?= count($badgesEarned); ?>/<?= $totalBadgeTier; ?>"
    data-materi="<?= $materiPersen; ?>%"
    data-sertifikat="<?= $jumlahSertifikat; ?>"
    data-foto="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>"
    data-riwayat='<?= e(json_encode($riwayat)); ?>'
    title="Lihat detail">
<i class="fa-solid fa-eye"></i>
</button>

</td>

</tr>

<?php } } ?>

</tbody>

</table>

</div>

<div class="admin-pagination">

<p class="admin-pagination-info">
Menampilkan <?= $totalRows === 0 ? 0 : $offset + 1; ?> - <?= min($offset + $perPage, $totalRows); ?> dari <?= $totalRows; ?> pemain
</p>

<?php if ($totalPages > 1) { ?>
<div class="admin-pagination-nav">

<?php
$qs = $_GET;
function pageLink($qs, $p) {
    $qs['page'] = $p;
    return 'ranking.php?' . http_build_query($qs);
}
?>

<a href="<?= pageLink($qs, max(1, $page - 1)); ?>" class="page-btn <?= $page <= 1 ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-left"></i></a>

<?php for ($p = 1; $p <= $totalPages; $p++) {
    if ($p == 1 || $p == $totalPages || abs($p - $page) <= 1) { ?>
<a href="<?= pageLink($qs, $p); ?>" class="page-btn <?= $p === $page ? 'active' : ''; ?>"><?= $p; ?></a>
<?php } elseif (abs($p - $page) == 2) { ?>
<span class="page-dots">...</span>
<?php }
} ?>

<a href="<?= pageLink($qs, min($totalPages, $page + 1)); ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-right"></i></a>

</div>
<?php } ?>

</div>

</div>

<div class="lb-sidebar">

<div class="admin-panel lb-summary-panel">

<div class="admin-panel-head">
<h2><i class="fa-solid fa-chart-simple"></i> Ringkasan Leaderboard</h2>
</div>

<div class="lb-tabs">
<button type="button" class="lb-tab-btn active" data-tab="level">Distribusi Level</button>
<button type="button" class="lb-tab-btn" data-tab="xp">Distribusi XP</button>
<button type="button" class="lb-tab-btn" data-tab="aktivitas">Aktivitas Mingguan</button>
</div>

<div class="lb-tab-content" data-tab-content="level">
<div class="lb-level-bars">
<?php foreach ($levelDistribusi as $ld) {
    $persen = $totalPemain > 0 ? round($ld['total'] / $totalPemain * 100) : 0;
    $lebar = round($ld['total'] / $levelDistribusiMax * 100);
?>
<div class="lb-level-row">
<span class="lb-level-label"><?= e($ld['label']); ?></span>
<div class="lb-level-track"><div class="lb-level-fill" style="width:<?= $lebar; ?>%;"></div></div>
<span class="lb-level-count"><?= $ld['total']; ?> (<?= $persen; ?>%)</span>
</div>
<?php } ?>
</div>
</div>

<div class="lb-tab-content" data-tab-content="xp" hidden>
<div class="lb-level-bars">
<?php foreach ($xpDistribusi as $xd) {
    $persen = $totalPemain > 0 ? round($xd['total'] / $totalPemain * 100) : 0;
    $lebar = round($xd['total'] / $xpDistribusiMax * 100);
?>
<div class="lb-level-row">
<span class="lb-level-label"><?= e($xd['label']); ?></span>
<div class="lb-level-track"><div class="lb-level-fill lb-level-fill-xp" style="width:<?= $lebar; ?>%;"></div></div>
<span class="lb-level-count"><?= $xd['total']; ?> (<?= $persen; ?>%)</span>
</div>
<?php } ?>
</div>
</div>

<div class="lb-tab-content" data-tab-content="aktivitas" hidden>
<div class="lb-level-bars">
<?php foreach ($aktivitasMingguan as $am) {
    $lebar = round($am['total'] / $aktivitasMingguanMax * 100);
?>
<div class="lb-level-row">
<span class="lb-level-label"><?= e($am['label']); ?></span>
<div class="lb-level-track"><div class="lb-level-fill lb-level-fill-aktivitas" style="width:<?= $lebar; ?>%;"></div></div>
<span class="lb-level-count"><?= $am['total']; ?> kuis</span>
</div>
<?php } ?>
</div>
</div>

</div>

<div class="admin-panel lb-top-detail-panel">

<div class="admin-panel-head">
<h2><i class="fa-solid fa-user-graduate"></i> Detail Pemain Teratas</h2>
</div>

<?php if ($peringkatPertama && $detailTeratas) {
    $usernameTop = $detailTeratas['username'];
?>

<div class="lb-detail-head">
<img src="../pengguna/assets/img/<?= !empty($peringkatPertama['foto']) ? e($peringkatPertama['foto']) : 'default.png'; ?>" class="lb-top-avatar" alt="">
<div>
<strong><?= e($peringkatPertama['nama']); ?></strong>
<span class="status-badge status-<?= $detailTeratas['status']; ?>" style="pointer-events:none;"><?= $detailTeratas['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?></span>
<span class="lb-detail-username">@<?= e($usernameTop); ?> &middot; <?= e($detailTeratas['email']); ?></span>
</div>
</div>

<div class="lb-detail-grid">
<div><span>Level</span><strong><?= (int) $peringkatPertama['level']; ?></strong></div>
<div><span>XP</span><strong><?= number_format((int) $peringkatPertama['xp_periode'], 0, ',', '.'); ?></strong></div>
<div><span>Skor</span><strong><?= number_format((int) $peringkatPertama['skor_periode'], 0, ',', '.'); ?></strong></div>
<div><span>Badge</span><strong><?= $detailTeratas['badge_count']; ?>/<?= $detailTeratas['totalBadgeTier']; ?></strong></div>
<div><span>Quiz Selesai</span><strong><?= $detailTeratas['quiz_selesai']; ?></strong></div>
<div><span>Materi Selesai</span><strong><?= $detailTeratas['materi_persen']; ?>%</strong></div>
<div><span>Sertifikat</span><strong><?= $detailTeratas['sertifikat']; ?></strong></div>
</div>

<button type="button" class="btn btn-sm lb-detail-btn btn-view"
    data-nama="<?= e($peringkatPertama['nama']); ?>"
    data-username="<?= e($usernameTop); ?>"
    data-email="<?= e($detailTeratas['email']); ?>"
    data-level="<?= (int) $peringkatPertama['level']; ?>"
    data-xp="<?= number_format((int) $peringkatPertama['xp_periode'], 0, ',', '.'); ?>"
    data-skor="<?= number_format((int) $peringkatPertama['skor_periode'], 0, ',', '.'); ?>"
    data-badge="<?= $detailTeratas['badge_count']; ?>/<?= $detailTeratas['totalBadgeTier']; ?>"
    data-materi="<?= $detailTeratas['materi_persen']; ?>%"
    data-sertifikat="<?= $detailTeratas['sertifikat']; ?>"
    data-foto="../pengguna/assets/img/<?= !empty($peringkatPertama['foto']) ? e($peringkatPertama['foto']) : 'default.png'; ?>"
    data-riwayat='<?= e(json_encode($detailTeratas['riwayat'])); ?>'>
<i class="fa-solid fa-eye"></i> Lihat Detail Lengkap
</button>

<?php } else { ?>
<p class="text-muted">Belum ada data untuk ditampilkan.</p>
<?php } ?>

</div>

</div>

</div>

<?php require __DIR__ . "/partials/footer.php"; ?>

</main>

</div>

<div class="user-view-overlay" id="userViewOverlay">
<div class="user-view-modal lb-view-modal">

<button type="button" class="user-view-close" id="userViewClose"><i class="fa-solid fa-xmark"></i></button>

<img src="" alt="" class="user-view-avatar" id="userViewAvatar">

<h3 id="userViewNama"></h3>
<p class="user-view-username" id="userViewUsername"></p>

<div class="user-view-grid">

<div>
<span>Email</span>
<strong id="userViewEmail"></strong>
</div>

<div>
<span>Level</span>
<strong id="userViewLevel"></strong>
</div>

<div>
<span>XP</span>
<strong id="userViewXp"></strong>
</div>

<div>
<span>Skor</span>
<strong id="userViewSkor"></strong>
</div>

<div>
<span>Badge / Achievement</span>
<strong id="userViewBadge"></strong>
</div>

<div>
<span>Materi Selesai</span>
<strong id="userViewMateri"></strong>
</div>

<div>
<span>Sertifikat Diterbitkan</span>
<strong id="userViewSertifikat"></strong>
</div>

</div>

<div class="lb-history-block">
<h4>Riwayat Kuis Terakhir</h4>
<div id="userViewRiwayat" class="lb-history-list"></div>
</div>

</div>
</div>

<script>
(function(){
    var overlay = document.getElementById("userViewOverlay");
    var closeBtn = document.getElementById("userViewClose");
    var riwayatBox = document.getElementById("userViewRiwayat");

    document.querySelectorAll(".btn-view").forEach(function(btn){
        btn.addEventListener("click", function(){
            document.getElementById("userViewAvatar").src = btn.dataset.foto;
            document.getElementById("userViewNama").textContent = btn.dataset.nama;
            document.getElementById("userViewUsername").textContent = "@" + btn.dataset.username;
            document.getElementById("userViewEmail").textContent = btn.dataset.email;
            document.getElementById("userViewLevel").textContent = "Level " + btn.dataset.level;
            document.getElementById("userViewXp").textContent = btn.dataset.xp;
            document.getElementById("userViewSkor").textContent = btn.dataset.skor;
            document.getElementById("userViewBadge").textContent = btn.dataset.badge;
            document.getElementById("userViewMateri").textContent = btn.dataset.materi;
            document.getElementById("userViewSertifikat").textContent = btn.dataset.sertifikat;

            var riwayat = [];
            try { riwayat = JSON.parse(btn.dataset.riwayat || "[]"); } catch (e) { riwayat = []; }

            riwayatBox.innerHTML = "";
            if (riwayat.length === 0) {
                riwayatBox.innerHTML = '<p class="text-muted">Belum ada riwayat kuis.</p>';
            } else {
                riwayat.forEach(function(r){
                    var item = document.createElement("div");
                    item.className = "lb-history-item";
                    item.innerHTML =
                        '<div><strong>' + r.kategori + '</strong><span>' + r.level + ' &middot; ' + r.tanggal + '</span></div>' +
                        '<strong class="lb-history-score">' + r.score + '</strong>';
                    riwayatBox.appendChild(item);
                });
            }

            overlay.classList.add("show");
        });
    });

    function closeOverlay(){
        overlay.classList.remove("show");
    }

    closeBtn.addEventListener("click", closeOverlay);
    overlay.addEventListener("click", function(e){
        if (e.target === overlay) closeOverlay();
    });
})();

(function(){
    var tabBtns = document.querySelectorAll(".lb-tab-btn");
    var tabContents = document.querySelectorAll(".lb-tab-content");

    tabBtns.forEach(function(btn){
        btn.addEventListener("click", function(){
            tabBtns.forEach(function(b){ b.classList.remove("active"); });
            btn.classList.add("active");

            var target = btn.dataset.tab;
            tabContents.forEach(function(c){
                c.hidden = c.dataset.tabContent !== target;
            });
        });
    });
})();
</script>

</body>

</html>
