<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;

/* koneksi database */

$host = 'database-1.cbmfocjqrekw.us-east-1.rds.amazonaws.com';
$user = 'admin';
$pass = '';
$db   = 'db_absensi';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

/* konfigurasi S3 */

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);

$bucketName = "bucketku-uploads";
$s3_folder = "uploads/";

/* debug error */

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
