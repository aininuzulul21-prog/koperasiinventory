<?php
/**
 * Halaman Login
 * Sistem Inventori Barang Koperasi
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $result = loginUser($username, $password, $remember);
        
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after'] ?? BASE_URL . '/index.php';
            unset($_SESSION['redirect_after']);
            redirect($redirect);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(APP_NAME) ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #1e3a5f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-logo .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
        }
        
        .login-logo .icon i {
            font-size: 36px;
            color: white;
        }
        
        .login-logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .login-logo p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .form-floating {
            margin-bottom: 16px;
        }
        
        .form-floating input {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 12px;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-floating input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-floating label {
            padding: 12px;
            color: #94a3b8;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            z-index: 10;
            padding: 4px 8px;
        }
        
        .password-toggle:hover {
            color: #2563eb;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .form-check {
            margin: 16px 0;
        }
        
        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 0.9rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 24px;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        .demo-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 20px;
        }
        
        .demo-info p {
            margin-bottom: 4px;
            font-size: 0.85rem;
            color: #0369a1;
        }
        
        .demo-info code {
            background: #e0f2fe;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="icon">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <h1>Inventory Koperasi</h1>
                <p>Sistem Manajemen Barang Koperasi</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="off">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required autofocus
                           value="<?= e($_POST['username'] ?? '') ?>">
                    <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                </div>
                
                <div class="form-floating position-relative">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword()" tabindex="-1">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-right-to-bracket me-2"></i>Masuk
                </button>
            </form>
            
            <div class="demo-info">
                <p><strong>Demo Akun:</strong></p>
                <p><i class="fas fa-user-shield me-1"></i> Admin: <code>admin</code> / <code>password</code></p>
                <p><i class="fas fa-user me-1"></i> Kasir: <code>kasir</code> / <code>password</code></p>
            </div>
            
            <div class="login-footer">
                &copy; <?= date('Y') ?> Sistem Inventori Barang Koperasi v<?= APP_VERSION ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

