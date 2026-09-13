<?php
session_start();
if (!isset($_SESSION['user'])) { header("location: login.php"); exit; }
include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $kelas = $_POST['kelas'];
    
    $foto = $_FILES['foto']['name'];
    $tmp = $_FILES['foto']['tmp_name'];
    $path = "uploads/" . $foto;
    
    if (move_uploaded_file($tmp, $path)) {
        mysqli_query($koneksi, "INSERT INTO siswa (nama, kelas, foto) VALUES ('$nama', '$kelas', '$foto')");
        header("location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa Baru</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 24px; width: 450px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        h2 { color: #a18cd1; margin-bottom: 25px; text-align: center; font-weight: 600; }
        label { font-size: 13px; color: #64748b; font-weight: 600; display: block; margin-top: 15px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 12px; margin-top: 8px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; transition: 0.3s; }
        input[type="text"]:focus { border-color: #a18cd1; outline: none; background: #fff; }
        .btn-group { display: flex; gap: 12px; margin-top: 30px; }
        button { flex: 1; padding: 14px; background: #a18cd1; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        a.batal { flex: 1; padding: 14px; background: #f1f5f9; color: #64748b; text-align: center; border-radius: 12px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Tambah Siswa Baru</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" required autocomplete="off">
            <label>Kelas</label>
            <input type="text" name="kelas" required autocomplete="off">
            <label>Upload Foto</label>
            <input type="file" name="foto" accept="image/*" required>
            
            <div class="btn-group">
                <button type="submit" name="simpan">Simpan Data</button>
                <a href="index.php" class="batal">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
