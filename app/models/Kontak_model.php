<?php
/**
 * Class Kontak_model
 *
 * Model untuk mengelola data kontak WhatsApp.
 * Mendukung skema tabel lama dan baru secara dinamis.
 */
class Kontak_model {
    /** @var Database Instance dari kelas Database. */
    private $db;
    /** @var string Nama tabel kontak. */
    private $table = 'kontak_whatsapp';

    /**
     * Kontak_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
    }

    /**
     * Mengambil semua kontak dengan paginasi.
     * @param int|null $limit Batas jumlah data.
     * @param int $offset Posisi awal data.
     * @return array Daftar kontak.
     */
    public function getAllKontak($limit = null, $offset = 0) {
        return $this->getKontakPaginated('', $limit, $offset);
    }

    /**
     * Mengambil kontak berdasarkan ID.
     * @param int $id ID kontak.
     * @return mixed Data kontak.
     */
    public function getKontakById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    /**
     * Menambahkan kontak baru.
     * @param array $data Data kontak baru.
     * @return bool True jika berhasil.
     */
    public function tambahKontak($data) {
        $usingNewSchema = $this->usingNewSchema();
        $query = $usingNewSchema
            ? "INSERT INTO {$this->table} (site, afd, nama, nomer_wa, kategori) VALUES (:site, :afd, :nama, :nomer_wa, :kategori)"
            : "INSERT INTO {$this->table} (nama_lengkap, nomor_telepon, whatsapp_lid, jabatan) VALUES (:nama, :nomer_wa, :whatsapp_lid, :kategori)";
        
        $this->db->query($query);
        $this->bindKontakData($data, $usingNewSchema);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Memperbarui kontak.
     * @param int $id ID kontak.
     * @param array $data Data kontak yang diperbarui.
     * @return bool True jika berhasil.
     */
    public function updateKontak($id, $data) {
        $usingNewSchema = $this->usingNewSchema();
        $query = $usingNewSchema
            ? "UPDATE {$this->table} SET site = :site, afd = :afd, nama = :nama, nomer_wa = :nomer_wa, kategori = :kategori WHERE id = :id"
            : "UPDATE {$this->table} SET nama_lengkap = :nama, nomor_telepon = :nomer_wa, whatsapp_lid = :whatsapp_lid, jabatan = :kategori WHERE id = :id";

        $this->db->query($query);
        $this->bindKontakData($data, $usingNewSchema);
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Menghapus kontak.
     * @param int $id ID kontak.
     * @return bool True jika berhasil.
     */
    public function hapusKontak($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /**
     * Mengambil kontak dengan paginasi dan filter pencarian.
     * @param string $search Kata kunci pencarian.
     * @param int|null $limit Batas jumlah data.
     * @param int $offset Posisi awal data.
     * @return array Daftar kontak.
     */
    public function getKontakPaginated($search = '', $limit = null, $offset = 0) {
        $usingNewSchema = $this->usingNewSchema();
        $baseQuery = $usingNewSchema
            ? "SELECT id, site, afd, nama, nomer_wa, kategori, terakhir_update FROM {$this->table}"
            : "SELECT id, NULL AS site, NULL AS afd, nama_lengkap AS nama, nomor_telepon AS nomer_wa, jabatan AS kategori, terakhir_update FROM {$this->table}";

        $searchable = $usingNewSchema ? ['site', 'afd', 'nama', 'nomer_wa', 'kategori'] : ['nama_lengkap', 'nomor_telepon', 'jabatan'];
        $order = $usingNewSchema ? "COALESCE(site, ''), COALESCE(afd, ''), nama ASC" : "nama_lengkap ASC";

        $query = $this->buildSearchQuery($baseQuery, $search, $searchable);
        $query .= " ORDER BY {$order}";
        if ($limit !== null) $query .= " LIMIT :limit OFFSET :offset";

        $this->db->query($query);
        if ($search !== '') $this->db->bind('keyword', '%' . $search . '%');
        if ($limit !== null) {
            $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
            $this->db->bind('offset', (int)$offset, PDO::PARAM_INT);
        }
        return $this->db->resultSet();
    }

    /**
     * Menghitung total kontak dengan filter pencarian.
     * @param string $search Kata kunci pencarian.
     * @return int Total kontak.
     */
    public function countFilteredKontak($search = '') {
        $usingNewSchema = $this->usingNewSchema();
        $baseQuery = "SELECT COUNT(id) AS total FROM {$this->table}";
        $searchable = $usingNewSchema ? ['site', 'afd', 'nama', 'nomer_wa', 'kategori'] : ['nama_lengkap', 'nomor_telepon', 'jabatan'];

        $query = $this->buildSearchQuery($baseQuery, $search, $searchable);

        $this->db->query($query);
        if ($search !== '') $this->db->bind('keyword', '%' . $search . '%');

        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Impor kontak secara massal.
     * @param array $rows Data kontak.
     * @return array Hasil impor.
     */
    public function importKontakBatch(array $rows) {
        if (empty($rows)) return ['inserted' => 0, 'skipped' => 0];

        $usingNewSchema = $this->usingNewSchema();
        $query = $usingNewSchema
            ? "INSERT INTO {$this->table} (site, afd, nama, nomer_wa, kategori) VALUES (:site, :afd, :nama, :nomer_wa, :kategori)"
            : "INSERT INTO {$this->table} (nama_lengkap, nomor_telepon, whatsapp_lid, jabatan) VALUES (:nama, :nomer_wa, :whatsapp_lid, :kategori)";

        $inserted = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            if (empty(trim($row['nama'] ?? ''))) {
                $skipped++;
                continue;
            }
            try {
                $this->db->query($query);
                $this->bindKontakData($row, $usingNewSchema);
                $this->db->execute();
                if ($this->db->rowCount() > 0) $inserted++;
                else $skipped++;
            } catch (Exception $e) {
                $skipped++;
            }
        }
        return compact('inserted', 'skipped');
    }

    /**
     * Mengambil daftar kategori/jabatan unik.
     * @return array Daftar kategori.
     */
    public function getDistinctKategori() {
        $columnName = $this->usingNewSchema() ? 'kategori' : 'jabatan';
        $this->db->query("SELECT DISTINCT {$columnName} AS kategori FROM {$this->table} WHERE {$columnName} IS NOT NULL AND {$columnName} <> '' ORDER BY {$columnName} ASC");
        return array_column($this->db->resultSet(), 'kategori');
    }

    /**
     * Helper untuk bind data kontak ke query.
     * @param array $data Data kontak.
     * @param bool $isNewSchema Apakah menggunakan skema baru.
     */
    private function bindKontakData($data, $isNewSchema) {
        $nomerWa = empty($data['nomer_wa']) ? null : $data['nomer_wa'];
        if ($isNewSchema) {
            $this->db->bind('site', empty($data['site']) ? null : $data['site']);
            $this->db->bind('afd', empty($data['afd']) ? null : $data['afd']);
            $this->db->bind('nama', $data['nama']);
            $this->db->bind('nomer_wa', $nomerWa);
            $this->db->bind('kategori', empty($data['kategori']) ? null : $data['kategori']);
        } else {
            $this->db->bind('nama', $data['nama']);
            $this->db->bind('nomer_wa', $nomerWa);
            $this->db->bind('whatsapp_lid', $nomerWa);
            $this->db->bind('kategori', empty($data['kategori']) ? null : $data['kategori']);
        }
    }
    
    /**
     * Helper untuk membangun query pencarian.
     * @param string $baseQuery Query dasar.
     * @param string $search Kata kunci.
     * @param array $columns Kolom yang dicari.
     * @return string Query lengkap.
     */
    private function buildSearchQuery($baseQuery, $search, $columns) {
        if ($search !== '') {
            $conditions = array_map(fn($col) => "{$col} LIKE :keyword", $columns);
            $baseQuery .= ' WHERE ' . implode(' OR ', $conditions);
        }
        return $baseQuery;
    }

    /**
     * Mendeteksi skema tabel kontak.
     * @return bool True jika skema baru.
     */
    private function usingNewSchema() {
        static $isNew;
        if ($isNew === null) {
            try {
                $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'nomer_wa'");
                $isNew = $this->db->rowCount() > 0;
            } catch (Exception $e) {
                $isNew = false;
            }
        }
        return $isNew;
    }
}
