<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Kamar berhasil dihapus!";
    } else {
        $error = "Gagal menghapus kamar!";
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $room_number = sanitize($_POST['room_number']);
        $floor = intval($_POST['floor']);
        $type = sanitize($_POST['type']);
        $price = floatval($_POST['price']);
        $facilities = sanitize($_POST['facilities']);
        $status = sanitize($_POST['status']);
        $description = sanitize($_POST['description']);
        
        // Handle image upload
        $image_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['image']);
            if ($upload_result['success']) {
                $image_name = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }
        
        if (empty($error)) {
            if (isset($_POST['room_id']) && !empty($_POST['room_id'])) {
                // Update
                $room_id = intval($_POST['room_id']);
                if ($image_name) {
                    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, floor=?, type=?, price=?, facilities=?, status=?, description=?, image=? WHERE id=?");
                    $stmt->bind_param("sisdssssi", $room_number, $floor, $type, $price, $facilities, $status, $description, $image_name, $room_id);
                } else {
                    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, floor=?, type=?, price=?, facilities=?, status=?, description=? WHERE id=?");
                    $stmt->bind_param("sisdsssi", $room_number, $floor, $type, $price, $facilities, $status, $description, $room_id);
                }
                if ($stmt->execute()) {
                    $success = "Kamar berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate kamar!";
                }
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO rooms (room_number, floor, type, price, facilities, status, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisdsss", $room_number, $floor, $type, $price, $facilities, $status, $description, $image_name);
                if ($stmt->execute()) {
                    $success = "Kamar berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan kamar!";
                }
            }
        }
    }
}

// Get edit data
$edit_room = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_room = $stmt->get_result()->fetch_assoc();
}

// Get all rooms
$rooms_result = $conn->query("SELECT * FROM rooms ORDER BY floor, room_number");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kamar - Admin Zuraa Kos</title>
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
                    <a class="nav-link active" href="rooms.php">
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
                    <h2><i class="bi bi-door-open"></i> Kelola Kamar</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="resetForm()">
                        <i class="bi bi-plus-circle"></i> Tambah Kamar
                    </button>
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

                <!-- Rooms Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No Kamar</th>
                                        <th>Lantai</th>
                                        <th>Tipe</th>
                                        <th>Harga</th>
                                        <th>Fasilitas</th>
                                        <th>Status</th>
                                        <th>Gambar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rooms_result->num_rows > 0): ?>
                                        <?php while ($room = $rooms_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?= cleanOutput($room['room_number']) ?></strong></td>
                                            <td>Lantai <?= $room['floor'] ?></td>
                                            <td><span class="badge bg-info"><?= ucfirst($room['type']) ?></span></td>
                                            <td><?= formatRupiah($room['price']) ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= substr(cleanOutput($room['facilities']), 0, 30) ?>...
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch($room['status']) {
                                                    case 'available': $badge_class = 'success'; break;
                                                    case 'occupied': $badge_class = 'danger'; break;
                                                    case 'maintenance': $badge_class = 'warning'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?= $badge_class ?>">
                                                    <?= ucfirst($room['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($room['image']): ?>
                                                    <img src="../uploads/<?= cleanOutput($room['image']) ?>" alt="Room" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editRoom(<?= htmlspecialchars(json_encode($room), ENT_QUOTES) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete=<?= $room['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus kamar ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                                <p class="text-muted mt-2">Belum ada kamar</p>
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

    <!-- Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="room_id" id="room_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Kamar Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Kamar *</label>
                                <input type="text" class="form-control" name="room_number" id="room_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lantai *</label>
                                <select class="form-select" name="floor" id="floor" required>
                                    <option value="1">Lantai 1</option>
                                    <option value="2">Lantai 2</option>
                                    <option value="3">Lantai 3</option>
                                    <option value="4">Lantai 4</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Kamar *</label>
                                <select class="form-select" name="type" id="type" required>
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                    <option value="shared">Shared</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Bulan *</label>
                                <input type="number" class="form-control" name="price" id="price" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Fasilitas *</label>
                                <textarea class="form-control" name="facilities" id="facilities" rows="2" placeholder="Contoh: AC, Kasur, Lemari, Kamar Mandi Dalam" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="available">Available</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gambar Kamar</label>
                                <input type="file" class="form-control" name="image" id="image" accept="image/*">
                                <small class="text-muted">Max 5MB (JPG, PNG, GIF)</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Tambah Kamar Baru';
            document.getElementById('room_id').value = '';
            document.getElementById('room_number').value = '';
            document.getElementById('floor').value = '1';
            document.getElementById('type').value = 'single';
            document.getElementById('price').value = '';
            document.getElementById('facilities').value = '';
            document.getElementById('status').value = 'available';
            document.getElementById('image').value = '';
            document.getElementById('description').value = '';
        }

        function editRoom(room) {
            document.getElementById('modalTitle').textContent = 'Edit Kamar';
            document.getElementById('room_id').value = room.id;
            document.getElementById('room_number').value = room.room_number;
            document.getElementById('floor').value = room.floor;
            document.getElementById('type').value = room.type;
            document.getElementById('price').value = room.price;
            document.getElementById('facilities').value = room.facilities;
            document.getElementById('status').value = room.status;
            document.getElementById('description').value = room.description || '';
            
            var modal = new bootstrap.Modal(document.getElementById('roomModal'));
            modal.show();
        }

        <?php if ($edit_room): ?>
        window.onload = function() {
            editRoom(<?= json_encode($edit_room) ?>);
        };
        <?php endif; ?>
    </script>
</body>
</html>
