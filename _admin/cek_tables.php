<?php
// check_tables.php
include 'koneksi.php';

echo "<h1>Checking Database Structure</h1>";

// Check tb_agenda table structure
$result = $koneksi->query("DESCRIBE tb_agenda");
echo "<h2>Table: tb_agenda</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check tb_status table
$result = $koneksi->query("SELECT * FROM tb_status");
echo "<h2>Table: tb_status</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>id_status</th><th>nama_status</th><th>akronim</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id_status'] . "</td>";
    echo "<td>" . $row['nama_status'] . "</td>";
    echo "<td>" . $row['akronim'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check tb_pejabat table
$result = $koneksi->query("SELECT * FROM tb_pejabat");
echo "<h2>Table: tb_pejabat</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>id</th><th>kode_pejabat</th><th>nama_jabatan</th><th>nama_pejabat</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['kode_pejabat'] . "</td>";
    echo "<td>" . $row['nama_jabatan'] . "</td>";
    echo "<td>" . $row['nama_pejabat'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$koneksi->close();
?>