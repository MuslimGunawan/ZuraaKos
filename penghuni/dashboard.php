<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requirePenghuni();

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get user's active booking
$booking_query = "SELECT b.*, r.room_number, r.type, r.facilities 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? AND b.status IN ('active', 'approved')
    ORDER BY b.created_at DESC LIMIT 1";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_booking = $stmt->get_result()->fetch_assoc();

// Get payment history
$payments_query = "SELECT p.*, b.room_id, r.room_number 
    FROM payments p 
    JOIN bookings b ON p.booking_id = b.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC LIMIT 5";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result();

// Get complaints
$complaints_query = "SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
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
    <title>Dashboard Penghuni - Zuraa Kos</title>
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
                    <a class="nav-link" href="my_room.php">
                        <i class="bi bi-door-open"></i> Kamar Saya
                    </a>
                    <a class="nav-link" href="my_bookings.php">
                        <i class="bi bi-calendar-check"></i> Booking Saya
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-cash-coin"></i> Pembayaran
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
                    <h2>Dashboard Penghuni</h2>
                    <div>
                        <span class="text-muted">Selamat datang, <?= $_SESSION['full_name'] ?></span>
                    </div>
                </div>

                <!-- Active Room Info -->
                <?php if ($active_booking): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-door-open"></i> Kamar Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Kamar <?= $active_booking['room_number'] ?></h4>
                                <p class="mb-1"><strong>Tipe:</strong> <?= ucfirst($active_booking['type']) ?></p>
                                <p class="mb-1"><strong>Harga/Bulan:</strong> <?= formatRupiah($active_booking['monthly_price']) ?></p>
                                <p class="mb-1"><strong>Tanggal Mulai:</strong> <?= formatDate($active_booking['start_date']) ?></p>
                                <p class="mb-1"><strong>Status:</strong> 
                                    <span class="badge bg-<?= getStatusBadge($active_booking['status']) ?>">
                                        <?= ucfirst($active_booking['status']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Fasilitas:</h6>
                                <p><?= $active_booking['facilities'] ?></p>
                                <a href="my_room.php" class="btn btn-primary">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Anda belum memiliki kamar aktif. <a href="../rooms.php" class="alert-link">Cari kamar sekarang</a>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Payment History -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Riwayat Pembayaran</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Jumlah</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($payments->num_rows > 0): ?>
                                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= formatDate($payment['payment_date']) ?></td>
                                                    <td><?= formatRupiah($payment['amount']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= getStatusBadge($payment['status']) ?>">
                                                            <?= ucfirst($payment['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">Belum ada pembayaran</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="payments.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Complaints -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Keluhan Saya</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Subjek</th>
                                                <th>Kategori</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($complaints->num_rows > 0): ?>
                                                <?php while ($complaint = $complaints->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= substr($complaint['subject'], 0, 20) ?>...</td>
                                                    <td><?= ucfirst($complaint['category']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= getStatusBadge($complaint['status']) ?>">
                                                            <?= str_replace('_', ' ', $complaint['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">Belum ada keluhan</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="complaints.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                                    <a href="complaints.php?action=add" class="btn btn-sm btn-primary">Buat Keluhan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
