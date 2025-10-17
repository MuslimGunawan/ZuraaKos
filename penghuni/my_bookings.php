<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requirePenghuni();

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get all bookings
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT b.*, r.room_number, r.type, r.facilities 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          WHERE b.user_id = ?";

if ($filter !== 'all') {
    $query .= " AND b.status = '$filter'";
}

$query .= " ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Get statistics
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id")->fetch_assoc()['count'];
$active_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id AND status IN ('approved', 'active')")->fetch_assoc()['count'];
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id AND status = 'pending'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - Zuraa Kos</title>
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
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="my_room.php"><i class="bi bi-door-open"></i> Kamar Saya</a>
                    <a class="nav-link active" href="my_bookings.php"><i class="bi bi-calendar-check"></i> Booking Saya</a>
                    <a class="nav-link" href="payments.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="complaints.php"><i class="bi bi-chat-left-text"></i> Keluhan</a>
                    <a class="nav-link" href="announcements.php"><i class="bi bi-megaphone"></i> Pengumuman</a>
                    <hr class="bg-light">
                    <a class="nav-link" href="../index.php"><i class="bi bi-house"></i> Beranda</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="bi bi-calendar-check"></i> Riwayat Booking Saya</h2>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $total_bookings ?></h3>
                                        <p class="text-muted mb-0">Total Booking</p>
                                    </div>
                                    <i class="bi bi-calendar-check text-primary" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $active_bookings ?></h3>
                                        <p class="text-muted mb-0">Aktif</p>
                                    </div>
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $pending_bookings ?></h3>
                                        <p class="text-muted mb-0">Menunggu</p>
                                    </div>
                                    <i class="bi bi-clock-history text-warning" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">
                                Semua
                            </a>
                            <a href="?filter=pending" class="btn btn-<?= $filter === 'pending' ? 'warning' : 'outline-warning' ?>">
                                Pending
                            </a>
                            <a href="?filter=approved" class="btn btn-<?= $filter === 'approved' ? 'success' : 'outline-success' ?>">
                                Approved
                            </a>
                            <a href="?filter=active" class="btn btn-<?= $filter === 'active' ? 'primary' : 'outline-primary' ?>">
                                Active
                            </a>
                            <a href="?filter=rejected" class="btn btn-<?= $filter === 'rejected' ? 'danger' : 'outline-danger' ?>">
                                Rejected
                            </a>
                            <a href="?filter=completed" class="btn btn-<?= $filter === 'completed' ? 'secondary' : 'outline-secondary' ?>">
                                Completed
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Daftar Booking</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kamar</th>
                                        <th>Tipe</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Harga/Bulan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bookings->num_rows > 0): ?>
                                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= $booking['id'] ?></strong></td>
                                            <td>Kamar <?= cleanOutput($booking['room_number']) ?></td>
                                            <td><span class="badge bg-info"><?= ucfirst($booking['type']) ?></span></td>
                                            <td><?= formatDate($booking['start_date']) ?></td>
                                            <td><?= formatRupiah($booking['monthly_price']) ?></td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch($booking['status']) {
                                                    case 'pending': $badge_class = 'warning'; break;
                                                    case 'approved': $badge_class = 'success'; break;
                                                    case 'active': $badge_class = 'primary'; break;
                                                    case 'rejected': $badge_class = 'danger'; break;
                                                    case 'terminated': $badge_class = 'secondary'; break;
                                                    case 'completed': $badge_class = 'info'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?= $badge_class ?>">
                                                    <?= ucfirst($booking['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $booking['id'] ?>">
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>
                                                <?php if ($booking['status'] === 'approved'): ?>
                                                    <a href="payments.php" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-upload"></i> Upload Bayar
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                                <p class="text-muted mt-2">Belum ada booking</p>
                                                <a href="../rooms.php" class="btn btn-primary">
                                                    <i class="bi bi-search"></i> Cari Kamar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php if ($bookings->num_rows > 0): ?>
        <?php
        $bookings->data_seek(0);
        while ($booking = $bookings->fetch_assoc()): 
        ?>
        <div class="modal fade" id="detailModal<?= $booking['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Detail Booking #<?= $booking['id'] ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="bi bi-door-open"></i> Informasi Kamar</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Kamar:</strong></td>
                                        <td>Kamar <?= cleanOutput($booking['room_number']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipe:</strong></td>
                                        <td><?= ucfirst($booking['type']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fasilitas:</strong></td>
                                        <td><?= nl2br(cleanOutput($booking['facilities'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="bi bi-calendar-check"></i> Informasi Booking</h6>
                                <table class="table table-borderless">
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
                                            <span class="badge bg-<?= $badge_class ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat:</strong></td>
                                        <td><?= formatDate($booking['created_at']) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['notes'])): ?>
                        <hr>
                        <h6><i class="bi bi-sticky"></i> Catatan:</h6>
                        <div class="alert alert-info">
                            <?= nl2br(cleanOutput($booking['notes'])) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] === 'pending'): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-clock-history"></i> 
                            Booking Anda sedang menunggu persetujuan admin. Mohon tunggu konfirmasi.
                        </div>
                        <?php elseif ($booking['status'] === 'approved'): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            Booking Anda telah disetujui! Silakan upload bukti pembayaran di halaman 
                            <a href="payments.php" class="alert-link">Pembayaran</a>
                        </div>
                        <?php elseif ($booking['status'] === 'active'): ?>
                        <div class="alert alert-primary">
                            <i class="bi bi-house-check"></i> 
                            Selamat! Anda sudah resmi menjadi penghuni kamar ini.
                        </div>
                        <?php elseif ($booking['status'] === 'rejected'): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle"></i> 
                            Maaf, booking Anda ditolak. Silakan coba kamar lain.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <?php if ($booking['status'] === 'approved'): ?>
                            <a href="payments.php" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Pembayaran
                            </a>
                        <?php elseif ($booking['status'] === 'active'): ?>
                            <a href="my_room.php" class="btn btn-primary">
                                <i class="bi bi-door-open"></i> Lihat Kamar
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
