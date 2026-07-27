<?php
/**
 * Logout
 * Sistem Inventori Barang Koperasi
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
redirect(BASE_URL . '/login.php');

