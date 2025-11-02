<?php
class LogController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    // Method untuk log duplikat
    public function duplikat() {
        $logModel = $this->model('Log_model');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $data['judul'] = 'Log File Duplikat';
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        $data['logs'] = $logModel->getDuplicateLogs($limit, $offset);
        $data['total_logs'] = $logModel->countDuplicateLogs();
        $data['total_halaman'] = ceil($data['total_logs'] / $limit);
        $data['halaman_aktif'] = $page;

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('log/duplikat', $data);
        $this->view('templates/footer');
    }

    // Method untuk log aktivitas
    public function aktivitas() {
        $logModel = $this->model('Log_model');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $data['judul'] = 'Log Aktivitas Bot';
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        $data['logs'] = $logModel->getBotLogs($offset, $limit);
        $data['total_logs'] = $logModel->countBotLogs();
        $data['total_halaman'] = ceil($data['total_logs'] / $limit);
        $data['halaman_aktif'] = $page;

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('log/aktivitas', $data);
        $this->view('templates/footer');
    }
}