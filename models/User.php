<?php
// File: models/User.php

class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function findByUsername($username) {
        $sql = "SELECT * FROM tb_users WHERE username = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM tb_users WHERE email = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function create($data) {
        $sql = "INSERT INTO tb_users (username, email, password, full_name, nip, jabatan, role, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->bind_param("ssssssss", 
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['nip'],
            $data['jabatan'],
            $data['role'],
            $data['phone']
        );
        
        return $stmt->execute();
    }
    
    public function updateLoginAttempt($userId, $attempt) {
        $sql = "UPDATE tb_users SET login_attempt = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $attempt, $userId);
        return $stmt->execute();
    }
    
    public function lockAccount($userId) {
        $sql = "UPDATE tb_users SET is_locked = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
    
    public function updateLastLogin($userId) {
        $sql = "UPDATE tb_users SET last_login = NOW(), login_attempt = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
    
    public function getAllUsers($role = null) {
        $sql = "SELECT id, username, email, full_name, nip, jabatan, role, 
                       phone, last_login, is_active, created_at 
                FROM tb_users WHERE 1=1";
        
        if ($role) {
            $sql .= " AND role = ?";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        if ($role) {
            $stmt->bind_param("s", $role);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
}