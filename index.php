<?php
session_start();
if (!isset($_SESSION['user'])) { header("location: login.php"); exit; }
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa - Aesthetic Mode</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); color: #334155; min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 35px; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .header-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; }
        h1 { font-size: 28px; font-weight: 700; color: #1e293b; } h1 span { color: #a18cd1; }
        .btn-group { display: flex; gap: 12px; }
        .btn { padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; color: white; transition: all 0.3s ease; }
        .btn-tambah { background: #a18cd1; } .btn-tambah:hover { background: #8972be; transform: translateY(-3px); }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; }
        th { color: #64748b; font-size: 13px; text-transform: uppercase; }
        tr { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.02); transition: 0.3s; }
        tr:hover { transform: scale(1.01); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        img.foto-profil { width: 50px; height: 50px; border-radius: 15px; object-fit: cover; border: 2px solid #fbc2eb; }
        .badge { background: #f1f5f9; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .aksi a { padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.3s; margin-right: 5px; }
        .btn-edit { background: #fef08a; color: #854d0e; } .btn-edit:hover { background: #fde047; }
        .btn-hapus { background: #fecdd3; color: #9f1239; } .btn-hapus:hover { background: #fda4af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-title">
            <h1>Data <span>Siswa</span></h1>
            <div class="btn-group">
                <a href="tambah.php" class="btn btn-tambah">+ Tambah Baru</a>
            </div>
        </div>
        <table>
            <tr>
                <th>No</th><th>Foto</th><th>Nama Lengkap</th><th>Kelas</th><th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            $query = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY id DESC");
            while ($row = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td><strong><?= $no++; ?></strong></td>
                <td><img src="uploads/<?= $row['foto']; ?>" class="foto-profil" alt="Foto"></td>
                <td><strong><?= $row['nama']; ?></strong></td>
                <td><span class="badge"><?= $row['kelas']; ?></span></td>
                <td class="aksi">
                    <a href="edit.php?id=<?= $row['id']; ?>" class="btn-edit">Edit</a>
                    <a href="hapus.php?id=<?= $row['id']; ?>" class="btn-hapus" onclick="return confirm('Yakin hapus?');">Hapus</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>
