<?php
/**
 * Class LaporanController
 *
 * Controller untuk mengelola laporan file yang masuk.
 * Menyediakan fungsionalitas untuk melihat laporan valid dan invalid,
 * mengunduh file, dan mengganti nama file yang invalid.
 * Membutuhkan otentikasi pengguna.
 */
class LaporanController extends Controller {
    /** @var string Regex untuk validasi nama file TRB. */
    private $trbRegex = '/^TRB-(\d+)-(\d+)-([A-Z0-9]{4})-([A-Z]{2})-(\d{15})\.(csv|zip)$/i';
    /** @var string Regex untuk validasi nama file TU. */
    private $tuRegex = '/^TU-([A-Z0-9]{8,9})-([A-Z0-9]{7})-([A-Z0-9]{7})-\d{8}-([A-Z0-9]{4})-([A-Z]{2})-(\d{15})\.(csv|zip)$/i';
    /** @var string Regex untuk validasi nama file AmandaRB. */
    private $amandaRegex = '/^AMANDARB_([A-Z0-9]{4})_(\d{8})-([a-zA-Z0-9]+)\.(csv|zip)$/i';

    /**
     * LaporanController constructor.
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
        $this->tampilkanLaporan('valid', $halaman);
    }

    /**
     * Menampilkan daftar laporan dengan status 'invalid'.
     */
    public function invalid() {
        $halaman = max(1, (int)($_GET['page'] ?? 1));
        $this->tampilkanLaporan('invalid', $halaman);
    }

    /**
     * Metode privat untuk menampilkan laporan berdasarkan status.
     *
     * @param string $status Status laporan ('valid' atau 'invalid').
     * @param int $halaman Nomor halaman saat ini.
     */
    private function tampilkanLaporan($status, $halaman) {
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

        $data['judul'] = 'Laporan ' . ucfirst($status);
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

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('laporan/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Mengunduh file laporan berdasarkan ID.
     *
     * @param int $id ID file submission.
     */
    public function download($id) {
        $submissionModel = $this->model('Submission_model');
        $counterModel = $this->model('DownloadCounter_model');

        $file = $submissionModel->getSubmissionById($id);

        if (!$file) {
            echo "❌ Error: Data file dengan ID tersebut tidak ditemukan.";
            return;
        }

        $basePath = rtrim(str_replace('\\', '/', BOT_BASE_PATH), '/');
        $relativePath = ltrim(str_replace('\\', '/', preg_replace('/^storage-wa-bot[\/\\\\]/', '', $file['file_path'])), '/');

        $baseCandidates = [$basePath];
        if (basename($basePath) !== 'storage-wa-bot') {
            $baseCandidates[] = $basePath . '/storage-wa-bot';
        } else {
            $parentPath = str_replace('\\', '/', dirname($basePath));
            if ($parentPath && $parentPath !== '.' && $parentPath !== $basePath) {
                $baseCandidates[] = rtrim($parentPath, '/');
            }
        }

        $possiblePaths = [];
        foreach ($baseCandidates as $candidateBase) {
            $possiblePaths[] = "{$candidateBase}/{$relativePath}";

            if (!preg_match('/^(valid|invalid)\//', $relativePath)) {
                $possiblePaths[] = "{$candidateBase}/valid/{$relativePath}";
                $possiblePaths[] = "{$candidateBase}/invalid/{$relativePath}";
            }
        }

        $possiblePaths = array_values(array_unique(array_filter($possiblePaths)));

        $realPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $realPath = realpath($path);
                break;
            }
        }

        if ($realPath && file_exists($realPath)) {
            $fileType = $file['file_type'];

            if (in_array($fileType, ['TU', 'TRB', 'AMANDARB'])) {
                $counterModel->incrementDownload($file['file_name']);
                $counterModel->incrementDownload($fileType);
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($realPath));

            if (ob_get_level()) ob_end_clean();
            flush();
            readfile($realPath);
            exit;
        } else {
            echo "❌ Error: File tidak ditemukan atau akses tidak diizinkan.<br>";
            echo "Path dicek: <br><code>" . implode('<br>', $possiblePaths) . "</code>";
        }
    }

    /**
     * Mengganti nama file yang invalid menjadi valid. Hanya menerima request POST.
     */
    public function rename() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

        $submissionModel = $this->model('Submission_model');
        $id = $_POST['id'];
        $newFileName = $_POST['new_filename'];

        $oldData = $submissionModel->getSubmissionById($id);
        if (!$oldData) {
            $_SESSION['flash'] = ['pesan' => 'Data file tidak ditemukan.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/LaporanController/invalid');
            exit;
        }

        $validationResult = $this->validateNewFileName($newFileName);
        if (!$validationResult['isValid']) {
            $_SESSION['flash'] = ['pesan' => 'Format nama file baru tidak valid.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/LaporanController/invalid');
            exit;
        }

        $oldPath = rtrim(BOT_BASE_PATH, '/') . '/' . preg_replace('/^storage-wa-bot[\/\\\\]/', '', $oldData['file_path']);
        $newRelativePath = "valid/{$validationResult['type']}/{$newFileName}";
        $newFullPath = rtrim(BOT_BASE_PATH, '/') . '/' . $newRelativePath;

        $newDir = dirname($newFullPath);
        if (!is_dir($newDir)) mkdir($newDir, 0775, true);

        if (@rename($oldPath, $newFullPath)) {
            $dataToUpdate = array_merge([
                'original_file_name' => $oldData['file_name'],
                'file_name'          => $newFileName,
                'file_path'          => $newRelativePath,
                'status'             => 'valid',
                'validation_notes'   => null,
                'file_type'          => $validationResult['type']
            ], $validationResult['data']);

            if ($submissionModel->updateSubmissionRename($id, $dataToUpdate)) {
                $_SESSION['flash'] = ['pesan' => 'Nama file berhasil diganti & divalidasi.', 'tipe' => 'success'];
            } else {
                $_SESSION['flash'] = ['pesan' => 'Gagal memperbarui data di database.', 'tipe' => 'error'];
                @rename($newFullPath, $oldPath); // rollback
            }
        } else {
            $_SESSION['flash'] = ['pesan' => 'Gagal mengganti nama file di server.', 'tipe' => 'error'];
        }

        header('Location: ' . BASE_URL . '/LaporanController/invalid');
        exit;
    }

    /**
     * Menyelesaikan path absolut file untuk diunduh.
     *
     * @param string $filePath Path relatif file.
     * @return string|null Path absolut atau null jika tidak ditemukan.
     */
    private function resolveDownloadPath($filePath) {
        $normalizedFilePath = ltrim(str_replace('\\', '/', $filePath), '/');
        $basePathRaw = rtrim(str_replace('\\', '/', BOT_BASE_PATH), '/');
        $baseRealPath = realpath($basePathRaw);

        if ($baseRealPath === false) {
            return null;
        }

        $allowedRoots = [$baseRealPath];
        $storageRealPath = null;

        if (basename($baseRealPath) !== 'storage-wa-bot') {
            $candidate = $baseRealPath . '/storage-wa-bot';
            $storageRealPath = realpath($candidate);
            if ($storageRealPath) {
                $allowedRoots[] = $storageRealPath;
            }
        } else {
            $parentRealPath = realpath(dirname($baseRealPath));
            if ($parentRealPath) {
                $allowedRoots[] = $parentRealPath;
            }
        }

        $candidates = [];
        $candidates[] = $baseRealPath . '/' . $normalizedFilePath;

        if ($storageRealPath) {
            $candidates[] = $storageRealPath . '/' . $normalizedFilePath;
        } elseif (basename($baseRealPath) === 'storage-wa-bot') {
            if (strpos($normalizedFilePath, 'storage-wa-bot/') === 0) {
                $trimmed = substr($normalizedFilePath, strlen('storage-wa-bot/'));
                $candidates[] = $baseRealPath . '/' . $trimmed;
                $parentRealPath = realpath(dirname($baseRealPath));
                if ($parentRealPath) {
                    $candidates[] = $parentRealPath . '/' . $trimmed;
                }
            } else {
                $parentRealPath = realpath(dirname($baseRealPath));
                if ($parentRealPath) {
                    $candidates[] = $parentRealPath . '/' . $normalizedFilePath;
                }
            }
        } else {
            if (strpos($normalizedFilePath, 'storage-wa-bot/') !== 0) {
                $candidates[] = $baseRealPath . '/storage-wa-bot/' . $normalizedFilePath;
            }
        }

        $uniqueCandidates = array_unique(array_filter($candidates));

        foreach ($uniqueCandidates as $candidate) {
            $realCandidate = realpath($candidate);
            if ($realCandidate && is_file($realCandidate) && $this->isPathWithinAllowedRoots($realCandidate, $allowedRoots)) {
                return $realCandidate;
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah path berada di dalam root yang diizinkan.
     *
     * @param string $path Path yang akan diperiksa.
     * @param array $roots Array path root yang diizinkan.
     * @return bool
     */
    private function isPathWithinAllowedRoots($path, array $roots) {
        foreach ($roots as $root) {
            if ($root && strpos($path, $root) === 0) {
                return true;
            }
        }
        return false;
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

    /**
     * Memvalidasi nama file baru dan mengekstrak datanya.
     *
     * @param string $fileName Nama file baru.
     * @return array Hasil validasi.
     */
    private function validateNewFileName($fileName) {
        if (preg_match($this->trbRegex, $fileName, $matches)) {
            return ['isValid' => true, 'type' => 'TRB', 'data' => ['site_code' => $matches[3], 'afdeling' => $matches[4], 'imei' => $matches[5]]];
        }
        if (preg_match($this->tuRegex, $fileName, $matches)) {
            return ['isValid' => true, 'type' => 'TU', 'data' => ['unit_code' => $matches[1], 'npk_driver' => $matches[2], 'npk_mandor' => $matches[3], 'site_code' => $matches[4], 'afdeling' => $matches[5], 'imei' => $matches[6]]];
        }
        if (preg_match($this->amandaRegex, $fileName, $matches)) {
            return ['isValid' => true, 'type' => 'AMANDARB', 'data' => ['site_code' => $matches[1]]];
        }
        return ['isValid' => false];
    }
}
