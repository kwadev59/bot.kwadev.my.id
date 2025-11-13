<?php
/**
 * Class Employee_model
 *
 * Model untuk mengelola data karyawan, khususnya untuk TU (Transfer Unit).
 * Membuat tabel secara dinamis jika belum ada.
 */
class Employee_model {
    /** @var Database Instance dari kelas Database. */
    private $db;
    /** @var string Nama tabel karyawan. */
    private $table = 'tu_employees';
    /** @var bool Status kesiapan tabel. */
    private $tableReady = false;

    /**
     * Employee_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
        $this->ensureTableIsReady();
    }

    /**
     * Mengambil seluruh data karyawan.
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
     * Mengambil data karyawan dengan paginasi.
     * @param int $limit Jumlah data per halaman.
     * @param int $offset Posisi awal data.
     * @param string|null $search Kata kunci pencarian.
     * @param string|null $site Filter berdasarkan site.
     * @return array<int, array<string, mixed>>
     */
    public function getPaginated(int $limit, int $offset, ?string $search = null, ?string $site = null): array {
        try {
            [$filterClause, $params] = $this->buildFilterClause($site, $search);
            $this->db->query("SELECT * FROM {$this->table} WHERE 1=1 {$filterClause} ORDER BY site, afd, nama LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
            foreach ($params as $key => $value) $this->db->bind($key, $value);
            return array_map([$this, 'formatRow'], $this->db->resultSet());
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Menghitung total karyawan dengan filter.
     * @param string|null $search Kata kunci pencarian.
     * @param string|null $site Filter site.
     * @return int Total karyawan.
     */
    public function countAll(?string $search = null, ?string $site = null): int {
        [$filterClause, $params] = $this->buildFilterClause($site, $search);
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE 1=1 {$filterClause}");
        foreach ($params as $key => $value) $this->db->bind($key, $value);
        return (int)($this->db->single()['total'] ?? 0);
    }

    /**
     * Menghitung karyawan aktif dengan filter.
     * @param string|null $search Kata kunci pencarian.
     * @param string|null $site Filter site.
     * @return int Total karyawan aktif.
     */
    public function countActive(?string $search = null, ?string $site = null): int {
        [$filterClause, $params] = $this->buildFilterClause($site, $search);
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE aktif = 1 {$filterClause}");
        foreach ($params as $key => $value) $this->db->bind($key, $value);
        return (int)($this->db->single()['total'] ?? 0);
    }

    /**
     * Membuat data karyawan baru.
     * @param array $data Data karyawan.
     * @return int ID karyawan yang baru dibuat.
     */
    public function create(array $data): int {
        $payload = $this->normalizeInput($data);
        $this->db->query("INSERT INTO {$this->table} (site, afd, npk, nama, jabatan, aktif) VALUES (:site, :afd, :npk, :nama, :jabatan, :aktif)");
        foreach ($payload as $key => $value) $this->db->bind($key, $value);
        $this->db->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data karyawan.
     * @param int $id ID karyawan.
     * @param array $data Data baru.
     * @return bool True jika berhasil.
     */
    public function update(int $id, array $data): bool {
        $payload = $this->normalizeInput($data);
        $this->db->query("UPDATE {$this->table} SET site = :site, afd = :afd, npk = :npk, nama = :nama, jabatan = :jabatan, aktif = :aktif WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        foreach ($payload as $key => $value) $this->db->bind($key, $value);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Mencari karyawan berdasarkan ID.
     * @param int $id ID karyawan.
     * @return array|null Data karyawan.
     */
    public function findById(int $id): ?array {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $row = $this->db->single();
        return $row ? $this->formatRow($row) : null;
    }

    /**
     * Mencari karyawan berdasarkan NPK.
     * @param string $npk NPK karyawan.
     * @return array|null Data karyawan.
     */
    public function findByNpk(string $npk): ?array {
        $this->db->query("SELECT * FROM {$this->table} WHERE npk = :npk");
        $this->db->bind('npk', trim($npk));
        $row = $this->db->single();
        return $row ? $this->formatRow($row) : null;
    }

    /**
     * Memeriksa apakah NPK sudah ada.
     * @param string $npk NPK.
     * @param int|null $excludeId ID yang dikecualikan.
     * @return bool True jika NPK ada.
     */
    public function npkExists(string $npk, ?int $excludeId = null): bool {
        $query = "SELECT COUNT(id) AS total FROM {$this->table} WHERE npk = :npk" . ($excludeId ? " AND id <> :exclude_id" : "");
        $this->db->query($query);
        $this->db->bind('npk', trim($npk));
        if ($excludeId) $this->db->bind('exclude_id', $excludeId, PDO::PARAM_INT);
        return (int)($this->db->single()['total'] ?? 0) > 0;
    }

    /**
     * Impor data karyawan secara massal.
     * @param array $rows Data karyawan.
     * @return array Hasil impor.
     */
    public function import(array $rows): array {
        $inserted = $updated = 0;
        $this->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $payload = $this->normalizeInput($row);
                if ($existing = $this->findByNpk($payload['npk'])) {
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
        return compact('inserted', 'updated');
    }

    private function ensureTableIsReady(): void {
        if ($this->tableReady) return;
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS {$this->table} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, site VARCHAR(10) NOT NULL, afd VARCHAR(10) NOT NULL, npk VARCHAR(20) NOT NULL UNIQUE, nama VARCHAR(100) NOT NULL, jabatan VARCHAR(100) NOT NULL, aktif TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX (site), INDEX (afd)) ENGINE=InnoDB");
            $this->db->execute();
            $this->tableReady = true;
        } catch (Exception $e) {
            $this->tableReady = false;
            throw $e;
        }
    }

    private function fetchFromDatabase(): array {
        $this->db->query("SELECT * FROM {$this->table} ORDER BY site, afd, nama");
        return array_map([$this, 'formatRow'], $this->db->resultSet());
    }

    private function formatRow(array $row): array {
        return ['id' => (int)($row['id'] ?? 0), 'site' => strtoupper(trim((string)($row['site'] ?? ''))), 'afd' => strtoupper(trim((string)($row['afd'] ?? ''))), 'npk' => trim((string)($row['npk'] ?? '')), 'nama' => strtoupper(trim((string)($row['nama'] ?? ''))), 'jabatan' => strtoupper(trim((string)($row['jabatan'] ?? ''))), 'aktif' => (bool)($row['aktif'] ?? false)];
    }

    private function normalizeInput(array $data): array {
        $aktif = $data['aktif'] ?? 'Y';
        return ['site' => strtoupper(trim((string)($data['site'] ?? ''))), 'afd' => strtoupper(trim((string)($data['afd'] ?? ''))), 'npk' => preg_replace('/\s+/', '', trim((string)($data['npk'] ?? ''))), 'nama' => strtoupper(trim((string)($data['nama'] ?? ''))), 'jabatan' => ($j = strtoupper(trim((string)($data['jabatan'] ?? 'MANDOR')))) ? $j : 'MANDOR', 'aktif' => is_string($aktif) ? (in_array(strtoupper(trim($aktif)), ['Y', '1', 'AKTIF']) ? 1 : 0) : ($aktif ? 1 : 0)];
    }

    private function buildFilterClause(?string $site, ?string $search): array {
        $clauses = [];
        $params = [];
        if ($site = $this->normalizeSite($site)) {
            $clauses[] = "site = :site";
            $params['site'] = $site;
        }
        if ($search = trim((string)$search)) {
            $clauses[] = "(site LIKE :keyword OR afd LIKE :keyword OR npk LIKE :keyword OR nama LIKE :keyword OR jabatan LIKE :keyword)";
            $params['keyword'] = '%' . addcslashes($search, "%_") . '%';
        }
        return [$clauses ? ' AND ' . implode(' AND ', $clauses) : '', $params];
    }

    private function normalizeSite(?string $site): ?string {
        $site = strtoupper(trim((string)$site));
        return in_array($site, ['BIM1', 'PPS1']) ? $site : null;
    }
}
