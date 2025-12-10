<?php
// File: controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Auth.php';

class AuthController {
    private $auth;
    private $conn;
    
    public function __construct() {
        $this->conn = DatabaseConfig::getConnection();
        $this->auth = new Auth($this->conn);
    }
    
    public function showLogin() {
        // If already logged in, redirect to dashboard
        if ($this->auth->isLoggedIn()) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        
        include __DIR__ . '/../views/auth/login.php';
    }
    
    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: login.php');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Token keamanan tidak valid.';
            header('Location: login.php');
            exit;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username dan password harus diisi.';
            header('Location: login.php');
            exit;
        }
        
        $result = $this->auth->login($username, $password);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Login berhasil. Selamat datang, ' . $result['user']['full_name'] . '!';
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: login.php');
            exit;
        }
    }
    
    public function logout() {
        $this->auth->logout();
        $_SESSION['success'] = 'Anda telah logout.';
        header('Location: login.php');
        exit;
    }
    
    public function showProfile() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        $user = $this->auth->getUserData();
        include __DIR__ . '/../views/auth/profile.php';
    }
    
    public function updateProfile() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: profile.php');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Token keamanan tidak valid.';
            header('Location: profile.php');
            exit;
        }
        
        $userId = $this->auth->getUserId();
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Validate input
        if (empty($fullName)) {
            $_SESSION['error'] = 'Nama lengkap harus diisi.';
            header('Location: profile.php');
            exit;
        }
        
        // Handle avatar upload
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            
            if (in_array($fileExt, $allowedTypes)) {
                $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                    $avatar = $newFileName;
                }
            }
        }
        
        // Update user data
        $userModel = new User($this->conn);
        $user = $userModel->findByUsername($_SESSION['username']);
        
        $sql = "UPDATE tb_users SET full_name = ?, phone = ?";
        $params = [$fullName, $phone];
        $types = "ss";
        
        if ($avatar) {
            $sql .= ", avatar = ?";
            $params[] = $avatar;
            $types .= "s";
            
            // Delete old avatar if exists and not default
            if ($user['avatar'] && $user['avatar'] !== 'default.png') {
                $oldAvatar = $uploadDir . $user['avatar'];
                if (file_exists($oldAvatar)) {
                    unlink($oldAvatar);
                }
            }
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['full_name'] = $fullName;
            if ($avatar) {
                $_SESSION['avatar'] = $avatar;
            }
            
            $this->auth->logAudit($userId, 'update_profile', 'tb_users', $userId);
            $_SESSION['success'] = 'Profil berhasil diperbarui.';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui profil.';
        }
        
        header('Location: profile.php');
        exit;
    }
}