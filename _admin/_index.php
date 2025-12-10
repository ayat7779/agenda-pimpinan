<?php
// File: index.php (Simplified version)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

echo "Login berhasil!<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Nama: " . $_SESSION['full_name'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";
echo "<a href='logout.php'>Logout</a>";

// Test database connection
include 'koneksi.php';
echo "<br><br>Database connected: " . ($koneksi ? "Yes" : "No");
?>