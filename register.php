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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        
        // Security: Enhanced validation
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            $error = 'Semua field wajib diisi';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid';
        } elseif (strlen($username) < 4) {
            $error = 'Username minimal 4 karakter';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username hanya boleh berisi huruf, angka, dan underscore';
        } elseif ($password !== $confirm_password) {
            $error = 'Password tidak cocok';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password harus mengandung huruf besar, huruf kecil, dan angka';
        } else {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Security: Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username atau email sudah terdaftar';
            } else {
                // Security: Use strong password hashing
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 2048,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'pengunjung')");
                $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
                
                if ($stmt->execute()) {
                    $success = 'Registrasi berhasil! Silakan login';
                    
                    // Security: Log successful registration
                    error_log("New user registered: " . $username . " from IP: " . $_SERVER['REMOTE_ADDR']);
                    
                    // Clear form data
                    $_POST = array();
                } else {
                    $error = 'Gagal mendaftar. Silakan coba lagi';
                    error_log("Registration failed for: " . $username);
                }
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
    <title>Registrasi - Sistem Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center py-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-person-plus-fill text-primary" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">Registrasi Akun</h3>
                            <p class="text-muted">Buat akun baru untuk booking kos</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">No. Telepon</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Daftar
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                                <p class="mb-0"><a href="index.php">Kembali ke Beranda</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
