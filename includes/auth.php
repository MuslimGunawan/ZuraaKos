<?php
// Authentication Functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Regenerate session ID to prevent session fixation
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}

// Security: Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /ZuraaaKos/login.php?timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_token']);
}

function getUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return $_SESSION;
}

function getUserRole() {
    return $_SESSION['role'] ?? 'pengunjung';
}

function isAdmin() {
    return getUserRole() === 'admin';
}

function isPenghuni() {
    return getUserRole() === 'penghuni';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /ZuraaaKos/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /ZuraaaKos/index.php');
        exit();
    }
}

function requirePenghuni() {
    requireLogin();
    if (!isPenghuni() && !isAdmin()) {
        header('Location: /ZuraaaKos/index.php');
        exit();
    }
}

function login($user) {
    // Security: Regenerate session ID on login
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    
    // Security: Create unique token for session validation
    $_SESSION['user_token'] = bin2hex(random_bytes(32));
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['last_activity'] = time();
}

// Security: Validate session integrity
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check if IP or User Agent changed (potential session hijacking)
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        logout();
        return false;
    }
    
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        logout();
        return false;
    }
    
    return true;
}

function logout() {
    session_destroy();
    header('Location: /ZuraaaKos/index.php');
    exit();
}
?>
