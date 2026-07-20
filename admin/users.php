<?php
require __DIR__ . "/auth.php";

$pesan = "";
$pesanTipe = "success";

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
   PENCARIAN
=========================== */

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if ($cari !== '') {
    $stmt = mysqli_prepare($conn, "
        SELECT u.*, (SELECT COUNT(*) FROM hasil_quiz h WHERE h.user_id = u.id) AS total_quiz
        FROM users u
        WHERE u.nama LIKE ? OR u.email LIKE ?
        ORDER BY u.xp DESC
    ");
    $like = "%$cari%";
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $daftarUsers = mysqli_stmt_get_result($stmt);
} else {
    $daftarUsers = mysqli_query($conn, "
        SELECT u.*, (SELECT COUNT(*) FROM hasil_quiz h WHERE h.user_id = u.id) AS total_quiz
        FROM users u
        ORDER BY u.xp DESC
    ");
}

$activeMenu = 'users';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola Pemain | Admin Quiz Challenge BPS</title>

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

<h1>Kelola Pemain</h1>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="admin-panel">

<h2>Daftar Pemain</h2>

<form method="GET" class="admin-filter">

<input type="text" name="cari" placeholder="Cari nama atau email..." value="<?= e($cari); ?>" style="flex:1;max-width:320px;padding:9px 12px;border-radius:8px;border:1px solid #ddd;">

<button class="btn btn-sm" type="submit">Cari</button>

<?php if ($cari !== '') { ?>
<a class="btn btn-sm btn-secondary" href="users.php">Reset</a>
<?php } ?>

</form>

<table class="admin-table">

<thead>
<tr>
<th>No</th>
<th>Nama</th>
<th>Email</th>
<th>Level</th>
<th>XP</th>
<th>Quiz Dikerjakan</th>
<th>Bergabung</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($daftarUsers) == 0) { ?>

<tr><td colspan="8" style="text-align:center;">Tidak ada pemain ditemukan.</td></tr>

<?php } else { $no = 1; while ($u = mysqli_fetch_assoc($daftarUsers)) { ?>

<tr>

<td><?= $no++; ?></td>

<td><?= e($u['nama']); ?></td>

<td><?= e($u['email']); ?></td>

<td><?= (int) $u['level']; ?></td>

<td><?= (int) $u['xp']; ?></td>

<td><?= (int) $u['total_quiz']; ?></td>

<td><?= date('d M Y', strtotime($u['created_at'])); ?></td>

<td>

<a class="btn btn-sm btn-hapus" href="users.php?hapus=<?= $u['id']; ?>" onclick="return confirm('Hapus pemain \'<?= e($u['nama']); ?>\' beserta seluruh riwayat quiznya? Tindakan ini tidak bisa dibatalkan.')">Hapus</a>

</td>

</tr>

<?php } } ?>

</tbody>

</table>

</div>

</main>

</div>

</body>

</html>
