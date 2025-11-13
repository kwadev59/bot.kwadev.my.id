<?php
/**
 * Class User_model
 *
 * Model untuk mengelola data pengguna (users).
 * Menyediakan metode untuk operasi CRUD pada tabel users.
 */
class User_model {
    /** @var Database Instance dari kelas Database. */
    private $db;
    /** @var string Nama tabel pengguna di database. */
    private $table = 'users';

    /**
     * User_model constructor.
     *
     * Menginisialisasi koneksi database.
     */
    public function __construct() {
        $this->db = new Database;
    }

    /**
     * Mengambil data pengguna berdasarkan username.
     *
     * @param string $username Username pengguna.
     * @return mixed Data pengguna atau false jika tidak ditemukan.
     */
    public function getUserByUsername($username) {
        $this->db->query("SELECT * FROM {$this->table} WHERE username = :username");
        $this->db->bind('username', $username);
        return $this->db->single();
    }

    /**
     * Mengambil data pengguna berdasarkan ID.
     *
     * @param int $id ID pengguna.
     * @return mixed Data pengguna atau false jika tidak ditemukan.
     */
    public function getUserById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }

    /**
     * Mengambil semua data pengguna.
     *
     * @return array Daftar semua pengguna.
     */
    public function getAllUsers() {
        $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    /**
     * Menambahkan pengguna baru ke database.
     *
     * @param array $data Data pengguna baru.
     * @return bool True jika berhasil, false jika gagal.
     */
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

    /**
     * Memperbarui data pengguna di database.
     *
     * @param int $id ID pengguna yang akan diperbarui.
     * @param array $data Data baru untuk pengguna.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function updateUser($id, $data) {
        $query = "UPDATE {$this->table} SET 
                    username = :username,
                    nama_lengkap = :nama_lengkap,
                    role = :role";
        
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

    /**
     * Menghapus pengguna dari database.
     *
     * @param int $id ID pengguna yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function hapusUser($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind('id', $id, PDO::PARAM_INT);
        $this->db->execute();
        
        return $this->db->rowCount() > 0;
    }
}
