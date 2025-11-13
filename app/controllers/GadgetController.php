<?php
/**
 * Class GadgetController
 *
 * Controller untuk mengelola data gadget (perangkat) untuk berbagai site.
 * Hanya dapat diakses oleh admin. Menyediakan fungsionalitas untuk menampilkan,
 * mengimpor, dan men-download template data gadget.
 */
class GadgetController extends Controller {
    /**
     * @var array<string,string> Judul halaman untuk setiap tipe data gadget.
     */
    private array $typeTitles = [
        'bim1' => 'Data Gadget BIM1',
        'pps1' => 'Data Gadget PPS1',
    ];

    /**
     * @var array<string,array<string,string>> Nama file template untuk setiap tipe data.
     */
    private array $templateFiles = [
        'bim1' => [
            'csv' => 'template_gadget_bim1.csv',
            'xlsx' => 'template_gadget_bim1.xlsx',
        ],
        'pps1' => [
            'csv' => 'template_gadget_pps1.csv',
            'xlsx' => 'template_gadget_pps1.xlsx',
        ],
    ];

    /**
     * @var int[] Opsi jumlah item per halaman untuk paginasi.
     */
    private array $perPageOptions = [25, 50, 100, 250];

    /**
     * GadgetController constructor.
     *
     * Memeriksa otentikasi dan peran admin.
     */
    public function __construct() {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    /**
     * Menampilkan halaman data gadget untuk site BIM1.
     */
    public function bim1() {
        $this->renderPage('bim1');
    }

    /**
     * Menampilkan halaman data gadget untuk site PPS1.
     */
    public function pps1() {
        $this->renderPage('pps1');
    }

    /**
     * Mengimpor data gadget dari file (CSV/XLSX).
     *
     * @param string $type Tipe data (site) yang akan diimpor.
     */
    public function import($type) {
        $type = strtolower(trim((string)$type));
        $redirectUrl = $this->pageUrl($type);

        if (!isset($this->typeTitles[$type])) {
            $_SESSION['flash'] = ['pesan' => 'Jenis data tidak dikenal.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (!isset($_FILES['gadget_file']) || $_FILES['gadget_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['flash'] = ['pesan' => 'Silakan pilih file Excel atau CSV terlebih dahulu.', 'tipe' => 'warning'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        $file = $_FILES['gadget_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash'] = ['pesan' => 'Gagal mengunggah file. Silakan coba lagi.', 'tipe' => 'error'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            $_SESSION['flash'] = ['pesan' => 'Format file tidak didukung. Gunakan file dengan ekstensi .csv atau .xlsx.', 'tipe' => 'warning'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            $rows = $this->readSpreadsheetRows($file['tmp_name'], $extension);
        } catch (Exception $exception) {
            $_SESSION['flash'] = ['pesan' => 'File tidak dapat diproses: ' . $exception->getMessage(), 'tipe' => 'error'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (empty($rows)) {
            $_SESSION['flash'] = ['pesan' => 'File kosong atau tidak memiliki data.', 'tipe' => 'warning'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        $header = array_shift($rows);
        try {
            $mappedRows = $this->mapRowsToSchema($header, $rows);
        } catch (RuntimeException $exception) {
            $_SESSION['flash'] = ['pesan' => $exception->getMessage(), 'tipe' => 'warning'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (empty($mappedRows)) {
            $_SESSION['flash'] = ['pesan' => 'Tidak ada baris valid yang ditemukan pada file.', 'tipe' => 'warning'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            /** @var Gadget_model $gadgetModel */
            $gadgetModel = $this->model('Gadget_model');
            $result = $gadgetModel->replaceDevices($type, $mappedRows);
            $inserted = (int)($result['inserted'] ?? 0);

            $_SESSION['flash'] = ['pesan' => "Berhasil memperbarui {$inserted} baris data gadget.", 'tipe' => 'success'];
        } catch (Throwable $exception) {
            $_SESSION['flash'] = ['pesan' => 'Gagal menyimpan data: ' . $exception->getMessage(), 'tipe' => 'error'];
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Mengunduh file template untuk impor data.
     *
     * @param string $type Tipe data (site).
     * @param string $format Format file ('csv' atau 'xlsx').
     */
    public function downloadTemplate($type, $format = 'csv') {
        $type = strtolower(trim((string)$type));
        $format = strtolower(trim((string)$format));

        if (!isset($this->typeTitles[$type])) {
            $_SESSION['flash'] = ['pesan' => 'Template tidak ditemukan.', 'tipe' => 'error'];
            header('Location: ' . BASE_URL);
            exit;
        }

        $files = $this->templateFiles[$type] ?? [];
        $filename = $files[$format] ?? null;

        if ($filename === null) {
            $_SESSION['flash'] = ['pesan' => 'Format template tidak dikenali.', 'tipe' => 'error'];
            header('Location: ' . $this->pageUrl($type));
            exit;
        }

        $templatePath = __DIR__ . '/../../public/templates/' . $filename;
        if (!file_exists($templatePath)) {
            $_SESSION['flash'] = ['pesan' => 'File template belum tersedia di server.', 'tipe' => 'error'];
            header('Location: ' . $this->pageUrl($type));
            exit;
        }

        /** @var DownloadCounter_model $counterModel */
        $counterModel = $this->model('DownloadCounter_model');
        $counterModel->incrementDownload($filename);

        $mime = $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($templatePath));
        header('Cache-Control: private');

        readfile($templatePath);
        exit;
    }

    /**
     * Merender halaman daftar gadget untuk tipe (site) tertentu.
     *
     * @param string $type Tipe data (site).
     */
    private function renderPage(string $type): void {
        if (!isset($this->typeTitles[$type])) {
            header('Location: ' . BASE_URL);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $perPageInput = (int)($_GET['per_page'] ?? $this->perPageOptions[0]);
        $perPage = in_array($perPageInput, $this->perPageOptions, true) ? $perPageInput : $this->perPageOptions[0];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        /** @var Gadget_model $gadgetModel */
        $gadgetModel = $this->model('Gadget_model');

        $devices = $gadgetModel->getDevices($type, $search, $perPage, $offset);
        $totalDevices = $gadgetModel->countDevices($type, $search);
        $lastImportedAt = $gadgetModel->getLastImportedAt($type);

        $totalPages = $perPage > 0 ? (int)ceil($totalDevices / $perPage) : 1;
        if ($totalPages === 0) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $queryParts = [];
        if ($search !== '') {
            $queryParts[] = 'search=' . urlencode($search);
        }
        if ($perPage !== $this->perPageOptions[0]) {
            $queryParts[] = 'per_page=' . $perPage;
        }

        $preservedQuery = implode('&', $queryParts);

        /** @var DownloadCounter_model $counterModel */
        $counterModel = $this->model('DownloadCounter_model');
        $templateDownloadCount = [];
        foreach ($this->templateFiles[$type] as $format => $filename) {
            $templateDownloadCount[$format] = $counterModel->getDownloadCount($filename);
        }

        $data = [
            'judul' => $this->typeTitles[$type],
            'nama_user' => $_SESSION['nama_lengkap'] ?? 'Admin',
            'type' => $type,
            'type_label' => $this->typeTitles[$type],
            'search' => $search,
            'per_page' => $perPage,
            'per_page_options' => $this->perPageOptions,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_devices' => $totalDevices,
            'devices' => $devices,
            'last_imported_at' => $lastImportedAt,
            'template_files' => $this->templateFiles[$type],
            'template_downloads' => $templateDownloadCount,
            'preserved_query' => $preservedQuery,
        ];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('gadget/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Membaca baris dari file spreadsheet (CSV atau XLSX).
     *
     * @param string $path Path ke file.
     * @param string $extension Ekstensi file ('csv' atau 'xlsx').
     * @return array<int, array<int, string>>
     * @throws InvalidArgumentException Jika ekstensi tidak didukung.
     */
    private function readSpreadsheetRows(string $path, string $extension): array {
        if ($extension === 'csv') {
            return $this->readCsvRows($path);
        }

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        throw new InvalidArgumentException('Ekstensi file tidak didukung.');
    }

    /**
     * Membaca baris dari file CSV.
     *
     * @param string $path Path ke file CSV.
     * @return array<int, array<int, string>>
     * @throws RuntimeException Jika file tidak dapat dibuka.
     */
    private function readCsvRows(string $path): array {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibuka.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($firstLine, $bom, 3) === 0) {
            $firstLine = substr($firstLine, 3);
        }

        $rows = [];
        $header = str_getcsv(rtrim($firstLine), $delimiter);
        if ($header === false) {
            fclose($handle);
            return [];
        }
        $rows[] = array_map([$this, 'cleanCellValue'], $header);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map([$this, 'cleanCellValue'], $row);
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Membaca baris dari file XLSX.
     *
     * @param string $path Path ke file XLSX.
     * @return array<int, array<int, string>>
     * @throws RuntimeException Jika file tidak dapat dibuka atau worksheet tidak ditemukan.
     */
    private function readXlsxRows(string $path): array {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi ZipArchive tidak tersedia di server.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sharedStrings = $this->parseSharedStrings($sharedXml);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Worksheet pertama tidak ditemukan pada file XLSX.');
        }

        $rows = $this->parseWorksheet($sheetXml, $sharedStrings);
        $zip->close();

        return $rows;
    }

    /**
     * Mem-parsing konten XML dari worksheet XLSX.
     *
     * @param string $xmlContent Konten XML worksheet.
     * @param array $sharedStrings Array shared strings dari file XLSX.
     * @return array<int, array<int, string>>
     * @throws RuntimeException Jika worksheet tidak valid.
     */
    private function parseWorksheet(string $xmlContent, array $sharedStrings): array {
        $document = simplexml_load_string($xmlContent);
        if ($document === false) {
            throw new RuntimeException('Worksheet XLSX tidak valid.');
        }
        $document->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($document->sheetData->row as $row) {
            $cells = [];
            $lastColumnIndex = -1;

            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r'];
                $columnIndex = $this->columnIndexFromReference($cellRef);

                while ($lastColumnIndex + 1 < $columnIndex) {
                    $cells[] = '';
                    $lastColumnIndex++;
                }

                $cells[] = $this->extractCellValue($cell, $sharedStrings);
                $lastColumnIndex = $columnIndex;
            }

            $rows[] = array_map([$this, 'cleanCellValue'], $cells);
        }

        return $rows;
    }

    /**
     * Mengekstrak nilai dari sebuah sel (cell) XML.
     *
     * @param SimpleXMLElement $cell Elemen XML sel.
     * @param array $sharedStrings Array shared strings.
     * @return string Nilai sel.
     */
    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string {
        $type = (string)($cell['t'] ?? '');

        if ($type === 's') {
            $index = (int)($cell->v ?? 0);
            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'b') {
            return ((string)$cell->v) === '1' ? 'TRUE' : 'FALSE';
        }

        if ($type === 'inlineStr' && isset($cell->is)) {
            $text = '';
            foreach ($cell->is->t as $inlineText) {
                $text .= (string)$inlineText;
            }
            return $text;
        }

        return (string)($cell->v ?? '');
    }

    /**
     * Mengkonversi referensi kolom (misal: 'A', 'B', 'AA') menjadi indeks berbasis nol.
     *
     * @param string $reference Referensi kolom.
     * @return int Indeks kolom.
     */
    private function columnIndexFromReference(string $reference): int {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($reference));
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    /**
     * Mem-parsing shared strings dari file XLSX.
     *
     * @param string $xmlContent Konten XML shared strings.
     * @return array<int,string>
     */
    private function parseSharedStrings(string $xmlContent): array {
        $document = simplexml_load_string($xmlContent);
        if ($document === false) {
            return [];
        }
        $document->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];
        foreach ($document->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text = (string)$si->t;
            } elseif (isset($si->r)) {
                foreach ($si->r as $run) {
                    $text .= (string)($run->t ?? '');
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * Membersihkan nilai sel.
     *
     * @param mixed $value Nilai sel.
     * @return string Nilai yang sudah dibersihkan.
     */
    private function cleanCellValue($value): string {
        if ($value === null) {
            return '';
        }

        $string = (string)$value;
        return trim(preg_replace('/\s+/u', ' ', $string));
    }

    /**
     * Memetakan baris-baris data ke skema database.
     *
     * @param array<int,string> $header Header file.
     * @param array<int,array<int,string>> $rows Baris-baris data.
     * @return array<int,array<string,mixed>>
     * @throws RuntimeException Jika kolom wajib tidak ditemukan.
     */
    private function mapRowsToSchema(array $header, array $rows): array {
        $normalizedHeader = [];
        foreach ($header as $index => $label) {
            $normalizedHeader[$index] = $this->normalizeHeaderKey($label);
        }

        $columnMap = $this->headerAliases();
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

        $requiredFields = ['imei', 'aplikasi'];
        foreach ($requiredFields as $field) {
            if (!isset($indexes[$field])) {
                throw new RuntimeException('Kolom wajib "' . strtoupper(str_replace('_', ' ', $field)) . '" tidak ditemukan pada header file.');
            }
        }

        $mapped = [];
        foreach ($rows as $row) {
            $isEmpty = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                continue;
            }

            $record = [
                'imei' => $this->valueOrNull($row, $indexes, 'imei'),
                'aplikasi' => $this->valueOrNull($row, $indexes, 'aplikasi'),
                'pt' => $this->valueOrNull($row, $indexes, 'pt'),
                'afd' => $this->valueOrNull($row, $indexes, 'afd'),
                'npk_pengguna' => $this->valueOrNull($row, $indexes, 'npk_pengguna'),
                'nama' => $this->valueOrNull($row, $indexes, 'nama'),
                'pos_title' => $this->valueOrNull($row, $indexes, 'pos_title'),
                'group_asset' => $this->valueOrNull($row, $indexes, 'group_asset'),
                'tipe_asset' => $this->valueOrNull($row, $indexes, 'tipe_asset'),
                'part_asset' => $this->valueOrNull($row, $indexes, 'part_asset'),
                'jumlah' => $this->numericValue($row, $indexes, 'jumlah'),
                'asal_desc' => $this->valueOrNull($row, $indexes, 'asal_desc'),
                'status_desc' => $this->valueOrNull($row, $indexes, 'status_desc'),
                'note' => $this->valueOrNull($row, $indexes, 'note'),
                'action' => $this->valueOrNull($row, $indexes, 'action'),
            ];

            if ($record['imei'] === null && $record['nama'] === null) {
                continue;
            }

            if ($record['jumlah'] === null) {
                $record['jumlah'] = 0;
            }

            $mapped[] = $record;
        }

        return $mapped;
    }

    /**
     * Mendapatkan alias-alias untuk header kolom.
     *
     * @return array<string,array<int,string>>
     */
    private function headerAliases(): array {
        return [
            'imei' => ['imei'],
            'aplikasi' => ['aplikasi', 'application', 'app'],
            'pt' => ['pt', 'company'],
            'afd' => ['afd', 'afdeling'],
            'npk_pengguna' => ['npk_pengguna', 'npk pengguna', 'npk', 'npk user'],
            'nama' => ['nama', 'nama pengguna', 'name', 'user_name'],
            'pos_title' => ['pos_title', 'pos title', 'position', 'jabatan'],
            'group_asset' => ['group_asset', 'group asset', 'asset group'],
            'tipe_asset' => ['tipe_asset', 'tipe asset', 'asset type', 'type_asset'],
            'part_asset' => ['part_asset', 'part asset', 'asset part'],
            'jumlah' => ['jumlah', 'qty', 'quantity', 'total'],
            'asal_desc' => ['asal_desc', 'asal', 'asal desc', 'origin'],
            'status_desc' => ['status_desc', 'status', 'status desc'],
            'note' => ['note', 'catatan', 'keterangan'],
            'action' => ['action', 'aksi', 'tindakan'],
        ];
    }

    /**
     * Menormalisasi kunci header.
     *
     * @param string $value Kunci header.
     * @return string Kunci yang sudah dinormalisasi.
     */
    private function normalizeHeaderKey(string $value): string {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim($value);
        $value = preg_replace('/\s+/', '_', $value);
        return $value;
    }

    /**
     * Mengambil nilai dari baris berdasarkan field, atau null jika tidak ada.
     *
     * @param array $row Baris data.
     * @param array $indexes Mapping field ke indeks kolom.
     * @param string $field Nama field.
     * @return string|null
     */
    private function valueOrNull(array $row, array $indexes, string $field): ?string {
        if (!isset($indexes[$field])) {
            return null;
        }

        $index = $indexes[$field];
        if (!array_key_exists($index, $row)) {
            return null;
        }

        $value = trim((string)$row[$index]);
        return $value === '' ? null : $value;
    }

    /**
     * Mengambil nilai numerik dari baris.
     *
     * @param array $row Baris data.
     * @param array $indexes Mapping field ke indeks kolom.
     * @param string $field Nama field.
     * @return int|null
     */
    private function numericValue(array $row, array $indexes, string $field): ?int {
        $raw = $this->valueOrNull($row, $indexes, $field);
        if ($raw === null) {
            return null;
        }

        $normalized = str_replace([',', ' '], '', $raw);
        if (is_numeric($normalized)) {
            return (int)round((float)$normalized);
        }

        return null;
    }

    /**
     * Membuat URL untuk halaman gadget berdasarkan tipe.
     *
     * @param string $type Tipe data (site).
     * @return string URL halaman.
     */
    private function pageUrl(string $type): string {
        return BASE_URL . '/GadgetController/' . ($type === 'pps1' ? 'pps1' : 'bim1');
    }
}
