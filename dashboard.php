<?php
// dashboard.php
include 'koneksi.php';

// Ambil statistik
$today = date('Y-m-d');
$stats = [];

// Total agenda
$sql = "SELECT COUNT(*) as total FROM tb_agenda";
$result = $koneksi->query($sql);
$stats['total'] = $result->fetch_assoc()['total'];

// Agenda hari ini
$sql = "SELECT COUNT(*) as total FROM tb_agenda WHERE tgl_agenda = CURDATE()";
$result = $koneksi->query($sql);
$stats['today'] = $result->fetch_assoc()['total'];

// Agenda minggu ini
$sql = "SELECT COUNT(*) as total FROM tb_agenda 
        WHERE tgl_agenda BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()";
$result = $koneksi->query($sql);
$stats['week'] = $result->fetch_assoc()['total'];

// Status breakdown
$sql = "SELECT s.nama_status, COUNT(a.id_agenda) as jumlah 
        FROM tb_status s 
        LEFT JOIN tb_agenda a ON s.id_status = a.id_status 
        GROUP BY s.id_status";
$status_stats = $koneksi->query($sql);

// Agenda mendatang (5 hari ke depan)
$sql = "SELECT * FROM tb_agenda 
        WHERE tgl_agenda >= CURDATE() 
        AND tgl_agenda <= DATE_ADD(CURDATE(), INTERVAL 5 DAY)
        ORDER BY tgl_agenda, waktu 
        LIMIT 10";
$upcoming = $koneksi->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agenda Pimpinan</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .upcoming-list {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .upcoming-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .upcoming-item:last-child {
            border-bottom: none;
        }

        .upcoming-date {
            color: #007bff;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>📊 Dashboard Agenda Pimpinan</h2>

        <div class="action-and-filter-container">
            <a href="index.php" class="button-tambah">Lihat Semua Agenda</a>
            <a href="tambah.php" class="button-tambah" style="background-color: #28a745;">➕ Tambah Agenda</a>
        </div>

        <div class="dashboard-container">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3>Total Agenda</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3>Agenda Hari Ini</h3>
                <div class="number"><?php echo $stats['today']; ?></div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3>Minggu Ini</h3>
                <div class="number"><?php echo $stats['week']; ?></div>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h3>Pejabat Aktif</h3>
                <div class="number">
                    <?php
                    $sql = "SELECT COUNT(DISTINCT pejabat) as total FROM tb_agenda";
                    $result = $koneksi->query($sql);
                    echo $result->fetch_assoc()['total'];
                    ?>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-container">
                <h3>Distribusi Status Agenda</h3>
                <canvas id="statusChart"></canvas>
            </div>

            <div class="upcoming-list">
                <h3>Agenda Mendatang (5 Hari)</h3>
                <?php if ($upcoming->num_rows > 0): ?>
                    <?php while ($row = $upcoming->fetch_assoc()): ?>
                        <div class="upcoming-item">
                            <div>
                                <strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong>
                                <p style="margin: 5px 0 0 0; color: #666;">
                                    <?php echo date('H:i', strtotime($row['waktu'])); ?> •
                                    <?php echo htmlspecialchars($row['tempat_kegiatan']); ?>
                                </p>
                            </div>
                            <span class="upcoming-date">
                                <?php echo date('d M', strtotime($row['tgl_agenda'])); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">
                        Tidak ada agenda mendatang
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Chart untuk distribusi status
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php
                    $status_labels = [];
                    $status_data = [];
                    while ($stat = $status_stats->fetch_assoc()) {
                        $status_labels[] = "'" . $stat['nama_status'] . "'";
                        $status_data[] = $stat['jumlah'];
                    }
                    echo implode(',', $status_labels);
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(',', $status_data); ?>],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ],
                    hoverBackgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>

</html>