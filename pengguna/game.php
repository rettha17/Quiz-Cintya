<?php
session_start();
require_once "koneksi.php";

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 1;
$level = isset($_GET['level']) ? $_GET['level'] : "Mudah";

$q = mysqli_query($conn,"
SELECT *
FROM soal
WHERE kategori_id='$kategori'
AND level='$level'
ORDER BY id ASC
");

$soal = [];

while($row=mysqli_fetch_assoc($q)){
    $soal[]=$row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Quiz Challenge</title>

<link rel="stylesheet" href="assets/css/game.css">

</head>

<body>

<div id="game">

<div id="hud">

<div id="heart">

❤️ ❤️ ❤️

</div>

<span id="xp">

XP : 0

</span>

</div>

<div id="map">

<div id="player">

😀

</div>

<div id="finish">

🏆

</div>

</div>

</div>

<div id="quizBox">

<h2 id="pertanyaan"></h2>

<div class="jawaban" id="A"></div>

<div class="jawaban" id="B"></div>

<div class="jawaban" id="C"></div>

<div class="jawaban" id="D"></div>

</div>

<div id="pembahasan" style="display:none">

<h3>Pembahasan</h3>

<p id="isiPembahasan"></p>

<button id="lanjut">

Lanjut

</button>

</div>

<script>

const soal = <?= json_encode($soal); ?>;

</script>

<script src="assets/js/game.js"></script>

</body>

</html>