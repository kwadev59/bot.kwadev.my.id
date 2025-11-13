<?php
/**
 * Class Gadget_model
 *
 * Model untuk mengelola data gadget (perangkat) untuk berbagai site.
 * Membuat tabel secara dinamis jika belum ada.
 */
class Gadget_model {
    /** @var Database Instance dari kelas Database. */
    private Database $db;

    /** @var array<string,string> Mapping tipe ke nama tabel. */
    private array $tableMap = [
        'bim1' => 'gadget_bim1_devices',
        'pps1' => 'gadget_pps1_devices',
    ];

    /**
     * Gadget_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
    }

    /**
     * Menyelesaikan nama tabel berdasarkan tipe gadget.
     * @param 'bim1'|'pps1' $type Tipe gadget.
     * @return string Nama tabel.
     * @throws InvalidArgumentException Jika tipe tidak dikenal.
     */
    private function resolveTable(string $type): string {
        $normalized = strtolower(trim($type));
        if (!isset($this->tableMap[$normalized])) {
            throw new InvalidArgumentException('Tipe gadget tidak dikenal: ' . $type);
        }
        $this->ensureTableExists($this->tableMap[$normalized]);
        return $this->tableMap[$normalized];
    }

    /**
     * Memastikan tabel untuk gadget ada di database.
     * @param string $table Nama tabel.
     */
    private function ensureTableExists(string $table): void {
        $createSql = "CREATE TABLE IF NOT EXISTS {$table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            imei VARCHAR(64) NOT NULL, aplikasi VARCHAR(128), pt VARCHAR(32), afd VARCHAR(32),
            npk_pengguna VARCHAR(32), nama VARCHAR(128), pos_title VARCHAR(128),
            group_asset VARCHAR(128), tipe_asset VARCHAR(128), part_asset VARCHAR(128),
            jumlah INT DEFAULT 0, asal_desc VARCHAR(255), status_desc VARCHAR(255),
            note TEXT, action VARCHAR(255), imported_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_imei (imei), INDEX idx_npk (npk_pengguna), INDEX idx_nama (nama)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $this->db->query($createSql);
        $this->db->execute();
    }

    /**
     * Mengambil daftar perangkat dengan paginasi dan pencarian.
     * @param 'bim1'|'pps1' $type Tipe gadget.
     * @param string $search Kata kunci pencarian.
     * @param int|null $limit Batas jumlah data.
     * @param int $offset Posisi awal.
     * @return array Daftar perangkat.
     */
    public function getDevices(string $type, string $search = '', ?int $limit = 25, int $offset = 0): array {
        $table = $this->resolveTable($type);
        $query = "SELECT * FROM {$table}";
        $searchable = ['imei', 'aplikasi', 'pt', 'afd', 'npk_pengguna', 'nama', 'pos_title', 'group_asset', 'tipe_asset', 'part_asset', 'asal_desc', 'status_desc', 'note', 'action'];

        $params = [];
        if ($search !== '') {
            $conditions = array_map(fn($col) => "{$col} LIKE :keyword", $searchable);
            $query .= ' WHERE ' . implode(' OR ', $conditions);
            $params['keyword'] = '%' . $search . '%';
        }
        $query .= ' ORDER BY nama ASC, imei ASC';
        if ($limit !== null) $query .= ' LIMIT :limit OFFSET :offset';

        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind($key, $value);
        if ($limit !== null) {
            $this->db->bind('limit', $limit, PDO::PARAM_INT);
            $this->db->bind('offset', $offset, PDO::PARAM_INT);
        }
        return $this->db->resultSet();
    }

    /**
     * Menghitung jumlah perangkat dengan filter pencarian.
     * @param 'bim1'|'pps1' $type Tipe gadget.
     * @param string $search Kata kunci pencarian.
     * @return int Jumlah perangkat.
     */
    public function countDevices(string $type, string $search = ''): int {
        $table = $this->resolveTable($type);
        $query = "SELECT COUNT(*) AS total FROM {$table}";
        $params = [];
        if ($search !== '') {
            $searchable = ['imei', 'aplikasi', 'pt', 'afd', 'npk_pengguna', 'nama'];
            $conditions = array_map(fn($col) => "{$col} LIKE :keyword", $searchable);
            $query .= ' WHERE ' . implode(' OR ', $conditions);
            $params['keyword'] = '%' . $search . '%';
        }

        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind($key, $value);

        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Mengganti semua data perangkat dengan data baru (impor).
     * @param 'bim1'|'pps1' $type Tipe gadget.
     * @param array $rows Data baru.
     * @return array Hasil impor.
     */
    public function replaceDevices(string $type, array $rows): array {
        $table = $this->resolveTable($type);
        $now = date('Y-m-d H:i:s');
        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM {$table}");
            $this->db->execute();

            $inserted = 0;
            if (!empty($rows)) {
                $insertQuery = "INSERT INTO {$table} (imei, aplikasi, pt, afd, npk_pengguna, nama, pos_title, group_asset, tipe_asset, part_asset, jumlah, asal_desc, status_desc, note, action, imported_at) VALUES (:imei, :aplikasi, :pt, :afd, :npk_pengguna, :nama, :pos_title, :group_asset, :tipe_asset, :part_asset, :jumlah, :asal_desc, :status_desc, :note, :action, :imported_at)";
                $this->db->query($insertQuery);
                foreach ($rows as $row) {
                    foreach ($row as $key => $value) $this->db->bind($key, $value);
                    $this->db->bind('imported_at', $now);
                    $this->db->execute();
                    if($this->db->rowCount() > 0) $inserted++;
                }
            }
            $this->db->commit();
            return ['inserted' => $inserted];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mendapatkan timestamp impor terakhir.
     * @param 'bim1'|'pps1' $type Tipe gadget.
     * @return string|null Timestamp impor.
     */
    public function getLastImportedAt(string $type): ?string {
        $table = $this->resolveTable($type);
        $this->db->query("SELECT MAX(imported_at) AS last_imported FROM {$table}");
        $result = $this->db->single();
        return $result['last_imported'] ?? null;
    }
}
