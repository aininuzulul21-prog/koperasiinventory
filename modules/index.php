<?php
/**
 * Cetak Struk Transaksi
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();
$no_transaksi = $_GET['no'] ?? '';

if (!$no_transaksi) {
    die('No transaksi tidak valid');
}

$transaksi = $db->prepare("
    SELECT t.*, u.nama as nama_kasir 
    FROM transaksi t 
    LEFT JOIN users u ON u.id = t.id_kasir 
    WHERE t.no_transaksi = ?
");
$transaksi->execute([$no_transaksi]);
$t = $transaksi->fetch();

if (!$t) {
    die('Transaksi tidak ditemukan');
}

$detail = $db->prepare("
    SELECT dt.*, p.nama_barang 
    FROM detail_transaksi dt 
    JOIN produk p ON p.id = dt.id_produk 
    WHERE dt.id_transaksi = ?
");
$detail->execute([$t['id']]);
$items = $detail->fetchAll();

// Get setting
$setting = $db->query("SELECT nama_setting, nilai_setting FROM setting")->fetchAll();
$settings = [];
foreach ($setting as $s) {
    $settings[$s['nama_setting']] = $s['nilai_setting'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= e($no_transaksi) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { font-size: 14px; margin-bottom: 4px; }
        .header p { font-size: 10px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .item { display: flex; justify-content: space-between; font-size: 11px; }
        .item-detail { display: flex; justify-content: space-between; font-size: 10px; margin-left: 10px; }
        .total-row { display: flex; justify-content: space-between; font-weight: bold; }
        .footer { text-align: center; margin-top: 10px; font-size: 10px; }
        .thankyou { text-align: center; margin-top: 6px; font-size: 11px; }
        @media print {
            body { width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><?= e($settings['nama_aplikasi'] ?? 'Koperasi') ?></h2>
        <p><?= e($settings['alamat_koperasi'] ?? '') ?></p>
        <p>Telp: <?= e($settings['telepon'] ?? '') ?></p>
    </div>
    
    <div class="divider"></div>
    
    <div style="display:flex;justify-content:space-between;font-size:10px">
        <span>No: <?= e($t['no_transaksi']) ?></span>
        <span><?= formatTanggal($t['created_at'], 'd/m/Y H:i') ?></span>
    </div>
    <div style="font-size:10px">Kasir: <?= e($t['nama_kasir'] ?? '-') ?></div>
    
    <div class="divider"></div>
    
    <div style="font-weight:bold;display:flex;justify-content:space-between">
        <span>Item</span>
        <span>Qty</span>
        <span>Harga</span>
        <span>Subtotal</span>
    </div>
    
    <div class="divider"></div>
    
    <?php foreach ($items as $item): ?>
    <div class="item">
        <span><?= e($item['nama_barang']) ?></span>
    </div>
    <div class="item-detail">
        <span><?= number_format($item['qty']) ?> x <?= formatRupiah($item['harga']) ?></span>
        <span><?= formatRupiah($item['subtotal']) ?></span>
    </div>
    <?php endforeach; ?>
    
    <div class="divider"></div>
    
    <div class="total-row"><span>Total</span><span><?= formatRupiah($t['total_harga']) ?></span></div>
    <?php if ($t['diskon'] > 0): ?>
    <div class="total-row"><span>Diskon</span><span>-<?= formatRupiah($t['diskon']) ?></span></div>
    <?php endif; ?>
    <div class="total-row" style="font-size:14px;margin:4px 0">
        <span>Grand Total</span>
        <span><?= formatRupiah($t['grand_total']) ?></span>
    </div>
    <div class="total-row"><span>Bayar</span><span><?= formatRupiah($t['bayar']) ?></span></div>
    <div class="total-row"><span>Kembalian</span><span><?= formatRupiah($t['kembalian']) ?></span></div>
    
    <div style="font-size:10px;margin-top:4px">Metode: <?= strtoupper($t['metode_bayar']) ?></div>
    
    <div class="divider"></div>
    
    <div class="thankyou">
        <p>Terima kasih telah berbelanja</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/kembali</p>
    </div>
    

