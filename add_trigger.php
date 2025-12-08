<?php
// add_trigger.php - Tambahkan trigger di database
include 'koneksi.php';

echo "<h1>Menambahkan Database Trigger</h1>";

// Hapus trigger jika sudah ada
$sql_drop = "DROP TRIGGER IF EXISTS prevent_status_change";
if ($koneksi->query($sql_drop)) {
    echo "<p style='color:green'>✓ Trigger lama dihapus</p>";
}

// Buat trigger baru
$sql_trigger = "
CREATE TRIGGER prevent_status_change 
BEFORE UPDATE ON tb_agenda
FOR EACH ROW
BEGIN
    DECLARE tl_count INT;
    
    -- Cek apakah ada tindak lanjut
    SELECT COUNT(*) INTO tl_count 
    FROM tb_tindaklanjut 
    WHERE id_agenda = OLD.id_agenda;
    
    -- Jika ada tindak lanjut dan mencoba mengubah status, pertahankan status lama
    IF tl_count > 0 AND OLD.id_status != NEW.id_status THEN
        SET NEW.id_status = OLD.id_status;
    END IF;
END;
";

if ($koneksi->query($sql_trigger)) {
    echo "<p style='color:green'>✓ Trigger berhasil dibuat</p>";
    echo "<p>Trigger akan mencegah perubahan status jika sudah ada tindak lanjut.</p>";
} else {
    echo "<p style='color:red'>✗ Gagal membuat trigger: " . $koneksi->error . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Kembali ke aplikasi</a></p>";

$koneksi->close();
