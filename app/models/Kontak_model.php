<?php
class Kontak_model {
    private $db;
    private $table = 'kontak_whatsapp';

    public function __construct() {
        $this->db = new Database;
    }

    public function getAllKontak($limit = null, $offset = 0) {
        return $this->getKontakPaginated('', $limit, $offset);
    }

    public function getKontakById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function tambahKontak($data) {
        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);
        
        if ($usingNewSchema) {
            $query = "INSERT INTO {$this->table} (site, afd, nama, nomer_wa, kategori) 
                      VALUES (:site, :afd, :nama, :nomer_wa, :kategori)";
        } else {
            $query = "INSERT INTO {$this->table} (nama_lengkap, nomor_telepon, whatsapp_lid, jabatan) 
                      VALUES (:nama, :nomer_wa, :whatsapp_lid, :kategori)";
        }
        
        $this->db->query($query);
        
        if ($usingNewSchema) {
            $this->db->bind('site', $data['site'] ?? null);
            $this->db->bind('afd', $data['afd'] ?? null);
            $this->db->bind('nama', $data['nama']);
            $nomerWa = $data['nomer_wa'] ?? null;
            $this->db->bind('nomer_wa', ($nomerWa === '' ? null : $nomerWa));
            $this->db->bind('kategori', $data['kategori'] ?? null);
        } else {
            $this->db->bind('nama', $data['nama']);
            $nomerWa = $data['nomer_wa'] ?? null;
            $this->db->bind('nomer_wa', ($nomerWa === '' ? null : $nomerWa));
            $this->db->bind('whatsapp_lid', null);
            $this->db->bind('kategori', $data['kategori'] ?? null);
        }
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    public function updateKontak($id, $data) {
        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);
        
        if ($usingNewSchema) {
            $query = "UPDATE {$this->table} SET 
                        site = :site,
                        afd = :afd,
                        nama = :nama,
                        nomer_wa = :nomer_wa,
                        kategori = :kategori
                      WHERE id = :id";
        } else {
            $query = "UPDATE {$this->table} SET 
                        nama_lengkap = :nama,
                        nomor_telepon = :nomer_wa,
                        whatsapp_lid = :whatsapp_lid,
                        jabatan = :kategori
                      WHERE id = :id";
        }
        
        $this->db->query($query);
        
        if ($usingNewSchema) {
            $this->db->bind('site', $data['site'] ?? null);
            $this->db->bind('afd', $data['afd'] ?? null);
            $this->db->bind('nama', $data['nama']);
            $nomerWa = $data['nomer_wa'] ?? null;
            $this->db->bind('nomer_wa', ($nomerWa === '' ? null : $nomerWa));
            $this->db->bind('kategori', $data['kategori'] ?? null);
        } else {
            $this->db->bind('nama', $data['nama']);
            $nomerWa = $data['nomer_wa'] ?? null;
            $this->db->bind('nomer_wa', ($nomerWa === '' ? null : $nomerWa));
            $this->db->bind('whatsapp_lid', null);
            $this->db->bind('kategori', $data['kategori'] ?? null);
        }
        
        $this->db->bind('id', $id, PDO::PARAM_INT);
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    public function hapusKontak($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    public function getKontakPaginated($search = '', $limit = null, $offset = 0) {
        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);

        if ($usingNewSchema) {
            $query = "SELECT id, site, afd, nama, nomer_wa, kategori, terakhir_update
                      FROM {$this->table}";
            $orderClause = " ORDER BY COALESCE(site, ''), COALESCE(afd, ''), nama ASC";
            $searchableColumns = ['site', 'afd', 'nama', 'nomer_wa', 'kategori'];
        } else {
            $query = "SELECT id, NULL AS site, NULL AS afd, nama_lengkap AS nama, nomor_telepon AS nomer_wa, jabatan AS kategori, terakhir_update
                      FROM {$this->table}";
            $orderClause = " ORDER BY nama_lengkap ASC";
            $searchableColumns = ['nama_lengkap', 'nomor_telepon', 'whatsapp_lid', 'jabatan'];
        }

        $params = [];
        if ($search !== '') {
            $conditions = [];
            foreach ($searchableColumns as $column) {
                $conditions[] = "{$column} LIKE :keyword";
            }
            $query .= ' WHERE ' . implode(' OR ', $conditions);
            $params['keyword'] = '%' . $search . '%';
        }

        $query .= $orderClause;

        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
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

    public function countFilteredKontak($search = '') {
        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);

        if ($usingNewSchema) {
            $query = "SELECT COUNT(id) AS total FROM {$this->table}";
            $searchableColumns = ['site', 'afd', 'nama', 'nomer_wa', 'kategori'];
        } else {
            $query = "SELECT COUNT(id) AS total FROM {$this->table}";
            $searchableColumns = ['nama_lengkap', 'nomor_telepon', 'whatsapp_lid', 'jabatan'];
        }

        if ($search !== '') {
            $conditions = [];
            foreach ($searchableColumns as $column) {
                $conditions[] = "{$column} LIKE :keyword";
            }
            $query .= ' WHERE ' . implode(' OR ', $conditions);
        }

        $this->db->query($query);

        if ($search !== '') {
            $this->db->bind('keyword', '%' . $search . '%');
        }

        $result = $this->db->single();
        return (int)($result['total'] ?? 0);
    }

    public function importKontakBatch(array $rows) {
        if (empty($rows)) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);

        if ($usingNewSchema) {
            $query = "INSERT INTO {$this->table} (site, afd, nama, nomer_wa, kategori)
                      VALUES (:site, :afd, :nama, :nomer_wa, :kategori)";
        } else {
            $query = "INSERT INTO {$this->table} (nama_lengkap, nomor_telepon, whatsapp_lid, jabatan)
                      VALUES (:nama, :nomer_wa, :whatsapp_lid, :kategori)";
        }

        $inserted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $nama = trim($row['nama'] ?? '');

            if ($nama === '') {
                $skipped++;
                continue;
            }

            $this->db->query($query);

            if ($usingNewSchema) {
                $this->db->bind('site', $row['site'] !== '' ? $row['site'] : null);
                $this->db->bind('afd', $row['afd'] !== '' ? $row['afd'] : null);
                $this->db->bind('nama', $nama);
                $this->db->bind('nomer_wa', $row['nomer_wa'] !== '' ? $row['nomer_wa'] : null);
                $this->db->bind('kategori', $row['kategori'] !== '' ? $row['kategori'] : null);
            } else {
                $this->db->bind('nama', $nama);
                $this->db->bind('nomer_wa', $row['nomer_wa'] !== '' ? $row['nomer_wa'] : null);
                $this->db->bind('whatsapp_lid', $row['nomer_wa'] !== '' ? $row['nomer_wa'] : null);
                $this->db->bind('kategori', $row['kategori'] !== '' ? $row['kategori'] : null);
            }

            try {
                $this->db->execute();
                if ($this->db->rowCount() > 0) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            } catch (Exception $e) {
                $skipped++;
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    public function getDistinctKategori() {
        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);

        $columnName = $usingNewSchema ? 'kategori' : 'jabatan';

        $this->db->query("SELECT DISTINCT {$columnName} AS kategori FROM {$this->table} WHERE {$columnName} IS NOT NULL AND {$columnName} <> '' ORDER BY {$columnName} ASC");
        $results = $this->db->resultSet();

        return array_values(array_filter(array_map(function ($row) {
            return trim((string)($row['kategori'] ?? ''));
        }, $results)));
    }

    public function searchKontak($keyword) {
        $columns = $this->getTableColumns();
        $usingNewSchema = $this->usingNewSchema($columns);
        
        if ($usingNewSchema) {
            $query = "SELECT id, site, afd, nama, nomer_wa, kategori, terakhir_update
                      FROM {$this->table}
                      WHERE site LIKE :keyword
                      OR afd LIKE :keyword
                      OR nama LIKE :keyword
                      OR nomer_wa LIKE :keyword
                      OR kategori LIKE :keyword
                      ORDER BY kategori ASC, nama ASC";
        } else {
            $query = "SELECT id, NULL AS site, NULL AS afd, nama_lengkap AS nama, nomor_telepon AS nomer_wa, jabatan AS kategori, terakhir_update
                      FROM {$this->table} 
                      WHERE nama_lengkap LIKE :keyword 
                      OR nomor_telepon LIKE :keyword 
                      OR whatsapp_lid LIKE :keyword
                      OR jabatan LIKE :keyword
                      ORDER BY nama_lengkap ASC";
        }
        
        $this->db->query($query);
        $this->db->bind('keyword', '%' . $keyword . '%');
        
        return $this->db->resultSet();
    }

    public function countAllKontak() {
        $this->db->query("SELECT COUNT(id) AS total FROM {$this->table}");
        $result = $this->db->single();
        return $result['total'];
    }
    
    private function getTableColumns() {
        try {
            $this->db->query("SHOW COLUMNS FROM {$this->table}");
            $result = $this->db->resultSet();
            $columns = [];
            foreach ($result as $column) {
                $columns[] = $column['Field'];
            }
            return $columns;
        } catch (Exception $e) {
            // Jika gagal karena driver tidak ditemukan atau error lainnya, asumsikan kolom tidak ada
            return []; // Kembalikan array kosong
        }
    }

    private function usingNewSchema(?array $columns = null) {
        if ($columns === null) {
            $columns = $this->getTableColumns();
        }

        $requiredColumns = ['site', 'afd', 'nama', 'nomer_wa', 'kategori'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                return false;
            }
        }

        return true;
    }
}
