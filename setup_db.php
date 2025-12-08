<?php
// setup_db.php - Setup database otomatis
echo "<h1>Database Setup</h1>";

// Koneksi ke MySQL
$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS db_agenda 
        CHARACTER SET utf8mb4 
        COLLATE utf8mb4_unicode_ci";
        
if ($conn->query($sql)) {
    echo "<p style='color:green'>✓ Database created or already exists</p>";
} else {
    echo "<p style='color:red'>✗ Error creating database: " . $conn->error . "</p>";
}

// Select database
$conn->select_db('db_agenda');

// SQL untuk membuat tabel
$sql_tables = "
-- Table: tb_pejabat
CREATE TABLE IF NOT EXISTS tb_pejabat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pejabat VARCHAR(10) NOT NULL,
    nama_jabatan VARCHAR(1024) NOT NULL,
    nama_pejabat VARCHAR(1024) NOT NULL
);

-- Table: tb_status
CREATE TABLE IF NOT EXISTS tb_status (
    id_status INT AUTO_INCREMENT PRIMARY KEY,
    nama_status VARCHAR(255) NOT NULL,
    akronim VARCHAR(255) NOT NULL
);

-- Table: tb_agenda
CREATE TABLE IF NOT EXISTS tb_agenda (
    id_agenda INT AUTO_INCREMENT PRIMARY KEY,
    tgl_agenda DATE NOT NULL,
    waktu TIME NOT NULL,
    nama_kegiatan VARCHAR(3065) NOT NULL,
    tempat_kegiatan VARCHAR(3065) NOT NULL,
    penanggungjawab_kegiatan VARCHAR(3065) NOT NULL,
    pakaian_kegiatan VARCHAR(3065) NOT NULL,
    pejabat VARCHAR(3065) NOT NULL,
    lampiran TEXT NULL,
    id_status INT NOT NULL DEFAULT 6,
    hasil_agenda TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_status) REFERENCES tb_status(id_status)
);

-- Table: tb_tindaklanjut
CREATE TABLE IF NOT EXISTS tb_tindaklanjut (
    id_tindaklanjut INT AUTO_INCREMENT PRIMARY KEY,
    tgl_tindaklanjut DATETIME NOT NULL,
    isi_tindaklanjut TEXT NOT NULL,
    penindaklanjut VARCHAR(255) NOT NULL,
    lampiran TEXT NULL,
    id_agenda INT NOT NULL,
    FOREIGN KEY (id_agenda) REFERENCES tb_agenda(id_agenda) ON DELETE CASCADE
);
";

// Execute table creation
if ($conn->multi_query($sql_tables)) {
    echo "<p style='color:green'>✓ Tables created successfully</p>";
    
    // Insert default data
    $default_data = "
    -- Insert default status
    INSERT IGNORE INTO tb_status (id_status, nama_status, akronim) VALUES
    (3, 'Tindaklanjuti', 'ttl'),
    (4, 'Selesai', 'sls'),
    (5, 'Ditunda', 'ttd'),
    (6, 'Belum Mulai', 'blm');
    
    -- Insert sample pejabat
    INSERT IGNORE INTO tb_pejabat (id, kode_pejabat, nama_jabatan, nama_pejabat) VALUES
    (1, '001', 'Sekretaris Daerah', 'SYAHRIAL ABDI'),
    (2, '002', 'Kepala Diskominfotik', 'TEZA');
    
    -- Insert sample agenda
    INSERT IGNORE INTO tb_agenda (id_agenda, tgl_agenda, waktu, nama_kegiatan, tempat_kegiatan, penanggungjawab_kegiatan, pakaian_kegiatan, pejabat, lampiran, id_status) VALUES
    (22, '2025-08-26', '11:59:00', 'Silaturahmi dan Perkenalan Forum Penyuluh Antikorupsi Provinsi Riau', 'Inspektorat Daerah Provinsi Riau Jl. Cut Nyak Dien Pekanbaru', 'Drs. H. Eduar, M.Psa, M.Kom, CRMO', 'Menyesuaikan', '2', 'CamScanner_250826_113326.pdf', 4);
    ";
    
    if ($conn->multi_query($default_data)) {
        echo "<p style='color:green'>✓ Default data inserted</p>";
    }
} else {
    echo "<p style='color:red'>✗ Error creating tables: " . $conn->error . "</p>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='test.php'>Back to Test Page</a> | <a href='index.php'>Go to Application</a></p>";
?>