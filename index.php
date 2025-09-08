<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file koneksi database
include 'koneksi.php';

// Array nama bulan untuk format tanggal
$nama_bulan = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember"
];

// Menentukan kondisi filter
$where_clause = "";
$filter = $_GET['filter'] ?? 'semua';

if ($filter == 'hari_ini') {
    $where_clause = "WHERE a.tgl_agenda = CURDATE()";
} elseif ($filter == 'bulan_ini') {
    $where_clause = "WHERE MONTH(a.tgl_agenda) = MONTH(CURDATE()) AND YEAR(a.tgl_agenda) = YEAR(CURDATE())";
} elseif ($filter == 'tahun_ini') {
    $where_clause = "WHERE YEAR(a.tgl_agenda) = YEAR(CURDATE())";
}

// Kueri SQL yang disatukan dan lebih rapi
$sql = "SELECT 
            a.*, 
            b.nama_status,
            c.isi_tindaklanjut,
            c.tgl_tindaklanjut,
            c.penindaklanjut,
            c.lampiran AS lampiran_tindaklanjut 
        FROM 
            tb_agenda AS a
        LEFT JOIN 
            tb_status AS b ON a.id_status = b.id_status 
        LEFT JOIN 
            tb_tindaklanjut AS c ON a.id_agenda = c.id_agenda    
        $where_clause
        ORDER BY 
            a.tgl_agenda DESC, 
            a.waktu DESC";

$result = $koneksi->query($sql);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Agenda Pimpinan</title>

    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.dataTables.min.css">
</head>

<body>
    <div class="container">
        <h2>Daftar Agenda Pimpinan</h2>

        <div class="action-and-filter-container">
            <div class="filter-buttons">
                <a href="index.php?filter=semua" class="button-filter <?php echo ($filter === 'semua') ? 'active' : ''; ?>">Semua</a>
                <a href="index.php?filter=hari_ini" class="button-filter <?php echo ($filter === 'hari_ini') ? 'active' : ''; ?>">Hari Ini</a>
                <a href="index.php?filter=bulan_ini" class="button-filter <?php echo ($filter === 'bulan_ini') ? 'active' : ''; ?>">Bulan Ini</a>
                <a href="index.php?filter=tahun_ini" class="button-filter <?php echo ($filter === 'tahun_ini') ? 'active' : ''; ?>">Tahun Ini</a>
            </div>
            <a href="tambah.php" class="button-tambah">Tambah Agenda Baru</a>
        </div>

        <div class="table-responsive">
            <table id="agendaTable" class="display responsive" style="width:100%">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Kegiatan</th>
                        <th>Tempat</th>
                        <th>Penanggung Jawab</th>
                        <th>Pakaian</th>
                        <th>Pejabat</th>
                        <th>Lampiran</th>
                        <th>Status</th>
                        <th>Hasil Yang Dicapai</th>
                        <th>Tindak Lanjut</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            // Cek apakah kolom 'hasil_agenda' ada, jika tidak, gunakan 'hasil_yang_dicapai'
                            $hasil_dicapai = isset($row['hasil_agenda']) ? $row['hasil_agenda'] : $row['hasil_agenda'];

                            $tanggal_agenda = strtotime($row['tgl_agenda']);
                            $format_tanggal = date('d', $tanggal_agenda) . " " . $nama_bulan[date('n', $tanggal_agenda) - 1] . " " . date('Y', $tanggal_agenda);
                    ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($format_tanggal); ?></td>
                                <td><?php echo htmlspecialchars(date('H:i', strtotime($row['waktu']))); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['tempat_kegiatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['penanggungjawab_kegiatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['pakaian_kegiatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['pejabat']); ?></td>
                                <td>
                                    <?php if (!empty($row['lampiran'])): ?>
                                        <span class="status-badge status-belum-mulai">
                                            <a href="uploads/<?php echo htmlspecialchars($row['lampiran']); ?>" target="_blank">Lihat File</a>
                                        </span>
                                    <?php else: ?>
                                        Tidak ada
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['nama_status'])); ?>">
                                        <?php echo htmlspecialchars($row['nama_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($hasil_dicapai); ?></td>
                                <td>Tgl: <?php echo htmlspecialchars($row['tgl_tindaklanjut']); ?><br>
                                    Isi: <b><?php echo htmlspecialchars($row['isi_tindaklanjut']); ?></b><br>
                                    Olh: <?php echo htmlspecialchars($row['penindaklanjut']); ?> <br>
                                    lmp: <?php if (!empty($row['lampiran_tindaklanjut'])): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($row['lampiran_tindaklanjut']); ?>" target="_blank">Lihat File Tindaklanjut</a>
                                    <?php else: ?>
                                        Tidak ada
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="tindaklanjut.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi tindaklanjut">Tindak Lanjut</a>
                                    <a href="edit.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi edit">Edit</a>
                                    <a href="hapus.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi hapus" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr colspan='13' style='text-align: center;'>Tidak ada data agenda yang ditemukan.</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#agendaTable').DataTable({
                responsive: true
            });
        });
    </script>
</body>

</html>

<?php $koneksi->close(); ?>