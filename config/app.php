<?php
/**
 * Konfigurasi Aplikasi
 * Sistem Inventori Barang Koperasi
 */

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Base URL
define('BASE_URL', 'http://localhost/koperasi-inventory');
define('APP_NAME', 'Sistem Inventori Barang Koperasi');
define('APP_VERSION', '1.0.0');

// Path
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');

// Format
define('CURRENCY', 'Rp');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

/**
 * Helper untuk response JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Helper untuk redirect
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Format mata uang
 */
function formatRupiah($angka) {
    return CURRENCY . ' ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal($date, $format = 'd F Y') {
    if (!$date || $date == '0000-00-00') return '-';
    
    $hari = [
        'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
    ];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $time = strtotime($date);
    $result = $format;
    $result = str_replace('d', date('d', $time), $result);
    $result = str_replace('m', date('m', $time), $result);
    $result = str_replace('Y', date('Y', $time), $result);
    $result = str_replace('F', $bulan[(int)date('m', $time)], $result);
    $result = str_replace('l', $hari[date('w', $time)], $result);
    
    return $result;
}

/**
 * Generate kode transaksi
 */
function generateNoTransaksi() {
    $date = date('Ymd');
    $rand = strtoupper(substr(uniqid(), -6));
    return 'TRX-' . $date . '-' . $rand;
}

/**
 * Generate kode barang
 */
function generateKodeBarang() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as total FROM produk");
    $row = $stmt->fetch();
    $num = str_pad($row['total'] + 1, 5, '0', STR_PAD_LEFT);
    return 'BRG-' . date('Y') . '-' . $num;
}

/**
 * Escape HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

