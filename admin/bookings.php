<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle Approve/Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $booking_id = intval($_GET['id']);
    
    if ($action === 'approve') {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update booking status
            $stmt = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            
            // Update room status to occupied
            $stmt = $conn->prepare("UPDATE rooms r 
                                   INNER JOIN bookings b ON r.id = b.room_id 
                                   SET r.status = 'occupied' 
                                   WHERE b.id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            
            $conn->commit();
            $success = "Booking disetujui! Penghuni perlu upload bukti pembayaran. Setelah payment diverifikasi, status akan otomatis menjadi Active.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal menyetujui booking!";
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            $success = "Booking ditolak!";
        } else {
            $error = "Gagal menolak booking!";
        }
    } elseif ($action === 'kick') {
        // Handle kick penghuni
        $reason = isset($_GET['reason']) ? sanitize($_GET['reason']) : 'Tidak ada keterangan';
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update booking status to terminated
            $stmt = $conn->prepare("UPDATE bookings SET status = 'terminated', notes = CONCAT(IFNULL(notes, ''), '\n[KICKED] ', ?, ' - ', NOW()) WHERE id = ?");
            $stmt->bind_param("si", $reason, $booking_id);
            $stmt->execute();
            
            // Free up the room
            $stmt = $conn->prepare("UPDATE rooms r 
                                   INNER JOIN bookings b ON r.id = b.room_id 
                                   SET r.status = 'available' 
                                   WHERE b.id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            
            $conn->commit();
            $success = "Penghuni berhasil dikeluarkan dari kamar!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal mengeluarkan penghuni!";
        }
    }
}

// Get all bookings with notes
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT b.*, u.full_name, u.phone, u.email, r.room_number, r.type 
          FROM bookings b 
          JOIN users u ON b.user_id = u.id 
          JOIN rooms r ON b.room_id = r.id";

if ($filter !== 'all') {
    $query .= " WHERE b.status = '$filter'";
}

$query .= " ORDER BY b.created_at DESC";
$bookings = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Admin Zuraa Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/security.css">
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
                    <a class="nav-link active" href="bookings.php">
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
                    <h2><i class="bi bi-calendar-check"></i> Kelola Booking</h2>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">
                                <i class="bi bi-list"></i> Semua
                            </a>
                            <a href="?filter=pending" class="btn btn-<?= $filter === 'pending' ? 'warning' : 'outline-warning' ?>">
                                <i class="bi bi-clock"></i> Pending
                            </a>
                            <a href="?filter=approved" class="btn btn-<?= $filter === 'approved' ? 'success' : 'outline-success' ?>">
                                <i class="bi bi-check-circle"></i> Approved
                            </a>
                            <a href="?filter=active" class="btn btn-<?= $filter === 'active' ? 'primary' : 'outline-primary' ?>">
                                <i class="bi bi-house-check"></i> Active
                            </a>
                            <a href="?filter=rejected" class="btn btn-<?= $filter === 'rejected' ? 'danger' : 'outline-danger' ?>">
                                <i class="bi bi-x-circle"></i> Rejected
                            </a>
                            <a href="?filter=completed" class="btn btn-<?= $filter === 'completed' ? 'info' : 'outline-info' ?>">
                                <i class="bi bi-check-all"></i> Completed
                            </a>
                            <a href="?filter=terminated" class="btn btn-<?= $filter === 'terminated' ? 'secondary' : 'outline-secondary' ?>">
                                <i class="bi bi-door-closed"></i> Terminated
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Penghuni</th>
                                        <th>Kontak</th>
                                        <th>Kamar</th>
                                        <th>Tipe</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bookings->num_rows > 0): ?>
                                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= $booking['id'] ?></strong></td>
                                            <td>
                                                <strong><?= cleanOutput($booking['full_name']) ?></strong><br>
                                                <small class="text-muted"><?= cleanOutput($booking['email']) ?></small>
                                            </td>
                                            <td>
                                                <a href="tel:<?= cleanOutput($booking['phone']) ?>" class="text-decoration-none">
                                                    <i class="bi bi-telephone"></i> <?= cleanOutput($booking['phone']) ?>
                                                </a>
                                            </td>
                                            <td><strong><?= cleanOutput($booking['room_number']) ?></strong></td>
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
                                                }
                                                ?>
                                                <span class="badge bg-<?= $badge_class ?>">
                                                    <?= ucfirst($booking['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <a href="?action=approve&id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       onclick="return confirm('Setujui booking ini?')">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </a>
                                                    <a href="?action=reject&id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Tolak booking ini?')">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </a>
                                                <?php elseif ($booking['status'] === 'approved' || $booking['status'] === 'active'): ?>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $booking['id'] ?>">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#kickModal<?= $booking['id'] ?>">
                                                        <i class="bi bi-door-open"></i> Kick
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $booking['id'] ?>">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                                <p class="text-muted mt-2">Belum ada booking</p>
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

    <!-- Modals - Outside the table -->
    <?php if ($bookings->num_rows > 0): ?>
        <?php
        $bookings->data_seek(0);
        while ($booking = $bookings->fetch_assoc()): 
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
                                <td><strong>Email:</strong></td>
                                <td><?= cleanOutput($booking['email']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telepon:</strong></td>
                                <td><?= cleanOutput($booking['phone']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kamar:</strong></td>
                                <td><?= cleanOutput($booking['room_number']) ?> (<?= ucfirst($booking['type']) ?>)</td>
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
                                    <span class="badge bg-<?= $badge_class ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dibuat:</strong></td>
                                <td><?= formatDate($booking['created_at']) ?></td>
                            </tr>
                            <?php if (!empty($booking['notes'])): ?>
                            <tr>
                                <td><strong>Catatan:</strong></td>
                                <td><?= nl2br(cleanOutput($booking['notes'])) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                        
                        <?php if ($booking['status'] === 'approved'): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Menunggu Upload Pembayaran</strong><br>
                            Penghuni perlu upload bukti pembayaran. Setelah diverifikasi, status akan otomatis menjadi "Active".
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kick Modal -->
        <?php if ($booking['status'] === 'approved' || $booking['status'] === 'active'): ?>
        <div class="modal fade" id="kickModal<?= $booking['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-door-open"></i> Kick Penghuni</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="" method="GET" onsubmit="return confirm('Yakin ingin mengeluarkan penghuni ini dari kamar?')">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="kick">
                            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Anda akan mengeluarkan <strong><?= cleanOutput($booking['full_name']) ?></strong> 
                                dari kamar <strong><?= cleanOutput($booking['room_number']) ?></strong>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Alasan Kick:</strong></label>
                                <textarea name="reason" class="form-control" rows="3" required placeholder="Masukkan alasan mengeluarkan penghuni..."></textarea>
                            </div>
                            
                            <p class="text-muted small">
                                <i class="bi bi-info-circle"></i> 
                                Booking akan diubah ke status "Terminated" dan kamar akan tersedia kembali
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-door-open"></i> Kick Penghuni
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endwhile; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
