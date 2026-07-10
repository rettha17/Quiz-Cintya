<?php
/**
 * Shared bootstrap for every admin page: starts the session,
 * connects to the DB, and loads the logged-in admin (or redirects to admin login).
 * Usage: require __DIR__ . "/auth.php";
 */

session_start();
require_once __DIR__ . "/../koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = (int) $_SESSION['admin_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$admin) {
    session_destroy();
    header("Location: login.php");
    exit;
}

function e($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}
