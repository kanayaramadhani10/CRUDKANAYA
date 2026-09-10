<?php
// ============================================
// edit.php - Halaman Edit Data Absensi (AWS S3 Integrated)
// ============================================
require_once 'koneksi.php';

// Konfigurasi S3 URL untuk tampilan
$s3_base_url = "https://bucketku-uploads.s3.us-east-1.amazonaws.com/uploads/";

// Ambil ID dari URL
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

// Ambil data absensi yang akan diedit
$row = $conn->query("SELECT a.*, s.nama, s.kelas FROM absensi a JOIN siswa s ON a.siswa_id = s.id WHERE a.id = $id")->fetch_assoc();
if (!$row) { header('Location: index.php'); exit; }

$errors = [];

// Proses form saat submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id     = intval($_POST['siswa_id'] ?? 0);
    $tanggal      = $conn->real_escape_string($_POST['tanggal'] ?? '');
    $status_hadir = $conn->real_escape_string($_POST['status_hadir'] ?? '');

    if ($siswa_id <= 0)       $errors[] = 'Pilih nama siswa terlebih dahulu.';
    if (empty($tanggal))      $errors[] = 'Tanggal wajib diisi.';
    if (empty($status_hadir)) $errors[] = 'Status kehadiran wajib dipilih.';

    $nama_foto_baru = $row['foto']; // default: pakai foto lama

    // 1. LOGIKA HAPUS FOTO (Jika dicentang)
    if (isset($_POST['hapus_foto']) && !isset($_FILES['foto']['name'])) {
        if ($row['foto']) {
            try {
                $s3->deleteObject([
                    'Bucket' => $bucketName,
                    'Key'    => 'uploads/' . $row['foto']
                ]);
                $nama_foto_baru = null;
            } catch (Exception $e) {
                $errors[] = "Gagal menghapus foto di S3: " . $e->getMessage();
            }
        }
    }

    // 2. LOGIKA UPLOAD FOTO BARU
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        $max_size = 2 * 1024 * 1024;

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format file tidak didukung. Gunakan JPG, JPEG, atau PNG.';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 2MB.';
        } else {
            $nama_file_temp = 'foto_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            try {
                // Upload ke S3
                $s3->putObject([
                    'Bucket'      => $bucketName,
                    'Key'         => 'uploads/' . $nama_file_temp,
                    'SourceFile'  => $file['tmp_name'],
                    'ACL'         => 'public-read', // Agar foto bisa diakses via URL
                    'ContentType' => $file['type']
                ]);

                // Hapus foto lama di S3 jika ada
                if ($row['foto']) {
                    $s3->deleteObject([
                        'Bucket' => $bucketName,
                        'Key'    => 'uploads/' . $row['foto']
                    ]);
                }
                $nama_foto_baru = $nama_file_temp;

            } catch (Exception $e) {
                $errors[] = 'Gagal upload ke AWS S3: ' . $e->getMessage();
            }
        }
    }

    // 3. UPDATE DATABASE
    if (empty($errors)) {
        $foto_sql = $nama_foto_baru ? "'$nama_foto_baru'" : "NULL";
        $sql = "UPDATE absensi SET siswa_id=$siswa_id, tanggal='$tanggal', status_hadir='$status_hadir', foto=$foto_sql WHERE id=$id";
        
        if ($conn->query($sql)) {
            header('Location: index.php?msg=edit_ok');
            exit;
        } else {
            $errors[] = 'Gagal memperbarui database: ' . $conn->error;
        }
    }
}

$siswa_list = $conn->query("SELECT id, nama, kelas FROM siswa ORDER BY kelas, nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Absensi Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CSS yang sama dengan file sebelumnya agar tampilan konsisten */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e40af; --primary-light: #3b82f6; --primary-pale: #eff6ff;
            --success: #16a34a; --success-pale: #f0fdf4;
            --danger: #dc2626; --danger-pale: #fef2f2;
            --warning: #d97706; --warning-pale: #fffbeb;
            --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
            --gray-400: #94a3b8; --gray-500: #64748b; --gray-700: #334155; --gray-900: #0f172a;
            --white: #ffffff; --radius: 12px;
            --shadow: 0 4px 16px rgba(0,0,0,.08);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--gray-50); color: var(--gray-900); min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%); color: white; padding: 16px 24px; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(30,64,175,.3); }
        .header-title { font-size: 18px; font-weight: 800; }
        .container { max-width: 680px; margin: 36px auto; padding: 0 24px 40px; }
        .card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 22px 28px; border-bottom: 1px solid var(--gray-100); background: linear-gradient(to right, var(--warning-pale), var(--white)); }
        .card-header h2 { font-size: 18px; font-weight: 800; color: var(--warning); }
        .card-body { padding: 28px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 7px; }
        .form-control { width: 100%; font-family: inherit; font-size: 14px; padding: 10px 14px; border: 1.5px solid var(--gray-200); border-radius: 8px; background: var(--gray-50); outline: none; }
        .form-control:focus { border-color: var(--primary-light); background: white; }
        .status-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .status-option { display: none; }
        .status-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 2px solid var(--gray-200); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all .2s; }
        .status-option:checked + .status-label.hadir { border-color: var(--success); background: var(--success-pale); color: var(--success); }
        .status-option:checked + .status-label.tidak { border-color: var(--danger); background: var(--danger-pale); color: var(--danger); }
        .foto-current { display: flex; align-items: center; gap: 14px; padding: 14px; background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); margin-bottom: 12px; }
        .foto-current img { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid var(--gray-200); }
        .upload-area { border: 2px dashed var(--gray-200); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; background: var(--gray-50); position: relative; }
        .upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .alert-danger { background: var(--danger-pale); border: 1px solid #fecaca; padding: 14px; border-radius: 8px; margin-bottom: 20px; color: var(--danger); font-size: 14px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: all .2s; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-ghost { background: var(--gray-100); color: var(--gray-700); }
    </style>
</head>
<body>

<header class="header">
    <div class="header-inner" style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 12px;">
        <div style="background: rgba(255,255,255,.2); padding: 8px; border-radius: 8px;">📋</div>
        <div class="header-title">Sistem Absensi Cloud</div>
    </div>
</header>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>✏️ Edit Data Absensi</h2>
            <p style="font-size: 13px; color: var(--gray-500);">ID Transaksi: #<?= $id ?></p>
        </div>
        <div class="card-body">

            <?php if (!empty($errors)): ?>
            <div class="alert-danger">
                <strong>⚠️ Terdapat kesalahan:</strong>
                <ul style="margin-left: 20px; margin-top: 5px;">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Siswa</label>
                    <select name="siswa_id" class="form-control">
                        <?php while ($s = $siswa_list->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" <?= $row['siswa_id'] == $s['id'] ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($s['kelas']) ?>] <?= htmlspecialchars($s['nama']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($row['tanggal']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status Kehadiran</label>
                    <div class="status-options">
                        <div>
                            <input type="radio" name="status_hadir" value="Hadir" id="hadir" class="status-option" <?= $row['status_hadir'] == 'Hadir' ? 'checked' : '' ?>>
                            <label for="hadir" class="status-label hadir">✅ Hadir</label>
                        </div>
                        <div>
                            <input type="radio" name="status_hadir" value="Tidak Hadir" id="tidak_hadir" class="status-option" <?= $row['status_hadir'] == 'Tidak Hadir' ? 'checked' : '' ?>>
                            <label for="tidak_hadir" class="status-label tidak">❌ Tidak Hadir</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bukti Foto (Cloud S3)</label>
                    <?php if ($row['foto']): ?>
                    <div class="foto-current">
                        <img src="<?= $s3_base_url . $row['foto'] ?>" alt="Foto S3">
                        <div style="font-size: 12px;">
                            <strong>Foto di Cloud:</strong><br>
                            <span style="color: var(--gray-500)"><?= htmlspecialchars($row['foto']) ?></span>
                        </div>
                    </div>
                    <label style="display: flex; gap: 8px; align-items: center; color: var(--danger); font-size: 13px; margin-bottom: 15px; cursor: pointer;">
                        <input type="checkbox" name="hapus_foto" value="1"> 🗑️ Hapus foto dari S3
                    </label>
                    <?php endif; ?>

                    <div class="upload-area">
                        <input type="file" name="foto" id="fotoInput" accept="image/*">
                        <div style="font-size: 24px;">☁️</div>
                        <div style="font-weight: 600; font-size: 13px;">Ganti / Upload Foto Baru</div>
                        <div style="font-size: 11px; color: var(--gray-400);">Maksimal 2MB (PNG, JPG)</div>
                        <img id="previewImg" style="max-width: 100px; margin-top: 10px; display: none; border-radius: 5px;">
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                    <a href="index.php" class="btn btn-ghost">← Batal</a>
                    <button type="submit" class="btn btn-warning">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('fotoInput').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('previewImg');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>