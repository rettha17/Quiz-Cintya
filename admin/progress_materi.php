<?php
require __DIR__ . "/auth.php";

$tab = (isset($_GET['tab']) && $_GET['tab'] === 'topik') ? 'topik' : 'pengguna';

$totalMateri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];
$totalDibuka = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi_progress"))['c'];
$totalSelesai = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi_progress WHERE status='selesai'"))['c'];
$totalXpMateri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(xp_diberikan),0) c FROM materi_progress"))['c'];
$totalPengguna = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

$daftarPengguna = mysqli_query($conn, "
    SELECT u.id, u.nama, u.foto,
        COUNT(mp.id) AS dibuka,
        SUM(CASE WHEN mp.status='selesai' THEN 1 ELSE 0 END) AS selesai,
        COALESCE(SUM(mp.xp_diberikan),0) AS xp_materi,
        MAX(COALESCE(mp.selesai_pada, mp.dibuka_pada)) AS terakhir_aktif
    FROM users u
    LEFT JOIN materi_progress mp ON mp.user_id = u.id
    GROUP BY u.id, u.nama, u.foto
    ORDER BY selesai DESC, xp_materi DESC, u.nama ASC
");

$daftarTopik = mysqli_query($conn, "
    SELECT m.id, m.judul, m.xp, k.nama AS kategori,
        COUNT(mp.id) AS dibuka,
        SUM(CASE WHEN mp.status='selesai' THEN 1 ELSE 0 END) AS selesai
    FROM materi m
    JOIN kategori k ON k.id = m.kategori_id
    LEFT JOIN materi_progress mp ON mp.materi_id = m.id
    GROUP BY m.id, m.judul, m.xp, k.nama
    ORDER BY k.id, m.urutan, m.id
");

$activeMenu = 'progress_materi';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Progress Materi | Admin Quiz Challenge BPS</title>

<link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . "/assets/admin.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="admin-container">

<?php require __DIR__ . "/partials/nav.php"; ?>

<main class="admin-content">

<div class="page-watermark">
<div class="wm-inner">
<img src="../pengguna/assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

<div class="admin-topbar">

<h1>Progress Materi</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<div class="admin-stats">

<div class="admin-stat-box">
<h2><?= $totalPengguna; ?></h2>
<p><i class="fa-solid fa-users"></i> Total Pengguna</p>
</div>

<div class="admin-stat-box">
<h2><?= $totalMateri; ?></h2>
<p><i class="fa-solid fa-book"></i> Total Topik Materi</p>
</div>

<div class="admin-stat-box">
<h2><?= $totalSelesai; ?> <small style="font-size:16px;color:#999;">/ <?= $totalDibuka; ?> dibuka</small></h2>
<p><i class="fa-solid fa-circle-check"></i> Topik Diselesaikan</p>
</div>

<div class="admin-stat-box">
<h2><?= $totalXpMateri; ?> XP</h2>
<p><i class="fa-solid fa-bolt"></i> Total XP dari Materi</p>
</div>

</div>

<div class="admin-panel">

<h2><i class="fa-solid fa-chart-line"></i> Progress Baca Materi Pengguna</h2>

<div class="admin-tabs">
<a href="progress_materi.php?tab=pengguna" class="<?= $tab === 'pengguna' ? 'active' : ''; ?>">Per Pengguna</a>
<a href="progress_materi.php?tab=topik" class="<?= $tab === 'topik' ? 'active' : ''; ?>">Per Topik</a>
</div>

<?php if ($tab === 'pengguna'): ?>

<table class="admin-table">

<thead>
<tr>
<th>Foto</th>
<th>Nama</th>
<th>Topik Diselesaikan</th>
<th>Progress</th>
<th>XP dari Materi</th>
<th>Terakhir Aktif</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarPengguna) == 0): ?>
<tr><td colspan="6" style="text-align:center;">Belum ada pengguna terdaftar.</td></tr>
<?php else: while ($row = mysqli_fetch_assoc($daftarPengguna)):
    $selesai = (int) $row['selesai'];
    $pct = $totalMateri > 0 ? round(($selesai / $totalMateri) * 100) : 0;
?>

<tr>
<td><img src="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>" class="rank-photo"></td>
<td><?= e($row['nama']); ?></td>
<td><?= $selesai; ?> / <?= $totalMateri; ?></td>
<td>
<div style="display:flex;align-items:center;gap:10px;">
<div style="flex:1;height:8px;border-radius:20px;background:var(--border);overflow:hidden;">
<div style="height:100%;border-radius:20px;background:linear-gradient(90deg,#1565C0,#42A5F5);width:<?= $pct; ?>%;"></div>
</div>
<small style="color:#1565C0;font-weight:700;"><?= $pct; ?>%</small>
</div>
</td>
<td><strong><?= (int) $row['xp_materi']; ?> XP</strong></td>
<td><?= $row['terakhir_aktif'] ? date('d M Y H:i', strtotime($row['terakhir_aktif'])) : '-'; ?></td>
</tr>

<?php endwhile; endif; ?>

</tbody>

</table>

<?php else: ?>

<table class="admin-table">

<thead>
<tr>
<th>Kategori</th>
<th>Topik</th>
<th>XP</th>
<th>Dibuka</th>
<th>Diselesaikan</th>
<th>Tingkat Penyelesaian</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarTopik) == 0): ?>
<tr><td colspan="6" style="text-align:center;">Belum ada materi.</td></tr>
<?php else: while ($row = mysqli_fetch_assoc($daftarTopik)):
    $selesai = (int) $row['selesai'];
    $pct = $totalPengguna > 0 ? round(($selesai / $totalPengguna) * 100) : 0;
?>

<tr>
<td><?= e($row['kategori']); ?></td>
<td><?= e($row['judul']); ?></td>
<td><?= (int) $row['xp']; ?> XP</td>
<td><?= (int) $row['dibuka']; ?></td>
<td><?= $selesai; ?> dari <?= $totalPengguna; ?> pengguna</td>
<td>
<div style="display:flex;align-items:center;gap:10px;">
<div style="flex:1;height:8px;border-radius:20px;background:var(--border);overflow:hidden;">
<div style="height:100%;border-radius:20px;background:linear-gradient(90deg,#2ecc71,#1e9e56);width:<?= $pct; ?>%;"></div>
</div>
<small style="color:#2ecc71;font-weight:700;"><?= $pct; ?>%</small>
</div>
</td>
</tr>

<?php endwhile; endif; ?>

</tbody>

</table>

<?php endif; ?>

</div>

</main>

</div>

</body>

</html>
