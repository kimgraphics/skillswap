<?php
require_once 'database.php';
require_once 'functions.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function register($userData) {
        // Validate input
        if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Check if user exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$userData['username'], $userData['email']]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $this->conn->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, location, availability) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $userData['username'],
            $userData['email'],
            $hashedPassword,
            $userData['first_name'],
            $userData['last_name'],
            $userData['location'],
            $userData['availability']
        ]);

        if ($success) {
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, username, email, password_hash, role, first_name, last_name, profile_image 
            FROM users 
            WHERE (username = ? OR email = ?) AND is_active = TRUE
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        
        $stmt = $this->conn->prepare("
            SELECT id, username, email, first_name, last_name, profile_image, bio, location, availability 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


?>