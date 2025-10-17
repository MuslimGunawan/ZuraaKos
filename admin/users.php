<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle Delete User
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Prevent deleting admin's own account
    if ($user_id == $_SESSION['user_id']) {
        $error = "Tidak dapat menghapus akun Anda sendiri!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success = "User berhasil dihapus!";
        } else {
            $error = "Gagal menghapus user!";
        }
    }
}

// Handle Change Role
if (isset($_GET['change_role']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $new_role = sanitize($_GET['change_role']);
    
    if (in_array($new_role, ['admin', 'penghuni'])) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        if ($stmt->execute()) {
            $success = "Role user berhasil diubah!";
        } else {
            $error = "Gagal mengubah role!";
        }
    }
}

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $role = sanitize($_POST['role']);
        $password = $_POST['password'] ?? '';
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing user
            $user_id = intval($_POST['id']);
            
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 2048,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $username, $email, $full_name, $phone, $role, $hashed_password, $user_id);
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $username, $email, $full_name, $phone, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $success = "User berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate user!";
            }
        } else {
            // Add new user
            if (empty($password)) {
                $error = "Password wajib diisi untuk user baru!";
            } else {
                // Check if username or email already exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = "Username atau email sudah digunakan!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                        'memory_cost' => 2048,
                        'time_cost' => 4,
                        'threads' => 3
                    ]);
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone, $role);
                    
                    if ($stmt->execute()) {
                        $success = "User baru berhasil ditambahkan!";
                    } else {
                        $error = "Gagal menambahkan user!";
                    }
                }
            }
        }
    }
}

// Get all users
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT * FROM users";

if ($filter !== 'all') {
    $query .= " WHERE role = '$filter'";
}

$query .= " ORDER BY created_at DESC";
$users = $conn->query($query);

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_penghuni = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'penghuni'")->fetch_assoc()['count'];
$total_admin = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Admin Zuraa Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-4">
                <h4 class="mb-4"><i class="bi bi-house-door-fill"></i> Zuraa Kos</h4>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="rooms.php">
                        <i class="bi bi-door-open"></i> Kelola Kamar
                    </a>
                    <a class="nav-link" href="bookings.php">
                        <i class="bi bi-calendar-check"></i> Booking
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-cash-coin"></i> Pembayaran
                    </a>
                    <a class="nav-link active" href="users.php">
                        <i class="bi bi-people"></i> Pengguna
                    </a>
                    <a class="nav-link" href="complaints.php">
                        <i class="bi bi-chat-left-text"></i> Keluhan
                    </a>
                    <a class="nav-link" href="announcements.php">
                        <i class="bi bi-megaphone"></i> Pengumuman
                    </a>
                    <hr class="bg-light">
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-house"></i> Beranda
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people"></i> Kelola Users</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                        <i class="bi bi-plus-circle"></i> Tambah User
                    </button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_users ?></h3>
                                <p class="card-text">Total Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card success">
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_penghuni ?></h3>
                                <p class="card-text">Total Penghuni</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card warning">
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_admin ?></h3>
                                <p class="card-text">Total Admin</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="users.php?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                                <i class="bi bi-funnel"></i> Semua (<?= $total_users ?>)
                            </a>
                            <a href="users.php?filter=admin" class="btn btn-outline-primary <?= $filter === 'admin' ? 'active' : '' ?>">
                                <i class="bi bi-shield-check"></i> Admin (<?= $total_admin ?>)
                            </a>
                            <a href="users.php?filter=penghuni" class="btn btn-outline-primary <?= $filter === 'penghuni' ? 'active' : '' ?>">
                                <i class="bi bi-person"></i> Penghuni (<?= $total_penghuni ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Bergabung</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><strong><?= cleanOutput($user['username']) ?></strong></td>
                                            <td><?= cleanOutput($user['full_name'] ?? '-') ?></td>
                                            <td>
                                                <a href="mailto:<?= cleanOutput($user['email']) ?>">
                                                    <?= cleanOutput($user['email']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($user['phone']): ?>
                                                    <a href="tel:<?= cleanOutput($user['phone']) ?>">
                                                        <?= cleanOutput($user['phone']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-shield-check"></i> Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">
                                                        <i class="bi bi-person"></i> Penghuni
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatDate($user['created_at']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick='editUser(<?= json_encode($user) ?>)'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <!-- Change Role -->
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown">
                                                            <i class="bi bi-person-badge"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="?change_role=admin&id=<?= $user['id'] ?>" 
                                                                   onclick="return confirm('Ubah role menjadi Admin?')">
                                                                    <i class="bi bi-shield-check"></i> Set as Admin
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="?change_role=penghuni&id=<?= $user['id'] ?>" 
                                                                   onclick="return confirm('Ubah role menjadi Penghuni?')">
                                                                    <i class="bi bi-person"></i> Set as Penghuni
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    
                                                    <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-success">You</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" id="userId">
                        
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" id="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="full_name" id="full_name">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">No. Telepon</label>
                            <input type="tel" class="form-control" name="phone" id="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="penghuni">Penghuni</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <span id="passwordRequired">(required)</span></label>
                            <input type="password" class="form-control" name="password" id="password">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Tambah User';
            document.getElementById('userId').value = '';
            document.getElementById('username').value = '';
            document.getElementById('email').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('role').value = 'penghuni';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').textContent = '(required)';
        }

        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('full_name').value = user.full_name || '';
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').textContent = '(optional)';
            
            new bootstrap.Modal(document.getElementById('userModal')).show();
        }
    </script>
</body>
</html>
