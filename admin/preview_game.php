<?php
require_once "auth.php";
require_once __DIR__ . "/../inc/functions.php";

$activeMenu = "game";

$levelOptions = levelList();

$daftarKategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama");
$kategoriOptions = [];
while ($k = mysqli_fetch_assoc($daftarKategori)) {
    $kategoriOptions[] = $k;
}

$filterKategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$filterLevel = levelValid($_GET['level'] ?? 'Mudah');

$where = "level = ?";
$params = [$filterLevel];
$types = "s";

if ($filterKategori > 0) {
    $where .= " AND kategori_id = ?";
    $params[] = $filterKategori;
    $types .= "i";
}

$stmt = mysqli_prepare($conn, "SELECT * FROM soal WHERE $where ORDER BY RAND() LIMIT 1");
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$soal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$kategoriInfo = null;
if ($soal) {
    $stmt = mysqli_prepare($conn, "SELECT nama FROM kategori WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $soal['kategori_id']);
    mysqli_stmt_execute($stmt);
    $kategoriInfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/* ===========================
   Tema quiz — mengikuti pola yang sama persis dengan
   pengguna/quiz.php & pengguna/cek_jawaban.php
=========================== */

$temaQuiz = $soal ? temaQuizAktif($kategoriInfo['nama'] ?? '', $filterLevel) : '';
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
$temaKarakterSalah = ($temaLebah ? '🐝' : ($temaPanda ? '🐼' : ($temaKatak ? '🐸' : ($temaPenguin ? '🐧' : ($temaAstronot ? '🧑‍🚀' : ($temaKsatria ? '🤺' : '😥'))))));
$temaHitIcon = $temaBajakLaut ? '✨' : ($temaKebun ? '💧' : ($temaLebah ? '🍯' : ($temaPanda ? '🍃' : ($temaKatak ? '👅' : ($temaPenguin ? '💦' : ($temaAstronot ? '✨' : ($temaKsatria ? '💥' : '💨')))))));
$temaSmoke1 = $temaBajakLaut ? '🦜' : ($temaKebun ? '🦋' : ($temaLebah ? '🍯' : ($temaPanda ? '🍃' : ($temaKatak ? '🪷' : ($temaPenguin ? '❄️' : ($temaAstronot ? '⭐' : ($temaKsatria ? '🔥' : '💨')))))));
$temaSmoke2 = $temaBajakLaut ? '⚓' : ($temaKebun ? '🍃' : ($temaLebah ? '🌻' : ($temaPanda ? '🌱' : ($temaKatak ? '🌿' : ($temaPenguin ? '💠' : ($temaAstronot ? '🌌' : ($temaKsatria ? '⚔️' : '💨')))))));

$temaStateSenang = $temaLebah ? 'lebah-bawa-nektar' : ($temaPanda ? 'panda-senang' : ($temaKatak ? 'katak-senang' : ($temaPenguin ? 'penguin-senang' : ($temaAstronot ? 'astronot-senang' : ($temaKsatria ? 'ksatria-senang' : '')))));
$temaStateKecewa = $temaLebah ? 'lebah-pulang-kosong' : ($temaPanda ? 'panda-kecewa' : ($temaKatak ? 'katak-kecewa' : ($temaPenguin ? 'penguin-kecewa' : ($temaAstronot ? 'astronot-kecewa' : ($temaKsatria ? 'ksatria-kecewa' : '')))));

$temaPesanBenar = $temaBajakLaut ? 'Peti harta terbuka, harta ditemukan!' : ($temaKebun ? 'Bunga mekar dan segar!' : ($temaLebah ? 'Lebah membawa nektar ke sarang!' : ($temaPanda ? 'Bambu tumbuh tinggi, hijau, dan segar!' : ($temaKatak ? 'Serangga berhasil tertangkap oleh lidah katak!' : ($temaPenguin ? 'Ikan berhasil ditangkap penguin!' : ($temaAstronot ? 'Bintang berhasil dikumpulkan astronot!' : ($temaKsatria ? 'Monster berhasil dikalahkan ksatria!' : 'Api mengecil!')))))));
$temaPesanSalah = $temaBajakLaut ? 'Peti tetap terkunci!' : ($temaKebun ? 'Bunga jadi layu!' : ($temaLebah ? 'Bunga menutup, lebah pulang tanpa nektar!' : ($temaPanda ? 'Bambu layu, menguning, dan daunnya rontok!' : ($temaKatak ? 'Serangga berhasil kabur, katak kecewa!' : ($temaPenguin ? 'Ikan berhasil kabur, penguin kecewa!' : ($temaAstronot ? 'Bintang melayang menjauh, astronot kehabisan energi!' : ($temaKsatria ? 'Serangan meleset, monster balik menyerang!' : 'Api membesar!')))))));

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

function temaIkonHasil($opt, $stateClass, $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji) {
    if ($temaBajakLaut) {
        if ($stateClass === 'window-correct') {
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
    if ($temaKatak) {
        if ($stateClass === 'window-correct') {
            return '✨';
        }
        if ($stateClass === 'window-wrong') {
            return '💨';
        }
        return $seranggaEmoji[$opt];
    }
    if ($temaPenguin) {
        if ($stateClass === 'window-wrong') {
            return '💦';
        }
        return $ikanEmoji[$opt];
    }
    if ($temaAstronot) {
        if ($stateClass === 'window-wrong') {
            return '💫';
        }
        return $angkasaEmoji[$opt];
    }
    if ($temaKsatria) {
        if ($stateClass === 'window-correct') {
            return '💥';
        }
        if ($stateClass === 'window-wrong') {
            return '💨';
        }
        return $monsterEmoji[$opt];
    }
    return $stateClass === 'window-correct' ? '💨' : '🔥';
}

$opsiIconAwal = [];
$opsiIconBenar = [];
$opsiIconSalah = [];

foreach (['A', 'B', 'C', 'D'] as $opt) {
    $opsiIconAwal[$opt] = temaIkonOpsi($opt, $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji);
    $opsiIconBenar[$opt] = temaIkonHasil($opt, 'window-correct', $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji);
    $opsiIconSalah[$opt] = temaIkonHasil($opt, 'window-wrong', $temaBajakLaut, $temaKebun, $temaLebah, $temaPanda, $temaKatak, $temaPenguin, $temaAstronot, $temaKsatria, $bungaEmoji, $bungaEmojiLebah, $seranggaEmoji, $ikanEmoji, $angkasaEmoji, $monsterEmoji);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Preview Game | Admin StatQuest BPS</title>

<link rel="stylesheet" href="../pengguna/assets/css/quiz.css?v=<?= filemtime(__DIR__ . "/../pengguna/assets/css/quiz.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
    background:#F4F7FB;
}

.preview-header{
    height:64px;
    background:#1565C0;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 25px;
}

.preview-header h2{
    font-size:18px;
    margin:0;
}

.preview-header a{
    color:#fff;
    text-decoration:none;
    font-weight:600;
    background:rgba(255,255,255,.15);
    padding:8px 16px;
    border-radius:20px;
}

.preview-header a:hover{
    background:rgba(255,255,255,.25);
}

.preview-badge{
    display:inline-block;
    background:#FFD54F;
    color:#5D4037;
    font-weight:700;
    font-size:12px;
    padding:4px 12px;
    border-radius:20px;
    margin-left:10px;
    vertical-align:middle;
}

.preview-filter{
    max-width:900px;
    margin:20px auto 0;
    padding:0 15px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
}

.preview-filter select{
    padding:8px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    font-family:'Poppins',sans-serif;
}

.preview-tema-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:#E3F2FD;
    color:#1565C0;
    font-weight:700;
    font-size:12.5px;
    padding:8px 14px;
    border-radius:20px;
}

.preview-empty{
    max-width:600px;
    margin:60px auto;
    background:#fff;
    padding:40px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,.08);
}

</style>

</head>

<body>

<div class="preview-header">

<h2><i class="fa-solid fa-gamepad"></i> Preview Game <span class="preview-badge">MODE PREVIEW</span></h2>

<a href="game.php">&larr; Kembali</a>

</div>

<?php if (!$soal) { ?>

<div class="preview-empty">
<h2>Belum ada soal</h2>
<p>Tidak ada soal untuk kombinasi kategori &amp; level ini. Coba pilih kombinasi lain atau tambah soal di Bank Soal.</p>
</div>

<?php } else { ?>

<form class="preview-filter" method="GET">

<select name="kategori" onchange="this.form.submit()">
<option value="0">Semua Kategori</option>
<?php foreach ($kategoriOptions as $k) { ?>
<option value="<?= $k['id']; ?>" <?= $filterKategori == $k['id'] ? 'selected' : ''; ?>><?= e($k['nama']); ?></option>
<?php } ?>
</select>

<select name="level" onchange="this.form.submit()">
<?php foreach ($levelOptions as $lvl) { ?>
<option value="<?= $lvl; ?>" <?= $filterLevel === $lvl ? 'selected' : ''; ?>><?= $lvl; ?></option>
<?php } ?>
</select>

<span class="preview-tema-badge"><i class="fa-solid fa-palette"></i> Tema: <?= $temaQuiz !== '' ? e(ucwords(str_replace('-', ' ', $temaQuiz))) : 'Pemadam Kebakaran (default)'; ?></span>

</form>

<div style="max-width:900px;margin:20px auto;padding:0 15px 40px;">

<div class="quiz-container" style="margin:0;">

<div class="hud">

<div class="hud-item hud-nyawa" id="nyawaBox">
<i class="fa-solid fa-heart heart-active"></i>
<i class="fa-solid fa-heart heart-active"></i>
<i class="fa-solid fa-heart heart-active"></i>
<i class="fa-solid fa-heart heart-active"></i>
<i class="fa-solid fa-heart heart-active"></i>
</div>

<div class="hud-item hud-skor" id="skorBox">
<i class="fa-solid fa-star"></i> 0
</div>

<div class="hud-item hud-soal">
<i class="fa-solid fa-bullseye"></i> Preview
</div>

</div>

<h2 class="misi-judul">
<?= e($kategoriInfo['nama'] ?? 'StatQuest BPS'); ?> - <?= e($filterLevel); ?>
</h2>

<div class="question-card">
<h3><?= e($soal['pertanyaan']); ?></h3>
</div>

<div class="fire-scene<?= $temaSceneClass; ?>" id="fireScene" data-hit-icon="<?= e($temaHitIcon); ?>">

<span class="smoke-bg smoke1"><?= $temaSmoke1; ?></span>
<span class="smoke-bg smoke2"><?= $temaSmoke2; ?></span>

<div class="firefighter-wrap">
<div class="firefighter" id="firefighter"><?= $temaKarakter; ?></div>
</div>

<?php if ($temaLebah) { ?>
<div class="sarang-lebah" id="sarangLebah">🍯</div>
<?php } ?>

<div class="targets" id="targets">

<?php foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $huruf): ?>

<button type="button" class="target-unit" data-jawaban="<?= $huruf; ?>">
<span class="window window-<?= $key; ?>">
<span class="window-badge"><?= $huruf; ?></span>
<span class="flame"><?= $opsiIconAwal[$huruf]; ?></span>
</span>
<span class="target-label label-<?= $key; ?>"><?= e($soal['opsi_' . $key]); ?></span>
</button>

<?php endforeach; ?>

</div>

<div class="water-fly" id="waterFly"></div>

<div class="ground"></div>

</div>

<div id="hasilArea"></div>

</div>

</div>

<script>

const JAWABAN_BENAR = <?= json_encode($soal['jawaban']); ?>;
const PEMBAHASAN = <?= json_encode(e($soal['pembahasan'])); ?>;
const KATEGORI_ID = <?= (int) $filterKategori; ?>;
const LEVEL = <?= json_encode($filterLevel); ?>;

const KARAKTER_BENAR = <?= json_encode($temaKarakter); ?>;
const KARAKTER_SALAH = <?= json_encode($temaKarakterSalah); ?>;
const STATE_SENANG = <?= json_encode($temaStateSenang); ?>;
const STATE_KECEWA = <?= json_encode($temaStateKecewa); ?>;
const PESAN_BENAR = <?= json_encode($temaPesanBenar); ?>;
const PESAN_SALAH = <?= json_encode($temaPesanSalah); ?>;
const IS_LEBAH = <?= $temaLebah ? 'true' : 'false'; ?>;

const ICON_BENAR = <?= json_encode($opsiIconBenar); ?>;
const ICON_SALAH = <?= json_encode($opsiIconSalah); ?>;

let sudahJawab = false;
let skor = 0;

const fireScene = document.getElementById("fireScene");
const firefighter = document.getElementById("firefighter");
const waterFly = document.getElementById("waterFly");
const skorBox = document.getElementById("skorBox");
const hasilArea = document.getElementById("hasilArea");
const sarangLebah = document.getElementById("sarangLebah");

document.querySelectorAll(".target-unit").forEach(function(target){

    target.addEventListener("click", function(){

        if (sudahJawab) return;
        sudahJawab = true;

        const jawaban = target.dataset.jawaban;
        const benar = (jawaban === JAWABAN_BENAR);

        const win = target.querySelector(".window");
        const firefighterRect = firefighter.getBoundingClientRect();
        const winRect = win.getBoundingClientRect();

        const startX = firefighterRect.left + firefighterRect.width * 0.85;
        const startY = firefighterRect.top + firefighterRect.height * 0.4;
        const endX = winRect.left + winRect.width / 2;
        const endY = winRect.top + winRect.height / 2;

        const dx = endX - startX;
        const dy = endY - startY;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const sudut = Math.atan2(dy, dx) * 180 / Math.PI;

        waterFly.style.transition = "none";
        waterFly.style.left = startX + "px";
        waterFly.style.top = startY + "px";
        waterFly.style.transform = `rotate(${sudut}deg) scaleX(0)`;
        waterFly.style.opacity = "1";

        const flame = target.querySelector(".flame");
        if (flame) flame.textContent = fireScene.dataset.hitIcon || "💨";

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
            selesaikanJawaban(jawaban, benar);
        }, 480);

    });

});

function selesaikanJawaban(jawaban, benar){

    firefighter.textContent = benar ? KARAKTER_BENAR : KARAKTER_SALAH;
    firefighter.classList.remove("firefighter-spray");
    const stateClassKarakter = benar ? STATE_SENANG : STATE_KECEWA;
    if (stateClassKarakter) firefighter.classList.add(stateClassKarakter);

    if (sarangLebah) {
        sarangLebah.classList.add(benar ? "sarang-terisi" : "sarang-kosong");
    }

    document.querySelectorAll(".target-unit").forEach(function(t){

        const huruf = t.dataset.jawaban;
        const isChosen = (huruf === jawaban);
        const isCorrect = (huruf === JAWABAN_BENAR);
        const win = t.querySelector(".window");
        const flame = t.querySelector(".flame");

        let stateClass = "";
        if (isChosen && benar) stateClass = "window-correct";
        else if (isChosen && !benar) stateClass = "window-wrong";
        else if (isCorrect && !benar) stateClass = "window-reveal";

        const dimmed = !isChosen && !(isCorrect && !benar);

        if (dimmed) t.classList.add("locked");
        if (stateClass) win.classList.add(stateClass);

        if (stateClass === "window-correct" || stateClass === "window-reveal") {
            flame.textContent = ICON_BENAR[huruf];
        } else if (stateClass === "window-wrong") {
            flame.textContent = ICON_SALAH[huruf];
        }

        if (stateClass === "window-reveal") {
            const mark = document.createElement("span");
            mark.className = "correct-mark";
            mark.textContent = "✅";
            win.appendChild(mark);
        }

    });

    const poin = 20;
    if (benar) {
        skor += poin;
        skorBox.innerHTML = '<i class="fa-solid fa-star"></i> ' + skor;
    }

    hasilArea.innerHTML = `
        <div class="hasil-banner ${benar ? 'banner-benar' : 'banner-salah'}">
            <strong>${benar ? '✅ JAWABAN BENAR' : '❌ JAWABAN SALAH'}</strong>
            <p>${benar ? PESAN_BENAR + ' +' + poin + ' poin' : PESAN_SALAH + ' -1 nyawa'}</p>
        </div>
        <div class="pembahasan-box">
            <div class="pembahasan-icon">💡</div>
            <div class="pembahasan-text">
                <h3>Pembahasan</h3>
                <p>${PEMBAHASAN}</p>
            </div>
        </div>
        <div style="text-align:center;">
            <button class="btn-lanjut" onclick="soalBerikutnya()">Soal Berikutnya ➜</button>
            <a href="game.php"><button class="btn-lanjut btn-secondary">← Kembali ke Game Editor</button></a>
        </div>
    `;

}

function soalBerikutnya(){
    const url = new URL(window.location.href);
    url.searchParams.set("kategori", KATEGORI_ID);
    url.searchParams.set("level", LEVEL);
    window.location.href = url.toString();
}

</script>

<?php } ?>

</body>

</html>
