<?php
require __DIR__ . "/auth.php";

$tab = (isset($_GET['tab']) && $_GET['tab'] === 'topik') ? 'topik' : 'pengguna';
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filterKategori = isset($_GET['filter_kategori']) ? (int) $_GET['filter_kategori'] : 0;

function trendPct($now, $prev) {
    if ($prev == 0) {
        return $now > 0 ? 100 : 0;
    }
    return (int) round((($now - $prev) / $prev) * 100);
}

$totalMateri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];
$totalDibuka = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi_progress"))['c'];
$totalSelesai = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi_progress WHERE status='selesai'"))['c'];
$totalXpMateri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(xp_diberikan),0) c FROM materi_progress"))['c'];
$totalPengguna = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

/* ===========================
   TREN DIBANDING BULAN LALU (dihitung dari data asli, bukan angka statis)
=========================== */

$penggunaBulanIni = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())"))['c'];
$penggunaBulanLalu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE YEAR(created_at)=YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(created_at)=MONTH(CURDATE() - INTERVAL 1 MONTH)"))['c'];
$trendPengguna = trendPct($penggunaBulanIni, $penggunaBulanLalu);

$selesaiBulanIni = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi_progress WHERE status='selesai' AND YEAR(selesai_pada)=YEAR(CURDATE()) AND MONTH(selesai_pada)=MONTH(CURDATE())"))['c'];
$selesaiBulanLalu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi_progress WHERE status='selesai' AND YEAR(selesai_pada)=YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(selesai_pada)=MONTH(CURDATE() - INTERVAL 1 MONTH)"))['c'];
$trendSelesai = trendPct($selesaiBulanIni, $selesaiBulanLalu);

$xpBulanIni = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(xp_diberikan),0) c FROM materi_progress WHERE YEAR(COALESCE(selesai_pada,dibuka_pada))=YEAR(CURDATE()) AND MONTH(COALESCE(selesai_pada,dibuka_pada))=MONTH(CURDATE())"))['c'];
$xpBulanLalu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(xp_diberikan),0) c FROM materi_progress WHERE YEAR(COALESCE(selesai_pada,dibuka_pada))=YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(COALESCE(selesai_pada,dibuka_pada))=MONTH(CURDATE() - INTERVAL 1 MONTH)"))['c'];
$trendXp = trendPct($xpBulanIni, $xpBulanLalu);

/* ===========================
   PER PENGGUNA (pencarian + filter status progress)
=========================== */

$cariLike = '%' . $cari . '%';
$stmtPengguna = mysqli_prepare($conn, "
    SELECT u.id, u.nama, u.email, u.foto,
        COUNT(mp.id) AS dibuka,
        SUM(CASE WHEN mp.status='selesai' THEN 1 ELSE 0 END) AS selesai,
        COALESCE(SUM(mp.xp_diberikan),0) AS xp_materi,
        MAX(COALESCE(mp.selesai_pada, mp.dibuka_pada)) AS terakhir_aktif
    FROM users u
    LEFT JOIN materi_progress mp ON mp.user_id = u.id
    WHERE (u.nama LIKE ? OR u.email LIKE ?)
    GROUP BY u.id, u.nama, u.email, u.foto
    ORDER BY selesai DESC, xp_materi DESC, u.nama ASC
");
mysqli_stmt_bind_param($stmtPengguna, "ss", $cariLike, $cariLike);
mysqli_stmt_execute($stmtPengguna);
$resultPengguna = mysqli_stmt_get_result($stmtPengguna);

$daftarPengguna = [];
while ($row = mysqli_fetch_assoc($resultPengguna)) {
    $selesai = (int) $row['selesai'];
    $dibuka = (int) $row['dibuka'];

    if ($dibuka === 0) {
        $statusRow = 'belum_mulai';
    } elseif ($totalMateri > 0 && $selesai === $totalMateri) {
        $statusRow = 'selesai';
    } else {
        $statusRow = 'sedang';
    }

    if ($filterStatus !== '' && $filterStatus !== $statusRow) {
        continue;
    }

    $daftarPengguna[] = $row;
}

/* ===========================
   PER TOPIK (pencarian + filter kategori)
=========================== */

$daftarKategori = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama ASC");

$whereTopik = "m.judul LIKE ?";
$paramsTopik = [$cariLike];
$typesTopik = "s";

if ($filterKategori > 0) {
    $whereTopik .= " AND m.kategori_id = ?";
    $paramsTopik[] = $filterKategori;
    $typesTopik .= "i";
}

$stmtTopik = mysqli_prepare($conn, "
    SELECT m.id, m.judul, m.xp, k.nama AS kategori,
        COUNT(mp.id) AS dibuka,
        SUM(CASE WHEN mp.status='selesai' THEN 1 ELSE 0 END) AS selesai
    FROM materi m
    JOIN kategori k ON k.id = m.kategori_id
    LEFT JOIN materi_progress mp ON mp.materi_id = m.id
    WHERE {$whereTopik}
    GROUP BY m.id, m.judul, m.xp, k.nama
    ORDER BY k.id, m.urutan, m.id
");
mysqli_stmt_bind_param($stmtTopik, $typesTopik, ...$paramsTopik);
mysqli_stmt_execute($stmtTopik);
$daftarTopik = mysqli_stmt_get_result($stmtTopik);

$activeMenu = 'progress_materi';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Progress Materi | Admin StatQuest BPS</title>

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
<h1><i class="fa-solid fa-chart-line"></i> Progress Materi</h1>
<p class="admin-breadcrumb"><a href="index.php">Dashboard</a> <i class="fa-solid fa-chevron-right"></i> Progress Materi</p>
</div>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<div class="admin-stats admin-stats-v2 cols-4">

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
<span class="stat-card-label">Total Pengguna</span>
</div>
<h2><?= $totalPengguna; ?></h2>
<span class="stat-trend <?= $trendPengguna >= 0 ? 'up' : 'flat'; ?>"><i class="fa-solid fa-arrow-<?= $trendPengguna >= 0 ? 'up' : 'down'; ?>"></i> <?= $trendPengguna; ?>% Dibanding bulan lalu</span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon purple"><i class="fa-solid fa-book"></i></div>
<span class="stat-card-label">Total Topik Materi</span>
</div>
<h2><?= $totalMateri; ?></h2>
<span class="stat-trend flat">Materi tersedia di sistem</span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
<span class="stat-card-label">Topik Diselesaikan</span>
</div>
<h2><?= $totalSelesai; ?> <small style="font-size:16px;color:var(--text-muted);">/ <?= $totalDibuka; ?> dibuka</small></h2>
<span class="stat-trend <?= $trendSelesai >= 0 ? 'up' : 'flat'; ?>"><i class="fa-solid fa-arrow-<?= $trendSelesai >= 0 ? 'up' : 'down'; ?>"></i> <?= $trendSelesai; ?>% Dibanding bulan lalu</span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon orange"><i class="fa-solid fa-bolt"></i></div>
<span class="stat-card-label">Total XP dari Materi</span>
</div>
<h2><?= $totalXpMateri; ?> XP</h2>
<span class="stat-trend <?= $trendXp >= 0 ? 'up' : 'flat'; ?>"><i class="fa-solid fa-arrow-<?= $trendXp >= 0 ? 'up' : 'down'; ?>"></i> <?= $trendXp; ?>% Dibanding bulan lalu</span>
</div>
</div>

</div>

<div class="admin-panel">

<h2><i class="fa-solid fa-chart-line"></i> Progress Baca Materi Pengguna</h2>

<div class="admin-tabs">
<a href="progress_materi.php?tab=pengguna" class="<?= $tab === 'pengguna' ? 'active' : ''; ?>">Per Pengguna</a>
<a href="progress_materi.php?tab=topik" class="<?= $tab === 'topik' ? 'active' : ''; ?>">Per Topik</a>
</div>

<?php if ($tab === 'pengguna'): ?>

<form method="GET" class="admin-filter user-table-filter">

<input type="hidden" name="tab" value="pengguna">

<div class="user-search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" name="cari" placeholder="Cari pengguna..." value="<?= e($cari); ?>">
</div>

<select name="filter_status" onchange="this.form.submit()">
<option value="">Semua Status</option>
<option value="belum_mulai" <?= $filterStatus === 'belum_mulai' ? 'selected' : ''; ?>>Belum Mulai</option>
<option value="sedang" <?= $filterStatus === 'sedang' ? 'selected' : ''; ?>>Sedang Belajar</option>
<option value="selesai" <?= $filterStatus === 'selesai' ? 'selected' : ''; ?>>Selesai Semua</option>
</select>

<button class="btn btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>

<?php if ($cari !== '' || $filterStatus !== '') { ?>
<a class="btn btn-sm btn-secondary" href="progress_materi.php?tab=pengguna">Reset</a>
<?php } ?>

</form>

<table class="admin-table">

<thead>
<tr>
<th>Foto</th>
<th>Nama</th>
<th>Topik Diselesaikan</th>
<th>Progress</th>
<th>XP dari Materi</th>
<th>Terakhir Aktif</th>
</tr>
</thead>

<tbody>

<?php if (count($daftarPengguna) === 0): ?>
<tr><td colspan="6" style="text-align:center;">Tidak ada pengguna yang cocok.</td></tr>
<?php else: foreach ($daftarPengguna as $row):
    $selesai = (int) $row['selesai'];
    $pct = $totalMateri > 0 ? round(($selesai / $totalMateri) * 100) : 0;
    $usernameRow = strtolower(explode('@', $row['email'])[0]);
?>

<tr>
<td><img src="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>" class="rank-photo"></td>
<td>
<?= e($row['nama']); ?><br>
<span class="lb-username">@<?= e($usernameRow); ?></span>
</td>
<td><?= $selesai; ?> / <?= $totalMateri; ?></td>
<td>
<div style="display:flex;align-items:center;gap:10px;">
<div style="flex:1;height:8px;border-radius:20px;background:var(--border);overflow:hidden;">
<div style="height:100%;border-radius:20px;background:linear-gradient(90deg,var(--brand-primary),#42A5F5);width:<?= $pct; ?>%;"></div>
</div>
<small style="color:var(--brand-primary);font-weight:700;"><?= $pct; ?>%</small>
</div>
</td>
<td><strong><?= (int) $row['xp_materi']; ?> XP</strong></td>
<td><?= $row['terakhir_aktif'] ? date('d M Y H:i', strtotime($row['terakhir_aktif'])) : '-'; ?></td>
</tr>

<?php endforeach; endif; ?>

</tbody>

</table>

<?php else: ?>

<form method="GET" class="admin-filter user-table-filter">

<input type="hidden" name="tab" value="topik">

<div class="user-search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" name="cari" placeholder="Cari topik materi..." value="<?= e($cari); ?>">
</div>

<select name="filter_kategori" onchange="this.form.submit()">
<option value="0">Semua Kategori</option>
<?php mysqli_data_seek($daftarKategori, 0); while ($k = mysqli_fetch_assoc($daftarKategori)): ?>
<option value="<?= $k['id']; ?>" <?= $filterKategori === (int) $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php endwhile; ?>
</select>

<button class="btn btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>

<?php if ($cari !== '' || $filterKategori > 0) { ?>
<a class="btn btn-sm btn-secondary" href="progress_materi.php?tab=topik">Reset</a>
<?php } ?>

</form>

<table class="admin-table">

<thead>
<tr>
<th>Kategori</th>
<th>Topik</th>
<th>XP</th>
<th>Dibuka</th>
<th>Diselesaikan</th>
<th>Tingkat Penyelesaian</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarTopik) == 0): ?>
<tr><td colspan="6" style="text-align:center;">Tidak ada topik yang cocok.</td></tr>
<?php else: while ($row = mysqli_fetch_assoc($daftarTopik)):
    $selesai = (int) $row['selesai'];
    $pct = $totalPengguna > 0 ? round(($selesai / $totalPengguna) * 100) : 0;
?>

<tr>
<td><?= e($row['kategori']); ?></td>
<td><?= e($row['judul']); ?></td>
<td><?= (int) $row['xp']; ?> XP</td>
<td><?= (int) $row['dibuka']; ?></td>
<td><?= $selesai; ?> dari <?= $totalPengguna; ?> pengguna</td>
<td>
<div style="display:flex;align-items:center;gap:10px;">
<div style="flex:1;height:8px;border-radius:20px;background:var(--border);overflow:hidden;">
<div style="height:100%;border-radius:20px;background:linear-gradient(90deg,#2ecc71,#1e9e56);width:<?= $pct; ?>%;"></div>
</div>
<small style="color:#2ecc71;font-weight:700;"><?= $pct; ?>%</small>
</div>
</td>
</tr>

<?php endwhile; endif; ?>

</tbody>

</table>

<?php endif; ?>

</div>

<?php require __DIR__ . "/partials/footer.php"; ?>

</main>

</div>

</body>

</html>
