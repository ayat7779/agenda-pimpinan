<?php
// update_database.php
include 'koneksi.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #4361ee; background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Update Database Aplikasi Agenda</h1>";

// Update 1: Add created_at and updated_at columns
echo "<div class='step'><h3>Update 1: Menambahkan kolom timestamp</h3>";
try {
    $sql = "ALTER TABLE tb_agenda 
            ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    $koneksi->query($sql);
    echo "<p class='success'>✓ Kolom timestamp berhasil ditambahkan</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Update 2: Create activity log table
echo "<div class='step'><h3>Update 2: Membuat tabel log aktivitas</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS tb_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agenda_id INT,
        action VARCHAR(50),
        user_ip VARCHAR(45),
        user_agent TEXT,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (agenda_id) REFERENCES tb_agenda(id_agenda) ON DELETE SET NULL
    )";
    $koneksi->query($sql);
    echo "<p class='success'>✓ Tabel activity log berhasil dibuat</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Update 3: Create notification log table
echo "<div class='step'><h3>Update 3: Membuat tabel log notifikasi</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS tb_notification_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50),
        recipient VARCHAR(255),
        status VARCHAR(20),
        details TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $koneksi->query($sql);
    echo "<p class='success'>✓ Tabel notification log berhasil dibuat</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Update 4: Add indexes for performance
echo "<div class='step'><h3>Update 4: Menambahkan indeks untuk performa</h3>";
try {
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_agenda_date ON tb_agenda(tgl_agenda)",
        "CREATE INDEX IF NOT EXISTS idx_agenda_status ON tb_agenda(id_status)",
        "CREATE INDEX IF NOT EXISTS idx_agenda_pejabat ON tb_agenda(pejabat)",
        "CREATE INDEX IF NOT EXISTS idx_tindaklanjut_agenda ON tb_tindaklanjut(id_agenda)",
        "CREATE INDEX IF NOT EXISTS idx_activity_agenda ON tb_activity_log(agenda_id)",
        "CREATE INDEX IF NOT EXISTS idx_activity_date ON tb_activity_log(created_at)"
    ];

    foreach ($indexes as $index_sql) {
        $koneksi->query($index_sql);
    }
    echo "<p class='success'>✓ Indeks berhasil ditambahkan</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Update 5: Add settings table
echo "<div class='step'><h3>Update 5: Membuat tabel pengaturan</h3>";
try {
    $sql = "CREATE TABLE IF NOT EXISTS tb_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT,
        setting_type VARCHAR(20) DEFAULT 'text',
        setting_group VARCHAR(50) DEFAULT 'general',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $koneksi->query($sql);

    // Insert default settings
    $default_settings = [
        ['app_name', 'Aplikasi Agenda Pimpinan', 'text', 'general', 'Nama aplikasi'],
        ['app_version', '2.0.0', 'text', 'general', 'Versi aplikasi'],
        ['notifications_enabled', '1', 'boolean', 'notifications', 'Aktifkan notifikasi'],
        ['notification_email', 'admin@example.com', 'email', 'notifications', 'Email notifikasi'],
        ['items_per_page', '25', 'number', 'display', 'Item per halaman'],
        ['dark_mode_default', '0', 'boolean', 'display', 'Dark mode default'],
        ['file_max_size', '5', 'number', 'uploads', 'Ukuran maksimal file (MB)'],
        ['allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'text', 'uploads', 'Tipe file yang diizinkan']
    ];

    $stmt = $koneksi->prepare("INSERT IGNORE INTO tb_settings 
        (setting_key, setting_value, setting_type, setting_group, description) 
        VALUES (?, ?, ?, ?, ?)");

    foreach ($default_settings as $setting) {
        $stmt->bind_param("sssss", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4]);
        $stmt->execute();
    }

    $stmt->close();
    echo "<p class='success'>✓ Tabel settings berhasil dibuat dan diinisialisasi</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='step'>
    <h3>Update Selesai!</h3>
    <p>Database telah berhasil diupdate ke versi terbaru.</p>
    <p><strong>Penting:</strong> Hapus file update_database.php setelah proses selesai.</p>
    <p><a href='index.php'>Kembali ke Aplikasi</a></p>
</div>";

$koneksi->close();
