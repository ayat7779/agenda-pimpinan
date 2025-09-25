<?php
// Aktifkan mode ketat untuk error reporting, tapi matikan display_errors di lingkungan produksi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

$pesan_error = ""; 

// Validasi input
$kegiatan_err = $tempat_err = $penanggungjawab_err = $pakaian_err = $pejabat_err = $lampiran_err = $hasil_agenda_err = $status_err = "";

// Cek jika metode request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize dan validasi input dari formulir
    $tgl_agenda = filter_var($_POST['tgl_agenda'], FILTER_SANITIZE_STRING);
    $waktu = filter_var($_POST['waktu'], FILTER_SANITIZE_STRING);
    $nama_kegiatan = filter_var($_POST['nama_kegiatan'], FILTER_SANITIZE_STRING);
    $tempat_kegiatan = filter_var($_POST['tempat_kegiatan'], FILTER_SANITIZE_STRING);
    $penanggungjawab_kegiatan = filter_var($_POST['penanggungjawab_kegiatan'], FILTER_SANITIZE_STRING);
    $pakaian_kegiatan = filter_var($_POST['pakaian_kegiatan'], FILTER_SANITIZE_STRING);
    $pejabat = filter_var($_POST['pejabat'], FILTER_SANITIZE_STRING);
    $hasil_agenda = filter_var($_POST['hasil_agenda'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_NUMBER_INT);

    // Proses unggah file
    $lampiran = "";
    $uploadOk = 1;
    $max_filesize = 2097152; // 2MB dalam byte

    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $file_size = $_FILES['lampiran']['size'];
        $file_info = pathinfo($_FILES["lampiran"]["name"]);
        $file_extension = strtolower($file_info['extension']);
        $allowed_extensions = array("pdf", "doc", "docx", "jpg", "jpeg", "png");

        // Cek ukuran file
        if ($file_size > $max_filesize) {
            $pesan_error = "Ukuran file melebihi 2MB. Mohon unggah file yang lebih kecil.";
            $uploadOk = 0;
        }

        // Cek ekstensi file
        if (!in_array($file_extension, $allowed_extensions)) {
            $pesan_error = "Tipe file tidak diizinkan. Hanya PDF, Word, dan gambar yang diperbolehkan.";
            $uploadOk = 0;
        }

        // Cek jika ada error
        if ($uploadOk == 1) {
            $target_dir = "uploads/";
            // Buat nama file unik untuk mencegah overwriting dan eksekusi skrip
            $unique_filename = md5(uniqid(rand(), true)) . '.' . $file_extension;
            $target_file = $target_dir . $unique_filename;
            
            // Pastikan direktori 'uploads' ada dan memiliki izin tulis
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            if (move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_file)) {
                $lampiran = $unique_filename;
            } else {
                $pesan_error = "Terjadi kesalahan saat mengunggah file. Kode error: " . $_FILES['lampiran']['error'];
            }
        }
    }
    
    if (empty($pesan_error)) {
        // Gunakan prepared statements untuk mencegah SQL Injection
        $sql = "INSERT INTO tb_agenda (tgl_agenda, waktu, nama_kegiatan, tempat_kegiatan, penanggungjawab_kegiatan, pakaian_kegiatan, pejabat, lampiran, hasil_agenda, id_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $koneksi->prepare($sql);
        
        // Periksa apakah prepared statement berhasil
        if ($stmt) {
            $stmt->bind_param("sssssssssi", $tgl_agenda, $waktu, $nama_kegiatan, $tempat_kegiatan, $penanggungjawab_kegiatan, $pakaian_kegiatan, $pejabat, $lampiran, $hasil_agenda, $status);

            if ($stmt->execute()) {
                header("Location: index.php");
                exit();
            } else {
                $pesan_error = "Error saat menyimpan data ke database: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $pesan_error = "Error saat mempersiapkan statement: " . $koneksi->error;
        }
    }
}

// Ambil data status dari tabel tb_status untuk combobox
$sql_status = "SELECT * FROM tb_status ORDER BY nama_status ASC";
$result_status = $koneksi->query($sql_status);

// Ambil data pejabat dari tabel tb_pejabat untuk combobox
$sql_pejabat = "SELECT * FROM tb_pejabat ORDER BY kode_pejabat ASC";
$result_pejabat = $koneksi->query($sql_pejabat);
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
            <p class="error"><?php echo htmlspecialchars($pesan_error); ?></p>
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
            
            <!-- <label for="pejabat">Pejabat:</label>
            <input type="text" name="pejabat" required> -->

            <label for="pejabat">Pejabat:</label>
            <select name="pejabat" id="pejabat" required>
                <option value="">-- Pilih Pejabat --</option>
                <?php
                if ($result_pejabat && $result_pejabat->num_rows > 0) {
                    while($row_pejabat = $result_pejabat->fetch_assoc()) {
                        echo "<option value='" . $row_pejabat['id'] . "'>" . $row_pejabat['nama_jabatan'] . "</option>";
                    }
                } else {
                    echo "<option value=''>Tidak ada pejabat</option>";
                }
                ?>
            </select>

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
                        // Lakukan HTML escaping untuk mencegah XSS
                        echo "<option value='" . htmlspecialchars($row_status['id_status']) . "'>" . htmlspecialchars($row_status['nama_status']) . "</option>";
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