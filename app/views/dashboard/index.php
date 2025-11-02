<?php
$botStatusData = $data['bot_status'] ?? [];
$botStatusText = $botStatusData['status'] ?? 'UNKNOWN';
$botStatusError = $botStatusData['error'] ?? null;
$botStatusIcon = 'bi-question-circle';
$botStatusColor = '#6c757d';
$botStatusTextClass = 'text-secondary';
$botStatusTimestampText = '';
$botStatusTimestampClass = 'd-block text-muted';

if ($botStatusError) {
    $botStatusText = 'ERROR';
    $botStatusIcon = 'bi-exclamation-triangle';
    $botStatusColor = '#ffc107';
    $botStatusTextClass = 'text-warning';
    $botStatusTimestampText = 'Error: ' . $botStatusError;
    $botStatusTimestampClass = 'd-block text-warning';
} else {
    if ($botStatusText === 'ONLINE') {
        $botStatusIcon = 'bi-power';
        $botStatusColor = '#28a745';
        $botStatusTextClass = 'text-success';
        if (!empty($botStatusData['last_update'])) {
            $botStatusTimestampText = 'Update: ' . $botStatusData['last_update'];
        } else {
            $botStatusTimestampText = '';
        }
    } elseif ($botStatusText === 'OFFLINE') {
        $botStatusIcon = 'bi-x-circle';
        $botStatusColor = '#dc3545';
        $botStatusTextClass = 'text-danger';
        if (!empty($botStatusData['timestamp'])) {
            $botStatusTimestampText = 'Sejak: ' . date('d M Y, H:i:s', strtotime($botStatusData['timestamp']));
        }
    }
}

if ($botStatusTimestampText === '') {
    $botStatusTimestampClass = '';
}
?>

<!-- Status Bot Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $botStatusIcon; ?> fs-4 me-3" id="botStatusIcon" style="color: <?= $botStatusColor; ?>;"></i>
                        <div>
                            <h5 class="card-title mb-0">Status WA Bot</h5>
                            <span class="<?= $botStatusTextClass; ?> fw-bold" id="botStatusText"><?= htmlspecialchars($botStatusText); ?></span>
                            <small class="<?= $botStatusTimestampClass; ?>" id="botStatusTimestamp"><?= htmlspecialchars($botStatusTimestampText); ?></small>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <button id="restartBotBtn" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Restart
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <h1 class="h3 mb-0 page-title">Dashboard</h1>
</div>

<!-- Statistik Cards -->
<?php
$statCards = [
    [
        'title' => 'File TRB',
        'value' => $data['total_trb'],
        'icon' => 'bi-file-earmark-text',
        'class' => 'gradient-trb',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['trb_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['trb_invalid']],
        ],
    ],
    [
        'title' => 'File TU',
        'value' => $data['total_tu'],
        'icon' => 'bi-truck',
        'class' => 'gradient-tu',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['tu_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['tu_invalid']],
        ],
    ],
    [
        'title' => 'File AmandaRB',
        'value' => $data['total_amandarb'],
        'icon' => 'bi-person-bounding-box',
        'class' => 'gradient-amandarb',
        'meta' => [],
    ],
    [
        'title' => 'File TPN',
        'value' => $data['total_tpn'],
        'icon' => 'bi-file-earmark-spreadsheet',
        'class' => 'gradient-tpn',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['tpn_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['tpn_invalid']],
        ],
    ],
    [
        'title' => 'File TR',
        'value' => $data['total_tr'],
        'icon' => 'bi-file-earmark-check',
        'class' => 'gradient-tr',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['tr_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['tr_invalid']],
        ],
    ],
    [
        'title' => 'File TO',
        'value' => $data['total_to'],
        'icon' => 'bi-file-earmark-pdf',
        'class' => 'gradient-to',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['to_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['to_invalid']],
        ],
    ],
    [
        'title' => 'File AMANTA',
        'value' => $data['total_amanta'],
        'icon' => 'bi-file-earmark-person',
        'class' => 'gradient-amanta',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['amanta_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['amanta_invalid']],
        ],
    ],
    [
        'title' => 'File AMANDA PANEN',
        'value' => $data['total_amanda_panen'],
        'icon' => 'bi-file-earmark-bar-graph',
        'class' => 'gradient-amanda-panen',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['amanda_panen_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['amanda_panen_invalid']],
        ],
    ],
    [
        'title' => 'File TIKA PLASMA',
        'value' => $data['total_tika_plasma'],
        'icon' => 'bi-file-earmark-word',
        'class' => 'gradient-tika-plasma',
        'meta' => [
            ['label' => 'Valid', 'icon' => 'bi-check-circle-fill', 'value' => $data['tika_plasma_valid']],
            ['label' => 'Invalid', 'icon' => 'bi-x-circle-fill', 'value' => $data['tika_plasma_invalid']],
        ],
    ],
    [
        'title' => 'Total Semua File',
        'value' => $data['total_files'],
        'icon' => 'bi-files',
        'class' => 'gradient-total',
        'meta' => [],
    ],
];
?>

<?php foreach (array_chunk($statCards, 4) as $cardsRow): ?>
    <div class="row dashboard-stats g-3 mb-4">
        <?php foreach ($cardsRow as $card): ?>
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6">
                <div class="card stat-card h-100 text-white <?= $card['class']; ?>">
                    <div class="card-body">
                        <div class="stat-card__header">
                            <div>
                                <p class="stat-card__label mb-1"><?= $card['title']; ?></p>
                                <p class="stat-card__value mb-0"><?= $card['value']; ?></p>
                            </div>
                            <span class="stat-card__icon">
                                <i class="bi <?= $card['icon']; ?>"></i>
                            </span>
                        </div>
                        <?php if (!empty($card['meta'])): ?>
                            <div class="stat-card__meta">
                                <?php foreach ($card['meta'] as $meta): ?>
                                    <span>
                                        <i class="bi <?= $meta['icon']; ?>"></i>
                                        <?= $meta['label']; ?>: <strong><?= $meta['value']; ?></strong>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<!-- Recent Submissions Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Laporan Terbaru</h5>
        <a href="<?= BASE_URL; ?>/LaporanController/valid" class="btn btn-primary btn-sm">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-2">Tanggal Kirim</th>
                    <th class="py-2">Tanggal File</th>
                    <th class="py-2">Nama File</th>
                    <th class="py-2">Pengirim</th>
                    <th class="py-2">Tipe</th>
                    <th class="py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['recent_submissions'])): ?>
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">Belum ada data laporan yang masuk.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['recent_submissions'] as $submission): ?>
                        <tr>
                            <td class="align-middle py-2"><?= date('d M Y, H:i', strtotime($submission['submission_date'])); ?></td>
                            <td class="align-middle py-2"><?= formatFileDate($submission['tanggal_file_date'] ?? $submission['tanggal'] ?? null); ?></td>
                            <td class="align-middle py-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-<?= strtolower(pathinfo($submission['file_name'], PATHINFO_EXTENSION)) === 'zip' ? 'zip' : 'text'; ?> me-2 text-muted"></i>
                                    <span><?= htmlspecialchars($submission['file_name']); ?></span>
                                </div>
                            </td>
                            <td class="align-middle py-2">
                                <?php
                                    $pengirim = !empty($submission['nama_lengkap']) ? $submission['nama_lengkap'] : $submission['sender_number'];
                                    echo htmlspecialchars($pengirim);
                                ?>
                            </td>
                            <td class="align-middle py-2">
                                <span class="badge bg-<?= $submission['file_type'] === 'TRB' ? 'success' : ($submission['file_type'] === 'TU' ? 'info' : ($submission['file_type'] === 'AMANDARB' ? 'warning' : 'secondary')); ?>">
                                    <?= htmlspecialchars($submission['file_type']); ?>
                                </span>
                            </td>
                            <td class="align-middle py-2">
                                <?php if ($submission['status'] == 'valid'): ?>
                                    <span class="badge bg-success">Valid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Invalid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    $tuMonitoringItems = $data['tu_monitoring_items'] ?? [];
    $tuSelectedDate = $data['tu_selected_date'] ?? date('Y-m-d');
    $tuSummaryValid = 0;
    foreach ($tuMonitoringItems as $item) {
        if (($item['status'] ?? '') === 'valid') {
            $tuSummaryValid++;
        }
    }
    $tuSummaryTotal = count($tuMonitoringItems);
    $tuSummaryInvalid = $tuSummaryTotal - $tuSummaryValid;
?>

<!-- Monitoring TU per Tanggal File -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">Monitoring TU per Tanggal File</h5>
            <small class="text-muted">Pilih tanggal file untuk melihat daftar TU yang harus dipantau</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="tuFileDatePicker" class="text-muted small mb-0">Tanggal File</label>
            <input
                type="date"
                class="form-control form-control-sm"
                id="tuFileDatePicker"
                value="<?= htmlspecialchars($tuSelectedDate, ENT_QUOTES, 'UTF-8'); ?>"
            >
            <span class="badge bg-warning text-dark">Percobaan</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3" id="tuMonitoringSummary">
            <div class="col-md-4">
                <div class="p-3 border rounded h-100 bg-light">
                    <div class="text-muted small mb-1">Total TU</div>
                    <div class="fs-4 fw-semibold" id="tuSummaryTotal"><?= $tuSummaryTotal; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded h-100 bg-light">
                    <div class="text-muted small mb-1">Valid</div>
                    <div class="fs-4 fw-semibold text-success" id="tuSummaryValid"><?= $tuSummaryValid; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded h-100 bg-light">
                    <div class="text-muted small mb-1">Invalid</div>
                    <div class="fs-4 fw-semibold text-danger" id="tuSummaryInvalid"><?= $tuSummaryInvalid; ?></div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-2">Tanggal File</th>
                        <th class="py-2">Tanggal Kirim</th>
                        <th class="py-2">Nama File</th>
                        <th class="py-2">Pengirim</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Catatan</th>
                    </tr>
                </thead>
                <tbody id="tuMonitoringTableBody">
                    <?php if (empty($tuMonitoringItems)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">Belum ada file TU pada tanggal ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tuMonitoringItems as $submission): ?>
                            <tr>
                                <td class="align-middle py-2"><?= formatFileDate($submission['tanggal_file_date'] ?? $submission['tanggal'] ?? null); ?></td>
                                <td class="align-middle py-2"><?= date('d M Y, H:i', strtotime($submission['submission_date'])); ?></td>
                                <td class="align-middle py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-<?= strtolower(pathinfo($submission['file_name'], PATHINFO_EXTENSION)) === 'zip' ? 'zip' : 'text'; ?> me-2 text-muted"></i>
                                        <span><?= htmlspecialchars($submission['file_name']); ?></span>
                                    </div>
                                </td>
                                <td class="align-middle py-2">
                                    <?php
                                        $pengirim = !empty($submission['nama_lengkap']) ? $submission['nama_lengkap'] : $submission['sender_number'];
                                        echo htmlspecialchars($pengirim);
                                    ?>
                                </td>
                                <td class="align-middle py-2">
                                    <?php if ($submission['status'] === 'valid'): ?>
                                        <span class="badge bg-success">Valid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Invalid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle py-2">
                                    <?= !empty($submission['validation_notes']) ? htmlspecialchars($submission['validation_notes']) : '<span class="text-muted">-</span>'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const tuMonitoringInitialData = <?= json_encode($tuMonitoringItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const tuMonitoringInitialDate = '<?= htmlspecialchars($tuSelectedDate, ENT_QUOTES, 'UTF-8'); ?>';
const tuMonitoringEndpoint = '<?= BASE_URL; ?>/DashboardController/getTuMonitoringByDate';

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDateDisplay(dateString, withTime = false) {
    if (!dateString) return '-';
    // Pastikan format ISO agar Date tidak NaN di berbagai browser
    const normalized = dateString.replace(' ', 'T');
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) return '-';

    const options = withTime
        ? { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }
        : { day: '2-digit', month: 'short', year: 'numeric' };

    return parsed.toLocaleString('id-ID', options);
}

function getFileIconClass(fileName) {
    if (!fileName) return 'bi-file-earmark';
    const ext = fileName.split('.').pop().toLowerCase();
    if (ext === 'zip' || ext === 'rar') {
        return 'bi-file-zip';
    }
    if (['xls', 'xlsx', 'csv'].includes(ext)) {
        return 'bi-file-earmark-spreadsheet';
    }
    if (['pdf'].includes(ext)) {
        return 'bi-file-earmark-pdf';
    }
    return 'bi-file-text';
}

function updateTuSummary(rows) {
    const totalEl = document.getElementById('tuSummaryTotal');
    const validEl = document.getElementById('tuSummaryValid');
    const invalidEl = document.getElementById('tuSummaryInvalid');
    if (!totalEl || !validEl || !invalidEl) return;

    const total = rows.length;
    let valid = 0;
    rows.forEach(item => {
        if (item.status === 'valid') valid += 1;
    });
    const invalid = total - valid;

    totalEl.textContent = total;
    validEl.textContent = valid;
    invalidEl.textContent = invalid;
}

function renderTuMonitoringRows(rows) {
    const tbody = document.getElementById('tuMonitoringTableBody');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Belum ada file TU pada tanggal ini.</td></tr>';
        return;
    }

    const html = rows.map(row => {
        const fileName = row.file_name || '-';
        const pengirim = row.nama_lengkap && row.nama_lengkap.trim() !== '' ? row.nama_lengkap : row.sender_number;
        const statusBadge = row.status === 'valid'
            ? '<span class="badge bg-success">Valid</span>'
            : '<span class="badge bg-danger">Invalid</span>';
        const notes = row.validation_notes && row.validation_notes.trim() !== ''
            ? escapeHtml(row.validation_notes)
            : '<span class="text-muted">-</span>';

        return `
            <tr>
                <td class="align-middle py-2">${formatDateDisplay(row.tanggal_file_date, false)}</td>
                <td class="align-middle py-2">${formatDateDisplay(row.submission_date, true)}</td>
                <td class="align-middle py-2">
                    <div class="d-flex align-items-center">
                        <i class="bi ${getFileIconClass(fileName)} me-2 text-muted"></i>
                        <span>${escapeHtml(fileName)}</span>
                    </div>
                </td>
                <td class="align-middle py-2">${escapeHtml(pengirim || '-')}</td>
                <td class="align-middle py-2">${statusBadge}</td>
                <td class="align-middle py-2">${notes}</td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = html;
}

function renderTuMonitoring(rows) {
    updateTuSummary(rows);
    renderTuMonitoringRows(rows);
}

function setTuMonitoringLoading() {
    const tbody = document.getElementById('tuMonitoringTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Memuat data...</td></tr>';
}

function setTuMonitoringError(message) {
    const tbody = document.getElementById('tuMonitoringTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3 text-danger">${escapeHtml(message)}</td></tr>`;
    updateTuSummary([]);
}

function fetchTuMonitoring(dateValue) {
    if (!dateValue) return;
    setTuMonitoringLoading();

    fetch(`${tuMonitoringEndpoint}?date=${encodeURIComponent(dateValue)}`)
        .then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.success) {
                const errorMessage = payload.message || 'Gagal memuat data TU.';
                throw new Error(errorMessage);
            }
            return payload;
        })
        .then(payload => {
            renderTuMonitoring(payload.data || []);
        })
        .catch(error => {
            console.error('Error fetching TU monitoring:', error);
            setTuMonitoringError(error.message || 'Gagal memuat data TU.');
        });
}

function initTuMonitoring() {
    const dateInput = document.getElementById('tuFileDatePicker');
    if (!dateInput) return;

    renderTuMonitoring(tuMonitoringInitialData || []);

    dateInput.addEventListener('change', event => {
        const value = event.target.value;
        if (!value) {
            renderTuMonitoring([]);
            return;
        }
        fetchTuMonitoring(value);
    });
}

// Fungsi untuk mengupdate status bot
function updateBotStatus() {
    fetch('<?= BASE_URL; ?>/DashboardController/getBotStatus')
        .then(response => response.json())
        .then(data => {
            const statusIcon = document.getElementById('botStatusIcon');
            const statusText = document.getElementById('botStatusText');
            const statusTimestamp = document.getElementById('botStatusTimestamp');
            
            let newStatus = data.status;
            let newIcon = '';
            let newColor = '';
            let newTextClass = '';
            
            if (data.error) {
                newStatus = 'ERROR';
                newIcon = 'bi-exclamation-triangle';
                newColor = '#ffc107';
                newTextClass = 'text-warning';
            } else if (data.status === 'ONLINE') {
                newIcon = 'bi-power';
                newColor = '#28a745';
                newTextClass = 'text-success';
            } else {
                newIcon = 'bi-x-circle';
                newColor = '#dc3545';
                newTextClass = 'text-danger';
            }
            
            // Update icon
            statusIcon.className = `bi ${newIcon} fs-4 me-3`;
            statusIcon.style.color = newColor;
            
            // Update text
            statusText.textContent = newStatus;
            statusText.className = newTextClass + ' fw-bold';
            
            // Update timestamp atau pesan error
            if (data.status === 'OFFLINE' && !data.error) {
                statusTimestamp.textContent = `Sejak: ${new Date(data.timestamp).toLocaleString()}`;
                statusTimestamp.className = 'd-block text-muted';
            } else if (data.error) {
                statusTimestamp.textContent = `Error: ${data.error}`;
                statusTimestamp.className = 'd-block text-warning';
            } else {
                statusTimestamp.textContent = '';
            }
        })
        .catch(error => {
            console.error('Error fetching bot status:', error);
            // Jika gagal, tetap tampilkan indikator error
            const statusIcon = document.getElementById('botStatusIcon');
            const statusText = document.getElementById('botStatusText');
            const statusTimestamp = document.getElementById('botStatusTimestamp');
            
            statusIcon.className = 'bi bi-exclamation-triangle fs-4 me-3';
            statusIcon.style.color = '#ffc107';
            statusText.textContent = 'ERROR';
            statusText.className = 'text-warning fw-bold';
            statusTimestamp.textContent = 'Gagal menghubungi WA Bot API';
            statusTimestamp.className = 'd-block text-warning';
        });
}

// Panggil fungsi pertama kali untuk load status
document.addEventListener('DOMContentLoaded', function() {
    updateBotStatus();
    
    // Update status setiap 10 detik
    setInterval(updateBotStatus, 10000);

    initTuMonitoring();
});

// Handler untuk tombol restart (hanya untuk admin)
document.getElementById('restartBotBtn')?.addEventListener('click', function() {
    if (confirm('Apakah Anda yakin ingin menyalakan ulang WA Bot? Ini akan memaksa bot untuk logout dan generate QR baru.')) {
        fetch('<?= BASE_URL; ?>/BotStatusController/restart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('QR baru sedang digenerate. Silakan periksa terminal WA Bot untuk QR terbaru.');
            } else {
                alert(data.message || 'Gagal merestart bot');
            }
        })
        .catch(error => {
            console.error('Error restarting bot:', error);
            alert('Gagal mengirim permintaan restart');
        });
    }
});
</script>
