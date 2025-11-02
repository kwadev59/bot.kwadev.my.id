<?php
// Format bytes to human readable format
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

// Sanitize output for HTML display
if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatFileDate')) {
    /**
     * Format tanggal dari database (mis. 20240930) menjadi format yang lebih ramah.
     * Mengembalikan '-' jika tidak ada tanggal.
     */
    function formatFileDate($value) {
        if ($value === null) {
            return '-';
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return '-';
        }

        $patterns = [
            'Ymd',
            'Y-m-d',
            'Y-m-d H:i:s',
            'd-m-Y',
            'd-m-Y H:i:s',
            'd/m/Y',
            'd/m/Y H:i:s',
            'Y/m/d',
            'Y/m/d H:i:s'
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
