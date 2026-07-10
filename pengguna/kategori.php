<?php
require __DIR__ . "/../inc/auth.php";
require __DIR__ . "/../inc/functions.php";

$progress = getProgress($conn, $user_id);

$activeMenu = 'quiz';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Quiz Challenge | Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">

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

<a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="section-title">

<h2>Quiz Challenge</h2>

</div>

<p style="color:#666;margin-bottom:25px;">
Pilih kategori, lalu pilih tingkat kesulitan (Mudah, Sedang, Sulit) untuk mulai mengerjakan quiz. Belum yakin? Baca dulu di <a href="materi.php" style="color:#1565C0;font-weight:600;">Materi Pembelajaran</a>.
</p>

<div class="cards">

<?php foreach ($progress as $kategoriId => $kat): ?>

<div class="card">

<div class="card-top">

<div class="icon green">

<i class="fa-solid <?= e($kat['icon']); ?>"></i>

</div>

</div>

<h3><?= e($kat['nama']); ?></h3>

<p><?= e($kat['deskripsi']); ?></p>

<div class="level-status">

<?php foreach ($kat['levels'] as $lvl => $best): ?>

<span class="badge <?= strtolower($lvl); ?>">

<?= $lvl; ?>

<?= $best !== null ? "✅ $best" : "· Belum"; ?>

</span>

<?php endforeach; ?>

</div>

<button onclick="bukaLevel(<?= $kategoriId; ?>)">
    Pilih Level
</button>
</div>

<?php endforeach; ?>

</div>

</main>

</div>

<!-- Modal Level -->
<div id="modalLevel" class="modal">

    <div class="modal-content">

        <span class="close" onclick="tutupLevel()">&times;</span>

        <h2>Pilih Level</h2>

        <a id="mudah">
            <button class="btn-level green">🟢 Mudah</button>
        </a>

        <a id="sedang">
            <button class="btn-level orange">🟡 Sedang</button>
        </a>

        <a id="sulit">
            <button class="btn-level red">🔴 Sulit</button>
        </a>

    </div>

</div>

<script>

function bukaLevel(id){

    document.getElementById("modalLevel").style.display="flex";

    document.getElementById("mudah").href="quiz.php?kategori="+id+"&level=Mudah";

    document.getElementById("sedang").href="quiz.php?kategori="+id+"&level=Sedang";

    document.getElementById("sulit").href="quiz.php?kategori="+id+"&level=Sulit";

}

function tutupLevel(){

    document.getElementById("modalLevel").style.display="none";

}

</script>

</body>

</html>
