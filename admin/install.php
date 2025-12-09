<?php
// install.php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Instalasi Aplikasi Agenda Pimpinan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .step { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #4361ee; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        button { background: #4361ee; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #3f37c9; }
        pre { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Instalasi Aplikasi Agenda Pimpinan</h1>
        
        <div class='step'>
            <h2>Step 1: Cek Persyaratan Sistem</h2>";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "<p class='success'>✓ PHP 7.4+ terdeteksi (" . PHP_VERSION . ")</p>";
} else {
    echo "<p class='error'>✗ PHP 7.4+ dibutuhkan. Versi saat ini: " . PHP_VERSION . "</p>";
}

// Check MySQLi
if (extension_loaded('mysqli')) {
    echo "<p class='success'>✓ MySQLi extension terdeteksi</p>";
} else {
    echo "<p class='error'>✗ MySQLi extension tidak terdeteksi</p>";
}

// Check GD
if (extension_loaded('gd')) {
    echo "<p class='success'>✓ GD extension terdeteksi</p>";
} else {
    echo "<p class='warning'>⚠ GD extension tidak terdeteksi (opsional untuk gambar)</p>";
}

// Check file permissions
$writable_dirs = ['uploads', '.'];
foreach ($writable_dirs as $dir) {
    if (is_writable($dir)) {
        echo "<p class='success'>✓ Folder '$dir' dapat ditulis</p>";
    } else {
        echo "<p class='error'>✗ Folder '$dir' tidak dapat ditulis</p>";
    }
}

echo "</div>

        <div class='step'>
            <h2>Step 2: Konfigurasi Database</h2>
            <form method='post' action=''>
                <p><strong>Database Configuration:</strong></p>
                <p>
                    <label>Host:</label><br>
                    <input type='text' name='db_host' value='localhost' style='width: 100%; padding: 8px; margin: 5px 0;'>
                </p>
                <p>
                    <label>Username:</label><br>
                    <input type='text' name='db_user' value='root' style='width: 100%; padding: 8px; margin: 5px 0;'>
                </p>
                <p>
                    <label>Password:</label><br>
                    <input type='password' name='db_pass' style='width: 100%; padding: 8px; margin: 5px 0;'>
                </p>
                <p>
                    <label>Database Name:</label><br>
                    <input type='text' name='db_name' value='db_agenda' style='width: 100%; padding: 8px; margin: 5px 0;'>
                </p>
                <button type='submit' name='install'>Install Database</button>
            </form>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['install'])) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    try {
        // Test connection
        $conn = new mysqli($db_host, $db_user, $db_pass);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Create database if not exists
        $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db($db_name);

        // Import SQL
        $sql = file_get_contents('db_agenda.sql');
        $conn->multi_query($sql);

        echo "<p class='success'>✓ Database berhasil dibuat dan diimport!</p>";

        // Create koneksi.php
        $koneksi_content = "<?php
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if (\$this->connection->connect_error) {
                throw new Exception('Koneksi database gagal: ' . \$this->connection->connect_error);
            }
            
            \$this->connection->set_charset('utf8mb4');
            \$this->connection->query('SET time_zone = \"+07:00\"');
            
        } catch (Exception \$e) {
            error_log(\$e->getMessage());
            die('Sistem sedang dalam perawatan. Silakan coba beberapa saat lagi.');
        }
    }
    
    public static function getInstance() {
        if (self::\$instance == null) {
            self::\$instance = new Database();
        }
        return self::\$instance->connection;
    }
    
    public static function close() {
        if (self::\$instance != null) {
            self::\$instance->connection->close();
            self::\$instance = null;
        }
    }
}

\$koneksi = Database::getInstance();
date_default_timezone_set('Asia/Jakarta');
?>";

        file_put_contents('koneksi.php', $koneksi_content);
        echo "<p class='success'>✓ File koneksi.php berhasil dibuat!</p>";

        $conn->close();
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

echo "</div>

        <div class='step'>
            <h2>Step 3: Setup Selesai</h2>
            <p>Jika semua step berhasil, aplikasi siap digunakan.</p>
            
            <h3>Login Default:</h3>
            <p><strong>URL:</strong> <a href='index.php'>index.php</a></p>
            
            <h3>Setup Cron Job (Opsional):</h3>
            <p>Untuk notifikasi harian, tambahkan ke crontab:</p>
            <pre>0 8 * * * /usr/bin/php " . realpath('notifications.php') . " >/dev/null 2>&1</pre>
            
            <h3>Security Checklist:</h3>
            <ul>
                <li>✓ Hapus file install.php setelah instalasi</li>
                <li>✓ Ubah password default di koneksi.php</li>
                <li>✓ Setup SSL/HTTPS untuk production</li>
                <li>✓ Backup database secara berkala</li>
            </ul>
            
            <p><a href='index.php'><button>Lanjut ke Aplikasi</button></a></p>
        </div>
    </div>
</body>
</html>";
