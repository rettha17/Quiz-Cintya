<?php
require_once "auth.php";

$rankMode = (isset($_GET['mode']) && $_GET['mode'] === 'level') ? 'level' : 'xp';

$levelPilihan = ['Mudah', 'Sedang', 'Sulit'];

$daftarKategoriRank = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama");
$kategoriRankOptions = [];
while ($k = mysqli_fetch_assoc($daftarKategoriRank)) {
    $kategoriRankOptions[] = $k;
}

$filterKategoriRank = isset($_GET['kategori']) ? (int) $_GET['kategori'] : ($kategoriRankOptions[0]['id'] ?? 0);
$filterLevelRank = (isset($_GET['level']) && in_array($_GET['level'], $levelPilihan, true)) ? $_GET['level'] : 'Mudah';

if ($rankMode === 'level') {

    $stmt = mysqli_prepare($conn, "
        SELECT u.nama, u.foto, MAX(h.score) AS skor_terbaik
        FROM hasil_quiz h
        JOIN users u ON u.id = h.user_id
        WHERE h.kategori_id = ? AND h.level = ?
        GROUP BY h.user_id, u.nama, u.foto
        ORDER BY skor_terbaik DESC
        LIMIT 20
    ");
    mysqli_stmt_bind_param($stmt, "is", $filterKategoriRank, $filterLevelRank);
    mysqli_stmt_execute($stmt);
    $topRanking = mysqli_stmt_get_result($stmt);

} else {

    $topRanking = mysqli_query($conn, "SELECT nama, foto, xp, level FROM users ORDER BY xp DESC, level DESC LIMIT 20");

}

$activeMenu = 'ranking';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ranking Pemain | Admin Quiz Challenge BPS</title>

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

<h1>Ranking Pemain</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<div class="admin-panel">

<h2>🏆 Top Ranking Pemain</h2>

<div class="admin-tabs">

<a href="ranking.php?mode=xp" class="<?= $rankMode === 'xp' ? 'active' : ''; ?>">Ranking XP Keseluruhan</a>

<a href="ranking.php?mode=level&kategori=<?= $filterKategoriRank; ?>&level=<?= urlencode($filterLevelRank); ?>" class="<?= $rankMode === 'level' ? 'active' : ''; ?>">Ranking per Kategori &amp; Level</a>

</div>

<?php if ($rankMode === 'level') { ?>

<form method="GET" class="admin-filter">

<input type="hidden" name="mode" value="level">

<select name="kategori" onchange="this.form.submit()">
<?php foreach ($kategoriRankOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $filterKategoriRank == $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>

<select name="level" onchange="this.form.submit()">
<?php foreach ($levelPilihan as $lvl) { ?>
<option value="<?= $lvl; ?>" <?= $filterLevelRank === $lvl ? 'selected' : ''; ?>><?= $lvl; ?></option>
<?php } ?>
</select>

</form>

<?php } ?>

<table class="admin-table">

<thead>
<tr>
<th>Rank</th>
<th>Foto</th>
<th>Nama</th>
<?php if ($rankMode === 'level') { ?>
<th>Skor Terbaik</th>
<?php } else { ?>
<th>Level</th>
<th>XP</th>
<?php } ?>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($topRanking) == 0) { ?>
<tr><td colspan="<?= $rankMode === 'level' ? 4 : 5; ?>" style="text-align:center;">Belum ada data untuk ditampilkan.</td></tr>
<?php } else { $no = 1; while ($row = mysqli_fetch_assoc($topRanking)) { ?>

<tr>
<td>
<?php
if ($no == 1) { echo "🥇"; }
elseif ($no == 2) { echo "🥈"; }
elseif ($no == 3) { echo "🥉"; }
else { echo $no; }
?>
</td>
<td>
<img src="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>" class="rank-photo">
</td>
<td><?= e($row['nama']); ?></td>
<?php if ($rankMode === 'level') { ?>
<td><strong><?= (int) $row['skor_terbaik']; ?></strong></td>
<?php } else { ?>
<td>Level <?= (int) $row['level']; ?></td>
<td><strong><?= (int) $row['xp']; ?> XP</strong></td>
<?php } ?>
</tr>

<?php $no++; } } ?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>
