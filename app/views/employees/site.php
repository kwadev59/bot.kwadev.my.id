<?php
$employees = $data['employees'] ?? [];
$summary = $data['summary'] ?? ['total' => 0, 'active' => 0, 'inactive' => 0];
$flash = $data['flash'] ?? null;
$searchQuery = $data['search_query'] ?? '';
$siteCode = $data['site_code'] ?? '';
$siteLabel = $data['site_label'] ?? 'Karyawan';
$routePath = $data['route_path'] ?? 'EmployeeBimController';
$pagination = $data['pagination'] ?? [
    'current'     => 1,
    'per_page'    => 10,
    'total_pages' => 1,
    'total'       => count($employees),
    'has_prev'    => false,
    'has_next'    => false,
    'prev_page'   => null,
    'next_page'   => null,
];
$siteOptions = $data['site_options'] ?? ['BIM1' => 'BIM1', 'PPS1' => 'PPS1'];

function formatStatusBadge($isActive): string {
    $isActive = !empty($isActive);
    $class = $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
    $label = $isActive ? 'Aktif' : 'Nonaktif';
    return sprintf('<span class="badge %s">%s</span>', htmlspecialchars($class), htmlspecialchars($label));
}
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($siteLabel); ?></h1>
        <p class="text-muted mb-0">Kelola data karyawan <?= htmlspecialchars($siteCode); ?>.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#employeeModal"
                data-mode="create"
                data-site="<?= htmlspecialchars($siteCode); ?>"
                data-page="<?= (int) ($pagination['current'] ?? 1); ?>">
            <i class="bi bi-plus-circle me-1"></i> Tambah Karyawan
        </button>
        <button type="button"
                class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#employeeModal"
                data-mode="import"
                data-site="<?= htmlspecialchars($siteCode); ?>"
                data-page="<?= (int) ($pagination['current'] ?? 1); ?>">
            <i class="bi bi-upload me-1"></i> Import CSV/XLSX
        </button>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-uppercase text-muted small">Total Karyawan</span>
                <h2 class="display-6 mb-0"><?= (int) ($summary['total'] ?? 0); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-uppercase text-muted small">Aktif</span>
                <h2 class="display-6 mb-1 text-success"><?= (int) ($summary['active'] ?? 0); ?></h2>
                <span class="badge bg-success-subtle text-success">
                    <?php
                    $total = max(1, (int) ($summary['total'] ?? 0));
                    $percentage = ($summary['active'] ?? 0) > 0
                        ? round(($summary['active'] / $total) * 100, 1)
                        : 0;
                    echo htmlspecialchars($percentage) . '%';
                    ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-uppercase text-muted small">Tidak Aktif</span>
                <h2 class="display-6 mb-1 text-danger"><?= (int) ($summary['inactive'] ?? 0); ?></h2>
                <span class="badge bg-danger-subtle text-danger">
                    <?php
                    $total = max(1, (int) ($summary['total'] ?? 0));
                    $percentage = ($summary['inactive'] ?? 0) > 0
                        ? round(($summary['inactive'] / $total) * 100, 1)
                        : 0;
                    echo htmlspecialchars($percentage) . '%';
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<form method="get" action="<?= BASE_URL; ?>/<?= htmlspecialchars($routePath); ?>" class="mb-4">
    <div class="input-group">
        <input
            type="search"
            class="form-control"
            name="q"
            placeholder="Cari nama, NPK, SITE, atau AFD"
            value="<?= htmlspecialchars($searchQuery); ?>"
            aria-label="Cari karyawan">
        <?php if ($searchQuery !== ''): ?>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL; ?>/<?= htmlspecialchars($routePath); ?>">
                Reset
            </a>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-search me-1"></i> Cari
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <h2 class="h5 mb-2 mb-md-0">Daftar <?= htmlspecialchars($siteCode); ?></h2>
            <?php
                $totalEmployees = (int)($pagination['total'] ?? 0);
                $currentPage = (int)($pagination['current'] ?? 1);
                $perPage = (int)($pagination['per_page'] ?? 10);
                $rangeStart = $totalEmployees > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
                $rangeEnd = $totalEmployees > 0 ? min($totalEmployees, $currentPage * $perPage) : 0;
            ?>
            <span class="text-muted small">
                Menampilkan <?= $totalEmployees > 0 ? htmlspecialchars($rangeStart) . '&ndash;' . htmlspecialchars($rangeEnd) : '0'; ?>
                dari <strong><?= $totalEmployees; ?></strong> data
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 60px;">No</th>
                    <th>SITE</th>
                    <th>AFD</th>
                    <th>NPK</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th class="text-center">Aktif</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data karyawan.</td>
                    </tr>
                <?php else: ?>
                    <?php
                        $rowNumberBase = max(0, ($currentPage - 1) * $perPage);
                    ?>
                    <?php foreach ($employees as $index => $employee): ?>
                        <tr>
                            <td class="text-center"><?= $rowNumberBase + $index + 1; ?></td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($employee['site'] ?? '-'); ?></span></td>
                            <td><?= htmlspecialchars($employee['afd'] ?? '-'); ?></td>
                            <td><code><?= htmlspecialchars($employee['npk'] ?? '-'); ?></code></td>
                            <td><?= htmlspecialchars(ucwords(strtolower($employee['nama'] ?? '-'))); ?></td>
                            <td><?= htmlspecialchars($employee['jabatan'] ?? '-'); ?></td>
                            <td class="text-center"><?= formatStatusBadge($employee['aktif'] ?? false); ?></td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#employeeModal"
                                        data-mode="edit"
                                        data-id="<?= (int) ($employee['id'] ?? 0); ?>"
                                        data-site="<?= htmlspecialchars($employee['site'] ?? ''); ?>"
                                        data-afd="<?= htmlspecialchars($employee['afd'] ?? ''); ?>"
                                        data-npk="<?= htmlspecialchars($employee['npk'] ?? ''); ?>"
                                        data-nama="<?= htmlspecialchars($employee['nama'] ?? ''); ?>"
                                        data-jabatan="<?= htmlspecialchars($employee['jabatan'] ?? ''); ?>"
                                        data-aktif="<?= !empty($employee['aktif']) ? '1' : '0'; ?>"
                                        data-page="<?= (int) ($pagination['current'] ?? 1); ?>">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pagination['total_pages'] ?? 1) > 1): ?>
    <?php
        $current = (int)($pagination['current'] ?? 1);
        $totalPages = (int)($pagination['total_pages'] ?? 1);
        $windowStart = max(1, $current - 2);
        $windowEnd = min($totalPages, $current + 2);
        $baseUrl = BASE_URL . '/' . $routePath;
        $baseParams = [];
        if ($searchQuery !== '') {
            $baseParams['q'] = $searchQuery;
        }
    ?>
    <nav aria-label="Navigasi halaman <?= htmlspecialchars($siteLabel); ?>" class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= empty($pagination['has_prev']) ? 'disabled' : ''; ?>">
                <?php
                    $prevParams = $baseParams;
                    if (!empty($pagination['has_prev'])) {
                        $prevParams['page'] = $pagination['prev_page'];
                    }
                ?>
                <a class="page-link"
                   href="<?= !empty($pagination['has_prev']) ? $baseUrl . '?' . http_build_query($prevParams) : '#'; ?>"
                   tabindex="<?= empty($pagination['has_prev']) ? '-1' : '0'; ?>"
                   aria-label="Sebelumnya">
                    &laquo;
                </a>
            </li>
            <?php for ($page = $windowStart; $page <= $windowEnd; $page++): ?>
                <?php
                    $pageParams = $baseParams;
                    $pageParams['page'] = $page;
                ?>
                <li class="page-item <?= $page === $current ? 'active' : ''; ?>">
                    <a class="page-link" href="<?= $baseUrl; ?>?<?= http_build_query($pageParams); ?>">
                        <?= $page; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= empty($pagination['has_next']) ? 'disabled' : ''; ?>">
                <?php
                    $nextParams = $baseParams;
                    if (!empty($pagination['has_next'])) {
                        $nextParams['page'] = $pagination['next_page'];
                    }
                ?>
                <a class="page-link"
                   href="<?= !empty($pagination['has_next']) ? $baseUrl . '?' . http_build_query($nextParams) : '#'; ?>"
                   tabindex="<?= empty($pagination['has_next']) ? '-1' : '0'; ?>"
                   aria-label="Berikutnya">
                    &raquo;
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal Kelola Karyawan -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h1 class="modal-title fs-5" id="employeeModalLabel">Kelola Karyawan</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills mb-3" id="employeeModalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="employee-add-tab" data-bs-toggle="pill" data-bs-target="#employee-add-pane" type="button" role="tab" aria-controls="employee-add-pane" aria-selected="true">
                            Tambah Manual
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="employee-import-tab" data-bs-toggle="pill" data-bs-target="#employee-import-pane" type="button" role="tab" aria-controls="employee-import-pane" aria-selected="false">
                            Import CSV/XLSX
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="employee-add-pane" role="tabpanel" aria-labelledby="employee-add-tab">
                        <form method="post"
                              action="<?= BASE_URL; ?>/<?= htmlspecialchars($routePath); ?>/store"
                              id="employeeForm"
                              data-store-action="<?= BASE_URL; ?>/<?= htmlspecialchars($routePath); ?>/store"
                              data-update-action="<?= BASE_URL; ?>/<?= htmlspecialchars($routePath); ?>/update">
                            <input type="hidden" name="id" id="employeeId">
                            <input type="hidden" name="redirect_page" id="employeeRedirectPage" value="<?= (int)($pagination['current'] ?? 1); ?>">
                            <input type="hidden" name="redirect_site" id="employeeRedirectSite" value="<?= htmlspecialchars($siteCode); ?>">
                            <input type="hidden" name="redirect_query" value="<?= htmlspecialchars($searchQuery); ?>">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="employeeSite" class="form-label">SITE</label>
                                    <select class="form-select" id="employeeSite" name="site" required>
                                        <?php foreach ($siteOptions as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value); ?>" <?= strtoupper($value) === strtoupper($siteCode) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="employeeAfd" class="form-label">AFD</label>
                                    <input type="text" class="form-control" id="employeeAfd" name="afd" placeholder="Contoh: OA" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="employeeNpk" class="form-label">NPK</label>
                                    <input type="text" class="form-control" id="employeeNpk" name="npk" placeholder="Contoh: 1425641" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="employeeJabatan" class="form-label">Jabatan</label>
                                    <input type="text" class="form-control" id="employeeJabatan" name="jabatan" placeholder="Contoh: MANDOR">
                                </div>
                                <div class="col-12">
                                    <label for="employeeNama" class="form-label">Nama</label>
                                    <input type="text" class="form-control" id="employeeNama" name="nama" placeholder="Contoh: HAMRULLAH" required>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="employeeAktif" name="aktif" checked>
                                        <label class="form-check-label" for="employeeAktif">
                                            Aktif
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary" id="employeeModalSubmit">
                                    Simpan Manual
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="employee-import-pane" role="tabpanel" aria-labelledby="employee-import-tab">
                        <form method="post" action="<?= BASE_URL; ?>/<?= htmlspecialchars($routePath); ?>/import" enctype="multipart/form-data" id="employeeImportForm">
                            <input type="hidden" name="redirect_page" id="employeeImportRedirectPage" value="<?= (int)($pagination['current'] ?? 1); ?>">
                            <input type="hidden" name="redirect_site" id="employeeImportRedirectSite" value="<?= htmlspecialchars($siteCode); ?>">
                            <input type="hidden" name="redirect_query" id="employeeImportRedirectQuery" value="<?= htmlspecialchars($searchQuery); ?>">
                            <p class="text-muted small">
                                Pilih file CSV atau XLSX dengan kolom: <code>site</code>, <code>afd</code>, <code>npk</code>, <code>nama</code>, <code>jabatan</code>, <code>aktif</code>. Nilai aktif gunakan Y/N atau 1/0.
                            </p>
                            <div class="mb-3">
                                <label for="employeeImportFile" class="form-label">File karyawan</label>
                                <input type="file" class="form-control" id="employeeImportFile" name="employee_file" accept=".csv,.xlsx" required>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-outline-primary" id="employeeModalImportBtn">
                                    Import File
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeeModal = document.getElementById('employeeModal');
    if (!employeeModal || typeof bootstrap === 'undefined') {
        return;
    }

    const manualForm = document.getElementById('employeeForm');
    const importForm = document.getElementById('employeeImportForm');

    const siteField = document.getElementById('employeeSite');
    const afdField = document.getElementById('employeeAfd');
    const npkField = document.getElementById('employeeNpk');
    const namaField = document.getElementById('employeeNama');
    const jabatanField = document.getElementById('employeeJabatan');
    const aktifField = document.getElementById('employeeAktif');
    const idField = document.getElementById('employeeId');

    const modalLabel = document.getElementById('employeeModalLabel');
    const submitButton = document.getElementById('employeeModalSubmit');

    const redirectPageField = document.getElementById('employeeRedirectPage');
    const redirectSiteField = document.getElementById('employeeRedirectSite');
    const importRedirectPageField = document.getElementById('employeeImportRedirectPage');
    const importRedirectSiteField = document.getElementById('employeeImportRedirectSite');

    const addTabTriggerEl = document.getElementById('employee-add-tab');
    const importTabTriggerEl = document.getElementById('employee-import-tab');
    const addTab = addTabTriggerEl ? bootstrap.Tab.getOrCreateInstance(addTabTriggerEl) : null;
    const importTab = importTabTriggerEl ? bootstrap.Tab.getOrCreateInstance(importTabTriggerEl) : null;

    employeeModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const mode = button ? (button.getAttribute('data-mode') || 'create') : 'create';
        const buttonSite = button ? (button.getAttribute('data-site') || '').toUpperCase() : '';
        const buttonPage = button ? (button.getAttribute('data-page') || '1') : '1';

        if (manualForm) {
            manualForm.reset();
            redirectPageField.value = buttonPage;
            redirectSiteField.value = buttonSite || redirectSiteField.value || siteField.value;
        }
        if (importForm) {
            importForm.reset();
            importRedirectPageField.value = buttonPage;
            importRedirectSiteField.value = buttonSite || importRedirectSiteField.value || '<?= htmlspecialchars($siteCode); ?>';
        }

        if (mode === 'edit') {
            if (addTab) addTab.show();
            modalLabel.textContent = 'Edit Karyawan';
            submitButton.textContent = 'Perbarui';
            manualForm.setAttribute('action', manualForm.dataset.updateAction);

            idField.value = button.getAttribute('data-id') || '';
            siteField.value = button.getAttribute('data-site') || siteField.value;
            afdField.value = button.getAttribute('data-afd') || '';
            npkField.value = button.getAttribute('data-npk') || '';
            namaField.value = button.getAttribute('data-nama') || '';
            jabatanField.value = button.getAttribute('data-jabatan') || '';
            aktifField.checked = (button.getAttribute('data-aktif') === '1');

            redirectSiteField.value = (button.getAttribute('data-site') || siteField.value).toUpperCase();
        } else if (mode === 'import') {
            if (importTab) importTab.show();
            modalLabel.textContent = 'Import Karyawan';
            submitButton.textContent = 'Simpan Manual';
            manualForm.setAttribute('action', manualForm.dataset.storeAction);
        } else {
            if (addTab) addTab.show();
            modalLabel.textContent = 'Tambah Karyawan';
            submitButton.textContent = 'Simpan Manual';
            manualForm.setAttribute('action', manualForm.dataset.storeAction);
            siteField.value = buttonSite || siteField.value || '<?= htmlspecialchars($siteCode); ?>';
        }
    });

    if (manualForm) {
        manualForm.addEventListener('submit', function () {
            redirectSiteField.value = (siteField.value || '<?= htmlspecialchars($siteCode); ?>').toUpperCase();
        });
    }
});
</script>
