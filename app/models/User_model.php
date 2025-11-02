<?php
class User_model {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = new Database;
    }

    public function getUserByUsername($username) {
        $this->db->query("SELECT * FROM {$this->table} WHERE username = :username");
        $this->db->bind('username', $username);
        return $this->db->single();
    }

    public function getUserById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    // Method untuk manajemen user (CRUD) - untuk fitur manajemen user nanti
    public function getAllUsers() {
        $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    public function tambahUser($data) {
        $query = "INSERT INTO {$this->table} (username, password, nama_lengkap, role) VALUES (:username, :password, :nama_lengkap, :role)";
        
        $this->db->query($query);
        $this->db->bind('username', $data['username']);
        $this->db->bind('password', $data['password']);
        $this->db->bind('nama_lengkap', $data['nama_lengkap']);
        $this->db->bind('role', $data['role']);
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    public function updateUser($id, $data) {
        $query = "UPDATE {$this->table} SET 
                    username = :username,
                    nama_lengkap = :nama_lengkap,
                    role = :role";
        
        // Tambahkan password ke query hanya jika disediakan
        if (!empty($data['password'])) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        $this->db->query($query);
        $this->db->bind('username', $data['username']);
        $this->db->bind('nama_lengkap', $data['nama_lengkap']);
        $this->db->bind('role', $data['role']);
        $this->db->bind('id', $id, PDO::PARAM_INT);
        
        if (!empty($data['password'])) {
            $this->db->bind('password', $data['password']);
        }
        
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }

    public function hapusUser($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }
}