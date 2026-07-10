<?php

session_start();
include "koneksi.php";

if(!isset($_SESSION['id'])){
    exit;
}

$id = $_SESSION['id'];

$xp = intval($_POST['xp']);

mysqli_query($conn,"
UPDATE users
SET xp=xp+$xp
WHERE id='$id'
");