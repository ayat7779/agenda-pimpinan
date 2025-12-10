<?php
// index.php - Updated with authentication
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== SESSION & AUTH CHECK =====
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
include __DIR__ . '/koneksi.php';

// DI AWAL FILE (setelah koneksi database)
error_reporting(E_ALL);
ini_set('display_errors', 1);



// ===== HELPER FUNCTIONS =====
function checkPermission($requiredRole)
{
    $userRole = $_SESSION['role'] ?? 'staff';
    $roleHierarchy = [
        'super_admin' => 4,
        'admin' => 3,
        'pimpinan' => 2,
        'staff' => 1
    ];

    return isset($roleHierarchy[$userRole]) &&
        $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
}

function can($action)
{
    $role = $_SESSION['role'] ?? 'staff';
    $permissions = [
        'super_admin' => ['view_all', 'create', 'edit', 'delete', 'export', 'manage_users'],
        'admin' => ['view_all', 'create', 'edit', 'delete', 'export'],
        'pimpinan' => ['view_own', 'create', 'edit_own', 'view_tindaklanjut', 'create_tindaklanjut'],
        'staff' => ['view_own', 'view_tindaklanjut']
    ];

    return in_array($action, $permissions[$role] ?? []);
}

// ===== ARRAY NAMA BULAN =====
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

// ===== FILTER LOGIC DENGAN ROLE-BASED =====
$where_clause = "";
$filter = $_GET['filter'] ?? 'semua';
$search = $_GET['search'] ?? '';

// Role-based filtering
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'staff';
$userFullName = $_SESSION['full_name'] ?? '';

var_dump($userId, $userRole, $userFullName);


// Base where clause berdasarkan role
if ($userRole === 'staff') {
    // Staff hanya bisa lihat agenda yang mereka buat ATAU yang mereka jadi penanggung jawab
    $where_clause = "WHERE (a.created_by = $userId OR 
                           a.penanggungjawab_kegiatan LIKE '%$userFullName%')";
} elseif ($userRole === 'pimpinan') {
    // Pimpinan bisa lihat semua agenda di departemennya atau yang melibatkan dia
    // CARA 1: Jika pejabat berisi ID
    // $where_clause = "WHERE (a.created_by = $userId OR a.pejabat = $userId)";

    // CARA 2: Jika pejabat berisi nama (sesuai struktur varchar)
    $where_clause = "WHERE (a.created_by = $userId OR 
                           a.pejabat LIKE '%$userFullName%' OR
                           a.pejabat IN (
                               SELECT CONCAT(nama_jabatan, ' (', nama_pejabat, ')') 
                               FROM tb_pejabat 
                               WHERE nama_pejabat LIKE '%$userFullName%'
                           ))";
} else {
    // Admin & Super Admin bisa lihat semua
    $where_clause = "WHERE 1=1";
}

// Tambahkan filter tanggal jika ada
if ($filter === 'hari_ini') {
    $where_clause .= " AND DATE(a.tgl_agenda) = CURDATE()";
} elseif ($filter === 'bulan_ini') {
    $where_clause .= " AND MONTH(a.tgl_agenda) = MONTH(CURDATE()) 
                       AND YEAR(a.tgl_agenda) = YEAR(CURDATE())";
} elseif ($filter === 'tahun_ini') {
    $where_clause .= " AND YEAR(a.tgl_agenda) = YEAR(CURDATE())";
}

// Tambahkan pencarian jika ada
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (a.nama_kegiatan LIKE '%$search%' 
                           OR a.tempat_kegiatan LIKE '%$search%'
                           OR a.penanggungjawab_kegiatan LIKE '%$search%'
                           OR p.nama_pejabat LIKE '%$search%')";
}

// ===== QUERY AGENDA =====
$sql = "SELECT 
            a.*, 
            b.nama_status,
            c.isi_tindaklanjut,
            c.tgl_tindaklanjut,
            p.nama_pejabat,
            p.nama_jabatan,
            u.full_name as created_by_name
        FROM 
            tb_agenda AS a
        LEFT JOIN 
            tb_status AS b ON a.id_status = b.id_status 
        LEFT JOIN 
            tb_tindaklanjut AS c ON a.id_agenda = c.id_agenda
        LEFT JOIN 
            tb_pejabat AS p ON a.pejabat = p.id    
        LEFT JOIN
            tb_users AS u ON a.created_by = u.id
        $where_clause
        ORDER BY 
            a.tgl_agenda DESC, 
            a.waktu DESC";

$result = $koneksi->query($sql);

// ===== STATISTICS =====
$sql_stats = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN tgl_agenda = CURDATE() THEN 1 END) as hari_ini,
                COUNT(CASE WHEN tgl_agenda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as minggu_ini,
                COUNT(CASE WHEN id_status = (SELECT id_status FROM tb_status WHERE nama_status = 'Selesai') THEN 1 END) as selesai
              FROM tb_agenda";

// Tambahkan filter role untuk stats
if ($userRole === 'staff') {
    $sql_stats .= " WHERE created_by = $userId";
} elseif ($userRole === 'pimpinan') {
    $sql_stats .= " WHERE pejabat IN (SELECT id FROM tb_pejabat WHERE nama_pejabat LIKE '%" . $_SESSION['full_name'] . "%')";
}

$stats_result = $koneksi->query($sql_stats);
$stats = $stats_result->fetch_assoc();

// ===== UPCOMING EVENTS =====
$sql_upcoming = "SELECT a.*, p.nama_pejabat 
                 FROM tb_agenda a 
                 LEFT JOIN tb_pejabat p ON a.pejabat = p.id
                 WHERE a.tgl_agenda >= CURDATE() 
                 AND a.tgl_agenda <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";

// Filter berdasarkan role
if ($userRole === 'staff') {
    $sql_upcoming .= " AND (a.created_by = $userId OR a.penanggungjawab_kegiatan LIKE '%" . $_SESSION['full_name'] . "%')";
} elseif ($userRole === 'pimpinan') {
    $sql_upcoming .= " AND (a.created_by = $userId OR a.pejabat IN (
        SELECT id FROM tb_pejabat WHERE nama_pejabat LIKE '%" . $_SESSION['full_name'] . "%'
    ))";
}

$sql_upcoming .= " ORDER BY a.tgl_agenda, a.waktu LIMIT 5";
$upcoming_result = $koneksi->query($sql_upcoming);
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

    <style>
        /* Additional styles for user info */
        .user-info-bar {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .user-info-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #4361ee;
            font-size: 18px;
        }

        .user-details h3 {
            margin: 0;
            font-size: 18px;
        }

        .user-details p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .user-actions {
            display: flex;
            gap: 10px;
        }

        .btn-user-action {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-user-action:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Role badge */
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .role-super_admin {
            background: #ff6b6b;
            color: white;
        }

        .role-admin {
            background: #4ecdc4;
            color: white;
        }

        .role-pimpinan {
            background: #45b7d1;
            color: white;
        }

        .role-staff {
            background: #96ceb4;
            color: white;
        }

        /* Hide actions based on permissions */
        .action-hidden {
            display: none !important;
        }

        .no-data-row td {
            text-align: center !important;
        }

        .no-sorting {
            pointer-events: none;
        }
    </style>
</head>

<body>
    <!-- User Info Bar -->
    <div class="user-info-bar">
        <div class="user-info-left">
            <div class="user-avatar">
                <?php
                $initials = '';
                $name = $_SESSION['full_name'] ?? 'User';
                $words = explode(' ', $name);
                foreach ($words as $word) {
                    $initials .= strtoupper($word[0]);
                    if (strlen($initials) >= 2) break;
                }
                echo $initials ?: 'U';
                ?>
            </div>
            <div class="user-details">
                <h3>
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                    <span class="role-badge role-<?php echo $_SESSION['role'] ?? 'staff'; ?>">
                        <?php
                        $roleNames = [
                            'super_admin' => 'Super Admin',
                            'admin' => 'Admin',
                            'pimpinan' => 'Pimpinan',
                            'staff' => 'Staff'
                        ];
                        echo $roleNames[$_SESSION['role'] ?? 'staff'];
                        ?>
                    </span>
                </h3>
                <p><i class="far fa-clock"></i> Terakhir login: <?php echo date('d M Y H:i'); ?></p>
            </div>
        </div>

        <div class="user-actions">
            <a href="profile.php" class="btn-user-action">
                <i class="fas fa-user"></i> Profil
            </a>
            <a href="logout.php" class="btn-user-action" onclick="return confirm('Yakin ingin logout?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    <div class="container">
        <h2>Daftar Agenda Pimpinan</h2>

        <!-- Notifikasi -->
        <?php
        if (isset($_GET['deleted'])) {
            echo '<div style="background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;">
                    <i class="fas fa-check-circle"></i> Agenda berhasil dihapus beserta semua data terkait.
                  </div>';
        }

        if (isset($_GET['success'])) {
            echo '<div style="background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;">
                    <i class="fas fa-check-circle"></i> Operasi berhasil dilakukan.
                  </div>';
        }
        ?>

        <div class="action-and-filter-container">
            <div class="filter-buttons">
                <a href="index.php?filter=semua" class="button-filter <?php echo ($filter === 'semua') ? 'active' : ''; ?>">Semua</a>
                <a href="index.php?filter=hari_ini" class="button-filter <?php echo ($filter === 'hari_ini') ? 'active' : ''; ?>">Hari Ini</a>
                <a href="index.php?filter=bulan_ini" class="button-filter <?php echo ($filter === 'bulan_ini') ? 'active' : ''; ?>">Bulan Ini</a>
                <a href="index.php?filter=tahun_ini" class="button-filter <?php echo ($filter === 'tahun_ini') ? 'active' : ''; ?>">Tahun Ini</a>
            </div>

            <?php if (can('create')): ?>
                <a href="tambah.php" class="button-tambah">Tambah Agenda Baru</a>
            <?php endif; ?>
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
                            <?php if (can('edit_own') && ($upcoming['created_by'] == $userId || $userRole === 'admin' || $userRole === 'super_admin')): ?>
                                <a href="edit.php?id=<?php echo $upcoming['id_agenda']; ?>"
                                    style="padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
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

                            // Permission checks
                            $canEdit = can('edit') || (can('edit_own') && $row['created_by'] == $userId);
                            $canDelete = can('delete');
                            $canTL = can('create_tindaklanjut') || can('view_tindaklanjut');
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
                                        <?php if ($canTL): ?>
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
                                            <span class="text-muted">Ada TL</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($canTL): ?>
                                            <a href="tindaklanjut.php?id=<?php echo $row['id_agenda']; ?>"
                                                class="button-aksi tindaklanjut">
                                                <i class="fas fa-plus"></i> TL
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canEdit): ?>
                                        <a href="edit.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi edit">Edit</a>
                                    <?php endif; ?>

                                    <?php if ($canDelete && ($userRole === 'admin' || $userRole === 'super_admin')): ?>
                                        <a href="hapus.php?id=<?php echo htmlspecialchars($row['id_agenda']); ?>" class="button-aksi hapus" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr>";
                        echo "<td colspan='13' style='text-align: center; padding: 20px; color: #666;'>";
                        echo "<i class='fas fa-calendar-times' style='font-size: 48px; margin-bottom: 10px; color: #ddd; display: block;'></i>";
                        echo "Tidak ada data agenda yang ditemukan.";
                        echo "</td>";
                        // Tambahkan 12 td kosong lainnya
                        for ($i = 1; $i <= 12; $i++) {
                            echo "<td></td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- User Info Footer -->
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; color: #666; font-size: 14px;">
            <p>
                Login sebagai: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> |
                Role: <strong><?php echo $roleNames[$_SESSION['role'] ?? 'staff']; ?></strong> |
                Session: <?php echo session_id(); ?>
            </p>
            <p style="font-size: 12px; margin-top: 5px;">
                Sistem Agenda Pimpinan v2.0 &copy; <?php echo date('Y'); ?>
            </p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#agendaTable').DataTable({
                responsive: true,
                columnDefs: [{
                        orderable: false,
                        targets: [12]
                    } // Disable sorting on the Actions column
                ],
            });
        });
    </script>
</body>

</html>

<?php
// Close database connection
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $koneksi->close();
}
?>