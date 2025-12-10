<?php
// logout.php
session_start();

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    include __DIR__ . '/koneksi.php';
    $userId = $_SESSION['user_id'];

    $sql = "INSERT INTO tb_audit_logs (user_id, action, created_at) 
            VALUES (?, 'logout', NOW())";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php?logout=1');
exit;
