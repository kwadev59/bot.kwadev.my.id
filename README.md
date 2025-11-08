# WA Bot Panel - Web Monitoring System

## Deskripsi
Sistem web panel untuk monitoring file laporan yang dikirim melalui WhatsApp ke WA Bot. Sistem ini menyediakan antarmuka admin untuk memantau, mengelola, dan memperbaiki data laporan yang diterima. Aplikasi ini dibangun dengan arsitektur MVC custom dan dirancang untuk digunakan di lingkungan produksi untuk memantau file laporan dari karyawan.

## Fitur Utama

### Dashboard
- Statistik real-time jumlah file TRB, TU, AmandaRB, TO, TPN, TR, dan tipe file lainnya
- Detail valid/invalid untuk setiap tipe file
- Status WA Bot (Online/Offline dengan timestamp terakhir)
- Tabel 10 laporan terbaru dengan informasi lengkap
- Monitoring TU per tanggal file
- Visualisasi data dengan gradient cards elegan

### Laporan
- Halaman terpisah untuk laporan valid dan invalid
- Fitur pencarian, filter, dan sorting
- Pagination untuk manajemen data skala besar
- Download file dengan counter tracking
- Rename file invalid untuk perbaikan otomatis
- Validasi format nama file TRB, TU, AmandaRB, TO, TPN, TR
- Estimasi ketepatan waktu pengiriman file (H+0, H+1, H+2, H+3+)
- Informasi ekstraksi data dari nama file (NPK, Tanggal, Site Code, Afdeling, IMEI)

### Manajemen Kontak
- CRUD operasi untuk kontak WhatsApp
- Template download untuk import data kontak
- Dukungan skema lama (nama_lengkap, nomor_telepon) dan skema baru (site, afd, nama, nomer_wa, kategori)
- Pencarian dan pagination
- Import batch dari file XLSX
- Normalisasi nomor WhatsApp untuk pencocokan

### Monitoring TU
- Filter berdasarkan tanggal file (bukan tanggal kirim)
- Menampilkan file TU berdasarkan tanggal yang dipilih
- Integrasi dengan sistem laporan untuk mencari data berdasarkan tanggal file

### Log System
- Log file duplikat
- Log aktivitas bot dengan level error/warning/info
- Pagination untuk log besar
- Tampilan log yang informatif dan mudah dipahami

### Manajemen User (Admin Only)
- CRUD operasi untuk akun user
- Role-based access (admin/viewer)
- Proteksi operasi sensitif
- Enkripsi password

### Sistem Karyawan & Gadget
- Manajemen data karyawan BIM dan PPS
- Manajemen gadget (ponsel) yang digunakan oleh karyawan
- Manajemen status gadget (aktif/non-aktif)

### Status Bot
- Monitoring status koneksi WA Bot
- API endpoint untuk mengambil status secara real-time
- Indikator visual status bot

## Teknologi yang Digunakan
- **Backend**: PHP Native (7.4+)
- **Arsitektur**: MVC Custom
- **Database**: MySQL
- **Frontend**: Bootstrap 5, JavaScript (ES6+)
- **Ikon**: Bootstrap Icons
- **Notifikasi**: SweetAlert2
- **Pemilihan Tanggal**: Flatpickr
- **Dependency Manager**: Composer
- **Environment Configuration**: vlucas/phpdotenv
- **XLSX Processing**: SimpleXLSX library

## Instalasi

### Prasyarat
- PHP 7.4+
- MySQL
- Composer
- Web Server (Apache/Nginx)
- WA Bot (terpisah, berjalan di port 3001 atau sesuai konfigurasi)
- Node.js (jika dibutuhkan untuk WA Bot)

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
     `file_type` enum('TRB','TU','AmandaRB','TPN','TR','TO','AMANTA','AMANDA PANEN','TIKA PLASMA') NOT NULL,
     `file_size` bigint(20) NOT NULL,
     `file_path` varchar(500) NOT NULL,
     `site_code` varchar(4) DEFAULT NULL,
     `afdeling` varchar(2) DEFAULT NULL,
     `npk` varchar(20) DEFAULT NULL,
     `imei` varchar(15) DEFAULT NULL,
     `unit_code` varchar(9) DEFAULT NULL,
     `npk_driver` varchar(7) DEFAULT NULL,
     `npk_mandor` varchar(7) DEFAULT NULL,
     `tanggal` varchar(20) DEFAULT NULL,
     `submission_date` datetime DEFAULT current_timestamp(),
     `sender_number` varchar(50) NOT NULL,
     `group_id` varchar(100) NOT NULL,
     `status` enum('valid','invalid') NOT NULL,
     `validation_notes` text DEFAULT NULL,
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `kontak_whatsapp` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `site` varchar(10) DEFAULT NULL,
     `afd` varchar(10) DEFAULT NULL,
     `nama` varchar(150) NOT NULL,
     `nomer_wa` varchar(25) NOT NULL,
     `kategori` varchar(50) DEFAULT NULL,
     `terakhir_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
     PRIMARY KEY (`id`),
     UNIQUE KEY `nomer_wa` (`nomer_wa`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   -- Versi lama (kompatibilitas)
   CREATE TABLE `kontak_whatsapp` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `nama_lengkap` varchar(150) NOT NULL,
     `nomor_telepon` varchar(25) NOT NULL,
     `whatsapp_lid` varchar(50) DEFAULT NULL,
     `jabatan` varchar(50) DEFAULT NULL,
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

   -- Tabel untuk sistem karyawan dan gadget
   CREATE TABLE `karyawan_bim` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `npk` varchar(20) NOT NULL,
     `nama` varchar(100) NOT NULL,
     `jabatan` varchar(100) DEFAULT NULL,
     `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
     `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
     PRIMARY KEY (`id`),
     UNIQUE KEY `npk` (`npk`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `karyawan_pps` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `npk` varchar(20) NOT NULL,
     `nama` varchar(100) NOT NULL,
     `jabatan` varchar(100) DEFAULT NULL,
     `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
     `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
     PRIMARY KEY (`id`),
     UNIQUE KEY `npk` (`npk`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `gadgets` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `imei` varchar(15) NOT NULL,
     `npk` varchar(20) DEFAULT NULL,
     `status` enum('aktif','non_aktif') DEFAULT 'aktif',
     `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
     `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
     PRIMARY KEY (`id`),
     UNIQUE KEY `imei` (`imei`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

   CREATE TABLE `gadget_status` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `imei` varchar(15) NOT NULL,
     `status` enum('good','warning','critical') NOT NULL,
     `keterangan` text DEFAULT NULL,
     `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp(),
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
   APP_TIMEZONE=Asia/Jakarta
   WA_BOT_API_PORT=3001
   ```

5. **Jalankan aplikasi**
   - Akses melalui web server di `http://localhost/wa-bot-panel/public`
   - Pastikan WA Bot sudah berjalan di port yang sesuai dengan konfigurasi

## Konfigurasi Awal
1. Login dengan akun admin default (buat akun admin pertama melalui database jika belum ada)
2. Tambahkan kontak WhatsApp untuk memetakan ID ke nama
3. Atur WA Bot untuk menyimpan file ke direktori yang terkonfigurasi
4. Pastikan endpoint WA Bot API dapat diakses untuk status monitoring

## Database Default User
Untuk membuat user admin pertama, Anda bisa menambahkan secara langsung ke tabel users:
```sql
INSERT INTO users (username, password, nama_lengkap, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
```
Password default adalah "password".

## Format Nama File yang Didukung

### TRB - Tracking Rawat Belanja
Format: `TRB-YYYYMMDD-NO-SSSS-AA-IMEI.ext`
Contoh: `TRB-20240930-001-BIM1-AA-123456789012345.csv`

### TU - Transfer Unit
Format: `TU-UNITCODE-NPKDRIVER-NPKMANDOR-YYYYMMDD-SSSS-AA-IMEI.ext`
Contoh: `TU-BIM12345-1234567-7654321-20240930-BIM1-AA-123456789012345.csv`

### AmandaRB - Amanda Report
Format: `AMANDARB_SSSS_YYYYMMDD-UNIQUEID.ext`
Contoh: `AMANDARB_BIM1_20240930-abc123.csv`

### TO - Transfer Order
Format: `TO-NPK-Tanggal-SiteCode-IMEI.ext`
Contoh: `TO-1234567-20251012-BIM1-123456789012345.csv`

### TPN - Tracking Panen
Format: `TPN-NPK-Tanggal-SiteCode-Afdeling-IMEI.ext`
Contoh: `TPN-1234567-20251012-BIM1-AA-123456789012345.csv`

### TR - Tracking Rawat
Format: `TR-NPK-Tanggal-SiteCode-Afdeling-IMEI.ext`
Contoh: `TR-1234567-20251012-BIM1-AA-123456789012345.csv`

## Struktur Direktori
```
wa-bot-panel/
├── app/
│   ├── config/          # File konfigurasi aplikasi
│   ├── controllers/     # File controller
│   ├── core/            # File inti framework (App, Controller, Database)
│   ├── helpers/         # File helper (formatBytes, sanitizeOutput, dll)
│   ├── models/          # File model (Submission, Kontak, User, dll)
│   └── views/           # File tampilan (auth, dashboard, laporan, dll)
│       ├── auth/        # Tampilan login
│       ├── dashboard/   # Tampilan dashboard
│       ├── laporan/     # Tampilan laporan
│       ├── log/         # Tampilan log
│       ├── templates/   # Template layout (header, navbar, footer)
│       └── user/        # Tampilan manajemen user
├── public/              # Entry point aplikasi
│   ├── templates/       # Template halaman publik
│   ├── .htaccess        # Konfigurasi Apache
│   ├── index.php        # File utama aplikasi
│   └── style_v2.css     # File CSS kustom
├── vendor/              # Dependencies dari Composer
├── .env                 # File konfigurasi lingkungan
├── .env.example         # Contoh file .env
├── .gitignore           # File yang diabaikan oleh Git
├── composer.json        # Konfigurasi dependency
├── composer.lock        # Versi dependency yang terkunci
└── README.md            # Dokumentasi proyek
```

## Kustomisasi & Pengembangan

### Menambahkan Tipe File Baru
1. Tambahkan tipe file baru ke ENUM `file_type` di tabel `file_submissions`
2. Tambahkan regex validasi di `LaporanController.php`
3. Tambahkan logika ekstraksi data dari nama file jika diperlukan
4. Tambahkan tampilan dan statistik di dashboard

### Integrasi dengan WA Bot
Aplikasi ini membutuhkan WA Bot yang berjalan di server terpisah. Pastikan:
- WA Bot API dapat diakses melalui port konfigurasi
- File laporan disimpan di direktori yang sesuai
- Format nama file sesuai dengan yang ditentukan

### Security Measures
- Input sanitization dan validation
- SQL injection protection melalui prepared statements
- Password hashing dengan bcrypt
- Session management
- Path traversal prevention

## API Endpoints

### Status Bot
- `GET /DashboardController/getBotStatus` - Mendapatkan status WA Bot

### Monitoring TU
- `GET /DashboardController/getTuMonitoringByDate?date={YYYY-MM-DD}` - Mendapatkan daftar file TU berdasarkan tanggal

## Troubleshooting

### Error Database Connection
- Pastikan konfigurasi `.env` sesuai
- Periksa apakah MySQL server berjalan
- Pastikan database `wa_tracking_bot` sudah dibuat

### Tidak dapat mengakses file
- Pastikan konfigurasi `BOT_BASE_PATH` benar
- Periksa apakah file benar-benar ada di server
- Pastikan hak akses file sesuai

### Tidak dapat menghubungi WA Bot
- Pastikan WA Bot sedang berjalan
- Periksa port konfigurasi WA Bot
- Pastikan firewall tidak memblokir koneksi

## Lisensi
Proyek ini dibuat untuk kebutuhan internal monitoring dan tidak memiliki lisensi khusus.

---
Dibangun dengan cinta oleh [kwadev.my.id](https://kwadev.my.id/)
