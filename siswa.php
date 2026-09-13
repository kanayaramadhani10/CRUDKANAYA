<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); min-height: 100vh; padding: 40px; }
        h1 { text-align: center; color: white; margin-bottom: 40px; font-size: 32px; text-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .grid-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; max-width: 1000px; margin: 0 auto; }
        .card { background: rgba(255,255,255,0.9); padding: 20px; border-radius: 20px; text-align: center; width: 220px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); transition: 0.3s; }
        .card:hover { transform: translateY(-10px); }
        .card img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 4px solid #fbc2eb; }
        .card h3 { color: #334155; font-size: 16px; margin-bottom: 5px; }
        .card p { color: #64748b; font-size: 13px; font-weight: 600; background: #f1f5f9; padding: 4px 10px; border-radius: 12px; display: inline-block; }
    </style>
</head>
<body>
    <h1>🌟 Daftar Profil Siswa 🌟</h1>
    <div class="grid-container">
        <?php
        $query = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($query)) {
        ?>
        <div class="card">
            <img src="uploads/<?= $row['foto']; ?>" alt="Foto Siswa">
            <h3><?= $row['nama']; ?></h3>
            <p><?= $row['kelas']; ?></p>
        </div>
        <?php } ?>
    </div>
</body>
</html>
