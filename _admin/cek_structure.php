<?php
// check_structure.php
include 'koneksi.php';

echo "<h1>Checking Database Structure</h1>";

// Check all tables
$tables = ['tb_agenda', 'tb_pejabat', 'tb_status', 'tb_tindaklanjut'];

foreach ($tables as $table) {
    echo "<h2>Table: $table</h2>";

    $result = $koneksi->query("SHOW COLUMNS FROM $table");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>Table $table tidak ditemukan!</p>";
    }

    // Show sample data
    $result = $koneksi->query("SELECT * FROM $table LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Sample Data (5 records):</h3>";
        echo "<table border='1' cellpadding='5'>";

        // Get column names
        $fields = $result->fetch_fields();
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";

        // Reset pointer
        $result->data_seek(0);

        // Get data
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}

$koneksi->close();
