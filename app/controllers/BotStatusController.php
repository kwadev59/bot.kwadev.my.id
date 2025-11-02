<?php
class BotStatusController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function index() {
        $data['judul'] = 'Status Bot WhatsApp';
        $data['nama_user'] = $_SESSION['nama_lengkap'];

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('botstatus/index', $data);
        $this->view('templates/footer');
    }

    public function getStatus() {
        $result = $this->callBotApi('/api/status', 'GET', null, 5);

        header('Content-Type: application/json');

        if ($result['success'] && $this->isSuccessfulHttpCode($result['status'])) {
            echo $result['body'];
            return;
        }

        http_response_code(503);
        echo json_encode([
            'connection' => 'offline',
            'isOnline' => false,
            'isConnecting' => false,
            'isLoggedOut' => false,
            'qr' => null,
            'timestamp' => date('c'),
            'error' => $result['error'] ?? 'Tidak dapat menghubungi WA Bot API'
        ]);
    }

    public function getQr() {
        $result = $this->callBotApi('/api/qr', 'GET', null, 5);

        header('Content-Type: application/json');

        if ($result['success'] && $this->isSuccessfulHttpCode($result['status'])) {
            echo $result['body'];
            return;
        }

        http_response_code(503);
        echo json_encode([
            'qr' => null,
            'message' => $result['error'] ?? 'Tidak dapat menghubungi WA Bot API'
        ]);
    }

    public function restart() {
        if (!$this->isAdmin()) {
            $this->respondJson([
                'success' => false,
                'message' => 'Akses ditolak: hanya admin yang dapat melakukan restart'
            ], 403);
            return;
        }

        $result = $this->callBotApi('/api/restart', 'POST', null, 15);

        if ($result['success'] && $this->isSuccessfulHttpCode($result['status'])) {
            header('Content-Type: application/json');
            echo $result['body'];
            return;
        }

        $this->respondJson([
            'success' => false,
            'message' => $result['error'] ?? 'Tidak dapat menghubungi WA Bot API untuk restart'
        ], 503);
    }

    public function logout() {
        if (!$this->isAdmin()) {
            $this->respondJson([
                'success' => false,
                'message' => 'Akses ditolak: hanya admin yang dapat logout bot'
            ], 403);
            return;
        }

        $result = $this->callBotApi('/api/logout', 'POST', null, 10);

        if ($result['success'] && $this->isSuccessfulHttpCode($result['status'])) {
            header('Content-Type: application/json');
            echo $result['body'];
            return;
        }

        $this->respondJson([
            'success' => false,
            'message' => $result['error'] ?? 'Tidak dapat menghubungi WA Bot API untuk logout'
        ], 503);
    }

    public function checkNumber() {
        if (!$this->isAdmin()) {
            $this->respondJson([
                'error' => 'Akses ditolak: hanya admin yang dapat cek nomor'
            ], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $number = $input['number'] ?? '';

        if (empty($number)) {
            $this->respondJson(['error' => 'Nomor harus diisi'], 400);
            return;
        }

        $payload = ['number' => $number];

        $result = $this->callBotApi('/api/check-number', 'POST', $payload, 10);

        header('Content-Type: application/json');

        if ($result['success'] && $this->isSuccessfulHttpCode($result['status'])) {
            echo $result['body'];
            return;
        }

        http_response_code(503);
        echo json_encode([
            'error' => $result['error'] ?? 'Tidak dapat menghubungi WA Bot API untuk pengecekan nomor'
        ]);
    }

    private function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    private function callBotApi($endpoint, $method = 'GET', $payload = null, $timeout = 10) {
        $baseUrl = $this->getApiBaseUrl();
        $url = rtrim($baseUrl, '/') . $endpoint;

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'error' => 'cURL tidak tersedia di server'
            ];
        }

        $ch = curl_init($url);

        $headers = ['Accept: application/json'];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => strtoupper($method)
        ]);

        if ($payload !== null) {
            $body = is_array($payload) ? json_encode($payload) : $payload;
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'error' => $error ?: 'Tidak dapat menghubungi WA Bot API'
            ];
        }

        return [
            'success' => true,
            'body' => $response,
            'status' => $status
        ];
    }

    private function getApiBaseUrl() {
        $host = $_ENV['WA_BOT_API_HOST'] ?? 'localhost';
        $port = $_ENV['WA_BOT_API_PORT'] ?? 3001;
        return sprintf('http://%s:%s', $host, $port);
    }

    private function isSuccessfulHttpCode($code) {
        return $code >= 200 && $code < 300;
    }

    private function respondJson(array $payload, int $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($payload);
    }
}
