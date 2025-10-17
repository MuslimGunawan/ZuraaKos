<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requirePenghuni();

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Handle Upload Payment Proof
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_payment'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $booking_id = intval($_POST['booking_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize($_POST['payment_method']);
        $payment_date = sanitize($_POST['payment_date']);
        
        // Debug: Check if file was uploaded
        if (!isset($_FILES['proof_image'])) {
            $error = "File tidak ditemukan! Pastikan form memiliki enctype='multipart/form-data'";
        } elseif ($_FILES['proof_image']['error'] !== 0) {
            $error_codes = [
                1 => 'File terlalu besar (melebihi upload_max_filesize)',
                2 => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
                3 => 'File hanya sebagian terupload',
                4 => 'Tidak ada file yang diupload',
                6 => 'Folder temporary tidak ditemukan',
                7 => 'Gagal menulis file ke disk',
                8 => 'Upload dihentikan oleh ekstensi PHP'
            ];
            $error = "Error upload: " . ($error_codes[$_FILES['proof_image']['error']] ?? 'Unknown error');
        } else {
            // Upload proof image
            $uploaded_image = uploadImage($_FILES['proof_image']);
            
            if ($uploaded_image['success']) {
                $payment_proof = $uploaded_image['filename'];
                
                $stmt = $conn->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_date, payment_method, payment_proof, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iidsss", $booking_id, $user_id, $amount, $payment_date, $payment_method, $payment_proof);
                
                if ($stmt->execute()) {
                    $success = "Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.";
                    // Refresh to clear POST data
                    header("Location: payments.php?success=1");
                    exit();
                } else {
                    $error = "Gagal menyimpan data pembayaran: " . $stmt->error;
                }
            } else {
                $error = $uploaded_image['message'];
            }
        }
    }
}

// Show success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.";
}

// Get booking that needs payment (approved status only)
// Note: 'active' status means payment already verified, no need to upload again
$booking_query = "SELECT b.*, r.room_number 
                  FROM bookings b 
                  JOIN rooms r ON b.room_id = r.id 
                  WHERE b.user_id = ? AND b.status = 'approved'
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
                   ORDER BY p.created_at DESC";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Zuraa Kos</title>
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
                    <a class="nav-link active" href="payments.php">
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
                    <h2><i class="bi bi-cash-coin"></i> Pembayaran</h2>
                    <?php if ($active_booking): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="bi bi-upload"></i> Upload Bukti Bayar
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($active_booking): ?>
                    <!-- Payment Info for Approved Booking -->
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Booking Disetujui - Perlu Upload Bukti Bayar!</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-info-circle"></i> <strong>Booking Anda sudah DISETUJUI admin!</strong><br>
                                Silakan upload bukti pembayaran agar status menjadi <strong>ACTIVE</strong> dan Anda bisa menempati kamar.
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Kamar:</strong></p>
                                    <p class="fs-5">Kamar <?= $active_booking['room_number'] ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Harga Sewa/Bulan:</strong></p>
                                    <p class="text-success fs-4 fw-bold"><?= formatRupiah($active_booking['monthly_price']) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Status Booking:</strong></p>
                                    <span class="badge bg-warning text-dark">Approved - Perlu Bayar</span>
                                </div>
                            </div>
                            <hr>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> <strong>Cara Pembayaran:</strong><br>
                                1. Transfer ke rekening: <strong>BCA 1234567890</strong> a.n. Zuraa Kos<br>
                                2. Upload bukti transfer melalui tombol "Upload Bukti Bayar" di kanan atas<br>
                                3. Tunggu verifikasi dari admin (1x24 jam)<br>
                                4. Setelah diverifikasi, status akan otomatis menjadi <strong>ACTIVE</strong>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No Approved Booking -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>Tidak ada booking yang perlu pembayaran.</strong><br>
                        - Jika booking sudah <strong>ACTIVE</strong>, Anda sudah bisa melihat riwayat pembayaran di bawah.<br>
                        - Jika booking masih <strong>PENDING</strong>, tunggu persetujuan admin terlebih dahulu.
                    </div>
                <?php endif; ?>

                <!-- Payment History -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($payments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal Bayar</th>
                                        <th>Kamar</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Status</th>
                                        <th>Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= formatDate($payment['payment_date']) ?></td>
                                        <td>Kamar <?= $payment['room_number'] ?></td>
                                        <td class="fw-bold text-success"><?= formatRupiah($payment['amount']) ?></td>
                                        <td><?= ucfirst($payment['payment_method']) ?></td>
                                        <td>
                                            <?php
                                            if ($payment['status'] === 'verified') {
                                                echo '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>';
                                            } elseif ($payment['status'] === 'rejected') {
                                                echo '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Ditolak - Upload Ulang!</span>';
                                            } else {
                                                echo '<span class="badge bg-warning"><i class="bi bi-clock"></i> Pending</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($payment['payment_proof']): ?>
                                            <button class="btn btn-sm btn-info" onclick="viewProof('../uploads/<?= $payment['payment_proof'] ?>')">
                                                <i class="bi bi-image"></i> Lihat
                                            </button>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Belum ada riwayat pembayaran</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Bukti Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="upload_payment" value="1">
                        <?php if ($active_booking): ?>
                        <input type="hidden" name="booking_id" value="<?= $active_booking['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" 
                                   value="<?= $active_booking['monthly_price'] ?>" required>
                            <small class="text-muted">Harga sewa: <?= formatRupiah($active_booking['monthly_price']) ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Cash</option>
                                <option value="e-wallet">E-Wallet</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bukti Pembayaran <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="proof_image" 
                                   accept="image/jpeg,image/jpg,image/png" required>
                            <small class="text-muted">Format: JPG, PNG (Max 5MB)</small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Proof Image Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="proofImage" src="" class="img-fluid" alt="Bukti Pembayaran">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProof(imagePath) {
            document.getElementById('proofImage').src = imagePath;
            new bootstrap.Modal(document.getElementById('proofModal')).show();
        }
    </script>
</body>
</html>
