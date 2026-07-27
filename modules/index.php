<?php
/**
 * Module Transaksi Penjualan
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) throw new Exception('Token tidak valid');
        
        $act = $_POST['act'] ?? '';
        
        switch ($act) {
            case 'get_produk':
                $barcode = trim($_POST['barcode'] ?? '');
                $stmt = $db->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON k.id = p.id_kategori WHERE p.barcode = ? AND p.is_active = 1");
                $stmt->execute([$barcode]);
                $produk = $stmt->fetch();
                
                if ($produk && $produk['stok'] <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Stok produk habis']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'data' => $produk, 'message' => $produk ? 'Produk ditemukan' : 'Produk tidak ditemukan']);
                break;
                
            case 'simpan_transaksi':
                $items = json_decode($_POST['items'] ?? '[]', true);
                $total = str_replace(['.', ','], ['', '.'], $_POST['total'] ?? 0);
                $diskon = str_replace(['.', ','], ['', '.'], $_POST['diskon'] ?? 0);
                $grand_total = str_replace(['.', ','], ['', '.'], $_POST['grand_total'] ?? 0);
                $bayar = str_replace(['.', ','], ['', '.'], $_POST['bayar'] ?? 0);
                $kembalian = str_replace(['.', ','], ['', '.'], $_POST['kembalian'] ?? 0);
                $metode = $_POST['metode_bayar'] ?? 'tunai';
                
                if (empty($items)) throw new Exception('Keranjang masih kosong');
                
                $db->beginTransaction();
                try {
                    $no_transaksi = generateNoTransaksi();
                    $total_item = array_sum(array_column($items, 'qty'));
                    
                    $stmt = $db->prepare("INSERT INTO transaksi (no_transaksi, total_item, total_harga, diskon, grand_total, bayar, kembalian, metode_bayar, id_kasir) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$no_transaksi, $total_item, $total, $diskon, $grand_total, $bayar, $kembalian, $metode, $_SESSION['user_id']]);
                    $transaksiId = $db->lastInsertId();
                    
                    foreach ($items as $item) {
                        // Detail
                        $stmt = $db->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga, diskon, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$transaksiId, $item['id'], $item['qty'], $item['harga'], $item['diskon'] ?? 0, $item['subtotal']]);
                        
                        // Kurangi stok
                        $stmt = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
                        $stmt->execute([$item['qty'], $item['id']]);
                    }
                    
                    // Log
                    logAktivitas($_SESSION['user_id'], 'transaksi', 'Penjualan', "Transaksi: {$no_transaksi} - Rp " . number_format($grand_total, 0, ',', '.'));
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Transaksi berhasil', 'no_transaksi' => $no_transaksi]);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
        }
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

$produkList = $db->query("SELECT id, barcode, nama_barang, harga_jual, stok, satuan FROM produk WHERE is_active = 1 AND stok > 0 ORDER BY nama_barang")->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Transaksi Penjualan</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-cash-register me-2 text-primary"></i>Transaksi Penjualan</h4>
</div>

<div class="row g-3">
    <!-- Left: Scanner & Products -->
    <div class="col-lg-7">
        <!-- Scan Barcode -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-camera me-2"></i>Scan Barcode</div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-lg" id="barcodeScan" placeholder="Scan barcode produk..." autofocus>
                    <button class="btn btn-primary" onclick="scanBarcode()"><i class="fas fa-camera"></i></button>
                    <button class="btn btn-outline-success" onclick="cariProdukManual()"><i class="fas fa-search"></i></button>
                </div>
                <select class="form-select select2" id="pilihProduk" onchange="pilihProduk()">
                    <option value="">-- Cari & Pilih Produk --</option>
                    <?php foreach ($produkList as $p): ?>
                    <option value="<?= $p['id'] ?>" data-barcode="<?= e($p['barcode']) ?>" data-harga="<?= $p['harga_jual'] ?>" data-stok="<?= $p['stok'] ?>" data-satuan="<?= e($p['satuan']) ?>">
                        <?= e($p['nama_barang']) ?> - <?= formatRupiah($p['harga_jual']) ?> (Stok: <?= $p['stok'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Cart -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja</span>
                <span class="badge bg-primary" id="cartCount">0 item</span>
            </div>
            <div class="card-body p-0">
                <div id="cartItems">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-cart-plus fa-2x mb-2"></i>
                        <p class="mb-0">Belum ada item. Scan barcode untuk memulai.</p>
                    </div>
                </div>
            </div>
            <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'kasir'): ?>
            <div class="card-footer">
                <button class="btn btn-danger btn-sm" onclick="clearCart()"><i class="fas fa-trash"></i> Kosongkan</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right: Total & Payment -->
    <div class="col-lg-5">
        <div class="total-section mb-3">
            <div class="row g-2">
                <div class="col-6">
                    <div class="total-label">Total</div>
                    <div class="total-value" id="totalDisplay">Rp 0</div>
                </div>
                <div class="col-6">
                    <div class="total-label">Item</div>
                    <div class="total-value text-warning" id="itemCount">0</div>
                </div>
            </div>
            <hr class="my-2 border-light opacity-25">
            <div class="row g-2">
                <div class="col-6">
                    <div class="total-label">Diskon</div>
                    <div class="total-value text-warning" id="diskonDisplay">Rp 0</div>
                </div>
                <div class="col-6">
                    <div class="total-label">Grand Total</div>
                    <div class="total-value" id="grandTotalDisplay">Rp 0</div>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="card mb-3">
            <div class="card-header">Metode Pembayaran</div>
            <div class="card-body">
                <div class="payment-methods" id="paymentMethods">
                    <div class="payment-method active" data-metode="tunai" onclick="pilihMetode(this)">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Tunai</span>
                    </div>
                    <div class="payment-method" data-metode="qris" onclick="pilihMetode(this)">
                        <i class="fas fa-qrcode"></i>
                        <span>QRIS</span>
                    </div>
                    <div class="payment-method" data-metode="transfer" onclick="pilihMetode(this)">
                        <i class="fas fa-university"></i>
                        <span>Transfer</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bayar -->
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Diskon</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control" id="diskonInput" value="0" oninput="hitungKembalian()">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control form-control-lg" id="bayarInput" oninput="hitungKembalian()" placeholder="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kembalian</label>
                    <div class="fw-bold text-success fs-4" id="kembalianDisplay">Rp 0</div>
                </div>
                
                <input type="hidden" id="metodeBayar" value="tunai">
                <button class="btn btn-success btn-lg w-100" onclick="simpanTransaksi()">
                    <i class="fas fa-check-circle"></i> Proses Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scanner Modal -->
<div class="modal fade" id="modalScan" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h6>Scan Barcode</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-2"><div id="scannerTrx" style="width:100%;min-height:200px"></div></div>
        </div>
    </div>
</div>

<script>
let cart = [];
let total = 0;

// Enter key to search barcode
$(document).ready(function() {
    $('#barcodeScan').on('keypress', function(e) {
        if (e.key === 'Enter') {
            const code = $(this).val().trim();
            if (code) cariProdukByBarcode(code);
        }
    });
    
    // Auto-focus barcode input
    setInterval(() => { if (!$('#barcodeScan').is(':focus')) $('#barcodeScan').focus(); }, 3000);
});

function cariProdukByBarcode(barcode) {
    $.ajax({
        url: window.location.href, method: 'POST',
        data: { ajax: 1, act: 'get_produk', barcode: barcode, csrf_token: CSRF_TOKEN },
        success: function(resp) {
            if (resp.success && resp.data) {
                tambahKeKeranjang(resp.data);
                $('#barcodeScan').val('').focus();
            } else {
                showToast(resp.message || 'Produk tidak ditemukan', 'warning');
                $('#barcodeScan').val('').focus();
            }
        }
    });
}

function cariProdukManual() {
    const val = $('#barcodeScan').val().trim();
    if (val) cariProdukByBarcode(val);
}

function pilihProduk() {
    const select = document.getElementById('pilihProduk');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const produk = {
            id: parseInt(option.value),
            barcode: option.dataset.barcode,
            nama_barang: option.text.split(' - ')[0],
            harga_jual: parseInt(option.dataset.harga),
            stok: parseInt(option.dataset.stok),
            satuan: option.dataset.satuan
        };
        tambahKeKeranjang(produk);
        select.selectedIndex = 0;
    }
}

function tambahKeKeranjang(produk) {
    const existing = cart.findIndex(item => item.id === produk.id);
    
    if (existing >= 0) {
        if (cart[existing].qty >= produk.stok) {
            showToast('Stok tidak mencukupi!', 'error');
            return;
        }
        cart[existing].qty += 1;
        cart[existing].subtotal = cart[existing].qty * cart[existing].harga;
    } else {
        if (produk.stok <= 0) {
            showToast('Stok habis!', 'error');
            return;
        }
        cart.push({
            id: produk.id,
            barcode: produk.barcode,
            nama_barang: produk.nama_barang,
            harga: parseFloat(produk.harga_jual),
            qty: 1,
            diskon: 0,
            subtotal: parseFloat(produk.harga_jual),
            stok: parseInt(produk.stok),
            satuan: produk.satuan
        });
    }
    
    renderCart();
    showToast('Ditambahkan: ' + produk.nama_barang, 'success');
}

function renderCart() {
    const container = $('#cartItems');
    
    if (cart.length === 0) {
        container.html('<div class="text-center py-4 text-muted"><i class="fas fa-cart-plus fa-2x mb-2"></i><p class="mb-0">Keranjang kosong</p></div>');
        $('#cartCount').text('0 item');
        updateTotal();
        return;
    }
    
    let html = '';
    let totalItem = 0;
    
    cart.forEach((item, index) => {
        totalItem += item.qty;
        html += `
            <div class="keranjang-item">
                <div class="item-info">
                    <div class="item-nama">${escapeHtml(item.nama_barang)}</div>
                    <div class="item-harga">${formatRupiah(item.harga)} x ${item.qty} = ${formatRupiah(item.subtotal)}</div>
                </div>
                <div class="item-actions">
                    <button class="btn btn-sm btn-outline-secondary" onclick="ubahQty(${index}, -1)">-</button>
                    <span class="fw-bold mx-1">${item.qty}</span>
                    <button class="btn btn-sm btn-outline-primary" onclick="ubahQty(${index}, 1)">+</button>
                    <button class="btn btn-sm btn-danger ms-2" onclick="hapusItem(${index})"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
    });
    
    container.html(html);
    $('#cartCount').text(totalItem + ' item');
    updateTotal();
}

function ubahQty(index, delta) {
    const newQty = cart[index].qty + delta;
    if (newQty <= 0) {
        hapusItem(index);
        return;
    }
    if (newQty > cart[index].stok) {
        showToast('Stok maksimal: ' + cart[index].stok, 'error');
        return;
    }
    
    cart[index].qty = newQty;
    cart[index].subtotal = newQty * cart[index].harga;
    renderCart();
}

function hapusItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    confirmDialog('Kosongkan keranjang?', function() {
        cart = [];
        renderCart();
    });
}

function updateTotal() {
    total = cart.reduce((sum, item) => sum + item.subtotal, 0);
    $('#totalDisplay').text(formatRupiah(total));
    $('#itemCount').text(cart.reduce((sum, item) => sum + item.qty, 0));
    hitungKembalian();
}

function hitungKembalian() {
    const diskon = parseRupiah($('#diskonInput').val());
    const bayar = parseRupiah($('#bayarInput').val());
    const grandTotal = Math.max(0, total - diskon);
    
    $('#diskonDisplay').text(formatRupiah(diskon));
    $('#grandTotalDisplay').text(formatRupiah(grandTotal));
    
    if (bayar >= grandTotal && grandTotal > 0) {
        $('#kembalianDisplay').text(formatRupiah(bayar - grandTotal));
        $('#kembalianDisplay').removeClass('text-danger').addClass('text-success');
    } else {
        $('#kembalianDisplay').text(formatRupiah(0));
        if (bayar > 0 && bayar < grandTotal) {
            $('#kembalianDisplay').removeClass('text-success').addClass('text-danger');
        }
    }
}

function pilihMetode(el) {
    $('.payment-method').removeClass('active');
    $(el).addClass('active');
    $('#metodeBayar').val($(el).data('metode'));
}

function simpanTransaksi() {
    if (cart.length === 0) {
        showToast('Keranjang masih kosong', 'error');
        return;
    }
    
    const diskon = parseRupiah($('#diskonInput').val());
    const grandTotal = Math.max(0, total - diskon);
    const bayar = parseRupiah($('#bayarInput').val());
    
    if (bayar < grandTotal) {
        showToast('Jumlah bayar kurang!', 'error');
        return;
    }
    
    confirmDialog(`Proses transaksi dengan total Rp ${formatNumber(grandTotal)}?`, function() {
        $.ajax({
            url: window.location.href, method: 'POST',
            data: {
                ajax: 1, act: 'simpan_transaksi',
                items: JSON.stringify(cart),
                total: total,
                diskon: diskon,
                grand_total: grandTotal,
                bayar: bayar,
                kembalian: bayar - grandTotal,
                metode_bayar: $('#metodeBayar').val(),
                csrf_token: CSRF_TOKEN
            },
            success: function(resp) {
                if (resp.success) {
                    showToast('Transaksi berhasil!', 'success');
                    cetakStruk(resp.no_transaksi);
                    cart = [];
                    renderCart();
                    $('#diskonInput').val('0');
                    $('#bayarInput').val('');
                    hitungKembalian();
                } else {
                    showToast(resp.message, 'error');
                }
            }
        });
    });
}

function cetakStruk(noTransaksi) {
    const url = BASE_URL + '/index.php?page=cetak-struk&no=' + noTransaksi;
    window.open(url, '_blank', 'width=400,height=600');
}

function scanBarcode() {
    $('#modalScan').modal('show');
    setTimeout(() => {
        const scanner = new Html5Qrcode("scannerTrx");
        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 150 } },
            function(decodedText) {
                $('#barcodeScan').val(decodedText);
                cariProdukByBarcode(decodedText);
                scanner.stop().catch(() => {});
                $('#modalScan').modal('hide');
            }, function() {}
        );
    }, 500);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

