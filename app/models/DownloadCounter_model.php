<?php
class DownloadCounter_model {
    private $db;
    private $table = 'template_downloads';

    public function __construct() {
        $this->db = new Database;
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists() {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL UNIQUE,
                    download_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $this->db->query($query);
        $this->db->execute();
    }

    public function incrementDownload($filename) {
        // Periksa apakah entri sudah ada
        $this->db->query("SELECT id FROM {$this->table} WHERE filename = :filename");
        $this->db->bind('filename', $filename);
        $result = $this->db->single();
        
        if ($result) {
            // Jika sudah ada, tambahkan count
            $this->db->query("UPDATE {$this->table} SET download_count = download_count + 1 WHERE filename = :filename");
        } else {
            // Jika belum ada, buat baru
            $this->db->query("INSERT INTO {$this->table} (filename, download_count) VALUES (:filename, 1)");
        }
        
        $this->db->bind('filename', $filename);
        $this->db->execute();
    }

    public function getDownloadCount($filename) {
        $this->db->query("SELECT download_count FROM {$this->table} WHERE filename = :filename");
        $this->db->bind('filename', $filename);
        $result = $this->db->single();
        
        return $result ? (int)$result['download_count'] : 0;
    }
    
    public function getAllDownloads() {
        $this->db->query("SELECT * FROM {$this->table} ORDER BY download_count DESC");
        return $this->db->resultSet();
    }
}