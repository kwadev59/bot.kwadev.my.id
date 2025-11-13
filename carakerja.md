# Alur Kerja Aplikasi

Aplikasi ini adalah panel web yang berfungsi sebagai antarmuka untuk memantau dan mengelola file laporan yang dikirim melalui WhatsApp Bot. Alur kerjanya dapat diuraikan sebagai berikut:

## 1. Arsitektur MVC (Model-View-Controller)

Aplikasi ini dibangun dengan pola arsitektur MVC:

- **Model**: Terletak di direktori `app/models`, bertanggung jawab untuk berinteraksi dengan database. `User_model.php` mengelola data pengguna, sementara `Submission_model.php` menangani semua logika terkait file laporan.
- **View**: Terletak di direktori `app/views`, bertanggung jawab untuk presentasi data. `auth/login.php` menampilkan halaman login, `dashboard/index.php` menampilkan dasbor utama, dan `templates/` berisi bagian-bagian yang dapat digunakan kembali seperti header, footer, dan navbar.
- **Controller**: Terletak di direktori `app/controllers`, bertindak sebagai perantara antara Model dan View. `AuthController.php` menangani proses otentikasi, sementara `DashboardController.php` mempersiapkan data untuk ditampilkan di dasbor.

## 2. Proses Inisialisasi

Alur kerja aplikasi dimulai dari `app/init.php`:

1.  **Konfigurasi**: Memuat file konfigurasi dari `app/config/config.php`.
2.  **Helper**: Memuat fungsi-fungsi bantuan dari `app/helpers`.
3.  **Autoloading**: Mendaftarkan autoloader untuk memuat kelas-kelas dari direktori `app/core` secara otomatis.

## 3. Routing

Routing ditangani oleh kelas `App` di `app/core/App.php`:

1.  URL di-parse untuk menentukan controller, method, dan parameter.
2.  Secara default, controller adalah `AuthController`. Jika pengguna sudah login, controller default adalah `DashboardController`.
3.  Controller yang sesuai di-instansiasi, dan method yang relevan dipanggil dengan parameter yang diberikan.

## 4. Alur Kerja Pengguna

1.  **Otentikasi**:
    -   Pengguna mengunjungi halaman utama, yang menampilkan halaman login (`auth/login.php`).
    -   `AuthController` menangani pengiriman form login.
    -   Data pengguna diverifikasi terhadap database menggunakan `User_model`.
    -   Jika berhasil, sesi dibuat, dan pengguna diarahkan ke dasbor.

2.  **Dasbor**:
    -   `DashboardController` mengambil data dari `Submission_model` untuk menampilkan statistik file laporan.
    -   Dasbor juga menampilkan status "WA Bot" dengan melakukan panggilan API ke layanan bot.
    -   Tampilan dasbor (`dashboard/index.php`) dirender dengan data yang diambil.

3.  **Fitur Lainnya**:
    -   Aplikasi ini mencakup fitur-fitur untuk melihat laporan yang valid dan tidak valid, log, dan manajemen pengguna (khusus admin). Masing-masing fitur ini memiliki controller dan view-nya sendiri.

## 5. Interaksi Database

-   Kelas `Database` di `app/core/Database.php` adalah pembungkus PDO yang menyediakan cara yang nyaman dan aman untuk berinteraksi dengan database.
-   Model menggunakan kelas `Database` ini untuk menjalankan query dan mengambil data.

Secara keseluruhan, aplikasi ini mengikuti alur kerja MVC standar, dengan titik masuk tunggal (`index.php` di direktori root publik), routing berbasis URL, dan pemisahan yang jelas antara logika bisnis, interaksi database, dan presentasi.
