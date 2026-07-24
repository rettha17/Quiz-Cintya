<?php
session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/../inc/functions.php";

if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}

function asset($path) {
    return $path . "?v=" . filemtime(__DIR__ . "/" . $path);
}

// Halaman ini hanya boleh diakses setelah minta OTP lewat lupa_password.php.
if (!isset($_SESSION['otp_user_id'])) {
    header("Location: lupa_password.php");
    exit;
}

$pesan = "";
$infoResend = "";
$maxAttempts = 5;
$resendCooldown = 60;

// Kirim ulang kode OTP.
if (isset($_POST['resend'])) {

    $sisaCooldown = $resendCooldown - (time() - ($_SESSION['otp_last_sent'] ?? 0));

    if ($sisaCooldown > 0) {

        $pesan = "Tunggu " . $sisaCooldown . " detik lagi sebelum minta kode baru.";

    } else {

        $otp = createPasswordResetOtp($conn, $_SESSION['otp_user_id'], $_SESSION['otp_email']);
        sendOtpEmail($_SESSION['otp_email'], $_SESSION['otp_nama'], $otp);

        $_SESSION['otp_attempts'] = 0;
        $_SESSION['otp_last_sent'] = time();
        $_SESSION['otp_expires_at'] = time() + 300;

        $infoResend = "Kode OTP baru sudah dikirim ke emailmu.";

    }

}

// Verifikasi kode OTP yang dimasukkan.
if (isset($_POST['verifikasi'])) {

    if (($_SESSION['otp_attempts'] ?? 0) >= $maxAttempts) {

        $pesan = "Terlalu banyak percobaan salah. Silakan minta kode OTP baru.";

    } else {

        $otpInput = preg_replace('/[^0-9]/', '', $_POST['otp_full'] ?? '');

        if (strlen($otpInput) !== 6) {

            $pesan = "Masukkan 6 digit kode OTP.";

        } else {

            $valid = getValidPasswordResetOtp($conn, $_SESSION['otp_user_id'], $otpInput);

            if ($valid) {

                markPasswordResetOtpUsed($conn, $valid['id']);

                $_SESSION['otp_verified'] = true;
                unset($_SESSION['otp_attempts'], $_SESSION['otp_last_sent'], $_SESSION['otp_expires_at']);

                header("Location: reset_password.php");
                exit;

            } else {

                $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;

                // Cek apakah OTP terakhir milik user ini sudah kedaluwarsa (untuk pesan yang lebih tepat).
                $stmt = mysqli_prepare($conn, "
                    SELECT expired_at FROM password_reset_otp
                    WHERE user_id=? AND is_used=0
                    ORDER BY id DESC LIMIT 1
                ");
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['otp_user_id']);
                mysqli_stmt_execute($stmt);
                $latest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if ($latest && strtotime($latest['expired_at']) < time()) {
                    $pesan = "Kode OTP telah kedaluwarsa. Silakan minta kode baru.";
                } else {
                    $pesan = "Kode OTP salah. Sisa percobaan: " . ($maxAttempts - $_SESSION['otp_attempts']) . ".";
                }

            }

        }

    }

}

$emailSamaran = $_SESSION['otp_email'];
$atPos = strpos($emailSamaran, '@');
if ($atPos !== false && $atPos > 1) {
    $emailSamaran = substr($emailSamaran, 0, 2) . str_repeat('*', $atPos - 2) . substr($emailSamaran, $atPos);
}

$expiresAt = $_SESSION['otp_expires_at'] ?? (time() + 300);
$sisaDetikCooldown = max(0, $resendCooldown - (time() - ($_SESSION['otp_last_sent'] ?? 0)));
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Verifikasi OTP | StatQuest BPS</title>

<link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . "/assets/css/login.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="login-page login-page-quest">

<div class="login-main">

<div class="login-hero">

<div class="hero-bg-deco" aria-hidden="true">

<span class="hero-blob hero-blob-1"></span>
<span class="hero-blob hero-blob-2"></span>
<span class="hero-dots"></span>

<svg class="hero-bg-svg" viewBox="0 0 640 720" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">

<g class="hero-clouds">
<ellipse cx="90" cy="70" rx="46" ry="18"/>
<ellipse cx="126" cy="60" rx="34" ry="15"/>
<ellipse cx="540" cy="110" rx="52" ry="20"/>
<ellipse cx="500" cy="122" rx="30" ry="13"/>
</g>

<g class="hero-building">
<rect x="470" y="330" width="70" height="160" rx="4"/>
<rect x="546" y="290" width="56" height="200" rx="4"/>
<rect x="410" y="380" width="54" height="110" rx="4"/>
<rect x="484" y="350" width="10" height="12"/>
<rect x="484" y="374" width="10" height="12"/>
<rect x="484" y="398" width="10" height="12"/>
<rect x="502" y="350" width="10" height="12"/>
<rect x="502" y="374" width="10" height="12"/>
<rect x="502" y="398" width="10" height="12"/>
</g>

<g class="hero-bars">
<rect x="30" y="560" width="34" height="90" rx="4"/>
<rect x="74" y="520" width="34" height="130" rx="4"/>
<rect x="118" y="480" width="34" height="170" rx="4"/>
<rect x="162" y="440" width="34" height="210" rx="4"/>
</g>

<g class="hero-pie">
<circle cx="560" cy="600" r="60"/>
<path d="M560,600 L560,540 A60,60 0 0 1 611,630 Z"/>
</g>

<polyline class="hero-line" points="20,300 90,260 150,290 220,220 290,250 360,180" fill="none"/>
<circle class="hero-line-dot" cx="360" cy="180" r="6"/>

<g class="hero-circles">
<circle cx="600" cy="250" r="26"/>
<circle cx="40" cy="480" r="16"/>
<circle cx="200" cy="620" r="10"/>
</g>

<g class="hero-shapes">
<polygon class="hero-shape-triangle" points="440,470 480,540 400,540"/>
<rect class="hero-shape-square" x="560" y="440" width="46" height="46" rx="8" transform="rotate(18 583 463)"/>
</g>

</svg>

<span class="hero-float-circle hfc-1"></span>
<span class="hero-float-circle hfc-2"></span>

</div>

<div class="quest-hero-topbar">
<img src="assets/img/logo-bps-transparent.png" alt="">
<div><b>BADAN PUSAT STATISTIK</b><span>Mencatat Indonesia</span></div>
</div>

<img src="<?= asset("assets/img/login-badge-icon.png"); ?>" alt="" class="quest-hero-badge">

<div class="quest-title-with-mascot">
<div>
<h1 class="quest-hero-title">StatQuest <span>BPS</span></h1>
<p class="quest-hero-tagline">Jelajah Statistik Indonesia</p>
</div>
<img src="<?= asset("assets/img/mascot-boy-hoodie.png"); ?>" alt="Maskot StatQuest BPS" class="quest-hero-title-mascot">
</div>

<p class="quest-hero-desc">Kami sudah mengirim kode verifikasi ke emailmu. Masukkan kodenya untuk melanjutkan proses reset password akunmu.</p>

<ul class="quest-hero-bullets">
<li><i class="fa-solid fa-shield-halved"></i> Aman &amp; Terenkripsi</li>
<li><i class="fa-solid fa-bolt"></i> Proses Cepat</li>
<li><i class="fa-solid fa-envelope-circle-check"></i> Verifikasi Email</li>
</ul>

</div>

<div class="login-panel">

<div class="login-card">

<img src="<?= asset("assets/img/login-badge-icon.png"); ?>" alt="" class="quest-card-badge">

<div class="quest-title-row">
<h2>Verifikasi OTP</h2>
<p>Masukkan kode OTP yang dikirim ke <b><?= e($emailSamaran); ?></b></p>
</div>

<div class="login-divider"><i class="fa-solid fa-star"></i></div>

<?php if ($pesan != "") { ?>
<div class="error"><?= htmlspecialchars($pesan); ?></div>
<?php } ?>

<?php if ($infoResend != "") { ?>
<div class="success"><?= htmlspecialchars($infoResend); ?></div>
<?php } ?>

<form method="POST" id="otpForm">

<input type="hidden" name="otp_full" id="otpFull">

<div class="otp-boxes">
<input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-otp-index="0">
<input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-otp-index="1">
<input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-otp-index="2">
<input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-otp-index="3">
<input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-otp-index="4">
<input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-otp-index="5">
</div>

<div class="otp-timer">
<i class="fa-solid fa-clock"></i> Kode berlaku <span id="otpCountdown">05:00</span>
</div>

<button type="submit" name="verifikasi" class="btn-masuk quest-btn-masuk">
<i class="fa-solid fa-shield-halved"></i> Verifikasi OTP
</button>

</form>

<form method="POST" id="resendForm">
<p class="login-link-text otp-resend-row">
Belum menerima kode?
<button type="submit" name="resend" class="otp-resend-btn" id="resendBtn" <?= $sisaDetikCooldown > 0 ? 'disabled' : ''; ?>>
Kirim Ulang OTP<span id="resendCooldownText"><?= $sisaDetikCooldown > 0 ? ' (' . $sisaDetikCooldown . 's)' : ''; ?></span>
</button>
</p>
</form>

<p class="login-link-text"><a href="lupa_password.php">Ganti Email</a></p>

</div>

</div>

</div>

<div class="login-bottom-strip quest-bottom-strip">

<p class="quest-strip-copyright">&copy; <?= date("Y"); ?> Badan Pusat Statistik. All rights reserved.</p>

</div>

</div>

<script>
(function(){
    var boxes = document.querySelectorAll(".otp-box");
    var full = document.getElementById("otpFull");

    function syncFull(){
        var val = "";
        boxes.forEach(function(b){ val += b.value; });
        full.value = val;
    }

    boxes.forEach(function(box, idx){
        box.addEventListener("input", function(){
            box.value = box.value.replace(/[^0-9]/g, "");
            if (box.value !== "" && idx < boxes.length - 1) {
                boxes[idx + 1].focus();
            }
            syncFull();
        });

        box.addEventListener("keydown", function(e){
            if (e.key === "Backspace" && box.value === "" && idx > 0) {
                boxes[idx - 1].focus();
            }
        });

        box.addEventListener("paste", function(e){
            e.preventDefault();
            var pasted = (e.clipboardData || window.clipboardData).getData("text").replace(/[^0-9]/g, "");
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].value = pasted[i] || "";
            }
            syncFull();
            var nextEmpty = Array.prototype.findIndex.call(boxes, function(b){ return b.value === ""; });
            boxes[nextEmpty === -1 ? boxes.length - 1 : nextEmpty].focus();
        });
    });

    if (boxes.length) {
        boxes[0].focus();
    }
})();

(function(){
    var countdownEl = document.getElementById("otpCountdown");
    var expiresAt = <?= (int) $expiresAt; ?> * 1000;

    function tick(){
        var sisa = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        var menit = Math.floor(sisa / 60);
        var detik = sisa % 60;
        countdownEl.textContent = String(menit).padStart(2, "0") + ":" + String(detik).padStart(2, "0");

        if (sisa <= 0) {
            countdownEl.textContent = "00:00";
            countdownEl.classList.add("otp-expired");
            clearInterval(timer);
        }
    }

    tick();
    var timer = setInterval(tick, 1000);
})();

(function(){
    var resendBtn = document.getElementById("resendBtn");
    var cooldownText = document.getElementById("resendCooldownText");
    var sisa = <?= (int) $sisaDetikCooldown; ?>;

    if (sisa <= 0) return;

    var timer = setInterval(function(){
        sisa--;
        if (sisa <= 0) {
            resendBtn.removeAttribute("disabled");
            cooldownText.textContent = "";
            clearInterval(timer);
        } else {
            cooldownText.textContent = " (" + sisa + "s)";
        }
    }, 1000);
})();
</script>

</body>

</html>
