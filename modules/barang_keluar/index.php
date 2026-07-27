<?php
/**
 * Module Barang Keluar
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) throw new Exception('Token tidak valid');
        
        $act = $_POST['act'] ?? '';
        
        switch ($act) {
            case 'create':
                $id_produk = $_POST['id_produk'];
                $qty = (int)$_POST['qty'];
                $tanggal = $_POST['tanggal'];
                $tujuan = $_POST['tujuan'] ?? '';
                $keterangan = $_POST['keterangan'] ?? '';
                
                // Cek stok
                $cek = $db->prepare("SELECT stok, nama_barang FROM produk WHERE id = ?");
                $cek->execute([$id_produk]);
                $produk = $cek->fetch();
                
                if (!$produk) throw new Exception('Produk tidak ditemukan');
                if ($produk['stok'] < $qty) throw new Exception('Stok tidak mencukupi. Stok saat ini: ' . $produk['stok']);
                
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("INSERT INTO barang_keluar (id_produk, qty, tujuan, keterangan, tanggal, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id_produk, $qty, $tujuan, $keterangan, $tanggal, $_SESSION['user_id']]);
                    
                    $stmt = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
                    $stmt->execute([$qty, $id_produk]);
                    
                    logAktivitas($_SESSION['user_id'], 'tambah', 'Barang Keluar', "Barang keluar: {$produk['nama_barang']} ({$qty})");
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Barang keluar berhasil dicatat. Stok berkurang.']);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
                
            case 'get_produk':
                $barcode = $_POST['barcode'] ?? '';
                $stmt = $db->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.barcode = ?");
                $stmt->execute([$barcode]);
                echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
                break;
        }
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$riwayat = $db->query("
    SELECT bk.*, p.nama_barang, p.barcode, u.nama as user_nama
    FROM barang_keluar bk
    JOIN produk p ON p.id = bk.id_produk
    LEFT JOIN users u ON u.id = bk.created_by
    ORDER BY bk.created_at DESC
    LIMIT 50
")->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Barang Keluar</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-arrow-right-from-bracket me-2 text-danger"></i>Barang Keluar</h4>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-minus-circle me-2 text-danger"></i>Input Barang Keluar</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Scan Barcode</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="barcodeScan" placeholder="Scan atau ketik barcode...">
                        <button class="btn btn-outline-primary" onclick="scanBarcode()"><i class="fas fa-camera"></i></button>
                    </div>
                </div>
                
                <div id="produkTerpilih" class="d-none mb-3 p-3 bg-light rounded">
                    <h6 class="mb-2"><i class="fas fa-box me-1"></i> Produk:</h6>
                    <p class="mb-1"><strong id="pNama">-</strong></p>
                    <p class="mb-0 text-muted small">Barcode: <span id="pBarcode">-</span> | Stok: <span id="pStok">0</span></p>
                </div>
                
                <form id="formBarangKeluar">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="act" value="create">
                    <input type="hidden" name="id_produk" id="idProduk" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="qty" id="qtyInput" min="1" value="1" required>
                        <small class="text-muted">Stok tersedia: <span id="sisaStok">0</span></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tujuan</label>
                        <input type="text" class="form-control" name="tujuan" placeholder="Contoh: Unit A, Gudang 2, dll">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-save"></i> Simpan Barang Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-history me-2"></i>Riwayat Barang Keluar</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabelBarangKeluar">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Barcode</th>
                                <th>Produk</th>
                                <th>Tujuan</th>
                                <th>Qty</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?= formatTanggal($r['tanggal'], 'd/m/Y') ?></td>
                                <td><code><?= e($r['barcode']) ?></code></td>
                                <td><strong><?= e($r['nama_barang']) ?></strong></td>
                                <td><?= e($r['tujuan'] ?? '-') ?></td>
                                <td class="fw-bold text-danger">-<?= number_format($r['qty']) ?></td>
                                <td><small><?= e($r['user_nama'] ?? '-') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalScanBK" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">Scan Barcode</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-2"><div id="scannerBK" style="width:100%;min-height:200px"></div></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelBarangKeluar').DataTable({ order: [[0, 'desc']] });
    
    $('#barcodeScan').on('keypress', function(e) {
        if (e.key === 'Enter') cariProduk($(this).val());
    });
    
    $('#formBarangKeluar').on('submit', function(e) {
        e.preventDefault();
        if ($('#idProduk').val() == 0) { showToast('Scan barcode dulu', 'error'); return; }
        const qty = parseInt($('#qtyInput').val());
        const stok = parseInt($('#sisaStok').text());
        if (qty > stok) { showToast('Stok tidak mencukupi! Tersedia: ' + stok, 'error'); return; }
        
        $.ajax({
            url: window.location.href, method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) { showToast(resp.message, 'success'); setTimeout(() => location.reload(), 1000); }
                else { showToast(resp.message, 'error'); }
            }
        });
    });
});

function cariProduk(barcode) {
    if (!barcode) return;
    $.ajax({
        url: window.location.href, method: 'POST',
        data: { ajax: 1, act: 'get_produk', barcode: barcode, csrf_token: CSRF_TOKEN },
        success: function(resp) {
            if (resp.success && resp.data) {
                const p = resp.data;
                $('#idProduk').val(p.id);
                $('#pNama').text(p.nama_barang);
                $('#pBarcode').text(p.barcode);
                $('#pStok').text(p.stok + ' ' + p.satuan);
                $('#sisaStok').text(p.stok);
                $('#produkTerpilih').removeClass('d-none');
                showToast('Produk: ' + p.nama_barang, 'success');
            } else {
                showToast('Produk tidak ditemukan', 'warning');
                $('#produkTerpilih').addClass('d-none');
                $('#idProduk').val(0);
            }
        }
    });
}

function scanBarcode() {
    $('#modalScanBK').modal('show');
    setTimeout(() => {
        const scanner = new Html5Qrcode("scannerBK");
        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 150 } },
            function(decodedText) {
                $('#barcodeScan').val(decodedText);
                cariProduk(decodedText);
                scanner.stop().catch(() => {});
                $('#modalScanBK').modal('hide');
            }, function() {}
        );
    }, 500);
}
</script>

