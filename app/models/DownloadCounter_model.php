<?php
/**
 * Class DownloadCounter_model
 *
 * Model untuk mengelola jumlah unduhan file template.
 * Membuat tabel secara dinamis jika belum ada.
 */
class DownloadCounter_model {
    /** @var Database Instance dari kelas Database. */
    private $db;
    /** @var string Nama tabel untuk penghitung unduhan. */
    private $table = 'template_downloads';

    /**
     * DownloadCounter_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
        $this->createTableIfNotExists();
    }

    /**
     * Membuat tabel jika belum ada.
     */
    private function createTableIfNotExists() {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL UNIQUE,
                    download_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  )";
        $this->db->query($query);
        $this->db->execute();
    }

    /**
     * Menambah jumlah unduhan untuk file tertentu.
     * @param string $filename Nama file.
     */
    public function incrementDownload($filename) {
        $this->db->query("SELECT id FROM {$this->table} WHERE filename = :filename");
        $this->db->bind('filename', $filename);
        if ($this->db->single()) {
            $this->db->query("UPDATE {$this->table} SET download_count = download_count + 1 WHERE filename = :filename");
        } else {
            $this->db->query("INSERT INTO {$this->table} (filename, download_count) VALUES (:filename, 1)");
        }
        $this->db->bind('filename', $filename);
        $this->db->execute();
    }

    /**
     * Mendapatkan jumlah unduhan untuk file tertentu.
     * @param string $filename Nama file.
     * @return int Jumlah unduhan.
     */
    public function getDownloadCount($filename) {
        $this->db->query("SELECT download_count FROM {$this->table} WHERE filename = :filename");
        $this->db->bind('filename', $filename);
        $result = $this->db->single();
        return $result ? (int)$result['download_count'] : 0;
    }
    
    /**
     * Mendapatkan semua data unduhan.
     * @return array Daftar semua unduhan.
     */
    public function getAllDownloads() {
        $this->db->query("SELECT * FROM {$this->table} ORDER BY download_count DESC");
        return $this->db->resultSet();
    }
}
