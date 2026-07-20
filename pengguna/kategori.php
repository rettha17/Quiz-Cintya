<?php
require __DIR__ . "/../inc/auth.php";
require __DIR__ . "/../inc/functions.php";

if (isset($_GET['keluar'])) {
    unset($_SESSION['soal'], $_SESSION['soal_kategori'], $_SESSION['soal_level'], $_SESSION['skor'], $_SESSION['xp'], $_SESSION['nyawa'], $_SESSION['posisi_terakhir']);
    header("Location: kategori.php");
    exit;
}

$progress = getProgress($conn, $user_id);
$soalStats = getSoalStats($conn);

$resumeKategori = $_SESSION['soal_kategori'] ?? null;
$resumeLevel = $_SESSION['soal_level'] ?? null;
$resumeNomor = quizResumeNomor($resumeKategori, $resumeLevel);

$activeMenu = 'quiz';

$qcColorCycle = ['green', 'blue', 'purple'];
$qcIllustrations = [
    1 => 'qc-illus-statistika.jpg',
    2 => 'qc-illus-bps.jpg',
    3 => 'qc-illus-indikator.jpg',
];
$qcLevelEmoji = ['Mudah' => '😊', 'Sedang' => '😐', 'Sulit' => '😟'];
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

<div class="section-title">

<h2><i class="fa-solid fa-trophy" style="color:#FFC107;"></i> Quiz Challenge</h2>

</div>

<p class="qc-subtitle">
Pilih kategori, lalu pilih tingkat kesulitan (Mudah, Sedang, Sulit) untuk mulai mengerjakan quiz. Belum yakin? Baca dulu di <a href="materi.php">Materi Pembelajaran</a>.
</p>

<div class="qc-stats-bar">

<div class="qc-stat-item">
<span class="qc-stat-icon blue"><i class="fa-solid fa-book"></i></span>
<div class="qc-stat-text"><b><?= (int) $soalStats['total_kategori']; ?></b><span>Kategori Materi Utama</span></div>
</div>

<div class="qc-stat-item">
<span class="qc-stat-icon green"><i class="fa-solid fa-layer-group"></i></span>
<div class="qc-stat-text"><b><?= (int) $soalStats['total_level']; ?></b><span>Level Kesulitan</span></div>
</div>

<div class="qc-stat-item">
<span class="qc-stat-icon orange"><i class="fa-solid fa-circle-question"></i></span>
<div class="qc-stat-text"><b><?= (int) $soalStats['total_soal']; ?></b><span>Total Soal Tersedia</span></div>
</div>

<div class="qc-stat-item">
<span class="qc-stat-icon purple"><i class="fa-solid fa-trophy"></i></span>
<div class="qc-stat-text"><b><?= number_format((int) ($user['xp'] ?? 0)); ?></b><span>XP Didapatkan</span></div>
</div>

</div>

<div class="cards qc-cards">

<?php $qcI = 0; foreach ($progress as $kategoriId => $kat):
    $qcColor = $qcColorCycle[$qcI % count($qcColorCycle)];
    $qcI++;
    $stat = $soalStats['kategori'][$kategoriId] ?? ['soal_per_level' => ['Mudah' => 0, 'Sedang' => 0, 'Sulit' => 0], 'total_soal' => 0, 'total_topik' => 0];
?>

<div class="card qc-card">

<div class="qc-card-head">

<div class="qc-card-info">

<div class="card-top">

<div class="icon <?= $qcColor; ?>">

<i class="fa-solid <?= e($kat['icon']); ?>"></i>

</div>

</div>

<h3 class="qc-card-title-<?= $qcColor; ?>"><?= e($kat['nama']); ?></h3>

<p><?= e($kat['deskripsi']); ?></p>

</div>

<?php if (!empty($qcIllustrations[$kategoriId])): ?>
<div class="qc-card-illustration">
<img src="assets/img/<?= e($qcIllustrations[$kategoriId]); ?>" alt="<?= e($kat['nama']); ?>">
</div>
<?php endif; ?>

</div>

<div class="qc-level-list">

<?php foreach (levelList() as $lvl):
    $count = $stat['soal_per_level'][$lvl];
    $percent = min(100, round($count / 50 * 100));
?>

<div class="qc-level-row qc-level-<?= strtolower($lvl); ?>">
<div class="qc-level-label"><?= $qcLevelEmoji[$lvl]; ?> <b><?= $lvl; ?></b></div>
<div class="qc-level-track"><div class="qc-level-fill" style="width:<?= $percent; ?>%"></div></div>
<span class="qc-level-count"><?= $count; ?> Soal</span>
</div>

<?php endforeach; ?>

</div>

<div class="qc-card-footer">

<div class="qc-footer-stat">
<span class="qc-footer-icon blue"><i class="fa-solid fa-book"></i></span>
<div class="qc-footer-text"><b><?= (int) $stat['total_topik']; ?></b><span>Topik Materi</span></div>
</div>

<div class="qc-footer-divider"></div>

<div class="qc-footer-stat">
<span class="qc-footer-icon orange"><i class="fa-solid fa-circle-question"></i></span>
<div class="qc-footer-text"><b><?= (int) $stat['total_soal']; ?></b><span>Total Quiz Soal</span></div>
</div>

</div>

<button onclick="bukaLevel(<?= $kategoriId; ?>)">
    Pilih Level <i class="fa-solid fa-arrow-right"></i>
</button>
</div>

<?php endforeach; ?>

</div>

<div class="qc-cta-banner">
<div class="qc-cta-icon"><i class="fa-solid fa-trophy"></i></div>
<div class="qc-cta-text">
<b>Tantang diri kamu dan raih XP sebanyak-banyaknya!</b>
<span>Semakin banyak quiz dikerjakan, semakin tinggi peringkatmu di leaderboard.</span>
</div>
<a href="leaderboard.php" class="qc-cta-btn"><i class="fa-solid fa-chart-simple"></i> Lihat Leaderboard <i class="fa-solid fa-chevron-right"></i></a>
</div>

</main>

</div>

<!-- Modal Level -->
<div id="modalLevel" class="modal">

    <div class="modal-content">

        <span class="close" onclick="tutupLevel()">&times;</span>

        <h2>Pilih Level</h2>

        <a id="mudah">
            <button class="btn-level green"><?= $qcLevelEmoji['Mudah']; ?> Mudah</button>
        </a>

        <a id="sedang">
            <button class="btn-level orange"><?= $qcLevelEmoji['Sedang']; ?> Sedang</button>
        </a>

        <a id="sulit">
            <button class="btn-level red"><?= $qcLevelEmoji['Sulit']; ?> Sulit</button>
        </a>

    </div>

</div>

<script>

const RESUME_KATEGORI = <?= json_encode($resumeKategori) ?>;
const RESUME_LEVEL = <?= json_encode($resumeLevel) ?>;
const RESUME_NOMOR = <?= json_encode($resumeNomor) ?>;

const LEVEL_TOMBOL = {
    mudah: { label: "Mudah", teks: "😊 Mudah" },
    sedang: { label: "Sedang", teks: "😐 Sedang" },
    sulit: { label: "Sulit", teks: "😟 Sulit" },
};

function bukaLevel(id){

    document.getElementById("modalLevel").style.display="flex";

    for (const key in LEVEL_TOMBOL) {
        const { label, teks } = LEVEL_TOMBOL[key];
        const link = document.getElementById(key);
        let href = "quiz.php?kategori="+id+"&level="+label;

        if (RESUME_NOMOR !== null && String(RESUME_KATEGORI) === String(id) && RESUME_LEVEL === label) {
            href += "&no="+RESUME_NOMOR;
            link.querySelector("button").textContent = "▶ Lanjutkan (Soal " + (RESUME_NOMOR + 1) + ")";
        } else {
            link.querySelector("button").textContent = teks;
        }

        link.href = href;
    }

}

function tutupLevel(){

    document.getElementById("modalLevel").style.display="none";

}

</script>

</body>

</html>
