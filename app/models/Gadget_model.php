<?php
class Gadget_model {
    private Database $db;

    /**
     * @var array<string,string>
     */
    private array $tableMap = [
        'bim1' => 'gadget_bim1_devices',
        'pps1' => 'gadget_pps1_devices',
    ];

    public function __construct() {
        $this->db = new Database;
    }

    /**
     * @param 'bim1'|'pps1' $type
     */
    private function resolveTable(string $type): string {
        $normalized = strtolower(trim($type));
        if (!isset($this->tableMap[$normalized])) {
            throw new InvalidArgumentException('Tipe gadget tidak dikenal: ' . $type);
        }

        $table = $this->tableMap[$normalized];
        $this->ensureTableExists($table);

        return $table;
    }

    private function ensureTableExists(string $table): void {
        $createSql = "CREATE TABLE IF NOT EXISTS {$table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            imei VARCHAR(64) NOT NULL,
            aplikasi VARCHAR(128) DEFAULT NULL,
            pt VARCHAR(32) DEFAULT NULL,
            afd VARCHAR(32) DEFAULT NULL,
            npk_pengguna VARCHAR(32) DEFAULT NULL,
            nama VARCHAR(128) DEFAULT NULL,
            pos_title VARCHAR(128) DEFAULT NULL,
            group_asset VARCHAR(128) DEFAULT NULL,
            tipe_asset VARCHAR(128) DEFAULT NULL,
            part_asset VARCHAR(128) DEFAULT NULL,
            jumlah INT DEFAULT 0,
            asal_desc VARCHAR(255) DEFAULT NULL,
            status_desc VARCHAR(255) DEFAULT NULL,
            note TEXT DEFAULT NULL,
            action VARCHAR(255) DEFAULT NULL,
            imported_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_imei (imei),
            INDEX idx_npk (npk_pengguna),
            INDEX idx_nama (nama)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->db->query($createSql);
        $this->db->execute();
    }

    /**
     * @param 'bim1'|'pps1' $type
     */
    public function getDevices(string $type, string $search = '', ?int $limit = 25, int $offset = 0): array {
        $table = $this->resolveTable($type);

        $query = "SELECT id, imei, aplikasi, pt, afd, npk_pengguna, nama, pos_title, group_asset,
                          tipe_asset, part_asset, jumlah, asal_desc, status_desc, note, action,
                          imported_at, updated_at
                   FROM {$table}";

        $searchableColumns = [
            'imei', 'aplikasi', 'pt', 'afd', 'npk_pengguna', 'nama', 'pos_title',
            'group_asset', 'tipe_asset', 'part_asset', 'asal_desc', 'status_desc', 'note', 'action'
        ];

        $params = [];
        if ($search !== '') {
            $conditions = [];
            foreach ($searchableColumns as $column) {
                $conditions[] = "{$column} LIKE :keyword";
            }
            $query .= ' WHERE ' . implode(' OR ', $conditions);
            $params['keyword'] = '%' . $search . '%';
        }

        $query .= ' ORDER BY nama ASC, imei ASC';

        if ($limit !== null) {
            $query .= ' LIMIT :limit OFFSET :offset';
        }

        $this->db->query($query);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        if ($limit !== null) {
            $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
            $this->db->bind('offset', (int)$offset, PDO::PARAM_INT);
        }

        return $this->db->resultSet();
    }

    /**
     * @param 'bim1'|'pps1' $type
     */
    public function countDevices(string $type, string $search = ''): int {
        $table = $this->resolveTable($type);

        $query = "SELECT COUNT(*) AS total FROM {$table}";
        $params = [];
        $searchableColumns = [
            'imei', 'aplikasi', 'pt', 'afd', 'npk_pengguna', 'nama', 'pos_title',
            'group_asset', 'tipe_asset', 'part_asset', 'asal_desc', 'status_desc', 'note', 'action'
        ];

        if ($search !== '') {
            $conditions = [];
            foreach ($searchableColumns as $column) {
                $conditions[] = "{$column} LIKE :keyword";
            }
            $query .= ' WHERE ' . implode(' OR ', $conditions);
            $params['keyword'] = '%' . $search . '%';
        }

        $this->db->query($query);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * @param 'bim1'|'pps1' $type
     * @return array{inserted:int}
     */
    public function replaceDevices(string $type, array $rows): array {
        $table = $this->resolveTable($type);
        $now = date('Y-m-d H:i:s');

        $this->db->beginTransaction();

        try {
            $this->db->query("DELETE FROM {$table}");
            $this->db->execute();

            if (!empty($rows)) {
                $insertQuery = "INSERT INTO {$table}
                    (imei, aplikasi, pt, afd, npk_pengguna, nama, pos_title, group_asset,
                     tipe_asset, part_asset, jumlah, asal_desc, status_desc, note, action, imported_at)
                    VALUES
                    (:imei, :aplikasi, :pt, :afd, :npk_pengguna, :nama, :pos_title, :group_asset,
                     :tipe_asset, :part_asset, :jumlah, :asal_desc, :status_desc, :note, :action, :imported_at)";

                $this->db->query($insertQuery);

                $inserted = 0;
                foreach ($rows as $row) {
                    $this->db->bind('imei', $row['imei']);
                    $this->db->bind('aplikasi', $row['aplikasi']);
                    $this->db->bind('pt', $row['pt']);
                    $this->db->bind('afd', $row['afd']);
                    $this->db->bind('npk_pengguna', $row['npk_pengguna']);
                    $this->db->bind('nama', $row['nama']);
                    $this->db->bind('pos_title', $row['pos_title']);
                    $this->db->bind('group_asset', $row['group_asset']);
                    $this->db->bind('tipe_asset', $row['tipe_asset']);
                    $this->db->bind('part_asset', $row['part_asset']);
                    $this->db->bind('jumlah', $row['jumlah'], PDO::PARAM_INT);
                    $this->db->bind('asal_desc', $row['asal_desc']);
                    $this->db->bind('status_desc', $row['status_desc']);
                    $this->db->bind('note', $row['note']);
                    $this->db->bind('action', $row['action']);
                    $this->db->bind('imported_at', $now);

                    $this->db->execute();
                    $inserted += $this->db->rowCount() > 0 ? 1 : 0;
                }
            } else {
                $inserted = 0;
            }

            $this->db->commit();

            return ['inserted' => $inserted];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /**
     * @param 'bim1'|'pps1' $type
     */
    public function getLastImportedAt(string $type): ?string {
        $table = $this->resolveTable($type);
        $this->db->query("SELECT MAX(imported_at) AS last_imported FROM {$table}");
        $result = $this->db->single();
        $value = $result['last_imported'] ?? null;
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }
}
