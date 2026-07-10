<?php
require __DIR__ . "/../inc/auth.php";
require __DIR__ . "/../inc/functions.php";

$progress = getProgress($conn, $user_id);
$persenSelesai = getPersenSelesai($progress);

$totalMateri = count($progress);

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

$totalQuiz = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM hasil_quiz WHERE user_id=$user_id"));

$badges = getBadges($user['xp']);
$totalBadge = count($badges);

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
   XP per hari, 7 hari terakhir (untuk grafik)
=========================== */

$stmt = mysqli_prepare($conn, "
    SELECT DATE(tanggal) AS tgl, SUM(xp) AS total_xp
    FROM hasil_quiz
    WHERE user_id = ? AND tanggal >= (CURDATE() - INTERVAL 6 DAY)
    GROUP BY DATE(tanggal)
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$xpPerTanggal = [];
while ($row = mysqli_fetch_assoc($res)) {
    $xpPerTanggal[$row['tgl']] = (int) $row['total_xp'];
}

$labelHari = [];
$dataXp = [];
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHari[] = $namaHari[date('w', strtotime($tgl))];
    $dataXp[] = $xpPerTanggal[$tgl] ?? 0;
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

<title>Dashboard | Quiz Challenge BPS</title>

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

<!-- ================= GREETING ================= -->

<div class="dashboard-greeting">

<h1>

<?= $sapaan; ?>,

<?= e($user['nama']); ?>

👋

</h1>

<p>

Selamat datang di

<b>Quiz Challenge : Jelajah Statistik Indonesia</b>

</p>

</div>

<!-- ================= WELCOME ================= -->

<section class="welcome">
<section class="chart-area">

<div class="section-title">

<h2>Grafik Progress Belajar (XP 7 Hari Terakhir)</h2>

</div>

<div class="chart-card">

<canvas id="progressChart"></canvas>

</div>

</section>

<div class="streak">

📊

<strong><?= $persenSelesai; ?>% Materi Dikuasai</strong>

</div>

</section>

<!-- ================= STATISTIK ================= -->

<section class="summary">

<div class="box">

<div class="box-icon blue">

<i class="fa-solid fa-book"></i>

</div>

<h2><?= $totalMateri; ?></h2>

<p>Total Materi</p>

</div>

<div class="box">

<div class="box-icon green">

<i class="fa-solid fa-gamepad"></i>

</div>

<h2><?= $totalQuiz; ?></h2>

<p>Total Quiz</p>

</div>

<div class="box">

<div class="box-icon orange">

<i class="fa-solid fa-star"></i>

</div>

<h2><?= e($user['xp']); ?></h2>

<p>Total XP</p>

</div>

<div class="box">

<div class="box-icon purple">

<i class="fa-solid fa-award"></i>

</div>

<h2><?= $totalBadge; ?></h2>

<p>Badge</p>

</div>

</section>

<!-- ================= QUICK MENU ================= -->

<section class="quick-menu">

<h2>Menu Cepat</h2>

<div class="quick-grid">

<a href="materi.php">

<div class="quick-card">

<i class="fa-solid fa-book-open"></i>

<h4>Materi</h4>

<p>Belajar Dulu</p>

</div>

</a>

<a href="kategori.php">

<div class="quick-card">

<i class="fa-solid fa-gamepad"></i>

<h4>Quiz</h4>

<p>Mainkan Quiz</p>

</div>

</a>

<a href="leaderboard.php">

<div class="quick-card">

<i class="fa-solid fa-ranking-star"></i>

<h4>Ranking</h4>

<p>Lihat Peringkat</p>

</div>

</a>

<a href="achievement.php">

<div class="quick-card">

<i class="fa-solid fa-award"></i>

<h4>Badge</h4>

<p>Pencapaian</p>

</div>

</a>

<a href="sertifikat.php">

<div class="quick-card">

<i class="fa-solid fa-certificate"></i>

<h4>Sertifikat</h4>

<p>Unduh Sertifikat</p>

</div>

</a>

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

</main>

</div>

<script>

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

<!-- ================= CHART PROGRESS ================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('progressChart');

if(ctx){

new Chart(ctx,{

type:'line',

data:{

labels: <?= json_encode($labelHari); ?>,

datasets:[{

label:'XP',

data: <?= json_encode($dataXp); ?>,

fill:true,

borderWidth:3,

tension:.4,

backgroundColor:'rgba(21,101,192,.1)',

borderColor:'#1565C0'

}]

},

options:{

responsive:true,

plugins:{

legend:{
display:false
}

},

scales:{

y:{
beginAtZero:true
}

}

}

});

}

</script>

<script src="assets/js/script.js?v=<?= filemtime(__DIR__ . '/assets/js/script.js'); ?>"></script>

</body>

</html>
