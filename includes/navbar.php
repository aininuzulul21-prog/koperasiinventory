<?php
/**
 * Navbar Template
 * Sistem Inventori Barang Koperasi
 */

$unreadNotif = getNotifikasiUnread();
?>
<!-- Navbar -->
<nav class="navbar navbar-top">
    <div class="navbar-left">
        <button class="btn btn-icon" id="sidebarToggleBtn">
            <i class="fas fa-bars"></i>
        </button>
        <span class="navbar-title">
            <?php 
            $page = $_GET['page'] ?? 'dashboard';
            $titles = [
                'dashboard' => 'Dashboard',
                'produk' => 'Data Produk',
                'kategori' => 'Kategori',
                'supplier' => 'Supplier',
                'user' => 'Data User',
                'barang-masuk' => 'Barang Masuk',
                'barang-keluar' => 'Barang Keluar',
                'transaksi' => 'Transaksi Penjualan',
                'riwayat-transaksi' => 'Riwayat Transaksi',
                'laporan' => 'Laporan',
                'pengaturan' => 'Pengaturan',
                'backup' => 'Backup & Restore',
            ];
            echo e($titles[$page] ?? 'Dashboard');
            ?>
        </span>
    </div>
    
    <div class="navbar-right">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn btn-icon position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotif > 0): ?>
                <span class="badge-notif"><?= $unreadNotif > 9 ? '9+' : $unreadNotif ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end dropdown-notif">
                <div class="dropdown-header">
                    <h6 class="mb-0">Notifikasi</h6>
                    <button class="btn btn-sm btn-link" onclick="markAllNotifRead()">Tandai dibaca</button>
                </div>
                <div class="dropdown-body" id="notifList">
                    <!-- Load via AJAX -->
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                </div>
                <div class="dropdown-footer text-center">
                    <a href="<?= BASE_URL ?>/index.php?page=notifikasi" class="btn btn-sm btn-link">Lihat Semua</a>
                </div>
            </div>
        </div>
        
        <!-- Dark Mode Toggle -->
        <button class="btn btn-icon" onclick="toggleDarkMode()" id="darkModeToggle">
            <i class="fas fa-moon"></i>
        </button>
        
        <!-- Fullscreen -->
        <button class="btn btn-icon d-none d-md-inline-flex" onclick="toggleFullscreen()">
            <i class="fas fa-expand"></i>
        </button>
        
        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar-sm">
                    <i class="fas fa-user"></i>
                </div>
                <span class="user-name-sm d-none d-md-inline">
                    <?= e($_SESSION['user_nama'] ?? 'User') ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/index.php?page=profile">
                        <i class="fas fa-user"></i> Profil
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">
                        <i class="fas fa-right-from-bracket text-danger"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

