<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get available rooms with security
$db = Database::getInstance();
$conn = $db->getConnection();

// Security: Use prepared statement
$rooms_query = "SELECT id, room_number, floor, type, price, facilities, status, description, image 
                FROM rooms WHERE status = 'available' 
                ORDER BY RAND() LIMIT 6";
$rooms_result = $conn->query($rooms_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Zuraa Kos - Hunian nyaman, aman, dan terjangkau">
    <meta name="keywords" content="kos, boarding house, hunian, room rental">
    <meta name="author" content="Zuraa Kos Management">
    <title>Zuraa Kos - Sistem Manajemen Kos</title>
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
                        <a class="nav-link active" href="index.php">Beranda</a>
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

    <!-- Hero Section -->
    <section class="hero text-center position-relative overflow-hidden">
        <div class="hero-background"></div>
        <div class="container position-relative">
            <h1 class="fade-in display-3 fw-bold mb-3">Selamat Datang di Zuraa Kos</h1>
            <p class="lead mb-4">Hunian nyaman, aman, dan terjangkau untuk Anda</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="rooms.php" class="btn btn-light btn-lg px-5 shadow">
                    <i class="bi bi-search"></i> Cari Kamar
                </a>
                <a href="#gallery" class="btn btn-outline-light btn-lg px-5">
                    <i class="bi bi-images"></i> Lihat Gallery
                </a>
            </div>
        </div>
    </section>
    
    <!-- Gallery Section -->
    <section class="py-5 bg-light" id="gallery">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Galeri Kos Kami</h2>
                <p class="lead text-muted">Lihat foto-foto kos dan kamar kami</p>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="gallery-item">
                        <img src="Asset/kos-depan.jpg" alt="Tampak Depan Kos" class="img-fluid rounded shadow" onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=Tampak+Depan+Kos'">
                        <div class="gallery-overlay">
                            <h5>Tampak Depan</h5>
                            <p>Area parkir luas dan aman</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="gallery-item">
                        <img src="Asset/kamar-1.jpg" alt="Kamar Type Single" class="img-fluid rounded shadow" onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=Kamar+Single'">
                        <div class="gallery-overlay">
                            <h6>Kamar Single</h6>
                            <p>Nyaman & Bersih</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="gallery-item">
                        <img src="Asset/kamar-2.jpg" alt="Kamar Type Double" class="img-fluid rounded shadow" onerror="this.src='https://via.placeholder.com/300x400/764ba2/ffffff?text=Kamar+Double'">
                        <div class="gallery-overlay">
                            <h6>Interior Kamar</h6>
                            <p>Design Modern</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-md-4 mb-4">
                    <div class="card p-4">
                        <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Aman & Nyaman</h4>
                        <p class="text-muted">Lingkungan yang aman dengan fasilitas lengkap</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card p-4">
                        <i class="bi bi-wallet2 text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Harga Terjangkau</h4>
                        <p class="text-muted">Berbagai pilihan harga sesuai budget Anda</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card p-4">
                        <i class="bi bi-geo-alt text-danger" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Lokasi Strategis</h4>
                        <p class="text-muted">Dekat dengan kampus dan pusat kota</p>
                    </div>
                </div>
            </div>

            <!-- Available Rooms -->
            <div class="text-center mb-5">
                <h2 class="mb-3">Kamar Terbaru & Terpopuler</h2>
                <p class="text-muted">Pilihan kamar terbaik dengan fasilitas lengkap</p>
            </div>
            <div class="row">
                <?php if ($rooms_result->num_rows > 0): ?>
                    <?php $delay = 0; ?>
                    <?php while ($room = $rooms_result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                            <div class="card room-card shadow-sm h-100">
                                <div class="position-relative overflow-hidden">
                                    <?php if ($room['image'] && file_exists('uploads/' . $room['image'])): ?>
                                        <img src="uploads/<?= cleanOutput($room['image']) ?>" class="card-img-top room-image" alt="Kamar <?= cleanOutput($room['room_number']) ?>" loading="lazy">
                                    <?php elseif (file_exists('Asset/kamar-1.jpg')): ?>
                                        <img src="Asset/kamar-1.jpg" class="card-img-top room-image" alt="Kamar <?= cleanOutput($room['room_number']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/400x300/667eea/ffffff?text=Kamar+<?= cleanOutput($room['room_number']) ?>" class="card-img-top room-image" alt="Kamar <?= cleanOutput($room['room_number']) ?>" loading="lazy">
                                    <?php endif; ?>
                                    <div class="position-absolute top-0 end-0 m-3">
                                        <span class="badge bg-success shadow">
                                            <i class="bi bi-check-circle"></i> Tersedia
                                        </span>
                                    </div>
                                    <div class="position-absolute bottom-0 start-0 m-3">
                                        <span class="badge bg-primary shadow">Lantai <?= $room['floor'] ?></span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0">Kamar <?= $room['room_number'] ?></h5>
                                        <span class="badge bg-info"><?= ucfirst($room['type']) ?></span>
                                    </div>
                                    <p class="card-text text-muted small mb-3"><?= substr($room['description'], 0, 80) ?>...</p>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-half text-warning"></i>
                                            <span class="ms-1">(4.5)</span>
                                        </small>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <small class="text-muted d-block">Mulai dari</small>
                                                <h4 class="price-tag mb-0"><?= formatRupiah($room['price']) ?></h4>
                                                <small class="text-muted">/bulan</small>
                                            </div>
                                        </div>
                                        <a href="room_detail.php?id=<?= $room['id'] ?>" class="btn btn-primary w-100 btn-hover">
                                            <i class="bi bi-eye"></i> Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $delay += 100; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 5rem; color: #e0e0e0;"></i>
                        <h4 class="mt-3 text-muted">Tidak ada kamar tersedia saat ini</h4>
                        <p class="text-muted">Silakan cek kembali nanti</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($rooms_result->num_rows > 0): ?>
                <div class="text-center mt-5">
                    <a href="rooms.php" class="btn btn-outline-primary btn-lg px-5 shadow-sm">
                        Lihat Semua Kamar <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                    <p class="text-muted mt-3"><small>Masih ada <?= $rooms_result->num_rows > 6 ? 'lebih banyak' : '' ?> kamar menanti Anda!</small></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
