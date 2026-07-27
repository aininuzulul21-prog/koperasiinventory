<?php
/**
 * Module Pengaturan Aplikasi
 * Sistem Inventori Barang Koperasi
 */

requireLogin();
requireRole('admin');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = $_POST['setting'] ?? [];
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("UPDATE setting SET nilai_setting = ? WHERE nama_setting = ?");
        $stmt->execute([$value, $key]);
    }
    logAktivitas($_SESSION['user_id'], 'edit', 'Pengaturan', 'Mengupdate pengaturan aplikasi');
    $success = 'Pengaturan berhasil disimpan';
}

$settings = $db->query("SELECT * FROM setting")->fetchAll();
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['nama_setting']] = $s['nilai_setting'];
}
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Pengaturan</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-gear me-2 text-primary"></i>Pengaturan Aplikasi</h4>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i> <?= $success ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Aplikasi</label>
                    <input type="text" class="form-control" name="setting[nama_aplikasi]" value="<?= e($settingsMap['nama_aplikasi'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Alamat Koperasi</label>
                    <input type="text" class="form-control" name="setting[alamat_koperasi]" value="<?= e($settingsMap['alamat_koperasi'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Telepon</label>
                    <input type="text" class="form-control" name="setting[telepon]" value="<?= e($settingsMap['telepon'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="setting[email]" value="<?= e($settingsMap['email'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Mata Uang</label>
                    <input type="text" class="form-control" name="setting[currency]" value="<?= e($settingsMap['currency'] ?? 'Rp') ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <select class="form-select" name="setting[timezone]">
                        <?php
                        $timezones = ['Asia/Jakarta' => 'WIB (UTC+7)', 'Asia/Makassar' => 'WITA (UTC+8)', 'Asia/Jayapura' => 'WIT (UTC+9)'];
                        foreach ($timezones as $tz => $label):
                        ?>
                        <option value="<?= $tz ?>" <?= ($settingsMap['timezone'] ?? 'Asia/Jakarta') == $tz ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <hr class="my-4">
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>
