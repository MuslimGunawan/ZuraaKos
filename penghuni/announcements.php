<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requirePenghuni();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get published announcements
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT a.*, u.username 
          FROM announcements a 
          JOIN users u ON a.created_by = u.id 
          WHERE a.is_active = 1";

if ($filter !== 'all' && in_array($filter, ['all', 'penghuni', 'admin'])) {
    if ($filter !== 'all') {
        $query .= " AND (a.target_role = '$filter' OR a.target_role = 'all')";
    }
}

$query .= " ORDER BY a.created_at DESC";
$announcements = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Zuraa Kos</title>
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
                    <a class="nav-link" href="complaints.php">
                        <i class="bi bi-chat-left-text"></i> Keluhan
                    </a>
                    <a class="nav-link active" href="announcements.php">
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
                <h2 class="mb-4"><i class="bi bi-megaphone"></i> Pengumuman</h2>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="?" class="btn btn-sm btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">
                                <i class="bi bi-list"></i> Semua
                            </a>
                            <a href="?filter=penghuni" class="btn btn-sm btn-<?= $filter === 'penghuni' ? 'info' : 'outline-info' ?>">
                                <i class="bi bi-people"></i> Untuk Penghuni
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Announcements Grid -->
                <div class="row">
                    <?php if ($announcements->num_rows > 0): ?>
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-megaphone-fill"></i> <?= cleanOutput($announcement['title']) ?></h5>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-tag"></i> <?= ucfirst($announcement['target_role']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="announcement-content">
                                        <?= nl2br(cleanOutput($announcement['content'])) ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-light text-muted">
                                    <small>
                                        <i class="bi bi-person-badge"></i> Diposting oleh: <strong><?= cleanOutput($announcement['username']) ?></strong>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-calendar-event"></i> <?= formatDate($announcement['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-megaphone" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">Belum ada pengumuman</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
