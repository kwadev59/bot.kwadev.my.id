<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Log Aktivitas Bot</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise"></i> Refresh
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

<!-- Log Aktivitas Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Level</th>
                        <th>Aktivitas</th>
                        <th>Detail</th>
                        <th>Pengguna</th>
                        <th>Nama File</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['logs'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Tidak ada log aktivitas bot.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['logs'] as $log): ?>
                            <tr class="<?= $log['log_level'] === 'ERROR' ? 'table-danger' : ($log['log_level'] === 'WARN' ? 'table-warning' : ''); ?>">
                                <td><?= date('d M Y, H:i:s', strtotime($log['timestamp'])); ?></td>
                                <td>
                                    <span class="badge bg-<?= $log['log_level'] === 'ERROR' ? 'danger' : ($log['log_level'] === 'WARN' ? 'warning' : 'info'); ?>">
                                        <?= htmlspecialchars($log['log_level']); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['activity']); ?></td>
                                <td>
                                    <?php if (!empty($log['details'])): ?>
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?= $log['id']; ?>">
                                            Lihat Detail
                                        </button>
                                        <div class="collapse" id="details-<?= $log['id']; ?>">
                                            <div class="card card-body mt-2 p-2">
                                                <?= htmlspecialchars($log['details']); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['user_number'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($log['file_name'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($data['total_halaman'] > 1): ?>
        <nav aria-label="Log pagination">
            <ul class="pagination justify-content-center">
                <?php if ($data['halaman_aktif'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $data['halaman_aktif'] - 1; ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $data['total_halaman']; $i++): ?>
                    <?php if ($i >= max(1, $data['halaman_aktif'] - 2) && $i <= min($data['total_halaman'], $data['halaman_aktif'] + 2)): ?>
                    <li class="page-item <?= ($i == $data['halaman_aktif']) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($data['halaman_aktif'] < $data['total_halaman']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $data['halaman_aktif'] + 1; ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>