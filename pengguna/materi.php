<?php
require __DIR__ . "/../inc/auth.php";
require __DIR__ . "/../inc/functions.php";

$daftarKategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id");
$materiProgress = getMateriDenganProgress($conn, $user_id);

$statusLabel = [
    'belum' => ['Belum Mulai', 'fa-circle-minus'],
    'proses' => ['Proses', 'fa-rotate'],
    'selesai' => ['Selesai', 'fa-circle-check'],
];

// Ikon kartu kategori berupa gambar ilustrasi asli (bukan simbol FontAwesome)
$kategoriIkonKartu = [
    'Statistika Dasar' => 'qc-illus-statistika.jpg',
    'Pengenalan BPS' => 'qc-illus-bps.jpg',
    'Indikator Statistik' => 'qc-illus-indikator.jpg',
];

// Gambar topik (per id materi), tersimpan di assets/img sesuai judul topiknya
$topikImages = [
    2 => 'Mean, Median dan Modus.jpg',
    3 => 'Grafik dan Tabel.jpg',
    5 => 'Tugas dan Fungsi BPS.jpg',
    6 => 'Sensus Penduduk.jpg',
    7 => 'Sensus Pertanian.jpg',
    8 => 'Sensus Ekonomi.jpg',
    9 => 'Survei BPS.jpg',
];

// Ikon & warna badge per topik (id materi => [ikon, warna])
// ikon 'sigma' dirender sebagai karakter Σ, bukan FontAwesome
$topikIconMap = [
    1 => ['fa-book', 'blue'],
    2 => ['sigma', 'green'],
    3 => ['fa-chart-column', 'purple'],
    5 => ['fa-clipboard-list', 'teal'],
    6 => ['fa-users', 'blue'],
    7 => ['fa-seedling', 'green'],
    8 => ['fa-chart-line', 'orange'],
    9 => ['fa-file-signature', 'teal'],
    10 => ['fa-people-group', 'blue'],
    11 => ['fa-coins', 'green'],
    12 => ['fa-briefcase', 'orange'],
    13 => ['fa-hand-holding-heart', 'red'],
    14 => ['fa-arrow-trend-up', 'purple'],
];

$activeMenu = 'materi';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Materi Pembelajaran | Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css'); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

<?php require __DIR__ . "/../inc/nav.php"; ?>

<main class="content">

<div class="page-watermark">
<div class="wm-inner">
<img src="assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<div class="section-title">

<h2>Materi Pembelajaran</h2>

</div>

<p style="color:#666;margin-bottom:25px;">
Baca dulu materinya, tandai selesai untuk mendapatkan XP, lalu klik <b>Mulai Quiz</b> untuk menguji pemahamanmu.
</p>

<div class="materi-list">

<?php while ($k = mysqli_fetch_assoc($daftarKategori)):

    $kat = $materiProgress[$k['id']] ?? ['materi' => [], 'total_topik' => 0, 'total_durasi' => 0, 'total_xp' => 0, 'xp_earned' => 0, 'progress_percent' => 0];
    $bagian = $kat['materi'];
    $xpPerTopik = $kat['total_topik'] > 0 ? round($kat['total_xp'] / $kat['total_topik']) : 0;
?>

<div class="materi-card" id="materi-<?= $k['id']; ?>">

<div class="materi-hero">

<i class="fa-solid <?= e($k['icon']); ?> hero-watermark"></i>

<div class="materi-hero-icon">
<?php if (!empty($kategoriIkonKartu[$k['nama']])): ?>
<img src="assets/img/<?= e($kategoriIkonKartu[$k['nama']]); ?>" alt="<?= e($k['nama']); ?>">
<?php else: ?>
<i class="fa-solid <?= e($k['icon']); ?>"></i>
<?php endif; ?>
</div>

<div class="materi-hero-text">

<span class="kategori-badge"><i class="fa-solid fa-circle"></i> KATEGORI</span>

<h3><?= e($k['nama']); ?></h3>

<p><?= e($k['deskripsi']); ?></p>

<div class="materi-pills">

<div class="pill blue">
<i class="fa-solid fa-book"></i>
<div><b><?= $kat['total_topik']; ?> Topik</b><span>Materi</span></div>
</div>

<div class="pill green">
<i class="fa-solid fa-clock"></i>
<div><b>&plusmn;<?= $kat['total_durasi']; ?> Menit</b><span>Estimasi Waktu</span></div>
</div>

<div class="pill orange">
<i class="fa-solid fa-trophy"></i>
<div><b><?= $kat['total_xp']; ?> XP</b><span>Total XP</span></div>
</div>

<div class="pill purple">
<i class="fa-solid fa-bullseye"></i>
<div><b><?= $xpPerTopik; ?> XP</b><span>per Topik</span></div>
</div>

</div>

</div>

<?php if ($k['nama'] === 'Statistika Dasar'): ?>
<div class="materi-hero-illustration">
<img src="assets/img/ilustrasi-statistik.jpg" alt="">
</div>
<?php elseif ($k['nama'] === 'Pengenalan BPS'): ?>
<div class="materi-hero-illustration">
<img src="assets/img/ilustrasi-bps-hero.jpg" alt="">
</div>
<?php endif; ?>

</div>

<?php if ($k['nama'] === 'Statistika Dasar'): ?>
<div class="materi-extra-desc">
Pelajari konsep dasar statistika yang penting untuk memahami data dan pengambilan keputusan.
</div>
<?php endif; ?>

<?php if ($k['nama'] === 'Pengenalan BPS' && !empty($bagian)):
    $topikUtama = $bagian[0];
?>

<div class="materi-highlight" data-materi-id="<?= $topikUtama['id']; ?>" data-status="<?= $topikUtama['status']; ?>">

<div class="highlight-top">
<span class="highlight-num">1</span>
<h4><?= e($topikUtama['judul']); ?></h4>
<span class="highlight-badge"><i class="fa-solid fa-chevron-up"></i> Terbuka</span>
</div>

<div class="highlight-body">

<img class="highlight-img" src="assets/img/ilustrasi-bps.jpg" alt="">

<div class="highlight-content">
<p><?= $topikUtama['isi']; ?></p>
<div class="highlight-inti">
<span class="highlight-inti-icon"><i class="fa-solid fa-lightbulb"></i></span>
<div><b>Inti Materi</b><span>Sejarah singkat, peran BPS, kedudukan, dan prinsip penyelenggaraan statistik.</span></div>
</div>
</div>

<?php if ($topikUtama['status'] === 'selesai'): ?>
<div class="selesai-note"><i class="fa-solid fa-circle-check"></i> Selesai &mdash; XP sudah kamu dapatkan</div>
<?php else: ?>
<button type="button" class="btn-mulai-materi" onclick="mulaiMateriHighlight(<?= $topikUtama['id']; ?>, <?= $topikUtama['xp']; ?>, this)">
<i class="fa-solid fa-book-open"></i> Mulai Materi <i class="fa-solid fa-chevron-right"></i>
</button>
<?php endif; ?>

</div>

</div>

<?php endif; ?>

<div class="materi-table-wrap">

<table class="materi-table-v2">

<thead>
<tr>
<th>No.</th>
<th>Topik</th>
<th>Deskripsi</th>
<th>Durasi</th>
<th>Progress</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php foreach ($bagian as $i => $sub):
    if ($k['nama'] === 'Pengenalan BPS' && $i === 0) continue;
    $snippet = str_ireplace(['<br>', '<br/>', '<br />'], ' ', $sub['isi']);
    $snippet = trim(html_entity_decode(strip_tags($snippet), ENT_QUOTES, 'UTF-8'));
    $snippet = preg_replace('/\s+/u', ' ', $snippet);
    if (mb_strlen($snippet) > 80) $snippet = mb_substr($snippet, 0, 80) . '...';
    $st = $sub['status'];
    $pct = $st === 'selesai' ? 100 : ($st === 'proses' ? 50 : 0);
    [$label, $icon] = $statusLabel[$st];
    [$topikIconType, $topikIconColor] = $topikIconMap[$sub['id']] ?? ['fa-book-open', 'blue'];
?>

<tr class="materi-row-v2" data-materi-id="<?= $sub['id']; ?>" data-status="<?= $st; ?>">
<td class="num"><?= $i + 1; ?></td>
<td class="topik-cell">
<div class="topik-cell-inner">
<span class="topik-icon <?= $topikIconColor; ?>">
<?php if ($topikIconType === 'sigma'): ?>&Sigma;<?php else: ?><i class="fa-solid <?= $topikIconType; ?>"></i><?php endif; ?>
</span>
<?= e($sub['judul']); ?>
</div>
</td>
<td class="deskripsi-cell"><?= e($snippet); ?></td>
<td class="durasi-cell"><i class="fa-regular fa-clock"></i> <?= $sub['durasi_menit']; ?> Menit</td>
<td class="progress-cell">
<span class="progress-percent"><?= $pct; ?>%</span>
<div class="progress-track"><div class="progress-fill-bar" style="width:<?= $pct; ?>%"></div></div>
</td>
<td><span class="status-badge <?= $st; ?>"><i class="fa-solid <?= $icon; ?>"></i> <?= $label; ?></span></td>
<td><button type="button" class="btn-lihat" onclick="toggleMateriV2(<?= $sub['id']; ?>)"><i class="fa-solid fa-eye"></i> Lihat Materi <i class="fa-solid fa-chevron-right"></i></button></td>
</tr>

<tr class="materi-content-row-v2" id="materi-content-<?= $sub['id']; ?>" style="display:none;">
<td colspan="7">
<div class="content-inner-v2<?= isset($topikImages[$sub['id']]) ? ' has-image' : ''; ?>">

<?php if (isset($topikImages[$sub['id']])): ?>
<img class="content-topik-img" src="assets/img/<?= rawurlencode($topikImages[$sub['id']]); ?>" alt="">
<?php endif; ?>

<div class="content-topik-text">

<?= $sub['isi']; ?>

<?php if ($st === 'selesai'): ?>
<div class="selesai-note"><i class="fa-solid fa-circle-check"></i> Selesai dibaca &mdash; XP sudah kamu dapatkan</div>
<?php else: ?>
<button type="button" class="btn-selesai" onclick="tandaiSelesai(<?= $sub['id']; ?>, <?= $sub['xp']; ?>)">
<i class="fa-solid fa-star"></i> Tandai Selesai &amp; Dapatkan <?= $sub['xp']; ?> XP
</button>
<?php endif; ?>

</div>

</div>
</td>
</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<div class="materi-summary">

<div class="summary-item">
<div class="summary-icon blue"><i class="fa-solid fa-book"></i></div>
<div><b><?= $kat['total_topik']; ?></b><span>Total Topik<br>Materi tersedia</span></div>
</div>

<div class="summary-item">
<div class="summary-icon green"><i class="fa-solid fa-clock"></i></div>
<div><b>&plusmn;<?= $kat['total_durasi']; ?> Menit</b><span>Estimasi Waktu<br>Total waktu belajar</span></div>
</div>

<div class="summary-item">
<div class="summary-icon orange"><i class="fa-solid fa-trophy"></i></div>
<div><b><?= $kat['total_xp']; ?> XP</b><span>Total XP<br>Jika semua selesai</span></div>
</div>

<div class="summary-item">
<div class="summary-icon purple"><i class="fa-solid fa-bolt"></i></div>
<div><b class="xp-earned-<?= $k['id']; ?>"><?= $kat['xp_earned']; ?> XP</b><span>XP Kamu Dapat<br>Progress <?= $kat['progress_percent']; ?>%</span></div>
</div>

</div>

<?php if ($k['nama'] === 'Pengenalan BPS'): ?>

<div class="materi-cta cta-green">

<div class="cta-icon"><i class="fa-solid fa-bullseye"></i></div>

<div class="cta-text">
<h4>Selesaikan Semua Topik</h4>
<p>Dapatkan total <b><?= $kat['total_xp']; ?> XP</b> dan tingkatkan levelmu!</p>
</div>

<div class="cta-progress">
<span><?= $kat['progress_percent']; ?>%</span>
<div class="cta-progress-track"><div class="cta-progress-fill" style="width:<?= $kat['progress_percent']; ?>%"></div></div>
<small>Progress Keseluruhan</small>
</div>

<div class="cta-action">
<button type="button" onclick="bukaLevel(<?= $k['id']; ?>)">Mulai Quiz <i class="fa-solid fa-arrow-right"></i></button>
<small>Uji pemahamanmu sekarang!</small>
</div>

</div>

<?php else: ?>

<div class="materi-cta">

<div class="cta-icon"><i class="fa-solid fa-gamepad"></i></div>

<div class="cta-text">
<h4>Siap Uji Pemahamanmu?</h4>
<p>Kerjakan quiz untuk menguji pemahamanmu dan dapatkan XP!</p>
</div>

<button type="button" onclick="bukaLevel(<?= $k['id']; ?>)">Mulai Quiz <i class="fa-solid fa-arrow-right"></i></button>

</div>

<?php endif; ?>

</div>

<?php endwhile; ?>

</div>

</main>

</div>

<!-- Modal Level -->
<div id="modalLevel" class="modal">

    <div class="modal-content">

        <span class="close" onclick="tutupLevel()">&times;</span>

        <h2>Pilih Level</h2>

        <a id="mudah">
            <button class="btn-level green">😊 Mudah</button>
        </a>

        <a id="sedang">
            <button class="btn-level orange">😐 Sedang</button>
        </a>

        <a id="sulit">
            <button class="btn-level red">😟 Sulit</button>
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

const statusInfo = {
    belum: { label: "Belum Mulai", icon: "fa-circle-minus" },
    proses: { label: "Proses", icon: "fa-rotate" },
    selesai: { label: "Selesai", icon: "fa-circle-check" },
};

function setRowStatus(row, status, pct){

    row.dataset.status = status;

    const badge = row.querySelector(".status-badge");
    badge.className = "status-badge " + status;
    badge.innerHTML = '<i class="fa-solid ' + statusInfo[status].icon + '"></i> ' + statusInfo[status].label;

    row.querySelector(".progress-percent").textContent = pct + "%";
    row.querySelector(".progress-fill-bar").style.width = pct + "%";

}

function toggleMateriV2(id){

    const contentRow = document.getElementById("materi-content-" + id);
    const isHidden = contentRow.style.display === "none";

    contentRow.style.display = isHidden ? "table-row" : "none";

    if (!isHidden) return;

    const row = document.querySelector('.materi-row-v2[data-materi-id="' + id + '"]');

    if (row.dataset.status === "belum") {
        fetch("materi_progress.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "materi_id=" + id + "&action=buka",
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === "ok") setRowStatus(row, "proses", 50);
        });
    }

}

function tandaiSelesai(id, xp){

    fetch("materi_progress.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "materi_id=" + id + "&action=selesai",
    })
    .then(r => r.json())
    .then(data => {

        if (data.status !== "ok") return;

        const row = document.querySelector('.materi-row-v2[data-materi-id="' + id + '"]');
        setRowStatus(row, "selesai", 100);

        const contentRow = document.getElementById("materi-content-" + id);
        const btn = contentRow.querySelector(".btn-selesai");
        if (btn) {
            btn.outerHTML = '<div class="selesai-note"><i class="fa-solid fa-circle-check"></i> Selesai dibaca &mdash; +' + data.xpAwarded + ' XP diperoleh</div>';
        }

        const card = row.closest(".materi-card");
        updateCardProgress(card, data.xpAwarded);

    });

}

function updateCardProgress(card, xpAwarded){

    const items = card.querySelectorAll('.materi-row-v2, .materi-highlight');
    let selesaiCount = 0;
    items.forEach(function(item){
        if (item.dataset.status === "selesai") selesaiCount++;
    });

    const xpEarnedEl = card.querySelector('[class*="xp-earned-"]');
    if (xpEarnedEl && xpAwarded > 0) {
        const current = parseInt(xpEarnedEl.textContent) || 0;
        xpEarnedEl.textContent = (current + xpAwarded) + " XP";
        const progressPct = Math.round((selesaiCount / items.length) * 100);
        xpEarnedEl.parentElement.querySelector("span").innerHTML = "XP Kamu Dapat<br>Progress " + progressPct + "%";

        const ctaPercentEl = card.querySelector(".cta-progress span");
        const ctaFillEl = card.querySelector(".cta-progress-fill");
        if (ctaPercentEl && ctaFillEl) {
            ctaPercentEl.textContent = progressPct + "%";
            ctaFillEl.style.width = progressPct + "%";
        }
    }

}

function mulaiMateriHighlight(id, xp, btn){

    fetch("materi_progress.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "materi_id=" + id + "&action=selesai",
    })
    .then(r => r.json())
    .then(data => {

        if (data.status !== "ok") return;

        const highlight = btn.closest(".materi-highlight");
        highlight.dataset.status = "selesai";

        btn.outerHTML = '<div class="selesai-note"><i class="fa-solid fa-circle-check"></i> Selesai &mdash; +' + data.xpAwarded + ' XP diperoleh</div>';

        const card = highlight.closest(".materi-card");
        updateCardProgress(card, data.xpAwarded);

    });

}

</script>

</body>

</html>
