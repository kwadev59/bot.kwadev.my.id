<?php
/**
 * Class KontakController
 *
 * Controller untuk mengelola data kontak WhatsApp.
 * Menyediakan fungsionalitas CRUD (Create, Read, Update, Delete),
 * impor dari file CSV, dan unduh template.
 * Membutuhkan otentikasi pengguna.
 */
class KontakController extends Controller {
    /**
     * KontakController constructor.
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
     * Menampilkan halaman utama manajemen kontak dengan daftar kontak,
     * fungsionalitas pencarian, dan paginasi.
     */
    public function index() {
        $kontakModel = $this->model('Kontak_model');
        $counterModel = $this->model('DownloadCounter_model');

        $search = trim($_GET['search'] ?? '');
        $perPageOptions = [10, 25, 50, 100];
        $requestedPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        $perPage = in_array($requestedPerPage, $perPageOptions, true) ? $requestedPerPage : 25;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $totalKontak = $kontakModel->countFilteredKontak($search);
        $totalPages = $perPage > 0 ? (int)ceil($totalKontak / $perPage) : 1;
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;
        if ($offset < 0) {
            $offset = 0;
        }

        $data['judul'] = 'Manajemen Kontak';
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        $data['search'] = $search;
        $data['per_page_options'] = $perPageOptions;
        $data['per_page'] = $perPage;
        $data['kontak_list'] = $kontakModel->getKontakPaginated($search, $perPage, $offset);
        $data['total_kontak'] = $totalKontak;
        $data['pagination'] = [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'total_items' => $totalKontak,
            'first_item' => $totalKontak > 0 ? $offset + 1 : 0,
            'last_item' => $totalKontak > 0 ? min($offset + $perPage, $totalKontak) : 0,
        ];
        $data['preserved_query'] = http_build_query(array_filter([
            'search' => $search !== '' ? $search : null,
            'per_page' => $perPage !== 25 ? $perPage : null,
        ]));

        $defaultKategori = [
            'MANDOR TRANSPORT',
            'MANDOR PANEN',
            'MANDOR RAWAT',
            'MANDOR 1',
            'ASISTEN AFDELING'
        ];

        $kategoriOptions = $defaultKategori;
        foreach ($kontakModel->getDistinctKategori() as $kategori) {
            $exists = false;
            foreach ($kategoriOptions as $existing) {
                if (strcasecmp($existing, $kategori) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists && $kategori !== '') {
                $kategoriOptions[] = $kategori;
            }
        }

        $data['kategori_options'] = $kategoriOptions;

        $data['download_counts'] = [
            'template' => $counterModel->getDownloadCount('template_kontak.csv'),
            'template_dengan_bom' => $counterModel->getDownloadCount('template_dengan_bom.csv'),
            'template_variasi_header' => $counterModel->getDownloadCount('template_variasi_header.csv'),
            'template_kontak_valid' => $counterModel->getDownloadCount('template_kontak_valid.csv')
        ];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('kontak/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Menambahkan kontak baru. Hanya menerima request POST.
     */
    public function tambah() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $kontakModel = $this->model('Kontak_model');
            
            $data = [
                'site' => trim($_POST['site'] ?? ''),
                'afd' => trim($_POST['afd'] ?? ''),
                'nama' => trim($_POST['nama'] ?? ''),
                'nomer_wa' => trim($_POST['nomer_wa'] ?? ''),
                'kategori' => trim($_POST['kategori'] ?? '')
            ];
            
            if ($kontakModel->tambahKontak($data)) {
                $_SESSION['flash'] = ['pesan' => 'Kontak berhasil ditambahkan.', 'tipe' => 'success'];
            } else {
                $_SESSION['flash'] = ['pesan' => 'Gagal menambahkan kontak.', 'tipe' => 'error'];
            }
            
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }
    }

    /**
     * Memperbarui kontak yang ada. Hanya menerima request POST.
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $kontakModel = $this->model('Kontak_model');
            
            $id = $_POST['id'];
            $data = [
                'site' => trim($_POST['site'] ?? ''),
                'afd' => trim($_POST['afd'] ?? ''),
                'nama' => trim($_POST['nama'] ?? ''),
                'nomer_wa' => trim($_POST['nomer_wa'] ?? ''),
                'kategori' => trim($_POST['kategori'] ?? '')
            ];
            
            if ($kontakModel->updateKontak($id, $data)) {
                $_SESSION['flash'] = ['pesan' => 'Kontak berhasil diperbarui.', 'tipe' => 'success'];
            } else {
                $_SESSION['flash'] = ['pesan' => 'Gagal memperbarui kontak.', 'tipe' => 'error'];
            }
            
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }
    }

    /**
     * Menghapus kontak berdasarkan ID.
     *
     * @param int $id ID kontak yang akan dihapus.
     */
    public function hapus($id) {
        $kontakModel = $this->model('Kontak_model');
        
        if ($kontakModel->hapusKontak($id)) {
            $_SESSION['flash'] = ['pesan' => 'Kontak berhasil dihapus.', 'tipe' => 'success'];
        } else {
            $_SESSION['flash'] = ['pesan' => 'Gagal menghapus kontak.', 'tipe' => 'error'];
        }
        
        header('Location: ' . BASE_URL . '/KontakController');
        exit;
    }

    /**
     * Mengimpor kontak dari file CSV. Hanya menerima request POST.
     */
    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        if (!isset($_FILES['kontak_csv']) || $_FILES['kontak_csv']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['flash'] = ['pesan' => 'Silakan pilih file CSV terlebih dahulu.', 'tipe' => 'warning'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $file = $_FILES['kontak_csv'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash'] = ['pesan' => 'Gagal mengunggah file CSV. Silakan coba lagi.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $_SESSION['flash'] = ['pesan' => 'Format file tidak valid. Unggah file dengan ekstensi .csv.', 'tipe' => 'warning'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            $_SESSION['flash'] = ['pesan' => 'File CSV tidak dapat dibaca.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            $_SESSION['flash'] = ['pesan' => 'File CSV kosong.', 'tipe' => 'warning'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            $_SESSION['flash'] = ['pesan' => 'Header CSV tidak valid.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $normalizedHeader = [];
        foreach ($header as $column) {
            $normalizedHeader[] = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', (string)$column)));
        }

        $columnMap = [
            'site' => ['site', 'estate'],
            'afd' => ['afd', 'afdeling'],
            'nama' => ['nama', 'nama lengkap', 'name'],
            'nomer_wa' => ['nomor wa', 'nomer wa', 'no wa', 'nomor whatsapp', 'no whatsapp', 'nomor hp', 'no hp', 'telepon', 'phone'],
            'kategori' => ['jabatan', 'kategori', 'role', 'posisi'],
        ];

        $indexes = [];
        foreach ($columnMap as $field => $aliases) {
            foreach ($aliases as $alias) {
                $position = array_search($alias, $normalizedHeader, true);
                if ($position !== false) {
                    $indexes[$field] = $position;
                    break;
                }
            }
        }

        if (!isset($indexes['nama'])) {
            fclose($handle);
            $_SESSION['flash'] = ['pesan' => 'Kolom nama tidak ditemukan pada file CSV.', 'tipe' => 'warning'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $kontakRows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === null) {
                continue;
            }

            $isEmptyRow = true;
            foreach ($row as $value) {
                if (trim((string)$value) !== '') {
                    $isEmptyRow = false;
                    break;
                }
            }

            if ($isEmptyRow) {
                continue;
            }

            $kontakRows[] = [
                'site' => isset($indexes['site'], $row[$indexes['site']]) ? trim((string)$row[$indexes['site']]) : '',
                'afd' => isset($indexes['afd'], $row[$indexes['afd']]) ? trim((string)$row[$indexes['afd']]) : '',
                'nama' => isset($row[$indexes['nama']]) ? trim((string)$row[$indexes['nama']]) : '',
                'nomer_wa' => isset($indexes['nomer_wa'], $row[$indexes['nomer_wa']]) ? preg_replace('/\\s+/', '', trim((string)$row[$indexes['nomer_wa']])) : '',
                'kategori' => isset($indexes['kategori'], $row[$indexes['kategori']]) ? trim((string)$row[$indexes['kategori']]) : '',
            ];
        }

        fclose($handle);

        if (empty($kontakRows)) {
            $_SESSION['flash'] = ['pesan' => 'Tidak ada baris data yang dapat diimpor.', 'tipe' => 'warning'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }

        $kontakModel = $this->model('Kontak_model');
        $result = $kontakModel->importKontakBatch($kontakRows);

        $inserted = (int)($result['inserted'] ?? 0);
        $skipped = (int)($result['skipped'] ?? 0);

        if ($inserted > 0) {
            $message = "Berhasil mengimpor {$inserted} kontak.";
            if ($skipped > 0) {
                $message .= " {$skipped} baris dilewati.";
            }
            $_SESSION['flash'] = ['pesan' => $message, 'tipe' => 'success'];
        } else {
            $_SESSION['flash'] = ['pesan' => 'Semua baris dilewati. Pastikan data memiliki kolom nama yang valid.', 'tipe' => 'warning'];
        }

        header('Location: ' . BASE_URL . '/KontakController');
        exit;
    }

    /**
     * Menyediakan file template CSV untuk diunduh.
     *
     * @param string $type Tipe template yang akan diunduh.
     */
    public function downloadTemplate($type = 'default') {
        $counterModel = $this->model('DownloadCounter_model');
        
        $filenames = [
            'default' => 'template_kontak.csv',
            'bom' => 'template_dengan_bom.csv',
            'variasi' => 'template_variasi_header.csv',
            'valid' => 'template_kontak_valid.csv'
        ];
        
        $filename = $filenames[$type] ?? 'template_kontak.csv';
        
        // Increment download counter
        $counterModel->incrementDownload($filename);
        
        $templatePath = __DIR__ . '/../../public/templates/' . $filename;
        
        if (file_exists($templatePath)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($templatePath));
            
            readfile($templatePath);
            exit;
        } else {
            $_SESSION['flash'] = ['pesan' => 'Template tidak ditemukan.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL . '/KontakController');
            exit;
        }
    }
}
