<?php

use Shuchkin\SimpleXLSX;

abstract class EmployeeSiteBaseController extends Controller {
    protected string $siteCode = 'BIM1';
    protected string $siteLabel = 'Karyawan BIM1';
    protected string $routePath = 'EmployeeBimController';

    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function index(): void {
        $this->requireAuth();

        $employeeModel = $this->model('Employee_model');
        $perPage = 10;

        $searchQuery = $this->sanitizeSearchQuery($_GET['q'] ?? null);
        $requestedPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $currentPage = $requestedPage > 0 ? $requestedPage : 1;

        $totalEmployees = $employeeModel->countAll($searchQuery, $this->siteCode);
        $totalPages = max(1, (int)ceil($totalEmployees / max(1, $perPage)));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;
        $employees = $employeeModel->getPaginated($perPage, $offset, $searchQuery, $this->siteCode);
        $activeEmployees = $employeeModel->countActive($searchQuery, $this->siteCode);

        $data = [
            'judul'         => $this->siteLabel,
            'nama_user'     => $_SESSION['nama_lengkap'] ?? 'User',
            'site_code'     => $this->siteCode,
            'site_label'    => $this->siteLabel,
            'route_path'    => $this->routePath,
            'search_query'  => $searchQuery ?? '',
            'employees'     => $employees,
            'pagination'    => [
                'current'     => $currentPage,
                'per_page'    => $perPage,
                'total_pages' => $totalPages,
                'total'       => $totalEmployees,
                'has_prev'    => $currentPage > 1,
                'has_next'    => $currentPage < $totalPages,
                'prev_page'   => $currentPage > 1 ? $currentPage - 1 : null,
                'next_page'   => $currentPage < $totalPages ? $currentPage + 1 : null,
            ],
            'summary'       => [
                'total'    => $totalEmployees,
                'active'   => $activeEmployees,
                'inactive' => max(0, $totalEmployees - $activeEmployees),
            ],
            'flash'         => $this->consumeFlash(),
            'site_options'  => $this->getSiteOptions(),
        ];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('employees/site', $data);
        $this->view('templates/footer');
    }

    public function store(): void {
        $this->assertPostRequest();
        $this->requireAuth();
        $employeeModel = $this->model('Employee_model');

        [$payload, $errors] = $this->validateEmployeeInput($_POST);
        $redirectPage = $this->sanitizeRedirectPage($_POST['redirect_page'] ?? null);
        $redirectQuery = $this->sanitizeSearchQuery($_POST['redirect_query'] ?? null);

        if (!empty($errors)) {
            $this->setFlash('danger', implode(' ', $errors));
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        if ($employeeModel->npkExists($payload['npk'])) {
            $this->setFlash('danger', 'NPK sudah terdaftar. Gunakan NPK lain atau edit data karyawan yang ada.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        try {
            $employeeModel->create($payload);
            $this->setFlash('success', 'Karyawan baru berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal menambahkan karyawan: ' . $e->getMessage());
        }

        $this->redirectToSite($payload['site'], $redirectPage, $redirectQuery);
    }

    public function update(): void {
        $this->assertPostRequest();
        $this->requireAuth();
        $employeeModel = $this->model('Employee_model');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $redirectPage = $this->sanitizeRedirectPage($_POST['redirect_page'] ?? null);
        $redirectQuery = $this->sanitizeSearchQuery($_POST['redirect_query'] ?? null);

        if ($id <= 0) {
            $this->setFlash('danger', 'ID karyawan tidak valid.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        if (!$employeeModel->findById($id)) {
            $this->setFlash('danger', 'Data karyawan tidak ditemukan.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        [$payload, $errors] = $this->validateEmployeeInput($_POST);
        if (!empty($errors)) {
            $this->setFlash('danger', implode(' ', $errors));
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        if ($employeeModel->npkExists($payload['npk'], $id)) {
            $this->setFlash('danger', 'NPK sudah dipakai oleh karyawan lain.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        try {
            $employeeModel->update($id, $payload);
            $this->setFlash('success', 'Data karyawan berhasil diperbarui.');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal memperbarui karyawan: ' . $e->getMessage());
        }

        $this->redirectToSite($payload['site'], $redirectPage, $redirectQuery);
    }

    public function import(): void {
        $this->assertPostRequest();
        $this->requireAuth();
        $employeeModel = $this->model('Employee_model');

        $redirectPage = $this->sanitizeRedirectPage($_POST['redirect_page'] ?? null);
        $redirectQuery = $this->sanitizeSearchQuery($_POST['redirect_query'] ?? null);

        if (!isset($_FILES['employee_file']) || $_FILES['employee_file']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('danger', 'File tidak ditemukan atau terjadi kesalahan saat upload.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        $file = $_FILES['employee_file'];
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->setFlash('danger', 'Ukuran file terlalu besar. Maksimal 5MB.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        try {
            if ($extension === 'csv') {
                $rows = $this->parseCsv($file['tmp_name']);
            } elseif ($extension === 'xlsx') {
                $rows = $this->parseXlsx($file['tmp_name']);
            } else {
                $this->setFlash('danger', 'Format file tidak didukung. Gunakan CSV atau XLSX.');
                $this->redirectToIndex($redirectPage, $redirectQuery);
            }
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal membaca file: ' . $e->getMessage());
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        if (empty($rows)) {
            $this->setFlash('danger', 'File tidak berisi data karyawan.');
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        $validRows = [];
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            [$payload, $rowErrors] = $this->validateEmployeeInput($row);
            if (!empty($rowErrors)) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $rowErrors);
                continue;
            }

            $validRows[] = $payload;
        }

        if (!empty($errors)) {
            $this->setFlash('danger', implode(' ', $errors));
            $this->redirectToIndex($redirectPage, $redirectQuery);
        }

        try {
            $result = $employeeModel->import($validRows);
            $this->setFlash(
                'success',
                sprintf(
                    'Import selesai. %d data baru ditambahkan, %d data diperbarui.',
                    $result['inserted'],
                    $result['updated']
                )
            );
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal mengimpor data karyawan: ' . $e->getMessage());
        }

        $this->redirectToIndex($redirectPage, $redirectQuery);
    }

    protected function assertPostRequest(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit;
        }
    }

    protected function redirectToIndex(?int $page = null, ?string $search = null): void {
        $params = [];
        if ($search !== null && $search !== '') {
            $params['q'] = $search;
        }
        if ($page !== null && $page > 1) {
            $params['page'] = $page;
        }

        $url = BASE_URL . '/' . $this->routePath;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        header('Location: ' . $url);
        exit;
    }

    protected function redirectToSite(string $site, ?int $page = null, ?string $search = null): void {
        $route = $this->getRoutePathForSite($site) ?? $this->routePath;

        $params = [];
        if ($search !== null && $search !== '') {
            $params['q'] = $search;
        }
        if ($page !== null && $page > 1) {
            $params['page'] = $page;
        }

        $url = BASE_URL . '/' . $route;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        header('Location: ' . $url);
        exit;
    }

    protected function sanitizeRedirectPage($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $page = (int)$value;
        return $page > 0 ? $page : null;
    }

    protected function sanitizeSearchQuery($value): ?string {
        if ($value === null) {
            return null;
        }

        $query = trim((string)$value);
        if ($query === '') {
            return null;
        }

        $query = preg_replace('/[\x00-\x1F\x7F]/u', '', $query);
        if ($query === null) {
            return null;
        }

        return mb_substr($query, 0, 100);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    protected function validateEmployeeInput(array $input): array {
        $errors = [];

        $site = strtoupper(trim((string)($input['site'] ?? $this->siteCode)));
        $afd = strtoupper(trim((string)($input['afd'] ?? '')));
        $npk = preg_replace('/\s+/', '', strtoupper(trim((string)($input['npk'] ?? ''))));
        $nama = strtoupper(trim((string)($input['nama'] ?? '')));
        $jabatan = strtoupper(trim((string)($input['jabatan'] ?? '')));
        $aktif = $input['aktif'] ?? 'Y';

        if (!in_array($site, ['BIM1', 'PPS1'], true)) {
            $errors[] = 'Site harus diisi dengan nilai BIM1 atau PPS1.';
        }

        if ($afd === '') {
            $errors[] = 'AFD wajib diisi.';
        }

        if ($npk === '' || !preg_match('/^[A-Z0-9]{4,}$/', $npk)) {
            $errors[] = 'NPK wajib diisi dan hanya boleh berisi huruf/angka, minimal 4 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi.';
        }

        $isActive = 'Y';
        if (is_string($aktif)) {
            $normalized = strtoupper(trim($aktif));
            if (in_array($normalized, ['Y', '1', 'YES', 'TRUE', 'ON', 'AKTIF'], true)) {
                $isActive = 'Y';
            } elseif (in_array($normalized, ['N', '0', 'NO', 'FALSE', 'OFF', 'NONAKTIF'], true)) {
                $isActive = 'N';
            }
        } else {
            $isActive = !empty($aktif) ? 'Y' : 'N';
        }

        $payload = [
            'site'    => $site,
            'afd'     => $afd,
            'npk'     => $npk,
            'nama'    => $nama,
            'jabatan' => $jabatan !== '' ? $jabatan : 'MANDOR',
            'aktif'   => $isActive,
        ];

        return [$payload, $errors];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseCsv(string $path): array {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Tidak dapat membaca file CSV.');
        }

        $rows = [];
        $header = null;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeaderRow($data);
                continue;
            }

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = $this->mapRowToEmployee($header, $data);
        }

        fclose($handle);
        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseXlsx(string $path): array {
        $xlsx = SimpleXLSX::parse($path);
        if (!$xlsx) {
            throw new RuntimeException(SimpleXLSX::parseError());
        }

        $rows = [];
        $header = null;
        $index = 0;

        foreach ($xlsx->rows() as $row) {
            $index++;
            if ($index === 1) {
                $header = $this->normalizeHeaderRow($row);
                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $this->mapRowToEmployee($header, $row);
        }

        return $rows;
    }

    /**
     * @param array<int, string|null> $row
     * @return array<string, int>
     */
    protected function normalizeHeaderRow(array $row): array {
        $map = [];

        foreach ($row as $index => $value) {
            $key = strtolower(trim((string)$value));
            if ($key === '') {
                continue;
            }

            if (in_array($key, ['site', 'afd', 'npk', 'nama', 'jabatan', 'aktif'], true)) {
                $map[$key] = $index;
            }
        }

        $required = ['site', 'afd', 'npk', 'nama'];
        foreach ($required as $column) {
            if (!array_key_exists($column, $map)) {
                throw new RuntimeException('Kolom wajib (site, afd, npk, nama) harus ada dalam file.');
            }
        }

        return $map;
    }

    /**
     * @param array<string, int> $headerMap
     * @param array<int, mixed> $row
     * @return array<string, mixed>
     */
    protected function mapRowToEmployee(array $headerMap, array $row): array {
        $getValue = function (string $column) use ($headerMap, $row) {
            if (!isset($headerMap[$column])) {
                return '';
            }

            $index = $headerMap[$column];
            return isset($row[$index]) ? trim((string)$row[$index]) : '';
        };

        return [
            'site'    => $getValue('site'),
            'afd'     => $getValue('afd'),
            'npk'     => $getValue('npk'),
            'nama'    => $getValue('nama'),
            'jabatan' => $getValue('jabatan'),
            'aktif'   => $getValue('aktif'),
        ];
    }

    /**
     * @param array<int, mixed> $row
     */
    protected function isEmptyRow(array $row): bool {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function setFlash(string $type, string $message): void {
        $_SESSION['employee_flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    protected function consumeFlash(): ?array {
        if (!isset($_SESSION['employee_flash'])) {
            return null;
        }

        $flash = $_SESSION['employee_flash'];
        unset($_SESSION['employee_flash']);

        return $flash;
    }

    protected function getSiteOptions(): array {
        return [
            'BIM1' => 'BIM1',
            'PPS1' => 'PPS1',
        ];
    }

    protected function getRoutePathForSite(string $site): ?string {
        $site = strtoupper(trim($site));
        if ($site === 'PPS1') {
            return 'EmployeePpsController';
        }
        if ($site === 'BIM1') {
            return 'EmployeeBimController';
        }

        return null;
    }
}
