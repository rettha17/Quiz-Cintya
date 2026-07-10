<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM hasil_quiz WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$totalQuiz = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

$totalXP = $user['xp'];
$tanggal = date("d F Y");

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM users WHERE xp > ?");
mysqli_stmt_bind_param($stmt, "i", $totalXP);
mysqli_stmt_execute($stmt);
$peringkat = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] + 1;

$totalPeserta = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

$foto = !empty($user['foto']) ? $user['foto'] : 'default.png';

$noSertifikat = sprintf("BPS/QC/%s/%04d", date("Y"), $user_id);
$kodeVerifikasi = strtoupper(substr(md5($user_id . $user['nama'] . $totalXP . 'quizbps'), 0, 10));

// Minimal 3 quiz selesai untuk mendapatkan sertifikat
if($totalQuiz < 3){
    die("
    <h2 align='center' style='margin-top:100px;font-family:sans-serif;'>
    Kamu harus menyelesaikan minimal 3 Quiz untuk mendapatkan Sertifikat.
    <br>Progress kamu saat ini: $totalQuiz / 3 quiz.
    <br><br>
    <a href='kategori.php'>Mulai Quiz</a>
    &nbsp;|&nbsp;
    <a href='dashboard.php'>Kembali ke Dashboard</a>
    </h2>
    ");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Sertifikat</title>

<link rel="stylesheet" href="assets/css/certificate.css?v=<?= filemtime(__DIR__ . "/assets/css/certificate.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Playfair+Display:wght@700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>

<div class="certificate">

<div class="border">

<div class="watermark">
<img src="assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>

<div class="frame-line"></div>

<div class="corner tl"></div>
<div class="corner tr"></div>
<div class="corner bl"></div>
<div class="corner br"></div>

<i class="fa-solid fa-star spark spark1"></i>
<i class="fa-solid fa-star spark spark2"></i>
<i class="fa-solid fa-sparkle spark spark3"></i>

<div class="logo-badge"><img src="assets/img/logo-bps.png" alt="Logo BPS"></div>

<div class="eyebrow"><i class="fa-solid fa-landmark"></i> Badan Pusat Statistik</div>

<h1 class="title">SERTIFIKAT</h1>

<div class="divider"><span></span><i class="fa-solid fa-diamond dot"></i><span></span></div>

<p class="lead">Dengan bangga diberikan kepada</p>

<div class="avatar-ring">
<img src="assets/img/<?= e($foto); ?>" alt="">
</div>

<h2 class="recipient"><?= e($user['nama']); ?></h2>

<p class="lead">Atas keberhasilannya menyelesaikan</p>

<p class="course">
Quiz Challenge
<span class="sub">Jelajah Statistik Indonesia</span>
</p>

<div class="stats">

<div class="stat">
<i class="fa-solid fa-ranking-star"></i>
<span class="num">#<?= e($peringkat) ?></span>
<span class="label">Peringkat</span>
</div>

<div class="stat">
<i class="fa-solid fa-layer-group"></i>
<span class="num"><?= e($user['level']) ?></span>
<span class="label">Level</span>
</div>

<div class="stat highlight">
<i class="fa-solid fa-bolt"></i>
<span class="num"><?= e($totalXP) ?></span>
<span class="label">Total XP</span>
</div>

<div class="stat">
<i class="fa-solid fa-circle-check"></i>
<span class="num"><?= e($totalQuiz) ?></span>
<span class="label">Quiz Selesai</span>
</div>

</div>

<p class="rank-note">Berada di posisi <b>#<?= e($peringkat) ?></b> dari <?= e($totalPeserta) ?> peserta terdaftar</p>

<div class="footer">

<div class="col">
<div class="line"></div>
<div class="label">Tanggal Terbit</div>
<b><?= $tanggal ?></b>
</div>

<div class="seal-wrap">
<div class="seal">
<div class="inner">
<i class="fa-solid fa-check check"></i>
<span class="txt">VERIFIED</span>
</div>
</div>
<div class="ribbon"><i class="tail"></i><i class="tail"></i></div>
</div>

<div class="col">
<div class="signature">Tim Quiz BPS</div>
<div class="line"></div>
<div class="label">Penyelenggara</div>
<b>Badan Pusat Statistik</b>
</div>

</div>

<div class="meta">
<span><i class="fa-solid fa-hashtag"></i> <?= e($noSertifikat) ?></span>
<span><i class="fa-solid fa-shield-halved"></i> <?= e($kodeVerifikasi) ?></span>
</div>

</div>

</div>

<div class="print">

<button onclick="window.print()">

🖨 Cetak Sertifikat

</button>

<a href="dashboard.php">

<button>

🏠 Dashboard

</button>

</a>

</div>

</body>

</html>
