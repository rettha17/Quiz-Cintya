<?php
require_once __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$filterKategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$filterPeriode = leaderboardPeriodeValid($_GET['periode'] ?? 'sepanjang');

$ranking = getLeaderboardRanking($conn, $filterKategori, $filterPeriode, 10);
$myRank = getUserRank($conn, $user_id, $filterKategori, $filterPeriode);
$myStats = getUserLeaderboardStats($conn, $user_id, $filterKategori, $filterPeriode);
$topKategori = getUserTopKategori($conn, $user_id, 3);

ob_start();
require __DIR__ . "/leaderboard_rows.php";
$rowsHtml = ob_get_clean();

ob_start();
require __DIR__ . "/leaderboard_sidebar.php";
$sidebarHtml = ob_get_clean();

header("Content-Type: application/json");
echo json_encode([
    'rows_html' => $rowsHtml,
    'sidebar_html' => $sidebarHtml,
    'updated_at' => date("H:i:s"),
]);
