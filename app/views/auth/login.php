<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['judul']; ?> - WA Bot Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>/../style_v2.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            position: relative;
            overflow: hidden;
        }
        
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            top: -100px;
            right: -100px;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #f72585, #b5179e);
            bottom: -80px;
            left: -80px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .login-header {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #3a56e4, #310a9d);
            transform: translateY(-1px);
        }
        
        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem 0 0 0.5rem;
        }

        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(67, 97, 238, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 5;
            backdrop-filter: blur(4px);
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .loading-content {
            max-width: 260px;
            color: #fff;
        }

        .loading-bar {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.3);
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .loading-bar::before {
            content: '';
            display: block;
            width: 40%;
            height: 100%;
            background: linear-gradient(135deg, #ffffff 0%, #a0c4ff 100%);
            border-radius: inherit;
            animation: shimmer 1.1s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            50% {
                transform: translateX(150%);
            }
            100% {
                transform: translateX(150%);
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-robot" style="font-size: 3rem;"></i>
                <h2 class="mt-3 mb-1">WA Bot Panel</h2>
                <p class="mb-0">Sistem Monitoring Laporan WhatsApp</p>
            </div>
            <div class="login-body">
                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['login_error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['login_error']); ?>
                <?php endif; ?>
                
                <form action="<?= BASE_URL; ?>/AuthController/login" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4 text-muted">
                    <small>Sistem Monitoring File Laporan &copy; <?= date('Y'); ?></small>
                </div>
            </div>
            <div class="loading-overlay" aria-hidden="true">
                <div class="loading-content">
                    <div class="loading-bar mb-3"></div>
                    <p class="mb-0 fw-semibold">Mengautentikasi akun...</p>
                    <small class="text-white-50">Mohon tunggu sebentar</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginForm = document.querySelector('.login-body form');
            if (!loginForm) {
                return;
            }

            const submitButton = loginForm.querySelector('button[type="submit"]');
            const overlay = document.querySelector('.loading-overlay');

            if (!submitButton || !overlay) {
                return;
            }

            const originalButtonHtml = submitButton.innerHTML;
            let resetTimer = null;

            loginForm.addEventListener('submit', function () {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
                overlay.classList.add('active');

                if (resetTimer) {
                    clearTimeout(resetTimer);
                }

                resetTimer = setTimeout(function () {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHtml;
                    overlay.classList.remove('active');
                }, 15000);
            });

            window.addEventListener('beforeunload', function () {
                if (resetTimer) {
                    clearTimeout(resetTimer);
                }
            });
        });
    </script>
</body>
</html>
