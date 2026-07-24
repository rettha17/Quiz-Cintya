<?php
/**
 * Shared bootstrap for every protected page: starts the session,
 * connects to the DB, and loads the logged-in user (or redirects to login).
 * Usage: require __DIR__ . "/inc/auth.php";
 */

session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/functions.php";

if (getSetting($conn, 'maintenance_mode', '0') === '1') {
    die("
    <h2 align='center' style='margin-top:100px;font-family:sans-serif;'>
    <i class='fa-solid fa-screwdriver-wrench'></i> StatQuest BPS sedang dalam pemeliharaan.
    <br>Silakan coba lagi beberapa saat lagi.
    </h2>
    ");
}

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}
