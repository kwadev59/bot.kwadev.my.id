<?php
/**
 * Mengubah ukuran byte menjadi format yang mudah dibaca manusia.
 *
 * @param int $bytes Ukuran dalam byte.
 * @param int $decimals Jumlah angka di belakang koma.
 * @return string Ukuran file dalam format yang mudah dibaca.
 */
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $decimals = 2) {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $dm = $decimals < 0 ? 0 : $decimals;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), $decimals) . ' ' . $sizes[$i];
    }
}

/**
 * Membersihkan output untuk ditampilkan di HTML.
 *
 * @param string $string String yang akan dibersihkan.
 * @return string String yang aman untuk ditampilkan.
 */
if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Memformat tanggal dari berbagai format menjadi format 'd M Y'.
 *
 * @param mixed $value Nilai tanggal yang akan diformat.
 * @return string Tanggal yang sudah diformat atau '-' jika tidak valid.
 */
if (!function_exists('formatFileDate')) {
    function formatFileDate($value) {
        if ($value === null) {
            return '-';
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return '-';
        }

        $patterns = [
            'Ymd', 'dmY', 'dmYHi', 'dmYHis',
            'Y-m-d', 'Y-m-d H:i:s', 'd-m-Y', 'd-m-Y H:i:s',
            'd/m/Y', 'd/m/Y H:i:s', 'Y/m/d', 'Y/m/d H:i:s'
        ];
        foreach ($patterns as $pattern) {
            $date = DateTime::createFromFormat($pattern, $raw);
            if ($date && $date->format($pattern) === $raw) {
                return $date->format('d M Y');
            }
        }

        return sanitizeOutput($raw);
    }
}
