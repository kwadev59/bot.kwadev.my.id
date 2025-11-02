<?php
// Memulai session dengan memastikan status aktif agar kompatibel dengan PHP terbaru
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Memuat file inisialisasi utama (yang akan memuat config.php)
require_once '../app/init.php';

// --- LOGIKA PENGATURAN ERROR BERDASARKAN LINGKUNGAN ---
if (APP_ENV == 'development') {
    // Jika kita di bengkel, tunjukkan semua masalah.
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Jika kita di showroom, sembunyikan semua masalah teknis.
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);

    // Opsional: Tampilkan halaman error yang cantik jika terjadi masalah fatal
    // set_exception_handler(function($exception) {
    //     // Anda bisa mencatatan error ke file log di sini
    //     // error_log($exception->getMessage());
    //     require_once '../app/views/error/500.php';
    // });
}
// --- AKHIR LOGIKA PENGATURAN ERROR ---

// Menjalankan class App (router)
$app = new App();
