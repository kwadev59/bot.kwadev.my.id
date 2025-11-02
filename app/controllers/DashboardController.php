<?php
class DashboardController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function index() {
        $submissionModel = $this->model('Submission_model');

        $data['judul'] = 'Dashboard';
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        
        // Ambil status WA Bot
        $data['bot_status'] = $this->getWaBotStatus();
        
        // Data Statistik Utama
        $data['total_files'] = $submissionModel->getTotalFiles();
        $data['total_trb'] = $submissionModel->getCountByType('TRB');
        $data['total_tu'] = $submissionModel->getCountByType('TU');
        $data['total_amandarb'] = $submissionModel->getCountByType('AMANDARB');
        $data['total_tpn'] = $submissionModel->getCountByType('TPN');
        $data['total_tr'] = $submissionModel->getCountByType('TR');
        $data['total_to'] = $submissionModel->getCountByType('TO');
        $data['total_amanta'] = $submissionModel->getCountByType('AMANTA');
        $data['total_amanda_panen'] = $submissionModel->getCountByType('AMANDA PANEN');
        $data['total_tika_plasma'] = $submissionModel->getCountByType('TIKA PLASMA');

        // Data Detail Valid/Invalid untuk TRB
        $data['trb_valid'] = $submissionModel->getCountByTypeAndStatus('TRB', 'valid');
        $data['trb_invalid'] = $submissionModel->getCountByTypeAndStatus('TRB', 'invalid');

        // Data Detail Valid/Invalid untuk TU
        $data['tu_valid'] = $submissionModel->getCountByTypeAndStatus('TU', 'valid');
        $data['tu_invalid'] = $submissionModel->getCountByTypeAndStatus('TU', 'invalid');

        // Data Detail Valid/Invalid untuk TPN
        $data['tpn_valid'] = $submissionModel->getCountByTypeAndStatus('TPN', 'valid');
        $data['tpn_invalid'] = $submissionModel->getCountByTypeAndStatus('TPN', 'invalid');

        // Data Detail Valid/Invalid untuk TR
        $data['tr_valid'] = $submissionModel->getCountByTypeAndStatus('TR', 'valid');
        $data['tr_invalid'] = $submissionModel->getCountByTypeAndStatus('TR', 'invalid');

        // Data Detail Valid/Invalid untuk TO
        $data['to_valid'] = $submissionModel->getCountByTypeAndStatus('TO', 'valid');
        $data['to_invalid'] = $submissionModel->getCountByTypeAndStatus('TO', 'invalid');

        // Data Detail Valid/Invalid untuk AMANTA
        $data['amanta_valid'] = $submissionModel->getCountByTypeAndStatus('AMANTA', 'valid');
        $data['amanta_invalid'] = $submissionModel->getCountByTypeAndStatus('AMANTA', 'invalid');

        // Data Detail Valid/Invalid untuk AMANDA PANEN
        $data['amanda_panen_valid'] = $submissionModel->getCountByTypeAndStatus('AMANDA PANEN', 'valid');
        $data['amanda_panen_invalid'] = $submissionModel->getCountByTypeAndStatus('AMANDA PANEN', 'invalid');

        // Data Detail Valid/Invalid untuk TIKA PLASMA
        $data['tika_plasma_valid'] = $submissionModel->getCountByTypeAndStatus('TIKA PLASMA', 'valid');
        $data['tika_plasma_invalid'] = $submissionModel->getCountByTypeAndStatus('TIKA PLASMA', 'invalid');

        // Data Laporan Terbaru
        $data['recent_submissions'] = $submissionModel->getRecentSubmissions(10);

        // Monitoring TU per tanggal file
        $selectedFileDate = $this->sanitizeFileDate($_GET['tu_date'] ?? date('Y-m-d')) ?? date('Y-m-d');
        $data['tu_selected_date'] = $selectedFileDate;
        $data['tu_monitoring_items'] = $submissionModel->getTuSubmissionsByFileDate($selectedFileDate);

        // Memuat view
        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('dashboard/index', $data);
        $this->view('templates/footer');
    }
    
    private function getWaBotStatus() {
        // Mendapatkan status dari WA Bot API
        $apiUrl = 'http://localhost:' . ($_ENV['WA_BOT_API_PORT'] ?? 3001) . '/api/status';
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 3
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response !== false) {
            $status = json_decode($response, true);
            
            if ($status && isset($status['isOnline'])) {
                $rawTimestamp = $status['timestamp'] ?? null;
                $timestampIso = $rawTimestamp ?: date('c');
                $lastUpdate = $rawTimestamp && strtotime($rawTimestamp)
                    ? date('d M Y, H:i:s', strtotime($rawTimestamp))
                    : date('d M Y, H:i:s');

                return [
                    'status' => $status['isOnline'] ? 'ONLINE' : 'OFFLINE',
                    'timestamp' => $timestampIso,
                    'last_update' => $lastUpdate,
                    'error' => null
                ];
            }
        }
        
        // Jika API tidak merespon atau error
        return [
            'status' => 'OFFLINE',
            'timestamp' => date('c'),
            'last_update' => date('d M Y, H:i:s'),
            'error' => 'Tidak dapat menghubungi WA Bot API'
        ];
    }
    
    public function getBotStatus() {
        $status = $this->getWaBotStatus();
        
        header('Content-Type: application/json');
        echo json_encode($status);
    }

    public function getTuMonitoringByDate() {
        $dateParam = $_GET['date'] ?? null;
        $validatedDate = $this->sanitizeFileDate($dateParam);

        if ($validatedDate === null) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.'
            ]);
            return;
        }

        $submissionModel = $this->model('Submission_model');
        $rows = $submissionModel->getTuSubmissionsByFileDate($validatedDate);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'date' => $validatedDate,
            'data' => $rows
        ]);
    }

    private function sanitizeFileDate($date) {
        if (!$date) {
            return null;
        }

        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'Y/m/d',
            'm/d/Y',
            'm-d-Y'
        ];

        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat('!' . $format, $date);
            if ($dateTime === false) {
                continue;
            }

            $errors = DateTime::getLastErrors();
            $hasIssues = is_array($errors) && (
                ($errors['warning_count'] ?? 0) > 0 ||
                ($errors['error_count'] ?? 0) > 0
            );

            if (!$hasIssues) {
                return $dateTime->format('Y-m-d');
            }
        }

        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}
