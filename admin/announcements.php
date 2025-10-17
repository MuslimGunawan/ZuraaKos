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
    $announcement_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $announcement_id);
    if ($stmt->execute()) {
        $success = "Pengumuman berhasil dihapus!";
    } else {
        $error = "Gagal menghapus pengumuman!";
    }
}

// Handle Toggle Publish
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $announcement_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE announcements SET is_published = NOT is_published WHERE id = ?");
    $stmt->bind_param("i", $announcement_id);
    if ($stmt->execute()) {
        $success = "Status publish berhasil diubah!";
    } else {
        $error = "Gagal mengubah status!";
    }
}

// Handle Add/Edit Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $category = sanitize($_POST['category']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $created_by = $_SESSION['user_id'];
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing announcement
            $announcement_id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, category = ?, is_published = ? WHERE id = ?");
            $stmt->bind_param("sssii", $title, $content, $category, $is_published, $announcement_id);
            
            if ($stmt->execute()) {
                $success = "Pengumuman berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate pengumuman!";
            }
        } else {
            // Add new announcement
            $stmt = $conn->prepare("INSERT INTO announcements (title, content, category, is_published, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $title, $content, $category, $is_published, $created_by);
            
            if ($stmt->execute()) {
                $success = "Pengumuman baru berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan pengumuman!";
            }
        }
    }
}

// Get all announcements
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$query = "SELECT a.*, u.username 
          FROM announcements a 
          JOIN users u ON a.created_by = u.id";

if ($filter === 'published') {
    $query .= " WHERE a.is_published = 1";
} elseif ($filter === 'draft') {
    $query .= " WHERE a.is_published = 0";
} elseif ($filter !== 'all' && in_array($filter, ['info', 'important', 'maintenance', 'event'])) {
    $query .= " WHERE a.category = '$filter'";
}

$query .= " ORDER BY a.created_at DESC";
$announcements = $conn->query($query);

// Get statistics
$total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];
$published = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE is_published = 1")->fetch_assoc()['count'];
$draft = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE is_published = 0")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman - Admin Zuraa Kos</title>
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
                    <a class="nav-link" href="rooms.php">
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
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-megaphone"></i> Kelola Pengumuman</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#announcementModal" onclick="resetForm()">
                        <i class="bi bi-plus-circle"></i> Buat Pengumuman
                    </button>
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

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card primary">
                            <div class="card-body">
                                <h3 class="card-title"><?= $total_announcements ?></h3>
                                <p class="card-text">Total Pengumuman</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card success">
                            <div class="card-body">
                                <h3 class="card-title"><?= $published ?></h3>
                                <p class="card-text">Published</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card warning">
                            <div class="card-body">
                                <h3 class="card-title"><?= $draft ?></h3>
                                <p class="card-text">Draft</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="btn-group" role="group">
                                    <a href="announcements.php?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                                        <i class="bi bi-funnel"></i> Semua (<?= $total_announcements ?>)
                                    </a>
                                    <a href="announcements.php?filter=published" class="btn btn-outline-success <?= $filter === 'published' ? 'active' : '' ?>">
                                        <i class="bi bi-check-circle"></i> Published (<?= $published ?>)
                                    </a>
                                    <a href="announcements.php?filter=draft" class="btn btn-outline-warning <?= $filter === 'draft' ? 'active' : '' ?>">
                                        <i class="bi bi-file-earmark"></i> Draft (<?= $draft ?>)
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="btn-group" role="group">
                                    <a href="announcements.php?filter=info" class="btn btn-outline-primary <?= $filter === 'info' ? 'active' : '' ?>">
                                        <i class="bi bi-info-circle"></i> Info
                                    </a>
                                    <a href="announcements.php?filter=important" class="btn btn-outline-danger <?= $filter === 'important' ? 'active' : '' ?>">
                                        <i class="bi bi-exclamation-triangle"></i> Important
                                    </a>
                                    <a href="announcements.php?filter=maintenance" class="btn btn-outline-warning <?= $filter === 'maintenance' ? 'active' : '' ?>">
                                        <i class="bi bi-tools"></i> Maintenance
                                    </a>
                                    <a href="announcements.php?filter=event" class="btn btn-outline-success <?= $filter === 'event' ? 'active' : '' ?>">
                                        <i class="bi bi-calendar-event"></i> Event
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements List -->
                <div class="row">
                    <?php while ($announcement = $announcements->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 <?= $announcement['is_published'] ? 'border-success' : 'border-warning' ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                            $category_badges = [
                                                'info' => '<span class="badge bg-primary"><i class="bi bi-info-circle"></i> Info</span>',
                                                'important' => '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Penting</span>',
                                                'maintenance' => '<span class="badge bg-warning"><i class="bi bi-tools"></i> Maintenance</span>',
                                                'event' => '<span class="badge bg-success"><i class="bi bi-calendar-event"></i> Event</span>'
                                            ];
                                            echo $category_badges[$announcement['category']] ?? '';
                                        ?>
                                    </div>
                                    <div>
                                        <?php if ($announcement['is_published']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><i class="bi bi-file-earmark"></i> Draft</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= cleanOutput($announcement['title']) ?></h5>
                                    <p class="card-text">
                                        <?= nl2br(cleanOutput(substr($announcement['content'], 0, 150))) ?>
                                        <?= strlen($announcement['content']) > 150 ? '...' : '' ?>
                                    </p>
                                    <hr>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?= cleanOutput($announcement['username']) ?> • 
                                        <i class="bi bi-calendar"></i> <?= formatDate($announcement['created_at']) ?>
                                    </small>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-sm btn-info" onclick='viewAnnouncement(<?= json_encode($announcement) ?>)'>
                                            <i class="bi bi-eye"></i> Lihat
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick='editAnnouncement(<?= json_encode($announcement) ?>)'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <a href="?toggle=1&id=<?= $announcement['id'] ?>" class="btn btn-sm btn-<?= $announcement['is_published'] ? 'warning' : 'success' ?>">
                                            <i class="bi bi-<?= $announcement['is_published'] ? 'eye-slash' : 'eye' ?>"></i>
                                            <?= $announcement['is_published'] ? 'Unpublish' : 'Publish' ?>
                                        </a>
                                        <a href="?delete=<?= $announcement['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus pengumuman ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcement Modal (Add/Edit) -->
    <div class="modal fade" id="announcementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Buat Pengumuman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" id="announcementId">
                        
                        <div class="mb-3">
                            <label class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="title" required 
                                   placeholder="Contoh: Pembayaran Bulanan - Februari 2025">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" id="category" required>
                                <option value="info">Info - Informasi umum</option>
                                <option value="important">Penting - Pengumuman penting</option>
                                <option value="maintenance">Maintenance - Perbaikan/pemeliharaan</option>
                                <option value="event">Event - Acara/kegiatan</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konten <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" id="content" rows="8" required 
                                      placeholder="Tulis isi pengumuman..."></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Tips: Gunakan paragraf yang jelas dan ringkas.
                            </small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" id="is_published" checked>
                            <label class="form-check-label" for="is_published">
                                <strong>Publish sekarang</strong>
                                <small class="text-muted d-block">Jika tidak dicentang, akan disimpan sebagai draft</small>
                            </label>
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

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="viewTitle"></h5>
                        <small class="text-muted" id="viewMeta"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewCategory" class="mb-3"></div>
                    <div id="viewContent" class="border p-3 rounded bg-light" style="white-space: pre-line;"></div>
                </div>
                <div class="modal-footer">
                    <span id="viewStatus"></span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Buat Pengumuman';
            document.getElementById('announcementId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('category').value = 'info';
            document.getElementById('content').value = '';
            document.getElementById('is_published').checked = true;
        }

        function editAnnouncement(announcement) {
            document.getElementById('modalTitle').textContent = 'Edit Pengumuman';
            document.getElementById('announcementId').value = announcement.id;
            document.getElementById('title').value = announcement.title;
            document.getElementById('category').value = announcement.category;
            document.getElementById('content').value = announcement.content;
            document.getElementById('is_published').checked = announcement.is_published == 1;
            
            new bootstrap.Modal(document.getElementById('announcementModal')).show();
        }

        function viewAnnouncement(announcement) {
            document.getElementById('viewTitle').textContent = announcement.title;
            document.getElementById('viewMeta').textContent = 
                'Oleh ' + announcement.username + ' • ' + 
                new Date(announcement.created_at).toLocaleDateString('id-ID', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });
            
            const categoryBadges = {
                'info': '<span class="badge bg-primary"><i class="bi bi-info-circle"></i> Info</span>',
                'important': '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Penting</span>',
                'maintenance': '<span class="badge bg-warning"><i class="bi bi-tools"></i> Maintenance</span>',
                'event': '<span class="badge bg-success"><i class="bi bi-calendar-event"></i> Event</span>'
            };
            document.getElementById('viewCategory').innerHTML = categoryBadges[announcement.category];
            
            document.getElementById('viewContent').textContent = announcement.content;
            
            const statusBadge = announcement.is_published == 1 
                ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Published</span>'
                : '<span class="badge bg-warning"><i class="bi bi-file-earmark"></i> Draft</span>';
            document.getElementById('viewStatus').innerHTML = statusBadge;
            
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        }
    </script>
</body>
</html>
