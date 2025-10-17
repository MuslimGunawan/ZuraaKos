<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requirePenghuni();

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get active booking with room details
$query = "SELECT b.*, r.* 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          WHERE b.user_id = ? AND b.status IN ('active', 'approved')
          ORDER BY b.created_at DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamar Saya - Zuraa Kos</title>
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
                    <a class="nav-link active" href="my_room.php">
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
                    <h2><i class="bi bi-door-open"></i> Kamar Saya</h2>
                </div>

                <?php if ($booking): ?>
                    <!-- Room Details -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Detail Kamar <?= $booking['room_number'] ?></h5>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $image_path = '../uploads/' . $booking['image'];
                                    if ($booking['image'] && file_exists($image_path)) {
                                        echo '<img src="' . cleanOutput($image_path) . '" class="img-fluid rounded mb-3" alt="Kamar">';
                                    } else {
                                        echo '<img src="../Asset/kamar-1.jpg" class="img-fluid rounded mb-3" alt="Kamar">';
                                    }
                                    ?>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong><i class="bi bi-house"></i> Nomor Kamar:</strong><br>
                                                <span class="fs-4">Kamar <?= $booking['room_number'] ?></span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong><i class="bi bi-building"></i> Lantai:</strong><br>
                                                <span class="fs-5">Lantai <?= $booking['floor'] ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong><i class="bi bi-tag"></i> Tipe:</strong><br>
                                                <span class="badge bg-info"><?= ucfirst($booking['type']) ?></span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong><i class="bi bi-cash"></i> Harga/Bulan:</strong><br>
                                                <span class="text-success fs-5"><?= formatRupiah($booking['price']) ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="bi bi-stars"></i> Fasilitas:</strong>
                                        <ul class="list-unstyled mt-2">
                                            <?php
                                            $facilities = explode(',', $booking['facilities']);
                                            foreach ($facilities as $facility):
                                            ?>
                                                <li><i class="bi bi-check-circle text-success"></i> <?= trim($facility) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <?php if ($booking['description']): ?>
                                    <div class="mb-3">
                                        <strong><i class="bi bi-info-circle"></i> Deskripsi:</strong>
                                        <p class="text-muted mt-2"><?= nl2br(cleanOutput($booking['description'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Booking Info -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Info Sewa</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">
                                        <strong>Status:</strong><br>
                                        <?php
                                        $status_class = $booking['status'] === 'active' ? 'success' : 'warning';
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>"><?= ucfirst($booking['status']) ?></span>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Mulai Sewa:</strong><br>
                                        <?= formatDate($booking['start_date']) ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Harga Bulanan:</strong><br>
                                        <span class="text-success fw-bold"><?= formatRupiah($booking['monthly_price']) ?></span>
                                    </p>
                                    <?php if ($booking['notes']): ?>
                                    <p class="mb-0">
                                        <strong>Catatan:</strong><br>
                                        <small class="text-muted"><?= cleanOutput($booking['notes']) ?></small>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bi bi-lightning"></i> Aksi Cepat</h6>
                                </div>
                                <div class="card-body">
                                    <a href="payments.php" class="btn btn-primary btn-sm w-100 mb-2">
                                        <i class="bi bi-cash-coin"></i> Bayar Sewa
                                    </a>
                                    <a href="complaints.php" class="btn btn-warning btn-sm w-100 mb-2">
                                        <i class="bi bi-chat-left-text"></i> Ajukan Keluhan
                                    </a>
                                    <a href="dashboard.php" class="btn btn-secondary btn-sm w-100">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No Active Booking -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Anda belum menyewa kamar.</strong><br>
                        Silakan pilih kamar yang tersedia di <a href="../rooms.php" class="alert-link">Daftar Kamar</a>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
