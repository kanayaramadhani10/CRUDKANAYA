<?php
// ============================================
// siswa.php - Daftar Siswa (AWS S3 Integrated)
// ============================================
require_once 'koneksi.php';

// Konfigurasi S3 URL untuk tampilan
$s3_base_url = "https://bucketku-uploads.s3.us-east-1.amazonaws.com/uploads/";

// ---- Tambah Siswa ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $nama        = $conn->real_escape_string(trim($_POST['nama'] ?? ''));
    $no_presensi = $conn->real_escape_string(trim($_POST['no_presensi'] ?? ''));
    $kelas       = $conn->real_escape_string(trim($_POST['kelas'] ?? ''));
    $foto_nama   = null;

    if ($nama && $no_presensi && $kelas) {
        // Logika Upload ke S3
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['foto'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png'];
            
            if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
                $foto_nama = 'siswa_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                
                try {
                    $s3->putObject([
                        'Bucket'      => $bucketName,
                        'Key'         => 'uploads/' . $foto_nama,
                        'SourceFile'  => $file['tmp_name'],
                        'ACL'         => 'public-read',
                        'ContentType' => $file['type']
                    ]);
                } catch (Exception $e) {
                    die("Gagal upload ke S3: " . $e->getMessage());
                }
            }
        }
        $foto_sql = $foto_nama ? "'$foto_nama'" : "NULL";
        $conn->query("INSERT INTO siswa (nama, no_presensi, kelas, foto) VALUES ('$nama','$no_presensi','$kelas',$foto_sql)");
        header('Location: siswa.php?msg=tambah_ok'); exit;
    }
}

// ---- Edit Siswa ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id          = intval($_POST['id']);
    $nama        = $conn->real_escape_string(trim($_POST['nama'] ?? ''));
    $no_presensi = $conn->real_escape_string(trim($_POST['no_presensi'] ?? ''));
    $kelas       = $conn->real_escape_string(trim($_POST['kelas'] ?? ''));
    $old_foto    = $conn->real_escape_string($_POST['old_foto'] ?? '');
    $foto_nama   = $old_foto;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, ['jpg','jpeg','png']) && $file['size'] <= 2*1024*1024) {
            $foto_nama = 'siswa_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            
            try {
                // 1. Upload foto baru ke S3
                $s3->putObject([
                    'Bucket'      => $bucketName,
                    'Key'         => 'uploads/' . $foto_nama,
                    'SourceFile'  => $file['tmp_name'],
                    'ACL'         => 'public-read',
                    'ContentType' => $file['type']
                ]);

                // 2. Hapus foto lama di S3 jika ada
                if ($old_foto) {
                    $s3->deleteObject([
                        'Bucket' => $bucketName,
                        'Key'    => 'uploads/' . $old_foto
                    ]);
                }
            } catch (Exception $e) {
                die("Gagal memperbarui foto di S3: " . $e->getMessage());
            }
        }
    }
    $foto_sql = $foto_nama ? "'$foto_nama'" : "NULL";
    $conn->query("UPDATE siswa SET nama='$nama', no_presensi='$no_presensi', kelas='$kelas', foto=$foto_sql WHERE id=$id");
    header('Location: siswa.php?msg=edit_ok'); exit;
}

// ---- Hapus Siswa ----
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $r  = $conn->query("SELECT foto FROM siswa WHERE id=$id")->fetch_assoc();
    
    if ($r && $r['foto']) {
        try {
            // Hapus file dari S3
            $s3->deleteObject([
                'Bucket' => $bucketName,
                'Key'    => 'uploads/' . $r['foto']
            ]);
        } catch (Exception $e) {
            // Lanjut hapus DB meski S3 gagal
        }
    }
    $conn->query("DELETE FROM siswa WHERE id=$id");
    header('Location: siswa.php?msg=hapus_ok'); exit;
}

// ---- Ambil data edit ----
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_data = $conn->query("SELECT * FROM siswa WHERE id=".intval($_GET['edit']))->fetch_assoc();
}

// ---- Mode tampil ----
$mode = isset($_GET['tambah']) ? 'tambah' : ($edit_data ? 'edit' : 'list');

// ---- Fetch all siswa ----
$siswa_list = $conn->query("SELECT * FROM siswa ORDER BY kelas, CAST(no_presensi AS UNSIGNED), nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Siswa - Cloud Storage</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* CSS dipertahankan agar UI tetap cantik sesuai keinginan Anda */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --blue: #1e40af; --blue-dk: #1e3a8a; --blue-lt: #eff6ff;
    --green: #16a34a; --amber: #d97706; --red: #dc2626; --red-lt: #fef2f2;
    --gray-0: #ffffff; --gray-1: #f8fafc; --gray-2: #f1f5f9; --gray-3: #e2e8f0;
    --gray-5: #64748b; --gray-7: #0f172a; --radius: 12px;
    --shadow: 0 1px 3px rgba(0,0,0,.1);
}
body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--gray-1); color: var(--gray-7); min-height: 100vh; }
.nav { background: var(--blue-dk); padding: 0 24px; display: flex; align-items: center; height: 64px; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.nav-brand { color: #fff; font-weight: 800; font-size: 18px; display: flex; align-items: center; gap: 10px; }
.nav-links { display: flex; gap: 8px; margin-left: 20px; }
.nav-link { color: rgba(255,255,255,.8); font-size: 14px; font-weight: 600; padding: 8px 16px; border-radius: 8px; text-decoration: none; transition: 0.2s; }
.nav-link:hover, .nav-link.active { background: rgba(255,255,255,.15); color: #fff; }
.page { max-width: 1100px; margin: 32px auto; padding: 0 20px 40px; }
.alert { padding: 12px 20px; border-radius: var(--radius); margin-bottom: 20px; font-size: 14px; font-weight: 600; border: 1px solid transparent; }
.alert-success { background: #dcfce7; color: var(--green); border-color: #bbf7d0; }
.alert-danger { background: var(--red-lt); color: var(--red); border-color: #fecaca; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-title { font-size: 24px; font-weight: 800; color: var(--gray-7); }
.btn { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; padding: 8px 16px; border-radius: 10px; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: 0.2s; }
.btn-primary { background: var(--blue); color: #fff; }
.btn-edit { background: var(--amber); color: #fff; }
.btn-delete { background: var(--red); color: #fff; }
.table-wrap { background: var(--gray-0); border: 1px solid var(--gray-3); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
table { width: 100%; border-collapse: collapse; }
thead th { background: var(--gray-2); padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 700; color: var(--gray-5); text-transform: uppercase; }
td { padding: 16px 20px; border-bottom: 1px solid var(--gray-2); vertical-align: middle; }
.td-foto img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; border: 2px solid var(--gray-3); cursor: pointer; }
.badge-kelas { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; background: var(--blue-lt); color: var(--blue); }
.form-card { background: var(--gray-0); border: 1px solid var(--gray-3); border-radius: var(--radius); box-shadow: 0 10px 25px rgba(0,0,0,.05); max-width: 700px; margin: 0 auto; overflow: hidden; }
.form-row { display: grid; grid-template-columns: 180px 1fr; border-bottom: 1px solid var(--gray-2); padding: 20px 24px; gap: 20px; align-items: center; }
.form-input { padding: 10px 14px; border: 1.5px solid var(--gray-3); border-radius: 8px; width: 100%; font-family: inherit; }
.form-input:focus { border-color: var(--blue); outline: none; }
.form-footer { padding: 20px 24px; background: var(--gray-1); display: flex; gap: 10px; justify-content: flex-end; }
.lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:9999; align-items:center; justify-content:center; }
.lightbox.show { display:flex; }
</style>
</head>
<body>

<nav class="nav">
    <div class="nav-brand"><span>☁️</span> Cloud Attendance</div>
    <div class="nav-links">
        <a href="index.php" class="nav-link">Absensi</a>
        <a href="siswa.php" class="nav-link active">Daftar Siswa</a>
    </div>
</nav>

<div class="page">
    <?php if (isset($_GET['msg'])): ?>
    <?php 
    $msgs = [
        'tambah_ok' => ['✅', 'Siswa berhasil disimpan ke S3!', 'success'],
        'edit_ok'   => ['✅', 'Data & foto diperbarui!', 'success'],
        'hapus_ok'  => ['🗑️', 'Siswa & berkas S3 dihapus!', 'danger']
    ]; 
    ?>
    <?php if (isset($msgs[$_GET['msg']])): $m = $msgs[$_GET['msg']]; ?>
    <div class="alert alert-<?=$m[2]?>"><?=$m[0]?> <?=$m[1]?></div>
    <?php endif; endif; ?>

    <?php if ($mode === 'list'): ?>
    <div class="page-header">
        <h1 class="page-title">Database Siswa</h1>
        <a href="siswa.php?tambah=1" class="btn btn-primary">＋ Tambah Siswa</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>No. Presensi</th>
                    <th>Kelas</th>
                    <th>Foto (S3)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($siswa_list && $siswa_list->num_rows > 0):
                while ($s = $siswa_list->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:700"><?=htmlspecialchars($s['nama'])?></td>
                <td style="font-weight:600"><?=htmlspecialchars($s['no_presensi'] ?? '-')?></td>
                <td><span class="badge-kelas"><?=htmlspecialchars($s['kelas'])?></span></td>
                <td class="td-foto">
                    <?php if (!empty($s['foto'])): ?>
                        <img src="<?= $s3_base_url . htmlspecialchars($s['foto']) ?>" onclick="bukaLB(this.src)">
                    <?php else: ?>
                        <div style="width:60px; height:60px; background:#f1f5f9; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#cbd5e1">👤</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex; gap:6px;">
                        <a href="siswa.php?edit=<?=$s['id']?>" class="btn btn-edit">✏️</a>
                        <a href="siswa.php?hapus=<?=$s['id']?>" class="btn btn-delete" onclick="return confirm('Hapus siswa?')">🗑️</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" style="text-align:center; padding:60px; color:#94a3b8;">Belum ada data siswa.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($mode === 'tambah' || $mode === 'edit'): ?>
    <a href="siswa.php" style="text-decoration:none; color:var(--blue); font-size:14px; font-weight:700; display:block; margin-bottom:15px;">← Kembali</a>
    <h1 class="page-title" style="margin-bottom:20px"><?= $mode === 'tambah' ? 'Tambah Siswa Baru' : 'Edit Profil Siswa' ?></h1>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="<?= $mode ?>">
            <?php if($mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?=$edit_data['id']?>">
                <input type="hidden" name="old_foto" value="<?=htmlspecialchars($edit_data['foto'] ?? '')?>">
            <?php endif; ?>
            
            <div class="form-body">
                <div class="form-row">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-input" value="<?= $edit_data['nama'] ?? '' ?>" required>
                </div>
                <div class="form-row">
                    <label>No. Presensi</label>
                    <input type="text" name="no_presensi" class="form-input" value="<?= $edit_data['no_presensi'] ?? '' ?>" required>
                </div>
                <div class="form-row">
                    <label>Kelas</label>
                    <input type="text" name="kelas" class="form-input" value="<?= $edit_data['kelas'] ?? '' ?>" required>
                </div>
                <div class="form-row">
                    <label>Foto Profil</label>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <?php if ($mode === 'edit' && !empty($edit_data['foto'])): ?>
                            <img src="<?= $s3_base_url . $edit_data['foto'] ?>" id="previewImg" style="width:60px; height:60px; border-radius:8px; object-fit:cover;">
                        <?php else: ?>
                            <img id="previewImg" style="width:60px; height:60px; border-radius:8px; object-fit:cover; display:none;">
                        <?php endif; ?>
                        <input type="file" name="foto" id="fotoInput" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="form-footer">
                <a href="siswa.php" class="btn" style="background:#e2e8f0; color:#475569;">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Simpan ke Cloud</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<div class="lightbox" id="lb" onclick="this.classList.remove('show')">
    <img id="lbImg" src="" style="max-width:90%; max-height:80%; border-radius:12px;">
</div>

<script>
function bukaLB(src){ document.getElementById('lbImg').src=src; document.getElementById('lb').classList.add('show'); }
document.getElementById('fotoInput')?.addEventListener('change', function(){
    const f=this.files[0]; if(!f) return;
    const p=document.getElementById('previewImg');
    const r=new FileReader();
    r.onload=e=>{ p.src=e.target.result; p.style.display='block'; };
    r.readAsDataURL(f);
});
</script>
</body>
</html>