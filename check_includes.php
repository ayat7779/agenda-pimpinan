<?php
// check_includes.php - Cek file mana yang include koneksi.php

echo "<h1>Checking Included Files</h1>";

// List files yang mungkin include koneksi.php
$files = [
    'index.php',
    'tambah.php',
    'edit.php',
    'hapus.php',
    'tindaklanjut.php',
    'security.php',
    'api.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<h3>$file</h3>";
        $content = file_get_contents($file);

        if (strpos($content, "include 'koneksi.php'") !== false) {
            echo "<p style='color:blue'>✓ Meng-include koneksi.php</p>";
        } elseif (strpos($content, "include_once 'koneksi.php'") !== false) {
            echo "<p style='color:green'>✓ Meng-include_once koneksi.php</p>";
        } elseif (strpos($content, "require 'koneksi.php'") !== false) {
            echo "<p style='color:blue'>✓ Meng-require koneksi.php</p>";
        } elseif (strpos($content, "require_once 'koneksi.php'") !== false) {
            echo "<p style='color:green'>✓ Meng-require_once koneksi.php</p>";
        } else {
            echo "<p style='color:red'>✗ Tidak include koneksi.php</p>";
        }

        // Check for function redeclaration
        if (strpos($content, "function connectDB") !== false) {
            echo "<p style='color:red'>✗ Memiliki fungsi connectDB()</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠ File $file tidak ditemukan</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>Kembali ke aplikasi</a></p>";
