<?php
// File: reset_system.php
include 'koneksi.php';

echo "<h3>Resetting System</h3>";

// 1. Drop tables if exist
$tables = ['tb_user_sessions', 'tb_role_permissions', 'tb_permissions', 'tb_users'];
foreach ($tables as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    if ($koneksi->query($sql)) {
        echo "Dropped table: $table<br>";
    }
}

// 2. Create tables again (simplified version)
echo "<h4>Creating tables...</h4>";

// tb_users
$sql = "CREATE TABLE tb_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin', 'pimpinan', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$koneksi->query($sql);
echo "Created tb_users<br>";

// Insert superadmin user
$password_hash = password_hash('Admin123!', PASSWORD_DEFAULT);
$sql = "INSERT INTO tb_users (username, email, password, full_name, role, is_active) 
        VALUES ('superadmin', 'admin@agenda.local', '$password_hash', 'Super Administrator', 'super_admin', 1)";
$koneksi->query($sql);
echo "Created superadmin user<br>";

// Insert test users
$users = [
    ['admin', 'admin@test.com', 'Admin123!', 'Administrator', 'admin'],
    ['pimpinan1', 'pimpinan@test.com', 'Admin123!', 'Pimpinan Utama', 'pimpinan'],
    ['staff1', 'staff@test.com', 'Admin123!', 'Staff Biasa', 'staff']
];

foreach ($users as $user) {
    $password_hash = password_hash($user[2], PASSWORD_DEFAULT);
    $sql = "INSERT INTO tb_users (username, email, password, full_name, role, is_active) 
            VALUES ('{$user[0]}', '{$user[1]}', '$password_hash', '{$user[3]}', '{$user[4]}', 1)";
    $koneksi->query($sql);
    echo "Created user: {$user[0]}<br>";
}

echo "<h4 style='color:green;'>Reset completed!</h4>";
echo "<a href='check_user.php'>Check Users</a> | ";
echo "<a href='login.php'>Go to Login</a>";
