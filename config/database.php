<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kos_management');

class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        try {
            // Security: Use mysqli with error reporting disabled in production
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                error_log("Database connection failed: " . $this->conn->connect_error);
                throw new Exception("Koneksi database gagal. Silakan coba lagi nanti.");
            }
            
            // Security: Set charset to prevent SQL injection
            $this->conn->set_charset("utf8mb4");
            
            // Security: Set SQL mode
            $this->conn->query("SET sql_mode = 'STRICT_ALL_TABLES'");
            
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
        }
    }
    
    // Singleton pattern for better connection management
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Auto-close connection on object destruction
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    // Optional: Manual close if needed
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}
?>
