<?php
require __DIR__ . "/../inc/auth.php";
require_once __DIR__ . "/../inc/functions.php";

if (!isset($_GET['kategori'])) {
    header("Location: kategori.php");
    exit;
}

$kategori = (int) $_GET['kategori'];
$level = levelValid($_GET['level'] ?? 'Mudah');
$nomor = isset($_GET['no']) ? (int) $_GET['no'] : 0;
$progressKey = quizProgressKey($kategori, $level);

/* ===========================
   Ambil soal (baru diacak ulang & skor direset
   setiap kali quiz dimulai dari soal pertama)
   - checkpoint disimpan per kategori+level, jadi berpindah
     kategori/level lain tidak menghapus progress yang ini.
=========================== */

if ($nomor === 0 || empty($_SESSION['quiz_checkpoints'][$progressKey]['soal'])) {

    $stmt = mysqli_prepare($conn, "SELECT id FROM soal WHERE kategori_id=? AND level=? ORDER BY RAND()");
    mysqli_stmt_bind_param($stmt, "is", $kategori, $level);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $daftarSoal = [];
    while ($d = mysqli_fetch_assoc($res)) {
        $daftarSoal[] = $d['id'];
    }

    $_SESSION['quiz_checkpoints'][$progressKey] = [
        'soal' => $daftarSoal,
        'skor' => 0,
        'xp' => 0,
        'nyawa' => 5,
        'jumlah_benar' => 0,
        'jumlah_salah' => 0,
        'waktu_mulai' => time(),
        'posisi_terakhir' => 0,
    ];
}

$checkpoint = &$_SESSION['quiz_checkpoints'][$progressKey];

$skorSaatIni = $checkpoint['skor'] ?? 0;
$nyawa = $checkpoint['nyawa'] ?? 5;
$durasiWaktu = levelDurasiWaktu($level);

if (empty($checkpoint['soal'])) {
    die("
    <div style='text-align:center;margin-top:100px;font-family:sans-serif;'>
    <h2>Belum ada soal untuk level ini.</h2>
    <a href='kategori.php'>Kembali ke Kategori</a>
    </div>
    ");
}

$jumlah = count($checkpoint['soal']);

if ($nomor >= $jumlah) {
    header("Location: hasil.php?kategori=$kategori&level=$level");
    exit;
}

$id_soal = $checkpoint['soal'][$nomor];

$stmt = mysqli_prepare($conn, "SELECT * FROM soal WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id_soal);
mysqli_stmt_execute($stmt);
$current = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$stmt = mysqli_prepare($conn, "SELECT nama FROM kategori WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $kategori);
mysqli_stmt_execute($stmt);
$kategoriInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$temaQuiz = temaQuizAktif($kategoriInfo['nama'] ?? '', $level);
$temaKebun = ($temaQuiz === 'kebun');
$temaBajakLaut = ($temaQuiz === 'bajak-laut');
$temaLebah = ($temaQuiz === 'lebah');
$temaPanda = ($temaQuiz === 'panda');
$temaKatak = ($temaQuiz === 'katak');
$temaPenguin = ($temaQuiz === 'penguin');
$temaAstronot = ($temaQuiz === 'astronot');
$temaKsatria = ($temaQuiz === 'ksatria');
$bungaEmoji = ['A' => '🌺', 'B' => '🪻', 'C' => '🌻', 'D' => '🌼'];
$bungaEmojiLebah = ['A' => '🌸', 'B' => '🪷', 'C' => '🌻', 'D' => '🌼'];
$seranggaEmoji = ['A' => '🪰', 'B' => '🦟', 'C' => '🦋', 'D' => '🪲'];
$ikanEmoji = ['A' => '🐟', 'B' => '🐠', 'C' => '🦑', 'D' => '🦀'];
$angkasaEmoji = ['A' => '⭐', 'B' => '🪐', 'C' => '☄️', 'D' => '🌙'];
$monsterEmoji = ['A' => '🐲', 'B' => '👹', 'C' => '🦇', 'D' => '🕷️'];

$temaSceneClass = $temaKebun ? ' tema-kebun' : ($temaBajakLaut ? ' tema-bajak-laut' : ($temaLebah ? ' tema-lebah' : ($temaPanda ? ' tema-panda' : ($temaKatak ? ' tema-katak' : ($temaPenguin ? ' tema-penguin' : ($temaAstronot ? ' tema-astronot' : ($temaKsatria ? ' tema-ksatria' : '')))))));
$temaKarakter = $temaBajakLaut ? '🏴‍☠️' : ($temaKebun ? '🧑‍🌾' : ($temaLebah ? '🐝' : ($temaPanda ? '🐼' : ($temaKatak ? '🐸' : ($temaPenguin ? '🐧' : ($temaAstronot ? '🧑‍🚀' : ($temaKsatria ? '🤺' : '🧑‍🚒')))))));
$temaHitIcon = $temaBajakLaut ? '✨' : ($temaKebun ? '💧' : ($temaLebah ? '🍯' : ($temaPanda ? '🍃' : ($temaKatak ? '👅' : ($temaPenguin ? '💦' : ($temaAstronot ? '✨' : ($temaKsatria ? '💥' : '💨')))))));
$temaLabelSisa = $temaBajakLaut ? 'Kait Tersisa' : ($temaKebun ? 'Air Tersisa' : ($temaLebah ? 'Nektar Tersisa' : ($temaPanda ? 'Daun Tersisa' : ($temaKatak ? 'Serangga Tersisa' : ($temaPenguin ? 'Ikan Tersisa' : ($temaAstronot ? 'Energi Tersisa' : ($temaKsatria ? 'Serangan Tersisa' : 'Semprotan Tersisa')))))));
$temaSmoke1 = $temaBajakLaut ? '🦜' : ($temaKebun ? '🦋' : ($temaLebah ? '🍯' : ($temaPanda ? '🍃' : ($temaKatak ? '🪷' : ($temaPenguin ? '❄️' : ($temaAstronot ? '⭐' : ($temaKsatria ? '🔥' : '💨')))))));
$temaSmoke2 = $temaBajakLaut ? '⚓' : ($temaKebun ? '🍃' : ($temaLebah ? '🌻' : ($temaPanda ? '🌱' : ($temaKatak ? '🌿' : ($temaPenguin ? '💠' : ($temaAstronot ? '🌌' : ($temaKsatria ? '⚔️' : '💨')))))));
$temaPanahIcon = $temaBajakLaut ? 'fa-anchor' : ($temaPanda ? 'fa-leaf' : ($temaKatak ? 'fa-bug' : ($temaPenguin ? 'fa-fish' : ($temaAstronot ? 'fa-star' : ($temaKsatria ? 'fa-shield-halved' : 'fa-droplet')))));
$temaPanahColor = $temaBajakLaut ? '#37474F' : ($temaKebun ? '#0288D1' : ($temaLebah ? '#F9A825' : ($temaPanda ? '#43A047' : ($temaKatak ? '#33691E' : ($temaPenguin ? '#0288D1' : ($temaAstronot ? '#FDD835' : ($temaKsatria ? '#8D6E63' : '#039BE5')))))));

function temaIkonOpsi($opt, $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji) {
    if ($temaBajakLaut) {
        return '🔒';
    }
    if ($temaKebun) {
        return $bungaEmoji[$opt];
    }
    if ($temaLebah) {
        return $bungaEmojiLebah[$opt];
    }
    if ($temaPanda) {
        return '🎋';
    }
    if ($temaKatak) {
        return $seranggaEmoji[$opt];
    }
    if ($temaPenguin) {
        return $ikanEmoji[$opt];
    }
    if ($temaAstronot) {
        return $angkasaEmoji[$opt];
    }
    if ($temaKsatria) {
        return $monsterEmoji[$opt];
    }
    return '🔥';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>StatQuest BPS</title>

<script>
document.documentElement.setAttribute("data-theme", localStorage.getItem("quizbps-theme") || "light");
</script>

<link rel="stylesheet"
href="assets/css/quiz.css?v=<?= filemtime(__DIR__ . "/assets/css/quiz.css"); ?>">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="quiz-container">

<a href="kategori.php?keluar=1&kategori=<?= $kategori; ?>&level=<?= e($level); ?>" class="quiz-back" onclick="return confirm('Keluar dari quiz? Progress soal saat ini akan hilang.')">

<i class="fa-solid fa-arrow-left"></i> Kembali

</a>

<div class="hud">

<div class="hud-item hud-nyawa" id="nyawaBox">

<?php for ($i = 0; $i < 5; $i++): ?>

<i class="fa-solid fa-heart <?= $i < $nyawa ? 'heart-active' : 'heart-lost'; ?>"></i>

<?php endfor; ?>

</div>

<div class="hud-item hud-skor">

<i class="fa-solid fa-star"></i> <?= $skorSaatIni; ?>

</div>

<div class="hud-item hud-soal">

<i class="fa-solid fa-bullseye"></i> <?= $nomor+1 ?>/<?= $jumlah ?>

</div>

<div class="hud-item hud-timer">

<i class="fa-solid fa-clock"></i> <span id="time">00:15</span>

</div>

<button type="button" class="hud-pause" id="pauseBtn" title="Jeda">

<i class="fa-solid fa-pause"></i>

</button>

</div>

<h2 class="misi-judul">

<?= e($kategoriInfo['nama'] ?? 'StatQuest BPS'); ?> - <?= e($level); ?>

</h2>

<div class="question-card">

<h3><?= e($current['pertanyaan']); ?></h3>

</div>

<form action="cek_jawaban.php" method="POST" id="quizForm">

<input type="hidden" name="id_soal" value="<?= $current['id']; ?>">
<input type="hidden" name="kategori" value="<?= $kategori; ?>">
<input type="hidden" name="level" value="<?= e($level); ?>">
<input type="hidden" name="nomor" value="<?= $nomor; ?>">
<input type="hidden" name="jawaban" id="jawabanInput" value="">

<div class="fire-scene<?= $temaSceneClass; ?>" data-hit-icon="<?= e($temaHitIcon); ?>">

<span class="smoke-bg smoke1"><?= $temaSmoke1; ?></span>
<span class="smoke-bg smoke2"><?= $temaSmoke2; ?></span>

<div class="firefighter-wrap">
<div class="firefighter"><?= $temaKarakter; ?></div>
</div>

<?php if ($temaLebah): ?>
<div class="sarang-lebah">🍯</div>
<?php endif; ?>

<div class="targets" id="targets">

<button type="button" class="target-unit" data-jawaban="A">
<span class="window window-a">
<span class="window-badge">A</span>
<span class="flame"><?= temaIkonOpsi('A', $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji); ?></span>
</span>
<span class="target-label label-a"><?= e($current['opsi_a']); ?></span>
</button>

<button type="button" class="target-unit" data-jawaban="B">
<span class="window window-b">
<span class="window-badge">B</span>
<span class="flame"><?= temaIkonOpsi('B', $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji); ?></span>
</span>
<span class="target-label label-b"><?= e($current['opsi_b']); ?></span>
</button>

<button type="button" class="target-unit" data-jawaban="C">
<span class="window window-c">
<span class="window-badge">C</span>
<span class="flame"><?= temaIkonOpsi('C', $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji); ?></span>
</span>
<span class="target-label label-c"><?= e($current['opsi_c']); ?></span>
</button>

<button type="button" class="target-unit" data-jawaban="D">
<span class="window window-d">
<span class="window-badge">D</span>
<span class="flame"><?= temaIkonOpsi('D', $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji); ?></span>
</span>
<span class="target-label label-d"><?= e($current['opsi_d']); ?></span>
</button>

</div>

<div class="water-fly" id="waterFly"></div>

<div class="ground"></div>

<div class="pause-overlay" id="pauseOverlay">

<i class="fa-solid fa-pause-circle"></i>
<p>Dijeda</p>

</div>

</div>

<div class="waktu-habis-overlay" id="waktuHabisOverlay">

<div class="waktu-habis-box">⏰ Waktu Habis!</div>

</div>

</form>

<div class="panah-tersisa">

<span><?= e($temaLabelSisa); ?></span>

<div class="panah-row">
<?php for ($i = 0; $i < ($jumlah - $nomor); $i++): ?>
<i class="fa-solid <?= $temaPanahIcon; ?> panah-icon" style="color:<?= $temaPanahColor; ?>"></i>
<?php endfor; ?>
</div>

</div>

</div>

<script>

const TOTAL_WAKTU = <?= (int) $durasiWaktu; ?>;
let waktu = TOTAL_WAKTU;
let dipause = false;
let sudahJawab = false;

const timerEl = document.getElementById("time");
const pauseBtn = document.getElementById("pauseBtn");
const pauseOverlay = document.getElementById("pauseOverlay");
const quizContainer = document.querySelector(".quiz-container");
const form = document.getElementById("quizForm");
const jawabanInput = document.getElementById("jawabanInput");
const waterFly = document.getElementById("waterFly");
const firefighter = document.querySelector(".firefighter");
const waktuHabisOverlay = document.getElementById("waktuHabisOverlay");

function formatWaktu(detik){
    const m = String(Math.floor(detik/60)).padStart(2,"0");
    const s = String(detik%60).padStart(2,"0");
    return m+":"+s;
}

function playBuzzer(){
    try{
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = "sawtooth";
        osc.frequency.setValueAtTime(180, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(80, ctx.currentTime + .35);
        gain.gain.setValueAtTime(.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + .35);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + .35);
    }catch(e){}
}

timerEl.textContent = formatWaktu(waktu);

let hitung = setInterval(function(){

    if (dipause || sudahJawab) return;

    waktu--;
    timerEl.textContent = formatWaktu(waktu);

    if (waktu <= 0) {
        clearInterval(hitung);
        sudahJawab = true;

        quizContainer.classList.add("shake-salah");
        waktuHabisOverlay.classList.add("show");
        playBuzzer();

        setTimeout(function(){
            form.submit();
        }, 700);
    }

}, 1000);

pauseBtn.addEventListener("click", function(){

    dipause = !dipause;

    pauseOverlay.classList.toggle("show", dipause);
    quizContainer.classList.toggle("paused", dipause);
    pauseBtn.innerHTML = dipause
        ? '<i class="fa-solid fa-play"></i>'
        : '<i class="fa-solid fa-pause"></i>';

});

document.querySelectorAll(".target-unit").forEach(function(target){

    target.addEventListener("click", function(){

        if (sudahJawab || dipause) return;
        sudahJawab = true;

        jawabanInput.value = target.dataset.jawaban;

        document.querySelectorAll(".target-unit").forEach(t => t.classList.add("locked"));
        target.classList.add("target-hit");

        const flame = target.querySelector(".flame");
        if (flame) flame.textContent = document.querySelector(".fire-scene").dataset.hitIcon || "💨";

        const win = target.querySelector(".window");
        const firefighterRect = firefighter.getBoundingClientRect();
        const winRect = win.getBoundingClientRect();

        const startX = firefighterRect.left + firefighterRect.width * 0.85;
        const startY = firefighterRect.top + firefighterRect.height * 0.4;
        const endX = winRect.left + winRect.width/2;
        const endY = winRect.top + winRect.height/2;

        const dx = endX - startX;
        const dy = endY - startY;
        const dist = Math.sqrt(dx*dx + dy*dy);
        const sudut = Math.atan2(dy, dx) * 180 / Math.PI;

        waterFly.style.transition = "none";
        waterFly.style.left = startX + "px";
        waterFly.style.top = startY + "px";
        waterFly.style.transform = `rotate(${sudut}deg) scaleX(0)`;
        waterFly.style.opacity = "1";

        firefighter.classList.add("firefighter-spray");

        requestAnimationFrame(function(){
            requestAnimationFrame(function(){
                waterFly.style.width = dist + "px";
                waterFly.style.transition = "transform .35s cubic-bezier(.2,.6,.3,1)";
                waterFly.style.transform = `rotate(${sudut}deg) scaleX(1)`;
            });
        });

        setTimeout(function(){
            waterFly.style.opacity = "0";
            waterFly.style.width = "60px";
        }, 400);

        setTimeout(function(){
            form.submit();
        }, 480);

    });

});

</script>

</body>
</html>
