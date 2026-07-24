<?php
require __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$kategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$level = levelValid($_GET['level'] ?? 'Mudah');
$kehabisanNyawa = isset($_GET['habis']);

$progressKey = quizProgressKey($kategori, $level);
$checkpoint = $_SESSION['quiz_checkpoints'][$progressKey] ?? [];

$skor = $checkpoint['skor'] ?? 0;
$xpDidapat = $checkpoint['xp'] ?? 0;
$jumlahBenar = $checkpoint['jumlah_benar'] ?? 0;
$jumlahSalah = $checkpoint['jumlah_salah'] ?? 0;
$durasiDetik = isset($checkpoint['waktu_mulai']) ? max(0, time() - (int) $checkpoint['waktu_mulai']) : 0;

$kategoriInfo = null;
if ($kategori > 0) {
    $stmt = mysqli_prepare($conn, "SELECT nama FROM kategori WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $kategori);
    mysqli_stmt_execute($stmt);
    $kategoriInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/* ===========================
   Simpan hasil quiz & tambah XP
   (hanya sekali, saat baru selesai main - dijaga lewat checkpoint kategori+level ini)
=========================== */

if ($kategori > 0 && isset($checkpoint['skor'])) {

    $xpLama = (int) $user['xp'];
    $levelLama = (int) $user['level'];

    $stmt = mysqli_prepare($conn, "
        INSERT INTO hasil_quiz (user_id, kategori_id, level, score, xp, jumlah_benar, jumlah_salah, durasi_detik)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iisiiiii", $user_id, $kategori, $level, $skor, $xpDidapat, $jumlahBenar, $jumlahSalah, $durasiDetik);
    mysqli_stmt_execute($stmt);

    $up = mysqli_prepare($conn, "UPDATE users SET xp = xp + ? WHERE id = ?");
    mysqli_stmt_bind_param($up, "ii", $xpDidapat, $user_id);
    mysqli_stmt_execute($up);

    $data = mysqli_query($conn, "SELECT xp FROM users WHERE id='$user_id'");
    $userBaru = mysqli_fetch_assoc($data);

    $levelBaru = floor($userBaru['xp'] / 100) + 1;

    $up2 = mysqli_prepare($conn, "UPDATE users SET level=? WHERE id=?");
    mysqli_stmt_bind_param($up2, "ii", $levelBaru, $user_id);
    mysqli_stmt_execute($up2);

    $user['xp'] = $userBaru['xp'];
    $user['level'] = $levelBaru;

    $namaKategoriLog = $kategoriInfo['nama'] ?? 'kategori';
    logAktivitas(
        $conn,
        'quiz_selesai',
        e($user['nama']) . ' menyelesaikan Quiz ' . e($namaKategoriLog) . ' (' . e($level) . ') dengan skor ' . (int) $skor . '.',
        'fa-gamepad',
        'blue',
        $user_id
    );

    if ($levelBaru > $levelLama) {
        logAktivitas($conn, 'level_up', e($user['nama']) . ' naik ke Level ' . (int) $levelBaru . '.', 'fa-arrow-up', 'orange', $user_id);
    }

    cekBadgeBaru($conn, $xpLama, (int) $userBaru['xp'], $user_id, $user['nama']);
}

/* ===========================
   Badge berdasarkan skor quiz ini
=========================== */

if ($skor == 100) {
    $badge = "🥇 Master Statistik";
} elseif ($skor >= 80) {
    $badge = "🥈 Hebat";
} elseif ($skor >= 60) {
    $badge = "🥉 Lumayan";
} else {
    $badge = "📖 Perlu Belajar Lagi";
}

/* ===========================
   Rekomendasi materi yang perlu dipelajari kembali
=========================== */

$rekomendasi = getRekomendasi($conn, $user_id, 70, 3);

/* ===========================
   Hapus Checkpoint Quiz kategori+level ini saja
   (checkpoint kategori/level lain yang sedang berjalan tidak ikut terhapus)
=========================== */

unset($_SESSION['quiz_checkpoints'][$progressKey]);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Hasil Quiz</title>

<script>
document.documentElement.setAttribute("data-theme", localStorage.getItem("quizbps-theme") || "light");
</script>

<link rel="stylesheet"
href="assets/css/quiz.css?v=<?= filemtime(__DIR__ . "/assets/css/quiz.css"); ?>">

</head>

<body>

<div class="hasil">

<h1>

<?= $kehabisanNyawa ? '💔 Kehabisan Nyawa' : '🎉 Quiz Selesai'; ?>

</h1>

<p style="color:#666;">
<?= e($kategoriInfo['nama'] ?? ''); ?> - <?= e($level); ?>
</p>

<h2>

Skor Kamu

</h2>

<div class="score">

<?= $skor ?>

</div>

<h3>

XP Didapat

</h3>

<p>

+<?= $xpDidapat ?> XP

</p>

<h3>

Badge

</h3>

<div class="badge">

<?= $badge ?>

</div>

<?php if(!empty($rekomendasi)): ?>

<hr>

<h3>📚 Rekomendasi Materi Dipelajari Kembali</h3>

<ul class="rekomendasi-list">

<?php foreach($rekomendasi as $r): ?>

<li>

<a href="quiz.php?kategori=<?= $r['kategori_id'] ?>&level=<?= urlencode($r['level']) ?>">

<?= e($r['nama']); ?> - <?= e($r['level']); ?>

<?= $r['best_score'] !== null ? "(skor terbaik: {$r['best_score']})" : "(belum dicoba)"; ?>

</a>

</li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

<br>

<a href="dashboard.php">

<button>

🏠 Dashboard

</button>

</a>

<?php if($kategori > 0): ?>

<a href="quiz.php?kategori=<?= $kategori ?>&level=<?= urlencode($level) ?>">

<button>

🔄 Main Lagi

</button>

</a>

<?php endif; ?>

</div>

</body>

</html>
