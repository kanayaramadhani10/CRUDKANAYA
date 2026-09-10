<?php
// ============================================
// hapus.php - Hapus Data Absensi (AWS S3 Integrated)
// ============================================
require_once 'koneksi.php';

// Ambil ID dari URL
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// 1. Ambil nama file foto dari database sebelum datanya dihapus
$query = "SELECT foto FROM absensi WHERE id = $id";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row) {
    $nama_foto = $row['foto'];

    // 2. Jika ada foto, hapus file dari Bucket S3
    if (!empty($nama_foto)) {
        try {
            $s3->deleteObject([
                'Bucket' => $bucketName,
                'Key'    => 'uploads/' . $nama_foto // Sesuai dengan struktur folder di S3
            ]);
        } catch (Exception $e) {
            // Kita tetap lanjut menghapus data di DB meskipun S3 gagal (opsional)
            // Atau Anda bisa log error: error_log($e->getMessage());
        }
    }

    // 3. Hapus data dari database RDS
    $delete_query = "DELETE FROM absensi WHERE id = $id";
    if ($conn->query($delete_query)) {
        // Redirect dengan notifikasi sukses
        header('Location: index.php?msg=hapus_ok');
        exit;
    } else {
        // Jika gagal hapus di DB
        die("Gagal menghapus data: " . $conn->error);
    }
} else {
    // Jika data tidak ditemukan
    header('Location: index.php');
    exit;
}
?>