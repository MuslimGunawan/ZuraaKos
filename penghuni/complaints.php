<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requirePenghuni();

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Handle Submit Complaint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $subject = sanitize($_POST['subject']);
        $description = sanitize($_POST['description']);
        $category = sanitize($_POST['category']);
        
        // Get user's active room
        $room_query = "SELECT room_id FROM bookings WHERE user_id = ? AND status IN ('active', 'approved') ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($room_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $room_result = $stmt->get_result()->fetch_assoc();
        
        if ($room_result) {
            $room_id = $room_result['room_id'];
            
            $stmt = $conn->prepare("INSERT INTO complaints (user_id, room_id, subject, description, category, status) VALUES (?, ?, ?, ?, ?, 'open')");
            $stmt->bind_param("iisss", $user_id, $room_id, $subject, $description, $category);
            
            if ($stmt->execute()) {
                $success = "Keluhan berhasil dikirim! Admin akan segera merespons.";
            } else {
                $error = "Gagal mengirim keluhan!";
            }
        } else {
            $error = "Anda belum memiliki kamar aktif!";
        }
    }
}

// Get user's complaints
$complaints_query = "SELECT c.*, r.room_number 
                     FROM complaints c 
                     JOIN rooms r ON c.room_id = r.id 
                     WHERE c.user_id = ? 
                     ORDER BY c.created_at DESC";
$stmt = $conn->prepare($complaints_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$complaints = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keluhan - Zuraa Kos</title>
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
                    <a class="nav-link" href="my_room.php">
                        <i class="bi bi-door-open"></i> Kamar Saya
                    </a>
                    <a class="nav-link" href="my_bookings.php">
                        <i class="bi bi-calendar-check"></i> Booking Saya
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-cash-coin"></i> Pembayaran
                    </a>
                    <a class="nav-link active" href="complaints.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-chat-left-text"></i> Keluhan</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#complaintModal">
                        <i class="bi bi-plus-circle"></i> Buat Keluhan
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

                <!-- Complaints List -->
                <div class="row">
                    <?php if ($complaints->num_rows > 0): ?>
                        <?php while ($complaint = $complaints->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?= cleanOutput($complaint['subject']) ?></h6>
                                    <?php
                                    $category_class = '';
                                    switch($complaint['category']) {
                                        case 'facility': $category_class = 'danger'; break;
                                        case 'service': $category_class = 'warning'; break;
                                        case 'payment': $category_class = 'info'; break;
                                        case 'other': $category_class = 'secondary'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $category_class ?>">
                                        <?= ucfirst($complaint['category']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-door-open"></i> Kamar <?= $complaint['room_number'] ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-clock"></i> <?= formatDate($complaint['created_at']) ?>
                                    </p>
                                    <p class="mb-3"><?= nl2br(cleanOutput($complaint['description'])) ?></p>
                                    
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($complaint['status']) {
                                        case 'open': 
                                            $status_class = 'warning'; 
                                            $status_icon = 'clock';
                                            break;
                                        case 'in_progress': 
                                            $status_class = 'info'; 
                                            $status_icon = 'hourglass-split';
                                            break;
                                        case 'resolved': 
                                            $status_class = 'success'; 
                                            $status_icon = 'check-circle';
                                            break;
                                        case 'closed':
                                            $status_class = 'secondary';
                                            $status_icon = 'check-all';
                                            break;
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $status_class ?>">
                                            <i class="bi bi-<?= $status_icon ?>"></i> <?= ucfirst(str_replace('_', ' ', $complaint['status'])) ?>
                                        </span>
                                        <?php if (!empty($complaint['response'])): ?>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#responseModal<?= $complaint['id'] ?>">
                                            <i class="bi bi-chat-dots"></i> Lihat Balasan
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Response Modal -->
                        <?php if (!empty($complaint['response'])): ?>
                        <div class="modal fade" id="responseModal<?= $complaint['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Balasan Admin</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <strong><i class="bi bi-person-badge"></i> Admin:</strong><br>
                                            <?= nl2br(cleanOutput($complaint['response'])) ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">Belum ada keluhan</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#complaintModal">
                                    <i class="bi bi-plus-circle"></i> Buat Keluhan Pertama
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Complaint Modal -->
    <div class="modal fade" id="complaintModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Keluhan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="submit_complaint" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Subjek Keluhan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required maxlength="200"
                                   placeholder="Contoh: AC tidak dingin">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" required>
                                <option value="facility">Fasilitas</option>
                                <option value="service">Layanan</option>
                                <option value="payment">Pembayaran</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="4" required
                                      placeholder="Jelaskan keluhan Anda secara detail..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Kirim Keluhan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
