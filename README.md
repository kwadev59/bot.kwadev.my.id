# WA Bot Panel - Web Monitoring System

## Deskripsi
Sistem web panel untuk monitoring file laporan yang dikirim melalui WhatsApp ke WA Bot. Sistem ini menyediakan antarmuka admin untuk memantau, mengelola, dan memperbaiki data laporan yang diterima.

## Fitur Utama

### Dashboard
- Statistik real-time jumlah file TRB, TU, dan AmandaRB
- Detail valid/invalid untuk setiap tipe file
- Tabel 10 laporan terbaruss

### Laporan
- Halaman terpisah untuk laporan valid dan invalid
- Fitur pencarian, filter, dan sorting
- Pagination untuk manajemen data skala besar
- Download file dengan counter tracking
- Rename file invalid untuk perbaikan otomatis

### Manajemen Kontak
- CRUD operasi untuk kontak WhatsApp
- Template download untuk import data kontak
- Pencarian dan pagination

### Log System
- Log file duplikat
- Log aktivitas bot dengan level error/warning/info
- Pagination untuk log besar

### Manajemen User (Admin Only)
- CRUD operasi untuk akun user
- Role-based access (admin/viewer)
- Proteksi operasi sensitif

## Teknologi yang Digunakan
- **Backend**: PHP Native
- **Arsitektur**: MVC Custom
- **Database**: MySQL
- **Frontend**: Bootstrap 5
- **Ikon**: Bootstrap Icons
- **Notifikasi**: SweetAlert2
- **Dependency Manager**: Composer

## Instalasi

### Prasyarat
- PHP 7.4+
- MySQL
- Composer
- Web Server (Apache/Nginx)

### Langkah-langkah Instalasi

1. **Clone atau buat proyek**
   ```
   mkdir wa-bot-panel
   cd wa-bot-panel
   ```

2. **Install dependencies**
   ```
   composer install
   ```

3. **Konfigurasi database**
   Buat database MySQL dengan nama `wa_tracking_bot` dan import skema:

   ```sql
   CREATE TABLE `file_submissions` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `original_file_name` varchar(255) DEFAULT NULL,
     `file_name` varchar(255) NOT NULL,
     `file_type` enum('TRB','TU','AmandaRB') NOT NULL,
     `file_size` bigint(20) NOT NULL,
     `file_path` varchar(500) NOT NULL,
     `site_code` varchar(4) DEFAULT NULL,
     `afdeling` varchar(2) DEFAULT NULL,
     `npk` varchar(20) DEFAULT NULL,
     `imei` varchar(15) DEFAULT NULL,
     `unit_code` varchar(9) DEFAULT NULL,
     `npk_driver` varchar(7) DEFAULT NULL,
     `npk_mandor` varchar(7) DEFAULT NULL,
     `submission_date` datetime DEFAULT current_timestamp(),
     `sender_number` varchar(50) NOT NULL,
     `group_id` varchar(100) NOT NULL,
     `status` enum('valid','invalid') NOT NULL,
     `validation_notes` text DEFAULT NULL,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `kontak_whatsapp` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `nama_lengkap` varchar(150) NOT NULL,
     `nomor_telepon` varchar(25) NOT NULL,
     `whatsapp_lid` varchar(50) DEFAULT NULL,
     `terakhir_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
     PRIMARY KEY (`id`),
     UNIQUE KEY `nomor_telepon` (`nomor_telepon`),
     UNIQUE KEY `whatsapp_lid` (`whatsapp_lid`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `users` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `username` varchar(50) NOT NULL,
     `password` varchar(255) NOT NULL,
     `nama_lengkap` varchar(100) DEFAULT NULL,
     `role` enum('admin','viewer') DEFAULT 'viewer',
     `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
     PRIMARY KEY (`id`),
     UNIQUE KEY `username` (`username`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `bot_logs` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `log_level` enum('INFO','WARN','ERROR') NOT NULL,
     `activity` varchar(100) NOT NULL,
     `details` text DEFAULT NULL,
     `user_number` varchar(30) DEFAULT NULL,
     `group_id` varchar(100) DEFAULT NULL,
     `file_name` varchar(255) DEFAULT NULL,
     `timestamp` datetime DEFAULT current_timestamp(),
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `duplicate_logs` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `file_name` varchar(255) NOT NULL,
     `sender_number` varchar(50) NOT NULL,
     `original_submission_date` datetime NOT NULL,
     `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
   ```

4. **Konfigurasi .env**
   Salin contoh .env dan sesuaikan dengan lingkungan Anda:
   ```
   cp .env.example .env
   ```
   
   Edit .env sesuai kebutuhan:
   ```env
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=your_password
   DB_NAME=wa_tracking_bot
   BASE_URL=http://localhost/wa-bot-panel/public
   BOT_BASE_PATH=/path/to/wa-bot/storage-wa-bot
   APP_ENV=development
   ```

5. **Jalankan aplikasi**
   - Akses melalui web server di `http://localhost/wa-bot-panel/public`

## Konfigurasi Awal
1. Login dengan akun admin default (buat akun admin pertama melalui database jika belum ada)
2. Tambahkan kontak WhatsApp untuk memetakan ID ke nama
3. Atur WA Bot untuk menyimpan file ke direktori yang terkonfigurasi

## Database Default User
Untuk membuat user admin pertama, Anda bisa menambahkan secara langsung ke tabel users:
```sql
INSERT INTO users (username, password, nama_lengkap, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
```
Password default adalah "password".

## Struktur Direktori
```
wa-bot-panel/
├── app/
│   ├── controllers/     # File controller
│   ├── models/          # File model
│   ├── views/           # File tampilan
│   ├── core/            # File inti framework
│   ├── config/          # File konfigurasi
│   └── helpers/         # File helper
├── public/              # Entry point aplikasi
├── vendor/              # Dependencies dari Composer
├── .env                 # File konfigurasi
└── composer.json        # Konfigurasi dependency
```

## Lisensi
Proyek ini dibuat untuk kebutuhan internal monitoring dan tidak memiliki lisensi khusus.