<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$totalXP = $user['xp'];
$persen = $totalXP % 100;

$stmt = mysqli_prepare($conn, "
    SELECT h.*, k.nama AS nama_kategori
    FROM hasil_quiz h
    JOIN kategori k ON h.kategori_id = k.id
    WHERE h.user_id = ?
    ORDER BY h.tanggal DESC
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);

$totalQuiz = mysqli_num_rows($history);

$activeMenu = 'profil';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Profil Saya</title>

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

<a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="profile-page">

<div class="profile-header">

<div class="avatar-wrap">
<img src="assets/img/<?= !empty($user['foto']) ? e($user['foto']) : 'default.png'; ?>">
<span class="level-badge"><?= e($user['level']) ?></span>
</div>

<div>

<h1><?= e($user['nama']); ?></h1>

<p><i class="fa-solid fa-envelope"></i> <?= e($user['email']); ?></p>

<span class="level-pill">
<i class="fa-solid fa-star"></i>
Level <?= e($user['level']); ?>
</span>

</div>

</div>

<div class="profile-stat">

<div>
<div class="stat-icon orange"><i class="fa-solid fa-bolt"></i></div>
<h2><?= $totalXP ?></h2>
<p>Total XP</p>
</div>

<div>
<div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
<h2><?= $totalQuiz ?></h2>
<p>Quiz Selesai</p>
</div>

<div>
<div class="stat-icon purple"><i class="fa-solid fa-layer-group"></i></div>
<h2><?= e($user['level']) ?></h2>
<p>Level</p>
</div>

</div>

<div class="profile-section-title">
<i class="fa-solid fa-chart-line"></i>
<h3>Progress Level</h3>
</div>

<div class="progress-bar">

<div class="progress-fill"

style="width:<?= $persen ?>%;">

</div>

</div>

<div class="progress-label">
<span>Menuju level berikutnya</span>
<b><?= $persen ?>%</b>
</div>

<br>

<a href="edit_profil.php">

<button>

<i class="fa-solid fa-user-pen"></i>

Edit Profil

</button>

</a>

<hr>

<div class="profile-section-title">
<i class="fa-solid fa-clock-rotate-left"></i>
<h2>Riwayat Quiz</h2>
</div>

<table>

<tr>

<th>Tanggal</th>

<th>Kategori</th>

<th>Level</th>

<th>Skor</th>

<th>XP</th>

</tr>

<?php if (mysqli_num_rows($history) == 0): ?>

<tr>
<td colspan="5" style="text-align:center;">Belum ada riwayat quiz.</td>
</tr>

<?php else: while($row=mysqli_fetch_assoc($history)): ?>

<tr>

<td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>

<td><?= e($row['nama_kategori']); ?></td>

<td><?= e($row['level']); ?></td>

<td><?= (int) $row['score']; ?></td>

<td><?= (int) $row['xp']; ?></td>

</tr>

<?php endwhile; endif; ?>

</table>

</div>

</main>

</div>

</body>

</html>
