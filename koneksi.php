<?php
// koneksi.php - MySQLi version
$host = "localhost";
$username = "root";
$password = "";
$database = "dbagenda";

// Create connection
$koneksi = new mysqli($host, $username, $password, $database);

// Check connection
if ($koneksi->connect_error) {
    die("Connection failed: " . $koneksi->connect_error);
}

// Set charset
$koneksi->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
