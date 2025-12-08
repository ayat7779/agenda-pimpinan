<?php
// fix_cascade.php - Script untuk menambahkan CASCADE constraint
include 'koneksi.php';

echo "<h1>Memperbaiki Foreign Key Constraint</h1>";

try {
    // Cek constraint yang ada
    $sql = "SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'tb_tindaklanjut' 
            AND CONSTRAINT_SCHEMA = DATABASE()";

    $result = $koneksi->query($sql);

    echo "<h3>Constraint saat ini:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Constraint Name</th><th>Table</th><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $row['TABLE_NAME'] . "</td>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Drop existing foreign key jika ada
    $drop_sql = "ALTER TABLE tb_tindaklanjut DROP FOREIGN KEY tb_tindaklanjut_ibfk_1";
    if ($koneksi->query($drop_sql)) {
        echo "<p style='color:green'>✓ Foreign key constraint lama dihapus</p>";
    } else {
        echo "<p style='color:orange'>⚠ Tidak ada foreign key constraint lama atau sudah dihapus</p>";
    }

    // Tambahkan foreign key dengan CASCADE
    $add_sql = "ALTER TABLE tb_tindaklanjut 
                ADD CONSTRAINT fk_tindaklanjut_agenda 
                FOREIGN KEY (id_agenda) 
                REFERENCES tb_agenda(id_agenda) 
                ON DELETE CASCADE";

    if ($koneksi->query($add_sql)) {
        echo "<p style='color:green'>✓ Foreign key dengan CASCADE berhasil ditambahkan</p>";
    } else {
        echo "<p style='color:red'>✗ Gagal menambahkan foreign key: " . $koneksi->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Kembali ke aplikasi</a></p>";

$koneksi->close();
