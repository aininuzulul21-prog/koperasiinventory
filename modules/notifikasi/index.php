<?php
/**
 * Module Notifikasi
 * Sistem Inventori Barang Koperasi
 */

$db = getDB();
$notifikasi = $db->prepare("SELECT * FROM notifikasi WHERE (id_user = ? OR id_user IS NULL) ORDER BY created_at DESC");
$notifikasi->execute([$_SESSION['user_id']]);
$notifikasi = $notifikasi->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Notifikasi</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-bell me-2 text-primary"></i>Notifikasi</h4>
    <button class="btn btn-sm btn-outline-primary" onclick="markAllNotifRead()">
        <i class="fas fa-check-double"></i> Tandai Semua Dibaca
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if ($notifikasi): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifikasi as $n): 
                $iconMap = ['info' => 'fa-info-circle text-primary', 'warning' => 'fa-exclamation-triangle text-warning', 'danger' => 'fa-times-circle text-danger', 'success' => 'fa-check-circle text-success'];
            ?>
            <a href="<?= e($n['url'] ?? '#') ?>" class="list-group-item list-group-item-action <?= $n['is_read'] ? '' : 'fw-bold' ?>">
                <div class="d-flex gap-3 align-items-start">
                    <i class="fas <?= $iconMap[$n['tipe']] ?? $iconMap['info'] ?> mt-1 fa-lg"></i>
                    <div class="flex-grow-1">
                        <p class="mb-1"><?= e($n['judul']) ?></p>
                        <small class="text-muted"><?= e($n['pesan']) ?></small>
                        <br><small class="text-muted"><?= formatTanggal($n['created_at'], 'd F Y H:i') ?></small>
                    </div>
                    <?php if (!$n['is_read']): ?>
                    <span class="badge bg-primary rounded-pill">Baru</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-bell-slash fa-3x mb-3"></i>
            <p class="mb-0">Tidak ada notifikasi</p>
        </div>
        <?php endif; ?>
    </div>
</div>
