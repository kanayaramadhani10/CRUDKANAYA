<?php
session_start();
if (!isset($_SESSION['user'])) { header("location: login.php"); exit; }
include 'koneksi.php';

$id = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

if (isset($_POST['update'])) {
    $nama = $_POST['nama'];
    $kelas = $_POST['kelas'];
    $foto_lama = $_POST['foto_lama'];
    
    if ($_FILES['foto']['name'] != '') {
        $foto_baru = $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        $path = "uploads/" . $foto_baru;
        
        if (move_uploaded_file($tmp, $path)) {
            if (file_exists("uploads/" . $foto_lama) && $foto_lama != "") { unlink("uploads/" . $foto_lama); }
            mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', kelas='$kelas', foto='$foto_baru' WHERE id='$id'");
            header("location: index.php"); exit;
        }
    } else {
        mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', kelas='$kelas' WHERE id='$id'");
        header("location: index.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 24px; width: 450px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        h2 { color: #a18cd1; margin-bottom: 25px; text-align: center; font-weight: 600; }
        label { font-size: 13px; color: #64748b; font-weight: 600; display: block; margin-top: 15px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 12px; margin-top: 8px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; }
        img.preview { width: 70px; height: 70px; border-radius: 12px; object-fit: cover; margin-top: 10px; border: 2px solid #fbc2eb; }
        .btn-group { display: flex; gap: 12px; margin-top: 30px; }
        button { flex: 1; padding: 14px; background: #a18cd1; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        a.batal { flex: 1; padding: 14px; background: #f1f5f9; color: #64748b; text-align: center; border-radius: 12px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Edit Data Siswa</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="foto_lama" value="<?= $data['foto']; ?>">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" value="<?= $data['nama']; ?>" required>
            <label>Kelas</label>
            <input type="text" name="kelas" value="<?= $data['kelas']; ?>" required>
            <label>Foto Saat Ini</label>
            <img src="uploads/<?= $data['foto']; ?>" class="preview" alt="Foto Lama">
            <label>Ganti Foto (Opsional)</label>
            <input type="file" name="foto" accept="image/*">
            <div class="btn-group">
                <button type="submit" name="update">Simpan Edit</button>
                <a href="index.php" class="batal">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
