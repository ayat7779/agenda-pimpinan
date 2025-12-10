<?php
// hapus.php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/koneksi.php';

// Jika metode POST (konfirmasi hapus)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_agenda'])) {
    $id_agenda = (int)$_POST['id_agenda'];

    // Mulai transaksi
    $koneksi->begin_transaction();

    try {
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px; border-radius: 5px;'>";
        echo "<h3>Proses Penghapusan:</h3>";

        // 1. Hapus file lampiran tindak lanjut
        $sql_tindaklanjut = "SELECT id_tindaklanjut, lampiran FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
        $result_tindaklanjut = $koneksi->query($sql_tindaklanjut);
        $count_tindaklanjut = $result_tindaklanjut->num_rows;

        echo "<p>Menghapus $count_tindaklanjut data tindak lanjut...</p>";

        while ($row = $result_tindaklanjut->fetch_assoc()) {
            if (!empty($row['lampiran']) && file_exists('uploads/' . $row['lampiran'])) {
                if (unlink('uploads/' . $row['lampiran'])) {
                    echo "<p style='color:green'>✓ File tindak lanjut dihapus: " . $row['lampiran'] . "</p>";
                }
            }
        }

        // 2. Hapus data tindak lanjut
        $delete_tindaklanjut = "DELETE FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
        if ($koneksi->query($delete_tindaklanjut)) {
            echo "<p style='color:green'>✓ Data tindak lanjut dihapus ($count_tindaklanjut records)</p>";
        }

        // 3. Hapus file lampiran agenda
        $sql_agenda = "SELECT lampiran, nama_kegiatan FROM tb_agenda WHERE id_agenda = $id_agenda";
        $result_agenda = $koneksi->query($sql_agenda);

        if ($result_agenda->num_rows > 0) {
            $agenda = $result_agenda->fetch_assoc();
            $nama_kegiatan = htmlspecialchars($agenda['nama_kegiatan']);

            if (!empty($agenda['lampiran']) && file_exists('uploads/' . $agenda['lampiran'])) {
                if (unlink('uploads/' . $agenda['lampiran'])) {
                    echo "<p style='color:green'>✓ File agenda dihapus: " . $agenda['lampiran'] . "</p>";
                }
            }

            // 4. Hapus agenda itu sendiri
            $delete_agenda = "DELETE FROM tb_agenda WHERE id_agenda = $id_agenda";
            if ($koneksi->query($delete_agenda)) {
                echo "<p style='color:green'>✓ Agenda '$nama_kegiatan' dihapus</p>";
            }
        }

        // Commit transaksi
        $koneksi->commit();

        echo "</div>";
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 15px; border-radius: 5px;'>
                <h3><i class='fas fa-check-circle'></i> Penghapusan Berhasil!</h3>
                <p>Semua data terkait agenda berhasil dihapus.</p>
                <p><a href='index.php'>Kembali ke daftar agenda</a></p>
              </div>";

        // Auto redirect setelah 3 detik
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 3000);
              </script>";
    } catch (Exception $e) {
        // Rollback jika ada error
        $koneksi->rollback();
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;'>
                <h3><i class='fas fa-exclamation-triangle'></i> Error!</h3>
                <p>Gagal menghapus: " . $e->getMessage() . "</p>
                <p><a href='index.php'>Kembali</a></p>
              </div>";
    }

    $koneksi->close();
    exit();
}

// Jika metode GET (tampilkan konfirmasi)
if (isset($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];

    // Ambil data agenda dan tindak lanjut untuk ditampilkan
    $sql_agenda = "SELECT a.*, p.nama_pejabat, p.nama_jabatan 
                   FROM tb_agenda a 
                   LEFT JOIN tb_pejabat p ON a.pejabat = p.id 
                   WHERE a.id_agenda = $id_agenda";
    $result_agenda = $koneksi->query($sql_agenda);

    if ($result_agenda->num_rows === 0) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;'>
                Data agenda tidak ditemukan. <a href='index.php'>Kembali</a>
              </div>";
        exit;
    }

    $agenda = $result_agenda->fetch_assoc();

    // Cek apakah ada tindak lanjut
    $sql_tindaklanjut = "SELECT COUNT(*) as total FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
    $result_tl = $koneksi->query($sql_tindaklanjut);
    $count_tl = $result_tl->fetch_assoc()['total'];
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Konfirmasi Hapus Agenda</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <style>
            .container {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
            }

            .warning-box {
                background: #fff3cd;
                border: 1px solid #ffecb5;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
            }

            .warning-icon {
                font-size: 48px;
                color: #ffc107;
                margin-bottom: 15px;
            }

            .agenda-details {
                background: white;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .detail-row {
                display: flex;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .detail-label {
                min-width: 150px;
                font-weight: bold;
                color: #333;
            }

            .tindaklanjut-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                margin: 15px 0;
                border-left: 4px solid #dc3545;
            }

            .form-actions {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 30px;
            }

            .btn {
                padding: 12px 25px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 16px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .btn-danger {
                background: #dc3545;
                color: white;
            }

            .btn-secondary {
                background: #6c757d;
                color: white;
            }

            .btn:hover {
                opacity: 0.9;
                transform: translateY(-2px);
                transition: all 0.3s ease;
            }

            .what-will-be-deleted {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                margin: 20px 0;
            }

            .delete-item {
                display: flex;
                align-items: center;
                margin: 8px 0;
            }

            .delete-item i {
                margin-right: 10px;
                width: 20px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="warning-box">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2>Konfirmasi Penghapusan Agenda</h2>
                <p>Apakah Anda yakin ingin menghapus agenda ini? Tindakan ini <strong>TIDAK DAPAT DIBATALKAN</strong>.</p>
            </div>

            <div class="agenda-details">
                <h3>Detail Agenda yang akan dihapus:</h3>

                <div class="detail-row">
                    <span class="detail-label">Nama Kegiatan:</span>
                    <span><?php echo htmlspecialchars($agenda['nama_kegiatan']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tanggal:</span>
                    <span><?php echo date('d F Y', strtotime($agenda['tgl_agenda'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Waktu:</span>
                    <span><?php echo date('H:i', strtotime($agenda['waktu'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tempat:</span>
                    <span><?php echo htmlspecialchars($agenda['tempat_kegiatan']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Pejabat:</span>
                    <span><?php echo htmlspecialchars($agenda['nama_jabatan'] . ' - ' . $agenda['nama_pejabat']); ?></span>
                </div>

                <?php if (!empty($agenda['lampiran'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Lampiran:</span>
                        <span>
                            <a href="uploads/<?php echo htmlspecialchars($agenda['lampiran']); ?>" target="_blank">
                                <i class="fas fa-file"></i> <?php echo htmlspecialchars($agenda['lampiran']); ?>
                            </a>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($count_tl > 0): ?>
                    <div class="tindaklanjut-info">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Peringatan:</strong> Agenda ini memiliki
                        <span style="color: #dc3545; font-weight: bold;"><?php echo $count_tl; ?> data tindak lanjut</span>
                        yang juga akan terhapus!
                    </div>
                <?php endif; ?>
            </div>

            <div class="what-will-be-deleted">
                <h4>Yang akan dihapus:</h4>

                <div class="delete-item">
                    <i class="fas fa-calendar-times" style="color: #dc3545;"></i>
                    <span>Data agenda: <strong><?php echo htmlspecialchars($agenda['nama_kegiatan']); ?></strong></span>
                </div>

                <?php if (!empty($agenda['lampiran'])): ?>
                    <div class="delete-item">
                        <i class="fas fa-file" style="color: #dc3545;"></i>
                        <span>File lampiran: <?php echo htmlspecialchars($agenda['lampiran']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($count_tl > 0): ?>
                    <div class="delete-item">
                        <i class="fas fa-tasks" style="color: #dc3545;"></i>
                        <span><?php echo $count_tl; ?> data tindak lanjut terkait</span>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="hapus.php" onsubmit="return confirmDelete()">
                <input type="hidden" name="id_agenda" value="<?php echo $id_agenda; ?>">

                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Ya, Hapus Semua Data
                    </button>
                </div>
            </form>
        </div>

        <script>
            function confirmDelete() {
                const countTL = <?php echo $count_tl; ?>;
                let message = "Apakah Anda benar-benar yakin ingin menghapus agenda ini?";

                if (countTL > 0) {
                    message += `\n\nPERINGATAN: ${countTL} data tindak lanjut juga akan terhapus!`;
                }

                message += "\n\nTindakan ini TIDAK DAPAT DIBATALKAN.";

                return confirm(message);
            }

            // Prevent accidental navigation
            window.addEventListener('beforeunload', function(e) {
                return 'Anda sedang dalam proses penghapusan. Data akan hilang jika Anda meninggalkan halaman ini.';
            });
        </script>
    </body>

    </html>
<?php
    $koneksi->close();
} else {
    header("Location: index.php");
    exit();
}
?>