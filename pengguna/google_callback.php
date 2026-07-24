<?php
session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/../google_config.php";

function google_login_fail() {
    header("Location: login.php?google_error=1");
    exit;
}

if (!isset($_GET['code'], $_GET['state']) || $_GET['state'] !== ($_SESSION['google_oauth_state'] ?? '')) {
    google_login_fail();
}

unset($_SESSION['google_oauth_state']);

$redirectUri = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/google_callback.php';

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]),
]);
$tokenResponse = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($tokenResponse['access_token'])) {
    google_login_fail();
}

$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenResponse['access_token']],
]);
$profile = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($profile['email'])) {
    google_login_fail();
}

$email = $profile['email'];
$nama  = $profile['name'] ?? explode('@', $email)[0];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($user && $user['status'] === 'nonaktif') {

    header("Location: login.php?google_error=nonaktif");
    exit;

} elseif ($user) {

    $_SESSION['id']   = $user['id'];
    $_SESSION['nama'] = $user['nama'];

} else {

    // Akun baru dari Google - password diacak karena tidak dipakai untuk login manual.
    $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $randomPassword);
    mysqli_stmt_execute($stmt);

    $_SESSION['id']   = mysqli_insert_id($conn);
    $_SESSION['nama'] = $nama;

}

header("Location: dashboard.php");
exit;
