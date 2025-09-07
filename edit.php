<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

$pesan_error = "";
$agenda = null;
$max_filesize = 2097152; // 2MB dalam byte

// Bagian 1: Ambil data agenda yang akan diedit dari database
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_agenda = $_GET['id'];
    $sql = "SELECT * FROM tb_agenda WHERE id_agenda = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id_agenda);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $agenda = $result->fetch_assoc();
    } else {
        $pesan_error = "Data agenda tidak ditemukan.";
    }
    $stmt->close();
} else {
    $pesan_error = "ID agenda tidak valid.";
}

// Bagian 2: Proses data formulir saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_agenda = $_POST['id_agenda'];
    $tgl_agenda = $_POST['tgl_agenda'];
    $waktu = $_POST['waktu'];
    $nama_kegiatan = $_POST['nama_kegiatan'];
    $tempat_kegiatan = $_POST['tempat_kegiatan'];
    $penanggungjawab_kegiatan = $_POST['penanggungjawab_kegiatan'];
    $pakaian_kegiatan = $_POST['pakaian_kegiatan'];
    $pejabat = $_POST['pejabat'];
    $hasil_agenda = $_POST['hasil_agenda'];
    $status = $_POST['status'];
    $lampiran_lama = $_POST['lampiran_lama'];
    
    $lampiran = $lampiran_lama;
    $uploadOk = 1;

    // Cek apakah ada file baru yang diunggah
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $file_size = $_FILES['lampiran']['size'];
        
        if ($file_size > $max_filesize) {
            $pesan_error = "Ukuran file melebihi 2MB. Mohon unggah file yang lebih kecil.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            $target_dir = "uploads/";
            $nama_file = basename($_FILES["lampiran"]["name"]);
            $target_file = $target_dir . $nama_file;
            
            if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_file)) {
                $lampiran = $nama_file; // Update nama lampiran
                // Hapus file lama jika ada
                if (!empty($lampiran_lama) && file_exists($target_dir . $lampiran_lama)) {
                    unlink($target_dir . $lampiran_lama);
                }
            } else {
                $pesan_error = "Terjadi kesalahan saat mengunggah file. Kode error: " . $_FILES['lampiran']['error'];
            }
        }
    }
    
    if (empty($pesan_error)) {
        $sql = "UPDATE tb_agenda SET 
                    tgl_agenda=?, 
                    waktu=?, 
                    nama_kegiatan=?, 
                    tempat_kegiatan=?, 
                    penanggungjawab_kegiatan=?, 
                    pakaian_kegiatan=?, 
                    pejabat=?, 
                    lampiran=?,
                    hasil_agenda=?,
                    id_status=?
                WHERE id_agenda=?";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssssssssii", 
            $tgl_agenda, 
            $waktu, 
            $nama_kegiatan, 
            $tempat_kegiatan, 
            $penanggungjawab_kegiatan, 
            $pakaian_kegiatan, 
            $pejabat, 
            $lampiran, 
            $hasil_agenda,
            $status,
            $id_agenda);

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $pesan_error = "Error saat memperbarui data ke database: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Ambil data status dari tabel tb_status untuk combobox
$sql_status = "SELECT * FROM tb_status ORDER BY nama_status ASC";
$result_status = $koneksi->query($sql_status);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Agenda</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Agenda</h2>
        <?php if (!empty($pesan_error)): ?>
            <p class="error"><?php echo $pesan_error; ?></p>
        <?php endif; ?>

        <?php if ($agenda): ?>
        <form id="formAgenda" action="edit.php?id=<?php echo htmlspecialchars($agenda['id_agenda']); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_agenda" value="<?php echo htmlspecialchars($agenda['id_agenda']); ?>">
            <input type="hidden" name="lampiran_lama" value="<?php echo htmlspecialchars($agenda['lampiran']); ?>">
            
            <label for="tgl_agenda">Tanggal Agenda:</label>
            <input type="date" name="tgl_agenda" id="tgl_agenda_input" value="<?php echo htmlspecialchars($agenda['tgl_agenda']); ?>" required>
            
            <label for="waktu">Waktu:</label>
            <input type="time" name="waktu" value="<?php echo htmlspecialchars($agenda['waktu']); ?>" required>

            <label for="nama_kegiatan">Nama Kegiatan:</label>
            <input type="text" name="nama_kegiatan" value="<?php echo htmlspecialchars($agenda['nama_kegiatan']); ?>" required>

            <label for="tempat_kegiatan">Tempat Kegiatan:</label>
            <input type="text" name="tempat_kegiatan" value="<?php echo htmlspecialchars($agenda['tempat_kegiatan']); ?>" required>

            <label for="penanggungjawab_kegiatan">Penanggung Jawab:</label>
            <input type="text" name="penanggungjawab_kegiatan" value="<?php echo htmlspecialchars($agenda['penanggungjawab_kegiatan']); ?>" required>
            
            <label for="pakaian_kegiatan">Pakaian:</label>
            <input type="text" name="pakaian_kegiatan" value="<?php echo htmlspecialchars($agenda['pakaian_kegiatan']); ?>" required>
            
            <label for="pejabat">Pejabat:</label>
            <input type="text" name="pejabat" value="<?php echo htmlspecialchars($agenda['pejabat']); ?>" required>

            <label for="lampiran">Lampiran:</label>
            <input type="file" name="lampiran" id="lampiran">
            <?php if (!empty($agenda['lampiran'])): ?>
                <p>File saat ini: <a href="uploads/<?php echo htmlspecialchars($agenda['lampiran']); ?>" target="_blank"><?php echo htmlspecialchars($agenda['lampiran']); ?></a></p>
            <?php endif; ?>
            <p id="pesan_ukuran_file" style="color: #dc3545; display: none;">Ukuran file melebihi 2MB.</p>
            <p id="ukuran-file" style="margin-top: 5px; font-size: 14px; color: #555;"></p>
            
            <label for="hasil_agenda">Hasil yang Dicapai:</label>
            <textarea name="hasil_agenda" id="hasil_agenda"><?php echo htmlspecialchars($agenda['hasil_agenda']); ?></textarea>

            <label for="status">Status:</label>
            <select name="status" id="status" required>
                <?php
                if ($result_status && $result_status->num_rows > 0) {
                    while($row_status = $result_status->fetch_assoc()) {
                        $selected = ($row_status['id_status'] == $agenda['id_status']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row_status['id_status']) . "' " . $selected . ">" . htmlspecialchars($row_status['nama_status']) . "</option>";
                    }
                } else {
                    echo "<option value=''>Tidak ada status</option>";
                }
                ?>
            </select>

            <button type="submit">Update Agenda</button>
            <a href="index.php" class="button-kembali">Batal</a>
        </form>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('lampiran').addEventListener('change', function() {
            const fileInput = this;
            const ukuranFileElement = document.getElementById('ukuran-file');
            const pesanErrorElement = document.getElementById('pesan_ukuran_file');
            const submitButton = document.querySelector('button[type="submit"]');

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileSize = file.size; // Ukuran dalam byte
                const maxFileSize = 2 * 1024 * 1024; // 2MB dalam byte

                // Konversi ukuran ke format yang mudah dibaca (KB atau MB)
                let ukuranTampil;
                if (fileSize > 1024 * 1024) {
                    ukuranTampil = (fileSize / (1024 * 1024)).toFixed(2) + ' MB';
                } else {
                    ukuranTampil = (fileSize / 1024).toFixed(2) + ' KB';
                }

                ukuranFileElement.textContent = 'Ukuran file yang akan diupload: ' + ukuranTampil;

                // Validasi ukuran dan nonaktifkan tombol jika melebihi batas
                if (fileSize > maxFileSize) {
                    pesanErrorElement.style.display = 'block';
                    ukuranFileElement.style.color = '#dc3545';
                    submitButton.disabled = true;
                } else {
                    pesanErrorElement.style.display = 'none';
                    ukuranFileElement.style.color = '#555';
                    submitButton.disabled = false;
                }
            } else {
                // Jika tidak ada file yang dipilih
                ukuranFileElement.textContent = '';
                pesanErrorElement.style.display = 'none';
                submitButton.disabled = false;
            }
        });
    </script>
</body>
</html>

<?php $koneksi->close(); ?>