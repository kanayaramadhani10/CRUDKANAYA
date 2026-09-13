CREATE DATABASE IF NOT EXISTS db_sekolah;
USE db_sekolah;

-- Tabel untuk akun admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Insert data admin default (Username: kanaya | Password: kanaya123)
INSERT INTO admin (username, password) VALUES ('kanaya', MD5('kanaya123'));

-- Tabel untuk data siswa
CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    foto VARCHAR(255) NOT NULL
);
