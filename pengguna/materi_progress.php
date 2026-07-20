<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

header('Content-Type: application/json');

$materiId = (int) ($_POST['materi_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($materiId <= 0 || !in_array($action, ['buka', 'selesai'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT xp, judul FROM materi WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $materiId);
mysqli_stmt_execute($stmt);
$materi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$materi) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Materi tidak ditemukan.']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT status FROM materi_progress WHERE user_id=? AND materi_id=?");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $materiId);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$xpAwarded = 0;

if ($action === 'buka') {

    if (!$existing) {
        $stmt = mysqli_prepare($conn, "INSERT INTO materi_progress (user_id, materi_id, status) VALUES (?, ?, 'proses')");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $materiId);
        mysqli_stmt_execute($stmt);
    }

    $status = $existing['status'] ?? 'proses';

} else {

    $status = 'selesai';

    if (!$existing) {
        $xpAwarded = (int) $materi['xp'];
        $stmt = mysqli_prepare($conn, "INSERT INTO materi_progress (user_id, materi_id, status, xp_diberikan, selesai_pada) VALUES (?, ?, 'selesai', ?, NOW())");
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $materiId, $xpAwarded);
        mysqli_stmt_execute($stmt);
    } elseif ($existing['status'] !== 'selesai') {
        $xpAwarded = (int) $materi['xp'];
        $stmt = mysqli_prepare($conn, "UPDATE materi_progress SET status='selesai', xp_diberikan=?, selesai_pada=NOW() WHERE user_id=? AND materi_id=?");
        mysqli_stmt_bind_param($stmt, "iii", $xpAwarded, $user_id, $materiId);
        mysqli_stmt_execute($stmt);
    }

    if ($xpAwarded > 0) {
        $xpLama = (int) $user['xp'];

        $stmt = mysqli_prepare($conn, "UPDATE users SET xp = xp + ? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ii", $xpAwarded, $user_id);
        mysqli_stmt_execute($stmt);

        logAktivitas(
            $conn,
            'materi_selesai',
            e($user['nama']) . ' menyelesaikan materi "' . e($materi['judul']) . '".',
            'fa-book-open',
            'teal',
            $user_id
        );

        cekBadgeBaru($conn, $xpLama, $xpLama + $xpAwarded, $user_id, $user['nama']);
    }
}

$totalXp = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT xp FROM users WHERE id=$user_id"))['xp'];

echo json_encode([
    'status' => 'ok',
    'materiStatus' => $status,
    'xpAwarded' => $xpAwarded,
    'totalXp' => $totalXp,
]);
