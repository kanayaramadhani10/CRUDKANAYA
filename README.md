# 📋 Sistem Absensi Siswa
### Aplikasi Web PHP Native + MySQL

---

## 📁 Struktur File

```
absensi/
├── koneksi.php       ← Konfigurasi database
├── index.php         ← Halaman utama (tampil data)
├── tambah.php        ← Form tambah absensi
├── edit.php          ← Form edit absensi
├── hapus.php         ← Script hapus data
├── database.sql      ← Script SQL database
└── uploads/          ← Folder foto (dibuat otomatis)
```

---

## 🚀 Cara Menjalankan di XAMPP

### Langkah 1 — Install & Jalankan XAMPP
1. Download XAMPP di https://www.apachefriends.org
2. Buka XAMPP Control Panel
3. Klik **Start** pada **Apache** dan **MySQL**

### Langkah 2 — Letakkan File Proyek
1. Copy seluruh folder `absensi/` ke:
   - Windows: `C:\xampp\htdocs\absensi\`
   - Mac/Linux: `/opt/lampp/htdocs/absensi/`

### Langkah 3 — Buat Database
1. Buka browser, ketik: http://localhost/phpmyadmin
2. Klik tab **SQL**
3. Copy isi file `database.sql` dan paste, lalu klik **Go**

   _Atau:_ Klik **Import** → pilih file `database.sql` → klik **Go**

### Langkah 4 — Konfigurasi Koneksi (jika perlu)
Buka `koneksi.php` dan sesuaikan:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // username MySQL Anda
define('DB_PASS', '');        // password MySQL (kosong jika default XAMPP)
define('DB_NAME', 'db_absensi');
```

### Langkah 5 — Buka Aplikasi
Ketik di browser: **http://localhost/absensi/**

---

## ✅ Fitur Lengkap

| Fitur | Keterangan |
|-------|------------|
| 📊 Dashboard | Statistik hadir/tidak hadir hari ini |
| 📋 Tampil Data | Tabel absensi dengan foto, filter kelas/tanggal/status |
| ➕ Tambah | Form dengan dropdown siswa, upload foto, validasi |
| ✏️ Edit | Update data termasuk ganti/hapus foto |
| 🗑️ Hapus | Hapus data + foto otomatis terhapus |
| 🔍 Filter | Filter berdasarkan kelas, tanggal, status |
| 🖼️ Lightbox | Klik foto untuk memperbesar |

---

## ⚠️ Troubleshooting

**Koneksi gagal?**
→ Pastikan MySQL sudah Start di XAMPP

**Foto tidak bisa diupload?**
→ Pastikan folder `uploads/` ada dan bisa ditulis (chmod 755)

**Halaman tidak ditemukan?**
→ Pastikan file diletakkan di folder `htdocs/absensi/`

---

## 🛠️ Teknologi
- **PHP** 7.4+ (Native, tanpa framework)
- **MySQL** dengan MySQLi
- **HTML5 + CSS3** + Google Fonts
- **Bootstrap** tidak digunakan (CSS murni)
