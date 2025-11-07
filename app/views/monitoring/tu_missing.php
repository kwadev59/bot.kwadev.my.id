<?php
$selectedDate = $data['selected_date'] ?? date('Y-m-d');
$missing = $data['missing'] ?? [];
$totalMissing = (int)($data['total_missing'] ?? count($missing));

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
?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h1 class="h3 mb-1">Driver Belum Kirim TU</h1>
        <p class="text-muted mb-0">Daftar driver yang belum mengirim file TU pada tanggal terpilih.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2">
            <i class="bi bi-calendar-event me-2"></i>Tanggal: <?= htmlspecialchars($selectedDateHuman); ?>
        </span>
        <a
            href="<?= BASE_URL; ?>/TuMonitoringController?tanggal=<?= htmlspecialchars($selectedDate); ?>"
            class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Monitoring
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h5 class="mb-1">Total Belum Kirim</h5>
            <p class="mb-0 display-6 fw-semibold text-danger"><?= $totalMissing; ?></p>
        </div>
        <div class="text-muted">
            Gunakan daftar ini untuk follow up driver yang belum mengirim file sesuai tanggal.
        </div>
    </div>
</div>

<?php if ($totalMissing === 0): ?>
    <div class="alert alert-success shadow-sm" role="alert">
        Semua driver telah mengirim file TU untuk tanggal tersebut. 🎉
    </div>
<?php else: ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <strong>Daftar Driver</strong>
        </div>
        <div class="card-body">
            <ol class="mb-0">
                <?php foreach ($missing as $driver): ?>
                    <?php
                        $gadgetStatus = strtoupper(trim((string)($driver['gadget_status'] ?? '')));
                        $isDamaged = $gadgetStatus === 'RUSAK';
                        $gadgetNotes = trim((string)($driver['gadget_notes'] ?? ''));
                    ?>
                    <li class="mb-2">
                        <?= htmlspecialchars($driver['site']); ?> /
                        <?= htmlspecialchars($driver['afdeling']); ?> —
                        <code><?= htmlspecialchars($driver['npk']); ?></code> —
                        <?= htmlspecialchars($driver['nama']); ?>
                        <?php if ($isDamaged): ?>
                            <span class="badge bg-danger text-white ms-2">Gadget Rusak</span>
                        <?php endif; ?>
                        <?php if ($gadgetNotes !== ''): ?>
                            <span class="text-muted small ms-2">(<?= htmlspecialchars($gadgetNotes); ?>)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
<?php endif; ?>
