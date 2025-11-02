# Bug Fix Summary

## Dashboard WA Bot Status
- **Issue**: Ketika status bot diperbarui melalui JavaScript, ikon Bootstrap menghilang karena kelas dasar `bi` terhapus. Hal ini menyebabkan ikon tidak ditampilkan setelah polling status berikutnya.
- **Lokasi**: `app/views/dashboard/index.php:558`
- **Perbaikan**: Menjaga kelas dasar `bi` ketika melakukan penggantian class ikon (`statusIcon.className = \`bi ${newIcon} fs-4 me-3\`;`), sehingga ikon selalu muncul untuk semua status.

## Pengambilan Status WA Bot
- **Issue**: Response WA Bot API tidak selalu menyertakan properti `timestamp`. Akses langsung terhadap indeks tersebut memicu notice PHP dan `last_update` menjadi tidak konsisten.
- **Lokasi**: `app/controllers/DashboardController.php:90`
- **Perbaikan**: Menambahkan penanganan aman untuk `timestamp` dengan fallback ke waktu saat ini dan hanya memformat ketika nilai valid. Kini panel tidak lagi mengeluarkan notice dan informasi waktu tampil konsisten.

## Validasi
- `php -l app/controllers/DashboardController.php`
- `php -l app/views/dashboard/index.php`
