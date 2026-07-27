<?php
/**
 * Dashboard Module
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();

// Statistik
$totalProduk = $db->query("SELECT COUNT(*) as total FROM produk WHERE is_active = 1")->fetch()['total'];
$totalKategori = $db->query("SELECT COUNT(*) as total FROM kategori WHERE is_active = 1")->fetch()['total'];
$totalSupplier = $db->query("SELECT COUNT(*) as total FROM supplier WHERE is_active = 1")->fetch()['total'];
$totalStok = $db->query("SELECT SUM(stok) as total FROM produk WHERE is_active = 1")->fetch()['total'] ?? 0;
$hampirHabis = $db->query("SELECT COUNT(*) as total FROM produk WHERE stok <= stok_minimum AND stok > 0 AND is_active = 1")->fetch()['total'];
$habis = $db->query("SELECT COUNT(*) as total FROM produk WHERE stok = 0 AND is_active = 1")->fetch()['total'];

$transaksiHariIni = $db->query("SELECT COUNT(*) as total FROM transaksi WHERE DATE(created_at) = CURDATE() AND status = 'selesai'")->fetch()['total'];
$pendapatanHariIni = $db->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM transaksi WHERE DATE(created_at) = CURDATE() AND status = 'selesai'")->fetch()['total'];

// Aktivitas terbaru
$aktivitas = $db->query("
    SELECT la.*, u.nama as user_nama 
    FROM log_aktivitas la 
    LEFT JOIN users u ON u.id = la.id_user 
    ORDER BY la.created_at DESC 
    LIMIT 10
")->fetchAll();

// Produk terlaris
$produkTerlaris = $db->query("
    SELECT p.nama_barang, p.foto, SUM(dt.qty) as total_qty, SUM(dt.subtotal) as total_nominal
    FROM detail_transaksi dt
    JOIN produk p ON p.id = dt.id_produk
    JOIN transaksi t ON t.id = dt.id_transaksi
    WHERE t.status = 'selesai' AND MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE())
    GROUP BY dt.id_produk
    ORDER BY total_qty DESC
    LIMIT 5
")->fetchAll();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-home me-1"></i>Dashboard</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">
        <i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard
    </h4>
    <div>
        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
            <i class="fas fa-rotate"></i> Refresh
        </button>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card primary animate-fadeIn">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Produk</div>
                <div class="stat-value"><?= number_format($totalProduk) ?></div>
                <div class="stat-change text-success">
                    <i class="fas fa-arrow-up"></i> Aktif
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card success animate-fadeIn" style="animation-delay: 0.1s">
            <div class="stat-icon">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Kategori</div>
                <div class="stat-value"><?= number_format($totalKategori) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card info animate-fadeIn" style="animation-delay: 0.2s">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Supplier</div>
                <div class="stat-value"><?= number_format($totalSupplier) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card secondary animate-fadeIn" style="animation-delay: 0.3s">
            <div class="stat-icon">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Stok</div>
                <div class="stat-value"><?= number_format($totalStok) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card warning animate-fadeIn" style="animation-delay: 0.4s">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Hampir Habis</div>
                <div class="stat-value"><?= number_format($hampirHabis) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card danger animate-fadeIn" style="animation-delay: 0.5s">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Stok Habis</div>
                <div class="stat-value"><?= number_format($habis) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card info animate-fadeIn" style="animation-delay: 0.6s">
            <div class="stat-icon">
                <i class="fas fa-cash-register"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Transaksi Hari Ini</div>
                <div class="stat-value"><?= number_format($transaksiHariIni) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card success animate-fadeIn" style="animation-delay: 0.7s">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Pendapatan Hari Ini</div>
                <div class="stat-value"><?= formatRupiah($pendapatanHariIni) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2 text-primary"></i>Grafik Barang Masuk & Keluar (Bulan Ini)
            </div>
            <div class="card-body">
                <canvas id="chartStok" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line me-2 text-primary"></i>Grafik Penjualan (7 Hari Terakhir)
            </div>
            <div class="card-body">
                <canvas id="chartPenjualan" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Second Row: Aktivitas & Produk Terlaris -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock-rotate-left me-2 text-primary"></i>Aktivitas Terbaru
            </div>
            <div class="card-body">
                <?php if ($aktivitas): ?>
                <ul class="timeline mb-0">
                    <?php foreach ($aktivitas as $a): ?>
                    <li class="timeline-item">
                        <div class="timeline-icon bg-<?= $a['aksi'] == 'login' ? 'success' : ($a['aksi'] == 'logout' ? 'secondary' : 'primary') ?>">
                            <i class="fas fa-<?= $a['aksi'] == 'login' ? 'right-to-bracket' : ($a['aksi'] == 'logout' ? 'right-from-bracket' : 'circle') ?>"></i>
                        </div>
                        <div class="timeline-time"><?= formatTanggal($a['created_at'], 'd F Y H:i') ?></div>
                        <div class="timeline-text">
                            <strong><?= e($a['user_nama'] ?? 'System') ?></strong> 
                            <?= e($a['keterangan']) ?>
                            <span class="badge bg-light text-dark ms-1"><?= e($a['module']) ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted text-center mb-0">Belum ada aktivitas</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-fire me-2 text-primary"></i>Produk Terlaris Bulan Ini
            </div>
            <div class="card-body p-0">
                <?php if ($produkTerlaris): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Terjual</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produkTerlaris as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <strong><?= e($p['nama_barang']) ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= number_format($p['total_qty']) ?> pcs</span>
                                </td>
                                <td class="text-end fw-bold"><?= formatRupiah($p['total_nominal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-shopping-bag fa-2x mb-2"></i>
                    <p class="mb-0">Belum ada data penjualan bulan ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart Data -->
<script>
// Ambil data chart dari API
$.ajax({
    url: BASE_URL + '/api/chart_data.php',
    method: 'GET',
    dataType: 'json',
    success: function(response) {
        if (response.success) {
            // Chart Barang Masuk & Keluar
            const ctx1 = document.getElementById('chartStok').getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: response.stok.labels,
                    datasets: [
                        {
                            label: 'Barang Masuk',
                            data: response.stok.masuk,
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: '#22c55e',
                            borderWidth: 2,
                            borderRadius: 6
                        },
                        {
                            label: 'Barang Keluar',
                            data: response.stok.keluar,
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: '#ef4444',
                            borderWidth: 2,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Chart Penjualan
            const ctx2 = document.getElementById('chartPenjualan').getContext('2d');
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: response.penjualan.labels,
                    datasets: [
                        {
                            label: 'Penjualan',
                            data: response.penjualan.total,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            fill: true,
                            borderWidth: 3,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            tension: 0.4
                        },
                        {
                            label: 'Transaksi',
                            data: response.penjualan.count,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            borderWidth: 3,
                            pointBackgroundColor: '#22c55e',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>

