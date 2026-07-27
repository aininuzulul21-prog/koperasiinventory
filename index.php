<?php
/**
 * Module Backup & Restore Database
 * Sistem Inventori Barang Koperasi
 */

requireLogin();
requireRole('admin');

$db = getDB();
$message = '';

// Handle backup
if (isset($_GET['action']) && $_GET['action'] == 'backup') {
    try {
        $backupFile = 'backup_koperasi_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = UPLOAD_PATH . '/backup/' . $backupFile;
        
        if (!is_dir(UPLOAD_PATH . '/backup')) {
            mkdir(UPLOAD_PATH . '/backup', 0777, true);
        }
        
        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $output = "-- Backup Database Koperasi Inventory\n";
        $output .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Create table structure
            $stmt = $db->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch();
            $output .= "\n\n" . $row['Create Table'] . ";\n\n";
            
            // Get data
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $columns = array_keys($rows[0]);
                $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        $vals[] = $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                    }
                    $values[] = "(" . implode(', ', $vals) . ")";
                }
                $output .= implode(",\n", $values) . ";\n";
            }
        }
        
        file_put_contents($backupPath, $output);
        logAktivitas($_SESSION['user_id'], 'backup', 'Database', 'Backup database: ' . $backupFile);
        $message = ['success' => true, 'text' => 'Backup berhasil: ' . $backupFile];
        
        // Download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backupFile . '"');
        readfile($backupPath);
        exit;
        
    } catch (Exception $e) {
        $message = ['success' => false, 'text' => 'Backup gagal: ' . $e->getMessage()];
    }
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['restore_file'])) {
    try {
        $file = $_FILES['restore_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload gagal');
        
        $content = file_get_contents($file['tmp_name']);
        $statements = explode(';', $content);
        
        $db->beginTransaction();
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && stripos($statement, 'INSERT') === 0) {
                    $db->exec($statement);
                }
            }
            $db->commit();
            logAktivitas($_SESSION['user_id'], 'restore', 'Database', 'Restore database dari file: ' . $file['name']);
            $message = ['success' => true, 'text' => 'Restore berhasil!'];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        $message = ['success' => false, 'text' => 'Restore gagal: ' . $e->getMessage()];
    }
}

// List backups
$backups = [];
$backupDir = UPLOAD_PATH . '/backup/';
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backupDir . $file))
            ];
        }
    }
}
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Backup & Restore</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-database me-2 text-primary"></i>Backup & Restore Database</h4>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $message['success'] ? 'success' : 'danger' ?> alert-dismissible fade show">
    <i class="fas fa-<?= $message['success'] ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
    <?= e($message['text']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-download me-2 text-success"></i>Backup Database</div>
            <div class="card-body text-center py-5">
                <i class="fas fa-database fa-4x text-success mb-3"></i>
                <p class="mb-3">Download backup database dalam format SQL.</p>
                <a href="?page=backup&action=backup" class="btn btn-success btn-lg">
                    <i class="fas fa-download"></i> Download Backup
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-upload me-2 text-warning"></i>Restore Database</div>
            <div class="card-body text-center py-5">
                <i class="fas fa-cloud-upload-alt fa-4x text-warning mb-3"></i>
                <p class="mb-3">Upload file SQL untuk restore database.</p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" class="form-control" name="restore_file" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Yakin ingin restore? Data saat ini akan ditimpa!')">
                        <i class="fas fa-upload"></i> Restore Database
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-history me-2"></i>Riwayat Backup</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($backups): foreach ($backups as $b): ?>
                            <tr>
                                <td><code><?= e($b['name']) ?></code></td>
                                <td><?= number_format($b['size'] / 1024, 2) ?> KB</td>
                                <td><?= $b['date'] ?></td>
                                <td>
                                    <a href="<?= UPLOAD_URL ?>/backup/<?= e($b['name']) ?>" class="btn btn-sm btn-success" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada backup</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
