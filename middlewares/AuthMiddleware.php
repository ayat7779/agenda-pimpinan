<?php
// File: middlewares/AuthMiddleware.php

require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/Permission.php';

class AuthMiddleware {
    private $auth;
    private $permission;
    private $conn;
    
    public function __construct() {
        $this->conn = DatabaseConfig::getConnection();
        $this->auth = new Auth($this->conn);
        $this->permission = new Permission($this->conn);
    }
    
    public function requireLogin($redirectTo = 'login.php') {
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Anda harus login terlebih dahulu.';
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function requireRole($requiredRole, $redirectTo = 'dashboard.php') {
        $this->requireLogin();
        
        $userRole = $this->auth->getUserRole();
        
        // Define role hierarchy
        $roleHierarchy = [
            'super_admin' => 4,
            'admin' => 3,
            'pimpinan' => 2,
            'staff' => 1
        ];
        
        if (!isset($roleHierarchy[$userRole]) || 
            $roleHierarchy[$userRole] < $roleHierarchy[$requiredRole]) {
            $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengakses halaman ini.';
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function requirePermission($permissionKey, $redirectTo = 'dashboard.php') {
        $this->requireLogin();
        
        $userRole = $this->auth->getUserRole();
        
        if (!$this->permission->hasPermission($userRole, $permissionKey)) {
            $_SESSION['error'] = 'Anda tidak memiliki izin untuk melakukan tindakan ini.';
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function redirectIfLoggedIn($redirectTo = 'dashboard.php') {
        if ($this->auth->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function getUserData() {
        return $this->auth->getUserData();
    }
}