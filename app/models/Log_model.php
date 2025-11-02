<?php
class Log_model {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function getDuplicateLogs($limit = 20, $offset = 0) {
        $query = "SELECT * FROM duplicate_logs 
                  ORDER BY attempted_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind('offset', (int)$offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    public function countDuplicateLogs() {
        $this->db->query("SELECT COUNT(id) AS total FROM duplicate_logs");
        $result = $this->db->single();
        return $result['total'];
    }

    public function getBotLogs($offset = 0, $limit = 20) {
        $query = "SELECT * FROM bot_logs 
                  ORDER BY timestamp DESC 
                  LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        $this->db->bind('offset', (int)$offset, PDO::PARAM_INT);
        $this->db->bind('limit', (int)$limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    public function countBotLogs() {
        $this->db->query("SELECT COUNT(id) AS total FROM bot_logs");
        $result = $this->db->single();
        return $result['total'];
    }
}