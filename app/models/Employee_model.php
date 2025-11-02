<?php
class Employee_model {
    private $db;
    private $table = 'tu_employees';
    private $tableReady = false;

    public function __construct() {
        $this->db = new Database;
        $this->ensureTableIsReady();
    }

    /**
     * Ambil seluruh data karyawan TU.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array {
        try {
            $this->ensureTableIsReady();
            return $this->fetchFromDatabase();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Ambil data karyawan dengan pagination.
     *
     * @param int $limit  Jumlah data per halaman
     * @param int $offset Offset data
     * @return array<int, array<string, mixed>>
     */
    public function getPaginated(int $limit, int $offset, ?string $search = null, ?string $site = null): array {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        try {
            $this->ensureTableIsReady();

            // Gunakan interpolasi integer yang sudah disanitasi agar kompatibel dengan MySQL tanpa emulate prepares.
            $limitSql = (int)$limit;
            $offsetSql = (int)$offset;
            [$filterClause, $params] = $this->buildFilterClause($site, $search);

            $this->db->query(
                "SELECT id, site, afd, npk, nama, jabatan, aktif
                 FROM {$this->table}
                 WHERE 1=1 {$filterClause}
                 ORDER BY site, afd, nama
                 LIMIT {$limitSql} OFFSET {$offsetSql}"
            );
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            $rows = $this->db->resultSet();

            return array_map([$this, 'formatRow'], $rows);
        } catch (Exception $e) {
            return [];
        }
    }

    public function countAll(?string $search = null, ?string $site = null): int {
        $this->ensureTableIsReady();
        [$filterClause, $params] = $this->buildFilterClause($site, $search);
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE 1=1 {$filterClause}");
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $row = $this->db->single();
        return (int)($row['total'] ?? 0);
    }

    public function countActive(?string $search = null, ?string $site = null): int {
        $this->ensureTableIsReady();
        [$filterClause, $params] = $this->buildFilterClause($site, $search);
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE aktif = 'Y' {$filterClause}");
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $row = $this->db->single();
        return (int)($row['total'] ?? 0);
    }

    public function create(array $data): int {
        $this->ensureTableIsReady();
        $payload = $this->normalizeInput($data);

        $this->db->query(
            "INSERT INTO {$this->table} (site, afd, npk, nama, jabatan, aktif)
             VALUES (:site, :afd, :npk, :nama, :jabatan, :aktif)"
        );
        $this->db->bind('site', $payload['site']);
        $this->db->bind('afd', $payload['afd']);
        $this->db->bind('npk', $payload['npk']);
        $this->db->bind('nama', $payload['nama']);
        $this->db->bind('jabatan', $payload['jabatan']);
        $this->db->bind('aktif', $payload['aktif']);
        $this->db->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $this->ensureTableIsReady();
        $payload = $this->normalizeInput($data);

        $this->db->query(
            "UPDATE {$this->table}
             SET site = :site,
                 afd = :afd,
                 npk = :npk,
                 nama = :nama,
                 jabatan = :jabatan,
                 aktif = :aktif
             WHERE id = :id"
        );
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $this->db->bind('site', $payload['site']);
        $this->db->bind('afd', $payload['afd']);
        $this->db->bind('npk', $payload['npk']);
        $this->db->bind('nama', $payload['nama']);
        $this->db->bind('jabatan', $payload['jabatan']);
        $this->db->bind('aktif', $payload['aktif']);
        $this->db->execute();

        return $this->db->rowCount() > 0;
    }

    public function findById(int $id): ?array {
        $this->ensureTableIsReady();
        $this->db->query(
            "SELECT id, site, afd, npk, nama, jabatan, aktif
             FROM {$this->table}
             WHERE id = :id"
        );
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $row = $this->db->single();
        return $row ? $this->formatRow($row) : null;
    }

    public function findByNpk(string $npk): ?array {
        $this->ensureTableIsReady();
        $this->db->query(
            "SELECT id, site, afd, npk, nama, jabatan, aktif
             FROM {$this->table}
             WHERE npk = :npk"
        );
        $this->db->bind('npk', trim($npk));
        $row = $this->db->single();
        return $row ? $this->formatRow($row) : null;
    }

    public function npkExists(string $npk, ?int $excludeId = null): bool {
        $this->ensureTableIsReady();
        $query = "SELECT COUNT(id) AS total FROM {$this->table} WHERE npk = :npk";
        if ($excludeId !== null) {
            $query .= " AND id <> :exclude_id";
        }

        $this->db->query($query);
        $this->db->bind('npk', trim($npk));
        if ($excludeId !== null) {
            $this->db->bind('exclude_id', $excludeId, PDO::PARAM_INT);
        }

        $row = $this->db->single();
        return (int)($row['total'] ?? 0) > 0;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{inserted:int, updated:int}
     */
    public function import(array $rows): array {
        $this->ensureTableIsReady();
        $inserted = 0;
        $updated = 0;

        $this->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $payload = $this->normalizeInput($row);
                $existing = $this->findByNpk($payload['npk']);

                if ($existing) {
                    $this->update((int)$existing['id'], $payload);
                    $updated++;
                } else {
                    $this->create($payload);
                    $inserted++;
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['inserted' => $inserted, 'updated' => $updated];
    }

    private function ensureTableIsReady(): void {
        if ($this->tableReady) {
            return;
        }

        try {
            $this->createTableIfNotExists();
            $this->tableReady = true;
        } catch (Exception $e) {
            $this->tableReady = false;
            throw $e;
        }
    }

    private function createTableIfNotExists(): void {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            site VARCHAR(10) NOT NULL,
            afd VARCHAR(10) NOT NULL,
            npk VARCHAR(20) NOT NULL UNIQUE,
            nama VARCHAR(100) NOT NULL,
            jabatan VARCHAR(100) NOT NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_site (site),
            INDEX idx_afd (afd)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
        $this->db->execute();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromDatabase(): array {
        $this->db->query(
            "SELECT id, site, afd, npk, nama, jabatan, aktif
             FROM {$this->table}
             ORDER BY site, afd, nama"
        );
        $rows = $this->db->resultSet();

        return array_map([$this, 'formatRow'], $rows);
    }

    private function formatRow(array $row): array {
        $aktif = $row['aktif'] ?? false;
        if (is_string($aktif)) {
            $aktif = strtoupper(trim($aktif)) === 'Y';
        }

        return [
            'id'      => (int)($row['id'] ?? 0),
            'site'    => strtoupper(trim((string)($row['site'] ?? ''))),
            'afd'     => strtoupper(trim((string)($row['afd'] ?? ''))),
            'npk'     => trim((string)($row['npk'] ?? '')),
            'nama'    => strtoupper(trim((string)($row['nama'] ?? ''))),
            'jabatan' => strtoupper(trim((string)($row['jabatan'] ?? ''))),
            'aktif'   => (bool)$aktif,
        ];
    }

    private function normalizeInput(array $data): array {
        $site = strtoupper(trim((string)($data['site'] ?? '')));
        $afd = strtoupper(trim((string)($data['afd'] ?? '')));
        $npk = preg_replace('/\s+/', '', trim((string)($data['npk'] ?? '')));
        $nama = strtoupper(trim((string)($data['nama'] ?? '')));
        $jabatan = strtoupper(trim((string)($data['jabatan'] ?? 'MANDOR')));
        $aktifRaw = $data['aktif'] ?? 'Y';

        if (is_string($aktifRaw)) {
            $aktif = strtoupper(trim($aktifRaw));
            $aktif = in_array($aktif, ['Y', 'N'], true) ? $aktif : 'Y';
        } else {
            $aktif = !empty($aktifRaw) ? 'Y' : 'N';
        }

        return [
            'site'    => $site,
            'afd'     => $afd,
            'npk'     => $npk,
            'nama'    => $nama,
            'jabatan' => $jabatan !== '' ? $jabatan : 'MANDOR',
            'aktif'   => $aktif,
        ];
    }

    /**
     * @return array{0:string,1:array<string,string>}
     */
    private function buildFilterClause(?string $site, ?string $search): array {
        $search = trim((string)$search);
        $clauses = [];
        $params = [];

        $site = $this->normalizeSite($site);
        if ($site !== null) {
            $clauses[] = "site = :site";
            $params['site'] = $site;
        }

        if ($search !== '') {
            $escaped = addcslashes($search, "%_");
            $keyword = '%' . $escaped . '%';
            $clauses[] = "(site LIKE :keyword OR afd LIKE :keyword OR npk LIKE :keyword OR nama LIKE :keyword OR jabatan LIKE :keyword)";
            $params['keyword'] = $keyword;
        }

        if (empty($clauses)) {
            return ['', []];
        }

        return [' AND ' . implode(' AND ', $clauses), $params];
    }

    private function normalizeSite(?string $site): ?string {
        if ($site === null) {
            return null;
        }

        $site = strtoupper(trim($site));
        return in_array($site, ['BIM1', 'PPS1'], true) ? $site : null;
    }
}
