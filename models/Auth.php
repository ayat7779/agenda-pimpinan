<?php
// File: models/Auth.php

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
            
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }
    
    public function login($username, $password) {
        // Rate limiting check
        if (!$this->checkRateLimit($username)) {
            return ['success' => false, 'message' => 'Akun terkunci. Coba lagi dalam 15 menit.'];
        }
        
        $userModel = new User($this->conn);
        $user = $userModel->findByUsername($username);
        
        if (!$user) {
            $this->logFailedAttempt($username);
            return ['success' => false, 'message' => 'Username atau password salah'];
        }
        
        // Check if account is locked
        if ($user['is_locked']) {
            return ['success' => false, 'message' => 'Akun terkunci. Hubungi administrator.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $userModel->updateLoginAttempt($user['id'], $user['login_attempt'] + 1);
            
            // Lock account after 5 failed attempts
            if ($user['login_attempt'] + 1 >= 5) {
                $userModel->lockAccount($user['id']);
                return ['success' => false, 'message' => 'Akun terkunci karena terlalu banyak percobaan gagal.'];
            }
            
            return ['success' => false, 'message' => 'Username atau password salah'];
        }
        
        // Reset login attempts and update last login
        $userModel->updateLastLogin($user['id']);
        
        // Create session
        $sessionId = bin2hex(random_bytes(32));
        $this->createUserSession($user['id'], $sessionId);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Log successful login
        $this->logAudit($user['id'], 'login', 'tb_users', $user['id']);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['full_name']
            ]
        ];
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'], $_SESSION['session_id'])) {
            $this->destroyUserSession($_SESSION['session_id']);
            $this->logAudit($_SESSION['user_id'], 'logout', 'tb_users', $_SESSION['user_id']);
        }
        
        $_SESSION = [];
        session_destroy();
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Verify session in database
        if (isset($_SESSION['session_id'])) {
            return $this->verifySession($_SESSION['session_id']);
        }
        
        return false;
    }
    
    public function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUserData() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userModel = new User($this->conn);
        return $userModel->findByUsername($_SESSION['username']);
    }
    
    private function checkRateLimit($username) {
        $key = "rate_limit_login_{$username}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'timestamp' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        // Reset after 15 minutes
        if (time() - $data['timestamp'] > 900) { // 15 minutes
            $_SESSION[$key] = [
                'count' => 1,
                'timestamp' => time()
            ];
            return true;
        }
        
        // Max 10 attempts per 15 minutes
        if ($data['count'] >= 10) {
            return false;
        }
        
        $data['count']++;
        $_SESSION[$key] = $data;
        return true;
    }
    
    private function logFailedAttempt($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $sql = "INSERT INTO tb_audit_logs (action, table_name, ip_address, user_agent, created_at) 
                VALUES ('failed_login', 'tb_users', ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $ip, $userAgent);
        $stmt->execute();
    }
    
    private function createUserSession($userId, $sessionId) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $sql = "INSERT INTO tb_user_sessions (user_id, session_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isss", $userId, $sessionId, $ip, $userAgent);
        $stmt->execute();
    }
    
    private function destroyUserSession($sessionId) {
        $sql = "UPDATE tb_user_sessions SET is_active = 0 WHERE session_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
    }
    
    private function verifySession($sessionId) {
        $sql = "SELECT * FROM tb_user_sessions 
                WHERE session_id = ? AND is_active = 1 
                AND last_activity > DATE_SUB(NOW(), INTERVAL 2 HOUR)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update last activity
            $updateSql = "UPDATE tb_user_sessions SET last_activity = NOW() WHERE session_id = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("s", $sessionId);
            $updateStmt->execute();
            return true;
        }
        
        return false;
    }
    
    private function logAudit($userId, $action, $tableName = null, $recordId = null) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $sql = "INSERT INTO tb_audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ississ", $userId, $action, $tableName, $recordId, $ip, $userAgent);
        $stmt->execute();
    }
}