<?php
$host     = "localhost";
$user     = "root"; 
$password = ""; // Isi jika MariaDB kamu pakai password
$db       = "db_sekolah"; 

$koneksi = mysqli_connect($host, $user, $password, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
