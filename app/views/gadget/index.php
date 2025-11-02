<?php
$type = $data['type'] ?? 'bim1';
$typeLabel = $data['type_label'] ?? 'Data Gadget';
$search = $data['search'] ?? '';
$perPage = (int)($data['per_page'] ?? 25);
$perPageOptions = $data['per_page_options'] ?? [25, 50, 100, 250];
$page = (int)($data['page'] ?? 1);
$totalPages = (int)($data['total_pages'] ?? 1);
$totalDevices = (int)($data['total_devices'] ?? 0);
$devices = $data['devices'] ?? [];
$templateFiles = $data['template_files'] ?? [];
$templateDownloads = $data['template_downloads'] ?? [];
$preservedQuery = $data['preserved_query'] ?? '';
$pageBaseUrl = BASE_URL . '/GadgetController/' . ($type === 'pps1' ? 'pps1' : 'bim1');

$lastImportedRaw = $data['last_imported_at'] ?? null;
$lastImportedDisplay = 'Belum pernah diperbarui.';
if ($lastImportedRaw) {
    try {
        $dt = new DateTime($lastImportedRaw);
        $lastImportedDisplay = 'Terakhir diperbarui: ' . $dt->format('d M Y \\p\\u\\k\\u\\l H:i');
    } catch (Exception $e) {
        $lastImportedDisplay = 'Terakhir diperbarui: ' . htmlspecialchars($lastImportedRaw, ENT_QUOTES, 'UTF-8');
    }
}

$queryConnector = $preservedQuery !== '' ? $preservedQuery . '&' : '';
$pageParam = function (int $pageNumber) use ($queryConnector): string {
    return '?' . $queryConnector . 'page=' . $pageNumber;
};
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-0"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-muted mb-0">Kelola master data gadget wilayah <?= htmlspecialchars(strtoupper($type), ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button
            type="button"
            class="btn btn-sm btn-primary fw-semibold"
            data-bs-toggle="modal"
            data-bs-target="#importGadgetModal">
            <i class="bi bi-upload me-1"></i> Import Data
        </button>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
<div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['tipe'] ?? 'info', ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['flash']['pesan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-start">
        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
        <div>
            <strong>Catatan:</strong> Setiap proses import akan menghapus seluruh data lama dan menggantinya dengan data baru.
            <div class="small text-muted mt-1"><?= $lastImportedDisplay; ?></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= htmlspecialchars($pageBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label for="searchGadget" class="form-label fw-semibold">Cari data</label>
                <input
                    type="text"
                    class="form-control"
                    id="searchGadget"
                    name="search"
                    placeholder="Cari berdasarkan IMEI, nama pengguna, PT, status..."
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-lg-2 col-md-4">
                <label for="perPage" class="form-label fw-semibold">Per halaman</label>
                <select class="form-select" id="perPage" name="per_page">
                    <?php foreach ($perPageOptions as $option): ?>
                        <?php $option = (int)$option; ?>
                        <option value="<?= $option; ?>" <?= $perPage === $option ? 'selected' : ''; ?>>
                            <?= $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label fw-semibold">&nbsp;</label>
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
            <div class="col-lg-3 col-md-4 text-lg-end">
                <label class="form-label fw-semibold d-block">Template Import</label>
                <div class="btn-group w-100 w-lg-auto">
                    <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Unduh Template
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($templateFiles as $format => $filename): ?>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center"
                                   href="<?= BASE_URL; ?>/GadgetController/downloadTemplate/<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>/<?= htmlspecialchars($format, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span><i class="bi bi-file-earmark-spreadsheet me-2"></i><?= strtoupper($format); ?> Template</span>
                                    <span class="badge bg-secondary"><?= (int)($templateDownloads[$format] ?? 0); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="text-muted">
                Total data: <strong><?= number_format($totalDevices); ?></strong>
            </div>
            <div class="text-muted small">
                Menampilkan halaman <?= $page; ?> dari <?= $totalPages; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">IMEI</th>
                        <th scope="col">Aplikasi</th>
                        <th scope="col">PT</th>
                        <th scope="col">AFD</th>
                        <th scope="col">NPK Pengguna</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Pos Title</th>
                        <th scope="col">Group Asset</th>
                        <th scope="col">Tipe Asset</th>
                        <th scope="col">Part Asset</th>
                        <th scope="col" class="text-end">Jumlah</th>
                        <th scope="col">Asal</th>
                        <th scope="col">Status</th>
                        <th scope="col">Catatan</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($devices)): ?>
                        <tr>
                            <td colspan="15" class="text-center py-4 text-muted">Tidak ada data gadget yang ditampilkan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($devices as $device): ?>
                            <tr>
                                <td><?= htmlspecialchars($device['imei'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['aplikasi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['pt'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['afd'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['npk_pengguna'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['nama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['pos_title'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['group_asset'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['tipe_asset'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['part_asset'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?= number_format((int)($device['jumlah'] ?? 0)); ?></td>
                                <td><?= htmlspecialchars($device['asal_desc'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['status_desc'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['note'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($device['action'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Navigasi halaman gadget" class="mt-3">
                <ul class="pagination justify-content-end mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                        <?php $prevHref = $page <= 1 ? '#' : htmlspecialchars($pageParam(max(1, $page - 1)), ENT_QUOTES, 'UTF-8'); ?>
                        <a class="page-link" href="<?= $prevHref; ?>">
                            &laquo;
                        </a>
                    </li>
                    <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?= htmlspecialchars($pageParam($i), ENT_QUOTES, 'UTF-8'); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                        <?php $nextHref = $page >= $totalPages ? '#' : htmlspecialchars($pageParam(min($totalPages, $page + 1)), ENT_QUOTES, 'UTF-8'); ?>
                        <a class="page-link" href="<?= $nextHref; ?>">
                            &raquo;
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="importGadgetModal" tabindex="-1" aria-labelledby="importGadgetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= BASE_URL; ?>/GadgetController/import/<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importGadgetLabel">Import Data Gadget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="gadgetFile" class="form-label fw-semibold">File Excel / CSV</label>
                        <input type="file" class="form-control" id="gadgetFile" name="gadget_file" accept=".csv,.xlsx" required>
                        <div class="form-text">Gunakan template yang tersedia (format .xlsx atau .csv). Header harus sesuai dengan template.</div>
                    </div>
                    <div class="alert alert-light border-start border-4 border-primary" role="alert">
                        <i class="bi bi-exclamation-triangle-fill text-primary me-2"></i>
                        Proses ini akan menghapus seluruh data gadget <?= htmlspecialchars(strtoupper($type), ENT_QUOTES, 'UTF-8'); ?> yang tersimpan sebelumnya.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
