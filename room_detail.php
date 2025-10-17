<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: rooms.php');
    exit();
}

$room = $result->fetch_assoc();

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $start_date = sanitize($_POST['start_date']);
    $notes = sanitize($_POST['notes']);
    
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, start_date, monthly_price, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisds", $_SESSION['user_id'], $room_id, $start_date, $room['price'], $notes);
    
    if ($stmt->execute()) {
        $success = "Booking berhasil! Menunggu persetujuan admin.";
    } else {
        $error = "Gagal melakukan booking.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kamar - Zuraa Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-house-door-fill text-primary"></i>
                <strong>Zuraa Kos</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rooms.php">Kamar</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= $_SESSION['full_name'] ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard Admin</a></li>
                                <?php elseif (isPenghuni()): ?>
                                    <li><a class="dropdown-item" href="penghuni/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary" href="register.php">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="rooms.php">Kamar</a></li>
                <li class="breadcrumb-item active">Kamar <?= $room['room_number'] ?></li>
            </ol>
        </nav>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-7">
                <div class="card mb-4">
                    <?php if ($room['image']): ?>
                        <img src="uploads/<?= $room['image'] ?>" class="card-img-top" style="height: 400px; object-fit: cover;" alt="<?= $room['room_number'] ?>">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/800x400?text=Kamar+<?= $room['room_number'] ?>" class="card-img-top" alt="<?= $room['room_number'] ?>">
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-3">Deskripsi</h3>
                        <p><?= nl2br($room['description']) ?></p>

                        <h5 class="mt-4 mb-3">Fasilitas</h5>
                        <ul class="list-unstyled">
                            <?php 
                            $facilities = explode(',', $room['facilities']);
                            foreach ($facilities as $facility): 
                            ?>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <?= trim($facility) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h4>Kamar <?= $room['room_number'] ?></h4>
                        <p class="text-muted">
                            <i class="bi bi-building"></i> Lantai <?= $room['floor'] ?> | 
                            <span class="badge bg-info"><?= ucfirst($room['type']) ?></span>
                        </p>

                        <div class="d-flex align-items-center mb-3">
                            <div class="status-indicator <?= $room['status'] ?>"></div>
                            <span class="text-capitalize"><?= $room['status'] ?></span>
                        </div>

                        <h3 class="price-tag mb-4"><?= formatRupiah($room['price']) ?>/bulan</h3>

                        <?php if ($room['status'] === 'available'): ?>
                            <?php if (isLoggedIn()): ?>
                                <button type="button" class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#bookingModal">
                                    <i class="bi bi-calendar-check"></i> Booking Sekarang
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Login untuk Booking
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg w-100" disabled>
                                Kamar Tidak Tersedia
                            </button>
                        <?php endif; ?>

                        <a href="rooms.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Kamar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Kamar <?= $room['room_number'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Booking akan menunggu persetujuan admin.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-house-door-fill"></i> Zuraa Kos</h5>
                    <p class="text-light mb-3">Sistem manajemen kos yang modern dan terpercaya untuk kenyamanan Anda.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Hubungi Kami</h5>
                    <ul class="list-unstyled footer-contact">
                        <li class="mb-2">
                            <a href="tel:+6281234567890" class="text-light text-decoration-none">
                                <i class="bi bi-telephone-fill"></i> +62 812-3456-7890
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://wa.me/6281234567890" target="_blank" class="text-light text-decoration-none">
                                <i class="bi bi-whatsapp"></i> WhatsApp Kami
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="mailto:info@zuraakos.com" class="text-light text-decoration-none">
                                <i class="bi bi-envelope-fill"></i> info@zuraakos.com
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://maps.google.com/?q=Jl.+Contoh+No.+123" target="_blank" class="text-light text-decoration-none">
                                <i class="bi bi-geo-alt-fill"></i> Jl. Contoh No. 123, Jakarta
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Link Cepat</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="index.php" class="text-light text-decoration-none"><i class="bi bi-chevron-right"></i> Beranda</a></li>
                        <li class="mb-2"><a href="rooms.php" class="text-light text-decoration-none"><i class="bi bi-chevron-right"></i> Daftar Kamar</a></li>
                        <li class="mb-2"><a href="login.php" class="text-light text-decoration-none"><i class="bi bi-chevron-right"></i> Login</a></li>
                        <li class="mb-2"><a href="register.php" class="text-light text-decoration-none"><i class="bi bi-chevron-right"></i> Daftar</a></li>
                    </ul>
                </div>
            </div>
            <hr class="bg-light opacity-25 my-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-2">
                    <p class="mb-0 text-light">&copy; 2025 Zuraa Kos. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-light">
                        Made with <i class="bi bi-heart-fill text-danger"></i> by Zuraa Team
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
