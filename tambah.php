<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';
$pesan_error = ""; // Inisialisasi variabel pesan error

$kegiatan_err = $tempat_err = $penanggungjawab_err = $pakaian_err = $pejabat_err = $lampiran_err = $hasil_agenda_err = $status_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tgl_agenda = $_POST['tgl_agenda'];
    $waktu = $_POST['waktu'];
    $nama_kegiatan = $_POST['nama_kegiatan'];
    $tempat_kegiatan = $_POST['tempat_kegiatan'];
    $penanggungjawab_kegiatan = $_POST['penanggungjawab_kegiatan'];
    $pakaian_kegiatan = $_POST['pakaian_kegiatan'];
    $pejabat = $_POST['pejabat'];
    $hasil_agendai = $_POST['hasil_agenda'];
    $status = $_POST['status'];
    
    $lampiran = "";
    $uploadOk = 1;
    $max_filesize = 2097152; // 2MB dalam byte

    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $file_size = $_FILES['lampiran']['size'];
        
        // Cek ukuran file
        if ($file_size > $max_filesize) {
            $pesan_error = "Ukuran file melebihi 2MB. Mohon unggah file yang lebih kecil.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            $target_dir = "uploads/";
            $nama_file = basename($_FILES["lampiran"]["name"]);
            $target_file = $target_dir . $nama_file;
            
            if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_file)) {
                $lampiran = $nama_file;
            } else {
                $pesan_error = "Terjadi kesalahan saat mengunggah file. Kode error: " . $_FILES['lampiran']['error'];
            }
        }
    }
    
    if (empty($pesan_error)) {
        $sql = "INSERT INTO tb_agenda (tgl_agenda, waktu, nama_kegiatan, tempat_kegiatan, penanggungjawab_kegiatan, pakaian_kegiatan, pejabat, lampiran, hasil_agenda, id_status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssssssssi", $tgl_agenda, $waktu, $nama_kegiatan, $tempat_kegiatan, $penanggungjawab_kegiatan, $pakaian_kegiatan, $pejabat, $lampiran, $hasil_agenda, $status);

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $pesan_error = "Error saat menyimpan data ke database: " . $stmt->error;
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
    <title>Tambah Agenda</title>
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
        <h2>Tambah Agenda Baru</h2>
        <?php if (!empty($pesan_error)): ?>
            <p class="error"><?php echo $pesan_error; ?></p>
        <?php endif; ?>

        <form id="formAgenda" action="tambah.php" method="post" enctype="multipart/form-data">
            <label for="tgl_agenda">Tanggal Agenda:</label>
            <input type="date" name="tgl_agenda" id="tgl_agenda_input" required>
            
            <label for="waktu">Waktu:</label>
            <input type="time" name="waktu" required>

            <label for="nama_kegiatan">Nama Kegiatan:</label>
            <input type="text" name="nama_kegiatan" required>

            <label for="tempat_kegiatan">Tempat Kegiatan:</label>
            <input type="text" name="tempat_kegiatan" required>

            <label for="penanggungjawab_kegiatan">Penanggung Jawab:</label>
            <input type="text" name="penanggungjawab_kegiatan" required>
            
            <label for="pakaian_kegiatan">Pakaian:</label>
            <input type="text" name="pakaian_kegiatan" required>
            
            <label for="pejabat">Pejabat:</label>
            <input type="text" name="pejabat" required>

            <label for="lampiran">Lampiran:</label>
            <input type="file" name="lampiran" id="lampiran">
            <p id="pesan_ukuran_file" style="color: #dc3545; display: none;">Tidak bisa diupload karena ukuran file melebihi 2MB.</p>
            <p id="ukuran-file" style="margin-top: 5px; font-size: 14px; color: #555;"></p>
            
            <label for="hasil_agenda">Hasil yang Dicapai:</label>
            <textarea name="hasil_agenda" id="hasil_agenda"></textarea>

            <label for="status">Status:</label>
            <select name="status" id="status" required>
                <?php
                if ($result_status && $result_status->num_rows > 0) {
                    while($row_status = $result_status->fetch_assoc()) {
                        echo "<option value='" . $row_status['id_status'] . "'>" . $row_status['nama_status'] . "</option>";
                    }
                } else {
                    echo "<option value=''>Tidak ada status</option>";
                }
                ?>
            </select>

            <button type="submit">Simpan Agenda</button>
            <a href="index.php" class="button-kembali">Kembali</a>
        </form>
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