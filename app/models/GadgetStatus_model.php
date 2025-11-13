<?php
/**
 * Class GadgetStatus_model
 *
 * Model untuk mengelola status gadget driver.
 * Membuat tabel secara dinamis jika belum ada.
 */
class GadgetStatus_model {
    /** @var Database Instance dari kelas Database. */
    private Database $db;
    /** @var string Nama tabel status gadget. */
    private string $table = 'tu_gadget_statuses';
    /** @var bool Status kesiapan tabel. */
    private bool $tableReady = false;
    /** @var string[] Status yang diizinkan. */
    private array $allowedStatuses = ['NORMAL', 'RUSAK'];

    /**
     * GadgetStatus_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
        $this->ensureTableIsReady();
    }

    /**
     * Mengambil status gadget driver dengan paginasi dan filter.
     * @param array $options Opsi filter dan paginasi.
     * @return array Daftar status gadget.
     */
    public function getDriverStatuses(array $options): array {
        $params = [];
        $query = $this->buildDriverQuery("e.id AS employee_id, e.site, e.afd, e.npk, e.nama, gs.id AS gadget_status_id, gs.status, gs.notes, gs.updated_at, gs.updated_by, u.nama_lengkap AS updated_by_name", $params, $options);
        $query .= " ORDER BY e.site ASC, e.afd ASC, e.nama ASC, e.npk ASC LIMIT " . (int)($options['limit'] ?? 25) . " OFFSET " . (int)($options['offset'] ?? 0);

        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind($key, $value);
        return $this->db->resultSet();
    }

    /**
     * Menghitung jumlah status gadget driver dengan filter.
     * @param array $options Opsi filter.
     * @return int Jumlah status.
     */
    public function countDriverStatuses(array $options): int {
        $params = [];
        $query = $this->buildDriverQuery('COUNT(*) AS total', $params, $options);
        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind($key, $value);
        $row = $this->db->single();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Mendapatkan ringkasan status gadget.
     * @param array $options Opsi filter.
     * @return array Ringkasan status.
     */
    public function getStatusSummary(array $options): array {
        $params = [];
        $query = $this->buildDriverQuery("COUNT(*) AS total, SUM(CASE WHEN gs.status IS NOT NULL THEN 1 ELSE 0 END) AS with_status, SUM(CASE WHEN gs.status = 'NORMAL' THEN 1 ELSE 0 END) AS normal_count, SUM(CASE WHEN gs.status = 'RUSAK' THEN 1 ELSE 0 END) AS rusak_count", $params, $options);
        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind($key, $value);
        $row = $this->db->single() ?: [];
        $total = (int)($row['total'] ?? 0);
        return [
            'total' => $total,
            'with_status' => (int)($row['with_status'] ?? 0),
            'without_status' => $total - (int)($row['with_status'] ?? 0),
            'normal' => (int)($row['normal_count'] ?? 0),
            'rusak' => (int)($row['rusak_count'] ?? 0),
        ];
    }

    /**
     * Mengambil mapping status gadget berdasarkan NPK.
     * @param array $npks Daftar NPK.
     * @return array Mapping NPK ke status.
     */
    public function getStatusMapByNpks(array $npks): array {
        if (empty($npks)) return [];
        $placeholders = implode(',', array_fill(0, count($npks), '?'));
        $this->db->query("SELECT npk, status, notes, updated_at FROM {$this->table} WHERE npk IN ({$placeholders})");
        foreach ($npks as $i => $npk) $this->db->bind($i + 1, $this->normalizeNpk((string)$npk));
        $rows = $this->db->resultSet();
        $map = [];
        foreach ($rows as $row) {
            $npk = $this->normalizeNpk($row['npk'] ?? '');
            if($npk) $map[$npk] = ['status' => strtoupper(trim((string)($row['status'] ?? ''))), 'notes' => trim((string)($row['notes'] ?? '')), 'updated_at' => $row['updated_at'] ?? null];
        }
        return $map;
    }

    /**
     * Menyimpan atau memperbarui status gadget (upsert).
     * @param int $employeeId ID karyawan.
     * @param string $npk NPK karyawan.
     * @param string $status Status gadget.
     * @param string|null $notes Catatan.
     * @param int|null $updatedBy ID pengguna yang memperbarui.
     * @return bool True jika berhasil.
     */
    public function upsertStatus(int $employeeId, string $npk, string $status, ?string $notes, ?int $updatedBy): bool {
        $normalizedStatus = $this->normalizeStatus($status);
        if ($normalizedStatus === null) throw new InvalidArgumentException('Status gadget tidak valid.');
        $normalizedNpk = $this->normalizeNpk($npk);
        if ($normalizedNpk === '') throw new InvalidArgumentException('NPK tidak valid.');

        $query = "INSERT INTO {$this->table} (employee_id, npk, status, notes, updated_by) VALUES (:employee_id, :npk, :status, :notes, :updated_by) ON DUPLICATE KEY UPDATE employee_id = VALUES(employee_id), status = VALUES(status), notes = VALUES(notes), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP";
        $this->db->query($query);
        $this->db->bind('employee_id', $employeeId, PDO::PARAM_INT);
        $this->db->bind('npk', $normalizedNpk);
        $this->db->bind('status', $normalizedStatus);
        $this->db->bind('notes', $this->sanitizeNotes($notes));
        $this->db->bind('updated_by', $updatedBy, $updatedBy ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Membangun query dasar untuk mengambil data driver.
     * @param string $selectClause Klausa SELECT.
     * @param array &$params Parameter query.
     * @param array $options Opsi filter.
     * @return string Query SQL.
     */
    private function buildDriverQuery(string $selectClause, array &$params, array $options): string {
        $conditions = ["UPPER(e.jabatan) = 'DRIVER'", "e.aktif = 1"];
        if (!empty($options['site']) && in_array($options['site'], ['BIM1', 'PPS1'])) {
            $conditions[] = 'e.site = :filter_site';
            $params['filter_site'] = $options['site'];
        }
        if (!empty($options['search'])) {
            $conditions[] = '(e.npk LIKE :search OR e.nama LIKE :search)';
            $params['search'] = '%' . $options['search'] . '%';
        }
        if (in_array($options['status'] ?? '', ['normal', 'rusak'])) {
            $conditions[] = "gs.status = '" . strtoupper($options['status']) . "'";
        } elseif (($options['status'] ?? '') === 'none') {
            $conditions[] = "gs.status IS NULL";
        }
        if (!empty($options['site_map'])) {
            $siteMapClause = $this->buildSiteAfdelingClause($options['site_map'], $params);
            if($siteMapClause) $conditions[] = $siteMapClause;
        }
        return "SELECT {$selectClause} FROM tu_employees e LEFT JOIN {$this->table} gs ON gs.npk = e.npk LEFT JOIN users u ON gs.updated_by = u.id WHERE " . implode(' AND ', $conditions);
    }

    /**
     * Membangun klausa WHERE untuk filter site dan afdeling.
     * @param array $siteMap Peta site dan afdeling.
     * @param array &$params Parameter query.
     * @return string|null Klausa SQL.
     */
    private function buildSiteAfdelingClause(array $siteMap, array &$params): ?string {
        $blocks = [];
        foreach ($siteMap as $site => $afdelings) {
            if (empty($afdelings)) continue;
            $siteParam = 'site_map_' . count($params);
            $params[$siteParam] = $site;
            $afdPlaceholders = [];
            foreach ($afdelings as $afd) {
                $afdParam = 'afd_map_' . count($params);
                $params[$afdParam] = $afd;
                $afdPlaceholders[] = ':' . $afdParam;
            }
            if ($afdPlaceholders) $blocks[] = "(e.site = :{$siteParam} AND e.afd IN (" . implode(', ', $afdPlaceholders) . '))';
        }
        return $blocks ? '(' . implode(' OR ', $blocks) . ')' : null;
    }

    private function ensureTableIsReady(): void {
        if ($this->tableReady) return;
        $this->db->query("CREATE TABLE IF NOT EXISTS {$this->table} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, employee_id INT UNSIGNED, npk VARCHAR(32) NOT NULL UNIQUE, status ENUM('NORMAL','RUSAK') NOT NULL DEFAULT 'NORMAL', notes VARCHAR(255), updated_by INT UNSIGNED, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX (employee_id), INDEX (status), INDEX (updated_by)) ENGINE=InnoDB");
        $this->db->execute();
        $this->tableReady = true;
    }

    private function normalizeStatus(string $status): ?string {
        $normalized = strtoupper(trim($status));
        return in_array($normalized, $this->allowedStatuses, true) ? $normalized : null;
    }

    private function normalizeNpk(string $npk): string {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $npk));
    }

    private function sanitizeNotes(?string $notes): ?string {
        $trimmed = trim((string)$notes);
        return $trimmed === '' ? null : substr($trimmed, 0, 255);
    }
}
