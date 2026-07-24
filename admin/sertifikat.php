<?php
require __DIR__ . "/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$pesan = "";
$pesanTipe = "success";

/* ===========================
   GENERATE SERTIFIKAT MANUAL
=========================== */

if (isset($_POST['generate_sertifikat'])) {

    $userId = (int) $_POST['user_id'];

    $userStmt = mysqli_prepare($conn, "SELECT nama FROM users WHERE id=?");
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userTarget = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));

    $cekStmt = mysqli_prepare($conn, "SELECT id FROM sertifikat WHERE user_id=?");
    mysqli_stmt_bind_param($cekStmt, "i", $userId);
    mysqli_stmt_execute($cekStmt);
    $sudahAda = mysqli_fetch_assoc(mysqli_stmt_get_result($cekStmt));

    $quizStmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM hasil_quiz WHERE user_id=?");
    mysqli_stmt_bind_param($quizStmt, "i", $userId);
    mysqli_stmt_execute($quizStmt);
    $quizCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($quizStmt))['c'];

    $aggStmt = mysqli_prepare($conn, "SELECT AVG(score) a FROM hasil_quiz WHERE user_id=?");
    mysqli_stmt_bind_param($aggStmt, "i", $userId);
    mysqli_stmt_execute($aggStmt);
    $nilaiAkhir = (int) round((float) mysqli_fetch_assoc(mysqli_stmt_get_result($aggStmt))['a']);

    $nilaiLulusGen = (int) getSetting($conn, 'sertifikat_nilai_lulus', '70');

    if (!$userTarget) {

        $pesan = "Pemain tidak ditemukan.";
        $pesanTipe = "error";

    } elseif ($sudahAda) {

        $pesan = "Pemain '{$userTarget['nama']}' sudah memiliki sertifikat.";
        $pesanTipe = "error";

    } elseif ($quizCount < 3) {

        $pesan = "Pemain '{$userTarget['nama']}' belum memenuhi syarat (minimal 3 quiz selesai).";
        $pesanTipe = "error";

    } elseif ($nilaiAkhir < $nilaiLulusGen) {

        $pesan = "Pemain '{$userTarget['nama']}' belum memenuhi nilai kelulusan (rata-rata {$nilaiAkhir}, minimal {$nilaiLulusGen}).";
        $pesanTipe = "error";

    } else {

        $totalMateriGen = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM materi"))['c'];
        $materiSelesaiGen = materiSelesaiCount($conn, $userId);
        $penguasaan = $totalMateriGen > 0 ? (int) round($materiSelesaiGen / $totalMateriGen * 100) : 0;

        $sert = getOrCreateSertifikat($conn, $userId, $nilaiAkhir, $penguasaan);

        $cekLog = mysqli_prepare($conn, "SELECT id FROM activity_log WHERE tipe='sertifikat' AND user_id=?");
        mysqli_stmt_bind_param($cekLog, "i", $userId);
        mysqli_stmt_execute($cekLog);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($cekLog))) {
            logAktivitas($conn, 'sertifikat', 'Sertifikat ' . e($sert['certificate_number']) . ' berhasil diterbitkan untuk ' . e($userTarget['nama']) . ' oleh admin.', 'fa-certificate', 'orange', $userId);
        }

        $pesan = "Sertifikat {$sert['certificate_number']} berhasil diterbitkan untuk '{$userTarget['nama']}'.";

    }

}

/* ===========================
   KIRIM ULANG SERTIFIKAT VIA EMAIL
=========================== */

if (isset($_POST['kirim_sertifikat'])) {

    $sertId = (int) $_POST['sertifikat_id'];

    $stmt = mysqli_prepare($conn, "
        SELECT s.certificate_number, s.verification_token, u.nama, u.email
        FROM sertifikat s JOIN users u ON u.id = s.user_id
        WHERE s.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $sertId);
    mysqli_stmt_execute($stmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$target) {

        $pesan = "Sertifikat tidak ditemukan.";
        $pesanTipe = "error";

    } else {

        $verifyUrl = getVerificationUrl($target['verification_token']);
        $terkirim = sendSertifikatEmail($target['email'], $target['nama'], $target['certificate_number'], $verifyUrl);

        if ($terkirim) {
            $pesan = "Sertifikat {$target['certificate_number']} berhasil dikirim ulang ke {$target['email']}.";
        } else {
            $pesan = "Gagal mengirim email. Pastikan SMTP sudah dikonfigurasi di smtp_config.php.";
            $pesanTipe = "error";
        }

    }

}

/* ===========================
   CABUT / AKTIFKAN SERTIFIKAT
=========================== */

if (isset($_GET['toggle_status'])) {

    $sertId = (int) $_GET['toggle_status'];

    $stmt = mysqli_prepare($conn, "SELECT s.status, s.certificate_number, u.nama FROM sertifikat s JOIN users u ON u.id=s.user_id WHERE s.id=?");
    mysqli_stmt_bind_param($stmt, "i", $sertId);
    mysqli_stmt_execute($stmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($target) {

        $statusBaru = $target['status'] === 'valid' ? 'dicabut' : 'valid';

        $up = mysqli_prepare($conn, "UPDATE sertifikat SET status=? WHERE id=?");
        mysqli_stmt_bind_param($up, "si", $statusBaru, $sertId);
        mysqli_stmt_execute($up);

        $pesan = "Sertifikat {$target['certificate_number']} milik {$target['nama']} " . ($statusBaru === 'dicabut' ? 'berhasil dicabut.' : 'berhasil diaktifkan kembali.');

    } else {

        $pesan = "Sertifikat tidak ditemukan.";
        $pesanTipe = "error";

    }

}

/* ===========================
   EDIT SERTIFIKAT (NILAI & PENGUASAAN)
=========================== */

if (isset($_POST['edit_sertifikat'])) {

    $sertId = (int) $_POST['sertifikat_id'];
    $nilaiEdit = max(0, min(100, (int) $_POST['nilai_akhir']));
    $penguasaanEdit = max(0, min(100, (int) $_POST['penguasaan']));

    $stmt = mysqli_prepare($conn, "SELECT s.certificate_number, u.nama FROM sertifikat s JOIN users u ON u.id=s.user_id WHERE s.id=?");
    mysqli_stmt_bind_param($stmt, "i", $sertId);
    mysqli_stmt_execute($stmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($target) {

        $up = mysqli_prepare($conn, "UPDATE sertifikat SET score=?, mastery=? WHERE id=?");
        mysqli_stmt_bind_param($up, "iii", $nilaiEdit, $penguasaanEdit, $sertId);
        mysqli_stmt_execute($up);

        $pesan = "Sertifikat {$target['certificate_number']} milik {$target['nama']} berhasil diperbarui.";

    } else {

        $pesan = "Sertifikat tidak ditemukan.";
        $pesanTipe = "error";

    }

}

/* ===========================
   HAPUS SERTIFIKAT
=========================== */

if (isset($_GET['hapus_sertifikat'])) {

    $sertId = (int) $_GET['hapus_sertifikat'];

    $stmt = mysqli_prepare($conn, "SELECT s.certificate_number, u.nama FROM sertifikat s JOIN users u ON u.id=s.user_id WHERE s.id=?");
    mysqli_stmt_bind_param($stmt, "i", $sertId);
    mysqli_stmt_execute($stmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($target) {

        $del = mysqli_prepare($conn, "DELETE FROM sertifikat WHERE id=?");
        mysqli_stmt_bind_param($del, "i", $sertId);
        mysqli_stmt_execute($del);

        $pesan = "Sertifikat {$target['certificate_number']} milik {$target['nama']} berhasil dihapus.";

    } else {

        $pesan = "Sertifikat tidak ditemukan.";
        $pesanTipe = "error";

    }

}

/* ===========================
   PENCARIAN, FILTER & PAGINASI
=========================== */

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filterTanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : '';

$whereParts = [];
$params = [];
$paramTypes = '';

if ($cari !== '') {
    $whereParts[] = "(u.nama LIKE ? OR u.email LIKE ? OR s.certificate_number LIKE ?)";
    $like = "%$cari%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $paramTypes .= 'sss';
}

if ($filterStatus === 'valid' || $filterStatus === 'dicabut') {
    $whereParts[] = "s.status = ?";
    $params[] = $filterStatus;
    $paramTypes .= 's';
}

if ($filterTanggal === '7d') {
    $whereParts[] = "s.issue_date >= CURDATE() - INTERVAL 7 DAY";
} elseif ($filterTanggal === '30d') {
    $whereParts[] = "s.issue_date >= CURDATE() - INTERVAL 30 DAY";
} elseif ($filterTanggal === 'bulan_ini') {
    $whereParts[] = "YEAR(s.issue_date) = YEAR(CURDATE()) AND MONTH(s.issue_date) = MONTH(CURDATE())";
} elseif ($filterTanggal === 'tahun_ini') {
    $whereParts[] = "YEAR(s.issue_date) = YEAR(CURDATE())";
}

$whereSql = $whereParts ? ("WHERE " . implode(" AND ", $whereParts)) : "";

$baseSql = "FROM sertifikat s JOIN users u ON u.id = s.user_id $whereSql";

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) c $baseSql");
if ($paramTypes !== '') {
    mysqli_stmt_bind_param($countStmt, $paramTypes, ...$params);
}
mysqli_stmt_execute($countStmt);
$totalRows = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'];

$perPage = 10;
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$dataStmt = mysqli_prepare($conn, "
    SELECT s.id, s.certificate_number, s.verification_token, s.score, s.mastery, s.status, s.issue_date,
        u.id AS user_id, u.nama, u.email, u.foto
    $baseSql
    ORDER BY s.issue_date DESC, s.id DESC
    LIMIT ? OFFSET ?
");
$dataParams = $params;
$dataParams[] = $perPage;
$dataParams[] = $offset;
mysqli_stmt_bind_param($dataStmt, $paramTypes . 'ii', ...$dataParams);
mysqli_stmt_execute($dataStmt);
$daftarSertifikat = mysqli_stmt_get_result($dataStmt);

/* ===========================
   RINGKASAN STATISTIK
=========================== */

$totalSertifikat = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sertifikat"))['c'];

$sertifikatBulanIni = (int) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM sertifikat WHERE YEAR(issue_date)=YEAR(CURDATE()) AND MONTH(issue_date)=MONTH(CURDATE())
"))['c'];

$sertifikatHariIni = (int) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM sertifikat WHERE issue_date = CURDATE()
"))['c'];

$sertifikatDicabut = (int) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM sertifikat WHERE status='dicabut'
"))['c'];

// Pemain yang sudah memenuhi syarat (>=3 quiz & nilai rata-rata >= nilai kelulusan) tapi belum punya sertifikat - kandidat "Generate Sertifikat".
$nilaiLulusEligible = (int) getSetting($conn, 'sertifikat_nilai_lulus', '70');
$eligibleResult = mysqli_query($conn, "
    SELECT u.id, u.nama, u.email, COUNT(h.id) AS jumlah_quiz, AVG(h.score) AS rata_skor
    FROM users u
    JOIN hasil_quiz h ON h.user_id = u.id
    LEFT JOIN sertifikat s ON s.user_id = u.id
    WHERE s.id IS NULL
    GROUP BY u.id, u.nama, u.email
    HAVING COUNT(h.id) >= 3 AND AVG(h.score) >= {$nilaiLulusEligible}
    ORDER BY u.nama
");
$eligibleUsers = [];
while ($row = mysqli_fetch_assoc($eligibleResult)) {
    $eligibleUsers[] = $row;
}
$menungguVerifikasi = count($eligibleUsers);

$activeMenu = 'sertifikat';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manajemen Sertifikat | Admin StatQuest BPS</title>

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

<div class="admin-topbar">

<div>
<h1><i class="fa-solid fa-certificate lb-trophy-icon"></i> Manajemen Sertifikat</h1>
<p class="admin-topbar-sub">Kelola, verifikasi, dan monitoring sertifikat yang diterbitkan kepada peserta.</p>
</div>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-stats admin-stats-v2 cols-5">

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon blue"><i class="fa-solid fa-award"></i></div>
<span class="stat-card-label">Total Sertifikat</span>
</div>
<h2><?= $totalSertifikat; ?></h2>
<span class="stat-trend flat">Sertifikat diterbitkan</span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon green"><i class="fa-solid fa-calendar-days"></i></div>
<span class="stat-card-label">Sertifikat Bulan Ini</span>
</div>
<h2><?= $sertifikatBulanIni; ?></h2>
<span class="stat-trend flat"><?= date('F Y'); ?></span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon purple"><i class="fa-solid fa-calendar-check"></i></div>
<span class="stat-card-label">Sertifikat Hari Ini</span>
</div>
<h2><?= $sertifikatHariIni; ?></h2>
<span class="stat-trend flat"><?= date('d F Y'); ?></span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon yellow"><i class="fa-solid fa-hourglass-half"></i></div>
<span class="stat-card-label">Menunggu Verifikasi</span>
</div>
<h2><?= $menungguVerifikasi; ?></h2>
<span class="stat-trend flat">Sudah eligible, belum digenerate</span>
</div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon red"><i class="fa-solid fa-ban"></i></div>
<span class="stat-card-label">Sertifikat Dicabut</span>
</div>
<h2><?= $sertifikatDicabut; ?></h2>
<span class="stat-trend flat">Sertifikat tidak aktif</span>
</div>
</div>

</div>

<div class="admin-panel user-form-panel<?= isset($_GET['generate']) ? ' open' : ''; ?>" id="generateFormPanel">

<h2>Generate Sertifikat Manual</h2>

<?php if ($menungguVerifikasi === 0) { ?>

<p class="text-muted">Tidak ada pemain yang memenuhi syarat (minimal 3 quiz selesai) namun belum memiliki sertifikat saat ini.</p>

<?php } else { ?>

<form method="POST" class="admin-form">

<label>Pilih Pemain (memenuhi syarat, belum memiliki sertifikat)</label>
<select name="user_id" required>
<option value="">-- Pilih Pemain --</option>
<?php foreach ($eligibleUsers as $eu) { ?>
<option value="<?= $eu['id']; ?>"><?= e($eu['nama']); ?> (<?= e($eu['email']); ?>) &middot; <?= (int) $eu['jumlah_quiz']; ?> quiz selesai</option>
<?php } ?>
</select>

<br><br>

<button class="btn" type="submit" name="generate_sertifikat"><i class="fa-solid fa-certificate"></i> Generate Sertifikat</button>
<a class="btn btn-secondary" href="sertifikat.php">Batal</a>

</form>

<?php } ?>

</div>

<div class="admin-panel">

<div class="admin-panel-head">
<h2><i class="fa-solid fa-certificate"></i> Daftar Sertifikat</h2>
<p class="admin-panel-sub">Berikut adalah daftar semua sertifikat yang telah diterbitkan.</p>
</div>

<form method="GET" class="admin-filter user-table-filter">

<div class="user-search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" name="cari" placeholder="Cari nama peserta, email, atau nomor sertifikat..." value="<?= e($cari); ?>">
</div>

<select name="filter_status" onchange="this.form.submit()">
<option value="">Semua Status</option>
<option value="valid" <?= $filterStatus === 'valid' ? 'selected' : ''; ?>>Valid</option>
<option value="dicabut" <?= $filterStatus === 'dicabut' ? 'selected' : ''; ?>>Dicabut</option>
</select>

<select name="filter_tanggal" onchange="this.form.submit()">
<option value="">Semua Tanggal</option>
<option value="7d" <?= $filterTanggal === '7d' ? 'selected' : ''; ?>>7 Hari Terakhir</option>
<option value="30d" <?= $filterTanggal === '30d' ? 'selected' : ''; ?>>30 Hari Terakhir</option>
<option value="bulan_ini" <?= $filterTanggal === 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
<option value="tahun_ini" <?= $filterTanggal === 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
</select>

<button class="btn btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>

<?php if ($cari !== '' || $filterStatus !== '' || $filterTanggal !== '') { ?>
<a class="btn btn-sm btn-secondary" href="sertifikat.php">Reset</a>
<?php } ?>

<a class="btn btn-tambah-pemain" href="sertifikat.php?generate=1#generateFormPanel"><i class="fa-solid fa-plus"></i> Generate Sertifikat</a>

</form>

<div class="admin-table-wrap">

<table class="admin-table">

<thead>
<tr>
<th>No</th>
<th>No. Sertifikat</th>
<th>Peserta</th>
<th>Email</th>
<th>Program</th>
<th>Nilai Akhir</th>
<th>Penguasaan</th>
<th>Status</th>
<th>Tanggal Terbit</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarSertifikat) == 0) { ?>

<tr><td colspan="10" style="text-align:center;">Belum ada sertifikat yang ditemukan.</td></tr>

<?php } else { $no = $offset + 1; while ($row = mysqli_fetch_assoc($daftarSertifikat)) {
    $labelPenguasaan = labelPenguasaanMateri((int) $row['mastery']);
    $verifyUrl = getVerificationUrl($row['verification_token']);
    $riwayatAktivitas = [];
    $logStmt = mysqli_prepare($conn, "SELECT judul, dibuat_pada FROM activity_log WHERE user_id=? AND tipe LIKE 'sertifikat%' ORDER BY dibuat_pada DESC LIMIT 5");
    mysqli_stmt_bind_param($logStmt, "i", $row['user_id']);
    mysqli_stmt_execute($logStmt);
    $logRes = mysqli_stmt_get_result($logStmt);
    while ($lg = mysqli_fetch_assoc($logRes)) {
        $riwayatAktivitas[] = ['judul' => $lg['judul'], 'tanggal' => date('d M Y H:i', strtotime($lg['dibuat_pada']))];
    }
?>

<tr>

<td><?= $no++; ?></td>

<td class="lb-username" style="font-weight:700;color:var(--text-strong);"><?= e($row['certificate_number']); ?></td>

<td>
<div class="user-actions" style="justify-content:flex-start;gap:10px;align-items:center;">
<img src="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>" class="rank-photo" alt="">
<?= e($row['nama']); ?>
</div>
</td>

<td><?= e($row['email']); ?></td>

<td>StatQuest BPS<br><span class="lb-username">Jelajah Statistik Indonesia</span></td>

<td><strong><?= (int) $row['score']; ?></strong></td>

<td>
<div class="lb-materi-cell">
<span><?= (int) $row['mastery']; ?>% (<?= $labelPenguasaan; ?>)</span>
<div class="lb-materi-track"><div class="lb-materi-fill" style="width:<?= (int) $row['mastery']; ?>%;"></div></div>
</div>
</td>

<td>
<a class="status-badge status-<?= $row['status'] === 'valid' ? 'aktif' : 'nonaktif'; ?>" href="sertifikat.php?toggle_status=<?= $row['id']; ?>" onclick="return confirm('<?= $row['status'] === 'valid' ? 'Cabut sertifikat ini? Sertifikat tidak akan lagi dinyatakan valid saat diverifikasi.' : 'Aktifkan kembali sertifikat ini?'; ?>')" title="Klik untuk ubah status">
<i class="fa-solid fa-circle"></i> <?= $row['status'] === 'valid' ? 'Valid' : 'Dicabut'; ?>
</a>
</td>

<td><?= date('d M Y', strtotime($row['issue_date'])); ?></td>

<td class="user-actions">

<button type="button" class="table-icon-btn btn-view"
    data-nama="<?= e($row['nama']); ?>"
    data-email="<?= e($row['email']); ?>"
    data-nomor="<?= e($row['certificate_number']); ?>"
    data-nilai="<?= (int) $row['score']; ?>"
    data-penguasaan="<?= (int) $row['mastery']; ?>% (<?= $labelPenguasaan; ?>)"
    data-status="<?= $row['status'] === 'valid' ? 'Valid' : 'Dicabut'; ?>"
    data-tanggal="<?= date('d M Y', strtotime($row['issue_date'])); ?>"
    data-foto="../pengguna/assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>"
    data-verify="<?= e($verifyUrl); ?>"
    data-riwayat='<?= e(json_encode($riwayatAktivitas)); ?>'
    title="Lihat detail">
<i class="fa-solid fa-eye"></i>
</button>

<a class="table-icon-btn btn-download-icon" href="<?= e($verifyUrl); ?>" target="_blank" rel="noopener" title="Unduh / lihat sertifikat">
<i class="fa-solid fa-download"></i>
</a>

<form method="POST" style="display:inline;" onsubmit="return confirm('Kirim ulang sertifikat ini ke email peserta?');">
<input type="hidden" name="sertifikat_id" value="<?= $row['id']; ?>">
<button type="submit" name="kirim_sertifikat" class="table-icon-btn btn-edit-icon" title="Kirim ulang sertifikat ke email peserta">
<i class="fa-solid fa-envelope"></i>
</button>
</form>

<div class="aksi-more-wrap">
<button type="button" class="table-icon-btn btn-more-icon aksi-more-toggle" title="Menu lainnya">
<i class="fa-solid fa-ellipsis-vertical"></i>
</button>
<div class="aksi-more-menu">
<button type="button" class="btn-edit-sertifikat" data-id="<?= $row['id']; ?>" data-nilai="<?= (int) $row['score']; ?>" data-penguasaan="<?= (int) $row['mastery']; ?>" data-nama="<?= e($row['nama']); ?>"><i class="fa-solid fa-pen"></i> Edit Sertifikat</button>
<a href="sertifikat.php?hapus_sertifikat=<?= $row['id']; ?>" onclick="return confirm('Hapus sertifikat \'<?= e($row['certificate_number']); ?>\' milik \'<?= e($row['nama']); ?>\'? Tindakan ini tidak bisa dibatalkan.');" class="danger"><i class="fa-solid fa-trash"></i> Hapus Sertifikat</a>
</div>
</div>

</td>

</tr>

<?php } } ?>

</tbody>

</table>

</div>

<div class="admin-pagination">

<p class="admin-pagination-info">
Menampilkan <?= $totalRows === 0 ? 0 : $offset + 1; ?> - <?= min($offset + $perPage, $totalRows); ?> dari <?= $totalRows; ?> sertifikat
</p>

<?php if ($totalPages > 1) { ?>
<div class="admin-pagination-nav">

<?php
$qs = $_GET;
function pageLink($qs, $p) {
    $qs['page'] = $p;
    return 'sertifikat.php?' . http_build_query($qs);
}
?>

<a href="<?= pageLink($qs, max(1, $page - 1)); ?>" class="page-btn <?= $page <= 1 ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-left"></i></a>

<?php for ($p = 1; $p <= $totalPages; $p++) {
    if ($p == 1 || $p == $totalPages || abs($p - $page) <= 1) { ?>
<a href="<?= pageLink($qs, $p); ?>" class="page-btn <?= $p === $page ? 'active' : ''; ?>"><?= $p; ?></a>
<?php } elseif (abs($p - $page) == 2) { ?>
<span class="page-dots">...</span>
<?php }
} ?>

<a href="<?= pageLink($qs, min($totalPages, $page + 1)); ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-right"></i></a>

</div>
<?php } ?>

</div>

</div>

<?php require __DIR__ . "/partials/footer.php"; ?>

</main>

</div>

<div class="user-view-overlay" id="userViewOverlay">
<div class="user-view-modal lb-view-modal">

<button type="button" class="user-view-close" id="userViewClose"><i class="fa-solid fa-xmark"></i></button>

<span class="cert-modal-icon"><i class="fa-solid fa-certificate"></i></span>

<h3 id="userViewNama"></h3>
<p class="user-view-username" id="userViewNomor"></p>

<div class="user-view-grid">

<div>
<span>Email</span>
<strong id="userViewEmail"></strong>
</div>

<div>
<span>Status</span>
<strong id="userViewStatus"></strong>
</div>

<div>
<span>Nilai Akhir</span>
<strong id="userViewNilai"></strong>
</div>

<div>
<span>Penguasaan Materi</span>
<strong id="userViewPenguasaan"></strong>
</div>

<div>
<span>Tanggal Terbit</span>
<strong id="userViewTanggal"></strong>
</div>

</div>

<a href="#" target="_blank" rel="noopener" id="userViewVerifyLink" class="btn btn-sm lb-detail-btn"><i class="fa-solid fa-shield-halved"></i> Buka Halaman Verifikasi</a>

<div class="lb-history-block">
<h4>Riwayat Aktivitas</h4>
<div id="userViewRiwayat" class="lb-history-list"></div>
</div>

</div>
</div>

<div class="user-view-overlay" id="certEditOverlay">
<div class="user-view-modal">

<button type="button" class="user-view-close" id="certEditClose"><i class="fa-solid fa-xmark"></i></button>

<span class="cert-modal-icon"><i class="fa-solid fa-pen"></i></span>

<h3 id="certEditNama"></h3>
<p class="user-view-username">Edit Sertifikat</p>

<form method="POST" class="admin-form">
<input type="hidden" name="sertifikat_id" id="certEditId">

<label for="certEditNilai">Nilai Akhir</label>
<input type="number" name="nilai_akhir" id="certEditNilai" min="0" max="100" required>

<label for="certEditPenguasaan">Penguasaan Materi (%)</label>
<input type="number" name="penguasaan" id="certEditPenguasaan" min="0" max="100" required>

<button type="submit" name="edit_sertifikat" class="btn btn-primary" style="width:100%;margin-top:14px;">Simpan Perubahan</button>

</form>

</div>
</div>

<script>
(function(){
    var overlay = document.getElementById("userViewOverlay");
    var closeBtn = document.getElementById("userViewClose");
    var riwayatBox = document.getElementById("userViewRiwayat");
    var verifyLink = document.getElementById("userViewVerifyLink");

    document.querySelectorAll(".btn-view[data-nomor]").forEach(function(btn){
        btn.addEventListener("click", function(){
            document.getElementById("userViewNama").textContent = btn.dataset.nama;
            document.getElementById("userViewNomor").textContent = btn.dataset.nomor;
            document.getElementById("userViewEmail").textContent = btn.dataset.email;
            document.getElementById("userViewStatus").textContent = btn.dataset.status;
            document.getElementById("userViewNilai").textContent = btn.dataset.nilai + " / 100";
            document.getElementById("userViewPenguasaan").textContent = btn.dataset.penguasaan;
            document.getElementById("userViewTanggal").textContent = btn.dataset.tanggal;
            verifyLink.href = btn.dataset.verify;

            var riwayat = [];
            try { riwayat = JSON.parse(btn.dataset.riwayat || "[]"); } catch (e) { riwayat = []; }

            riwayatBox.innerHTML = "";
            if (riwayat.length === 0) {
                riwayatBox.innerHTML = '<p class="text-muted">Belum ada riwayat aktivitas.</p>';
            } else {
                riwayat.forEach(function(r){
                    var item = document.createElement("div");
                    item.className = "lb-history-item";
                    item.innerHTML = '<div><strong>' + r.judul + '</strong><span>' + r.tanggal + '</span></div>';
                    riwayatBox.appendChild(item);
                });
            }

            overlay.classList.add("show");
        });
    });

    function closeOverlay(){
        overlay.classList.remove("show");
    }

    closeBtn.addEventListener("click", closeOverlay);
    overlay.addEventListener("click", function(e){
        if (e.target === overlay) closeOverlay();
    });

    var editOverlay = document.getElementById("certEditOverlay");
    var editClose = document.getElementById("certEditClose");

    document.querySelectorAll(".btn-edit-sertifikat").forEach(function(btn){
        btn.addEventListener("click", function(){
            document.getElementById("certEditId").value = btn.dataset.id;
            document.getElementById("certEditNilai").value = btn.dataset.nilai;
            document.getElementById("certEditPenguasaan").value = btn.dataset.penguasaan;
            document.getElementById("certEditNama").textContent = btn.dataset.nama;
            editOverlay.classList.add("show");
        });
    });

    editClose.addEventListener("click", function(){ editOverlay.classList.remove("show"); });
    editOverlay.addEventListener("click", function(e){
        if (e.target === editOverlay) editOverlay.classList.remove("show");
    });

    function closeAllMoreMenus(){
        document.querySelectorAll(".aksi-more-menu.open").forEach(function(m){ m.classList.remove("open"); });
    }

    function positionMoreMenu(btn, menu){
        var rect = btn.getBoundingClientRect();
        var menuWidth = menu.offsetWidth;
        var menuHeight = menu.offsetHeight;
        var left = rect.right - menuWidth;
        var top = rect.bottom + 6;

        if (left < 8) left = 8;
        if (top + menuHeight > window.innerHeight - 8) top = rect.top - menuHeight - 6;

        menu.style.left = left + "px";
        menu.style.top = top + "px";
    }

    document.querySelectorAll(".aksi-more-toggle").forEach(function(btn){
        btn.addEventListener("click", function(e){
            e.stopPropagation();
            var menu = btn.nextElementSibling;
            var wasOpen = menu.classList.contains("open");
            closeAllMoreMenus();
            if (!wasOpen) {
                menu.classList.add("open");
                positionMoreMenu(btn, menu);
            }
        });
    });

    window.addEventListener("scroll", closeAllMoreMenus, true);
    window.addEventListener("resize", closeAllMoreMenus);

    document.addEventListener("click", function(){
        document.querySelectorAll(".aksi-more-menu.open").forEach(function(m){ m.classList.remove("open"); });
    });
})();
</script>

</body>

</html>
