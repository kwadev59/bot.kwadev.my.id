<?php
/**
 * Class Controller
 *
 * Kelas dasar untuk semua controller dalam aplikasi.
 * Menyediakan metode untuk memuat model dan view.
 */
class Controller {
    
    /**
     * Memuat dan menginisialisasi sebuah model.
     *
     * @param string $model Nama file model (tanpa ekstensi .php).
     * @return object Instance dari model yang diminta.
     */
    public function model($model) {
        require_once '../app/models/' . $model . '.php';
        return new $model();
    }
    
    /**
     * Memuat dan menampilkan sebuah view.
     *
     * @param string $view Nama file view (tanpa ekstensi .php).
     * @param array $data Data yang akan diekstrak dan tersedia di dalam view.
     */
    public function view($view, $data = []) {
        $viewFile = '../app/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View tidak ditemukan: $view");
        }
    }
}