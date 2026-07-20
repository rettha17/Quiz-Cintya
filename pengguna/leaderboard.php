<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$mode = (isset($_GET['mode']) && $_GET['mode'] === 'level') ? 'level' : 'xp';

$daftarKategori = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama");
$kategoriOptions = [];
while ($k = mysqli_fetch_assoc($daftarKategori)) {
    $kategoriOptions[] = $k;
}

$levelPilihan = levelList();

$filterKategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : ($kategoriOptions[0]['id'] ?? 0);
$filterLevel = (isset($_GET['level']) && in_array($_GET['level'], $levelPilihan, true)) ? $_GET['level'] : 'Mudah';

if ($mode === 'level') {

    $stmt = mysqli_prepare($conn, "
        SELECT u.nama, u.foto, MAX(h.score) AS skor_terbaik
        FROM hasil_quiz h
        JOIN users u ON u.id = h.user_id
        WHERE h.kategori_id = ? AND h.level = ?
        GROUP BY h.user_id, u.nama, u.foto
        ORDER BY skor_terbaik DESC
        LIMIT 50
    ");
    mysqli_stmt_bind_param($stmt, "is", $filterKategori, $filterLevel);
    mysqli_stmt_execute($stmt);
    $ranking = mysqli_stmt_get_result($stmt);

} else {

    $ranking = mysqli_query($conn, "
        SELECT nama, foto, level, xp
        FROM users
        ORDER BY xp DESC, level DESC
        LIMIT 50
    ");

}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">

<title>Leaderboard</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<?php require_once __DIR__ . "/../inc/nav.php"; ?>

<main class="content">

<div class="page-watermark">
<div class="wm-inner">
<img src="assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<div class="leaderboard">

<h1>🏆 Leaderboard</h1>

<p>Ranking pemain berdasarkan XP, atau lihat siapa terbaik di kategori & level tertentu.</p>

<div class="leaderboard-tabs">

<a href="leaderboard.php?mode=xp" class="<?= $mode === 'xp' ? 'active' : ''; ?>">Ranking XP Keseluruhan</a>

<a href="leaderboard.php?mode=level&kategori=<?= $filterKategori; ?>&level=<?= urlencode($filterLevel); ?>" class="<?= $mode === 'level' ? 'active' : ''; ?>">Ranking per Kategori &amp; Level</a>

</div>

<?php if ($mode === 'level') { ?>

<form method="GET" class="leaderboard-filter">

<input type="hidden" name="mode" value="level">

<select name="kategori" onchange="this.form.submit()">
<?php foreach ($kategoriOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $filterKategori == $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>

<select name="level" onchange="this.form.submit()">
<?php foreach ($levelPilihan as $lvl) { ?>
<option value="<?= $lvl; ?>" <?= $filterLevel === $lvl ? 'selected' : ''; ?>><?= $lvl; ?></option>
<?php } ?>
</select>

</form>

<?php } ?>

<table>

<thead>

<tr>

<th>Rank</th>

<th>Foto</th>

<th>Nama</th>

<?php if ($mode === 'level') { ?>

<th>Skor Terbaik</th>

<?php } else { ?>

<th>Level</th>

<th>XP</th>

<?php } ?>

</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($ranking) == 0) { ?>

<tr>
<td colspan="<?= $mode === 'level' ? 4 : 5; ?>" style="text-align:center;">Belum ada data untuk kategori &amp; level ini.</td>
</tr>

<?php } else {

$no=1;

while($row=mysqli_fetch_assoc($ranking)){

?>

<tr>

<td>

<?php

if($no==1){

echo "🥇";

}elseif($no==2){

echo "🥈";

}elseif($no==3){

echo "🥉";

}else{

echo $no;

}

?>

</td>

<td>

<img
src="assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>"
class="rank-photo">

</td>

<td>

<?= e($row['nama']); ?>

</td>

<?php if ($mode === 'level') { ?>

<td>

<strong><?= (int) $row['skor_terbaik']; ?></strong>

</td>

<?php } else { ?>

<td>

Level <?= e($row['level']); ?>

</td>

<td>

<strong>

<?= e($row['xp']); ?>

XP

</strong>

</td>

<?php } ?>

</tr>

<?php

$no++;

}

}

?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>
