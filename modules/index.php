<?php
/**
 * Module Supplier
 * Sistem Inventori Barang Koperasi
 */

requireLogin();
requireRole('admin');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) throw new Exception('Token tidak valid');
        
        $act = $_POST['act'] ?? '';
        switch ($act) {
            case 'create':
                $stmt = $db->prepare("INSERT INTO supplier (nama_supplier, alamat, telepon, email, kontak_person) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['nama_supplier'], $_POST['alamat'], $_POST['telepon'], $_POST['email'], $_POST['kontak_person']]);
                logAktivitas($_SESSION['user_id'], 'tambah', 'Supplier', 'Menambah supplier: ' . $_POST['nama_supplier']);
                echo json_encode(['success' => true, 'message' => 'Supplier berhasil ditambahkan']);
                break;
            case 'update':
                $stmt = $db->prepare("UPDATE supplier SET nama_supplier=?, alamat=?, telepon=?, email=?, kontak_person=? WHERE id=?");
                $stmt->execute([$_POST['nama_supplier'], $_POST['alamat'], $_POST['telepon'], $_POST['email'], $_POST['kontak_person'], $_POST['id']]);
                logAktivitas($_SESSION['user_id'], 'edit', 'Supplier', 'Mengedit supplier: ' . $_POST['nama_supplier']);
                echo json_encode(['success' => true, 'message' => 'Supplier berhasil diupdate']);
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM supplier WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                logAktivitas($_SESSION['user_id'], 'hapus', 'Supplier', 'Menghapus supplier ID: ' . $_POST['id']);
                echo json_encode(['success' => true, 'message' => 'Supplier berhasil dihapus']);
                break;
            case 'get':
                $stmt = $db->prepare("SELECT * FROM supplier WHERE id = ?");
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

$supplierList = $db->query("SELECT * FROM supplier ORDER BY nama_supplier")->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Supplier</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-truck me-2 text-primary"></i>Data Supplier</h4>
    <button class="btn btn-primary btn-sm" onclick="openModalSup('add')">
        <i class="fas fa-plus"></i> Tambah Supplier
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelSupplier">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Supplier</th>
                        <th>Kontak Person</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($supplierList as $s): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($s['nama_supplier']) ?></strong></td>
                        <td><?= e($s['kontak_person'] ?? '-') ?></td>
                        <td><?= e($s['telepon'] ?? '-') ?></td>
                        <td><?= e($s['email'] ?? '-') ?></td>
                        <td><?= e($s['alamat'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="openModalSup('edit', <?= $s['id'] ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSupplier(<?= $s['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSupplier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-truck me-2 text-primary"></i><span id="formTitleSup">Tambah Supplier</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSupplier">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="act" id="supAct" value="create">
                <input type="hidden" name="id" id="supId" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_supplier" id="supNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kontak Person</label>
                        <input type="text" class="form-control" name="kontak_person" id="supKontak">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-control" name="telepon" id="supTelepon" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="supEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" id="supAlamat" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span id="submitTextSup">Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelSupplier').DataTable();
    $('#formSupplier').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    $('#modalSupplier').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(resp.message, 'error');
                }
            }
        });
    });
});

function openModalSup(type, id = 0) {
    if (type === 'add') {
        $('#supAct').val('create'); $('#formTitleSup').text('Tambah Supplier');
        $('#submitTextSup').text('Simpan'); $('#supId').val(0);
        $('#formSupplier')[0].reset();
        $('#modalSupplier').modal('show');
    } else {
        $.ajax({
            url: window.location.href, method: 'POST',
            data: { ajax: 1, act: 'get', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) {
                if (resp.data) {
                    const s = resp.data;
                    $('#supAct').val('update'); $('#formTitleSup').text('Edit Supplier');
                    $('#submitTextSup').text('Update'); $('#supId').val(s.id);
                    $('#supNama').val(s.nama_supplier); $('#supKontak').val(s.kontak_person);
                    $('#supTelepon').val(s.telepon); $('#supEmail').val(s.email);
                    $('#supAlamat').val(s.alamat);
                    $('#modalSupplier').modal('show');
                }
            }
        });
    }
}

function deleteSupplier(id) {
    confirmDialog('Yakin ingin menghapus supplier ini?', function() {
        $.ajax({
            url: window.location.href, method: 'POST',
            data: { ajax: 1, act: 'delete', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) { showToast(resp.message, 'success'); setTimeout(() => location.reload(), 1000); }
        });
    });
}
</script>

