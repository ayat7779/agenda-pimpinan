<?php
// check_connection_type.php
echo "<h1>Checking Connection Type</h1>";

// Cek apakah koneksi.php ada
if (file_exists('koneksi.php')) {
    $content = file_get_contents('koneksi.php');

    if (strpos($content, 'new mysqli') !== false) {
        echo "<p style='color:green'>✓ Menggunakan MySQLi</p>";
    } elseif (strpos($content, 'new PDO') !== false) {
        echo "<p style='color:blue'>✓ Menggunakan PDO</p>";
    } else {
        echo "<p style='color:red'>✗ Jenis koneksi tidak dikenali</p>";
    }

    // Cek apakah ada method yang tidak kompatibel
    $files = ['index.php', 'tambah.php', 'edit.php', 'hapus.php', 'tindaklanjut.php'];

    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);

            echo "<h3>$file</h3>";

            if (strpos($content, '->num_rows') !== false) {
                echo "<p style='color:orange'>⚠ Menggunakan num_rows (MySQLi method)</p>";
            }

            if (strpos($content, '->fetch_assoc()') !== false) {
                echo "<p style='color:orange'>⚠ Menggunakan fetch_assoc() (MySQLi method)</p>";
            }

            if (strpos($content, '->close()') !== false) {
                echo "<p style='color:orange'>⚠ Menggunakan close() (MySQLi method)</p>";
            }

            if (strpos($content, '->fetch(PDO::FETCH_ASSOC)') !== false) {
                echo "<p style='color:blue'>⚠ Menggunakan PDO::FETCH_ASSOC (PDO method)</p>";
            }
        }
    }
} else {
    echo "<p style='color:red'>✗ File koneksi.php tidak ditemukan</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Kembali ke aplikasi</a></p>";
