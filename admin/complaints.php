<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);
    $new_status = sanitize($_GET['status']);
    
    if (in_array($new_status, ['pending', 'in_progress', 'resolved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $complaint_id);
        if ($stmt->execute()) {
            $success = "Status keluhan berhasil diubah!";
        } else {
            $error = "Gagal mengubah status!";
        }
    }
}

// Handle Priority Change
if (isset($_GET['priority']) && isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);
    $new_priority = sanitize($_GET['priority']);
    
    if (in_array($new_priority, ['low', 'medium', 'high'])) {
        $stmt = $conn->prepare("UPDATE complaints SET priority = ? WHERE id = ?");
        $stmt->bind_param("si", $new_priority, $complaint_id);
        if ($stmt->execute()) {
            $success = "Prioritas keluhan berhasil diubah!";
        } else {
            $error = "Gagal mengubah prioritas!";
        }
    }
}

// Handle Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $complaint_id = intval($_POST['complaint_id']);
        $reply = sanitize($_POST['reply_text']);
        
        $stmt = $conn->prepare("UPDATE complaints SET reply = ?, status = 'resolved' WHERE id = ?");
        $stmt->bind_param("si", $reply, $complaint_id);
        
        if ($stmt->execute()) {
            $success = "Balasan berhasil dikirim dan status diubah menjadi Resolved!";
        } else {
            $error = "Gagal mengirim balasan!";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $complaint_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM complaints WHERE id = ?");
    $stmt->bind_param("i", $complaint_id);
    if ($stmt->execute()) {
        $success = "Keluhan berhasil dihapus!";
    } else {
        $error = "Gagal menghapus keluhan!";
    }
}

// Get all complaints
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT c.*, u.full_name, u.phone, u.email, r.room_number 
          FROM complaints c 
          JOIN users u ON c.user_id = u.id 
          LEFT JOIN rooms r ON c.room_id = r.id";

if ($filter !== 'all') {
    $query .= " WHERE c.status = '$filter'";
}

$query .= " ORDER BY 
            CASE c.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            c.created_at DESC";

$complaints = $conn->query($query);

// Get statistics
$total_complaints = $conn->query("SELECT COUNT(*) as count FROM complaints")->fetch_assoc()['count'];
$pending = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'")->fetch_assoc()['count'];
$in_progress = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'in_progress'")->fetch_assoc()['count'];
$resolved = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'resolved'")->fetch_assoc()['count'];
$high_priority = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE priority = 'high' AND status != 'resolved'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Keluhan - Admin Zuraa Kos</title>
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
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> Pengguna
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
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-exclamation-circle"></i> Kelola Keluhan</h1>
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
                    <div class="col-md-3">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_complaints ?></h3>
                                <p class="card-text">Total Keluhan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card warning">
                            <div class="card-body">
                                <h3 class="card-title"><?= $pending ?></h3>
                                <p class="card-text">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <h3 class="card-title"><?= $in_progress ?></h3>
                                <p class="card-text">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card dashboard-card danger">
                            <div class="card-body">
                                <h3 class="card-title"><?= $high_priority ?></h3>
                                <p class="card-text">High Priority</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert for High Priority -->
                <?php if ($high_priority > 0): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Perhatian!</strong> Ada <?= $high_priority ?> keluhan dengan prioritas tinggi yang belum diselesaikan.
                    </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="complaints.php?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                                <i class="bi bi-funnel"></i> Semua (<?= $total_complaints ?>)
                            </a>
                            <a href="complaints.php?filter=pending" class="btn btn-outline-warning <?= $filter === 'pending' ? 'active' : '' ?>">
                                <i class="bi bi-clock"></i> Pending (<?= $pending ?>)
                            </a>
                            <a href="complaints.php?filter=in_progress" class="btn btn-outline-info <?= $filter === 'in_progress' ? 'active' : '' ?>">
                                <i class="bi bi-arrow-repeat"></i> In Progress (<?= $in_progress ?>)
                            </a>
                            <a href="complaints.php?filter=resolved" class="btn btn-outline-success <?= $filter === 'resolved' ? 'active' : '' ?>">
                                <i class="bi bi-check-circle"></i> Resolved (<?= $resolved ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Complaints Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Priority</th>
                                        <th>Judul</th>
                                        <th>Penghuni</th>
                                        <th>Kamar</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($complaint = $complaints->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $complaint['id'] ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm dropdown-toggle 
                                                        <?php 
                                                            echo $complaint['priority'] === 'high' ? 'btn-danger' : 
                                                                ($complaint['priority'] === 'medium' ? 'btn-warning' : 'btn-secondary');
                                                        ?>" 
                                                        data-bs-toggle="dropdown">
                                                        <?= strtoupper($complaint['priority']) ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="?priority=high&id=<?= $complaint['id'] ?>">
                                                                <span class="badge bg-danger">HIGH</span>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="?priority=medium&id=<?= $complaint['id'] ?>">
                                                                <span class="badge bg-warning">MEDIUM</span>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="?priority=low&id=<?= $complaint['id'] ?>">
                                                                <span class="badge bg-secondary">LOW</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= cleanOutput($complaint['title']) ?></strong>
                                            </td>
                                            <td>
                                                <?= cleanOutput($complaint['full_name']) ?><br>
                                                <small class="text-muted">
                                                    <a href="tel:<?= cleanOutput($complaint['phone']) ?>">
                                                        <?= cleanOutput($complaint['phone']) ?>
                                                    </a>
                                                </small>
                                            </td>
                                            <td>
                                                <?= $complaint['room_number'] ? 'Kamar ' . $complaint['room_number'] : '-' ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $status_badges = [
                                                        'pending' => '<span class="badge bg-warning">Pending</span>',
                                                        'in_progress' => '<span class="badge bg-info">In Progress</span>',
                                                        'resolved' => '<span class="badge bg-success">Resolved</span>',
                                                        'rejected' => '<span class="badge bg-danger">Rejected</span>'
                                                    ];
                                                    echo $status_badges[$complaint['status']] ?? '';
                                                ?>
                                            </td>
                                            <td><?= formatDate($complaint['created_at']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick='viewComplaint(<?= json_encode($complaint) ?>)'>
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>
                                                
                                                <?php if ($complaint['status'] !== 'resolved'): ?>
                                                    <button class="btn btn-sm btn-success" onclick='replyComplaint(<?= json_encode($complaint) ?>)'>
                                                        <i class="bi bi-reply"></i> Balas
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <a href="?delete=<?= $complaint['id'] ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Yakin ingin menghapus keluhan ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Keluhan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Penghuni:</strong>
                            <p id="detailName" class="mb-1"></p>
                            <p id="detailContact" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Kamar:</strong>
                            <p id="detailRoom"></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Judul Keluhan:</strong>
                        <p id="detailTitle"></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Deskripsi:</strong>
                        <p id="detailDescription" class="border p-3 rounded bg-light"></p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Status:</strong>
                            <p id="detailStatus"></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Priority:</strong>
                            <p id="detailPriority"></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Tanggal:</strong>
                            <p id="detailDate"></p>
                        </div>
                    </div>
                    
                    <div id="replySection" class="mb-3" style="display: none;">
                        <strong>Balasan Admin:</strong>
                        <div id="detailReply" class="border p-3 rounded bg-success bg-opacity-10"></div>
                    </div>
                    
                    <div class="btn-group" role="group">
                        <a id="btnPending" class="btn btn-warning btn-sm">
                            <i class="bi bi-clock"></i> Set Pending
                        </a>
                        <a id="btnInProgress" class="btn btn-info btn-sm">
                            <i class="bi bi-arrow-repeat"></i> Set In Progress
                        </a>
                        <a id="btnResolved" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Set Resolved
                        </a>
                        <a id="btnRejected" class="btn btn-danger btn-sm">
                            <i class="bi bi-x-circle"></i> Set Rejected
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Balas Keluhan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="complaint_id" id="replyComplaintId">
                        <input type="hidden" name="reply" value="1">
                        
                        <div class="mb-3">
                            <strong>Judul Keluhan:</strong>
                            <p id="replyTitle" class="text-muted"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Balasan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reply_text" rows="5" required 
                                      placeholder="Tulis balasan untuk penghuni..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Status keluhan akan otomatis berubah menjadi <strong>Resolved</strong> setelah Anda mengirim balasan.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Kirim Balasan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewComplaint(complaint) {
            document.getElementById('detailName').textContent = complaint.full_name;
            document.getElementById('detailContact').innerHTML = 
                '<a href="tel:' + complaint.phone + '">' + complaint.phone + '</a> • ' +
                '<a href="mailto:' + complaint.email + '">' + complaint.email + '</a>';
            document.getElementById('detailRoom').textContent = complaint.room_number ? 'Kamar ' + complaint.room_number : '-';
            document.getElementById('detailTitle').textContent = complaint.title;
            document.getElementById('detailDescription').textContent = complaint.description;
            
            const statusBadges = {
                'pending': '<span class="badge bg-warning">Pending</span>',
                'in_progress': '<span class="badge bg-info">In Progress</span>',
                'resolved': '<span class="badge bg-success">Resolved</span>',
                'rejected': '<span class="badge bg-danger">Rejected</span>'
            };
            document.getElementById('detailStatus').innerHTML = statusBadges[complaint.status];
            
            const priorityBadges = {
                'high': '<span class="badge bg-danger">HIGH</span>',
                'medium': '<span class="badge bg-warning">MEDIUM</span>',
                'low': '<span class="badge bg-secondary">LOW</span>'
            };
            document.getElementById('detailPriority').innerHTML = priorityBadges[complaint.priority];
            
            document.getElementById('detailDate').textContent = new Date(complaint.created_at).toLocaleDateString('id-ID', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
            
            // Show reply if exists
            if (complaint.reply) {
                document.getElementById('replySection').style.display = 'block';
                document.getElementById('detailReply').textContent = complaint.reply;
            } else {
                document.getElementById('replySection').style.display = 'none';
            }
            
            // Update status buttons
            document.getElementById('btnPending').href = '?status=pending&id=' + complaint.id;
            document.getElementById('btnInProgress').href = '?status=in_progress&id=' + complaint.id;
            document.getElementById('btnResolved').href = '?status=resolved&id=' + complaint.id;
            document.getElementById('btnRejected').href = '?status=rejected&id=' + complaint.id;
            
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }

        function replyComplaint(complaint) {
            document.getElementById('replyComplaintId').value = complaint.id;
            document.getElementById('replyTitle').textContent = complaint.title;
            
            new bootstrap.Modal(document.getElementById('replyModal')).show();
        }
    </script>
</body>
</html>
