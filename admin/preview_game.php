<?php
require_once "auth.php";

$activeMenu = "game";

$levelOptions = ['Mudah', 'Sedang', 'Sulit'];

$daftarKategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama");
$kategoriOptions = [];
while ($k = mysqli_fetch_assoc($daftarKategori)) {
    $kategoriOptions[] = $k;
}

$filterKategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$filterLevel = in_array($_GET['level'] ?? '', $levelOptions, true) ? $_GET['level'] : 'Mudah';

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
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Preview Game | Admin Quiz Challenge BPS</title>

<link rel="stylesheet" href="../assets/css/quiz.css?v=<?= filemtime(__DIR__ . "/../assets/css/quiz.css"); ?>">

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

</form>

<div style="max-width:900px;margin:20px auto;padding:0 15px 40px;">

<div class="quiz-container" style="margin:0;">

<div class="hud">

<div class="hud-item hud-nyawa" id="nyawaBox">
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
<?= e($kategoriInfo['nama'] ?? 'Quiz Challenge'); ?> - <?= e($filterLevel); ?>
</h2>

<div class="question-card">
<h3><?= e($soal['pertanyaan']); ?></h3>
</div>

<div class="fire-scene" id="fireScene">

<span class="smoke-bg smoke1">💨</span>
<span class="smoke-bg smoke2">💨</span>

<div class="firefighter-wrap">
<div class="firefighter" id="firefighter">🧑‍🚒</div>
</div>

<div class="targets" id="targets">

<?php foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $huruf): ?>

<button type="button" class="target-unit" data-jawaban="<?= $huruf; ?>">
<span class="window window-<?= $key; ?>">
<span class="window-badge"><?= $huruf; ?></span>
<span class="flame">🔥</span>
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

let sudahJawab = false;
let skor = 0;

const firefighter = document.getElementById("firefighter");
const waterFly = document.getElementById("waterFly");
const skorBox = document.getElementById("skorBox");
const hasilArea = document.getElementById("hasilArea");

function labelUntuk(huruf){
    return document.querySelector(".target-unit[data-jawaban='" + huruf + "'] .target-label").textContent;
}

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

    firefighter.textContent = benar ? "🧑‍🚒" : "😥";

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

        if (stateClass === "window-correct") {
            flame.textContent = "💨";
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
            <p>${benar ? 'Api mengecil! +' + poin + ' poin' : 'Api membesar! -1 nyawa'}</p>
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
