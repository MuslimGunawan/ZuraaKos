<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle Verify Payment
if (isset($_GET['verify']) && isset($_GET['id'])) {
    $payment_id = intval($_GET['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update payment status
        $stmt = $conn->prepare("UPDATE payments SET status = 'verified' WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        
        // Get booking_id from payment
        $stmt = $conn->prepare("SELECT booking_id FROM payments WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $booking_id = $stmt->get_result()->fetch_assoc()['booking_id'];
        
        // Update booking status to active
        $stmt = $conn->prepare("UPDATE bookings SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        $conn->commit();
        $success = "Pembayaran berhasil diverifikasi dan booking diaktifkan!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal memverifikasi pembayaran!";
    }
}

// Handle Reject Payment
if (isset($_GET['reject']) && isset($_GET['id'])) {
    $payment_id = intval($_GET['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update payment status to rejected
        $stmt = $conn->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        
        $conn->commit();
        $success = "Pembayaran ditolak! Penghuni perlu upload ulang bukti pembayaran yang benar.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menolak pembayaran!";
    }
}

// Get all payments
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT p.*, u.full_name, u.phone, b.room_id, r.room_number 
          FROM payments p 
          JOIN users u ON p.user_id = u.id 
          JOIN bookings b ON p.booking_id = b.id 
          JOIN rooms r ON b.room_id = r.id";

if ($filter !== 'all') {
    $query .= " WHERE p.status = '$filter'";
}

$query .= " ORDER BY p.created_at DESC";
$payments = $conn->query($query);

// Get statistics
$total_payments = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'verified'")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch_assoc()['count'];
$this_month = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'verified' AND MONTH(payment_date) = MONTH(CURRENT_DATE)")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Admin Zuraa Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (sama seperti sebelumnya) -->
            <div class="col-md-2 sidebar p-4">
                <h4 class="mb-4"><i class="bi bi-house-door-fill"></i> Zuraa Kos</h4>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="rooms.php"><i class="bi bi-door-open"></i> Kelola Kamar</a>
                    <a class="nav-link" href="bookings.php"><i class="bi bi-calendar-check"></i> Booking</a>
                    <a class="nav-link active" href="payments.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Pengguna</a>
                    <a class="nav-link" href="complaints.php"><i class="bi bi-chat-left-text"></i> Keluhan</a>
                    <a class="nav-link" href="announcements.php"><i class="bi bi-megaphone"></i> Pengumuman</a>
                    <hr class="bg-light">
                    <a class="nav-link" href="../index.php"><i class="bi bi-house"></i> Beranda</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="bi bi-cash-coin"></i> Kelola Pembayaran</h2>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card success">
                            <div class="card-body">
                                <h6 class="text-muted">Total Pembayaran</h6>
                                <h3><?= formatRupiah($total_payments) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card warning">
                            <div class="card-body">
                                <h6 class="text-muted">Pending</h6>
                                <h3><?= $pending_payments ?> Pembayaran</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <h6 class="text-muted">Bulan Ini</h6>
                                <h3><?= formatRupiah($this_month) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="btn-group">
                            <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">Semua</a>
                            <a href="?filter=pending" class="btn btn-<?= $filter === 'pending' ? 'warning' : 'outline-warning' ?>">Pending</a>
                            <a href="?filter=verified" class="btn btn-<?= $filter === 'verified' ? 'success' : 'outline-success' ?>">Verified</a>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Penghuni</th>
                                        <th>Kamar</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal</th>
                                        <th>Metode</th>
                                        <th>Bukti</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <?php 
                                    // Define badge class for status
                                    $badge_class = match($payment['status']) {
                                        'verified' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <tr>
                                        <td>#<?= $payment['id'] ?></td>
                                        <td><?= cleanOutput($payment['full_name']) ?></td>
                                        <td><?= cleanOutput($payment['room_number']) ?></td>
                                        <td><strong><?= formatRupiah($payment['amount']) ?></strong></td>
                                        <td><?= formatDate($payment['payment_date']) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($payment['payment_method']) ?></span></td>
                                        <td>
                                            <?php if ($payment['payment_proof']): ?>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#proofModal<?= $payment['id'] ?>">
                                                    <i class="bi bi-image"></i> Lihat
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badge_class ?>">
                                                <?= ucfirst($payment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['status'] === 'pending'): ?>
                                                <div class="btn-group" role="group">
                                                    <a href="?verify=1&id=<?= $payment['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Verifikasi pembayaran ini? Status booking akan menjadi Active.')">
                                                        <i class="bi bi-check-lg"></i> Verify
                                                    </a>
                                                    <a href="?reject=1&id=<?= $payment['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pembayaran ini? Penghuni perlu upload ulang bukti yang benar.')">
                                                        <i class="bi bi-x-lg"></i> Tolak
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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

    <!-- Proof Modals (Outside Table) -->
    <?php 
    $payments->data_seek(0); // Reset pointer
    while ($payment = $payments->fetch_assoc()): 
    ?>
        <?php if ($payment['payment_proof']): ?>
        <div class="modal fade" id="proofModal<?= $payment['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bukti Pembayaran #<?= $payment['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Penghuni:</strong> <?= cleanOutput($payment['full_name']) ?><br>
                                <strong>Kamar:</strong> <?= cleanOutput($payment['room_number']) ?><br>
                                <strong>Jumlah:</strong> <?= formatRupiah($payment['amount']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Tanggal:</strong> <?= formatDate($payment['payment_date']) ?><br>
                                <strong>Metode:</strong> <?= ucfirst($payment['payment_method']) ?><br>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?= $payment['status'] === 'verified' ? 'success' : ($payment['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <img src="../uploads/<?= cleanOutput($payment['payment_proof']) ?>" class="img-fluid rounded shadow" alt="Bukti Pembayaran" style="max-height: 500px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <?php if ($payment['status'] === 'pending'): ?>
                            <a href="?verify=1&id=<?= $payment['id'] ?>" class="btn btn-success" onclick="return confirm('Verifikasi pembayaran ini?')">
                                <i class="bi bi-check-lg"></i> Verify
                            </a>
                            <a href="?reject=1&id=<?= $payment['id'] ?>" class="btn btn-danger" onclick="return confirm('Tolak pembayaran ini?')">
                                <i class="bi bi-x-lg"></i> Tolak
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
