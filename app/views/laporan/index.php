<?php $isValidPage = ($data['status_laporan'] ?? '') === 'valid'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <h1 class="h3 mb-0 page-title">Laporan <?= ucfirst($data['status_laporan']); ?></h1>
    <div class="btn-group">
        <a href="<?= BASE_URL; ?>/LaporanController/valid" class="btn <?= $data['status_laporan'] === 'valid' ? 'btn-primary' : 'btn-outline-primary'; ?>">Valid</a>
        <a href="<?= BASE_URL; ?>/LaporanController/invalid" class="btn <?= $data['status_laporan'] === 'invalid' ? 'btn-primary' : 'btn-outline-primary'; ?>">Invalid</a>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= $_SESSION['flash']['tipe']; ?> alert-dismissible fade show mb-4" role="alert">
    <?= $_SESSION['flash']['pesan']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php unset($_SESSION['flash']); ?>
</div>
<?php endif; ?>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($data['filters']['search'] ?? ''); ?>" placeholder="Cari nama file atau pengirim...">
                </div>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit"><i class="bi bi-search me-1"></i> Cari</button>
                <a href="<?= BASE_URL; ?>/LaporanController/<?= $data['status_laporan']; ?>" class="btn btn-secondary flex-fill"><i class="bi bi-arrow-clockwise me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Laporan Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-2">
                        <a href="?sort=submission_date&dir=<?= ($data['sort']['by'] === 'submission_date' && $data['sort']['dir'] === 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?= urlencode($data['filters']['search'] ?? ''); ?>" class="text-decoration-none">
                            Tanggal Kirim <i class="bi bi-arrow-<?= ($data['sort']['by'] === 'submission_date') ? (($data['sort']['dir'] === 'ASC') ? 'up' : 'down') : 'up'; ?>"></i>
                        </a>
                    </th>
                    <th class="py-2">
                        <a href="?sort=tanggal&dir=<?= ($data['sort']['by'] === 'tanggal' && $data['sort']['dir'] === 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?= urlencode($data['filters']['search'] ?? ''); ?>" class="text-decoration-none">
                            Tanggal File <i class="bi bi-arrow-<?= ($data['sort']['by'] === 'tanggal') ? (($data['sort']['dir'] === 'ASC') ? 'up' : 'down') : 'up'; ?>"></i>
                        </a>
                    </th>
                    <?php if ($isValidPage): ?>
                    <th class="py-2">Ketepatan Kirim</th>
                    <th class="py-2 text-center">Jml Unduh</th>
                    <?php endif; ?>
                    <th class="py-2">
                        <a href="?sort=file_name&dir=<?= ($data['sort']['by'] === 'file_name' && $data['sort']['dir'] === 'ASC') ? 'DESC' : 'ASC'; ?>&search=<?= urlencode($data['filters']['search'] ?? ''); ?>" class="text-decoration-none">
                            Nama File <i class="bi bi-arrow-<?= ($data['sort']['by'] === 'file_name') ? (($data['sort']['dir'] === 'ASC') ? 'up' : 'down') : 'up'; ?>"></i>
                        </a>
                    </th>
                    <th class="py-2">Tipe</th>
                    <th class="py-2">Ukuran</th>
                    <th class="py-2">Pengirim</th>
                    <th class="py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['laporan'])): ?>
                    <tr>
                        <td colspan="<?= $isValidPage ? 9 : 7; ?>" class="text-center py-3 text-muted">Tidak ada data laporan <?= $data['status_laporan']; ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['laporan'] as $laporan): ?>
                        <tr>
                            <td class="align-middle py-2"><?= date('d M Y, H:i', strtotime($laporan['submission_date'])); ?></td>
                            <td class="align-middle py-2"><?= formatFileDate($laporan['tanggal'] ?? null); ?></td>
                            <?php if ($isValidPage): ?>
                            <td class="align-middle py-2">
                                <?php if (!empty($laporan['timeliness'])): ?>
                                    <span class="badge <?= htmlspecialchars($laporan['timeliness']['badge_class']); ?>">
                                        <?php if (!empty($laporan['timeliness']['icon'])): ?>
                                            <i class="bi <?= htmlspecialchars($laporan['timeliness']['icon']); ?> me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($laporan['timeliness']['label']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Tanggal tidak diketahui</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-center py-2"><?= $laporan['download_count'] ?? 0; ?></td>
                            <?php endif; ?>
                            <td class="align-middle py-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-<?= strtolower(pathinfo($laporan['file_name'], PATHINFO_EXTENSION)) === 'zip' ? 'zip' : 'text'; ?> me-2 text-muted"></i>
                                    <span><?= htmlspecialchars($laporan['file_name']); ?></span>
                                </div>
                            </td>
                            <td class="align-middle py-2">
                                <span class="badge bg-<?= $laporan['file_type'] === 'TRB' ? 'success' : ($laporan['file_type'] === 'TU' ? 'info' : ($laporan['file_type'] === 'AMANDARB' ? 'warning' : 'secondary')); ?>">
                                    <?= htmlspecialchars($laporan['file_type']); ?>
                                </span>
                            </td>
                            <td class="align-middle py-2"><?= formatBytes($laporan['file_size']); ?></td>
                            <td class="align-middle py-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-1 text-muted"></i>
                                    <?= htmlspecialchars($laporan['nama_lengkap'] ?? $laporan['sender_number']); ?>
                                </div>
                            </td>
                            <td class="align-middle text-center py-2">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL; ?>/LaporanController/download/<?= $laporan['id']; ?>" class="btn btn-outline-primary" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <?php if ($data['status_laporan'] === 'invalid'): ?>
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#renameModal" 
                                            data-id="<?= $laporan['id']; ?>" data-filename="<?= htmlspecialchars($laporan['file_name']); ?>" title="Perbaiki">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($data['pagination']['total_halaman'] > 1): ?>
<nav aria-label="Laporan pagination" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php $params = http_build_query(['search' => $data['filters']['search'], 'sort' => $data['sort']['by'], 'dir' => $data['sort']['dir']]); ?>
        
        <?php if ($data['pagination']['halaman_aktif'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $data['pagination']['halaman_aktif'] - 1; ?>&<?= $params; ?>">Previous</a>
        </li>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $data['pagination']['total_halaman']; $i++): ?>
            <?php if ($i >= max(1, $data['pagination']['halaman_aktif'] - 2) && $i <= min($data['pagination']['total_halaman'], $data['pagination']['halaman_aktif'] + 2)): ?>
            <li class="page-item <?= ($i == $data['pagination']['halaman_aktif']) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?= $i; ?>&<?= $params; ?>"><?= $i; ?></a>
            </li>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($data['pagination']['halaman_aktif'] < $data['pagination']['total_halaman']): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $data['pagination']['halaman_aktif'] + 1; ?>&<?= $params; ?>">Next</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="renameModalLabel">Perbaiki Nama File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL; ?>/LaporanController/rename" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="renameId" name="id" value="">
                    <div class="mb-3">
                        <label for="newFilename" class="form-label">Nama File Baru</label>
                        <input type="text" class="form-control" id="newFilename" name="new_filename" required>
                        <div class="form-text">Format file harus sesuai dengan pola TRB/TU/AmandaRB yang valid.</div>
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

<script>
// Handle rename modal
document.addEventListener('DOMContentLoaded', function() {
    var renameModal = document.getElementById('renameModal');
    if (renameModal) {
        renameModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var filename = button.getAttribute('data-filename');
            
            var modalId = renameModal.querySelector('#renameId');
            var modalFilename = renameModal.querySelector('#newFilename');
            
            modalId.value = id;
            modalFilename.value = filename;
        });
    }
});

// Format bytes helper function
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
</script>
