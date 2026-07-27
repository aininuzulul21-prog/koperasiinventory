<?php
/**
 * Sistem Autentikasi
 * Handle login, logout, session, dan role-based access
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek role user
 */
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    
    if (is_string($roles)) {
        return $_SESSION['user_role'] === $roles;
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

/**
 * Require login - redirect ke login jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after'] = $_SERVER['REQUEST_URI'];
        redirect(BASE_URL . '/login.php');
    }
}

/**
 * Require role tertentu
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        redirect(BASE_URL . '/index.php?error=akses_ditolak');
    }
}

/**
 * Login user
 */
function loginUser($username, $password, $remember = false) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_foto'] = $user['foto'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Log aktivitas login
            logAktivitas($user['id'], 'login', 'Auth', 'User login: ' . $username);
            
            // Log riwayat login
            logRiwayatLogin($user['id'], $username, 'login');
            
            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                // Simpan token ke database (implementasi sederhana)
                $_SESSION['remember_token'] = $token;
            }
            
            return ['success' => true, 'message' => 'Login berhasil', 'role' => $user['role']];
        }
        
        return ['success' => false, 'message' => 'Username atau password salah'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $username = $_SESSION['user_username'];
        
        // Log aktivitas
        logAktivitas($userId, 'logout', 'Auth', 'User logout: ' . $username);
        logRiwayatLogin($userId, $username, 'logout');
    }
    
    // Hapus session
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Hapus cookie remember
    setcookie('remember_token', '', time() - 3600, '/');
}

/**
 * Log aktivitas user
 */
function logAktivitas($userId, $aksi, $module, $keterangan = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO log_aktivitas (id_user, aksi, module, keterangan, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $aksi,
            $module,
            $keterangan,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Silent fail untuk logging
    }
}

/**
 * Log riwayat login
 */
function logRiwayatLogin($userId, $username, $aksi) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO riwayat_login (id_user, username, aksi, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $username,
            $aksi,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Silent fail
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nama, username, role, foto, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get notifikasi unread count
 */
function getNotifikasiUnread() {
    if (!isLoggedIn()) return 0;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifikasi WHERE (id_user = ? OR id_user IS NULL) AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch()['total'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Cek stok minimum dan buat notifikasi
 */
function cekStokMinimum() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM produk WHERE stok <= stok_minimum AND stok > 0 AND is_active = 1");
        $hampirHabis = $stmt->fetchAll();
        
        $stmt = $db->query("SELECT * FROM produk WHERE stok = 0 AND is_active = 1");
        $habis = $stmt->fetchAll();
        
        // Buat notifikasi untuk stok hampir habis
        foreach ($hampirHabis as $produk) {
            $cekTidak = $db->prepare("SELECT id FROM notifikasi WHERE judul LIKE ? AND is_read = 0");
            $cekTidak->execute(['%' . $produk['nama_barang'] . '%']);
            
            if (!$cekTidak->fetch()) {
                $stmt = $db->prepare("INSERT INTO notifikasi (judul, pesan, tipe, url) VALUES (?, ?, 'warning', ?)");
                $stmt->execute([
                    'Stok Hampir Habis: ' . $produk['nama_barang'],
                    'Stok ' . $produk['nama_barang'] . ' hanya tersisa ' . $produk['stok'] . ' ' . $produk['satuan'],
                    'index.php?page=produk&action=edit&id=' . $produk['id']
                ]);
            }
        }
        
        // Buat notifikasi untuk stok habis
        foreach ($habis as $produk) {
            $cekTidak = $db->prepare("SELECT id FROM notifikasi WHERE judul LIKE ? AND is_read = 0");
            $cekTidak->execute(['%' . $produk['nama_barang'] . '%']);
            
            if (!$cekTidak->fetch()) {
                $stmt = $db->prepare("INSERT INTO notifikasi (judul, pesan, tipe, url) VALUES (?, ?, 'danger', ?)");
                $stmt->execute([
                    'Stok Habis: ' . $produk['nama_barang'],
                    $produk['nama_barang'] . ' sudah habis. Segera lakukan pengadaan.',
                    'index.php?page=produk&action=edit&id=' . $produk['id']
                ]);
            }
        }
    } catch (Exception $e) {
        // Silent fail
    }
}

