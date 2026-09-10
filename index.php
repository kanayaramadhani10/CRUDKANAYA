<?php
// ============================================
// index.php - Halaman Utama (Tampilan Data Absensi - AWS S3 Integrated)
// ============================================
require_once 'koneksi.php';

// Konfigurasi S3 (Gunakan URL yang Anda berikan)
$s3_base_url = "https://bucketku-uploads.s3.us-east-1.amazonaws.com/uploads/";

// Ambil filter dari URL (opsional)
$filter_kelas = isset($_GET['kelas']) ? $conn->real_escape_string($_GET['kelas']) : '';
$filter_tanggal = isset($_GET['tanggal']) ? $conn->real_escape_string($_GET['tanggal']) : '';
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Query dengan JOIN + filter opsional
$where = [];
if ($filter_kelas)   $where[] = "s.kelas = '$filter_kelas'";
if ($filter_tanggal) $where[] = "a.tanggal = '$filter_tanggal'";
if ($filter_status)  $where[] = "a.status_hadir = '$filter_status'";
$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT a.id, s.nama, s.kelas, a.tanggal, a.status_hadir, a.foto
          FROM absensi a
          JOIN siswa s ON a.siswa_id = s.id
          $where_sql
          ORDER BY a.tanggal DESC, s.nama ASC";

$result = $conn->query($query);

// Ambil daftar kelas untuk filter dropdown
$kelas_result = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC");

// Hitung statistik hari ini
$today = date('Y-m-d');
$stat_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_hadir = 'Hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN status_hadir = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir
    FROM absensi WHERE tanggal = '$today'";
$stat = $conn->query($stat_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Siswa - AWS Cloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CSS tetap sama dengan desain Anda sebelumnya untuk menjaga konsistensi UI */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1e40af; --primary-light: #3b82f6; --primary-pale: #eff6ff;
            --success: #16a34a; --success-pale: #f0fdf4;
            --danger: #dc2626; --danger-pale: #fef2f2;
            --warning: #d97706; --warning-pale: #fffbeb;
            --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
            --gray-400: #94a3b8; --gray-50: #f8fafc; --gray-700: #334155; --gray-900: #0f172a;
            --white: #ffffff; --radius: 12px; --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow: 0 4px 16px rgba(0,0,0,.08);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--gray-50); color: var(--gray-900); min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%); color: white; padding: 16px 24px; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(30,64,175,.3); }
        .header-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
        .header-brand { display: flex; align-items: center; gap: 12px; }
        .header-icon { width: 42px; height: 42px; background: rgba(255,255,255,.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .header-title { font-size: 18px; font-weight: 800; }
        .container { max-width: 1200px; margin: 0 auto; padding: 28px 24px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .stat-icon.blue { background: var(--primary-pale); }
        .stat-icon.green { background: var(--success-pale); }
        .stat-icon.red { background: var(--danger-pale); }
        .stat-number { font-size: 28px; font-weight: 800; }
        .card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 18px 22px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead th { background: var(--gray-50); padding: 12px 16px; text-align: left; color: var(--gray-500); font-weight: 700; text-transform: uppercase; border-bottom: 1px solid var(--gray-200); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .foto-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 2px solid var(--gray-200); cursor: pointer; transition: 0.2s; }
        .foto-thumb:hover { transform: scale(1.1); }
        .foto-placeholder { width: 48px; height: 48px; border-radius: 8px; background: var(--gray-100); border: 2px dashed var(--gray-200); display: flex; align-items: center; justify-content: center; color: var(--gray-400); }
        .btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; cursor: pointer; border: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-ghost { background: var(--gray-100); color: var(--gray-700); }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; }
        .status-hadir { background: var(--success-pale); color: var(--success); }
        .status-tidak { background: var(--danger-pale); color: var(--danger); }
        .lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 1000; align-items: center; justify-content: center; }
        .lightbox.show { display: flex; }
        .lightbox img { max-width: 90%; max-height: 80%; border-radius: 12px; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-inner">
        <div class="header-brand">
            <div class="header-icon">☁️</div>
            <div>
                <div class="header-title">Sistem Absensi Cloud</div>
                <div class="header-subtitle">AWS EC2 + RDS + S3 Storage</div>
            </div>
        </div>
        <div style="font-size: 13px; opacity: 0.9;">📅 <?= date('d F Y') ?></div>
    </div>
</header>

<div class="container">
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📊</div>
            <div>
                <div class="stat-number" style="color:var(--primary)"><?= $stat['total'] ?? 0 ?></div>
                <div style="font-size:13px; color:var(--gray-500)">Total Hari Ini</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div>
                <div class="stat-number" style="color:var(--success)"><?= $stat['hadir'] ?? 0 ?></div>
                <div style="font-size:13px; color:var(--gray-500)">Siswa Hadir</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">❌</div>
            <div>
                <div class="stat-number" style="color:var(--danger)"><?= $stat['tidak_hadir'] ?? 0 ?></div>
                <div style="font-size:13px; color:var(--gray-500)">Tidak Hadir</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div style="font-weight:700">📋 Daftar Kehadiran</div>
            <div style="display:flex; gap:10px;">
                <form method="GET" style="display:flex; gap:5px;">
                    <select name="kelas" style="padding:7px; border-radius:6px; border:1px solid var(--gray-200)">
                        <option value="">Semua Kelas</option>
                        <?php while ($k = $kelas_result->fetch_assoc()): ?>
                            <option value="<?= $k['kelas'] ?>" <?= $filter_kelas == $k['kelas'] ? 'selected' : '' ?>><?= $k['kelas'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-ghost">Filter</button>
                </form>
                <a href="tambah.php" class="btn btn-primary">＋ Tambah</a>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Bukti Foto (S3)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($row['nama']) ?></td>
                            <td><span style="background:var(--primary-pale); color:var(--primary); padding:2px 8px; border-radius:4px; font-size:12px; font-weight:700"><?= htmlspecialchars($row['kelas']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <span class="status-badge <?= $row['status_hadir'] == 'Hadir' ? 'status-hadir' : 'status-tidak' ?>">
                                    <?= $row['status_hadir'] == 'Hadir' ? '✅ Hadir' : '❌ Absen' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['foto'])): ?>
                                    <img src="<?= $s3_base_url . $row['foto'] ?>" 
                                         class="foto-thumb" 
                                         onclick="bukaLightbox(this.src)"
                                         alt="Foto S3">
                                <?php else: ?>
                                    <div class="foto-placeholder">📷</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning" style="padding:5px 10px">✏️</a>
                                    <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger" style="padding:5px 10px" onclick="return confirm('Hapus data?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:50px; color:var(--gray-400)">Belum ada data absensi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="lightbox" id="lightbox" onclick="this.classList.remove('show')">
    <img id="lightboxImg" src="">
</div>

<script>
function bukaLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('show');
}
</script>

</body>
</html>