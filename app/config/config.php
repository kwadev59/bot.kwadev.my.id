<?php
// Memuat autoloader dari Composer (yang akan memuat library phpdotenv)
require_once __DIR__ . '/../../vendor/autoload.php';

// Inisialisasi library phpdotenv untuk membaca file .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Mendefinisikan konstanta untuk konfigurasi aplikasi
// BASE_URL digunakan untuk semua link aset (CSS, JS) dan redirect
define('BASE_URL', $_ENV['BASE_URL']);

// Mendefinisikan konstanta untuk koneksi database
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);

// Tambahkan baris ini
define('BOT_BASE_PATH', $_ENV['BOT_BASE_PATH']);

// Tambahkan baris ini. Default-nya 'production' untuk keamanan.
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Timezone aplikasi (default ke Asia/Jakarta agar konsisten dengan WIB)
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');
date_default_timezone_set(APP_TIMEZONE);
