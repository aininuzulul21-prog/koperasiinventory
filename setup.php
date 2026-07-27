<?php
/**
 * Setup / Installer
 * Sistem Inventori Barang Koperasi
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konstanta lokal biar tidak ketergantungan file lain
define('APP_NAME', 'Sistem Inventori Barang Koperasi');
define('APP_VERSION', '1.0.0');

// Check if already installed
$configPath = __DIR__ . '/config/database.php';
$installed = false;

// Jika sudah ada database, cek koneksi
if (file_exists($configPath)) {
    try {
        require_once $configPath;
        $db = getConnection();
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $installed = true;
    } catch (Exception $e) {
        $installed = false;
    }
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $host = trim($_POST['host'] ?? 'localhost');
    $user = trim($_POST['user'] ?? 'root');
    $pass = $_POST['password'] ?? '';
    $dbname = trim($_POST['dbname'] ?? 'koperasi_inventory');
    
    try {
        // Koneksi tanpa database dulu
        $pdo = new PDO("mysql:host={$host};port=3306;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Buat database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbname}`");
        
        // Jalankan schema
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema) {
            $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
            $schema = preg_replace('/USE.*?;/i', '', $schema);
            
            $statements = explode(';', $schema);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
        }
        
        // Update config file
        $configContent = "<?php
define('DB_HOST', '{$host}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass}');
define('DB_NAME', '{$dbname}');
define('DB_PORT', '3306');

function getConnection() {
    static \$conn = null;
    if (\$conn === null) {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";dbname=\" . DB_NAME . \";charset=utf8mb4\";
            \$conn = new PDO(\$dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException \$e) {
            die('Koneksi database gagal: ' . \$e->getMessage());
        }
    }
    return \$conn;
}

function getDB() { return getConnection(); }
";
        
        file_put_contents($configPath, $configContent);
        
        $success = 'Instalasi berhasil! Database dan tabel telah dibuat.';
        $installed = true;
        
    } catch (PDOException $e) {
        $error = 'Gagal: ' . $e->getMessage();
    } catch (Exception $e) {
        $error = 'Gagal: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .setup-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            margin: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .setup-card h1 { font-size: 1.5rem; font-weight: 700; text-align: center; margin-bottom: 8px; }
        .setup-card .subtitle { text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 32px; }
        .logo-icon {
            width: 80px; height: 80px; background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 36px; color: white; box-shadow: 0 8px 24px rgba(37,99,235,0.3);
        }
        .form-label { font-weight: 600; font-size: 0.85rem; }
        .btn-install {
            width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none; border-radius: 10px; color: white; font-weight: 600; font-size: 1rem;
        }
        .btn-install:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,0.4); }
        .success-page { text-align: center; }
        .success-page .icon { font-size: 64px; color: #22c55e; margin-bottom: 16px; }
        .success-page h2 { color: #166534; }
        .info-card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 16px; text-align: left; margin: 16px 0; }
        .info-card code { background: #e0f2fe; padding: 2px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="logo-icon"><i class="fas fa-boxes-stacked" style="font-size:36px"></i></div>
        
        <?php if ($installed && $success): ?>
        <div class="success-page">
            <div class="icon">&#10003;</div>
            <h2>Instalasi Berhasil! &#127881;</h2>
            <p class="text-muted">Sistem Inventori Barang Koperasi siap digunakan.</p>
            <div class="info-card">
                <p class="mb-2"><strong>Akun Default:</strong></p>
                <p class="mb-1">Admin: <code>admin</code> / <code>password</code></p>
                <p>Kasir: <code>kasir</code> / <code>password</code></p>
            </div>
            <div class="alert alert-warning">
                <strong>Penting!</strong> Hapus file <code>setup.php</code> setelah instalasi untuk keamanan.
            </div>
            <a href="login.php" class="btn btn-primary btn-lg mt-3">Login ke Aplikasi</a>
        </div>
        
        <?php elseif ($installed): ?>
        <h1 style="text-align:center"><?= APP_NAME ?></h1>
        <p class="subtitle">Aplikasi sudah terinstal. Silakan login.</p>
        <div class="alert alert-info">Database sudah terkonfigurasi.</div>
        <div style="text-align:center"><a href="login.php" class="btn btn-primary btn-lg">Login</a></div>
        
        <?php else: ?>
        <h1 style="text-align:center">Instalasi Aplikasi</h1>
        <p class="subtitle"><?= APP_NAME ?> v<?= APP_VERSION ?></p>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="install" value="1">
            <div class="mb-3">
                <label class="form-label">Host Database</label>
                <input type="text" class="form-control" name="host" value="localhost" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username Database</label>
                <input type="text" class="form-control" name="user" value="root" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Database</label>
                <input type="password" class="form-control" name="password" placeholder="Kosongkan jika root tanpa password">
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Database</label>
                <input type="text" class="form-control" name="dbname" value="koperasi_inventory" required>
            </div>
            <div class="alert alert-info">
                Pastikan MySQL server sudah berjalan (XAMPP/Laragon).
            </div>
            <button type="submit" class="btn-install">Install Sekarang</button>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- Font Awesome via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" defer></script>
</body>
</html>
