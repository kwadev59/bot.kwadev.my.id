<?php
/**
 * Class Database
 *
 * Wrapper untuk PDO yang menyediakan metode yang lebih mudah untuk berinteraksi dengan database.
 * Mengelola koneksi, preparasi statement, binding, dan eksekusi query.
 */
class Database {
    /** @var string Host database. */
    private $host = DB_HOST;
    /** @var string User database. */
    private $user = DB_USER;
    /** @var string Password database. */
    private $pass = DB_PASS;
    /** @var string Nama database. */
    private $db_name = DB_NAME;

    /** @var PDO|null Handler koneksi database. */
    private $dbh;
    /** @var PDOStatement|null Statement yang disiapkan. */
    private $stmt;

    /**
     * Database constructor.
     *
     * Membuat koneksi ke database menggunakan PDO.
     */
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

    /**
     * Menyiapkan statement query.
     *
     * @param string $query String query SQL.
     */
    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    /**
     * Melakukan binding data ke statement yang telah disiapkan.
     * Mencegah SQL Injection dengan menggunakan prepared statements.
     *
     * @param string $param Placeholder parameter (misal: :nama).
     * @param mixed $value Nilai yang akan di-bind.
     * @param int|null $type Tipe data PDO (misal: PDO::PARAM_STR). Jika null, tipe akan ditentukan secara otomatis.
     */
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

    /**
     * Mengeksekusi statement yang sudah disiapkan.
     */
    public function execute() {
        $this->stmt->execute();
    }

    /**
     * Mengambil semua hasil query sebagai array asosiatif.
     *
     * @return array Array dari hasil query.
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil satu baris hasil query sebagai array asosiatif.
     *
     * @return array|false Satu baris hasil, atau false jika tidak ada.
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Menghitung jumlah baris yang terpengaruh oleh query terakhir.
     *
     * @return int Jumlah baris yang terpengaruh.
     */
    public function rowCount(){
        return $this->stmt->rowCount();
    }

    /**
     * Memulai transaksi database.
     */
    public function beginTransaction(): void {
        if (!$this->dbh->inTransaction()) {
            $this->dbh->beginTransaction();
        }
    }

    /**
     * Melakukan commit terhadap transaksi yang sedang berjalan.
     */
    public function commit(): void {
        if ($this->dbh->inTransaction()) {
            $this->dbh->commit();
        }
    }

    /**
     * Melakukan rollback terhadap transaksi yang sedang berjalan.
     */
    public function rollBack(): void {
        if ($this->dbh->inTransaction()) {
            $this->dbh->rollBack();
        }
    }

    /**
     * Mengambil ID dari baris terakhir yang dimasukkan.
     *
     * @return string ID dari baris terakhir.
     */
    public function lastInsertId(): string {
        return $this->dbh->lastInsertId();
    }
}
