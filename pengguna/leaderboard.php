<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$activeMenu = 'leaderboard';

$daftarKategori = mysqli_query($conn, "SELECT id, nama FROM kategori ORDER BY nama");
$kategoriOptions = [];
while ($k = mysqli_fetch_assoc($daftarKategori)) {
    $kategoriOptions[] = $k;
}

$filterKategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$filterPeriode = leaderboardPeriodeValid($_GET['periode'] ?? 'sepanjang');

$ranking = getLeaderboardRanking($conn, $filterKategori, $filterPeriode, 10);
$myRank = getUserRank($conn, $user_id, $filterKategori, $filterPeriode);
$myStats = getUserLeaderboardStats($conn, $user_id, $filterKategori, $filterPeriode);
$topKategori = getUserTopKategori($conn, $user_id, 3);
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Leaderboard | StatQuest BPS</title>

<script>
document.documentElement.setAttribute("data-theme", localStorage.getItem("quizbps-theme") || "light");
</script>

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

<div class="lb-page">

<div class="lb-header">

<div class="lb-header-left">

<div class="lb-header-title">
<span class="lb-trophy-icon"><i class="fa-solid fa-trophy"></i></span>
<h1>Leaderboard</h1>
</div>

<p>Bersaing dengan seluruh pemain <b>StatQuest BPS</b> dan raih peringkat terbaik!</p>

</div>

<div class="lb-podium-deco">

<i class="fa-solid fa-star lb-deco-star ds-1"></i>
<i class="fa-solid fa-star lb-deco-star ds-2"></i>
<span class="lb-deco-dot dd-1"></span>
<span class="lb-deco-dot dd-2"></span>

<svg class="lb-trend-line" viewBox="0 0 120 60" aria-hidden="true">
<polyline points="4,50 30,35 55,42 80,15 116,6" fill="none" stroke="#22C55E" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>

<div class="lb-podium-trophy"><i class="fa-solid fa-trophy"></i></div>

<div class="lb-podium-bars">
<div class="lb-podium-bar bar-2"><span>2</span></div>
<div class="lb-podium-bar bar-1"><span>1</span></div>
<div class="lb-podium-bar bar-3"><span>3</span></div>
</div>

</div>

</div>

<form method="GET" class="lb-filters">

<div class="lb-filter-group">
<label><i class="fa-solid fa-folder"></i> Kategori</label>
<select name="kategori" onchange="this.form.submit()">
<option value="0" <?= $filterKategori === 0 ? 'selected' : ''; ?>>Semua Kategori</option>
<?php foreach ($kategoriOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $filterKategori === (int) $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>
</div>

<div class="lb-filter-group">
<label><i class="fa-regular fa-calendar"></i> Periode</label>
<select name="periode" onchange="this.form.submit()">
<option value="hari_ini" <?= $filterPeriode === 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
<option value="minggu_ini" <?= $filterPeriode === 'minggu_ini' ? 'selected' : ''; ?>>Minggu Ini</option>
<option value="bulan_ini" <?= $filterPeriode === 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
<option value="sepanjang" <?= $filterPeriode === 'sepanjang' ? 'selected' : ''; ?>>Sepanjang Waktu</option>
</select>
</div>

</form>

<div class="lb-body">

<div class="lb-main">

<div class="lb-table-wrap">

<table class="lb-table">

<thead>
<tr>
<th>Rank</th>
<th>Pemain</th>
<th>Level</th>
<th>Total XP</th>
<th>Quiz Benar</th>
<th>Quiz Salah</th>
<th>Akurasi</th>
<th>Rata-rata Durasi</th>
<th>Streak</th>
</tr>
</thead>

<tbody id="lbRows">
<?php require __DIR__ . "/leaderboard_rows.php"; ?>
</tbody>

</table>

</div>

<div class="lb-updated"><i class="fa-solid fa-arrows-rotate"></i> Data diperbarui secara real-time &middot; <span id="lbUpdatedTime">baru saja</span></div>

</div>

<div class="lb-sidebar">

<div id="lbSidebar">
<?php require __DIR__ . "/leaderboard_sidebar.php"; ?>
</div>

<div class="lb-tips-card">
<div class="lb-tips-icon"><i class="fa-solid fa-lightbulb"></i></div>
<div class="lb-tips-text">
<b>Tips</b>
<p>Kerjakan quiz setiap hari untuk meningkatkan XP dan pertahankan streak kamu!</p>
</div>
<div class="lb-tips-target"><i class="fa-solid fa-bullseye"></i></div>
</div>

</div>

</div>

</div>

<?php require __DIR__ . "/../inc/footer.php"; ?>

</main>

</div>

<script>
(function(){
    var params = new URLSearchParams(window.location.search);

    function refreshLeaderboard(){
        fetch("leaderboard_data.php?" + params.toString(), { headers: { "X-Requested-With": "fetch" } })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(data){
                if (!data) return;
                document.getElementById("lbRows").innerHTML = data.rows_html;
                document.getElementById("lbSidebar").innerHTML = data.sidebar_html;
                var timeEl = document.getElementById("lbUpdatedTime");
                if (timeEl) timeEl.textContent = data.updated_at;
            })
            .catch(function(){});
    }

    setInterval(refreshLeaderboard, 20000);
})();
</script>

</body>

</html>
