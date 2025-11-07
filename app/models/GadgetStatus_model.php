<?php

class GadgetStatus_model {
    private Database $db;
    private string $table = 'tu_gadget_statuses';
    private bool $tableReady = false;

    /**
     * @var string[]
     */
    private array $allowedStatuses = ['NORMAL', 'RUSAK'];

    public function __construct() {
        $this->db = new Database;
        $this->ensureTableIsReady();
    }

    public function getDriverStatuses(array $options): array {
        $this->ensureTableIsReady();

        $params = [];
        $query = $this->buildDriverQuery(
            "e.id AS employee_id,
             e.site,
             e.afd,
             e.npk,
             e.nama,
             gs.id AS gadget_status_id,
             gs.status,
             gs.notes,
             gs.updated_at,
             gs.updated_by,
             u.nama_lengkap AS updated_by_name",
            $params,
            $options
        );

        $limit = max(1, (int)($options['limit'] ?? 25));
        $offset = max(0, (int)($options['offset'] ?? 0));
        $query .= " ORDER BY e.site ASC, e.afd ASC, e.nama ASC, e.npk ASC";
        $query .= " LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($query);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->resultSet();
    }

    public function countDriverStatuses(array $options): int {
        $this->ensureTableIsReady();
        $params = [];
        $query = $this->buildDriverQuery('COUNT(*) AS total', $params, $options);

        $this->db->query($query);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $row = $this->db->single();
        return (int)($row['total'] ?? 0);
    }

    public function getStatusSummary(array $options): array {
        $this->ensureTableIsReady();
        $params = [];
        $select = "COUNT(*) AS total,
                   SUM(CASE WHEN gs.status IS NOT NULL THEN 1 ELSE 0 END) AS with_status,
                   SUM(CASE WHEN gs.status = 'NORMAL' THEN 1 ELSE 0 END) AS normal_count,
                   SUM(CASE WHEN gs.status = 'RUSAK' THEN 1 ELSE 0 END) AS rusak_count";

        $query = $this->buildDriverQuery($select, $params, $options);

        $this->db->query($query);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $row = $this->db->single() ?: [];
        $total = (int)($row['total'] ?? 0);
        $withStatus = (int)($row['with_status'] ?? 0);
        $normal = (int)($row['normal_count'] ?? 0);
        $rusak = (int)($row['rusak_count'] ?? 0);

        return [
            'total'          => $total,
            'with_status'    => $withStatus,
            'without_status' => max(0, $total - $withStatus),
            'normal'         => $normal,
            'rusak'          => $rusak,
        ];
    }

    public function getStatusMapByNpks(array $npks): array {
        $this->ensureTableIsReady();
        $normalized = [];
        foreach ($npks as $npk) {
            $clean = $this->normalizeNpk((string)$npk);
            if ($clean !== '') {
                $normalized[$clean] = true;
            }
        }

        if (empty($normalized)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        $index = 0;
        foreach (array_keys($normalized) as $npk) {
            $param = 'npk_' . $index++;
            $placeholders[] = ':' . $param;
            $params[$param] = $npk;
        }

        $inClause = implode(', ', $placeholders);
        $query = "SELECT npk, status, notes, updated_at FROM {$this->table} WHERE npk IN ({$inClause})";

        $this->db->query($query);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $rows = $this->db->resultSet();
        $map = [];
        foreach ($rows as $row) {
            $npk = $this->normalizeNpk($row['npk'] ?? '');
            if ($npk === '') {
                continue;
            }
            $map[$npk] = [
                'status' => strtoupper(trim((string)($row['status'] ?? ''))),
                'notes'  => trim((string)($row['notes'] ?? '')),
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }

        return $map;
    }

    public function upsertStatus(int $employeeId, string $npk, string $status, ?string $notes, ?int $updatedBy): bool {
        $this->ensureTableIsReady();
        $normalizedStatus = $this->normalizeStatus($status);
        if ($normalizedStatus === null) {
            throw new InvalidArgumentException('Status gadget tidak valid.');
        }

        $normalizedNpk = $this->normalizeNpk($npk);
        if ($normalizedNpk === '') {
            throw new InvalidArgumentException('NPK tidak valid.');
        }

        $cleanNotes = $this->sanitizeNotes($notes);

        $query = "INSERT INTO {$this->table} (employee_id, npk, status, notes, updated_by)
                  VALUES (:employee_id, :npk, :status, :notes, :updated_by)
                  ON DUPLICATE KEY UPDATE
                    employee_id = VALUES(employee_id),
                    status = VALUES(status),
                    notes = VALUES(notes),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP";

        $this->db->query($query);
        $this->db->bind('employee_id', $employeeId, PDO::PARAM_INT);
        $this->db->bind('npk', $normalizedNpk);
        $this->db->bind('status', $normalizedStatus);
        $this->db->bind('notes', $cleanNotes);
        if ($updatedBy !== null) {
            $this->db->bind('updated_by', $updatedBy, PDO::PARAM_INT);
        } else {
            $this->db->bind('updated_by', null, PDO::PARAM_NULL);
        }

        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildDriverQuery(string $selectClause, array &$params, array $options): string {
        $conditions = [
            "UPPER(e.jabatan) = 'DRIVER'",
            "e.aktif = 1",
        ];

        $site = strtoupper(trim((string)($options['site'] ?? '')));
        if ($site !== '' && in_array($site, ['BIM1', 'PPS1'], true)) {
            $conditions[] = 'e.site = :filter_site';
            $params['filter_site'] = $site;
        }

        $search = trim((string)($options['search'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(e.npk LIKE :search OR e.nama LIKE :search)';
            $params['search'] = '%' . addcslashes($search, '%_') . '%';
        }

        $statusFilter = strtolower(trim((string)($options['status'] ?? '')));
        if ($statusFilter === 'normal') {
            $conditions[] = "gs.status = 'NORMAL'";
        } elseif ($statusFilter === 'rusak') {
            $conditions[] = "gs.status = 'RUSAK'";
        } elseif ($statusFilter === 'none') {
            $conditions[] = "gs.status IS NULL";
        }

        $siteMap = $options['site_map'] ?? [];
        if (is_array($siteMap) && !empty($siteMap)) {
            $siteMapClause = $this->buildSiteAfdelingClause($siteMap, $params);
            if ($siteMapClause !== null) {
                $conditions[] = $siteMapClause;
            }
        }

        $whereClause = implode(' AND ', $conditions);
        if ($whereClause === '') {
            $whereClause = '1=1';
        }

        return "SELECT {$selectClause}
                FROM tu_employees e
                LEFT JOIN {$this->table} gs ON gs.npk = e.npk
                LEFT JOIN users u ON gs.updated_by = u.id
                WHERE {$whereClause}";
    }

    /**
     * @param array<string,array<int,string>> $siteMap
     */
    private function buildSiteAfdelingClause(array $siteMap, array &$params): ?string {
        $blocks = [];
        $siteIndex = 0;

        foreach ($siteMap as $site => $afdelings) {
            if (empty($afdelings)) {
                continue;
            }

            $siteParam = 'site_map_' . $siteIndex;
            $params[$siteParam] = strtoupper(trim((string)$site));

            $afdPlaceholders = [];
            $afdIndex = 0;
            foreach ($afdelings as $afdeling) {
                $param = 'afd_map_' . $siteIndex . '_' . $afdIndex++;
                $params[$param] = strtoupper(trim((string)$afdeling));
                $afdPlaceholders[] = ':' . $param;
            }

            if (!empty($afdPlaceholders)) {
                $blocks[] = "(e.site = :{$siteParam} AND e.afd IN (" . implode(', ', $afdPlaceholders) . '))';
            }

            $siteIndex++;
        }

        if (empty($blocks)) {
            return null;
        }

        return '(' . implode(' OR ', $blocks) . ')';
    }

    private function ensureTableIsReady(): void {
        if ($this->tableReady) {
            return;
        }
        $this->createTableIfNotExists();
        $this->tableReady = true;
    }

    private function createTableIfNotExists(): void {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            employee_id INT UNSIGNED NULL,
            npk VARCHAR(32) NOT NULL UNIQUE,
            status ENUM('NORMAL','RUSAK') NOT NULL DEFAULT 'NORMAL',
            notes VARCHAR(255) DEFAULT NULL,
            updated_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_employee_id (employee_id),
            INDEX idx_status (status),
            INDEX idx_updated_by (updated_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
        $this->db->execute();
    }

    private function normalizeStatus(string $status): ?string {
        $normalized = strtoupper(trim($status));
        return in_array($normalized, $this->allowedStatuses, true) ? $normalized : null;
    }

    private function normalizeNpk(string $npk): string {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $npk));
    }

    private function sanitizeNotes(?string $notes): ?string {
        if ($notes === null) {
            return null;
        }
        $trimmed = trim($notes);
        if ($trimmed === '') {
            return null;
        }
        if (strlen($trimmed) > 255) {
            $trimmed = substr($trimmed, 0, 255);
        }
        return $trimmed;
    }
}
