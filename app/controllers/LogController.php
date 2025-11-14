<?php
/**
 * Class LogController
 *
 * Controller untuk mengelola log file yang masuk.
 * Menyediakan fungsionalitas untuk melihat laporan file yang valid dan invalid.
 * Membutuhkan otentikasi pengguna.
 */
class LogController extends Controller {
    /**
     * LogController constructor.
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
     * Menampilkan daftar laporan dengan status 'valid'.
     */
    public function valid() {
        $halaman = max(1, (int)($_GET['page'] ?? 1));
        $this->tampilkanLog('valid', $halaman);
    }

    /**
     * Menampilkan daftar laporan dengan status 'invalid'.
     */
    public function invalid() {
        $halaman = max(1, (int)($_GET['page'] ?? 1));
        $this->tampilkanLog('invalid', $halaman);
    }

    /**
     * Metode privat untuk menampilkan log laporan berdasarkan status.
     *
     * @param string $status Status laporan ('valid' atau 'invalid').
     * @param int $halaman Nomor halaman saat ini.
     */
    private function tampilkanLog($status, $halaman) {
        $submissionModel = $this->model('Submission_model');
        
        $options = [
            'search' => (isset($_GET['search']) && $_GET['search'] !== '') ? $_GET['search'] : '',
            'sort'   => $_GET['sort'] ?? 'submission_date',
            'dir'    => $_GET['dir'] ?? 'DESC'
        ];
        
        $data_per_halaman = 10;
        $halaman_aktif = (int)$halaman;
        $total_data = $submissionModel->countFilteredSubmissions($status, $options);
        $total_halaman = ceil($total_data / $data_per_halaman);
        $awal_data = ($data_per_halaman * $halaman_aktif) - $data_per_halaman;

        $data['judul'] = 'Log Laporan ' . ucfirst($status);
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        $data['status_laporan'] = $status;
        $data['laporan'] = $submissionModel->searchSubmissions($status, $awal_data, $data_per_halaman, $options);

        if ($status === 'valid') {
            $downloadCounts = [];
            if (!empty($data['laporan'])) {
                $filenames = array_column($data['laporan'], 'file_name');
                $counterModel = $this->model('DownloadCounter_model');
                $downloadCounts = $counterModel->getDownloadCounts($filenames);
            }

            $data['laporan'] = array_map(function(array $row) use ($downloadCounts) {
                $row['timeliness'] = $this->calculateTimelinessForSubmission($row);
                $row['download_count'] = $downloadCounts[$row['file_name']] ?? 0;
                return $row;
            }, $data['laporan']);
        }

        $data['filters'] = [
            'search' => !empty($options['search']) ? $options['search'] : null
        ];
        $data['sort'] = ['by' => $options['sort'], 'dir' => $options['dir']];
        $data['pagination'] = [
            'total_data'    => $total_data,
            'total_halaman' => $total_halaman,
            'halaman_aktif' => $halaman_aktif
        ];

        // Menggunakan view yang sama dengan LaporanController
        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('laporan/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Menghitung ketepatan waktu pengiriman file.
     *
     * @param array $submission Data submission.
     * @return array|null Data ketepatan waktu atau null.
     */
    private function calculateTimelinessForSubmission(array $submission): ?array {
        $fileDate = $this->resolveFileDateFromSubmission($submission);
        if (!$fileDate instanceof DateTime) {
            return null;
        }
        $fileDate->setTime(0, 0, 0);

        $submissionTimestamp = strtotime((string)($submission['submission_date'] ?? ''));
        if ($submissionTimestamp === false) {
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
     * Mengekstrak tanggal dari data submission.
     *
     * @param array $submission Data submission.
     * @return DateTime|null Objek DateTime atau null.
     */
    private function resolveFileDateFromSubmission(array $submission): ?DateTime {
        $candidates = [];

        $rawTanggal = $submission['tanggal'] ?? null;
        if (is_array($rawTanggal) && isset($rawTanggal['date'])) {
            $rawTanggal = $rawTanggal['date'];
        }
        $rawTanggal = trim((string)$rawTanggal);
        if ($rawTanggal !== '') {
            $candidates[] = $rawTanggal;
        }

        $fileName = trim((string)($submission['file_name'] ?? ''));
        if ($fileName !== '') {
            if (preg_match_all('/(19|20)\d{2}[-\/](0[1-9]|1[0-2])[-\/](0[1-9]|[12]\d|3[01])/', $fileName, $matches)) {
                foreach ($matches[0] as $match) {
                    $candidates[] = $match;
                }
            }
            if (preg_match_all('/\b(19|20)\d{6}\b/', $fileName, $digitMatches)) {
                foreach ($digitMatches[0] as $match) {
                    $candidates[] = $match;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $date = $this->parseDateString($candidate);
            if ($date instanceof DateTime) {
                $date->setTime(0, 0, 0);
                return $date;
            }
        }

        return null;
    }

    /**
     * Mem-parsing string tanggal ke objek DateTime.
     *
     * @param string|null $value String tanggal.
     * @return DateTime|null
     */
    private function parseDateString(?string $value): ?DateTime {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d', 'Y-m-d H:i:s', 'Y/m/d', 'Y/m/d H:i:s',
            'd-m-Y', 'd-m-Y H:i:s', 'd/m/Y', 'd/m/Y H:i:s',
            'Ymd', 'YmdHis',
        ];

        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $value);
            if ($dateTime instanceof DateTime) return $dateTime;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) return (new DateTime())->setTimestamp($timestamp);

        if (preg_match('/\b(20\d{2})(\d{2})(\d{2})\b/', $value, $matches)) {
            $normalized = sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]);
            $dateTime = DateTime::createFromFormat('Y-m-d', $normalized);
            if ($dateTime instanceof DateTime) return $dateTime;
        }

        return null;
    }
}
