<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Filter with security
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$price_filter = isset($_GET['price']) ? sanitize($_GET['price']) : '';

$query = "SELECT id, room_number, floor, type, price, facilities, status, description, image 
          FROM rooms WHERE status = 'available'";
$params = [];
$types = '';

if ($type_filter && in_array($type_filter, ['single', 'double', 'shared'])) {
    $query .= " AND type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if ($price_filter) {
    if ($price_filter == 'low') {
        $query .= " AND price < 1000000";
    } elseif ($price_filter == 'medium') {
        $query .= " AND price BETWEEN 1000000 AND 1500000";
    } elseif ($price_filter == 'high') {
        $query .= " AND price > 1500000";
    }
}

$query .= " ORDER BY floor, room_number";

// Security: Use prepared statement
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rooms_result = $stmt->get_result();
} else {
    $rooms_result = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Daftar kamar kos tersedia - Zuraa Kos">
    <title>Daftar Kamar - Zuraa Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/security.css">
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
                        <a class="nav-link active" href="rooms.php">Kamar</a>
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
        <!-- Header Section -->
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold mb-3">Temukan Kamar Impian Anda</h1>
            <p class="lead text-muted">Lebih dari <?= $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'] ?> kamar tersedia untuk Anda</p>
        </div>
        
        <!-- Filter Card -->
        <div class="card shadow-lg border-0 mb-5 filter-card">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Pencarian</h5>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label fw-bold">
                                <i class="bi bi-door-open"></i> Tipe Kamar
                            </label>
                            <select name="type" id="type" class="form-select form-select-lg">
                                <option value="">🏠 Semua Tipe</option>
                                <option value="single" <?= $type_filter == 'single' ? 'selected' : '' ?>>🛏️ Single Room</option>
                                <option value="double" <?= $type_filter == 'double' ? 'selected' : '' ?>>🛏️🛏️ Double Room</option>
                                <option value="shared" <?= $type_filter == 'shared' ? 'selected' : '' ?>>👥 Shared Room</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="price" class="form-label fw-bold">
                                <i class="bi bi-cash"></i> Rentang Harga
                            </label>
                            <select name="price" id="price" class="form-select form-select-lg">
                                <option value="">💰 Semua Harga</option>
                                <option value="low" <?= $price_filter == 'low' ? 'selected' : '' ?>>💵 < Rp 1.000.000</option>
                                <option value="medium" <?= $price_filter == 'medium' ? 'selected' : '' ?>>💴 Rp 1 - 1,5 Juta</option>
                                <option value="high" <?= $price_filter == 'high' ? 'selected' : '' ?>>💶 > Rp 1.500.000</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="bi bi-search"></i> Cari Kamar
                            </button>
                            <?php if ($type_filter || $price_filter): ?>
                            <a href="rooms.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Reset Filter
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Info -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">
                    <i class="bi bi-grid-3x3-gap"></i> 
                    <?php if ($type_filter || $price_filter): ?>
                        Hasil Pencarian
                    <?php else: ?>
                        Semua Kamar
                    <?php endif; ?>
                </h4>
                <p class="text-muted mb-0"><?= $rooms_result->num_rows ?> kamar ditemukan</p>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active">
                    <i class="bi bi-grid-3x3"></i>
                </button>
                <button type="button" class="btn btn-outline-primary">
                    <i class="bi bi-list-ul"></i>
                </button>
            </div>
        </div>

        <!-- Rooms Grid -->
        <div class="row">
            <?php if ($rooms_result->num_rows > 0): ?>
                <?php while ($room = $rooms_result->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card room-card-enhanced h-100 shadow-hover">
                            <div class="position-relative overflow-hidden">
                                <?php if ($room['image'] && file_exists('uploads/' . $room['image'])): ?>
                                    <img src="uploads/<?= cleanOutput($room['image']) ?>" class="card-img-top room-image-enhanced" alt="Kamar <?= cleanOutput($room['room_number']) ?>" loading="lazy">
                                <?php elseif (file_exists('Asset/kamar-1.jpg')): ?>
                                    <img src="Asset/kamar-1.jpg" class="card-img-top room-image-enhanced" alt="Kamar <?= cleanOutput($room['room_number']) ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/400x300/667eea/ffffff?text=Kamar+<?= cleanOutput($room['room_number']) ?>" class="card-img-top room-image-enhanced" alt="Kamar <?= cleanOutput($room['room_number']) ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="room-overlay">
                                    <a href="room_detail.php?id=<?= $room['id'] ?>" class="btn btn-light btn-sm">
                                        <i class="bi bi-eye"></i> Lihat Detail
                                    </a>
                                </div>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-success shadow-sm">
                                        <i class="bi bi-check-circle"></i> Tersedia
                                    </span>
                                </div>
                                <div class="position-absolute top-0 start-0 m-3">
                                    <span class="badge bg-dark bg-opacity-75">Lantai <?= $room['floor'] ?></span>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0 fw-bold">Kamar <?= $room['room_number'] ?></h5>
                                    <span class="badge bg-gradient-info"><?= ucfirst($room['type']) ?></span>
                                </div>
                                
                                <p class="card-text text-muted small mb-3"><?= substr($room['description'], 0, 85) ?>...</p>
                                
                                <div class="facilities-preview mb-3">
                                    <?php 
                                    $facilities = explode(',', $room['facilities']);
                                    $facility_icons = ['AC' => 'snow', 'Kasur' => 'moon', 'Lemari' => 'cabinet', 'Kamar Mandi' => 'droplet'];
                                    $count = 0;
                                    foreach ($facilities as $facility): 
                                        if ($count >= 3) break;
                                        $facility = trim($facility);
                                    ?>
                                        <span class="badge bg-light text-dark me-1 mb-1">
                                            <i class="bi bi-check2"></i> <?= $facility ?>
                                        </span>
                                    <?php 
                                        $count++;
                                    endforeach; 
                                    if (count($facilities) > 3): ?>
                                        <span class="badge bg-light text-dark">+<?= count($facilities) - 3 ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <small class="text-muted d-block">Harga</small>
                                            <h4 class="price-tag-enhanced mb-0"><?= formatRupiah($room['price']) ?></h4>
                                            <small class="text-muted">/bulan</small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-warning">
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-fill"></i>
                                                <i class="bi bi-star-half"></i>
                                            </small>
                                            <br>
                                            <small class="text-muted">4.5/5</small>
                                        </div>
                                    </div>
                                    <a href="room_detail.php?id=<?= $room['id'] ?>" class="btn btn-primary w-100 btn-hover-effect">
                                        <i class="bi bi-calendar-check"></i> Booking Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-search" style="font-size: 5rem; color: #e0e0e0;"></i>
                            <h3 class="mt-4 text-muted">Tidak Ada Kamar Ditemukan</h3>
                            <p class="text-muted mb-4">Tidak ada kamar yang sesuai dengan filter pencarian Anda</p>
                            <a href="rooms.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-arrow-clockwise"></i> Lihat Semua Kamar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
