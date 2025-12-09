<?php
// index.php 
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
$search = $_GET['search'] ?? '';

if ($filter == 'hari_ini') {
    $where_clause = "WHERE a.tgl_agenda = CURDATE()";
} elseif ($filter == 'bulan_ini') {
    $where_clause = "WHERE MONTH(a.tgl_agenda) = MONTH(CURDATE()) AND YEAR(a.tgl_agenda) = YEAR(CURDATE())";
} elseif ($filter == 'tahun_ini') {
    $where_clause = "WHERE YEAR(a.tgl_agenda) = YEAR(CURDATE())";
}

// Tambahkan pencarian
if (!empty($search)) {
    $search = $koneksi->real_escape_string($search);
    if ($where_clause) {
        $where_clause .= " AND (a.nama_kegiatan LIKE '%$search%' OR a.tempat_kegiatan LIKE '%$search%' OR p.nama_pejabat LIKE '%$search%')";
    } else {
        $where_clause = "WHERE (a.nama_kegiatan LIKE '%$search%' OR a.tempat_kegiatan LIKE '%$search%' OR p.nama_pejabat LIKE '%$search%')";
    }
}

// Kueri SQL yang disatukan dan lebih rapi
$sql = "SELECT distinct 
            a.*, 
            b.nama_status,
            c.isi_tindaklanjut,
            p.nama_pejabat,
            p.nama_jabatan
        FROM 
            tb_agenda AS a
        LEFT JOIN 
            tb_status AS b ON a.id_status = b.id_status 
        LEFT JOIN 
            tb_tindaklanjut AS c ON a.id_agenda = c.id_agenda
        LEFT JOIN 
            tb_pejabat AS p ON a.pejabat = p.id    
        $where_clause
        ORDER BY 
            a.tgl_agenda DESC, 
            a.waktu DESC";

$result = $koneksi->query($sql);

// Ambil statistik untuk dashboard
$sql_stats = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN tgl_agenda = CURDATE() THEN 1 END) as hari_ini,
                COUNT(CASE WHEN tgl_agenda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as minggu_ini,
                COUNT(CASE WHEN id_status = 4 THEN 1 END) as selesai
              FROM tb_agenda";
$stats_result = $koneksi->query($sql_stats);
$stats = $stats_result->fetch_assoc();

// Ambil agenda mendatang
$sql_upcoming = "SELECT a.*, p.nama_pejabat 
                 FROM tb_agenda a 
                 LEFT JOIN tb_pejabat p ON a.pejabat = p.id
                 WHERE a.tgl_agenda >= CURDATE() 
                 AND a.tgl_agenda <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY a.tgl_agenda, a.waktu 
                 LIMIT 5";
$upcoming_result = $koneksi->query($sql_upcoming);
?>

<?php
// Tampilkan notifikasi
if (isset($_GET['deleted'])) {
    echo '<div style="background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;">
            <i class="fas fa-check-circle"></i> Agenda berhasil dihapus beserta semua data terkait.
          </div>';
}

if (isset($_GET['tl_deleted'])) {
    echo '<div style="background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;">
            <i class="fas fa-check-circle"></i> Tindak lanjut berhasil dihapus.
          </div>';
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        <!-- Search Box -->
        <div style="margin: 20px 0;">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Cari agenda..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <button type="submit" style="padding: 10px 15px; background: #4361ee; color: white; border: none; border-radius: 4px;">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if (!empty($search)): ?>
                    <a href="index.php?filter=<?php echo $filter; ?>" style="margin-left: 10px; color: #666;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Dashboard Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0;">
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #666; font-size: 14px; margin-bottom: 10px;">Total Agenda</h3>
                <div style="font-size: 32px; font-weight: bold; color: #4361ee;"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #666; font-size: 14px; margin-bottom: 10px;">Hari Ini</h3>
                <div style="font-size: 32px; font-weight: bold; color: #28a745;"><?php echo $stats['hari_ini'] ?? 0; ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #666; font-size: 14px; margin-bottom: 10px;">Minggu Ini</h3>
                <div style="font-size: 32px; font-weight: bold; color: #17a2b8;"><?php echo $stats['minggu_ini'] ?? 0; ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #666; font-size: 14px; margin-bottom: 10px;">Selesai</h3>
                <div style="font-size: 32px; font-weight: bold; color: #ffc107;"><?php echo $stats['selesai'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Upcoming Events -->
        <?php if ($upcoming_result && $upcoming_result->num_rows > 0): ?>
            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 15px; color: #333;">
                    <i class="fas fa-clock"></i> Agenda Mendatang (7 Hari)
                </h3>
                <?php while ($upcoming = $upcoming_result->fetch_assoc()):
                    $tanggal = strtotime($upcoming['tgl_agenda']);
                ?>
                    <div style="display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                        <div style="min-width: 60px; text-align: center; margin-right: 15px;">
                            <div style="font-size: 24px; font-weight: bold; color: #4361ee;"><?php echo date('d', $tanggal); ?></div>
                            <div style="font-size: 12px; color: #666;"><?php echo $nama_bulan[date('n', $tanggal) - 1]; ?></div>
                        </div>
                        <div style="flex-grow: 1;">
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($upcoming['nama_kegiatan']); ?></div>
                            <div style="font-size: 14px; color: #666; margin-top: 5px;">
                                <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($upcoming['waktu'])); ?>
                                | <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($upcoming['tempat_kegiatan']); ?>
                                | <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($upcoming['nama_pejabat']); ?>
                            </div>
                        </div>
                        <div>
                            <a href="edit.php?id=<?php echo $upcoming['id_agenda']; ?>"
                                style="padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- Main Table -->
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
                        <th>Hasil</th>
                        <th>Tindak Lanjut</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            $hasil_dicapai = $row['hasil_agenda'] ?? '';
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
                                <td><?php echo htmlspecialchars($row['nama_jabatan'] . ' (' . $row['nama_pejabat'] . ')'); ?></td>
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

                                        <?php if (!empty($row['isi_tindaklanjut'])): ?>
                                            <i class="fas fa-lock" style="margin-left: 5px; font-size: 10px;"
                                                title="Status terkunci karena sudah ada tindak lanjut"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($hasil_dicapai, 0, 50)); ?></td>
                                <td>
                                    <?php if (!empty($row['isi_tindaklanjut'])): ?>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="tindaklanjut.php?id=<?php echo $row['id_agenda']; ?>"
                                                class="button-aksi tindaklanjut"
                                                title="Edit Tindak Lanjut">
                                                <i class="fas fa-edit"></i> Edit TL
                                            </a>
                                            <span style="font-size: 12px; color: #666; margin-left: 5px;">
                                                (<?php echo date('d/m', strtotime($row['tgl_tindaklanjut'] ?? '')); ?>)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <a href="tindaklanjut.php?id=<?php echo $row['id_agenda']; ?>"
                                            class="button-aksi tindaklanjut">
                                            <i class="fas fa-plus"></i> TL
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi edit">Edit</a>
                                    <a href="hapus.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi hapus" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='13' style='text-align: center; padding: 20px; color: #666;'>
                                <i class='fas fa-calendar-times' style='font-size: 48px; margin-bottom: 10px; color: #ddd; display: block;'></i>
                                Tidak ada data agenda yang ditemukan.
                              </td></tr>";
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
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                }
            });
        });
    </script>
</body>

</html>

<?php
// Close database connection (MySQLi)
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $koneksi->close();
}
?>