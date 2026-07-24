<?php
/**
 * Halaman verifikasi publik sertifikat StatQuest BPS.
 * Diakses lewat /v/{token} (rewrite di .htaccess) atau langsung v.php?token=...
 * Tidak butuh login - siapa pun yang scan QR sertifikat harus bisa membukanya.
 */
require __DIR__ . "/koneksi.php";
require __DIR__ . "/inc/functions.php";

$verifikasiOnlineAktif = getSetting($conn, 'sertifikat_verifikasi_online', '1') === '1';

$token = $_GET['token'] ?? '';
$token = preg_match('/^[a-f0-9]{32}$/', $token) ? $token : '';

$sertifikat = ($verifikasiOnlineAktif && $token !== '') ? getSertifikatByToken($conn, $token) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi Sertifikat | StatQuest BPS</title>

<link rel="stylesheet" href="pengguna/assets/css/verify.css?v=<?= filemtime(__DIR__ . "/pengguna/assets/css/verify.css"); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>

<div class="verify-page">

<div class="verify-brand">
<img src="pengguna/assets/img/login-badge-icon.png" alt="">
<span><b>StatQuest</b> BPS</span>
</div>

<div class="verify-card <?= ($sertifikat && $sertifikat['status'] === 'valid') ? 'is-valid' : 'is-invalid'; ?>">

<?php if (!$verifikasiOnlineAktif): ?>

<div class="verify-icon invalid"><i class="fa-solid fa-circle-pause"></i></div>
<h1>Verifikasi Online Nonaktif</h1>
<p class="verify-sub">Layanan verifikasi sertifikat online sedang dinonaktifkan sementara oleh Badan Pusat Statistik.</p>

<?php elseif ($sertifikat && $sertifikat['status'] === 'dicabut'): ?>

<div class="verify-icon invalid"><i class="fa-solid fa-ban"></i></div>
<h1>Sertifikat Dicabut</h1>
<p class="verify-sub">Sertifikat ini telah dicabut oleh Badan Pusat Statistik dan tidak lagi berlaku.</p>

<?php elseif ($sertifikat): ?>

<div class="verify-icon valid"><i class="fa-solid fa-circle-check"></i></div>
<h1>Sertifikat Valid</h1>
<p class="verify-sub">Sertifikat ini terdaftar resmi di sistem StatQuest BPS.</p>

<div class="verify-detail">

<div class="verify-row">
<span class="verify-label">Nama</span>
<span class="verify-value"><?= e($sertifikat['nama']); ?></span>
</div>

<div class="verify-row">
<span class="verify-label">Program</span>
<span class="verify-value">StatQuest BPS: Jelajah Statistik Indonesia</span>
</div>

<div class="verify-row">
<span class="verify-label">Nomor Sertifikat</span>
<span class="verify-value verify-mono"><?= e($sertifikat['certificate_number']); ?></span>
</div>

<div class="verify-row">
<span class="verify-label">Tanggal Terbit</span>
<span class="verify-value"><?= date("d F Y", strtotime($sertifikat['issue_date'])); ?></span>
</div>

<div class="verify-row">
<span class="verify-label">Nilai Akhir</span>
<span class="verify-value"><?= (int) $sertifikat['score']; ?> / 100</span>
</div>

<div class="verify-row">
<span class="verify-label">Penguasaan Materi</span>
<span class="verify-value"><?= (int) $sertifikat['mastery']; ?>%</span>
</div>

<div class="verify-row">
<span class="verify-label">Status</span>
<span class="verify-value verify-status-ok"><i class="fa-solid fa-shield-halved"></i> Terverifikasi</span>
</div>

</div>

<?php else: ?>

<div class="verify-icon invalid"><i class="fa-solid fa-circle-xmark"></i></div>
<h1>Sertifikat Tidak Ditemukan</h1>
<p class="verify-sub">Token verifikasi tidak valid atau sertifikat tidak terdaftar di sistem StatQuest BPS.</p>

<?php endif; ?>

</div>

<p class="verify-footer">Badan Pusat Statistik &middot; StatQuest BPS</p>

</div>

</body>
</html>
