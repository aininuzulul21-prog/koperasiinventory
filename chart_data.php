<?php
/**
 * API Chart Data
 * Sistem Inventori Barang Koperasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // === Stok Chart (Barang Masuk & Keluar per hari dalam bulan ini) ===
    $stokLabels = [];
    $stokMasuk = [];
    $stokKeluar = [];
    
    // Get days in current month (last 10 days)
    for ($i = 9; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $stokLabels[] = date('d/m', strtotime($date));
        
        // Barang Masuk
        $stmt = $db->prepare("SELECT COALESCE(SUM(qty), 0) as total FROM barang_masuk WHERE tanggal = ?");
        $stmt->execute([$date]);
        $stokMasuk[] = (int)$stmt->fetch()['total'];
        
        // Barang Keluar
        $stmt = $db->prepare("SELECT COALESCE(SUM(qty), 0) as total FROM barang_keluar WHERE tanggal = ?");
        $stmt->execute([$date]);
        $stokKeluar[] = (int)$stmt->fetch()['total'];
    }
    
    // === Penjualan Chart (7 hari terakhir) ===
    $penjualanLabels = [];
    $penjualanTotal = [];
    $penjualanCount = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $penjualanLabels[] = date('d/m', strtotime($date));
        
        // Total penjualan
        $stmt = $db->prepare("SELECT COALESCE(SUM(grand_total), 0) as total, COUNT(*) as count FROM transaksi WHERE DATE(created_at) = ? AND status = 'selesai'");
        $stmt->execute([$date]);
        $row = $stmt->fetch();
        $penjualanTotal[] = (float)$row['total'];
        $penjualanCount[] = (int)$row['count'];
    }
    
    echo json_encode([
        'success' => true,
        'stok' => [
            'labels' => $stokLabels,
            'masuk' => $stokMasuk,
            'keluar' => $stokKeluar
        ],
        'penjualan' => [
            'labels' => $penjualanLabels,
            'total' => $penjualanTotal,
            'count' => $penjualanCount
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

