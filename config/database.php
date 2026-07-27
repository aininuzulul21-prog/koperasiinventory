<?php
/**
 * Konfigurasi Database MySQL
 * Sistem Inventori Barang Koperasi
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'koperasi_inventory');
define('DB_PORT', '3306');

/**
 * Mendapatkan koneksi database dengan PDO
 */
function getConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal: ' . $e->getMessage()
            ]));
        }
    }
    
    return $conn;
}

/**
 * Memulai koneksi database (alias)
 */
function getDB() {
    return getConnection();
}

