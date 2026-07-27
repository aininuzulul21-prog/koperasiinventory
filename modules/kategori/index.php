<?php
/**
 * Module Kategori
 * Sistem Inventori Barang Koperasi
 */

requireLogin();
requireRole('admin');

$db = getDB();
$action = $_GET['action'] ?? 'list';

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrf)) throw new Exception('Token tidak valid');
        
        $act = $_POST['act'] ?? '';
        
        switch ($act) {
            case 'create':
                $stmt = $db->prepare("INSERT INTO kategori (nama_kategori, deskripsi, icon) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['nama_kategori'], $_POST['deskripsi'] ?? '', $_POST['icon'] ?? 'fa-box']);
                logAktivitas($_SESSION['user_id'], 'tambah', 'Kategori', 'Menambah kategori: ' . $_POST['nama_kategori']);
                echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan']);
                break;
                
            case 'update':
                $stmt = $db->prepare("UPDATE kategori SET nama_kategori=?, deskripsi=?, icon=? WHERE id=?");
                $stmt->execute([$_POST['nama_kategori'], $_POST['deskripsi'] ?? '', $_POST['icon'] ?? 'fa-box', $_POST['id']]);
                logAktivitas($_SESSION['user_id'], 'edit', 'Kategori', 'Mengedit kategori: ' . $_POST['nama_kategori']);
                echo json_encode(['success' => true, 'message' => 'Kategori berhasil diupdate']);
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                logAktivitas($_SESSION['user_id'], 'hapus', 'Kategori', 'Menghapus kategori ID: ' . $_POST['id']);
                echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus']);
                break;
                
            case 'get':
                $stmt = $db->prepare("SELECT * FROM kategori WHERE id = ?");
                $stmt->execute([$_POST['id']]);
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

$kategoriList = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$iconList = ['fa-box', 'fa-utensils', 'fa-pen', 'fa-plug', 'fa-soap', 'fa-tshirt', 'fa-book', 'fa-laptop', 'fa-mobile', 'fa-car', 'fa-home', 'fa-gift', 'fa-tag', 'fa-tools', 'fa-medkit'];
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Kategori</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-tags me-2 text-primary"></i>Data Kategori</h4>
    <button class="btn btn-primary btn-sm" onclick="openModal('add')">
        <i class="fas fa-plus"></i> Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelKategori">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Icon</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Produk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($kategoriList as $k): 
                        $jml = $db->prepare("SELECT COUNT(*) as total FROM produk WHERE id_kategori = ?");
                        $jml->execute([$k['id']]);
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><i class="fas <?= e($k['icon'] ?? 'fa-box') ?> fa-lg text-primary"></i></td>
                        <td><strong><?= e($k['nama_kategori']) ?></strong></td>
                        <td><?= e($k['deskripsi'] ?? '-') ?></td>
                        <td><span class="badge bg-primary"><?= $jml->fetch()['total'] ?> produk</span></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="openModal('edit', <?= $k['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteKategori(<?= $k['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalKategori" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-tag me-2 text-primary"></i><span id="formTitleKtg">Tambah Kategori</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formKategori">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="act" id="ktgAct" value="create">
                <input type="hidden" name="id" id="ktgId" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_kategori" id="ktgNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <select class="form-select" name="icon" id="ktgIcon">
                            <?php foreach ($iconList as $icon): ?>
                            <option value="<?= $icon ?>"><i class="fas <?= $icon ?>"></i> <?= $icon ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="ktgDeskripsi" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span id="submitTextKtg">Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelKategori').DataTable();
    
    $('#formKategori').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    $('#modalKategori').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(resp.message, 'error');
                }
            }
        });
    });
});

function openModal(type, id = 0) {
    if (type === 'add') {
        $('#ktgAct').val('create');
        $('#formTitleKtg').text('Tambah Kategori');
        $('#submitTextKtg').text('Simpan');
        $('#ktgId').val(0);
        $('#ktgNama').val('');
        $('#ktgDeskripsi').val('');
        $('#modalKategori').modal('show');
    } else if (type === 'edit' && id) {
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: { ajax: 1, act: 'get', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) {
                if (resp.data) {
                    const k = resp.data;
                    $('#ktgAct').val('update');
                    $('#formTitleKtg').text('Edit Kategori');
                    $('#submitTextKtg').text('Update');
                    $('#ktgId').val(k.id);
                    $('#ktgNama').val(k.nama_kategori);
                    $('#ktgDeskripsi').val(k.deskripsi);
                    $('#ktgIcon').val(k.icon);
                    $('#modalKategori').modal('show');
                }
            }
        });
    }
}

function deleteKategori(id) {
    confirmDialog('Yakin ingin menghapus kategori ini?', function() {
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
</script>

