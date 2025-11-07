<?php
class TuMonitoringController extends Controller {
    private const TU_FILENAME_REGEX = '/^TU-([A-Z0-9]{8,9})-([A-Z0-9]{7})-([A-Z0-9]{7})-([0-9]{8})-([A-Z0-9]{4})-([A-Z]{2})-([A-Z0-9]{15})\.(CSV|ZIP)$/i';

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

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
     * @param array<int, array<string, mixed>> $employees
     * @param array<string, array<int, string>> $siteAfdelingMap
     * @return array<int, array<string, mixed>>
     */
    private function filterTargetEmployees(array $employees, array $siteAfdelingMap): array {
        $filtered = [];

        foreach ($employees as $employee) {
            $jabatan = strtoupper(trim((string)($employee['jabatan'] ?? '')));
            $isActive = !empty($employee['aktif']);

            if ($jabatan !== 'DRIVER' || !$isActive) {
                continue;
            }

            $site = $this->normalizeSiteCode($employee['site'] ?? '');
            $afdeling = $this->normalizeAfdeling($employee['afd'] ?? '');

            if (!isset($siteAfdelingMap[$site])) {
                continue;
            }
            if (!in_array($afdeling, $siteAfdelingMap[$site], true)) {
                continue;
            }

            $filtered[] = [
                'id'             => (int)($employee['id'] ?? 0),
                'site'           => $site,
                'afd'            => $afdeling,
                'npk'            => trim((string)($employee['npk'] ?? '')),
                'npk_normalized' => $this->normalizeNpk($employee['npk'] ?? ''),
                'nama'           => strtoupper(trim((string)($employee['nama'] ?? ''))),
                'jabatan'        => $jabatan,
            ];
        }

        usort($filtered, function(array $a, array $b) {
            return [$a['site'], $a['afd'], $a['nama'], $a['npk_normalized']]
                <=> [$b['site'], $b['afd'], $b['nama'], $b['npk_normalized']];
        });

        return $filtered;
    }

    /**
     * @param array<int, array<string, mixed>> $tuRows
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function buildTuIndex(array $tuRows): array {
        $index = [];

        foreach ($tuRows as $row) {
            $normalized = $this->normalizeTuSubmissionRow($row);
            if ($normalized === null) {
                continue;
            }

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
     * @param array<int, array<string, mixed>> $employees
     * @param array<string, array<int, string>> $siteAfdelingMap
     * @param array<string, array<string, array<string, array<string, mixed>>>> $tuIndex
     * @param array<string, array<string, mixed>> $gadgetStatusIndex
     * @param string $selectedDate
     * @return array{0: array<string, array<string, array<int, array<string, mixed>>>>, 1: array<string, mixed>}
     */
    private function buildMonitoringData(
        array $employees,
        array $siteAfdelingMap,
        array $tuIndex,
        string $selectedDate,
        array $gadgetStatusIndex
    ): array {
        $matrix = [];
        foreach ($siteAfdelingMap as $site => $afdelings) {
            foreach ($afdelings as $afdeling) {
                $matrix[$site][$afdeling] = [];
            }
        }

        $siteKeys = array_keys($siteAfdelingMap);
        $summary = [
            'total_drivers'      => count($employees),
            'with_file'          => 0,
            'without_file'       => 0,
            'site_driver_counts' => array_fill_keys($siteKeys, 0),
            'site_file_counts'   => array_fill_keys($siteKeys, 0),
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
                if (array_key_exists($site, $summary['site_file_counts'])) {
                    $summary['site_file_counts'][$site]++;
                } else {
                    $summary['site_file_counts'][$site] = 1;
                }
            } else {
                $summary['without_file']++;
            }

            if (array_key_exists($site, $summary['site_driver_counts'])) {
                $summary['site_driver_counts'][$site]++;
            } else {
                $summary['site_driver_counts'][$site] = 1;
            }

            $matrix[$site][$afdeling][] = [
                'employee' => $employee,
                'tu'       => $tuRecord,
                'timeliness' => $timeliness,
                'gadget_status' => $gadgetStatus,
            ];
        }

        foreach ($matrix as $site => &$afdelingRows) {
            ksort($afdelingRows);
            foreach ($afdelingRows as &$rows) {
                usort($rows, function(array $a, array $b) {
                    return strcmp($a['employee']['nama'], $b['employee']['nama']);
                });
            }
        }
        unset($afdelingRows, $rows);

        return [$matrix, $summary];
    }

    private function resolveSelectedDate(?string $rawDate): string {
        if ($rawDate !== null) {
            $rawDate = trim((string)$rawDate);
        }
        $defaultDate = date('Y-m-d', strtotime('-1 day'));

        if (empty($rawDate)) {
            return $defaultDate;
        }

        $candidateFormats = ['Y-m-d', 'd-m-Y', 'd/m/Y'];
        foreach ($candidateFormats as $format) {
            $dateTime = DateTime::createFromFormat($format, $rawDate);
            if ($dateTime instanceof DateTime) {
                return $dateTime->format('Y-m-d');
            }
        }

        $timestamp = strtotime($rawDate);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return $defaultDate;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>|null
     */
    private function normalizeTuSubmissionRow(array $row): ?array {
        $site = $this->normalizeSiteCode($row['site_code'] ?? '');
        $afdeling = $this->normalizeAfdeling($row['afdeling'] ?? '');
        $npk = $this->normalizeNpk($row['npk_driver'] ?? '');

        if ($site === '' || $afdeling === '' || $npk === '') {
            $parsed = $this->parseTuFileName($row['file_name'] ?? '');
            if (!empty($parsed)) {
                if ($site === '' && isset($parsed['site'])) {
                    $site = $this->normalizeSiteCode($parsed['site']);
                }
                if ($afdeling === '' && isset($parsed['afdeling'])) {
                    $afdeling = $this->normalizeAfdeling($parsed['afdeling']);
                }
                if ($npk === '' && isset($parsed['npk_driver'])) {
                    $npk = $this->normalizeNpk($parsed['npk_driver']);
                }
            }
        }

        if ($site === '' || $afdeling === '' || $npk === '') {
            return null;
        }

        return [
            'site'       => $site,
            'afdeling'   => $afdeling,
            'npk_driver' => $npk,
        ];
    }

    private function parseTimestamp($dateString): int {
        if (empty($dateString)) {
            return 0;
        }
        $timestamp = strtotime((string)$dateString);
        return $timestamp !== false ? $timestamp : 0;
    }

    /**
     * @param array<string, mixed> $tuRecord
     */
    private function calculateTimeliness(array $tuRecord, string $fallbackDate): ?array {
        $fileDateRaw = $tuRecord['tanggal_file_date'] ?? null;
        if (is_array($fileDateRaw)) {
            $fileDateRaw = $fileDateRaw['date'] ?? null;
        }
        $fileDateString = trim((string)($fileDateRaw ?: $fallbackDate));
        if ($fileDateString === '') {
            return null;
        }

        $fileDate = DateTime::createFromFormat('Y-m-d', substr($fileDateString, 0, 10));
        if (!$fileDate instanceof DateTime) {
            return null;
        }
        $fileDate->setTime(0, 0, 0);

        $submissionTimestamp = $tuRecord['submission_timestamp'] ?? null;
        if (!is_int($submissionTimestamp) || $submissionTimestamp <= 0) {
            $submissionTimestamp = $this->parseTimestamp($tuRecord['submission_date'] ?? null);
        }
        if (!is_int($submissionTimestamp) || $submissionTimestamp <= 0) {
            return null;
        }

        $submissionDate = (new DateTime())->setTimestamp($submissionTimestamp)->setTime(0, 0, 0);
        $secondsDiff = $submissionDate->getTimestamp() - $fileDate->getTimestamp();
        $daysDiff = (int)floor($secondsDiff / 86400);

        $label = sprintf('File dikirim H%s%d', $daysDiff >= 0 ? '+' : '', $daysDiff);
        $badgeClass = 'bg-secondary-subtle text-secondary';
        $icon = '';

        if ($daysDiff <= -1) {
            $badgeClass = 'bg-info-subtle text-info fw-semibold';
        } elseif ($daysDiff <= 1) {
            $badgeClass = 'bg-success-subtle text-success fw-semibold';
        } elseif ($daysDiff === 2) {
            $badgeClass = 'bg-warning-subtle text-warning fw-semibold';
        } else {
            $badgeClass = 'bg-danger-subtle text-danger fw-semibold';
            $icon = 'bi-exclamation-triangle-fill';
        }

        return [
            'label'      => $label,
            'badge_class'=> $badgeClass,
            'icon'       => $icon,
            'days_diff'  => $daysDiff,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getSiteAfdelingMap(): array {
        return [
            'BIM1' => ['OA', 'OB', 'OC', 'OD', 'OE', 'OF', 'OG'],
            'PPS1' => ['OB', 'OC', 'OD', 'OE', 'OF'],
        ];
    }

    private function normalizeSiteCode(?string $site): string {
        $site = strtoupper(preg_replace('/\s+/', '', (string)$site));
        if ($site === '') {
            return '';
        }

        if ($site === 'BIM') {
            return 'BIM1';
        }
        if ($site === 'PPS') {
            return 'PPS1';
        }

        return $site;
    }

    private function normalizeAfdeling(?string $afdeling): string {
        return strtoupper(trim((string)$afdeling));
    }

    private function normalizeNpk(?string $npk): string {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$npk));
    }

    /**
     * @return array<string, string>|null
     */
    private function parseTuFileName(?string $fileName): ?array {
        $fileName = trim((string)$fileName);
        if ($fileName === '') {
            return null;
        }

        if (preg_match(self::TU_FILENAME_REGEX, $fileName, $matches)) {
            return [
                'unit_code'  => strtoupper($matches[1]),
                'npk_driver' => strtoupper($matches[2]),
                'npk_mandor' => strtoupper($matches[3]),
                'date'       => $matches[4],
                'site'       => strtoupper($matches[5]),
                'afdeling'   => strtoupper($matches[6]),
                'imei'       => strtoupper($matches[7]),
            ];
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    private function prepareMonitoringContext(string $selectedDate): array {
        $siteAfdelingMap = $this->getSiteAfdelingMap();

        $employeeModel = $this->model('Employee_model');
        $targetEmployees = $this->filterTargetEmployees($employeeModel->getAll(), $siteAfdelingMap);

        /** @var GadgetStatus_model $gadgetStatusModel */
        $gadgetStatusModel = $this->model('GadgetStatus_model');
        $gadgetStatuses = $gadgetStatusModel->getStatusMapByNpks(array_column($targetEmployees, 'npk_normalized'));

        $submissionModel = $this->model('Submission_model');
        $tuSubmissions = $submissionModel->getTuSubmissionsByFileDate($selectedDate);
        $tuIndex = $this->buildTuIndex($tuSubmissions);

        [$monitoringMatrix, $summary] = $this->buildMonitoringData(
            $targetEmployees,
            $siteAfdelingMap,
            $tuIndex,
            $selectedDate,
            $gadgetStatuses
        );

        return [
            'monitoring'         => $monitoringMatrix,
            'summary'            => $summary,
            'tu_available_count' => count($tuSubmissions),
        ];
    }

    /**
     * @param array<string,array<string,array<int,array<string,mixed>>>> $monitoring
     * @return array<int,array<string,string>>
     */
    private function extractMissingDrivers(array $monitoring): array {
        $list = [];
        foreach ($monitoring as $site => $afdelings) {
            foreach ($afdelings as $afdeling => $rows) {
                foreach ($rows as $row) {
                    $employee = $row['employee'] ?? [];
                    $tu = $row['tu'] ?? null;
                    if (!empty($tu)) {
                        continue;
                    }
                    $gadgetStatus = $row['gadget_status'] ?? null;
                    $gadgetLabel = null;
                    $gadgetNotes = null;
                    if (is_array($gadgetStatus)) {
                        $gadgetLabel = strtoupper(trim((string)($gadgetStatus['status'] ?? '')));
                        $gadgetNotes = trim((string)($gadgetStatus['notes'] ?? ''));
                    }
                    $list[] = [
                        'site'     => $site,
                        'afdeling' => $afdeling,
                        'npk'      => $employee['npk'] ?? '-',
                        'nama'     => isset($employee['nama'])
                            ? ucwords(strtolower((string)$employee['nama']))
                            : '-',
                        'gadget_status' => $gadgetLabel,
                        'gadget_notes'  => $gadgetNotes,
                    ];
                }
            }
        }
        return $list;
    }
}
