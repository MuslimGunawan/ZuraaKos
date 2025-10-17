<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Security: Check for session timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Sesi Anda telah berakhir. Silakan login kembali.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        // Security: Rate limiting - prevent brute force
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt'] = time();
        }
        
        // Reset counter after 15 minutes
        if (time() - $_SESSION['last_attempt'] > 900) {
            $_SESSION['login_attempts'] = 0;
        }
        
        if ($_SESSION['login_attempts'] >= 5) {
            $error = 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.';
        } elseif (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi';
        } else {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Security: Use prepared statements
            $stmt = $conn->prepare("SELECT id, username, email, password, full_name, phone, role, status FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Security: Verify password with timing-safe comparison
                if (password_verify($password, $user['password'])) {
                    // Reset login attempts on successful login
                    $_SESSION['login_attempts'] = 0;
                    
                    // Security: Log successful login
                    error_log("Successful login: " . $user['username'] . " from IP: " . $_SERVER['REMOTE_ADDR']);
                    
                    login($user);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } elseif ($user['role'] === 'penghuni') {
                        header('Location: penghuni/dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                    $error = 'Username atau password salah';
                    
                    // Security: Log failed login attempt
                    error_log("Failed login attempt: " . $username . " from IP: " . $_SERVER['REMOTE_ADDR']);
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
                $error = 'Username atau password salah';
            }
            
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-house-door-fill text-primary" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">Login Sistem Kos</h3>
                            <p class="text-muted">Masuk ke akun Anda</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username atau Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= isset($_POST['username']) ? cleanOutput($_POST['username']) : '' ?>"
                                           required autocomplete="username">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                                <p class="mb-0"><a href="index.php">Kembali ke Beranda</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
