<?php
/**
 * Module Data User
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
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['nama'], $_POST['username'], $password, $_POST['role']]);
                logAktivitas($_SESSION['user_id'], 'tambah', 'User', 'Menambah user: ' . $_POST['username']);
                echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan']);
                break;
                
            case 'update':
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET nama=?, username=?, password=?, role=? WHERE id=?");
                    $stmt->execute([$_POST['nama'], $_POST['username'], $password, $_POST['role'], $_POST['id']]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET nama=?, username=?, role=? WHERE id=?");
                    $stmt->execute([$_POST['nama'], $_POST['username'], $_POST['role'], $_POST['id']]);
                }
                logAktivitas($_SESSION['user_id'], 'edit', 'User', 'Mengedit user: ' . $_POST['username']);
                echo json_encode(['success' => true, 'message' => 'User berhasil diupdate']);
                break;
                
            case 'delete':
                if ($_POST['id'] == $_SESSION['user_id']) throw new Exception('Tidak bisa menghapus akun sendiri');
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                logAktivitas($_SESSION['user_id'], 'hapus', 'User', 'Menghapus user ID: ' . $_POST['id']);
                echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
                break;
                
            case 'get':
                $stmt = $db->prepare("SELECT id, nama, username, role FROM users WHERE id = ?");
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

$userList = $db->query("SELECT id, nama, username, role, last_login, is_active FROM users ORDER BY nama")->fetchAll();
?>

<nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Data User</li>
    </ol>
</nav>

<div class="page-header">
    <h4><i class="fas fa-users me-2 text-primary"></i>Data User</h4>
    <button class="btn btn-primary btn-sm" onclick="openModalUser('add')">
        <i class="fas fa-plus"></i> Tambah User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelUser">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Terakhir Login</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($userList as $u): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($u['nama']) ?></strong></td>
                        <td><?= e($u['username']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['role'] == 'admin' ? 'primary' : 'success' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td><?= $u['last_login'] ? formatTanggal($u['last_login'], 'd F Y H:i') : '-' ?></td>
                        <td>
                            <span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                                <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="openModalUser('edit', <?= $u['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $u['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user me-2 text-primary"></i><span id="formTitleUser">Tambah User</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUser">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="act" id="userAct" value="create">
                <input type="hidden" name="id" id="userId" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="userNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="userUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger" id="passReq">*</span></label>
                        <input type="password" class="form-control" name="password" id="userPassword">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="userRole">
                            <option value="admin">Administrator</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span id="submitTextUser">Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelUser').DataTable();
    
    $('#formUser').on('submit', function(e) {
        e.preventDefault();
        const password = $('#userPassword').val();
        const act = $('#userAct').val();
        
        if (act === 'create' && password.length < 6) {
            showToast('Password minimal 6 karakter', 'error');
            return;
        }
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    $('#modalUser').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(resp.message, 'error');
                }
            }
        });
    });
});

function openModalUser(type, id = 0) {
    if (type === 'add') {
        $('#userAct').val('create');
        $('#formTitleUser').text('Tambah User');
        $('#submitTextUser').text('Simpan');
        $('#userId').val(0);
        $('#formUser')[0].reset();
        $('#passReq').show();
        $('#userPassword').prop('required', true);
        $('#modalUser').modal('show');
    } else {
        $.ajax({
            url: window.location.href, method: 'POST',
            data: { ajax: 1, act: 'get', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) {
                if (resp.data) {
                    const u = resp.data;
                    $('#userAct').val('update');
                    $('#formTitleUser').text('Edit User');
                    $('#submitTextUser').text('Update');
                    $('#userId').val(u.id);
                    $('#userNama').val(u.nama);
                    $('#userUsername').val(u.username);
                    $('#userRole').val(u.role);
                    $('#userPassword').val('').prop('required', false);
                    $('#passReq').hide();
                    $('#modalUser').modal('show');
                }
            }
        });
    }
}

function deleteUser(id) {
    confirmDialog('Yakin ingin menghapus user ini?', function() {
        $.ajax({
            url: window.location.href, method: 'POST',
            data: { ajax: 1, act: 'delete', id: id, csrf_token: CSRF_TOKEN },
            success: function(resp) { showToast(resp.message, 'success'); setTimeout(() => location.reload(), 1000); }
        });
    });
}
</script>

