<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'kitchenkart';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Session configuration
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireSupplier() {
    requireLogin();
    if (getUserType() !== 'supplier') {
        header('Location: index.php');
        exit();
    }
}

function requireVendor() {
    requireLogin();
    if (getUserType() !== 'vendor') {
        header('Location: index.php');
        exit();
    }
}
?>