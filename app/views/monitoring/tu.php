<?php
$defaultDate = date('Y-m-d', strtotime('-1 day'));
$selectedDate = $data['selected_date'] ?? $defaultDate;
$monitoring = $data['monitoring'] ?? [];
$summary = $data['summary'] ?? ['total_drivers' => 0, 'with_file' => 0, 'without_file' => 0];
$siteLabels = $data['site_labels'] ?? [];
$siteFileCounts = $summary['site_file_counts'] ?? [];
$siteDriverCounts = $summary['site_driver_counts'] ?? [];

$totalDrivers = (int)($summary['total_drivers'] ?? 0);
$withFile = (int)($summary['with_file'] ?? 0);
$withoutFile = (int)($summary['without_file'] ?? 0);
$fileTuBim = (int)($siteFileCounts['BIM1'] ?? 0);
$fileTuPps = (int)($siteFileCounts['PPS1'] ?? 0);
$driverBim = (int)($siteDriverCounts['BIM1'] ?? 0);
$driverPps = (int)($siteDriverCounts['PPS1'] ?? 0);
$achievement = $totalDrivers > 0 ? round(($withFile / $totalDrivers) * 100, 1) : 0;

$monthNames = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
];
$dateTime = DateTime::createFromFormat('Y-m-d', $selectedDate);
$selectedDateHuman = $dateTime
    ? $dateTime->format('d') . ' ' . ($monthNames[$dateTime->format('m')] ?? $dateTime->format('M')) . ' ' . $dateTime->format('Y')
    : $selectedDate;

$statCards = [
    [
        'title' => 'Total Driver',
        'value' => $totalDrivers,
        'icon'  => 'bi-people-fill',
        'class' => 'gradient-total',
        'meta'  => [
            ['label' => 'Sudah Kirim', 'icon' => 'bi-check2-circle', 'value' => $withFile],
            ['label' => 'Belum Kirim', 'icon' => 'bi-exclamation-circle', 'value' => $withoutFile],
        ],
    ],
    [
        'title' => 'Sudah Kirim',
        'value' => $withFile,
        'icon'  => 'bi-send-check-fill',
        'class' => 'gradient-tu',
        'meta'  => [
            ['label' => 'Pencapaian', 'icon' => 'bi-graph-up', 'value' => $achievement . '%'],
        ],
    ],
    [
        'title' => 'File TU BIM',
        'value' => $fileTuBim,
        'icon'  => 'bi-truck',
        'class' => 'gradient-bim',
        'meta'  => [
            ['label' => 'Driver BIM', 'icon' => 'bi-people-fill', 'value' => $driverBim],
        ],
    ],
    [
        'title' => 'File TU PPS',
        'value' => $fileTuPps,
        'icon'  => 'bi-truck-front',
        'class' => 'gradient-pps',
        'meta'  => [
            ['label' => 'Driver PPS', 'icon' => 'bi-people-fill', 'value' => $driverPps],
        ],
    ],
];
?>

<style>
.monitoring-table {
    font-size: clamp(0.9rem, 1.2vw, 1.02rem);
    table-layout: fixed;
}
.monitoring-table th,
.monitoring-table td {
    vertical-align: middle;
    white-space: nowrap;
    padding-top: 0.4rem;
    padding-bottom: 0.4rem;
}
.monitoring-table .col-no { width: 55px; }
.monitoring-table .col-afdeling { width: 90px; }
.monitoring-table .col-npk { width: 150px; }
.monitoring-table .col-name { min-width: 200px; white-space: normal; }
.monitoring-table .col-gadget { min-width: 190px; white-space: normal; }
.monitoring-table .col-status { width: 140px; }
.monitoring-table .col-file { min-width: 220px; white-space: normal; word-break: break-word; }
.monitoring-table .col-sent { width: 165px; }
.monitoring-table .col-notes { min-width: 180px; white-space: normal; }
.monitoring-table .file-tu-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
}
.monitoring-table .file-tu-meta .file-tu-link {
    white-space: nowrap;
}
.monitoring-table .file-tu-meta .file-tu-sender {
    font-size: 0.78rem;
    color: #6c757d;
}
.monitoring-table .badge {
    font-size: 0.78rem;
    padding: 0.35rem 0.65rem;
}
@media (max-width: 1400px) {
    .monitoring-table {
        font-size: 0.85rem;
    }
    .monitoring-table .col-name {
        min-width: 170px;
    }
}
</style>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h1 class="h3 mb-1">Monitoring File TU</h1>
        <p class="text-muted mb-0">Pantau pengiriman file TU harian untuk driver SITE BIM1 dan PPS1.</p>
    </div>
    <div class="text-lg-end">
        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2">
            <i class="bi bi-calendar-event me-2"></i>Tanggal: <?= htmlspecialchars($selectedDateHuman); ?>
        </span>
    </div>
</div>

<form method="get" action="<?= BASE_URL; ?>/TuMonitoringController" class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <label for="tanggal" class="form-label fw-semibold mb-0">Pilih Tanggal</label>
                <small class="text-muted d-block">Data akan difilter berdasarkan nama file.</small>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <input
                    type="text"
                    class="form-control"
                    id="tanggal"
                    name="tanggal"
                    value="<?= htmlspecialchars($selectedDate); ?>"
                    placeholder="YYYY-MM-DD"
                    autocomplete="off"
                    required>
            </div>
            <div class="col-sm-12 col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
                <a href="<?= BASE_URL; ?>/TuMonitoringController?tanggal=<?= date('Y-m-d'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Hari Ini
                </a>
            </div>
        </div>
    </div>
</form>

<div class="row dashboard-stats g-3 mb-4">
    <?php foreach ($statCards as $card): ?>
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6">
            <div class="card stat-card h-100 text-white <?= htmlspecialchars($card['class']); ?>">
                <div class="card-body">
                    <div class="stat-card__header">
                        <div>
                            <p class="stat-card__label mb-1"><?= htmlspecialchars($card['title']); ?></p>
                            <p class="stat-card__value mb-0"><?= htmlspecialchars($card['value']); ?></p>
                        </div>
                        <span class="stat-card__icon">
                            <i class="bi <?= htmlspecialchars($card['icon']); ?>"></i>
                        </span>
                    </div>
                    <?php if (!empty($card['meta'])): ?>
                        <div class="stat-card__meta">
                            <?php foreach ($card['meta'] as $meta): ?>
                                <span>
                                    <i class="bi <?= htmlspecialchars($meta['icon']); ?>"></i>
                                    <?= htmlspecialchars($meta['label']); ?>: <strong><?= htmlspecialchars($meta['value']); ?></strong>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ((int)($summary['total_drivers'] ?? 0) === 0): ?>
    <div class="alert alert-info shadow-sm" role="alert">
        Belum ada data driver aktif dengan jabatan DRIVER untuk konfigurasi monitoring yang ditentukan.
    </div>
<?php else: ?>
    <?php foreach ($monitoring as $site => $afdelings): ?>
        <?php
            $employeeCount = 0;
            foreach ($afdelings as $rows) {
                $employeeCount += count($rows);
            }
            if ($employeeCount === 0) {
                continue;
            }
            $afdelingList = implode(', ', array_keys($afdelings));
            $siteLabel = $siteLabels[$site] ?? $site;
            $cardId = 'tu-site-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($site));
            $allowExport = in_array($site, ['BIM1', 'PPS1'], true);
        ?>
        <div class="card shadow-sm border-0 mb-4" id="<?= htmlspecialchars($cardId); ?>" data-site="<?= htmlspecialchars($site); ?>">
            <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="h5 mb-0"><?= htmlspecialchars($siteLabel); ?></h2>
                    <span class="text-muted small">Total driver: <?= $employeeCount; ?> | Afdeling: <?= htmlspecialchars($afdelingList); ?></span>
                </div>
                <?php if ($allowExport): ?>
                    <div class="d-flex">
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm export-tu-btn"
                            data-export-target="<?= htmlspecialchars($cardId); ?>"
                            data-export-site="<?= htmlspecialchars($site); ?>"
                            data-export-date="<?= htmlspecialchars($selectedDate); ?>">
                            <i class="bi bi-image me-1"></i> Export JPG
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 monitoring-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center col-no">No</th>
                            <th class="col-afdeling">Afdeling</th>
                            <th class="col-npk">NPK Driver</th>
                            <th class="col-name">Nama</th>
                            <th class="col-gadget">Status Gadget</th>
                            <th class="text-center col-status">Status Kirim</th>
                            <th class="col-file">File TU</th>
                            <th class="col-sent">Dikirim Pada</th>
                            <th class="col-notes">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $rowNumber = 1;
                            foreach ($afdelings as $afdeling => $rows):
                                if (empty($rows)) {
                                    continue;
                                }
                                foreach ($rows as $entry):
                                    $employee = $entry['employee'] ?? [];
                                    $tu = $entry['tu'] ?? null;
                                    $hasTu = !empty($tu);
                                    $statusBadgeClass = $hasTu ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                                    $statusText = $hasTu ? 'SUDAH KIRIM' : 'BELUM KIRIM';
                                    $tuFileName = $hasTu ? ($tu['file_name'] ?? null) : null;
                                    $tuDownloadUrl = $hasTu && !empty($tu['id']) ? BASE_URL . '/LaporanController/download/' . (int)$tu['id'] : null;
                                    if ($hasTu) {
                                        $senderName = trim((string)($tu['nama_lengkap'] ?? ''));
                                        $senderNumber = trim((string)($tu['sender_number'] ?? ''));
                                        if ($senderName !== '' && $senderNumber !== '') {
                                            $tuSenderLabel = $senderName . ' (' . $senderNumber . ')';
                                        } elseif ($senderName !== '') {
                                            $tuSenderLabel = $senderName;
                                        } elseif ($senderNumber !== '') {
                                            $tuSenderLabel = $senderNumber;
                                        } else {
                                            $tuSenderLabel = null;
                                        }
                                    } else {
                                        $tuSenderLabel = null;
                                    }
                                    $timeliness = $entry['timeliness'] ?? null;
                                    $sentAt = $hasTu ? ($tu['submission_timestamp'] ?? 0) : 0;
                                    $sentAtText = $sentAt > 0 ? date('d M Y, H:i', $sentAt) : '-';
                                    $tuNotes = $hasTu ? trim((string)($tu['validation_notes'] ?? '')) : '';
                                    $employeeName = isset($employee['nama']) ? ucwords(strtolower((string)$employee['nama'])) : '-';
                                    $gadgetStatus = $entry['gadget_status'] ?? null;
                                    $gadgetStatusLabel = strtoupper(trim((string)($gadgetStatus['status'] ?? '')));
                                    $gadgetNotes = trim((string)($gadgetStatus['notes'] ?? ''));
                                    $gadgetUpdatedAt = $gadgetStatus['updated_at'] ?? null;
                                    $gadgetUpdatedAtText = null;
                                    if ($gadgetUpdatedAt) {
                                        $gadgetTimestamp = strtotime((string)$gadgetUpdatedAt);
                                        $gadgetUpdatedAtText = $gadgetTimestamp ? date('d M Y H:i', $gadgetTimestamp) : $gadgetUpdatedAt;
                                    }
                                    if ($gadgetStatusLabel === 'NORMAL') {
                                        $gadgetBadgeClass = 'bg-success-subtle text-success';
                                        $gadgetStatusText = 'Normal';
                                    } elseif ($gadgetStatusLabel === 'RUSAK') {
                                        $gadgetBadgeClass = 'bg-danger-subtle text-danger';
                                        $gadgetStatusText = 'Rusak';
                                    } else {
                                        $gadgetBadgeClass = 'bg-secondary-subtle text-secondary';
                                        $gadgetStatusText = 'Belum Diset';
                                    }
                        ?>
                            <tr>
                                <td class="text-center col-no"><?= $rowNumber++; ?></td>
                                <td class="col-afdeling"><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($afdeling); ?></span></td>
                                <td class="col-npk"><code><?= htmlspecialchars($employee['npk'] ?? '-'); ?></code></td>
                                <td class="col-name"><?= htmlspecialchars($employeeName); ?></td>
                                <td class="col-gadget">
                                    <span class="badge <?= htmlspecialchars($gadgetBadgeClass); ?>">
                                        <?= htmlspecialchars($gadgetStatusText); ?>
                                    </span>
                                    <?php if ($gadgetNotes !== ''): ?>
                                        <div class="small text-muted mt-1">Catatan: <?= htmlspecialchars($gadgetNotes); ?></div>
                                    <?php endif; ?>
                                    <?php if ($gadgetUpdatedAtText): ?>
                                        <div class="small text-muted">Update: <?= htmlspecialchars($gadgetUpdatedAtText); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center col-status">
                                    <span class="badge <?= $statusBadgeClass; ?>"><?= $statusText; ?></span>
                                </td>
                                <td class="col-file">
                                    <?php if ($hasTu && $tuFileName): ?>
                                        <div class="file-tu-meta">
                                            <span class="fw-semibold">
                                                <?php if ($tuDownloadUrl): ?>
                                                    <a href="<?= htmlspecialchars($tuDownloadUrl); ?>" class="link-primary file-tu-link">
                                                        <?= htmlspecialchars($tuFileName); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($tuFileName); ?>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($tuSenderLabel): ?>
                                                <span class="file-tu-sender">
                                                    <?= htmlspecialchars($tuSenderLabel); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-sent"><?= htmlspecialchars($sentAtText); ?></td>
                                <td class="col-notes">
                                    <?php if ($hasTu): ?>
                                        <?php if (!empty($timeliness)): ?>
                                            <span class="badge <?= htmlspecialchars($timeliness['badge_class']); ?>">
                                                <?php if (!empty($timeliness['icon'])): ?>
                                                    <i class="bi <?= htmlspecialchars($timeliness['icon']); ?> me-1"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($timeliness['label']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Tanggal tidak diketahui</span>
                                        <?php endif; ?>
                                        <?php if ($tuNotes !== ''): ?>
                                            <div class="small text-muted mt-1"><?= htmlspecialchars($tuNotes); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">BELUM KIRIM</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                                endforeach;
                            endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dateInput = document.getElementById('tanggal');
    if (dateInput && typeof flatpickr === 'function') {
        flatpickr(dateInput, {
            dateFormat: 'Y-m-d',
            defaultDate: '<?= htmlspecialchars($selectedDate, ENT_QUOTES); ?>',
            allowInput: true,
        });
    }

    var selectedDate = '<?= htmlspecialchars($selectedDate, ENT_QUOTES); ?>';
    var exportButtons = document.querySelectorAll('.export-tu-btn');

    if (exportButtons.length > 0 && typeof html2canvas !== 'function') {
        console.warn('html2canvas belum dimuat sehingga fitur export tidak aktif.');
        exportButtons.forEach(function (button) {
            button.classList.add('disabled');
            button.setAttribute('title', 'Fitur export tidak tersedia saat ini.');
        });
        return;
    }

    var sanitizeForFileName = function (value) {
        return (value || '')
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9\-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || 'monitoring-tu';
    };

    var setLoadingState = function (button, isLoading) {
        if (!button) {
            return;
        }
        if (isLoading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Memproses...';
        } else {
            button.disabled = false;
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
        }
    };

    exportButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-export-target');
            var siteCode = button.getAttribute('data-export-site') || 'site';
            var exportDate = button.getAttribute('data-export-date') || selectedDate;
            var target = document.getElementById(targetId);

            if (!target) {
                console.error('Elemen target export tidak ditemukan untuk ID:', targetId);
                return;
            }

            setLoadingState(button, true);

            html2canvas(target, {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                windowWidth: document.documentElement.scrollWidth,
            }).then(function (canvas) {
                var link = document.createElement('a');
                var fileName = [
                    'monitoring',
                    'tu',
                    sanitizeForFileName(siteCode),
                    sanitizeForFileName(exportDate)
                ].join('-') + '.jpg';

                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.download = fileName;
                link.click();
            }).catch(function (error) {
                console.error('Gagal mengekspor JPG:', error);
                alert('Terjadi kesalahan saat membuat file JPG. Silakan coba lagi.');
            }).finally(function () {
                setLoadingState(button, false);
            });
        });
    });
});
</script>
