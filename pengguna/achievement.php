<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$badges = getBadges($user['xp']);

$activeMenu = 'achievement';
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">

<title>Achievement</title>

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

<div class="achievement-page">

<h1>

🏆 Achievement

</h1>

<p>

Badge yang berhasil kamu kumpulkan.

</p>

<div class="badge-grid">

<?php

if(count($badges)>0){

foreach($badges as $badge){

?>

<div class="badge-card">

<div class="emoji">

<?= $badge['icon']; ?>

</div>

<h2>

<?= e($badge['nama']); ?>

</h2>

<p>

<?= e($badge['deskripsi']); ?>

</p>

</div>

<?php

}

}else{

?>

<div class="badge-card">

<h2>Belum Ada Badge</h2>

<p>

Selesaikan quiz untuk mendapatkan badge.

</p>

</div>

<?php } ?>

</div>

</div>

</main>

</div>

</body>

</html>
