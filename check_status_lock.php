<?php
// check_status_lock.php - Cek sistem penguncian status
include 'koneksi.php';

echo "<h1>Status Lock System Check</h1>";

// Cek agenda yang sudah ada tindak lanjut
$sql = "SELECT 
            a.id_agenda,
            a.nama_kegiatan,
            a.id_status,
            s.nama_status,
            COUNT(t.id_tindaklanjut) as jumlah_tl
        FROM tb_agenda a
        LEFT JOIN tb_status s ON a.id_status = s.id_status
        LEFT JOIN tb_tindaklanjut t ON a.id_agenda = t.id_agenda
        GROUP BY a.id_agenda
        ORDER BY jumlah_tl DESC";

$result = $koneksi->query($sql);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>
        <tr>
            <th>ID</th>
            <th>Kegiatan</th>
            <th>Status</th>
            <th>Jumlah Tindak Lanjut</th>
            <th>Status Lock</th>
            <th>Aksi</th>
        </tr>";

while ($row = $result->fetch_assoc()) {
    $is_locked = $row['jumlah_tl'] > 0;
    $lock_status = $is_locked ? '🔒 TERKUNCI' : '🔓 TERBUKA';
    $lock_color = $is_locked ? 'red' : 'green';

    echo "<tr>";
    echo "<td>" . $row['id_agenda'] . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_kegiatan']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_status']) . "</td>";
    echo "<td>" . $row['jumlah_tl'] . "</td>";
    echo "<td style='color: $lock_color; font-weight: bold;'>$lock_status</td>";
    echo "<td>";
    echo "<a href='edit.php?id=" . $row['id_agenda'] . "' style='margin-right: 10px;'>Edit</a>";
    if ($is_locked) {
        echo "<a href='unlock_status.php?id=" . $row['id_agenda'] . "' style='color: red;'>Unlock</a>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Summary:</h3>";

$sql_summary = "SELECT 
    COUNT(CASE WHEN tl_count > 0 THEN 1 END) as locked,
    COUNT(CASE WHEN tl_count = 0 THEN 1 END) as unlocked,
    COUNT(*) as total
FROM (
    SELECT a.id_agenda, COUNT(t.id_tindaklanjut) as tl_count
    FROM tb_agenda a
    LEFT JOIN tb_tindaklanjut t ON a.id_agenda = t.id_agenda
    GROUP BY a.id_agenda
) as subquery";

$result_summary = $koneksi->query($sql_summary);
$summary = $result_summary->fetch_assoc();

echo "<p>Total Agenda: <strong>" . $summary['total'] . "</strong></p>";
echo "<p>Status Terkunci: <strong style='color:red'>" . $summary['locked'] . "</strong> agenda</p>";
echo "<p>Status Terbuka: <strong style='color:green'>" . $summary['unlocked'] . "</strong> agenda</p>";

echo "<hr>";
echo "<p><a href='index.php'>Kembali ke aplikasi</a></p>";

$koneksi->close();
