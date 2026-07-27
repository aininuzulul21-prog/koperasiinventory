<?php
/**
 * Module Data Produk
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();
$action = $_GET['action'] ?? 'list';

// Handle AJAX CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrf)) {
            throw new Exception('Token CSRF tidak valid');
        }
        
        $act = $_POST['act'] ?? '';
        
        switch ($act) {
            case 'create':
            case 'update':
                $id = $_POST['id'] ?? 0;
                $barcode = trim($_POST['barcode'] ?? '');
                $kode_barang = trim($_POST['kode_barang'] ?? '');
                $nama_barang = trim($_POST['nama_barang'] ?? '');
                $id_kategori = $_POST['id_kategori'] ?: null;
                $merk = trim($_POST['merk'] ?? '');
                $satuan = trim($_POST['satuan'] ?? 'Pcs');
                $harga_beli = str_replace(['.', ','], ['', '.'], $_POST['harga_beli'] ?? 0);
                $harga_jual = str_replace(['.', ','], ['', '.'], $_POST['harga_jual'] ?? 0);
                $stok = (int)($_POST['stok'] ?? 0);
                $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
                $lokasi_rak = trim($_POST['lokasi_rak'] ?? '');
                $deskripsi = trim($_POST['deskripsi'] ?? '');
                
                // Handle upload foto
                $foto = '';
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                    $foto = 'produk_' . time() . '_' . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_PATH . '/produk/' . $foto);
                }
                
                if ($act == 'create') {
                    if (empty($kode_barang)) {
                        $kode_barang = generateKodeBarang();
                    }
                    
                    $stmt = $db->prepare("INSERT INTO produk (barcode, kode_barang, nama_barang, id_kategori, merk, satuan, harga_beli, harga_jual, stok, stok_minimum, lokasi_rak, foto, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$barcode, $kode_barang, $nama_barang, $id_kategori, $merk, $satuan, $harga_beli, $harga_jual, $stok, $stok_minimum, $lokasi_rak, $foto, $deskripsi]);
                    $produkId = $db->lastInsertId();
                    
                    logAktivitas($_SESSION['user_id'], 'tambah', 'Produk', 'Menambah produk: ' . $nama_barang);
                    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan']);
                } else {
                    if (!$foto) {
                        $stmt = $db->prepare("UPDATE produk SET barcode=?, kode_barang=?, nama_barang=?, id_kategori=?, merk=?, satuan=?, harga_beli=?, harga_jual=?, stok=?, stok_minimum=?, lokasi_rak=?, deskripsi=? WHERE id=?");
                        $stmt->execute([$barcode, $kode_barang, $nama_barang, $id_kategori, $merk, $satuan, $harga_beli, $harga_jual, $stok, $stok_minimum, $lokasi_rak, $deskripsi, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE produk SET barcode=?, kode_barang=?, nama_barang=?, id_kategori=?, merk=?, satuan=?, harga_beli=?, harga_jual=?, stok=?, stok_minimum=?, lokasi_rak=?, foto=?, deskripsi=? WHERE id=?");
                        $stmt->execute([$barcode, $kode_barang, $nama_barang, $id_kategori, $merk, $satuan, $harga_beli, $harga_jual, $stok, $stok_minimum, $lokasi_rak, $foto, $deskripsi, $id]);
                    }
                    
                    logAktivitas($_SESSION['user_id'], 'edit', 'Produk', 'Mengedit produk: ' . $nama_barang);
                    echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate']);
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $db->prepare("DELETE FROM produk WHERE id = ?");
                $stmt->execute([$id]);
                logAktivitas($_SESSION['user_id'], 'hapus', 'Produk', 'Menghapus produk ID: ' . $id);
                echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus']);
                break;
                
            case 'get':
                $id = $_POST['id'] ?? 0;
                $stmt = $db->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.id = ?");
                $stmt->execute([$id]);
                $produk = $stmt->fetch();
                echo json_encode(['success' => true, 'data' => $produk]);
                break;
                
            case 'search_barcode':
                $barcode = trim($_POST['barcode'] ?? '');
                $stmt = $db->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.barcode = ?");
                $stmt->execute([$barcode]);
                $produk = $stmt->fetch();
                echo json_encode(['success' => true, 'data' => $produk]);
                break;
        }
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Tampilkan halaman
$kategoriList = $db->query("SELECT id, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori")->fetchAll();
$satuanList = ['Pcs', 'Unit', 'Kg', 'Gram', 'Liter', 'Ml', 'Meter', 'Box', 'Pak', 'Dus', 'Karton', 'Botol', 'Kaleng', 'Sachet', 'Rim', 'Lembar'];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Data Produk</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-box me-2 text-primary"></i>Data Produk</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm" onclick="openModal('import')">
            <i class="fas fa-file-excel"></i> Import
        </button>
        <a href="#" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="#" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <button class="btn btn-primary btn-sm" onclick="openModal('add')">
            <i class="fas fa-plus"></i> Tambah Produk
        </button>
    </div>
</div>

<!-- Filter & Search -->
<div class="filter-section">
    <form class="row g-2 align-items-end" id="filterForm">
        <div class="col-md-4">
            <label class="form-label">Cari</label>
            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Nama, barcode, atau kode barang...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kategori</label>
            <select class="form-select form-select-sm" id="filterKategori">
                <option value="">Semua</option>
                <?php foreach ($kategoriList as $k): ?>
                <option value="<?= $k['id'] ?>"><?= e($k['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status Stok</label>
            <select class="form-select form-select-sm" id="filterStok">
                <option value="">Semua</option>
                <option value="aman">Aman</option>
                <option value="minimum">Hampir Habis</option>
                <option value="habis">Habis</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-primary btn-sm" onclick="filterTable()">
                <i class="fas fa-search"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Tabel Produk -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelProduk">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Barcode</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $produkList = $db->query("
                        SELECT p.*, k.nama_kategori 
                        FROM produk p 
                        LEFT JOIN kategori k ON k.id = p.id_kategori 
                        WHERE p.is_active = 1 
                        ORDER BY p.nama_barang ASC
                    ")->fetchAll();
                    
                    $no = 1;
                    foreach ($produkList as $p):
                        $stokStatus = '';
                        $stokBadge = '';
                        if ($p['stok'] <= 0) {
                            $stokStatus = 'Habis';
                            $stokBadge = 'badge-stok-habis';
                        } elseif ($p['stok'] <= $p['stok_minimum']) {
                            $stokStatus = 'Hampir Habis';
                            $stokBadge = 'badge-stok-minimum';
                        } else {
                            $stokStatus = 'Aman';
                            $stokBadge = 'badge-stok-aman';
                        }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if ($p['foto']): ?>
                            <img src="<?= UPLOAD_URL ?>/produk/<?= $p['foto'] ?>" class="img-thumb-product" alt="<?= e($p['nama_barang']) ?>">
                            <?php else: ?>
                            <div class="img-thumb-product d-flex align-items-center justify-content-center bg-light">
                                <i class="fas fa-box text-muted"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($p['barcode']) ?></code></td>
                        <td><small><?= e($p['kode_barang']) ?></small></td>
                        <td>
                            <strong><?= e($p['nama_barang']) ?></strong>
                            <br><small class="text-muted"><?= e($p['merk']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= e($p['nama_kategori'] ?? '-') ?></span></td>
                        <td class="fw-bold"><?= formatRupiah($p['harga_jual']) ?></td>
                        <td class="text-center fw-bold"><?= number_format($p['stok']) ?> <small class="text-muted"><?= e($p['satuan']) ?></small></td>
                        <td><span class="<?= $stokBadge ?>"><?= $stokStatus ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-info" onclick="openModal('edit', <?= $p['id'] ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduk(<?= $p['id'] ?>)" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="cetakBarcode('<?= e($p['barcode']) ?>', '<?= e($p['nama_barang']) ?>')" title="Cetak Barcode">
                                    <i class="fas fa-barcode"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Produk -->
<div class="modal fade" id="modalProduk" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProdukTitle">
                    <i class="fas fa-box me-2 text-primary"></i><span id="formTitle">Tambah Produk</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProduk" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="act" id="formAct" value="create">
                <input type="hidden" name="id" id="produkId" value="0">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Barcode & Scanner -->
                        <div class="col-md-6">
                            <label class="form-label">Barcode <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="barcode" id="barcodeInput" required>
                                <button type="button" class="btn btn-outline-primary" onclick="openScanner()" title="Scan Barcode">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="generateBarcode()" title="Generate Barcode">
                                    <i class="fas fa-magic"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" class="form-control" name="kode_barang" id="kodeBarangInput" readonly>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" id="namaBarangInput" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Merk</label>
                            <input type="text" class="form-control" name="merk" id="merkInput">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="id_kategori">
                                <option value="">-- Pilih --</option>
                                <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= e($k['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Satuan</label>
                            <select class="form-select" name="satuan">
                                <?php foreach ($satuanList as $s): ?>
                                <option value="<?= $s ?>" <?= $s == 'Pcs' ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Stok Awal</label>
                            <input type="number" class="form-control" name="stok" id="stokInput" min="0" value="0">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Stok Minimum</label>
                            <input type="number" class="form-control" name="stok_minimum" min="0" value="5">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Harga Beli</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" name="harga_beli" id="hargaBeliInput" oninput="formatInputRupiah(this)">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" name="harga_jual" id="hargaJualInput" oninput="formatInputRupiah(this)" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Lokasi Rak</label>
                            <input type="text" class="form-control" name="lokasi_rak" placeholder="Contoh: A-01">
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Foto Produk</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG. Maks 2MB</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <span id="submitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Scanner -->
<div class="modal fade" id="modalScanner" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-camera me-2"></i>Scan Barcode</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <div id="scannerContainer" style="width:100%;min-height:200px"></div>
                <div id="scannerResult" class="mt-2 text-center d-none">
                    <div class="alert alert-success mb-0">
                        <strong>Barcode:</strong> <span id="scannedCode"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Inisialisasi DataTable
let tableProduk;

$(document).ready(function() {
    tableProduk = $('#tabelProduk').DataTable({
        columnDefs: [
            { orderable: false, targets: [1, 7, 9] }
        ],
        order: [[4, 'asc']]
    });
});

function filterTable() {
    const search = $('#searchInput').val();
    const kategori = $('#filterKategori').val();
    const stok = $('#filterStok').val();
    
    tableProduk.search(search).draw();
    
    // Custom filtering by column
    $.fn.dataTable.ext.search.pop();
    if (kategori || stok) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const row = tableProduk.row(dataIndex).data();
            return true; // Simplified - reload page with params
        });
    }
}

// Modal CRUD
function openModal(type, id = 0) {
    resetForm();
    
    if (type === 'add') {
        $('#formAct').val('create');
        $('#formTitle').text('Tambah Produk Baru');
        $('#submitText').text('Simpan');
        $('#produkId').val(0);
        $('#kodeBarangInput').val('(Otomatis)');
        $('#modalProduk').modal('show');
    } else if (type === 'edit' && id) {
        $('#formAct').val('update');
        $('#formTitle').text('Edit Produk');
        $('#submitText').text('Update');
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: { ajax: 1, act: 'get', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) {
                if (resp.success && resp.data) {
                    const p = resp.data;
                    $('#produkId').val(p.id);
                    $('#barcodeInput').val(p.barcode);
                    $('#kodeBarangInput').val(p.kode_barang);
                    $('#namaBarangInput').val(p.nama_barang);
                    $('#merkInput').val(p.merk);
                    $('select[name="id_kategori"]').val(p.id_kategori);
                    $('select[name="satuan"]').val(p.satuan);
                    $('#stokInput').val(p.stok);
                    $('input[name="stok_minimum"]').val(p.stok_minimum);
                    $('#hargaBeliInput').val(p.harga_beli > 0 ? formatNumber(p.harga_beli) : '');
                    $('#hargaJualInput').val(p.harga_jual > 0 ? formatNumber(p.harga_jual) : '');
                    $('input[name="lokasi_rak"]').val(p.lokasi_rak);
                    $('textarea[name="deskripsi"]').val(p.deskripsi);
                    $('#modalProduk').modal('show');
                }
            }
        });
    }
}

// Form submit
$('#formProduk').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(resp) {
            if (resp.success) {
                showToast(resp.message, 'success');
                $('#modalProduk').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(resp.message, 'error');
            }
        },
        error: function(xhr) {
            try {
                const resp = JSON.parse(xhr.responseText);
                showToast(resp.message, 'error');
            } catch(e) {
                showToast('Terjadi kesalahan', 'error');
            }
        }
    });
});

// Delete
function deleteProduk(id) {
    confirmDialog('Yakin ingin menghapus produk ini?', function() {
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: { ajax: 1, act: 'delete', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) {
                showToast(resp.message, 'success');
                setTimeout(() => location.reload(), 1000);
            }
        });
    });
}

// Barcode Scanner
let html5QrCode = null;

function openScanner() {
    $('#scannerResult').addClass('d-none');
    $('#modalScanner').modal('show');
    
    setTimeout(() => {
        if (html5QrCode) {
            html5QrCode.stop();
            html5QrCode.clear();
        }
        
        html5QrCode = new Html5Qrcode("scannerContainer");
        html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.QR_CODE
                ]
            },
            function(decodedText) {
                // Barcode detected
                $('#scannedCode').text(decodedText);
                $('#scannerResult').removeClass('d-none');
                $('#barcodeInput').val(decodedText);
                
                // Search product
                cariProdukByBarcode(decodedText);
                
                // Stop scanner
                if (html5QrCode) {
                    html5QrCode.stop();
                }
                
                setTimeout(() => $('#modalScanner').modal('hide'), 1500);
            },
            function(error) {
                // Ignore scanning errors
            }
        ).catch(function(err) {
            showToast('Gagal mengakses kamera: ' + err, 'error');
        });
    }, 500);
}

// Close scanner modal cleanup
$('#modalScanner').on('hidden.bs.modal', function() {
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
        html5QrCode.clear().catch(() => {});
        html5QrCode = null;
    }
});

// Cari produk by barcode via AJAX
function cariProdukByBarcode(barcode) {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { ajax: 1, act: 'search_barcode', barcode: barcode, csrf_token: CSRF_TOKEN },
        success: function(resp) {
            if (resp.success && resp.data) {
                showToast('Produk ditemukan! Data terisi.', 'info');
                const p = resp.data;
                $('#produkId').val(p.id);
                $('#kodeBarangInput').val(p.kode_barang);
                $('#namaBarangInput').val(p.nama_barang);
                $('#merkInput').val(p.merk);
                $('select[name="id_kategori"]').val(p.id_kategori);
                $('select[name="satuan"]').val(p.satuan);
                $('#stokInput').val(p.stok);
                $('input[name="stok_minimum"]').val(p.stok_minimum);
                $('#hargaBeliInput').val(p.harga_beli > 0 ? formatNumber(p.harga_beli) : '');
                $('#hargaJualInput').val(p.harga_jual > 0 ? formatNumber(p.harga_jual) : '');
                $('input[name="lokasi_rak"]').val(p.lokasi_rak);
                $('textarea[name="deskripsi"]').val(p.deskripsi);
                $('#formAct').val('update');
                $('#formTitle').text('Edit Produk');
                $('#submitText').text('Update');
            }
        }
    });
}

// Generate barcode otomatis
function generateBarcode() {
    const random = Math.floor(1000000000000 + Math.random() * 9000000000000);
    $('#barcodeInput').val(random.toString());
    showToast('Barcode digenerate', 'success');
}

// Format number helpers
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function formatInputRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        input.value = formatNumber(parseInt(value));
    }
}

function resetForm() {
    $('#formProduk')[0].reset();
    $('#produkId').val(0);
    $('#formAct').val('create');
    $('#formTitle').text('Tambah Produk Baru');
    $('#submitText').text('Simpan');
    $('#kodeBarangInput').val('(Otomatis)');
}

// Cetak Barcode
function cetakBarcode(barcode, nama) {
    const printWindow = window.open('', '_blank', 'width=400,height=300');
    printWindow.document.write(`
        <html><head><title>Cetak Barcode</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 20px; }
            .barcode-label { margin: 20px; padding: 10px; border: 1px dashed #ccc; }
            .barcode { font-family: 'Libre Barcode 128', monospace; font-size: 48px; }
            .nama { font-size: 14px; margin-top: 5px; }
            @media print { body { margin: 0; padding: 10px; } }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
        </head><body>
        <div class="barcode-label">
            <div class="barcode">${barcode}</div>
            <div class="nama">${nama}</div>
            <div><small>${barcode}</small></div>
        </div>
        <script>window.print();window.close();</script>
        </body></html>
    `);
    printWindow.document.close();
}
</script>

