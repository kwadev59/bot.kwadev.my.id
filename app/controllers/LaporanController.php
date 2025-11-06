<?php
class LaporanController extends Controller {
    // Regex validasi nama file
    private $trbRegex = '/^TRB-(\d+)-(\d+)-([A-Z0-9]{4})-([A-Z]{2})-(\d{15})\.(csv|zip)$/i';
    private $tuRegex = '/^TU-([A-Z0-9]{8,9})-([A-Z0-9]{7})-([A-Z0-9]{7})-\d{8}-([A-Z0-9]{4})-([A-Z]{2})-(\d{15})\.(csv|zip)$/i';
    private $amandaRegex = '/^AMANDARB_([A-Z0-9]{4})_(\d{8})-([a-zA-Z0-9]+)\.(csv|zip)$/i';

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function valid() {
        $halaman = max(1, (int)($_GET['page'] ?? 1));
        $this->tampilkanLaporan('valid', $halaman);
    }

    public function invalid() {
        $halaman = max(1, (int)($_GET['page'] ?? 1));
        $this->tampilkanLaporan('invalid', $halaman);
    }

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
     * Download file
     */
    public function download($id) {
        $submissionModel = $this->model('Submission_model');
        $counterModel = $this->model('DownloadCounter_model');
        
        $file = $submissionModel->getSubmissionById($id);

        if ($file) {
            $realPath = $this->resolveDownloadPath($file['file_path']);

            if ($realPath) {
                $fileType = $file['file_type'];
                if (in_array($fileType, ['TU', 'TRB', 'AMANDARB'])) {
                    $counterModel->incrementDownload($file['file_name']);
                    $counterModel->incrementDownload($fileType);
                }
                
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file['file_path']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($realPath));
                
                if (ob_get_level()) {
                    ob_end_clean();
                }
                flush();
                readfile($realPath);
                exit;
            } else {
                $message = "Error: File tidak ditemukan atau akses tidak diizinkan.";

                if (defined('APP_ENV') && APP_ENV !== 'production') {
                    $message .= '<br><small>';
                    $message .= 'Detail: file_path=' . htmlspecialchars($file['file_path'], ENT_QUOTES, 'UTF-8');
                    $message .= ' | BOT_BASE_PATH=' . htmlspecialchars(BOT_BASE_PATH, ENT_QUOTES, 'UTF-8');
                    $message .= '</small>';
                }

                echo $message;
            }
        } else {
            echo "Error: Data file dengan ID tersebut tidak ditemukan.";
        }
    }

    /**
     * Rename file
     */
    public function rename() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

            $oldPath = BOT_BASE_PATH . '/' . $oldData['file_path'];
            $newRelativePath = "storage-wa-bot/{$validationResult['type']}/valid/{$newFileName}";
            $newFullPath = BOT_BASE_PATH . '/' . $newRelativePath;

            // Pastikan folder tujuan ada
            $newDir = dirname($newFullPath);
            if (!is_dir($newDir)) {
                mkdir($newDir, 0775, true);
            }
            
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
    }

    /**
     * Resolve full path untuk file yang ingin diunduh.
     * Mengakomodasi konfigurasi BOT_BASE_PATH yang menunjuk ke root proyek
     * atau langsung ke folder storage-wa-bot.
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
     * Pastikan path hasil resolve masih berada dalam root yang diizinkan.
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
     * Validasi nama file baru
     */
    private function validateNewFileName($fileName) {
        if (preg_match($this->trbRegex, $fileName, $matches)) {
            return [
                'isValid' => true, 'type' => 'TRB',
                'data' => [
                    'site_code'   => $matches[3],
                    'afdeling'    => $matches[4],
                    'imei'        => $matches[5],
                    'unit_code'   => null,
                    'npk_driver'  => null,
                    'npk_mandor'  => null
                ]
            ];
        }
        if (preg_match($this->tuRegex, $fileName, $matches)) {
            return [
                'isValid' => true, 'type' => 'TU',
                'data' => [
                    'unit_code'   => $matches[1],
                    'npk_driver'  => $matches[2],
                    'npk_mandor'  => $matches[3],
                    'site_code'   => $matches[4],
                    'afdeling'    => $matches[5],
                    'imei'        => $matches[6]
                ]
            ];
        }
        if (preg_match($this->amandaRegex, $fileName, $matches)) {
            return [
                'isValid' => true, 'type' => 'AMANDARB',
                'data' => [
                    'site_code'   => $matches[1],
                    'afdeling'    => null,
                    'imei'        => null,
                    'unit_code'   => null,
                    'npk_driver'  => null,
                    'npk_mandor'  => null
                ]
            ];
        }
        return ['isValid' => false];
    }
}
