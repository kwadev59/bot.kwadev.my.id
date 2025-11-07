<?php

class GadgetStatusController extends Controller {
    /**
     * @var array<string,string>
     */
    private array $siteOptions = [
        'ALL'  => 'Semua Site',
        'BIM1' => 'SITE BIM1',
        'PPS1' => 'SITE PPS1',
    ];

    /**
     * @var array<string,string>
     */
    private array $statusOptions = [
        'normal' => 'Normal',
        'rusak'  => 'Rusak',
    ];

    /**
     * @var int[]
     */
    private array $perPageOptions = [25, 50, 100, 250];

    public function __construct() {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function index(): void {
        $site = strtoupper(trim((string)($_GET['site'] ?? 'ALL')));
        if (!array_key_exists($site, $this->siteOptions)) {
            $site = 'ALL';
        }

        $statusFilter = strtolower(trim((string)($_GET['status'] ?? 'all')));
        $allowedFilters = ['all', 'normal', 'rusak', 'none'];
        if (!in_array($statusFilter, $allowedFilters, true)) {
            $statusFilter = 'all';
        }

        $search = trim((string)($_GET['search'] ?? ''));

        $perPage = (int)($_GET['per_page'] ?? $this->perPageOptions[0]);
        if (!in_array($perPage, $this->perPageOptions, true)) {
            $perPage = $this->perPageOptions[0];
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        /** @var GadgetStatus_model $gadgetStatusModel */
        $gadgetStatusModel = $this->model('GadgetStatus_model');

        $baseFilters = [
            'site'     => $site === 'ALL' ? null : $site,
            'search'   => $search,
            'site_map' => $this->getSiteAfdelingMap(),
        ];

        $drivers = $gadgetStatusModel->getDriverStatuses([
            'site'     => $baseFilters['site'],
            'search'   => $baseFilters['search'],
            'site_map' => $baseFilters['site_map'],
            'status'   => $statusFilter === 'all' ? null : $statusFilter,
            'limit'    => $perPage,
            'offset'   => $offset,
        ]);

        $totalDrivers = $gadgetStatusModel->countDriverStatuses([
            'site'     => $baseFilters['site'],
            'search'   => $baseFilters['search'],
            'site_map' => $baseFilters['site_map'],
            'status'   => $statusFilter === 'all' ? null : $statusFilter,
        ]);

        $totalPages = max(1, (int)ceil($totalDrivers / max(1, $perPage)));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
            $drivers = $gadgetStatusModel->getDriverStatuses([
                'site'     => $baseFilters['site'],
                'search'   => $baseFilters['search'],
                'site_map' => $baseFilters['site_map'],
                'status'   => $statusFilter === 'all' ? null : $statusFilter,
                'limit'    => $perPage,
                'offset'   => $offset,
            ]);
        }

        $summary = $gadgetStatusModel->getStatusSummary([
            'site'     => $baseFilters['site'],
            'search'   => $baseFilters['search'],
            'site_map' => $baseFilters['site_map'],
        ]);

        $queryParams = [];
        if ($site !== 'ALL') {
            $queryParams['site'] = $site;
        }
        if ($statusFilter !== 'all') {
            $queryParams['status'] = $statusFilter;
        }
        if ($search !== '') {
            $queryParams['search'] = $search;
        }
        if ($perPage !== $this->perPageOptions[0]) {
            $queryParams['per_page'] = $perPage;
        }
        $baseQueryString = http_build_query($queryParams);

        $data = [
            'judul'                 => 'Status Gadget Driver',
            'nama_user'             => $_SESSION['nama_lengkap'] ?? 'Admin',
            'drivers'               => $drivers,
            'site_options'          => $this->siteOptions,
            'status_options'        => $this->statusOptions,
            'status_filter_options' => [
                'all'  => 'Semua Status',
                'normal' => 'Normal',
                'rusak'  => 'Rusak',
                'none'   => 'Belum Diset',
            ],
            'selected_site'   => $site,
            'selected_status' => $statusFilter,
            'search_query'    => $search,
            'per_page'        => $perPage,
            'per_page_options'=> $this->perPageOptions,
            'pagination'      => [
                'current'     => $page,
                'total_pages' => $totalPages,
                'total'       => $totalDrivers,
                'base_query'  => $baseQueryString,
            ],
            'summary'         => $summary,
            'flash'           => $this->pullFlash(),
        ];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('gadget/status', $data);
        $this->view('templates/footer');
    }

    public function update(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . BASE_URL . '/GadgetStatusController');
            exit;
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $status = strtolower(trim((string)($_POST['status'] ?? '')));
        $notes = trim((string)($_POST['notes'] ?? ''));

        $redirectUrl = $this->buildRedirectUrl([
            'site'     => $_POST['redirect_site'] ?? 'ALL',
            'status'   => $_POST['redirect_status'] ?? 'all',
            'search'   => $_POST['redirect_search'] ?? '',
            'page'     => $_POST['redirect_page'] ?? 1,
            'per_page' => $_POST['redirect_per_page'] ?? $this->perPageOptions[0],
        ]);

        if ($employeeId <= 0) {
            $this->setFlash('danger', 'Driver tidak valid.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (!array_key_exists($status, $this->statusOptions)) {
            $this->setFlash('danger', 'Status gadget harus dipilih antara Normal atau Rusak.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        /** @var Employee_model $employeeModel */
        $employeeModel = $this->model('Employee_model');
        $employee = $employeeModel->findById($employeeId);
        if (!$employee) {
            $this->setFlash('danger', 'Data driver tidak ditemukan.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (strtoupper($employee['jabatan'] ?? '') !== 'DRIVER') {
            $this->setFlash('danger', 'Status gadget hanya dapat diperbarui untuk driver.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (empty($employee['aktif'])) {
            $this->setFlash('danger', 'Driver tidak aktif sehingga status gadget tidak dapat diperbarui.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        /** @var GadgetStatus_model $gadgetStatusModel */
        $gadgetStatusModel = $this->model('GadgetStatus_model');
        try {
            $gadgetStatusModel->upsertStatus(
                (int)$employee['id'],
                $employee['npk'],
                $status,
                $notes,
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null
            );
            $driverName = ucwords(strtolower((string)($employee['nama'] ?? 'Driver')));
            $this->setFlash(
                'success',
                sprintf(
                    'Status gadget %s (%s) diperbarui menjadi %s.',
                    $driverName,
                    $employee['npk'],
                    ucfirst($status)
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash('danger', 'Gagal memperbarui status gadget: ' . $exception->getMessage());
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    private function buildRedirectUrl(array $params): string {
        $site = strtoupper(trim((string)($params['site'] ?? 'ALL')));
        if (!array_key_exists($site, $this->siteOptions)) {
            $site = 'ALL';
        }

        $status = strtolower(trim((string)($params['status'] ?? 'all')));
        $allowedFilters = ['all', 'normal', 'rusak', 'none'];

        if (!in_array($status, $allowedFilters, true)) {
            $status = 'all';
        }

        $search = trim((string)($params['search'] ?? ''));
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = (int)($params['per_page'] ?? $this->perPageOptions[0]);
        if (!in_array($perPage, $this->perPageOptions, true)) {
            $perPage = $this->perPageOptions[0];
        }

        $query = [];
        if ($site !== 'ALL') {
            $query['site'] = $site;
        }
        if ($status !== 'all') {
            $query['status'] = $status;
        }
        if ($search !== '') {
            $query['search'] = $search;
        }
        if ($perPage !== $this->perPageOptions[0]) {
            $query['per_page'] = $perPage;
        }
        if ($page > 1) {
            $query['page'] = $page;
        }

        $url = BASE_URL . '/GadgetStatusController';
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = [
            'tipe'  => $type,
            'pesan' => $message,
        ];
    }

    private function pullFlash(): ?array {
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    private function getSiteAfdelingMap(): array {
        return [
            'BIM1' => ['OA', 'OB', 'OC', 'OD', 'OE', 'OF', 'OG'],
            'PPS1' => ['OB', 'OC', 'OD', 'OE', 'OF'],
        ];
    }
}
