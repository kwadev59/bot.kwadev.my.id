<?php
class UserController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    public function index() {
        $userModel = $this->model('User_model');
        
        $data['judul'] = 'Manajemen User';
        $data['nama_user'] = $_SESSION['nama_lengkap'];
        $data['users'] = $userModel->getAllUsers();

        $this->view('templates/header', $data);
        $this->view('templates/navbar', $data);
        $this->view('user/index', $data);
        $this->view('templates/footer');
    }

    public function tambah() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('User_model');
            
            // Validasi input
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $nama_lengkap = trim($_POST['nama_lengkap']);
            $role = $_POST['role'];
            
            // Cek apakah username sudah ada
            $existingUser = $userModel->getUserByUsername($username);
            if ($existingUser) {
                $_SESSION['flash'] = ['pesan' => 'Username sudah digunakan.', 'tipe' => 'error'];
                header('Location: ' . BASE_URL . '/UserController');
                exit;
            }
            
            // Validasi password (minimal 6 karakter)
            if (strlen($password) < 6) {
                $_SESSION['flash'] = ['pesan' => 'Password minimal 6 karakter.', 'tipe' => 'error'];
                header('Location: ' . BASE_URL . '/UserController');
                exit;
            }
            
            $data = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'nama_lengkap' => $nama_lengkap,
                'role' => $role
            ];
            
            if ($userModel->tambahUser($data)) {
                $_SESSION['flash'] = ['pesan' => 'User berhasil ditambahkan.', 'tipe' => 'success'];
            } else {
                $_SESSION['flash'] = ['pesan' => 'Gagal menambahkan user.', 'tipe' => 'error'];
            }
            
            header('Location: ' . BASE_URL . '/UserController');
            exit;
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userModel = $this->model('User_model');
            
            $id = $_POST['id'];
            $username = trim($_POST['username']);
            $nama_lengkap = trim($_POST['nama_lengkap']);
            $role = $_POST['role'];
            $password = $_POST['password'] ?? '';
            
            // Cek apakah user yang diupdate adalah user sendiri
            if ($id == $_SESSION['user_id'] && $role !== $_SESSION['role']) {
                $_SESSION['flash'] = ['pesan' => 'Anda tidak dapat mengubah peran Anda sendiri.', 'tipe' => 'error'];
                header('Location: ' . BASE_URL . '/UserController');
                exit;
            }
            
            // Jika password diisi, hash password baru
            $data = [
                'username' => $username,
                'nama_lengkap' => $nama_lengkap,
                'role' => $role
            ];
            
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $_SESSION['flash'] = ['pesan' => 'Password minimal 6 karakter.', 'tipe' => 'error'];
                    header('Location: ' . BASE_URL . '/UserController');
                    exit;
                }
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            if ($userModel->updateUser($id, $data)) {
                $_SESSION['flash'] = ['pesan' => 'User berhasil diperbarui.', 'tipe' => 'success'];
            } else {
                $_SESSION['flash'] = ['pesan' => 'Gagal memperbarui user.', 'tipe' => 'error'];
            }
            
            header('Location: ' . BASE_URL . '/UserController');
            exit;
        }
    }

    public function hapus($id) {
        $userModel = $this->model('User_model');
        
        // Tidak dapat menghapus akun sendiri
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash'] = ['pesan' => 'Anda tidak dapat menghapus akun Anda sendiri.', 'tipe' => 'error'];
        } else {
            if ($userModel->hapusUser($id)) {
                $_SESSION['flash'] = ['pesan' => 'User berhasil dihapus.', 'tipe' => 'success'];
            } else {
                $_SESSION['flash'] = ['pesan' => 'Gagal menghapus user.', 'tipe' => 'error'];
            }
        }
        
        header('Location: ' . BASE_URL . '/UserController');
        exit;
    }
}