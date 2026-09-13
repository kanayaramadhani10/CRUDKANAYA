<?php
session_start();
if (!isset($_SESSION['user'])) { header("location: login.php"); exit; }
include 'koneksi.php';

$id = $_GET['id'];

$query = mysqli_query($koneksi, "SELECT foto FROM siswa WHERE id='$id'");
$data = mysqli_fetch_assoc($query);
$foto_lama = $data['foto'];

if (file_exists("uploads/" . $foto_lama) && $foto_lama != "") {
    unlink("uploads/" . $foto_lama);
}

mysqli_query($koneksi, "DELETE FROM siswa WHERE id='$id'");
header("location: index.php");
?>
