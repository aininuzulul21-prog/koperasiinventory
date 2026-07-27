# 📦 Sistem Inventori Barang Koperasi

Aplikasi web manajemen inventaris barang koperasi dengan fitur barcode scanner, laporan, dan multi-user. Dibangun dengan **PHP Native**, **MySQL**, **Bootstrap 5**, dan **JavaScript**.

## 🚀 Fitur Lengkap

### 🔐 Autentikasi & Manajemen User
- Login dengan username & password
- Show Password
- Remember Me
- Session & Logout
- Role-based access: **Administrator** & **Kasir**
- Riwayat login

### 📊 Dashboard
- 8 Kartu Statistik (Total Produk, Kategori, Supplier, Stok, Hampir Habis, Habis, Transaksi Hari Ini, Pendapatan)
- 4 Grafik (Barang Masuk, Barang Keluar, Penjualan, Stok)
- Aktivitas Terbaru (Timeline)
- Produk Terlaris Bulan Ini

### 📦 Master Data
- **Produk**: CRUD lengkap, scan barcode, generate barcode, upload foto, import/export, cetak barcode
- **Kategori**: CRUD dengan icon
- **Supplier**: CRUD dengan kontak person
- **User**: CRUD dengan role admin/kasir

### 📷 Barcode Scanner
- Scan menggunakan kamera laptop/HP (Html5-QRCode)
- Mendukung: Code128, QR Code, EAN13, UPC, Code39
- Real-time scanning
- Auto-fill form produk
- Auto-add ke transaksi

### 📦 Manajemen Stok
- **Barang Masuk**: Scan barcode, pilih supplier, stok otomatis bertambah
- **Barang Keluar**: Scan barcode, stok otomatis berkurang

### 💰 Transaksi Penjualan
- Scan barcode → otomatis masuk keranjang
- Keranjang: jumlah, diskon per item, subtotal
- Hitung otomatis: Total, Bayar, Kembalian
- Metode Pembayaran: Tunai, QRIS, Transfer
- Cetak Struk
- Riwayat Transaksi dengan filter

### 📈 Laporan
- Laporan Stok
- Barang Masuk & Keluar
- Penjualan
- Produk Terlaris
- Stok Minimum
- Nilai Persediaan
- Export CSV

### 🔔 Notifikasi
- Stok hampir habis
- Barang habis
- Badge merah di navbar
- Dropdown notifikasi real-time

### 🌙 Bonus Fitur
- ✅ Dark Mode
- ✅ PWA (Progressive Web App)
- ✅ Responsive Design (Desktop, Tablet, Mobile)
- ✅ DataTables dengan filter
- ✅ Select2 untuk dropdown
- ✅ CSRF Protection
- ✅ Prepared Statement
- ✅ Password Hashing
- ✅ Session Security
- ✅ Role Based Access Control
- ✅ Aktivitas Log
- ✅ Cetak Barcode Massal

## 🛠️ Teknologi

| Teknologi | Keterangan |
|-----------|------------|
| **Frontend** | HTML5, CSS3, Bootstrap 5.3, JavaScript, jQuery |
| **Backend** | PHP 8 (Native) |
| **Database** | MySQL 8 |
| **Barcode** | Html5-QRCode |
| **Chart** | Chart.js 4 |
| **Table** | DataTables |
| **Icons** | Font Awesome 6 |
| **Alert** | SweetAlert2 |
| **Select** | Select2 |

## 📋 Persyaratan Sistem

- PHP 8.0+
- MySQL 8.0+
- Web Server (Apache/Nginx)
- XAMPP / Laragon / WAMP (rekomendasi)
- Browser modern (Chrome, Firefox, Edge)
- Kamera (untuk fitur scanner)

## 🔧 Instalasi

### 1. Clone project
```bash
cd C:/xampp/htdocs/
git clone https://github.com/username/koperasi-inventory.git
# atau copy folder koperasi-inventory ke htdocs
```

### 2. Setup Database
Akses melalui browser:
```
http://localhost/koperasi-inventory/setup.php
```

Atau import manual:
1. Buka phpMyAdmin
2. Buat database `koperasi_inventory`
3. Import file `database/schema.sql`

### 3. Konfigurasi
File `config/database.php` akan otomatis terisi saat instalasi.
Jika manual, sesuaikan:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'koperasi_inventory');
```

### 4. Akses Aplikasi
```
http://localhost/koperasi-inventory/login.php
```

### Akun Default
| Role | Username | Password |
|------|----------|----------|
| **Administrator** | `admin` | `password` |
| **Kasir** | `kasir` | `password` |

## 📁 Struktur Folder

```
koperasi-inventory/
├── api/                    # API Endpoints
│   ├── chart_data.php
│   ├── get_notifikasi.php
│   ├── mark_notif_read.php
│   └── mark_all_notif_read.php
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── dark-mode.css
│   ├── js/
│   │   └── app.js
│   ├── img/
│   └── uploads/
│       └── produk/
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── auth.php
│   ├── header.php
│   ├── navbar.php
│   ├── sidebar.php
│   └── footer.php
├── modules/
│   ├── dashboard/
│   ├── produk/
│   ├── kategori/
│   ├── supplier/
│   ├── user/
│   ├── barang_masuk/
│   ├── barang_keluar/
│   ├── transaksi/
│   ├── riwayat-transaksi/
│   ├── cetak-struk/
│   └── laporan/
├── index.php
├── login.php
├── logout.php
├── setup.php
├── manifest.json
├── README.md
└── TODO.md
```

## 🗄️ Database

### Tabel
| Tabel | Keterangan |
|-------|------------|
| `users` | Data pengguna (admin & kasir) |
| `produk` | Data produk barang |
| `kategori` | Kategori produk |
| `supplier` | Data supplier |
| `barang_masuk` | Riwayat barang masuk |
| `barang_keluar` | Riwayat barang keluar |
| `transaksi` | Transaksi penjualan |
| `detail_transaksi` | Detail item transaksi |
| `log_aktivitas` | Log aktivitas user |
| `notifikasi` | Notifikasi sistem |
| `riwayat_login` | Riwayat login user |
| `setting` | Pengaturan aplikasi |

### Relasi
- `produk.id_kategori` → `kategori.id`
- `barang_masuk.id_produk` → `produk.id`
- `barang_masuk.id_supplier` → `supplier.id`
- `transaksi.id_kasir` → `users.id`
- `detail_transaksi.id_transaksi` → `transaksi.id`
- `detail_transaksi.id_produk` → `produk.id`

## 📱 Penggunaan

### Untuk Admin
1. Login dengan akun admin
2. Kelola master data (Produk, Kategori, Supplier, User)
3. Pantau dashboard
4. Proses transaksi
5. Cetak laporan

### Untuk Kasir
1. Login dengan akun kasir
2. Scan barcode produk
3. Proses transaksi penjualan
4. Lihat stok produk

## 🔒 Keamanan

- Password hashing dengan `password_hash()`
- Prepared statement untuk query SQL
- CSRF Token pada setiap form
- Session login dengan timeout
- Validasi & sanitasi input
- Role-based access control
- XSS protection dengan `htmlspecialchars()`

## 🎨 Tema

- **Light Mode**: Default dengan warna biru (#2563EB) dominan
- **Dark Mode**: Toggle di navbar
- Responsive: Desktop, tablet, mobile

## 📄 Lisensi

MIT License - Silakan gunakan dan modifikasi sesuai kebutuhan.

## 👨‍💻 Pengembang

Dibuat oleh Tim Developer
© 2024 Sistem Inventori Barang Koperasi
