<?php
// File: controllers/UserController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UserController {
    private $auth;
    private $userModel;
    private $middleware;
    private $conn;
    
    public function __construct() {
        $this->conn = DatabaseConfig::getConnection();
        $this->auth = new Auth($this->conn);
        $this->userModel = new User($this->conn);
        $this->middleware = new AuthMiddleware();
    }
    
    public function index() {
        $this->middleware->requirePermission('user_view');
        
        $users = $this->userModel->getAllUsers();
        
        include __DIR__ . '/../views/users/index.php';
    }
    
    public function create() {
        $this->middleware->requirePermission('user_create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            include __DIR__ . '/../views/users/create.php';
        }
    }
    
    private function handleCreate() {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Token keamanan tidak valid.';
            header('Location: users.php?action=create');
            exit;
        }
        
        // Validate input
        $required = ['username', 'email', 'full_name', 'role'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Field {$field} harus diisi.";
                header('Location: users.php?action=create');
                exit;
            }
        }
        
        // Check if username exists
        $existingUser = $this->userModel->findByUsername($_POST['username']);
        if ($existingUser) {
            $_SESSION['error'] = 'Username sudah digunakan.';
            header('Location: users.php?action=create');
            exit;
        }
        
        // Check if email exists
        $existingEmail = $this->userModel->findByEmail($_POST['email']);
        if ($existingEmail) {
            $_SESSION['error'] = 'Email sudah terdaftar.';
            header('Location: users.php?action=create');
            exit;
        }
        
        // Prepare user data
        $userData = [
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'] ?? 'Default123!', // Default password
            'full_name' => trim($_POST['full_name']),
            'nip' => trim($_POST['nip'] ?? ''),
            'jabatan' => trim($_POST['jabatan'] ?? ''),
            'role' => $_POST['role'],
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        // Create user
        if ($this->userModel->create($userData)) {
            $this->auth->logAudit($_SESSION['user_id'], 'create_user', 'tb_users', $this->conn->insert_id);
            $_SESSION['success'] = 'User berhasil dibuat.';
            header('Location: users.php');
            exit;
        } else {
            $_SESSION['error'] = 'Gagal membuat user.';
            header('Location: users.php?action=create');
            exit;
        }
    }
    
    public function edit($id) {
        $this->middleware->requirePermission('user_edit');
        
        $user = $this->getUserById($id);
        if (!$user) {
            $_SESSION['error'] = 'User tidak ditemukan.';
            header('Location: users.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id, $user);
        } else {
            include __DIR__ . '/../views/users/edit.php';
        }
    }
    
    private function handleEdit($id, $oldUser) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Token keamanan tidak valid.';
            header("Location: users.php?action=edit&id=$id");
            exit;
        }
        
        // Prepare update data
        $updateData = [
            'full_name' => trim($_POST['full_name']),
            'nip' => trim($_POST['nip'] ?? ''),
            'jabatan' => trim($_POST['jabatan'] ?? ''),
            'role' => $_POST['role'],
            'phone' => trim($_POST['phone'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Update user
        $sql = "UPDATE tb_users SET 
                full_name = ?, 
                nip = ?, 
                jabatan = ?, 
                role = ?, 
                phone = ?, 
                is_active = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssii", 
            $updateData['full_name'],
            $updateData['nip'],
            $updateData['jabatan'],
            $updateData['role'],
            $updateData['phone'],
            $updateData['is_active'],
            $id
        );
        
        if ($stmt->execute()) {
            // Log changes
            $oldValues = json_encode([
                'full_name' => $oldUser['full_name'],
                'nip' => $oldUser['nip'],
                'jabatan' => $oldUser['jabatan'],
                'role' => $oldUser['role'],
                'phone' => $oldUser['phone'],
                'is_active' => $oldUser['is_active']
            ]);
            
            $newValues = json_encode($updateData);
            
            $this->logUserChange($id, $oldValues, $newValues);
            
            $_SESSION['success'] = 'User berhasil diperbarui.';
            header('Location: users.php');
            exit;
        } else {
            $_SESSION['error'] = 'Gagal memperbarui user.';
            header("Location: users.php?action=edit&id=$id");
            exit;
        }
    }
    
    public function delete($id) {
        $this->middleware->requirePermission('user_delete');
        
        // Prevent deleting own account
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri.';
            header('Location: users.php');
            exit;
        }
        
        $user = $this->getUserById($id);
        if (!$user) {
            $_SESSION['error'] = 'User tidak ditemukan.';
            header('Location: users.php');
            exit;
        }
        
        // Soft delete (update is_active to 0)
        $sql = "UPDATE tb_users SET is_active = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->auth->logAudit($_SESSION['user_id'], 'delete_user', 'tb_users', $id);
            $_SESSION['success'] = 'User berhasil dinonaktifkan.';
        } else {
            $_SESSION['error'] = 'Gagal menonaktifkan user.';
        }
        
        header('Location: users.php');
        exit;
    }
    
    public function resetPassword($id) {
        $this->middleware->requirePermission('user_reset_password');
        
        $user = $this->getUserById($id);
        if (!$user) {
            $_SESSION['error'] = 'User tidak ditemukan.';
            header('Location: users.php');
            exit;
        }
        
        // Generate new password
        $newPassword = $this->generateRandomPassword();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE tb_users SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $id);
        
        if ($stmt->execute()) {
            $this->auth->logAudit($_SESSION['user_id'], 'reset_password', 'tb_users', $id);
            
            // Store password for display (in real app, send via email)
            $_SESSION['new_password'] = [
                'username' => $user['username'],
                'password' => $newPassword
            ];
            
            $_SESSION['success'] = 'Password berhasil direset.';
            header("Location: users.php?action=show_password&id=$id");
            exit;
        } else {
            $_SESSION['error'] = 'Gagal reset password.';
            header('Location: users.php');
            exit;
        }
    }
    
    private function getUserById($id) {
        $sql = "SELECT * FROM tb_users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    private function generateRandomPassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    private function logUserChange($userId, $oldValues, $newValues) {
        $sql = "INSERT INTO tb_audit_logs (user_id, action, table_name, record_id, old_values, new_values) 
                VALUES (?, 'update_user', 'tb_users', ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $currentUserId = $_SESSION['user_id'];
        $stmt->bind_param("iiss", $currentUserId, $userId, $oldValues, $newValues);
        $stmt->execute();
    }
}