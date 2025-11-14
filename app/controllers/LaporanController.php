<?php
/**
 * Class LaporanController
 *
 * Controller untuk mengelola laporan file berdasarkan jenisnya.
 * Menyediakan fungsionalitas untuk mengunduh file dan mengganti nama file yang invalid.
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

    public function tu() { $this->tampilkanLaporanBerdasarkanJenis('TU'); }
    public function trb() { $this->tampilkanLaporanBerdasarkanJenis('TRB'); }
    public function amandarb() { $this->tampilkanLaporanBerdasarkanJenis('AMANDARB'); }
    public function to() { $this->tampilkanLaporanBerdasarkanJenis('TO'); }
    public function tpn() { $this->tampilkanLaporanBerdasarkanJenis('TPN'); }
    public function tr() { $this->tampilkanLaporanBerdasarkanJenis('TR'); }

    private function tampilkanLaporanBerdasarkanJenis($jenis) {
        $submissionModel = $this->model('Submission_model');
        $halaman = max(1, (int)($_GET['page'] ?? 1));
        
        $options = [
            'search' => (isset($_GET['search']) && $_GET['search'] !== '') ? $_GET['search'] : '',
            'sort'   => $_GET['sort'] ?? 'submission_date',
            'dir'    => $_GET['dir'] ?? 'DESC',
            'file_type' => $jenis
        ];
        
        $data_per_halaman = 10;
        $halaman_aktif = (int)$halaman;
        // Kita akan membutuhkan metode baru di model, untuk saat ini kita asumsikan saja ada.
        $total_data = $submissionModel->countFilteredSubmissions('all', $options);
        $total_halaman = ceil($total_data / $data_per_halaman);
        $awal_data = ($data_per_halaman * $halaman_aktif) - $data_per_halaman;

        $data['judul'] = 'Laporan ' . strtoupper($jenis);
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        $data['status_laporan'] = 'by_type'; // Status custom untuk view
        $data['laporan'] = $submissionModel->searchSubmissions('all', $awal_data, $data_per_halaman, $options);

        $downloadCounts = [];
        if (!empty($data['laporan'])) {
            $filenames = array_column($data['laporan'], 'file_name');
            $counterModel = $this->model('DownloadCounter_model');
            $downloadCounts = $counterModel->getDownloadCounts($filenames);
        }

        $data['laporan'] = array_map(function(array $row) use ($downloadCounts) {
            // Logika ketepatan waktu mungkin tidak berlaku di sini, jadi kita lewati.
            $row['download_count'] = $downloadCounts[$row['file_name']] ?? 0;
            return $row;
        }, $data['laporan']);

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
            header('Location: ' . BASE_URL . '/LogController/invalid');
            exit;
        }

        $validationResult = $this->validateNewFileName($newFileName);
        if (!$validationResult['isValid']) {
            $_SESSION['flash'] = ['pesan' => 'Format nama file baru tidak valid.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/LogController/invalid');
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

        header('Location: ' . BASE_URL . '/LogController/invalid');
        exit;
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
