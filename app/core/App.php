<?php
class App {
    protected $controller = 'AuthController'; // Controller default
    protected $method = 'index'; // Method default
    protected $params = []; // Parameter default

    public function __construct() {
        $url = $this->parseURL();

        // --- CONTROLLER ---
        // Jika user sudah login, controller defaultnya adalah Dashboard
        if (isset($_SESSION['user_id'])) {
            $this->controller = 'DashboardController';
        }
        
        // Cek apakah ada controller yang sesuai dengan URL
        if (isset($url[0])) {
            if (file_exists('../app/controllers/' . $url[0] . '.php')) {
                $this->controller = $url[0];
                unset($url[0]);
            }
        }
        
        // Memuat dan membuat instance dari controller
        require_once '../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // --- METHOD ---
        // Cek apakah ada method yang sesuai dengan URL
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // --- PARAMS ---
        // Mengambil sisa URL sebagai parameter
        if (!empty($url)) {
            $this->params = array_values($url);
        }

        // Menjalankan controller, method, dan mengirimkan parameter
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    // Method untuk mem-parsing URL agar lebih bersih
    public function parseURL() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}