<?php
require_once "auth.php";

$activeMenu = "game";

// Jumlah soal
$jmlSoal = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM soal"));

// Jumlah kategori
$jmlKategori = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM kategori"));

// Jumlah materi
$jmlMateri = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM materi"));
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Game Editor</title>

<link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . "/assets/admin.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

.main{

margin-left:84px;

padding:30px;

transition:margin-left .25s ease;

}

@media(max-width:900px){

.main{

margin-left:0;

padding:20px;

padding-top:80px;

}

}

.title{

display:flex;

justify-content:space-between;

align-items:center;

margin-bottom:30px;

}

.preview-btn{

background:#2563eb;

color:white;

padding:12px 25px;

border:none;

border-radius:10px;

font-size:16px;

cursor:pointer;

}

.preview-btn:hover{

background:#1d4ed8;

}

.stats{

display:grid;

grid-template-columns:repeat(3,1fr);

gap:20px;

margin-bottom:40px;

}

.box{

background:white;

padding:25px;

border-radius:15px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

text-align:center;

}

.box h2{

font-size:35px;

color:#2563eb;

margin:0;

}

.box p{

margin-top:10px;

color:#666;

}

.flow{

display:flex;

align-items:center;

justify-content:center;

gap:25px;

flex-wrap:wrap;

}

.node{

width:140px;

height:140px;

border-radius:20px;

background:white;

display:flex;

flex-direction:column;

justify-content:center;

align-items:center;

font-size:18px;

box-shadow:0 8px 25px rgba(0,0,0,.1);

transition:.3s;

}

.node:hover{

transform:translateY(-8px);

}

.node i{

font-size:45px;

margin-bottom:15px;

}

.arrow{

font-size:40px;

color:#2563eb;

}

.start{

background:#10b981;

color:white;

}

.finish{

background:#ef4444;

color:white;

}

.info{

margin-top:50px;

background:white;

padding:25px;

border-radius:15px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

}

.info h3{

margin-bottom:15px;

}

</style>

</head>

<body>

<?php include "partials/nav.php"; ?>

<div class="main">

<div class="page-watermark">
<div class="wm-inner">
<img src="../pengguna/assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="title">

<div>

<h1>🎮 Game Editor</h1>

<p>Atur alur Quiz Challenge BPS.</p>

</div>

<a href="preview_game.php">

<button class="preview-btn">

<i class="fa-solid fa-play"></i>

Preview Game

</button>

</a>

</div>

<div class="stats">

<div class="box">

<h2><?= $jmlKategori ?></h2>

<p>Total Kategori</p>

</div>

<div class="box">

<h2><?= $jmlSoal ?></h2>

<p>Total Soal</p>

</div>

<div class="box">

<h2><?= $jmlMateri ?></h2>

<p>Total Materi</p>

</div>

</div>

<div class="flow">

<div class="node start">

<i class="fa-solid fa-flag-checkered"></i>

START

</div>

<div class="arrow">

➡

</div>

<div class="node">

<i class="fa-solid fa-layer-group"></i>

Pilih Kategori

</div>

<div class="arrow">

➡

</div>

<div class="node">

<i class="fa-solid fa-signal"></i>

Pilih Level

</div>

<div class="arrow">

➡

</div>

<div class="node">

<i class="fa-solid fa-fire"></i>

Padamkan Api (Quiz)

</div>

<div class="arrow">

➡

</div>

<div class="node">

<i class="fa-solid fa-star"></i>

Hasil &amp; XP

</div>

<div class="arrow">

➡

</div>

<div class="node finish">

<i class="fa-solid fa-trophy"></i>

FINISH

</div>

</div>

<div class="info">

<h3>📋 Alur Game</h3>

<p>

1. User memilih kategori di halaman Quiz Challenge.

</p>

<p>

2. User memilih level (🟢 Mudah / 🟡 Sedang / 🔴 Sulit).

</p>

<p>

3. Soal muncul satu per satu bertema petugas pemadam kebakaran — setiap opsi jawaban adalah jendela gedung yang terbakar.

</p>

<p>

4. Jawaban benar → api mengecil jadi asap, skor bertambah. Jawaban salah → api membesar, -1 nyawa.

</p>

<p>

5. Nyawa habis atau soal sudah habis → lanjut ke halaman Hasil.

</p>

<p>

6. Halaman Hasil menampilkan skor akhir, XP yang didapat, dan badge pencapaian.

</p>

</div>

</div>

</body>

</html>