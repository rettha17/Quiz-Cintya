<?php
session_start();
require_once __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/../inc/functions.php";

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_soal   = (int) $_POST['id_soal'];
$kategori  = (int) $_POST['kategori'];
$nomor     = (int) $_POST['nomor'];
$level     = levelValid($_POST['level'] ?? 'Mudah');
$jawaban   = isset($_POST['jawaban']) ? $_POST['jawaban'] : "";

$stmt = mysqli_prepare($conn, "SELECT * FROM soal WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id_soal);
mysqli_stmt_execute($stmt);
$soal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$soal) {
    die("Soal tidak ditemukan.");
}

$stmt = mysqli_prepare($conn, "SELECT nama FROM kategori WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $kategori);
mysqli_stmt_execute($stmt);
$kategoriInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$temaQuiz = temaQuizAktif($kategoriInfo['nama'] ?? '', $level);
$temaKebun = ($temaQuiz === 'kebun');
$temaBajakLaut = ($temaQuiz === 'bajak-laut');
$temaLebah = ($temaQuiz === 'lebah');
$temaPanda = ($temaQuiz === 'panda');
$bungaEmoji = ['A' => '🌺', 'B' => '🪻', 'C' => '🌻', 'D' => '🌼'];
$bungaEmojiLebah = ['A' => '🌸', 'B' => '🪷', 'C' => '🌻', 'D' => '🌼'];
$temaSceneClass = $temaKebun ? ' tema-kebun' : ($temaBajakLaut ? ' tema-bajak-laut' : ($temaLebah ? ' tema-lebah' : ($temaPanda ? ' tema-panda' : '')));
$temaSmoke1 = $temaBajakLaut ? '🦜' : ($temaKebun ? '🦋' : ($temaLebah ? '🍯' : ($temaPanda ? '🍃' : '💨')));
$temaSmoke2 = $temaBajakLaut ? '⚓' : ($temaKebun ? '🍃' : ($temaLebah ? '🌻' : ($temaPanda ? '🌱' : '💨')));

function temaIkonHasil($opt, $stateClass, $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $bungaEmoji, $bungaEmojiLebah) {
    if ($temaBajakLaut) {
        if ($stateClass === 'window-correct' || $stateClass === 'window-reveal') {
            return '💰';
        }
        if ($stateClass === 'window-wrong') {
            return '💨';
        }
        return '🔒';
    }
    if ($temaKebun) {
        return $bungaEmoji[$opt];
    }
    if ($temaLebah) {
        return $stateClass === 'window-wrong' ? '🌷' : $bungaEmojiLebah[$opt];
    }
    if ($temaPanda) {
        return $stateClass === 'window-wrong' ? '🍂' : '🎋';
    }
    return $stateClass === 'window-correct' ? '💨' : '🔥';
}

$benar = ($jawaban === $soal['jawaban']);

$total = !empty($_SESSION['soal']) ? count($_SESSION['soal']) : 1;
$poin = (int) round(100 / $total);

if (!isset($_SESSION['skor'])) {
    $_SESSION['skor'] = 0;
}

if (!isset($_SESSION['xp'])) {
    $_SESSION['xp'] = 0;
}

if (!isset($_SESSION['nyawa'])) {
    $_SESSION['nyawa'] = 3;
}

if ($benar) {
    $_SESSION['skor'] += $poin;
    $_SESSION['xp'] += 10;
} else {
    $_SESSION['nyawa'] = max(0, $_SESSION['nyawa'] - 1);
}

$nyawaSisa = $_SESSION['nyawa'];
$gameOver = ($nyawaSisa <= 0);
$skorSaatIni = $_SESSION['skor'];

$next = $nomor + 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Hasil Jawaban</title>

<script>
document.documentElement.setAttribute("data-theme", localStorage.getItem("quizbps-theme") || "light");
</script>

<link rel="stylesheet" href="assets/css/quiz.css?v=<?= filemtime(__DIR__ . "/assets/css/quiz.css"); ?>">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="quiz-container <?= $benar ? 'pop-benar flash-benar' : 'shake-salah flash-salah'; ?>">

<div class="hud">

<div class="hud-item hud-nyawa">
<?php for ($i = 0; $i < 3; $i++): ?>
<i class="fa-solid fa-heart <?= $i < $nyawaSisa ? 'heart-active' : 'heart-lost'; ?>"></i>
<?php endfor; ?>
</div>

<div class="hud-item hud-skor">
<i class="fa-solid fa-star"></i> <?= $skorSaatIni; ?>
</div>

</div>

<div class="question-card">
<h3><?= e($soal['pertanyaan']); ?></h3>
</div>

<div class="fire-scene<?= $temaSceneClass; ?>">

<span class="smoke-bg smoke1"><?= $temaSmoke1; ?></span>
<span class="smoke-bg smoke2"><?= $temaSmoke2; ?></span>

<div class="firefighter-wrap">
<div class="firefighter<?php
    if ($temaLebah) {
        echo $benar ? ' lebah-bawa-nektar' : ' lebah-pulang-kosong';
    } elseif ($temaPanda) {
        echo $benar ? ' panda-senang' : ' panda-kecewa';
    }
?>">
<?= $benar
    ? ($temaBajakLaut ? '🏴‍☠️' : ($temaKebun ? '🧑‍🌾' : ($temaLebah ? '🐝' : ($temaPanda ? '🐼' : '🧑‍🚒'))))
    : ($temaLebah ? '🐝' : ($temaPanda ? '🐼' : '😥')); ?>
</div>
</div>

<?php if ($temaLebah): ?>
<div class="sarang-lebah <?= $benar ? 'sarang-terisi' : 'sarang-kosong'; ?>">🍯</div>
<?php endif; ?>

<div class="targets">

<?php foreach (['A','B','C','D'] as $opt):

    $isChosen = ($jawaban === $opt);
    $isCorrect = ($soal['jawaban'] === $opt);
    $optKey = 'opsi_' . strtolower($opt);

    $stateClass = '';
    if ($isChosen && $benar) {
        $stateClass = 'window-correct';
    } elseif ($isChosen && !$benar) {
        $stateClass = 'window-wrong';
    } elseif ($isCorrect && !$benar) {
        $stateClass = 'window-reveal';
    }

    $dimmed = (!$isChosen && !($isCorrect && !$benar));

    $flameIcon = temaIkonHasil($opt, $stateClass, $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $bungaEmoji, $bungaEmojiLebah);
?>

<div class="target-unit <?= $dimmed ? 'locked' : ''; ?>">
<span class="window window-<?= strtolower($opt); ?> <?= $stateClass; ?>">
<span class="window-badge"><?= $opt; ?></span>
<span class="flame"><?= $flameIcon; ?></span>
<?php if ($stateClass === 'window-reveal'): ?>
<span class="correct-mark">✅</span>
<?php endif; ?>
</span>
<span class="target-label label-<?= strtolower($opt); ?>"><?= e($soal[$optKey]); ?></span>
</div>

<?php endforeach; ?>

</div>

<div class="ground"></div>

</div>

<div class="hasil-banner <?= $benar ? 'banner-benar' : 'banner-salah'; ?>">

<?php if ($benar): ?>

<strong>✅ JAWABAN BENAR</strong>
<p><?= $temaBajakLaut ? 'Peti harta terbuka, harta ditemukan!' : ($temaKebun ? 'Bunga mekar dan segar!' : ($temaLebah ? 'Lebah membawa nektar ke sarang!' : ($temaPanda ? 'Bambu tumbuh tinggi, hijau, dan segar!' : 'Api mengecil!'))); ?> +<?= $poin; ?> poin</p>

<?php else: ?>

<strong>❌ JAWABAN SALAH</strong>
<p><?= $temaBajakLaut ? 'Peti tetap terkunci!' : ($temaKebun ? 'Bunga jadi layu!' : ($temaLebah ? 'Bunga menutup, lebah pulang tanpa nektar!' : ($temaPanda ? 'Bambu layu, menguning, dan daunnya rontok!' : 'Api membesar!'))); ?> -1 nyawa</p>

<?php endif; ?>

</div>

<div class="pembahasan-box">

<div class="pembahasan-icon">💡</div>

<div class="pembahasan-text">
<h3>Pembahasan</h3>
<p><?= e($soal['pembahasan']); ?></p>
</div>

</div>

<div style="text-align:center;">

<?php if($gameOver){ ?>

<h2 style="color:#D32F2F;">💔 Nyawa Habis!</h2>

<a href="hasil.php?kategori=<?= $kategori ?>&level=<?= urlencode($level) ?>&habis=1">

<button class="btn-lanjut">Lihat Hasil</button>

</a>

<?php } elseif($next < $total){ ?>

<a href="quiz.php?kategori=<?= $kategori ?>&level=<?= urlencode($level) ?>&no=<?= $next ?>">

<button class="btn-lanjut">Lanjut Soal ➜</button>

</a>

<?php }else{ ?>

<a href="hasil.php?kategori=<?= $kategori ?>&level=<?= urlencode($level) ?>">

<button class="btn-lanjut">Selesai Quiz</button>

</a>

<?php } ?>

<a href="kategori.php">

<button class="btn-lanjut btn-secondary">← Kembali ke Kategori</button>

</a>

</div>

</div>

<?php if (!$benar): ?>
<script>
(function(){
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
})();
</script>
<?php else: ?>
<script>
(function(){
    try{
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const notes = [523.25, 784];
        notes.forEach(function(freq, i){
            const start = ctx.currentTime + i * .12;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = "sine";
            osc.frequency.setValueAtTime(freq, start);
            gain.gain.setValueAtTime(.001, start);
            gain.gain.exponentialRampToValueAtTime(.25, start + .03);
            gain.gain.exponentialRampToValueAtTime(.001, start + .25);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(start);
            osc.stop(start + .25);
        });
    }catch(e){}
})();
</script>
<?php endif; ?>

</body>

</html>
