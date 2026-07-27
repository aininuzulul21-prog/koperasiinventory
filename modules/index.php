<?php
/**
 * Module Barang Masuk
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
                $harga_beli = str_replace(['.', ','], ['', '.'], $_POST['harga_beli'] ?? 0);
                $id_supplier = $_POST['id_supplier'] ?: null;
                $tanggal = $_POST['tanggal'];
                $keterangan = $_POST['keterangan'] ?? '';
                
                $db->beginTransaction();
                try {
                    // Insert barang masuk
                    $stmt = $db->prepare("INSERT INTO barang_masuk (id_produk, id_supplier, qty, harga_beli, tanggal, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id_produk, $id_supplier, $qty, $harga_beli, $tanggal, $keterangan, $_SESSION['user_id']]);
                    
                    // Update stok
                    $stmt = $db->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
                    $stmt->execute([$qty, $id_produk]);
                    
                    // Log
                    $stmt = $db->prepare("SELECT nama_barang FROM produk WHERE id = ?");
                    $stmt->execute([$id_produk]);
                    $nama = $stmt->fetch()['nama_barang'];
                    logAktivitas($_SESSION['user_id'], 'tambah', 'Barang Masuk', "Barang masuk: {$nama} ({$qty})");
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Barang masuk berhasil dicatat. Stok bertambah.']);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
                
            case 'get_produk':
                $barcode = $_POST['barcode'] ?? '';
                $stmt = $db->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.barcode = ?");
                $stmt->execute([$barcode]);
                $produk = $stmt->fetch();
                
                // Get latest purchase price
                $latest = $db->prepare("SELECT harga_beli FROM barang_masuk WHERE id_produk = ? ORDER BY created_at DESC LIMIT 1");
                $latest->execute([$produk['id'] ?? 0]);
                $latestPrice = $latest->fetch();
                
                echo json_encode([
                    'success' => true, 
                    'data' => $produk,
                    'latest_harga_beli' => $latestPrice['harga_beli'] ?? $produk['harga_beli'] ?? 0
                ]);
                break;
        }
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$supplierList = $db->query("SELECT id, nama_supplier FROM supplier WHERE is_active = 1 ORDER BY nama_supplier")->fetchAll();
$riwayat = $db->query("
    SELECT bm.*, p.nama_barang, p.barcode, s.nama_supplier, u.nama as user_nama
    FROM barang_masuk bm
    JOIN produk p ON p.id = bm.id_produk
    LEFT JOIN supplier s ON s.id = bm.id_supplier
    LEFT JOIN users u ON u.id = bm.created_by
    ORDER BY bm.created_at DESC
    LIMIT 50
")->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Barang Masuk</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-arrow-right-to-bracket me-2 text-success"></i>Barang Masuk</h4>
</div>

<div class="row g-3">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle me-2 text-success"></i>Input Barang Masuk
            </div>
            <div class="card-body">
                <!-- Barcode Scanner -->
                <div class="mb-3">
                    <label class="form-label">Scan Barcode</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="barcodeScan" placeholder="Scan atau ketik barcode...">
                        <button class="btn btn-outline-primary" onclick="scanBarcode()"><i class="fas fa-camera"></i></button>
                    </div>
                </div>
                
                <div id="produkTerpilih" class="d-none mb-3 p-3 bg-light rounded">
                    <h6 class="mb-2"><i class="fas fa-box me-1"></i> Produk Terpilih:</h6>
                    <p class="mb-1"><strong id="pNama">-</strong></p>
                    <p class="mb-0 text-muted small">Barcode: <span id="pBarcode">-</span> | Stok: <span id="pStok">0</span></p>
                </div>
                
                <form id="formBarangMasuk">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="act" value="create">
                    <input type="hidden" name="id_produk" id="idProduk" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="id_supplier">
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach ($supplierList as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['nama_supplier']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="qty" id="qtyInput" min="1" value="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Harga Beli</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" name="harga_beli" id="hargaBeliInput" oninput="formatInputRupiah(this)">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-save"></i> Simpan Barang Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Riwayat -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Riwayat Barang Masuk
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabelBarangMasuk">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Barcode</th>
                                <th>Produk</th>
                                <th>Supplier</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?= formatTanggal($r['tanggal'], 'd/m/Y') ?></td>
                                <td><code><?= e($r['barcode']) ?></code></td>
                                <td><strong><?= e($r['nama_barang']) ?></strong></td>
                                <td><?= e($r['nama_supplier'] ?? '-') ?></td>
                                <td class="fw-bold text-success">+<?= number_format($r['qty']) ?></td>
                                <td><?= formatRupiah($r['harga_beli']) ?></td>
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

<!-- Scanner Modal -->
<div class="modal fade" id="modalScanBM" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title">Scan Barcode</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-2">
                <div id="scannerBM" style="width:100%;min-height:200px"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelBarangMasuk').DataTable({ order: [[0, 'desc']] });
    
    // Cari produk by barcode on input
    $('#barcodeScan').on('keypress', function(e) {
        if (e.key === 'Enter') {
            cariProdukBM($(this).val());
        }
    });
    
    // Form submit
    $('#formBarangMasuk').on('submit', function(e) {
        e.preventDefault();
        if ($('#idProduk').val() == 0) {
            showToast('Scan barcode produk terlebih dahulu', 'error');
            return;
        }
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    resetFormBM();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(resp.message, 'error');
                }
            }
        });
    });
});

function cariProdukBM(barcode) {
    if (!barcode) return;
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { ajax: 1, act: 'get_produk', barcode: barcode, csrf_token: CSRF_TOKEN },
        success: function(resp) {
            if (resp.success && resp.data) {
                const p = resp.data;
                $('#idProduk').val(p.id);
                $('#pNama').text(p.nama_barang);
                $('#pBarcode').text(p.barcode);
                $('#pStok').text(p.stok + ' ' + p.satuan);
                $('#produkTerpilih').removeClass('d-none');
                $('#hargaBeliInput').val(formatNumber(parseInt(resp.latest_harga_beli) || 0));
                showToast('Produk: ' + p.nama_barang, 'success');
            } else {
                showToast('Produk tidak ditemukan. Silakan tambah produk terlebih dahulu.', 'warning');
                $('#produkTerpilih').addClass('d-none');
                $('#idProduk').val(0);
            }
        }
    });
}

function scanBarcode() {
    $('#modalScanBM').modal('show');
    setTimeout(() => {
        const scanner = new Html5Qrcode("scannerBM");
        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 150 } },
            function(decodedText) {
                $('#barcodeScan').val(decodedText);
                cariProdukBM(decodedText);
                scanner.stop().catch(() => {});
                $('#modalScanBM').modal('hide');
            },
            function() {}
        );
    }, 500);
    
    $('#modalScanBM').on('hidden.bs.modal', function() {
        // Cleanup handled
    });
}

function resetFormBM() {
    $('#idProduk').val(0);
    $('#produkTerpilih').addClass('d-none');
    $('#barcodeScan').val('');
    $('#qtyInput').val(1);
    $('#hargaBeliInput').val('');
}

function formatInputRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) { input.value = formatNumber(parseInt(value)); }
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>

