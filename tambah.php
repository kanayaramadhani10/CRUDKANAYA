<?php
// ============================================
// tambah.php - Halaman Tambah Data Absensi (AWS S3)
// ============================================
require_once 'koneksi.php';

$errors = [];
$success = false;

// Proses form saat submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil & bersihkan input
    $siswa_id     = intval($_POST['siswa_id'] ?? 0);
    $tanggal      = $conn->real_escape_string($_POST['tanggal'] ?? '');
    $status_hadir = $conn->real_escape_string($_POST['status_hadir'] ?? '');

    // Validasi input
    if ($siswa_id <= 0)        $errors[] = 'Pilih nama siswa terlebih dahulu.';
    if (empty($tanggal))       $errors[] = 'Tanggal wajib diisi.';
    if (empty($status_hadir))  $errors[] = 'Status kehadiran wajib dipilih.';

    // Proses upload foto ke AWS S3
    $nama_foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['foto'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format file tidak didukung. Gunakan JPG, JPEG, atau PNG.';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 2MB.';
        } else {
            // Buat nama file unik
            $nama_foto = 'absensi_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            try {
                // Upload ke S3
                $result = $s3->putObject([
                    'Bucket'      => $bucketName,
                    'Key'         => 'uploads/' . $nama_foto,
                    'SourceFile'  => $file['tmp_name'],
                    'ACL'         => 'public-read', // Agar foto bisa diakses via URL
                    'ContentType' => $file['type']
                ]);
            } catch (Exception $e) {
                $errors[] = 'Gagal mengupload ke Cloud Storage: ' . $e->getMessage();
                $nama_foto = null;
            }
        }
    }

    // Simpan ke database jika tidak ada error
    if (empty($errors)) {
        $foto_sql = $nama_foto ? "'$nama_foto'" : "NULL";
        $sql = "INSERT INTO absensi (siswa_id, tanggal, status_hadir, foto)
                VALUES ($siswa_id, '$tanggal', '$status_hadir', $foto_sql)";

        if ($conn->query($sql)) {
            header('Location: index.php?msg=tambah_ok');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan data ke RDS: ' . $conn->error;
        }
    }
}

// Ambil daftar siswa untuk dropdown
$siswa_list = $conn->query("SELECT id, nama, kelas FROM siswa ORDER BY kelas, nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Absensi - Cloud Version</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CSS tetap dipertahankan sesuai desain cantik Anda */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e40af; --primary-light: #3b82f6; --primary-pale: #eff6ff;
            --success: #16a34a; --success-pale: #f0fdf4;
            --danger: #dc2626; --danger-pale: #fef2f2;
            --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
            --gray-400: #94a3b8; --gray-500: #64748b; --gray-700: #334155; --gray- white: #ffffff; --radius: 12px;
            --shadow: 0 4px 16px rgba(0,0,0,.08);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--gray-50); color: var(--gray-700); min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: white; padding: 16px 24px; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 12px; }
        .container { max-width: 680px; margin: 36px auto; padding: 0 24px; }
        .card { background: white; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 22px 28px; border-bottom: 1px solid var(--gray-100); }
        .card-body { padding: 28px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1.5px solid var(--gray-200); border-radius: 8px; font-family: inherit; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--primary-light); background: white; }
        .status-options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .status-option { display: none; }
        .status-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 2px solid var(--gray-200); border-radius: 10px; cursor: pointer; font-weight: 700; transition: 0.2s; }
        .status-option:checked + .status-label.hadir { border-color: var(--success); background: var(--success-pale); color: var(--success); }
        .status-option:checked + .status-label.tidak { border-color: var(--danger); background: var(--danger-pale); color: var(--danger); }
        .upload-area { border: 2px dashed var(--gray-200); border-radius: 10px; padding: 30px; text-align: center; cursor: pointer; background: var(--gray-50); position: relative; }
        .upload-area:hover { border-color: var(--primary-light); background: var(--primary-pale); }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #1e3a8a; transform: translateY(-1px); }
        .alert-danger { background: var(--danger-pale); color: var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-inner">
        <div style="font-size: 24px;">☁️</div>
        <div>
            <div style="font-weight: 800; font-size: 18px;">Absensi Cloud</div>
            <div style="font-size: 12px; opacity: 0.8;">Powered by AWS S3 & RDS</div>
        </div>
    </div>
</header>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2 style="color: var(--primary);">➕ Catat Kehadiran</h2>
            <p style="font-size: 13px; color: var(--gray-500);">Data akan langsung tersimpan secara aman di cloud</p>
        </div>
        <div class="card-body">

            <?php if (!empty($errors)): ?>
            <div class="alert-danger">
                <strong>⚠️ Terjadi kendala:</strong>
                <ul style="margin-left: 20px; margin-top: 5px;">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Pilih Siswa</label>
                    <select name="siswa_id" class="form-control" required>
                        <option value="">-- Cari Nama Siswa --</option>
                        <?php
                        $current_kelas = '';
                        while ($s = $siswa_list->fetch_assoc()):
                            if ($current_kelas !== $s['kelas']) {
                                if ($current_kelas) echo '</optgroup>';
                                echo '<optgroup label="Kelas ' . htmlspecialchars($s['kelas']) . '">';
                                $current_kelas = $s['kelas'];
                            }
                        ?>
                        <option value="<?= $s['id'] ?>" <?= (isset($_POST['siswa_id']) && $_POST['siswa_id'] == $s['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nama']) ?>
                        </option>
                        <?php endwhile; ?>
                        <?php if ($current_kelas) echo '</optgroup>'; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Absensi</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status Kehadiran</label>
                    <div class="status-options">
                        <div>
                            <input type="radio" name="status_hadir" value="Hadir" id="hadir" class="status-option" checked>
                            <label for="hadir" class="status-label hadir">✅ Hadir</label>
                        </div>
                        <div>
                            <input type="radio" name="status_hadir" value="Tidak Hadir" id="tidak_hadir" class="status-option">
                            <label for="tidak_hadir" class="status-label tidak">❌ Tidak Hadir</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Foto Bukti (S3 Upload)</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="foto" id="fotoInput" accept="image/*" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                        <div style="font-size: 30px;">📸</div>
                        <div style="font-weight: 700; font-size: 14px; margin-top: 10px;" id="uploadText">Ketuk untuk pilih foto</div>
                        <div style="font-size: 11px; color: var(--gray-400); margin-top: 5px;">Format: JPG, PNG (Maks. 2MB)</div>
                        <img id="previewImg" src="" alt="" style="display:none; max-width: 100%; border-radius: 8px; margin-top: 15px;">
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                    <a href="index.php" style="text-decoration: none; color: var(--gray-500); font-weight: 700; font-size: 14px;">← Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan ke Cloud 🚀</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('fotoInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const preview = document.getElementById('previewImg');
    const text = document.getElementById('uploadText');
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        text.textContent = file.name;
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>