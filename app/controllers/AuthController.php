<?php
/**
 * Class AuthController
 *
 * Mengelola otentikasi pengguna, termasuk login dan logout.
 */
class AuthController extends Controller {
    
    /**
     * Menampilkan halaman login.
     *
     * Jika pengguna sudah login, akan diarahkan ke dashboard.
     */
    public function index() {
        // Jika user sudah login, tendang ke dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/DashboardController');
            exit;
        }
        
        $data['judul'] = 'Login Panel';
        $this->view('auth/login', $data);
    }

    /**
     * Memproses permintaan login dari pengguna.
     *
     * Memverifikasi username dan password, membuat sesi jika berhasil,
     * dan mengarahkan kembali dengan pesan error jika gagal.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('User_model');
            $user = $userModel->getUserByUsername($_POST['username']);

            // Cek user dan verifikasi password
            if ($user && password_verify($_POST['password'], $user['password'])) {
                // Jika login berhasil, buat session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: ' . BASE_URL . '/DashboardController');
                exit;
            } else {
                // Jika gagal, kembalikan ke halaman login dengan pesan error
                $_SESSION['login_error'] = 'Username atau password salah!';
                header('Location: ' . BASE_URL);
                exit;
            }
        } else {
            // Jika diakses langsung tanpa POST, tendang ke halaman utama
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    /**
     * Memproses permintaan logout.
     *
     * Menghancurkan sesi pengguna dan mengarahkan kembali ke halaman utama.
     */
    public function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();
            session_destroy();
        }

        header('Location: ' . BASE_URL);
        exit;
    }
}
