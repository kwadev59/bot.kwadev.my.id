<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen User</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
            <i class="bi bi-plus-lg"></i> Tambah User
        </button>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['tipe']; ?> alert-dismissible fade show" role="alert">
    <?= $_SESSION['flash']['pesan']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php unset($_SESSION['flash']); ?>
</div>
<?php endif; ?>

<!-- User Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Dibuat Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['users'])): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Tidak ada data user.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['users'] as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']); ?></td>
                                <td><?= htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?= htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y, H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                data-id="<?= $user['id']; ?>" 
                                                data-username="<?= htmlspecialchars($user['username']); ?>" 
                                                data-nama="<?= htmlspecialchars($user['nama_lengkap']); ?>" 
                                                data-role="<?= $user['role']; ?>"
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>disabled<?php endif; ?>>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="#" class="btn btn-outline-danger" 
                                           onclick="confirmDelete(<?= $user['id']; ?>, '<?= htmlspecialchars($user['username']); ?>')" 
                                           <?php if ($user['id'] == $_SESSION['user_id']): ?>disabled<?php endif; ?>>
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tambah User Modal -->
<div class="modal fade" id="tambahUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL; ?>/UserController/tambah" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tambahUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="tambahUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="tambahPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="tambahPassword" name="password" required>
                        <div class="form-text">Minimal 6 karakter</div>
                    </div>
                    <div class="mb-3">
                        <label for="tambahNamaLengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="tambahNamaLengkap" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label for="tambahRole" class="form-label">Role</label>
                        <select class="form-select" id="tambahRole" name="role" required>
                            <option value="viewer">Viewer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL; ?>/UserController/update" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="editId" name="id" value="">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Password (Kosongkan jika tidak ingin diubah)</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                        <div class="form-text">Minimal 6 karakter (kosongkan jika tidak ingin diubah)</div>
                    </div>
                    <div class="mb-3">
                        <label for="editNamaLengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="editNamaLengkap" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <select class="form-select" id="editRole" name="role" required>
                            <option value="viewer">Viewer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle edit modal
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            var nama = button.getAttribute('data-nama');
            var role = button.getAttribute('data-role');
            
            var modalId = editModal.querySelector('#editId');
            var modalUsername = editModal.querySelector('#editUsername');
            var modalNama = editModal.querySelector('#editNamaLengkap');
            var modalRole = editModal.querySelector('#editRole');
            
            modalId.value = id;
            modalUsername.value = username;
            modalNama.value = nama;
            modalRole.value = role;
        });
    }
});

function confirmDelete(userId, username) {
    if (confirm('Yakin ingin menghapus user "' + username + '"?')) {
        window.location.href = '<?= BASE_URL; ?>/UserController/hapus/' + userId;
    }
}
</script>