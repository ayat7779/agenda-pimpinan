<?php
// File: models/Permission.php

class Permission {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function hasPermission($role, $permissionKey) {
        $sql = "SELECT COUNT(*) as count 
                FROM tb_role_permissions rp
                JOIN tb_permissions p ON rp.permission_key = p.permission_key
                WHERE rp.role = ? AND p.permission_key = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $role, $permissionKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    public function getRolePermissions($role) {
        $sql = "SELECT p.* 
                FROM tb_role_permissions rp
                JOIN tb_permissions p ON rp.permission_key = p.permission_key
                WHERE rp.role = ?
                ORDER BY p.module, p.permission_name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        
        return $permissions;
    }
    
    public function getAllPermissions() {
        $sql = "SELECT * FROM tb_permissions ORDER BY module, permission_name";
        $result = $this->conn->query($sql);
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        
        return $permissions;
    }
    
    public function updateRolePermissions($role, $permissions) {
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Delete existing permissions for this role
            $deleteSql = "DELETE FROM tb_role_permissions WHERE role = ?";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->bind_param("s", $role);
            $deleteStmt->execute();
            
            // Insert new permissions
            if (!empty($permissions)) {
                $insertSql = "INSERT INTO tb_role_permissions (role, permission_key) VALUES (?, ?)";
                $insertStmt = $this->conn->prepare($insertSql);
                
                foreach ($permissions as $permissionKey) {
                    $insertStmt->bind_param("ss", $role, $permissionKey);
                    $insertStmt->execute();
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}