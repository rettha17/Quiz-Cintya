<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM hasil_quiz WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$totalQuiz = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

$totalXP = (int) $user['xp'];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM users WHERE xp > ?");
mysqli_stmt_bind_param($stmt, "i", $totalXP);
mysqli_stmt_execute($stmt);
$peringkat = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] + 1;

$totalPeserta = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

$foto = !empty($user['foto']) ? $user['foto'] : 'default.png';

$nilaiLulus = (int) getSetting($conn, 'sertifikat_nilai_lulus', '70');

$stmtAvg = mysqli_prepare($conn, "SELECT AVG(score) a FROM hasil_quiz WHERE user_id=?");
mysqli_stmt_bind_param($stmtAvg, "i", $user_id);
mysqli_stmt_execute($stmtAvg);
$rataSkorAwal = (int) round((float) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtAvg))['a']);

// Minimal 3 quiz selesai untuk mendapatkan sertifikat
if ($totalQuiz < 3) {
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

// Sertifikat baru butuh rata-rata skor minimal (nilai kelulusan) - sertifikat yang sudah terbit tetap tampil apa adanya
$stmtSudahAda = mysqli_prepare($conn, "SELECT id FROM sertifikat WHERE user_id=?");
mysqli_stmt_bind_param($stmtSudahAda, "i", $user_id);
mysqli_stmt_execute($stmtSudahAda);
$sudahPunyaSertifikat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSudahAda));

if (!$sudahPunyaSertifikat && $rataSkorAwal < $nilaiLulus) {
    die("
    <h2 align='center' style='margin-top:100px;font-family:sans-serif;'>
    Rata-rata skor kamu belum memenuhi nilai kelulusan untuk mendapatkan Sertifikat.
    <br>Rata-rata skor kamu saat ini: {$rataSkorAwal}, minimal yang dibutuhkan: {$nilaiLulus}.
    <br><br>
    <a href='kategori.php'>Coba Lagi</a>
    &nbsp;|&nbsp;
    <a href='dashboard.php'>Kembali ke Dashboard</a>
    </h2>
    ");
}

/* ===========================
   Tantangan (quiz) & Achievement - data asli
=========================== */

$progress = getProgress($conn, $user_id);
$persenTantangan = getPersenSelesai($progress);

$badges = getBadges($totalXP);
$totalAchievement = count($badges);

/* ===========================
   Penguasaan materi - data asli (materi_progress selesai)
=========================== */

$totalMateri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM materi_progress WHERE user_id=? AND status='selesai'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$materiSelesaiCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

$penguasaanMateri = $totalMateri > 0 ? (int) round(($materiSelesaiCount / $totalMateri) * 100) : 0;

$labelPenguasaan = labelPenguasaanMateri($penguasaanMateri);

/* ===========================
   Nilai akhir - rata-rata skor seluruh quiz
=========================== */

$stmt = mysqli_prepare($conn, "SELECT AVG(score) a, MAX(tanggal) t FROM hasil_quiz WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$agg = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$nilaiAkhir = (int) round((float) $agg['a']);
$tanggalSelesai = $agg['t'] ? date("d F Y", strtotime($agg['t'])) : '-';

/* ===========================
   Sertifikat: nomor unik & token verifikasi (sekali terbit, tidak berubah)
=========================== */

$sertifikat = getOrCreateSertifikat($conn, $user_id, $nilaiAkhir, $penguasaanMateri);
$noSertifikat = $sertifikat['certificate_number'];
$tanggalTerbit = date("d F Y", strtotime($sertifikat['issue_date']));
$verifikasiUrl = getVerificationUrl($sertifikat['verification_token']);

// Catat sekali saja per pengguna (bukan tiap kali halaman dibuka).
$stmt = mysqli_prepare($conn, "SELECT id FROM activity_log WHERE tipe='sertifikat' AND user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    logAktivitas(
        $conn,
        'sertifikat',
        'Sertifikat ' . e($noSertifikat) . ' berhasil diterbitkan untuk ' . e($user['nama']) . '.',
        'fa-certificate',
        'orange',
        $user_id
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sertifikat | StatQuest BPS</title>

<link rel="stylesheet" href="assets/css/certificate.css?v=<?= filemtime(__DIR__ . "/assets/css/certificate.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Playfair+Display:wght@700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

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

<div class="verified-ribbon">
<div class="verified-ribbon-tag">
<i class="fa-solid fa-check"></i>
<span>VERIFIED</span>
</div>
</div>

<div class="deco-icons-tr">
<i class="fa-solid fa-chart-column"></i>
<i class="fa-solid fa-chart-pie"></i>
</div>

<div class="logo-badge"><img src="assets/img/logo-bps.png" alt="Logo BPS"></div>

<div class="eyebrow"><i class="fa-solid fa-landmark"></i> Badan Pusat Statistik</div>

<h1 class="title">SERTIFIKAT</h1>

<div class="title-badge"><i class="fa-solid fa-star"></i> PENYELESAIAN <i class="fa-solid fa-star"></i></div>

<p class="cert-no-line"><span class="dot">&bull;</span> No. Sertifikat : <?= e($noSertifikat) ?> <span class="dot">&bull;</span></p>

<p class="lead">Dengan bangga diberikan kepada</p>

<div class="avatar-wrap">
<i class="fa-solid fa-wheat-awn wreath wreath-l"></i>
<div class="avatar-ring">
<img src="assets/img/<?= e($foto); ?>" alt="">
</div>
<i class="fa-solid fa-wheat-awn wreath wreath-r"></i>
</div>

<h2 class="recipient"><?= e($user['nama']); ?></h2>

<p class="lead">
Atas keberhasilannya menyelesaikan seluruh materi dan tantangan pada
<b>StatQuest BPS: Jelajah Statistik Indonesia</b>
</p>

<div class="stats">

<div class="stat">
<i class="fa-solid fa-ranking-star"></i>
<span class="num">#<?= e($peringkat) ?></span>
<span class="label">Peringkat</span>
<span class="sub">Dari <?= e($totalPeserta) ?> Peserta</span>
</div>

<div class="stat">
<i class="fa-solid fa-layer-group"></i>
<span class="num"><?= e($user['level']) ?></span>
<span class="label">Level</span>
<span class="sub">&nbsp;</span>
</div>

<div class="stat highlight">
<i class="fa-solid fa-bolt"></i>
<span class="num"><?= number_format($totalXP, 0, ',', '.') ?></span>
<span class="label">Total XP</span>
<span class="sub">Diperoleh</span>
</div>

<div class="stat">
<i class="fa-solid fa-circle-check"></i>
<span class="num"><?= e($totalQuiz) ?></span>
<span class="label">Tantangan Selesai</span>
<span class="sub"><?= $persenTantangan ?>%</span>
</div>

<div class="stat">
<i class="fa-solid fa-trophy"></i>
<span class="num"><?= e($totalAchievement) ?></span>
<span class="label">Achievement</span>
<span class="sub">Diperoleh</span>
</div>

</div>

<div class="info-banner">
<div class="info-banner-icon"><i class="fa-solid fa-shield-halved"></i></div>
<p>
Anda telah menyelesaikan seluruh materi, quiz, dan tantangan dengan hasil yang luar biasa!
Terus belajar dan menjadi <b>Generasi Cerdas Statistik Indonesia!</b>
</p>
<i class="fa-solid fa-arrow-trend-up info-banner-trend"></i>
</div>

<div class="meta-cards">

<div class="meta-card">
<i class="fa-regular fa-calendar-check"></i>
<div>
<span class="meta-card-label">Tanggal Selesai</span>
<b><?= e($tanggalSelesai) ?></b>
</div>
</div>

<div class="meta-card">
<i class="fa-regular fa-clock"></i>
<div>
<span class="meta-card-label">Tanggal Terbit</span>
<b><?= e($tanggalTerbit) ?></b>
</div>
</div>

<div class="meta-card">
<i class="fa-solid fa-medal"></i>
<div>
<span class="meta-card-label">Nilai Akhir</span>
<b><?= $nilaiAkhir ?> / 100</b>
</div>
</div>

<div class="meta-card">
<i class="fa-solid fa-chart-pie"></i>
<div>
<span class="meta-card-label">Penguasaan Materi</span>
<b><?= $penguasaanMateri ?>% <span class="meta-card-note">&bigstar; <?= e($labelPenguasaan) ?></span></b>
</div>
</div>

</div>

<div class="footer">

<div class="col">
<div class="signature">Tim StatQuest BPS</div>
<div class="line"></div>
<div class="label">Penyelenggara</div>
<b>Badan Pusat Statistik</b>
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

<?php if (getSetting($conn, 'sertifikat_qr_aktif', '1') === '1') { ?>
<div class="col qr-col">
<div class="qr-title">Verifikasi Sertifikat</div>
<div class="qr-box"><?= renderQrSvg($verifikasiUrl) ?></div>
<div class="qr-note">Pindai untuk memverifikasi keaslian</div>
</div>
<?php } ?>

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
