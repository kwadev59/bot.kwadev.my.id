<?php
/**
 * Class TuMonitoringController
 *
 * Controller untuk monitoring pengiriman file TU (Transfer Unit).
 * Menyediakan tampilan ringkasan, daftar driver yang belum mengirim, dan ekspor data.
 * Membutuhkan otentikasi pengguna.
 */
class TuMonitoringController extends Controller {
    /** @var string Regex untuk mem-parsing nama file TU. */
    private const TU_FILENAME_REGEX = '/^TU-([A-Z0-9]{8,9})-([A-Z0-9]{7})-([A-Z0-9]{7})-([0-9]{8})-([A-Z0-9]{4})-([A-Z]{2})-([A-Z0-9]{15})\.(CSV|ZIP)$/i';

    /**
     * TuMonitoringController constructor.
     *
     * Memeriksa otentikasi pengguna.
     */
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    /**
     * Menampilkan halaman utama monitoring file TU.
     */
    public function index() {
        $selectedDate = $this->resolveSelectedDate($_GET['tanggal'] ?? $_GET['date'] ?? null);
        $context = $this->prepareMonitoringContext($selectedDate);

        $data = [
            'judul'              => 'Monitoring File TU',
            'nama_user'          => $_SESSION['nama_lengkap'] ?? 'User',
            'selected_date'      => $selectedDate,
            'monitoring'         => $context['monitoring'],
            'summary'            => $context['summary'],
            'site_labels'        => [
                'BIM1' => 'SITE BIM1 (OA, OB, OC, OD, OE, OF, OG)',
                'PPS1' => 'SITE PPS1 (OB, OC, OD, OE, OF)',
            ],
            'tu_available_count' => $context['tu_available_count'],
        ];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('monitoring/tu', $data);
        $this->view('templates/footer');
    }

    /**
     * Menampilkan daftar driver yang belum mengirim file TU pada tanggal yang dipilih.
     */
    public function missing() {
        $selectedDate = $this->resolveSelectedDate($_GET['tanggal'] ?? $_GET['date'] ?? null);
        $context = $this->prepareMonitoringContext($selectedDate);
        $missingDrivers = $this->extractMissingDrivers($context['monitoring']);

        $data = [
            'judul'          => 'Driver Belum Kirim TU',
            'nama_user'      => $_SESSION['nama_lengkap'] ?? 'User',
            'selected_date'  => $selectedDate,
            'missing'        => $missingDrivers,
            'total_missing'  => count($missingDrivers),
            'summary'        => $context['summary'],
        ];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('monitoring/tu_missing', $data);
        $this->view('templates/footer');
    }

    /**
     * Mengekspor data monitoring TU ke dalam format CSV.
     */
    public function exportCsv() {
        $selectedDate = $this->resolveSelectedDate($_GET['tanggal'] ?? $_GET['date'] ?? null);
        $context = $this->prepareMonitoringContext($selectedDate);
        $rows = $this->flattenMonitoringRows($context['monitoring']);

        $fileName = 'monitoring-tu-' . $selectedDate . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) exit;
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $headers = ['Site', 'Afdeling', 'NPK Driver', 'Nama', 'Status Gadget', 'Catatan Gadget', 'Status Kirim', 'Nama File', 'Pengirim', 'Dikirim Pada', 'Ketepatan', 'Catatan Validasi'];
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, array_values($row));
        }

        fclose($output);
        exit;
    }

    /**
     * Memfilter karyawan target untuk monitoring.
     *
     * @param array $employees Daftar semua karyawan.
     * @param array $siteAfdelingMap Mapping site dan afdeling.
     * @return array Karyawan yang relevan untuk monitoring.
     */
    private function filterTargetEmployees(array $employees, array $siteAfdelingMap): array {
        $filtered = [];

        foreach ($employees as $employee) {
            $jabatan = strtoupper(trim((string)($employee['jabatan'] ?? '')));
            $isActive = !empty($employee['aktif']);

            if ($jabatan !== 'DRIVER' || !$isActive) continue;

            $site = $this->normalizeSiteCode($employee['site'] ?? '');
            $afdeling = $this->normalizeAfdeling($employee['afd'] ?? '');

            if (!isset($siteAfdelingMap[$site]) || !in_array($afdeling, $siteAfdelingMap[$site], true)) continue;

            $filtered[] = [
                'id' => (int)($employee['id'] ?? 0),
                'site' => $site,
                'afd' => $afdeling,
                'npk' => trim((string)($employee['npk'] ?? '')),
                'npk_normalized' => $this->normalizeNpk($employee['npk'] ?? ''),
                'nama' => strtoupper(trim((string)($employee['nama'] ?? ''))),
                'jabatan' => $jabatan,
            ];
        }

        usort($filtered, fn($a, $b) => [$a['site'], $a['afd'], $a['nama'], $a['npk_normalized']] <=> [$b['site'], $b['afd'], $b['nama'], $b['npk_normalized']]);

        return $filtered;
    }

    /**
     * Membangun indeks file TU untuk pencarian cepat.
     *
     * @param array $tuRows Daftar file TU dari database.
     * @return array Indeks file TU.
     */
    private function buildTuIndex(array $tuRows): array {
        $index = [];

        foreach ($tuRows as $row) {
            $normalized = $this->normalizeTuSubmissionRow($row);
            if ($normalized === null) continue;

            $site = $normalized['site'];
            $afdeling = $normalized['afdeling'];
            $npk = $normalized['npk_driver'];

            $existing = $index[$site][$afdeling][$npk] ?? null;
            $existingTimestamp = $existing ? $this->parseTimestamp($existing['submission_date'] ?? null) : 0;
            $submissionTimestamp = $this->parseTimestamp($row['submission_date'] ?? null);

            if ($existing === null || $submissionTimestamp >= $existingTimestamp) {
                $row['site_code'] = $site;
                $row['afdeling'] = $afdeling;
                $row['npk_driver'] = $npk;
                $row['submission_timestamp'] = $submissionTimestamp;
                $index[$site][$afdeling][$npk] = $row;
            }
        }

        return $index;
    }

    /**
     * Membangun data monitoring lengkap.
     *
     * @param array $employees Karyawan target.
     * @param array $siteAfdelingMap Mapping site dan afdeling.
     * @param array $tuIndex Indeks file TU.
     * @param string $selectedDate Tanggal yang dimonitor.
     * @param array $gadgetStatusIndex Indeks status gadget.
     * @return array Data monitoring dan ringkasannya.
     */
    private function buildMonitoringData(array $employees, array $siteAfdelingMap, array $tuIndex, string $selectedDate, array $gadgetStatusIndex): array {
        $matrix = [];
        foreach ($siteAfdelingMap as $site => $afdelings) {
            foreach ($afdelings as $afdeling) {
                $matrix[$site][$afdeling] = [];
            }
        }

        $siteKeys = array_keys($siteAfdelingMap);
        $summary = [
            'total_drivers' => count($employees),
            'with_file' => 0,
            'without_file' => 0,
            'site_driver_counts' => array_fill_keys($siteKeys, 0),
            'site_file_counts' => array_fill_keys($siteKeys, 0),
        ];

        foreach ($employees as $employee) {
            $site = $employee['site'];
            $afdeling = $employee['afd'];
            $npk = $employee['npk_normalized'];
            $tuRecord = $tuIndex[$site][$afdeling][$npk] ?? null;
            $gadgetStatus = $gadgetStatusIndex[$npk] ?? null;

            $timeliness = null;
            if ($tuRecord !== null) {
                $timeliness = $this->calculateTimeliness($tuRecord, $selectedDate);
                $summary['with_file']++;
                $summary['site_file_counts'][$site]++;
            } else {
                $summary['without_file']++;
            }
            $summary['site_driver_counts'][$site]++;

            $matrix[$site][$afdeling][] = [
                'employee' => $employee,
                'tu' => $tuRecord,
                'timeliness' => $timeliness,
                'gadget_status' => $gadgetStatus,
            ];
        }

        foreach ($matrix as &$afdelingRows) ksort($afdelingRows);
        unset($afdelingRows);

        return [$matrix, $summary];
    }

    /**
     * Menyelesaikan dan memvalidasi tanggal yang dipilih.
     *
     * @param string|null $rawDate Tanggal dari input pengguna.
     * @return string Tanggal dalam format Y-m-d.
     */
    private function resolveSelectedDate(?string $rawDate): string {
        $rawDate = trim((string)$rawDate);
        $defaultDate = date('Y-m-d', strtotime('-1 day'));

        if (empty($rawDate)) return $defaultDate;

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            $dateTime = DateTime::createFromFormat($format, $rawDate);
            if ($dateTime instanceof DateTime) return $dateTime->format('Y-m-d');
        }

        $timestamp = strtotime($rawDate);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : $defaultDate;
    }

    /**
     * Menormalisasi data dari baris submission file TU.
     *
     * @param array $row Data baris dari database.
     * @return array|null Data yang sudah dinormalisasi atau null jika tidak valid.
     */
    private function normalizeTuSubmissionRow(array $row): ?array {
        $site = $this->normalizeSiteCode($row['site_code'] ?? '');
        $afdeling = $this->normalizeAfdeling($row['afdeling'] ?? '');
        $npk = $this->normalizeNpk($row['npk_driver'] ?? '');

        if ($site === '' || $afdeling === '' || $npk === '') {
            $parsed = $this->parseTuFileName($row['file_name'] ?? '');
            if (!empty($parsed)) {
                $site = $site ?: $this->normalizeSiteCode($parsed['site']);
                $afdeling = $afdeling ?: $this->normalizeAfdeling($parsed['afdeling']);
                $npk = $npk ?: $this->normalizeNpk($parsed['npk_driver']);
            }
        }

        return ($site && $afdeling && $npk) ? ['site' => $site, 'afdeling' => $afdeling, 'npk_driver' => $npk] : null;
    }

    /**
     * Mem-parsing timestamp dari string tanggal.
     *
     * @param mixed $dateString String tanggal.
     * @return int Timestamp Unix.
     */
    private function parseTimestamp($dateString): int {
        if (empty($dateString)) return 0;
        $timestamp = strtotime((string)$dateString);
        return $timestamp ?: 0;
    }

    /**
     * Menghitung ketepatan waktu pengiriman.
     *
     * @param array $tuRecord Data rekaman TU.
     * @param string $fallbackDate Tanggal fallback.
     * @return array|null Informasi ketepatan waktu.
     */
    private function calculateTimeliness(array $tuRecord, string $fallbackDate): ?array {
        $fileDateRaw = $tuRecord['tanggal_file_date'] ?? $fallbackDate;
        $fileDate = DateTime::createFromFormat('Y-m-d', substr((string)$fileDateRaw, 0, 10));
        if (!$fileDate) return null;
        $fileDate->setTime(0, 0, 0);

        $submissionTimestamp = $tuRecord['submission_timestamp'] ?? $this->parseTimestamp($tuRecord['submission_date'] ?? null);
        if (!$submissionTimestamp) return null;

        $submissionDate = (new DateTime())->setTimestamp($submissionTimestamp)->setTime(0, 0, 0);
        $daysDiff = (int)floor(($submissionDate->getTimestamp() - $fileDate->getTimestamp()) / 86400);

        $label = sprintf('File dikirim H%s%d', $daysDiff >= 0 ? '+' : '', $daysDiff);
        $badgeClass = 'bg-secondary-subtle text-secondary';
        $icon = '';

        if ($daysDiff <= -1) $badgeClass = 'bg-info-subtle text-info fw-semibold';
        elseif ($daysDiff <= 1) $badgeClass = 'bg-success-subtle text-success fw-semibold';
        elseif ($daysDiff === 2) $badgeClass = 'bg-warning-subtle text-warning fw-semibold';
        else {
            $badgeClass = 'bg-danger-subtle text-danger fw-semibold';
            $icon = 'bi-exclamation-triangle-fill';
        }

        return compact('label', 'badgeClass', 'icon', 'daysDiff');
    }

    /**
     * Mendapatkan mapping site ke afdeling.
     * @return array<string,string[]>
     */
    private function getSiteAfdelingMap(): array {
        return ['BIM1' => ['OA', 'OB', 'OC', 'OD', 'OE', 'OF', 'OG'], 'PPS1' => ['OB', 'OC', 'OD', 'OE', 'OF']];
    }

    private function normalizeSiteCode(?string $site): string {
        $site = strtoupper(preg_replace('/\s+/', '', (string)$site));
        if ($site === 'BIM') return 'BIM1';
        if ($site === 'PPS') return 'PPS1';
        return $site;
    }

    private function normalizeAfdeling(?string $afdeling): string {
        return strtoupper(trim((string)$afdeling));
    }

    private function normalizeNpk(?string $npk): string {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$npk));
    }

    /**
     * Mem-parsing nama file TU.
     * @return array<string, string>|null
     */
    private function parseTuFileName(?string $fileName): ?array {
        if (preg_match(self::TU_FILENAME_REGEX, trim((string)$fileName), $matches)) {
            return [
                'unit_code' => strtoupper($matches[1]), 'npk_driver' => strtoupper($matches[2]),
                'npk_mandor' => strtoupper($matches[3]), 'date' => $matches[4],
                'site' => strtoupper($matches[5]), 'afdeling' => strtoupper($matches[6]),
                'imei' => strtoupper($matches[7]),
            ];
        }
        return null;
    }

    /**
     * Menyiapkan konteks data untuk monitoring.
     * @return array<string,mixed>
     */
    private function prepareMonitoringContext(string $selectedDate): array {
        $siteAfdelingMap = $this->getSiteAfdelingMap();
        $employeeModel = $this->model('Employee_model');
        $targetEmployees = $this->filterTargetEmployees($employeeModel->getAll(), $siteAfdelingMap);
        $gadgetStatusModel = $this->model('GadgetStatus_model');
        $gadgetStatuses = $gadgetStatusModel->getStatusMapByNpks(array_column($targetEmployees, 'npk_normalized'));
        $submissionModel = $this->model('Submission_model');
        $tuSubmissions = $submissionModel->getTuSubmissionsByFileDate($selectedDate);
        $tuIndex = $this->buildTuIndex($tuSubmissions);

        [$monitoringMatrix, $summary] = $this->buildMonitoringData($targetEmployees, $siteAfdelingMap, $tuIndex, $selectedDate, $gadgetStatuses);

        return [
            'monitoring' => $monitoringMatrix,
            'summary' => $summary,
            'tu_available_count' => count($tuSubmissions),
        ];
    }

    /**
     * Mengekstrak daftar driver yang belum mengirim file.
     * @param array $monitoring Data monitoring.
     * @return array<int,array<string,string>>
     */
    private function extractMissingDrivers(array $monitoring): array {
        $list = [];
        foreach ($monitoring as $site => $afdelings) {
            foreach ($afdelings as $afdeling => $rows) {
                foreach ($rows as $row) {
                    if (!empty($row['tu'])) continue;

                    $gadgetStatus = $row['gadget_status'] ?? null;
                    $gadgetLabel = strtoupper(trim((string)($gadgetStatus['status'] ?? '')));
                    if ($gadgetLabel === 'RUSAK') continue;

                    $employee = $row['employee'] ?? [];
                    $list[] = [
                        'site' => $site, 'afdeling' => $afdeling,
                        'npk' => $employee['npk'] ?? '-',
                        'nama' => isset($employee['nama']) ? ucwords(strtolower((string)$employee['nama'])) : '-',
                        'gadget_status' => $gadgetLabel,
                        'gadget_notes' => trim((string)($gadgetStatus['notes'] ?? '')),
                    ];
                }
            }
        }
        return $list;
    }

    /**
     * Meratakan data monitoring untuk ekspor CSV.
     * @param array $monitoring Data monitoring.
     * @return array<int,array<string,string>>
     */
    private function flattenMonitoringRows(array $monitoring): array {
        $rows = [];
        foreach ($monitoring as $site => $afdelings) {
            foreach ($afdelings as $afdeling => $entries) {
                foreach ($entries as $entry) {
                    $employee = $entry['employee'] ?? [];
                    $tu = $entry['tu'] ?? null;
                    $timeliness = $entry['timeliness'] ?? null;
                    $gadgetStatus = $entry['gadget_status'] ?? null;

                    $gadgetLabel = strtoupper(trim((string)($gadgetStatus['status'] ?? '')));
                    if ($gadgetLabel === '') $gadgetLabel = 'BELUM DISET';

                    $senderLabel = '';
                    if (!empty($tu)) {
                        $senderName = trim((string)($tu['nama_lengkap'] ?? ''));
                        $senderNumber = trim((string)($tu['sender_number'] ?? ''));
                        if ($senderName && $senderNumber) $senderLabel = "$senderName ($senderNumber)";
                        elseif ($senderName) $senderLabel = $senderName;
                        elseif ($senderNumber) $senderLabel = $senderNumber;
                    }

                    $sentAt = '';
                    if (!empty($tu)) {
                        $sentTimestamp = $tu['submission_timestamp'] ?? strtotime((string)($tu['submission_date'] ?? ''));
                        if ($sentTimestamp) $sentAt = date('Y-m-d H:i', $sentTimestamp);
                    }

                    $rows[] = [
                        'site' => $site, 'afdeling' => $afdeling,
                        'npk' => $employee['npk'] ?? '-',
                        'nama' => isset($employee['nama']) ? ucwords(strtolower((string)$employee['nama'])) : '-',
                        'gadget_status' => $gadgetLabel,
                        'gadget_notes' => trim((string)($gadgetStatus['notes'] ?? '')),
                        'status_kirim' => empty($tu) ? 'BELUM KIRIM' : 'SUDAH KIRIM',
                        'file_name' => $tu['file_name'] ?? '',
                        'pengirim' => $senderLabel,
                        'dikirim_pada' => $sentAt,
                        'ketepatan' => $timeliness['label'] ?? '',
                        'catatan' => trim((string)($tu['validation_notes'] ?? '')),
                    ];
                }
            }
        }
        return $rows;
    }
}
