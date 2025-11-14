<?php
/**
 * Class Submission_model
 *
 * Model untuk mengelola data pengiriman file (file_submissions).
 * Menyediakan metode untuk mengambil, menghitung, dan memanipulasi data laporan.
 */
class Submission_model {
    /** @var Database Instance dari kelas Database. */
    private $db;
    /** @var string Nama tabel di database. */
    private $table = 'file_submissions';
    /** @var array|null Skema tabel kontak untuk join dinamis. */
    private $kontakSchema = null;

    /**
     * Submission_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
    }

    /**
     * Menghitung total semua file yang tersimpan.
     * @return int Total file.
     */
    public function getTotalFiles() {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table}");
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Menghitung jumlah file berdasarkan tipe.
     * @param string $type Tipe file (misal: 'TRB', 'TU').
     * @return int Jumlah file.
     */
    public function getCountByType($type) {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE file_type = :type");
        $this->db->bind('type', $type);
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Menghitung jumlah file berdasarkan tipe dan status.
     * @param string $type Tipe file.
     * @param string $status Status file ('valid' atau 'invalid').
     * @return int Jumlah file.
     */
    public function getCountByTypeAndStatus($type, $status) {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE file_type = :type AND status = :status");
        $this->db->bind('type', $type);
        $this->db->bind('status', $status);
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Mengambil beberapa file terbaru untuk ditampilkan di dashboard.
     * @param int $limit Jumlah file yang akan diambil.
     * @return array Daftar file terbaru.
     */
    public function getRecentSubmissions($limit = 10) {
        $joinClause = $this->getKontakJoinClause();
        $kontakNamaExpr = $this->getKontakNamaExpression('fs.sender_number');

        $this->db->query(
            "SELECT fs.*, {$kontakNamaExpr} AS nama_lengkap
             FROM {$this->table} fs
             {$joinClause}
             ORDER BY fs.submission_date DESC
             LIMIT :limit"
        );
        $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Mengambil daftar file TU berdasarkan tanggal yang diekstrak dari nama file.
     * @param string $fileDate Tanggal dalam format Y-m-d.
     * @return array Daftar file TU.
     */
    public function getTuSubmissionsByFileDate($fileDate) {
        $joinClause = $this->getKontakJoinClause();
        $kontakNamaExpr = $this->getKontakNamaExpression('fs.sender_number');
        $parsedDateExpr = "COALESCE(
            NULLIF(STR_TO_DATE(fs.tanggal, '%Y%m%d'), '0000-00-00'),
            STR_TO_DATE(fs.tanggal, '%Y-%m-%d'),
            STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(fs.file_name, '-', 5), '-', -1), '%Y%m%d')
        )";
        $parsedDateExprDateOnly = "DATE({$parsedDateExpr})";

        $query = "SELECT fs.*, {$kontakNamaExpr} AS nama_lengkap, {$parsedDateExprDateOnly} AS tanggal_file_date
                  FROM {$this->table} fs
                  {$joinClause}
                  WHERE fs.file_type = 'TU' AND {$parsedDateExpr} IS NOT NULL AND {$parsedDateExprDateOnly} = :file_date
                  ORDER BY fs.submission_date DESC";

        $this->db->query($query);
        $this->db->bind('file_date', $fileDate);
        return $this->db->resultSet();
    }

    /**
     * Menghitung total data untuk paginasi dengan filter.
     * @param string $status Status file.
     * @param array $filters Opsi filter.
     * @return int Total data.
     */
    public function countFilteredSubmissions($status, $filters = []) {
        $joinClause = $this->getKontakJoinClause();
        $query = "SELECT COUNT(fs.id) AS total FROM {$this->table} fs {$joinClause}";
        $conditions = [];
        $params = [];

        if ($status !== 'all') {
            $conditions[] = "fs.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($filters['search'])) {
            $conditions[] = "fs.file_name LIKE :search";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['file_type'])) {
            $conditions[] = "fs.file_type = :file_type";
            $params[':file_type'] = $filters['file_type'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind(ltrim($key, ':'), $value);

        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Mengambil data dengan paginasi, filter, dan sorting.
     * @param string $status Status file.
     * @param int $start Posisi awal data.
     * @param int $limit Jumlah data per halaman.
     * @param array $options Opsi filter dan sorting.
     * @return array Daftar data.
     */
    public function searchSubmissions($status, $start, $limit, $options = []) {
        $joinClause = $this->getKontakJoinClause();
        $kontakNamaExpr = $this->getKontakNamaExpression('fs.sender_number');

        $query = "SELECT fs.*, {$kontakNamaExpr} AS nama_lengkap FROM {$this->table} fs {$joinClause}";
        $conditions = [];
        $params = [];

        if ($status !== 'all') {
            $conditions[] = "fs.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($options['search'])) {
            $conditions[] = "fs.file_name LIKE :search";
            $params[':search'] = '%' . trim($options['search']) . '%';
        }

        if (!empty($options['file_type'])) {
            $conditions[] = "fs.file_type = :file_type";
            $params[':file_type'] = $options['file_type'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $kolom_valid = ['submission_date', 'tanggal', 'file_name', 'file_type', 'file_size', 'nama_lengkap'];
        $sort_by = in_array($options['sort'] ?? '', $kolom_valid) ? $options['sort'] : 'submission_date';
        $sort_dir = in_array(strtoupper($options['dir'] ?? ''), ['ASC', 'DESC']) ? strtoupper($options['dir']) : 'DESC';
        $sort_column = $sort_by === 'nama_lengkap' ? "({$kontakNamaExpr})" : "fs.{$sort_by}";

        $query .= " ORDER BY {$sort_column} {$sort_dir} LIMIT " . (int)$limit . " OFFSET " . (int)$start;

        $this->db->query($query);
        foreach ($params as $key => $value) $this->db->bind(ltrim($key, ':'), $value);

        return $this->db->resultSet();
    }

    /**
     * Mengambil detail file berdasarkan ID.
     * @param int $id ID file.
     * @return mixed Detail file.
     */
    public function getSubmissionById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', (int)$id, PDO::PARAM_INT);
        return $this->db->single();
    }

    /**
     * Memperbarui data setelah file diganti nama.
     * @param int $id ID file.
     * @param array $data Data baru.
     * @return bool True jika berhasil.
     */
    public function updateSubmissionRename($id, $data) {
        $query = "UPDATE {$this->table} SET
                    original_file_name = :original_file_name, file_name = :file_name, file_path = :file_path,
                    status = :status, validation_notes = :validation_notes, file_type = :file_type,
                    site_code = :site_code, afdeling = :afdeling, imei = :imei,
                    unit_code = :unit_code, npk_driver = :npk_driver, npk_mandor = :npk_mandor
                  WHERE id = :id";

        $this->db->query($query);
        $this->db->bind('id', (int)$id, PDO::PARAM_INT);
        foreach ($data as $key => $value) $this->db->bind($key, $value);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Mendeteksi skema tabel kontak_whatsapp (lama atau baru).
     * @return array Skema tabel.
     */
    private function getKontakSchema() {
        if ($this->kontakSchema !== null) return $this->kontakSchema;

        try {
            $this->db->query("SHOW COLUMNS FROM kontak_whatsapp");
            $columns = array_column($this->db->resultSet(), 'Field');
        } catch (Exception $e) {
            $columns = [];
        }

        if (in_array('nomer_wa', $columns, true)) {
            $this->kontakSchema = ['type' => 'new', 'join_field' => 'nomer_wa', 'name_field' => 'nama'];
        } else {
            $this->kontakSchema = ['type' => 'old', 'join_field' => 'whatsapp_lid', 'name_field' => 'nama_lengkap'];
        }
        return $this->kontakSchema;
    }

    /**
     * Membangun klausa LEFT JOIN untuk tabel kontak.
     * @return string Klausa JOIN.
     */
    private function getKontakJoinClause() {
        $schema = $this->getKontakSchema();
        $joinField = 'kw.' . $schema['join_field'];
        $senderExpr = $schema['type'] === 'new' ? $this->getSanitizedSenderExpression('fs.sender_number') : 'fs.sender_number';
        return "LEFT JOIN kontak_whatsapp kw ON {$senderExpr} = {$joinField}";
    }

    /**
     * Mendapatkan ekspresi SQL untuk nama kontak.
     * @param string $fallbackColumn Kolom fallback jika nama tidak ditemukan.
     * @return string Ekspresi SQL.
     */
    private function getKontakNamaExpression($fallbackColumn) {
        $schema = $this->getKontakSchema();
        return "COALESCE(NULLIF(kw.{$schema['name_field']}, ''), {$fallbackColumn})";
    }

    /**
     * Menghasilkan ekspresi SQL untuk membersihkan nomor pengirim.
     * @param string $column Nama kolom.
     * @return string Ekspresi SQL.
     */
    private function getSanitizedSenderExpression($column) {
        $expr = $column;
        $replacements = ["'+'", "'-'", "' '", "'.'", "'@s.whatsapp.net'", "'@c.us'", "'@g.us'"];
        foreach ($replacements as $r) $expr = "REPLACE({$expr}, {$r}, '')";
        return "TRIM({$expr})";
    }
}
