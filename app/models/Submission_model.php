<?php
class Submission_model {
    private $db;
    private $table = 'file_submissions';
    private $kontakSchema = null;

    public function __construct() {
        $this->db = new Database;
    }

    /** Hitung semua file */
    public function getTotalFiles() {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table}");
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /** Hitung file per tipe */
    public function getCountByType($type) {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE file_type = :type");
        $this->db->bind('type', $type);
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /** Hitung file per tipe dan status */
    public function getCountByTypeAndStatus($type, $status) {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table} WHERE file_type = :type AND status = :status");
        $this->db->bind('type', $type);
        $this->db->bind('status', $status);
        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /** Ambil beberapa file terbaru untuk dashboard */
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
     * Ambil daftar file TU berdasarkan tanggal file (bukan tanggal kirim).
     *
     * @param string $fileDate format Y-m-d
     */
    public function getTuSubmissionsByFileDate($fileDate) {
        $joinClause = $this->getKontakJoinClause();
        $kontakNamaExpr = $this->getKontakNamaExpression('fs.sender_number');
        $parsedDateExpr = "COALESCE(
            NULLIF(STR_TO_DATE(fs.tanggal, '%Y%m%d'), '0000-00-00'),
            STR_TO_DATE(fs.tanggal, '%Y-%m-%d'),
            STR_TO_DATE(fs.tanggal, '%Y-%m-%d %H:%i:%s'),
            STR_TO_DATE(fs.tanggal, '%d-%m-%Y'),
            STR_TO_DATE(fs.tanggal, '%d-%m-%Y %H:%i:%s'),
            STR_TO_DATE(fs.tanggal, '%d/%m/%Y'),
            STR_TO_DATE(fs.tanggal, '%d/%m/%Y %H:%i:%s'),
            STR_TO_DATE(fs.tanggal, '%Y/%m/%d'),
            STR_TO_DATE(fs.tanggal, '%Y/%m/%d %H:%i:%s'),
            STR_TO_DATE(SUBSTRING_INDEX(fs.tanggal, ' ', 1), '%Y-%m-%d'),
            STR_TO_DATE(SUBSTRING_INDEX(fs.tanggal, ' ', 1), '%d-%m-%Y'),
            STR_TO_DATE(SUBSTRING_INDEX(fs.tanggal, ' ', 1), '%d/%m/%Y'),
            STR_TO_DATE(SUBSTRING_INDEX(fs.tanggal, ' ', 1), '%Y/%m/%d'),
            STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(fs.file_name, '-', 5), '-', -1), '%Y%m%d')
        )";
        $parsedDateExprDateOnly = "DATE({$parsedDateExpr})";

        $query = "SELECT
                    fs.*,
                    {$kontakNamaExpr} AS nama_lengkap,
                    {$parsedDateExprDateOnly} AS tanggal_file_date
                  FROM {$this->table} fs
                  {$joinClause}
                  WHERE fs.file_type = 'TU'
                    AND {$parsedDateExpr} IS NOT NULL
                    AND {$parsedDateExprDateOnly} = :file_date
                  ORDER BY fs.submission_date DESC";

        $this->db->query($query);
        $this->db->bind('file_date', $fileDate);
        return $this->db->resultSet();
    }

    /** Hitung total data untuk pagination + filter */
    public function countFilteredSubmissions($status, $filters = []) {
        $joinClause = $this->getKontakJoinClause();

        $query = "SELECT COUNT(fs.id) AS total
                  FROM {$this->table} fs
                  {$joinClause}
                  WHERE fs.status = :status";
        $params = [':status' => $status];

        if (!empty($filters['search']) && trim($filters['search']) !== '') {
            $search = trim(html_entity_decode(urldecode($filters['search'])));
            $search = preg_replace('/[\x00-\x1F\x7F]/u', '', $search);
            $params[':search'] = '%' . $search . '%';
            $query .= " AND fs.file_name LIKE :search";
        }

        $this->db->query($query);
        foreach ($params as $key => $value) {
            $this->db->bind(ltrim($key, ':'), $value);
        }

        $row = $this->db->single();
        return $row ? (int)$row['total'] : 0;
    }

    /** Ambil data dengan pagination, filter, sort */
    public function searchSubmissions($status, $start, $limit, $options = []) {
        // Normalisasi angka (wajib untuk LIMIT/OFFSET)
        $start = max(0, (int)$start);
        $limit = max(1, (int)$limit);

        $joinClause = $this->getKontakJoinClause();
        $kontakNamaExpr = $this->getKontakNamaExpression('fs.sender_number');

        $query = "SELECT
                    fs.*,
                    {$kontakNamaExpr} AS nama_lengkap
                  FROM {$this->table} fs
                  {$joinClause}
                  WHERE fs.status = :status";

        $params = [':status' => $status];

        // Filter: search by file_name
        if (!empty($options['search']) && trim($options['search']) !== '') {
            $search = trim(html_entity_decode(urldecode($options['search'])));
            $search = preg_replace('/[\x00-\x1F\x7F]/u', '', $search);
            $params[':search'] = '%' . $search . '%';
            $query .= " AND fs.file_name LIKE :search";
        }

        // Sorting
        $kolom_valid = ['submission_date', 'tanggal', 'file_name', 'file_type', 'file_size', 'nama_lengkap'];
        $sort_by  = $options['sort'] ?? 'submission_date';
        $sort_dir = strtoupper($options['dir'] ?? 'DESC');
        if (!in_array($sort_dir, ['ASC', 'DESC'])) $sort_dir = 'DESC';

        if (in_array($sort_by, $kolom_valid)) {
            if ($sort_by === 'nama_lengkap') {
                $sort_column = "({$kontakNamaExpr})";
            } else {
                $sort_column = "fs.{$sort_by}";
            }
        } else {
            $sort_column = 'fs.submission_date';
            $sort_dir    = 'DESC';
        }

        $query .= " ORDER BY {$sort_column} {$sort_dir}";
        $query .= " LIMIT {$limit} OFFSET {$start}";

        $this->db->query($query);
        foreach ($params as $key => $value) {
            $this->db->bind(ltrim($key, ':'), $value);
        }

        return $this->db->resultSet();
    }

    /** Ambil detail file by ID */
    public function getSubmissionById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', (int)$id, PDO::PARAM_INT);
        return $this->db->single();
    }

    /** Update setelah rename */
    public function updateSubmissionRename($id, $data) {
        $query = "UPDATE {$this->table} SET
                    original_file_name = :original_file_name,
                    file_name          = :file_name,
                    file_path          = :file_path,
                    status             = :status,
                    validation_notes   = :validation_notes,
                    file_type          = :file_type,
                    site_code          = :site_code,
                    afdeling           = :afdeling,
                    imei               = :imei,
                    unit_code          = :unit_code,
                    npk_driver         = :npk_driver,
                    npk_mandor         = :npk_mandor
                  WHERE id = :id";

        $this->db->query($query);
        $this->db->bind('id', (int)$id, PDO::PARAM_INT);

        foreach ($data as $key => $value) {
            $this->db->bind($key, $value);
        }

        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Ambil metadata struktur tabel kontak_whatsapp agar query dinamis.
     *
     * @return array{type:string, join_field:string, name_field:string}
     */
    private function getKontakSchema() {
        if ($this->kontakSchema !== null) {
            return $this->kontakSchema;
        }

        try {
            $this->db->query("SHOW COLUMNS FROM kontak_whatsapp");
            $result = $this->db->resultSet();
        } catch (Exception $e) {
            $result = [];
        }

        $columns = [];
        foreach ($result as $column) {
            $columns[] = $column['Field'];
        }

        if (in_array('nama', $columns, true) && in_array('nomer_wa', $columns, true)) {
            $this->kontakSchema = [
                'type' => 'new',
                'join_field' => 'nomer_wa',
                'name_field' => 'nama'
            ];
        } else {
            $this->kontakSchema = [
                'type' => 'old',
                'join_field' => 'whatsapp_lid',
                'name_field' => 'nama_lengkap'
            ];
        }

        return $this->kontakSchema;
    }

    /**
     * Bangun potongan LEFT JOIN kontak_whatsapp sesuai skema.
     */
    private function getKontakJoinClause() {
        $schema = $this->getKontakSchema();
        $joinField = 'kw.' . $schema['join_field'];

        if ($schema['type'] === 'new') {
            $sanitizedSender = $this->getSanitizedSenderExpression('fs.sender_number');
            return "LEFT JOIN kontak_whatsapp kw ON {$sanitizedSender} = {$joinField}";
        }

        return "LEFT JOIN kontak_whatsapp kw ON fs.sender_number = {$joinField}";
    }

    /**
     * Ekspresi SQL untuk nama kontak dengan fallback ke pengirim asli.
     */
    private function getKontakNamaExpression($fallbackColumn) {
        $schema = $this->getKontakSchema();
        $nameField = 'kw.' . $schema['name_field'];

        return "COALESCE(NULLIF({$nameField}, ''), {$fallbackColumn})";
    }

    /**
     * Normalisasi nomor pengirim agar cocok dengan nomer_wa (digit only).
     */
    private function getSanitizedSenderExpression($column) {
        $expr = $column;
        $replacements = [
            "'+'",
            "'-'",
            "' '",
            "'.'",
            "'@s.whatsapp.net'",
            "'@c.us'",
            "'@g.us'"
        ];

        foreach ($replacements as $replace) {
            $expr = "REPLACE({$expr}, {$replace}, '')";
        }

        return "TRIM({$expr})";
    }
}
