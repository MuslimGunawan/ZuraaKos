<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Statistics
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$available_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")->fetch_assoc()['count'];
$total_penghuni = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'penghuni'")->fetch_assoc()['count'];
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$total_income = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'verified' AND MONTH(payment_date) = MONTH(CURRENT_DATE)")->fetch_assoc()['total'] ?? 0;

// Recent bookings with notes
$recent_bookings = $conn->query("SELECT b.*, u.full_name, r.room_number 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    ORDER BY b.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Zuraa Kos</title>
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
                    <a class="nav-link active" href="dashboard.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard Admin</h2>
                    <div>
                        <span class="text-muted">Selamat datang, <?= $_SESSION['full_name'] ?></span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $total_rooms ?></h3>
                                        <p class="text-muted mb-0">Total Kamar</p>
                                    </div>
                                    <i class="bi bi-door-open text-primary" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $available_rooms ?></h3>
                                        <p class="text-muted mb-0">Kamar Tersedia</p>
                                    </div>
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $total_penghuni ?></h3>
                                        <p class="text-muted mb-0">Total Penghuni</p>
                                    </div>
                                    <i class="bi bi-people text-warning" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= formatRupiah($total_income) ?></h3>
                                        <p class="text-muted mb-0">Pendapatan Bulan Ini</p>
                                    </div>
                                    <i class="bi bi-cash-stack text-success" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Actions -->
                <?php if ($pending_bookings > 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Ada <?= $pending_bookings ?> booking yang menunggu persetujuan. 
                    <a href="bookings.php" class="alert-link">Lihat sekarang</a>
                </div>
                <?php endif; ?>

                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Booking Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Penghuni</th>
                                        <th>Kamar</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_bookings->num_rows > 0): ?>
                                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $booking['id'] ?></td>
                                            <td><?= $booking['full_name'] ?></td>
                                            <td><?= $booking['room_number'] ?></td>
                                            <td><?= formatDate($booking['start_date']) ?></td>
                                            <td><?= formatRupiah($booking['monthly_price']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadge($booking['status']) ?>">
                                                    <?= ucfirst($booking['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $booking['id'] ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Belum ada booking</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="bookings.php" class="btn btn-outline-primary">Lihat Semua Booking</a>
                        </div>
                    </div>
                </div>
                
                <!-- Modals - Outside the card -->
                <?php if ($recent_bookings->num_rows > 0): ?>
                    <?php
                    // Reset pointer to beginning
                    $recent_bookings->data_seek(0);
                    while ($booking = $recent_bookings->fetch_assoc()): 
                    ?>
                    <!-- Detail Modal -->
                    <div class="modal fade" id="detailModal<?= $booking['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detail Booking #<?= $booking['id'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Penghuni:</strong></td>
                                            <td><?= cleanOutput($booking['full_name']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kamar:</strong></td>
                                            <td>Kamar <?= cleanOutput($booking['room_number']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Mulai:</strong></td>
                                            <td><?= formatDate($booking['start_date']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Harga/Bulan:</strong></td>
                                            <td><?= formatRupiah($booking['monthly_price']) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusBadge($booking['status']) ?>">
                                                    <?= ucfirst($booking['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php if (!empty($booking['notes'])): ?>
                                        <tr>
                                            <td><strong>Catatan:</strong></td>
                                            <td><?= nl2br(cleanOutput($booking['notes'])) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
