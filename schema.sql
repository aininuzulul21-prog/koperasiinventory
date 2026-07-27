-- ============================================
-- SISTEM INVENTORI BARANG KOPERASI
-- Database Schema MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS koperasi_inventory;
USE koperasi_inventory;

-- ==================== USERS ====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    foto VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== KATEGORI ====================
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'fa-box',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama_kategori (nama_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== SUPPLIER ====================
CREATE TABLE supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_supplier VARCHAR(150) NOT NULL,
    alamat TEXT DEFAULT NULL,
    telepon VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    kontak_person VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama_supplier (nama_supplier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== PRODUK ====================
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    nama_barang VARCHAR(200) NOT NULL,
    id_kategori INT DEFAULT NULL,
    merk VARCHAR(100) DEFAULT NULL,
    satuan VARCHAR(20) DEFAULT 'Pcs',
    harga_beli DECIMAL(15,2) DEFAULT 0,
    harga_jual DECIMAL(15,2) DEFAULT 0,
    stok INT DEFAULT 0,
    stok_minimum INT DEFAULT 5,
    lokasi_rak VARCHAR(50) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_barcode (barcode),
    INDEX idx_kode_barang (kode_barang),
    INDEX idx_nama_barang (nama_barang),
    INDEX idx_id_kategori (id_kategori),
    CONSTRAINT fk_produk_kategori FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== BARANG MASUK ====================
CREATE TABLE barang_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    id_supplier INT DEFAULT NULL,
    qty INT NOT NULL,
    harga_beli DECIMAL(15,2) DEFAULT 0,
    no_batch VARCHAR(50) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    tanggal DATE NOT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_produk (id_produk),
    INDEX idx_id_supplier (id_supplier),
    INDEX idx_tanggal (tanggal),
    CONSTRAINT fk_bm_produk FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bm_supplier FOREIGN KEY (id_supplier) REFERENCES supplier(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_bm_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== BARANG KELUAR ====================
CREATE TABLE barang_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    qty INT NOT NULL,
    tujuan VARCHAR(200) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    tanggal DATE NOT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_produk (id_produk),
    INDEX idx_tanggal (tanggal),
    CONSTRAINT fk_bk_produk FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bk_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== TRANSAKSI ====================
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    total_item INT DEFAULT 0,
    total_harga DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) DEFAULT 0,
    bayar DECIMAL(15,2) DEFAULT 0,
    kembalian DECIMAL(15,2) DEFAULT 0,
    metode_bayar ENUM('tunai', 'qris', 'transfer') DEFAULT 'tunai',
    status ENUM('selesai', 'batal', 'refund') DEFAULT 'selesai',
    id_kasir INT DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_no_transaksi (no_transaksi),
    INDEX idx_id_kasir (id_kasir),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_transaksi_kasir FOREIGN KEY (id_kasir) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== DETAIL TRANSAKSI ====================
CREATE TABLE detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_produk INT NOT NULL,
    qty INT NOT NULL,
    harga DECIMAL(15,2) NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_transaksi (id_transaksi),
    INDEX idx_id_produk (id_produk),
    CONSTRAINT fk_dt_transaksi FOREIGN KEY (id_transaksi) REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_dt_produk FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== LOG AKTIVITAS ====================
CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT DEFAULT NULL,
    aksi VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    keterangan TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_user (id_user),
    INDEX idx_aksi (aksi),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_log_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== SETTING ====================
CREATE TABLE setting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_setting VARCHAR(100) NOT NULL UNIQUE,
    nilai_setting TEXT NOT NULL,
    tipe VARCHAR(20) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== NOTIFIKASI ====================
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT DEFAULT NULL,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT DEFAULT NULL,
    tipe ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
    url VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_user (id_user),
    INDEX idx_is_read (is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== RIWAYAT LOGIN ====================
CREATE TABLE riwayat_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    aksi ENUM('login', 'logout') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_user (id_user),
    INDEX idx_aksi (aksi),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_rl_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== INSERT DATA DEFAULT ====================

-- Default Admin
INSERT INTO users (nama, username, password, role) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password

-- Default Settings
INSERT INTO setting (nama_setting, nilai_setting, tipe) VALUES
('nama_aplikasi', 'Sistem Inventori Barang Koperasi', 'text'),
('alamat_koperasi', 'Jl. Contoh No. 123, Kota', 'text'),
('telepon', '081234567890', 'text'),
('email', 'koperasi@example.com', 'text'),
('logo', '', 'text'),
('currency', 'Rp', 'text'),
('timezone', 'Asia/Jakarta', 'text');

-- Default Kategori
INSERT INTO kategori (nama_kategori, icon) VALUES
('Makanan & Minuman', 'fa-utensils'),
('Alat Tulis Kantor', 'fa-pen'),
('Elektronik', 'fa-plug'),
('Kebersihan', 'fa-soap'),
('Lainnya', 'fa-box');

