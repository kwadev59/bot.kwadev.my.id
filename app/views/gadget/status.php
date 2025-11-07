<?php
$drivers = $data['drivers'] ?? [];
$siteOptions = $data['site_options'] ?? [];
$statusOptions = $data['status_options'] ?? [];
$statusFilterOptions = $data['status_filter_options'] ?? [];

$selectedSite = $data['selected_site'] ?? 'ALL';
$selectedStatus = $data['selected_status'] ?? 'all';
$searchQuery = $data['search_query'] ?? '';
$perPage = (int)($data['per_page'] ?? 25);
$perPageOptions = $data['per_page_options'] ?? [25, 50, 100];
$pagination = $data['pagination'] ?? ['current' => 1, 'total_pages' => 1, 'total' => count($drivers), 'base_query' => ''];
$summary = $data['summary'] ?? ['total' => 0, 'with_status' => 0, 'without_status' => 0, 'normal' => 0, 'rusak' => 0];
$flash = $data['flash'] ?? null;

$currentPage = max(1, (int)($pagination['current'] ?? 1));
$totalPages = max(1, (int)($pagination['total_pages'] ?? 1));
$totalRecords = (int)($pagination['total'] ?? count($drivers));
$baseQuery = trim((string)($pagination['base_query'] ?? ''));
$queryPrefix = $baseQuery !== '' ? $baseQuery . '&' : '';

$buildPageUrl = function(int $pageNumber) use ($queryPrefix) {
    $pageNumber = max(1, $pageNumber);
    return '?' . $queryPrefix . 'page=' . $pageNumber;
};

$statusBadgeClass = function (?string $status) {
    if ($status === 'NORMAL') {
        return 'bg-success-subtle text-success';
    }
    if ($status === 'RUSAK') {
        return 'bg-danger-subtle text-danger';
    }
    return 'bg-secondary-subtle text-secondary';
};
?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h1 class="h3 mb-1">Status Gadget Driver</h1>
        <p class="text-muted mb-0">Tetapkan status perangkat driver (Normal atau Rusak) agar muncul pada halaman Monitoring TU.</p>
    </div>
    <div class="text-lg-end">
        <span class="badge bg-light text-dark fw-semibold px-3 py-2">
            Total Driver Terpantau: <?= number_format($summary['total'] ?? 0); ?>
        </span>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe'] ?? 'info', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['pesan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-xxl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase fs-12 mb-1">Total Driver</p>
                <h4 class="mb-1"><?= number_format($summary['total'] ?? 0); ?></h4>
                <span class="badge bg-primary-subtle text-primary">Aktif &amp; terdaftar</span>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase fs-12 mb-1">Sudah Diset</p>
                <h4 class="mb-1"><?= number_format($summary['with_status'] ?? 0); ?></h4>
                <span class="badge bg-success-subtle text-success">Normal: <?= number_format($summary['normal'] ?? 0); ?></span>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase fs-12 mb-1">Status Rusak</p>
                <h4 class="mb-1"><?= number_format($summary['rusak'] ?? 0); ?></h4>
                <span class="badge bg-danger-subtle text-danger">Perangkat perlu perhatian</span>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase fs-12 mb-1">Belum Diset</p>
                <h4 class="mb-1"><?= number_format($summary['without_status'] ?? 0); ?></h4>
                <span class="badge bg-secondary-subtle text-secondary">Segera tentukan status</span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="get" action="<?= htmlspecialchars(BASE_URL . '/GadgetStatusController', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="filterSite">Site</label>
                <select class="form-select" id="filterSite" name="site">
                    <?php foreach ($siteOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value); ?>" <?= $selectedSite === $value ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="filterStatus">Status Gadget</label>
                <select class="form-select" id="filterStatus" name="status">
                    <?php foreach ($statusFilterOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value); ?>" <?= $selectedStatus === $value ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="searchDriver">Cari Driver</label>
                <input
                    type="text"
                    class="form-control"
                    id="searchDriver"
                    name="search"
                    placeholder="Cari nama atau NPK"
                    value="<?= htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="perPage">Per Halaman</label>
                <select class="form-select" id="perPage" name="per_page">
                    <?php foreach ($perPageOptions as $option): ?>
                        <?php $option = (int)$option; ?>
                        <option value="<?= $option; ?>" <?= $perPage === $option ? 'selected' : ''; ?>>
                            <?= $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label fw-semibold d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
        <div>
            <strong><?= number_format($totalRecords); ?></strong> driver ditemukan
            <span class="text-muted">| Halaman <?= $currentPage; ?> dari <?= $totalPages; ?></span>
        </div>
        <div class="small text-muted">
            Status yang diatur di sini akan otomatis muncul di halaman Monitoring TU untuk driver terkait.
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;" class="text-center">#</th>
                    <th>Site &amp; Afdeling</th>
                    <th>NPK</th>
                    <th>Nama Driver</th>
                    <th>Status Saat Ini</th>
                    <th style="width: 320px;">Perbarui Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Tidak ada data driver sesuai filter yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                        $startNumber = ($currentPage - 1) * max(1, $perPage) + 1;
                        foreach ($drivers as $index => $driver):
                            $rowNumber = $startNumber + $index;
                            $status = strtoupper(trim((string)($driver['status'] ?? '')));
                            $notes = trim((string)($driver['notes'] ?? ''));
                            $updatedAt = $driver['updated_at'] ?? null;
                            $updatedBy = $driver['updated_by_name'] ?? null;
                            $updatedAtText = null;
                            if ($updatedAt) {
                                $timestamp = strtotime((string)$updatedAt);
                                $updatedAtText = $timestamp ? date('d M Y H:i', $timestamp) : $updatedAt;
                            }
                    ?>
                        <tr>
                            <td class="text-center"><?= $rowNumber; ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($driver['site'] ?? '-'); ?> / <?= htmlspecialchars($driver['afd'] ?? '-'); ?></div>
                                <div class="text-muted small text-uppercase">Driver</div>
                            </td>
                            <td><code><?= htmlspecialchars($driver['npk'] ?? '-'); ?></code></td>
                            <td><?= htmlspecialchars(ucwords(strtolower((string)($driver['nama'] ?? '-')))); ?></td>
                            <td>
                                <span class="badge <?= $statusBadgeClass($status); ?>">
                                    <?= $status !== '' ? htmlspecialchars($status) : 'BELUM DISET'; ?>
                                </span>
                                <?php if ($notes !== ''): ?>
                                    <div class="small text-muted mt-1">
                                        Catatan: <?= htmlspecialchars($notes); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($updatedAtText): ?>
                                    <div class="small text-muted">
                                        Diperbarui <?= htmlspecialchars($updatedAtText); ?>
                                        <?php if ($updatedBy): ?>
                                            oleh <?= htmlspecialchars($updatedBy); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= htmlspecialchars(BASE_URL . '/GadgetStatusController/update', ENT_QUOTES, 'UTF-8'); ?>" class="row g-2 align-items-center">
                                    <input type="hidden" name="employee_id" value="<?= (int)($driver['employee_id'] ?? 0); ?>">
                                    <input type="hidden" name="redirect_site" value="<?= htmlspecialchars($selectedSite); ?>">
                                    <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($selectedStatus); ?>">
                                    <input type="hidden" name="redirect_search" value="<?= htmlspecialchars($searchQuery); ?>">
                                    <input type="hidden" name="redirect_page" value="<?= $currentPage; ?>">
                                    <input type="hidden" name="redirect_per_page" value="<?= $perPage; ?>">
                                    <div class="col-lg-4 col-md-12">
                                        <select class="form-select form-select-sm" name="status" required>
                                            <option value="" disabled <?= $status === '' ? 'selected' : ''; ?>>Pilih status</option>
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?= htmlspecialchars($value); ?>" <?= strtolower($status) === strtolower($value) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-5 col-md-8">
                                        <input
                                            type="text"
                                            name="notes"
                                            class="form-control form-control-sm"
                                            placeholder="Catatan (opsional)"
                                            value="<?= htmlspecialchars($notes); ?>"
                                            maxlength="255">
                                    </div>
                                    <div class="col-lg-3 col-md-4 d-grid">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save me-1"></i> Simpan
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
            <div>Menampilkan halaman <?= $currentPage; ?> dari <?= $totalPages; ?></div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= $currentPage <= 1 ? '#' : $buildPageUrl($currentPage - 1); ?>" tabindex="-1">Previous</a>
                    </li>
                    <?php
                        $window = 2;
                        $start = max(1, $currentPage - $window);
                        $end = min($totalPages, $currentPage + $window);
                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $buildPageUrl(1) . '">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        for ($pageNumber = $start; $pageNumber <= $end; $pageNumber++) {
                            $active = $pageNumber === $currentPage ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $buildPageUrl($pageNumber) . '">' . $pageNumber . '</a></li>';
                        }
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . $buildPageUrl($totalPages) . '">' . $totalPages . '</a></li>';
                        }
                    ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= $currentPage >= $totalPages ? '#' : $buildPageUrl($currentPage + 1); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
