<?php
/**
 * Class Log_model
 *
 * Model untuk mengelola data log.
 * Menyediakan metode untuk mengambil log file duplikat dan log aktivitas bot.
 */
class Log_model {
    /** @var Database Instance dari kelas Database. */
    private $db;

    /**
     * Log_model constructor.
     */
    public function __construct() {
        $this->db = new Database;
    }

    /**
     * Mengambil log file duplikat dengan paginasi.
     *
     * @param int $limit Jumlah log per halaman.
     * @param int $offset Posisi awal data.
     * @return array Daftar log duplikat.
     */
    public function getDuplicateLogs($limit = 20, $offset = 0) {
        $query = "SELECT * FROM duplicate_logs 
                  ORDER BY attempted_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind('offset', (int)$offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * Menghitung total log file duplikat.
     *
     * @return int Total log duplikat.
     */
    public function countDuplicateLogs() {
        $this->db->query("SELECT COUNT(id) AS total FROM duplicate_logs");
        $result = $this->db->single();
        return $result['total'];
    }

    /**
     * Mengambil log aktivitas bot dengan paginasi.
     *
     * @param int $offset Posisi awal data.
     * @param int $limit Jumlah log per halaman.
     * @return array Daftar log aktivitas bot.
     */
    public function getBotLogs($offset = 0, $limit = 20) {
        $query = "SELECT * FROM bot_logs 
                  ORDER BY timestamp DESC 
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind('offset', (int)$offset, PDO::PARAM_INT);
        $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * Menghitung total log aktivitas bot.
     *
     * @return int Total log aktivitas bot.
     */
    public function countBotLogs() {
        $this->db->query("SELECT COUNT(id) AS total FROM bot_logs");
        $result = $this->db->single();
        return $result['total'];
    }
}
