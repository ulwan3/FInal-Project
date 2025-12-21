-- Buat database
CREATE DATABASE IF NOT EXISTS keuangan_mahasiswa;
USE keuangan_mahasiswa;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'mahasiswa') DEFAULT 'mahasiswa',
    nama_lengkap VARCHAR(100) NOT NULL,
    nim VARCHAR(20),
    program_studi VARCHAR(100),
    tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
    terakhir_login DATETIME
);

-- Tabel mata_uang
CREATE TABLE IF NOT EXISTS mata_uang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_uang VARCHAR(3) UNIQUE NOT NULL,
    nama_uang VARCHAR(50) NOT NULL,
    nilai_kurs_terhadap_idr DECIMAL(15,6) NOT NULL,
    tanggal_update DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_aktif BOOLEAN DEFAULT TRUE
);

-- Tabel kategori
CREATE TABLE IF NOT EXISTS kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    tipe ENUM('pemasukan', 'pengeluaran') NOT NULL,
    deskripsi TEXT
);

-- Tabel transaksi
CREATE TABLE IF NOT EXISTS transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kategori_id INT NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    mata_uang_id INT NOT NULL,
    jumlah_idr DECIMAL(15,2) NOT NULL,
    deskripsi TEXT,
    tanggal_transaksi DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE,
    FOREIGN KEY (mata_uang_id) REFERENCES mata_uang(id) ON DELETE CASCADE
);

-- Tabel budget
CREATE TABLE IF NOT EXISTS budget (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    kategori_id INT NOT NULL,
    jumlah_budget DECIMAL(15,2) NOT NULL,
    periode ENUM('bulanan', 'semester', 'tahunan') DEFAULT 'bulanan',
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE
);

-- Tabel untuk push subscriptions
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subscription_data TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_subscription (user_id)
);

-- Tabel untuk recurring payments (tagihan berulang)
CREATE TABLE IF NOT EXISTS recurring_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_type ENUM('utility', 'subscription', 'loan', 'other') DEFAULT 'other',
    due_day TINYINT NOT NULL, -- Tanggal jatuh tempo (1-31)
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    related_module VARCHAR(50),
    related_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tambahkan kolom untuk tracking kapan terakhir generate notifikasi
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_notification_generation DATE NULL;

-- Buat index untuk performa
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);

-- Insert data awal
INSERT IGNORE INTO users (id, username, password, email, role, nama_lengkap) VALUES 
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin', 'Administrator');

INSERT IGNORE INTO mata_uang (id, kode_uang, nama_uang, nilai_kurs_terhadap_idr) VALUES
(1, 'IDR', 'Rupiah Indonesia', 1),
(2, 'USD', 'Dollar Amerika', 15000),
(3, 'EUR', 'Euro', 16000),
(4, 'SGD', 'Dollar Singapura', 11000);

INSERT IGNORE INTO kategori (id, nama_kategori, tipe, deskripsi) VALUES
(1, 'Gaji', 'pemasukan', 'Pendapatan dari pekerjaan'),
(2, 'Beasiswa', 'pemasukan', 'Dana beasiswa'),
(3, 'Orang Tua', 'pemasukan', 'Bantuan dari orang tua'),
(4, 'Makanan', 'pengeluaran', 'Pengeluaran untuk makanan'),
(5, 'Transportasi', 'pengeluaran', 'Biaya transportasi'),
(6, 'Hiburan', 'pengeluaran', 'Pengeluaran untuk hiburan'),
(7, 'Belanja', 'pengeluaran', 'Pengeluaran untuk belanja'),
(8, 'Pendidikan', 'pengeluaran', 'Biaya pendidikan');

-- Buat index untuk performa
CREATE INDEX idx_transaksi_user_id ON transaksi(user_id);
CREATE INDEX idx_transaksi_tanggal ON transaksi(tanggal_transaksi);
CREATE INDEX idx_budget_user_id ON budget(user_id);