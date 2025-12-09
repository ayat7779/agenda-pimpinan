<?php
// test.php - Untuk testing basic functionality
echo "<h1>Test Page</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test database connection
$conn = @new mysqli('localhost', 'root', '', 'db_agenda');
if ($conn->connect_error) {
    echo "<p style='color:red'>Database Error: " . $conn->connect_error . "</p>";
    
    // Try to create database
    echo "<p>Attempting to create database...</p>";
    $conn = new mysqli('localhost', 'root', '');
    if ($conn->connect_error) {
        echo "<p style='color:red'>MySQL Error: " . $conn->connect_error . "</p>";
    } else {
        $sql = "CREATE DATABASE IF NOT EXISTS db_agenda";
        if ($conn->query($sql)) {
            echo "<p style='color:green'>Database created successfully!</p>";
        } else {
            echo "<p style='color:red'>Failed to create database: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color:green'>✓ Database connected successfully!</p>";
    $conn->close();
}

// Test file permissions
echo "<h2>File Status:</h2>";
$files = [
    'index.php',
    'koneksi.php',
    '.htaccess'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p>✓ $file exists (Permission: $perms)</p>";
    } else {
        echo "<p style='color:red'>✗ $file not found</p>";
    }
}

// Check uploads folder
if (!is_dir('uploads')) {
    if (@mkdir('uploads', 0755)) {
        echo "<p>✓ Created uploads directory</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create uploads directory</p>";
    }
} else {
    echo "<p>✓ Uploads directory exists</p>";
}

// Check if we can write to uploads
if (is_dir('uploads') && is_writable('uploads')) {
    echo "<p>✓ Uploads directory is writable</p>";
} else {
    echo "<p style='color:red'>✗ Uploads directory is not writable</p>";
}

// PHP extensions check
echo "<h2>PHP Extensions:</h2>";
$exts = ['mysqli', 'gd', 'mbstring', 'json'];
foreach ($exts as $ext) {
    echo extension_loaded($ext) ? 
        "<p>✓ $ext loaded</p>" : 
        "<p style='color:orange'>⚠ $ext not loaded (optional)</p>";
}

echo "<hr>";
echo "<h2>Quick Navigation:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>Main Application</a></li>";
echo "<li><a href='tambah.php'>Add Agenda Test</a></li>";
echo "<li><a href='api.php'>API Test</a></li>";
echo "</ul>";
?>