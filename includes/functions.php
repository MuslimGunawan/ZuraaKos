<?php
// Helper Functions

// Security: Enhanced sanitization
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = strip_tags($data);
    return $data;
}

// Security: CSRF Token Generation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Security: CSRF Token Validation
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Security: SQL Injection Prevention Helper
function escapeSQL($conn, $data) {
    return $conn->real_escape_string($data);
}

// Security: XSS Prevention
function cleanOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function formatDate($date) {
    return date('d F Y', strtotime($date));
}

function uploadImage($file, $targetDir = 'uploads/') {
    $targetDir = __DIR__ . '/../' . $targetDir;
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Security: Validate file upload
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Parameter file tidak valid'];
    }
    
    // Security: Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'Tidak ada file yang diupload'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File terlalu besar'];
        default:
            return ['success' => false, 'message' => 'Error upload file'];
    }
    
    // Security: Check file size (max 5MB)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'message' => 'File terlalu besar (max 5MB)'];
    }
    
    // Security: Verify MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'message' => 'File harus berupa gambar (JPG, PNG, GIF)'];
    }
    
    // Security: Verify actual image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'File bukan gambar yang valid'];
    }
    
    // Security: Get safe file extension
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['success' => false, 'message' => 'Ekstensi file tidak diizinkan'];
    }
    
    // Security: Generate random filename
    $newFileName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFileName;
    
    // Security: Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Security: Set file permissions
        chmod($targetFile, 0644);
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
}

function getStatusBadge($status) {
    $badges = [
        'available' => 'success',
        'occupied' => 'warning',
        'maintenance' => 'danger',
        'pending' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'completed' => 'secondary',
        'open' => 'warning',
        'in_progress' => 'info',
        'resolved' => 'success',
        'closed' => 'secondary',
        'verified' => 'success'
    ];
    
    return $badges[$status] ?? 'secondary';
}
?>
