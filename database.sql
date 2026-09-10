-- ============================================
-- database.sql (VERSI TERBARU)
-- Jalankan ini di phpMyAdmin / MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS db_absensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_absensi;

-- Tabel siswa (dengan no_presensi dan foto)
CREATE TABLE IF NOT EXISTS siswa (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama         VARCHAR(100)  NOT NULL,
    no_presensi  VARCHAR(10)   DEFAULT NULL,
    kelas        VARCHAR(30)   NOT NULL,
    foto         VARCHAR(255)  DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- Tabel absensi
CREATE TABLE IF NOT EXISTS absensi (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id     INT           NOT NULL,
    tanggal      DATE          NOT NULL,
    status_hadir ENUM('Hadir','Tidak Hadir') NOT NULL DEFAULT 'Hadir',
    foto         VARCHAR(255)  DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

-- Data contoh
INSERT INTO siswa (nama, no_presensi, kelas) VALUES
('Andi Pratama','1','XI RPL 1'),('Budi Santoso','2','XI RPL 1'),
('Citra Dewi','3','XI RPL 1'),('Dina Rahayu','4','XI RPL 2'),
('Eko Wahyudi','5','XI RPL 2'),('Fitri Handayani','6','XII RPL 1'),
('Gilang Ramadhan','7','XII RPL 1'),('Hana Permata','8','XII RPL 2'),
('Irfan Maulana','9','XII RPL 2'),('Julia Sari','10','XII RPL 2');

-- == Jika tabel siswa sudah ADA, jalankan ini untuk upgrade: ==
-- ALTER TABLE siswa ADD COLUMN no_presensi VARCHAR(10) DEFAULT NULL AFTER nama;
-- ALTER TABLE siswa ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER kelas;
