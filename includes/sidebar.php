<?php
/**
 * Sidebar Template
 * Sistem Inventori Barang Koperasi
 * 
 * Hak Akses:
 * - Admin: Semua menu
 * - Kasir: Transaksi, Produk (lihat stok)
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$userRole = $_SESSION['user_role'] ?? '';
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/index.php" class="sidebar-brand">
            <i class="fas fa-boxes-stacked"></i>
            <span class="brand-text">Koperasi Inventory</span>
        </a>
        <button class="sidebar-toggle d-lg-none" id="sidebarCloseBtn">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <div class="avatar">
            <i class="fas fa-user-circle fa-2x"></i>
        </div>
        <div class="user-info">
            <span class="user-name"><?= e($_SESSION['user_nama'] ?? 'User') ?></span>
            <span class="user-role badge bg-<?= $userRole == 'admin' ? 'primary' : 'success' ?>">
                <?= ucfirst($userRole) ?>
            </span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <!-- Dashboard -->
            <li class="nav-item <?= $currentPage == 'dashboard' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="nav-link">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Master Data (Hanya Admin) -->
            <?php if ($userRole == 'admin'): ?>
            <li class="nav-divider">Master Data</li>
            
            <li class="nav-item <?= $currentPage == 'produk' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=produk" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Data Produk</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'kategori' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=kategori" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'supplier' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=supplier" class="nav-link">
                    <i class="fas fa-truck"></i>
                    <span>Supplier</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'user' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=user" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Data User</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Transaksi -->
            <li class="nav-divider">Transaksi</li>
            
            <li class="nav-item <?= $currentPage == 'barang-masuk' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=barang-masuk" class="nav-link">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    <span>Barang Masuk</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'barang-keluar' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=barang-keluar" class="nav-link">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span>Barang Keluar</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'transaksi' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=transaksi" class="nav-link">
                    <i class="fas fa-cash-register"></i>
                    <span>Transaksi Penjualan</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'riwayat-transaksi' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=riwayat-transaksi" class="nav-link">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>Riwayat Transaksi</span>
                </a>
            </li>
            
            <!-- Laporan (Hanya Admin) -->
            <?php if ($userRole == 'admin'): ?>
            <li class="nav-divider">Laporan</li>
            
            <li class="nav-item <?= $currentPage == 'laporan' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=laporan" class="nav-link">
                    <i class="fas fa-file-lines"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Pengaturan (Hanya Admin) -->
            <?php if ($userRole == 'admin'): ?>
            <li class="nav-divider">Pengaturan</li>
            
            <li class="nav-item <?= $currentPage == 'pengaturan' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=pengaturan" class="nav-link">
                    <i class="fas fa-gear"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            
            <li class="nav-item <?= $currentPage == 'backup' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/index.php?page=backup" class="nav-link">
                    <i class="fas fa-database"></i>
                    <span>Backup & Restore</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-logout">
            <i class="fas fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

