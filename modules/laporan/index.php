<?php
/**
 * Module Laporan
 * Sistem Inventori Barang Koperasi
 */

requireLogin();
requireRole('admin');

$db = getDB();
$type = $_GET['type'] ?? 'stok';

// Handle export
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    $type = $_GET['type'] ?? 'stok';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_' . $type . '_' . date('Ymd') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($type) {
        case 'stok':
            fputcsv($output, ['No', 'Barcode', 'Kode', 'Nama Barang', 'Kategori', 'Stok', 'Min Stok', 'Status', 'Harga Beli', 'Harga Jual']);
            $data = $db->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.is_active = 1 ORDER BY p.nama_barang")->fetchAll();
            $no = 1;
            foreach ($data as $d) {
                $status = $d['stok'] <= 0 ? 'Habis' : ($d['stok'] <= $d['stok_minimum'] ? 'Minim' : 'Aman');
                fputcsv($output, [$no++, $d['barcode'], $d['kode_barang'], $d['nama_barang'], $d['nama_kategori'], $d['stok'], $d['stok_minimum'], $status, $d['harga_beli'], $d['harga_jual']]);
            }
            break;
            
        case 'penjualan':
            fputcsv($output, ['No', 'No Transaksi', 'Tanggal', 'Total Item', 'Total Harga', 'Diskon', 'Grand Total', 'Bayar', 'Kembalian', 'Metode', 'Kasir']);
            $data = $db->query("SELECT t.*, u.nama as kasir FROM transaksi t LEFT JOIN users u ON u.id = t.id_kasir WHERE t.status = 'selesai' ORDER BY t.created_at DESC")->fetchAll();
            $no = 1;
            foreach ($data as $d) {
                fputcsv($output, [$no++, $d['no_transaksi'], $d['created_at'], $d['total_item'], $d['total_harga'], $d['diskon'], $d['grand_total'], $d['bayar'], $d['kembalian'], $d['metode_bayar'], $d['kasir']]);
            }
            break;
            
        case 'barang-masuk':
            fputcsv($output, ['No', 'Tanggal', 'Barcode', 'Produk', 'Supplier', 'Qty', 'Harga Beli', 'User']);
            $data = $db->query("SELECT bm.*, p.nama_barang, p.barcode, s.nama_supplier, u.nama as user_nama FROM barang_masuk bm JOIN produk p ON p.id = bm.id_produk LEFT JOIN supplier s ON s.id = bm.id_supplier LEFT JOIN users u ON u.id = bm.created_by ORDER BY bm.created_at DESC")->fetchAll();
            $no = 1;
            foreach ($data as $d) {
                fputcsv($output, [$no++, $d['tanggal'], $d['barcode'], $d['nama_barang'], $d['nama_supplier'], $d['qty'], $d['harga_beli'], $d['user_nama']]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

$laporanTypes = [
    'stok' => ['title' => 'Laporan Stok', 'icon' => 'fa-warehouse'],
    'penjualan' => ['title' => 'Laporan Penjualan', 'icon' => 'fa-chart-line'],
    'barang-masuk' => ['title' => 'Laporan Barang Masuk', 'icon' => 'fa-arrow-right-to-bracket'],
    'barang-keluar' => ['title' => 'Laporan Barang Keluar', 'icon' => 'fa-arrow-right-from-bracket'],
    'produk-terlaris' => ['title' => 'Produk Terlaris', 'icon' => 'fa-fire'],
    'stok-minimum' => ['title' => 'Stok Minimum', 'icon' => 'fa-exclamation-triangle'],
    'nilai-persediaan' => ['title' => 'Nilai Persediaan', 'icon' => 'fa-calculator'],
];
$current = $laporanTypes[$type] ?? $laporanTypes['stok'];
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Laporan</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas <?= $current['icon'] ?> me-2 text-primary"></i><?= $current['title'] ?></h4>
    <div class="d-flex gap-2">
        <a href="index.php?page=laporan&type=<?= $type ?>&export=csv" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export CSV
        </a>
        <button class="btn btn-danger btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Tab Navigation -->
<ul class="nav nav-pills mb-3">
    <?php foreach ($laporanTypes as $key => $lt): ?>
    <li class="nav-item">
        <a class="nav-link <?= $type == $key ? 'active' : '' ?>" href="index.php?page=laporan&type=<?= $key ?>">
            <i class="fas <?= $lt['icon'] ?> me-1"></i><?= $lt['title'] ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <?php if ($type == 'stok'): 
                $data = $db->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.is_active = 1 ORDER BY p.nama_barang")->fetchAll();
            ?>
            <table class="table table-hover" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barcode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Min Stok</th>
                        <th>Status</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data as $d): 
                        $status = $d['stok'] <= 0 ? 'Habis' : ($d['stok'] <= $d['stok_minimum'] ? 'Minim' : 'Aman');
                        $badge = $d['stok'] <= 0 ? 'badge-stok-habis' : ($d['stok'] <= $d['stok_minimum'] ? 'badge-stok-minimum' : 'badge-stok-aman');
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><code><?= e($d['barcode']) ?></code></td>
                        <td><strong><?= e($d['nama_barang']) ?></strong></td>
                        <td><?= e($d['nama_kategori'] ?? '-') ?></td>
                        <td class="fw-bold text-center"><?= number_format($d['stok']) ?></td>
                        <td class="text-center"><?= number_format($d['stok_minimum']) ?></td>
                        <td><span class="<?= $badge ?>"><?= $status ?></span></td>
                        <td><?= formatRupiah($d['harga_beli']) ?></td>
                        <td><?= formatRupiah($d['harga_jual']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4">Total Stok</td>
                        <td class="text-center"><?= number_format(array_sum(array_column($data, 'stok'))) ?></td>
                        <td colspan="2"></td>
                        <td><?= formatRupiah(array_sum(array_column($data, 'harga_beli'))) ?></td>
                        <td><?= formatRupiah(array_sum(array_column($data, 'harga_jual'))) ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <?php elseif ($type == 'penjualan'): 
                $data = $db->query("SELECT t.*, u.nama as kasir FROM transaksi t LEFT JOIN users u ON u.id = t.id_kasir WHERE t.status = 'selesai' ORDER BY t.created_at DESC")->fetchAll();
            ?>
            <table class="table table-hover" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Transaksi</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Total</th>
                        <th>Diskon</th>
                        <th>Grand Total</th>
                        <th>Metode</th>
                        <th>Kasir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data as $d): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><code><?= e($d['no_transaksi']) ?></code></td>
                        <td><?= formatTanggal($d['created_at'], 'd/m/Y H:i') ?></td>
                        <td class="text-center"><?= $d['total_item'] ?></td>
                        <td><?= formatRupiah($d['total_harga']) ?></td>
                        <td><?= formatRupiah($d['diskon']) ?></td>
                        <td class="fw-bold"><?= formatRupiah($d['grand_total']) ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($d['metode_bayar']) ?></span></td>
                        <td><?= e($d['kasir'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4">Total</td>
                        <td><?= formatRupiah(array_sum(array_column($data, 'total_harga'))) ?></td>
                        <td><?= formatRupiah(array_sum(array_column($data, 'diskon'))) ?></td>
                        <td><?= formatRupiah(array_sum(array_column($data, 'grand_total'))) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            
            <?php elseif ($type == 'barang-masuk'): 
                $data = $db->query("SELECT bm.*, p.nama_barang, p.barcode, s.nama_supplier, u.nama as user_nama FROM barang_masuk bm JOIN produk p ON p.id = bm.id_produk LEFT JOIN supplier s ON s.id = bm.id_supplier LEFT JOIN users u ON u.id = bm.created_by ORDER BY bm.created_at DESC")->fetchAll();
            ?>
            <table class="table table-hover" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Barcode</th>
                        <th>Produk</th>
                        <th>Supplier</th>
                        <th>Qty</th>
                        <th>Harga Beli</th>
                        <th>Total</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data as $d): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= formatTanggal($d['tanggal'], 'd/m/Y') ?></td>
                        <td><code><?= e($d['barcode']) ?></code></td>
                        <td><strong><?= e($d['nama_barang']) ?></strong></td>
                        <td><?= e($d['nama_supplier'] ?? '-') ?></td>
                        <td class="fw-bold text-success text-center">+<?= number_format($d['qty']) ?></td>
                        <td><?= formatRupiah($d['harga_beli']) ?></td>
                        <td><?= formatRupiah($d['qty'] * $d['harga_beli']) ?></td>
                        <td><small><?= e($d['user_nama'] ?? '-') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php elseif ($type == 'stok-minimum'): 
                $data = $db->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.stok <= p.stok_minimum AND p.is_active = 1 ORDER BY p.stok ASC")->fetchAll();
            ?>
            <table class="table table-hover" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barcode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Min Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data as $d): 
                        $badge = $d['stok'] <= 0 ? 'badge-stok-habis' : 'badge-stok-minimum';
                        $status = $d['stok'] <= 0 ? 'Habis' : 'Minim';
                    ?>
                    <tr class="bg-warning bg-opacity-10">
                        <td><?= $no++ ?></td>
                        <td><code><?= e($d['barcode']) ?></code></td>
                        <td><strong><?= e($d['nama_barang']) ?></strong></td>
                        <td><?= e($d['nama_kategori'] ?? '-') ?></td>
                        <td class="fw-bold text-center"><?= number_format($d['stok']) ?></td>
                        <td class="text-center"><?= number_format($d['stok_minimum']) ?></td>
                        <td><span class="<?= $badge ?>"><?= $status ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php elseif ($type == 'nilai-persediaan'): 
                $data = $db->query("SELECT p.*, k.nama_kategori, (p.stok * p.harga_beli) as nilai_modal, (p.stok * p.harga_jual) as nilai_jual FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.is_active = 1 ORDER BY p.nama_barang")->fetchAll();
                $totalModal = array_sum(array_column($data, 'nilai_modal'));
                $totalJual = array_sum(array_column($data, 'nilai_jual'));
            ?>
            <table class="table table-hover" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Nilai Modal</th>
                        <th>Nilai Jual</th>
                        <th>Potensi Laba</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data as $d): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($d['nama_barang']) ?></strong></td>
                        <td class="text-center"><?= number_format($d['stok']) ?></td>
                        <td><?= formatRupiah($d['harga_beli']) ?></td>
                        <td><?= formatRupiah($d['harga_jual']) ?></td>
                        <td><?= formatRupiah($d['nilai_modal']) ?></td>
                        <td><?= formatRupiah($d['nilai_jual']) ?></td>
                        <td class="fw-bold text-success"><?= formatRupiah($d['nilai_jual'] - $d['nilai_modal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5">Total</td>
                        <td><?= formatRupiah($totalModal) ?></td>
                        <td><?= formatRupiah($totalJual) ?></td>
                        <td class="text-success"><?= formatRupiah($totalJual - $totalModal) ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <?php else: ?>
            <p class="text-muted text-center p-4">Module laporan sedang dalam pengembangan</p>
            <?php endif; ?>
        </div>
    </div>
</div>
