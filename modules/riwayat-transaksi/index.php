<?php
/**
 * Module Riwayat Transaksi
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();
$filter_tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

$transaksiList = $db->prepare("
    SELECT t.*, u.nama as nama_kasir
    FROM transaksi t
    LEFT JOIN users u ON u.id = t.id_kasir
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    ORDER BY t.created_at DESC
");
$transaksiList->execute([$filter_tgl_awal, $filter_tgl_akhir]);
$transaksiList = $transaksiList->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Riwayat Transaksi</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-clock-rotate-left me-2 text-primary"></i>Riwayat Transaksi</h4>
</div>

<div class="filter-section">
    <form class="row g-2 align-items-end" method="GET">
        <input type="hidden" name="page" value="riwayat-transaksi">
        <div class="col-md-3">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control form-control-sm" name="tgl_awal" value="<?= $filter_tgl_awal ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control form-control-sm" name="tgl_akhir" value="<?= $filter_tgl_akhir ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
        </div>
        <div class="col-md-2 d-grid">
            <a href="?page=laporan&type=penjualan&tgl_awal=<?= $filter_tgl_awal ?>&tgl_akhir=<?= $filter_tgl_akhir ?>&export=excel" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</a>
        </div>
        <div class="col-md-2 d-grid">
            <a href="?page=laporan&type=penjualan&tgl_awal=<?= $filter_tgl_awal ?>&tgl_akhir=<?= $filter_tgl_akhir ?>&export=pdf" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelRiwayat">
                <thead>
                    <tr>
                        <th>No Transaksi</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Total</th>
                        <th>Diskon</th>
                        <th>Grand Total</th>
                        <th>Bayar</th>
                        <th>Metode</th>
                        <th>Kasir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaksiList as $t): ?>
                    <tr>
                        <td><code><?= e($t['no_transaksi']) ?></code></td>
                        <td><?= formatTanggal($t['created_at'], 'd/m/Y H:i') ?></td>
                        <td class="text-center"><?= $t['total_item'] ?></td>
                        <td><?= formatRupiah($t['total_harga']) ?></td>
                        <td><?= formatRupiah($t['diskon']) ?></td>
                        <td class="fw-bold"><?= formatRupiah($t['grand_total']) ?></td>
                        <td><?= formatRupiah($t['bayar']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= ucfirst($t['metode_bayar']) ?></span></td>
                        <td><small><?= e($t['nama_kasir'] ?? '-') ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="detailTransaksi('<?= e($t['no_transaksi']) ?>')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="cetakStruk('<?= e($t['no_transaksi']) ?>')">
                                <i class="fas fa-print"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Detail Transaksi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody">Loading...</div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelRiwayat').DataTable({
        order: [[1, 'desc']],
        pageLength: 25
    });
});

function detailTransaksi(no) {
    $('#detailBody').html('<div class="text-center"><div class="spinner-border"></div></div>');
    $('#modalDetail').modal('show');
    
    $.ajax({
        url: '<?= BASE_URL ?>/api/get_detail_transaksi.php',
        method: 'POST',
        data: { no_transaksi: no, csrf_token: CSRF_TOKEN },
        success: function(resp) {
            if (resp.success) {
                let html = `
                    <p><strong>No. Transaksi:</strong> ${resp.transaksi.no_transaksi}</p>
                    <p><strong>Tanggal:</strong> ${resp.transaksi.created_at}</p>
                    <p><strong>Kasir:</strong> ${resp.transaksi.nama_kasir || '-'}</p>
                    <p><strong>Metode Bayar:</strong> ${resp.transaksi.metode_bayar.toUpperCase()}</p>
                    <hr>
                    <table class="table table-sm">
                        <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
                        <tbody>
                `;
                resp.detail.forEach(function(d) {
                    html += `<tr><td>${d.nama_barang}</td><td>${d.qty}</td><td>${formatRupiah(d.harga)}</td><td class="fw-bold">${formatRupiah(d.subtotal)}</td></tr>`;
                });
                html += `</tbody></table><hr>
                    <div class="d-flex justify-content-between"><strong>Total:</strong> ${formatRupiah(resp.transaksi.total_harga)}</div>
                    <div class="d-flex justify-content-between"><strong>Diskon:</strong> ${formatRupiah(resp.transaksi.diskon)}</div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-primary"><strong>Grand Total:</strong> ${formatRupiah(resp.transaksi.grand_total)}</div>
                    <div class="d-flex justify-content-between"><strong>Bayar:</strong> ${formatRupiah(resp.transaksi.bayar)}</div>
                    <div class="d-flex justify-content-between text-success"><strong>Kembalian:</strong> ${formatRupiah(resp.transaksi.kembalian)}</div>
                `;
                $('#detailBody').html(html);
            } else {
                $('#detailBody').html('<p class="text-danger">Gagal memuat detail</p>');
            }
        },
        error: function() {
            $('#detailBody').html('<p class="text-danger">Gagal memuat detail</p>');
        }
    });
}

function cetakStruk(no) {
    window.open(BASE_URL + '/index.php?page=cetak-struk&no=' + no, '_blank', 'width=400,height=600');
}
</script>

