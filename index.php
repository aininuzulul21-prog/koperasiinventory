<?php
/**
 * Main Router
 * Sistem Inventori Barang Koperasi
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

// Require login untuk semua halaman
requireLogin();

// Get page parameter
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';

// Whitelist halaman yang diizinkan
$allowedPages = [
    'dashboard', 'produk', 'kategori', 'supplier', 'user',
    'barang-masuk', 'barang-keluar', 'transaksi', 'riwayat-transaksi',
    'laporan', 'pengaturan', 'backup', 'notifikasi', 'profile',
    'cetak-struk'
];

// Cek akses
$restrictedPages = ['user', 'laporan', 'pengaturan', 'backup'];
if (in_array($page, $restrictedPages) && $_SESSION['user_role'] !== 'admin') {
    redirect(BASE_URL . '/index.php?page=dashboard&error=akses_ditolak');
}

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// Include header
include_once __DIR__ . '/includes/header.php';

// Load module berdasarkan page
$modulePath = __DIR__ . '/modules/' . $page . '/index.php';

if (file_exists($modulePath)) {
    include_once $modulePath;
} else {
    include_once __DIR__ . '/modules/dashboard/index.php';
}

// Include footer
include_once __DIR__ . '/includes/footer.php';

