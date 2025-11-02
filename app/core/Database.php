<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $db_name = DB_NAME;

    private $dbh; // Database Handler
    private $stmt; // Statement

    public function __construct() {
        // Data Source Name
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';

        $option = [
            PDO::ATTR_PERSISTENT => true, // Menjaga koneksi agar efisien
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Mode error untuk menampilkan exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Default fetch mode
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $option);
        } catch (PDOException $e) {
            die('Koneksi database gagal: ' . $e->getMessage());
        }
    }

    // Menyiapkan query
    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    // Binding data untuk keamanan (mencegah SQL Injection)
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Eksekusi statement yang sudah disiapkan
    public function execute() {
        $this->stmt->execute();
    }

    // Mengambil semua hasil sebagai array
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mengambil satu hasil
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Menghitung baris yang terpengaruh
    public function rowCount(){
        return $this->stmt->rowCount();
    }

    public function beginTransaction(): void {
        if (!$this->dbh->inTransaction()) {
            $this->dbh->beginTransaction();
        }
    }

    public function commit(): void {
        if ($this->dbh->inTransaction()) {
            $this->dbh->commit();
        }
    }

    public function rollBack(): void {
        if ($this->dbh->inTransaction()) {
            $this->dbh->rollBack();
        }
    }

    public function lastInsertId(): string {
        return $this->dbh->lastInsertId();
    }
}
