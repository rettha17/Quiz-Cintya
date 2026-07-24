<?php
require __DIR__ . "/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$pesan = "";
$pesanTipe = "success";

/* ===========================
   TAMBAH / EDIT PEMAIN
=========================== */

if (isset($_POST['simpan'])) {

    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $level = max(1, (int) $_POST['level']);
    $xp = max(0, (int) $_POST['xp']);
    $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
    $password = trim($_POST['password']);

    if ($nama === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $pesan = "Nama dan email wajib diisi dengan format yang benar.";
        $pesanTipe = "error";

    } elseif ($id === 0 && $password === '') {

        $pesan = "Password wajib diisi untuk pemain baru.";
        $pesanTipe = "error";

    } else {

        $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? AND id<>?");
        mysqli_stmt_bind_param($cek, "si", $email, $id);
        mysqli_stmt_execute($cek);
        $emailDipakai = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

        if ($emailDipakai) {

            $pesan = "Email tersebut sudah dipakai pemain lain.";
            $pesanTipe = "error";

        } elseif ($id > 0) {

            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama=?, email=?, level=?, xp=?, status=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssiissi", $nama, $email, $level, $xp, $status, $hash, $id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama=?, email=?, level=?, xp=?, status=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssiisi", $nama, $email, $level, $xp, $status, $id);
            }
            mysqli_stmt_execute($stmt);
            $pesan = "Data pemain '$nama' berhasil diperbarui.";

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, level, xp, status, created_at) VALUES (?,?,?,?,?,?,NOW())");
            mysqli_stmt_bind_param($stmt, "sssiis", $nama, $email, $hash, $level, $xp, $status);
            mysqli_stmt_execute($stmt);
            $pesan = "Pemain '$nama' berhasil ditambahkan.";

        }

    }

}

/* ===========================
   HAPUS PEMAIN
=========================== */

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare($conn, "SELECT nama FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($target) {

        $del = mysqli_prepare($conn, "DELETE FROM hasil_quiz WHERE user_id=?");
        mysqli_stmt_bind_param($del, "i", $id);
        mysqli_stmt_execute($del);

        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
        mysqli_stmt_bind_param($del, "i", $id);
        mysqli_stmt_execute($del);

        $pesan = "Pemain '{$target['nama']}' beserta riwayat quiznya berhasil dihapus.";

    } else {

        $pesan = "Pemain tidak ditemukan.";
        $pesanTipe = "error";

    }

}

/* ===========================
   TOGGLE STATUS AKTIF / NONAKTIF
=========================== */

if (isset($_GET['toggle_status'])) {

    $id = (int) $_GET['toggle_status'];

    $stmt = mysqli_prepare($conn, "SELECT nama, status FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($target) {

        $statusBaru = $target['status'] === 'aktif' ? 'nonaktif' : 'aktif';

        $up = mysqli_prepare($conn, "UPDATE users SET status=? WHERE id=?");
        mysqli_stmt_bind_param($up, "si", $statusBaru, $id);
        mysqli_stmt_execute($up);

        $pesan = "Status pemain '{$target['nama']}' diubah menjadi $statusBaru.";

    } else {

        $pesan = "Pemain tidak ditemukan.";
        $pesanTipe = "error";

    }

}

/* ===========================
   DATA UNTUK FORM TAMBAH / EDIT
=========================== */

$editData = ['id' => 0, 'nama' => '', 'email' => '', 'level' => 1, 'xp' => 0, 'status' => 'aktif'];
$formTerbuka = false;

if (isset($_POST['simpan'])) {

    if ($pesanTipe === 'error') {
        $editData = ['id' => $id, 'nama' => $nama, 'email' => $email, 'level' => $level, 'xp' => $xp, 'status' => $status];
        $formTerbuka = true;
    }

} elseif (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT id, nama, email, level, xp, status FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($found) {
        $editData = $found;
        $formTerbuka = true;
    }

} elseif (isset($_GET['tambah'])) {

    $formTerbuka = true;

}

/* ===========================
   PENCARIAN, FILTER & PAGINASI
=========================== */

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filterLevel = isset($_GET['filter_level']) ? $_GET['filter_level'] : '';
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filterTanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : '';

$whereParts = [];
$params = [];
$paramTypes = '';

if ($cari !== '') {
    $whereParts[] = "(u.nama LIKE ? OR u.email LIKE ?)";
    $like = "%$cari%";
    $params[] = $like;
    $params[] = $like;
    $paramTypes .= 'ss';
}

if ($filterLevel === '1-5') {
    $whereParts[] = "u.level BETWEEN 1 AND 5";
} elseif ($filterLevel === '6-10') {
    $whereParts[] = "u.level BETWEEN 6 AND 10";
} elseif ($filterLevel === '11+') {
    $whereParts[] = "u.level >= 11";
}

if ($filterStatus === 'aktif' || $filterStatus === 'nonaktif') {
    $whereParts[] = "u.status = ?";
    $params[] = $filterStatus;
    $paramTypes .= 's';
}

if ($filterTanggal === '7d') {
    $whereParts[] = "u.created_at >= NOW() - INTERVAL 7 DAY";
} elseif ($filterTanggal === '30d') {
    $whereParts[] = "u.created_at >= NOW() - INTERVAL 30 DAY";
} elseif ($filterTanggal === 'bulan_ini') {
    $whereParts[] = "YEAR(u.created_at) = YEAR(CURDATE()) AND MONTH(u.created_at) = MONTH(CURDATE())";
} elseif ($filterTanggal === 'tahun_ini') {
    $whereParts[] = "YEAR(u.created_at) = YEAR(CURDATE())";
}

$whereSql = $whereParts ? ("WHERE " . implode(" AND ", $whereParts)) : "";

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM users u $whereSql");
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
    SELECT u.*,
        (SELECT COUNT(*) FROM hasil_quiz h WHERE h.user_id = u.id) AS total_quiz,
        (SELECT COUNT(*) + 1 FROM users u2 WHERE u2.xp > u.xp) AS posisi
    FROM users u
    $whereSql
    ORDER BY u.xp DESC
    LIMIT ? OFFSET ?
");
$dataParams = $params;
$dataParams[] = $perPage;
$dataParams[] = $offset;
mysqli_stmt_bind_param($dataStmt, $paramTypes . 'ii', ...$dataParams);
mysqli_stmt_execute($dataStmt);
$daftarUsers = mysqli_stmt_get_result($dataStmt);

/* ===========================
   RINGKASAN STATISTIK PEMAIN
=========================== */

$totalPemain = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];

$pemainBaruMinggu = (int) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY
"))['c'];

$rataSkor = (int) round((float) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(AVG(xp),0) v FROM users"))['v']);

$skorTertinggiRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama, xp FROM users ORDER BY xp DESC LIMIT 1"));
$skorTertinggi = (int) ($skorTertinggiRow['xp'] ?? 0);
$skorTertinggiNama = $skorTertinggiRow['nama'] ?? '-';

$hariLabel = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

$baseSebelumWindow = (int) mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM users WHERE created_at < CURDATE() - INTERVAL 6 DAY
"))['c'];

$userHarianResult = mysqli_query($conn, "
    SELECT DATE(created_at) d, COUNT(*) c FROM users
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
");
$userHarianMap = [];
while ($row = mysqli_fetch_assoc($userHarianResult)) {
    $userHarianMap[$row['d']] = (int) $row['c'];
}

$skorHarianResult = mysqli_query($conn, "
    SELECT DATE(tanggal) d, AVG(score) rata, MAX(score) maks FROM hasil_quiz
    WHERE tanggal >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(tanggal)
");
$skorHarianMap = [];
while ($row = mysqli_fetch_assoc($skorHarianResult)) {
    $skorHarianMap[$row['d']] = ['rata' => (float) $row['rata'], 'maks' => (int) $row['maks']];
}

$totalPemainHarian = [];
$pemainBaruHarian = [];
$rataSkorHarian = [];
$skorTertinggiHarian = [];

$kumulatif = $baseSebelumWindow;
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHari = $hariLabel[date('N', strtotime($tgl)) - 1];
    $baruHariIni = $userHarianMap[$tgl] ?? 0;
    $kumulatif += $baruHariIni;

    $totalPemainHarian[] = ['label' => $labelHari, 'total' => $kumulatif];
    $pemainBaruHarian[] = ['label' => $labelHari, 'total' => $baruHariIni];
    $rataSkorHarian[] = ['label' => $labelHari, 'total' => (int) round($skorHarianMap[$tgl]['rata'] ?? 0)];
    $skorTertinggiHarian[] = ['label' => $labelHari, 'total' => $skorHarianMap[$tgl]['maks'] ?? 0];
}

$activeMenu = 'users';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manajemen Pemain | Admin StatQuest BPS</title>

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
<h1><i class="fa-solid fa-users-line"></i> Manajemen Pemain</h1>
<p class="admin-topbar-sub">Kelola seluruh pemain yang terdaftar pada StatQuest BPS.</p>
</div>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-stats admin-stats-v2 cols-4">

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
<span class="stat-card-label">Total Pemain</span>
</div>
<h2><?= $totalPemain; ?></h2>
<?php if ($pemainBaruMinggu > 0) { ?>
<span class="stat-trend up"><i class="fa-solid fa-arrow-up"></i> <?= $pemainBaruMinggu; ?> dari minggu lalu</span>
<?php } else { ?>
<span class="stat-trend flat">Belum ada minggu ini</span>
<?php } ?>
</div>
<div class="stat-card-spark"><?= renderSparkline($totalPemainHarian, '#2a78d6'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon green"><i class="fa-solid fa-user-plus"></i></div>
<span class="stat-card-label">Pemain Baru (Minggu Ini)</span>
</div>
<h2><?= $pemainBaruMinggu; ?></h2>
<span class="stat-trend flat">7 hari terakhir</span>
</div>
<div class="stat-card-spark"><?= renderSparkline($pemainBaruHarian, '#1baf7a'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon purple"><i class="fa-solid fa-trophy"></i></div>
<span class="stat-card-label">Rata-rata Skor</span>
</div>
<h2><?= number_format($rataSkor, 0, ',', '.'); ?></h2>
<span class="stat-trend flat">Berdasarkan XP pemain</span>
</div>
<div class="stat-card-spark"><?= renderSparkline($rataSkorHarian, '#4a3aa7'); ?></div>
</div>

<div class="stat-card-v2">
<div class="stat-card-body">
<div class="stat-card-top">
<div class="stat-card-icon yellow"><i class="fa-solid fa-star"></i></div>
<span class="stat-card-label">Skor Tertinggi</span>
</div>
<h2><?= number_format($skorTertinggi, 0, ',', '.'); ?></h2>
<span class="stat-trend flat">oleh <?= e($skorTertinggiNama); ?></span>
</div>
<div class="stat-card-spark"><?= renderSparkline($skorTertinggiHarian, '#eda100'); ?></div>
</div>

</div>

<div class="admin-panel user-form-panel<?= $formTerbuka ? ' open' : ''; ?>" id="userFormPanel">

<h2><?= $editData['id'] > 0 ? 'Edit Pemain' : 'Tambah Pemain Baru'; ?></h2>

<form method="POST" class="admin-form">

<input type="hidden" name="id" value="<?= (int) $editData['id']; ?>">

<div class="admin-form-row">

<div>
<label>Nama Lengkap</label>
<input type="text" name="nama" value="<?= e($editData['nama']); ?>" required>
</div>

<div>
<label>Email</label>
<input type="email" name="email" value="<?= e($editData['email']); ?>" required>
</div>

</div>

<div class="admin-form-row">

<div>
<label>Level</label>
<input type="number" name="level" min="1" value="<?= (int) $editData['level']; ?>" required>
</div>

<div>
<label>XP / Skor</label>
<input type="number" name="xp" min="0" value="<?= (int) $editData['xp']; ?>" required>
</div>

</div>

<label>Status</label>
<select name="status">
<option value="aktif" <?= $editData['status'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
<option value="nonaktif" <?= $editData['status'] === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
</select>

<br><br>

<label><?= $editData['id'] > 0 ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password'; ?></label>
<input type="password" name="password" <?= $editData['id'] > 0 ? '' : 'required'; ?>>

<br><br>

<button class="btn" type="submit" name="simpan">
<?= $editData['id'] > 0 ? 'Simpan Perubahan' : 'Tambah Pemain'; ?>
</button>

<a class="btn btn-secondary" href="users.php">Batal</a>

</form>

</div>

<div class="admin-panel">

<form method="GET" class="admin-filter user-table-filter">

<div class="user-search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" name="cari" placeholder="Cari pemain (nama, username, email)..." value="<?= e($cari); ?>">
</div>

<select name="filter_level" onchange="this.form.submit()">
<option value="">Semua Level</option>
<option value="1-5" <?= $filterLevel === '1-5' ? 'selected' : ''; ?>>Level 1 - 5</option>
<option value="6-10" <?= $filterLevel === '6-10' ? 'selected' : ''; ?>>Level 6 - 10</option>
<option value="11+" <?= $filterLevel === '11+' ? 'selected' : ''; ?>>Level 11 ke atas</option>
</select>

<select name="filter_status" onchange="this.form.submit()">
<option value="">Semua Status</option>
<option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
<option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
</select>

<select name="filter_tanggal" onchange="this.form.submit()">
<option value="">Semua Tanggal</option>
<option value="7d" <?= $filterTanggal === '7d' ? 'selected' : ''; ?>>7 Hari Terakhir</option>
<option value="30d" <?= $filterTanggal === '30d' ? 'selected' : ''; ?>>30 Hari Terakhir</option>
<option value="bulan_ini" <?= $filterTanggal === 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
<option value="tahun_ini" <?= $filterTanggal === 'tahun_ini' ? 'selected' : ''; ?>>Tahun Ini</option>
</select>

<button class="btn btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>

<?php if ($cari !== '' || $filterLevel !== '' || $filterStatus !== '' || $filterTanggal !== '') { ?>
<a class="btn btn-sm btn-secondary" href="users.php">Reset</a>
<?php } ?>

<a class="btn btn-tambah-pemain" href="users.php?tambah=1#userFormPanel"><i class="fa-solid fa-plus"></i> Tambah Pemain</a>

</form>

<div class="admin-panel-head">
<h2><i class="fa-solid fa-users-line"></i> Daftar Pemain</h2>
<p class="admin-panel-sub">Berikut adalah daftar semua pemain yang terdaftar.</p>
</div>

<div class="admin-table-wrap">

<table class="admin-table">

<thead>
<tr>
<th>No</th>
<th>Avatar</th>
<th>Nama</th>
<th>Username</th>
<th>Email</th>
<th>Skor</th>
<th>Level</th>
<th>Peringkat</th>
<th>Status</th>
<th>Bergabung</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarUsers) == 0) { ?>

<tr><td colspan="11" style="text-align:center;">Tidak ada pemain ditemukan.</td></tr>

<?php } else { $no = $offset + 1; while ($u = mysqli_fetch_assoc($daftarUsers)) {
    $badge = levelBadge($u['level']);
    $username = strtolower(explode('@', $u['email'])[0]);
?>

<tr>

<td><?= $no++; ?></td>

<td><img src="../pengguna/assets/img/<?= !empty($u['foto']) ? e($u['foto']) : 'default.png'; ?>" class="rank-photo" alt=""></td>

<td><?= e($u['nama']); ?></td>

<td class="text-muted">@<?= e($username); ?></td>

<td><?= e($u['email']); ?></td>

<td><strong><?= number_format((int) $u['xp'], 0, ',', '.'); ?></strong></td>

<td>
<span class="level-badge" style="color:<?= $badge['color']; ?>;background:<?= $badge['bg']; ?>;">
<i class="fa-solid fa-star"></i> <?= (int) $u['level']; ?>
</span>
</td>

<td>
<?php if ((int) $u['posisi'] === 1) { ?>
<i class="fa-solid fa-crown rank-icon rank-gold" title="Peringkat 1"></i>
<?php } elseif ((int) $u['posisi'] === 2) { ?>
<i class="fa-solid fa-medal rank-icon rank-silver" title="Peringkat 2"></i>
<?php } elseif ((int) $u['posisi'] === 3) { ?>
<i class="fa-solid fa-medal rank-icon rank-bronze" title="Peringkat 3"></i>
<?php } else { ?>
<?= (int) $u['posisi']; ?>
<?php } ?>
</td>

<td>
<a class="status-badge status-<?= $u['status']; ?>" href="users.php?toggle_status=<?= $u['id']; ?>" onclick="return confirm('Ubah status \'<?= e($u['nama']); ?>\' menjadi <?= $u['status'] === 'aktif' ? 'Nonaktif' : 'Aktif'; ?>?')" title="Klik untuk ubah status">
<i class="fa-solid fa-circle"></i> <?= $u['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
</a>
</td>

<td><?= date('d M Y', strtotime($u['created_at'])); ?></td>

<td class="user-actions">

<button type="button" class="table-icon-btn btn-view"
    data-nama="<?= e($u['nama']); ?>"
    data-email="<?= e($u['email']); ?>"
    data-username="<?= e($username); ?>"
    data-level="<?= (int) $u['level']; ?>"
    data-xp="<?= number_format((int) $u['xp'], 0, ',', '.'); ?>"
    data-quiz="<?= (int) $u['total_quiz']; ?>"
    data-posisi="<?= (int) $u['posisi']; ?>"
    data-status="<?= $u['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>"
    data-bergabung="<?= date('d M Y', strtotime($u['created_at'])); ?>"
    data-foto="../pengguna/assets/img/<?= !empty($u['foto']) ? e($u['foto']) : 'default.png'; ?>"
    title="Lihat detail">
<i class="fa-solid fa-eye"></i>
</button>

<a class="table-icon-btn btn-edit-icon" href="users.php?edit=<?= $u['id']; ?>#userFormPanel" title="Edit pemain">
<i class="fa-solid fa-pen"></i>
</a>

<a class="table-icon-btn btn-hapus-icon" href="users.php?hapus=<?= $u['id']; ?>" onclick="return confirm('Hapus pemain \'<?= e($u['nama']); ?>\' beserta seluruh riwayat quiznya? Tindakan ini tidak bisa dibatalkan.')" title="Hapus pemain">
<i class="fa-solid fa-trash"></i>
</a>

</td>

</tr>

<?php } } ?>

</tbody>

</table>

</div>

<div class="admin-pagination">

<p class="admin-pagination-info">
Menampilkan <?= $totalRows === 0 ? 0 : $offset + 1; ?> - <?= min($offset + $perPage, $totalRows); ?> dari <?= $totalRows; ?> pemain
</p>

<?php if ($totalPages > 1) { ?>
<div class="admin-pagination-nav">

<?php
$qs = $_GET;
function pageLink($qs, $p) {
    $qs['page'] = $p;
    return 'users.php?' . http_build_query($qs);
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
<div class="user-view-modal">

<button type="button" class="user-view-close" id="userViewClose"><i class="fa-solid fa-xmark"></i></button>

<img src="" alt="" class="user-view-avatar" id="userViewAvatar">

<h3 id="userViewNama"></h3>
<p class="user-view-username" id="userViewUsername"></p>

<div class="user-view-grid">

<div>
<span>Email</span>
<strong id="userViewEmail"></strong>
</div>

<div>
<span>Level</span>
<strong id="userViewLevel"></strong>
</div>

<div>
<span>Skor (XP)</span>
<strong id="userViewXp"></strong>
</div>

<div>
<span>Peringkat</span>
<strong id="userViewPosisi"></strong>
</div>

<div>
<span>Status</span>
<strong id="userViewStatus"></strong>
</div>

<div>
<span>Quiz Dikerjakan</span>
<strong id="userViewQuiz"></strong>
</div>

<div>
<span>Bergabung</span>
<strong id="userViewBergabung"></strong>
</div>

</div>

</div>
</div>

<script>
(function(){
    var overlay = document.getElementById("userViewOverlay");
    var closeBtn = document.getElementById("userViewClose");

    document.querySelectorAll(".btn-view").forEach(function(btn){
        btn.addEventListener("click", function(){
            document.getElementById("userViewAvatar").src = btn.dataset.foto;
            document.getElementById("userViewNama").textContent = btn.dataset.nama;
            document.getElementById("userViewUsername").textContent = "@" + btn.dataset.username;
            document.getElementById("userViewEmail").textContent = btn.dataset.email;
            document.getElementById("userViewLevel").textContent = "Level " + btn.dataset.level;
            document.getElementById("userViewXp").textContent = btn.dataset.xp;
            document.getElementById("userViewPosisi").textContent = "#" + btn.dataset.posisi;
            document.getElementById("userViewStatus").textContent = btn.dataset.status;
            document.getElementById("userViewQuiz").textContent = btn.dataset.quiz;
            document.getElementById("userViewBergabung").textContent = btn.dataset.bergabung;
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
})();
</script>

</body>

</html>
