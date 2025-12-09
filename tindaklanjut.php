<?php
// tindaklanjut.php - Dengan fitur edit tindak lanjut
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

// Simple session
session_start();

// Pastikan id_agenda ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px;'>
            ID Agenda tidak ditemukan. <a href='index.php'>Kembali</a>
          </div>";
    exit;
}

$id_agenda = (int)$_GET['id'];
$message = "";
$is_edit = false;
$tindaklanjut_data = null;

// Cek apakah sudah ada data tindak lanjut
$sql_check = "SELECT * FROM tb_tindaklanjut WHERE id_agenda = $id_agenda ORDER BY tgl_tindaklanjut DESC LIMIT 1";
$result_check = $koneksi->query($sql_check);

if ($result_check->num_rows > 0) {
    $is_edit = true;
    $tindaklanjut_data = $result_check->fetch_assoc();
}

// Cek apakah form sudah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $isi_tindaklanjut = $koneksi->real_escape_string($_POST['isi_tindaklanjut'] ?? '');
    $penindaklanjut = $koneksi->real_escape_string($_POST['penindaklanjut'] ?? '');

    // Jika edit, ambil id_tindaklanjut
    $id_tindaklanjut = isset($_POST['id_tindaklanjut']) ? (int)$_POST['id_tindaklanjut'] : 0;

    // Menangani unggahan file lampiran
    $lampiran_tindaklanjut = $tindaklanjut_data['lampiran'] ?? ''; // Simpan file lama jika ada

    // Handle delete file
    if (isset($_POST['delete_lampiran']) && $_POST['delete_lampiran'] == '1') {
        if (!empty($lampiran_tindaklanjut) && file_exists('uploads/' . $lampiran_tindaklanjut)) {
            unlink('uploads/' . $lampiran_tindaklanjut);
        }
        $lampiran_tindaklanjut = '';
    }

    if (isset($_FILES['lampiran_tindaklanjut']) && $_FILES['lampiran_tindaklanjut']['error'] == UPLOAD_ERR_OK) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $file_name = $_FILES['lampiran_tindaklanjut']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            // Hapus file lama jika ada
            if (!empty($lampiran_tindaklanjut) && file_exists('uploads/' . $lampiran_tindaklanjut)) {
                unlink('uploads/' . $lampiran_tindaklanjut);
            }

            // Generate new filename
            $newFileName = 'tindaklanjut_' . date('YmdHis') . '_' . md5(time()) . '.' . $file_ext;
            $uploadFileDir = 'uploads/';
            $destPath = $uploadFileDir . $newFileName;

            if (move_uploaded_file($_FILES['lampiran_tindaklanjut']['tmp_name'], $destPath)) {
                $lampiran_tindaklanjut = $newFileName;
            } else {
                $message = "Ada masalah saat mengunggah file.";
            }
        } else {
            $message = "Tipe file tidak diizinkan.";
        }
    }

    // Periksa apakah ada data tindak lanjut yang diinput
    if (!empty($isi_tindaklanjut) && !empty($penindaklanjut)) {

        if ($is_edit && $id_tindaklanjut > 0) {
            // UPDATE data tindak lanjut yang sudah ada
            $tgl_tindaklanjut = date("Y-m-d H:i:s");
            $sql = "UPDATE tb_tindaklanjut SET 
                    tgl_tindaklanjut = '$tgl_tindaklanjut',
                    isi_tindaklanjut = '$isi_tindaklanjut',
                    penindaklanjut = '$penindaklanjut',
                    lampiran = '$lampiran_tindaklanjut'
                    WHERE id_tindaklanjut = $id_tindaklanjut";

            $action = "diperbarui";
        } else {
            // INSERT data tindak lanjut baru
            $tgl_tindaklanjut = date("Y-m-d H:i:s");
            $sql = "INSERT INTO tb_tindaklanjut 
                    (tgl_tindaklanjut, isi_tindaklanjut, penindaklanjut, lampiran, id_agenda) 
                    VALUES 
                    ('$tgl_tindaklanjut', '$isi_tindaklanjut', '$penindaklanjut', '$lampiran_tindaklanjut', $id_agenda)";

            $action = "disimpan";
            $is_edit = true; // Setelah insert, menjadi edit mode
        }

        // if ($koneksi->query($sql)) {
        //     // Setelah berhasil disimpan/update, update status agenda menjadi "Selesai"
        //     $sql_update_status = "UPDATE tb_agenda SET id_status = 4 WHERE id_agenda = $id_agenda";
        //     $koneksi->query($sql_update_status);

        //     $message = "Tindak lanjut berhasil $action dan status agenda diperbarui!";


        // Di bagian tindaklanjut.php, saat menyimpan/update tindak lanjut
        // Tambahkan ini setelah query INSERT/UPDATE berhasil:

        if ($koneksi->query($sql)) {
            // Setelah berhasil disimpan/update, update status agenda menjadi "Selesai"
            // Cari id_status untuk "Selesai"
            $sql_selesai = "SELECT id_status FROM tb_status WHERE nama_status = 'Selesai' LIMIT 1";
            $result_selesai = $koneksi->query($sql_selesai);

            if ($result_selesai->num_rows > 0) {
                $selesai = $result_selesai->fetch_assoc();
                $id_status_selesai = $selesai['id_status'];

                $sql_update_status = "UPDATE tb_agenda SET id_status = $id_status_selesai WHERE id_agenda = $id_agenda";
                $koneksi->query($sql_update_status);
            }

            $message = "Tindak lanjut berhasil $action dan status agenda diperbarui ke 'Selesai'!";
            // ... sisa kode

            // Refresh data tindak lanjut
            $result_check = $koneksi->query($sql_check);
            if ($result_check->num_rows > 0) {
                $tindaklanjut_data = $result_check->fetch_assoc();
            }

            // Redirect setelah 2 detik
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'index.php?success=1';
                    }, 2000);
                  </script>";
        } else {
            $message = "Error: " . $koneksi->error;
        }
    } else {
        $message = "Harap isi semua kolom yang wajib.";
    }
}

// Ambil data agenda untuk ditampilkan
$sql_agenda = "SELECT a.*, p.nama_pejabat, p.nama_jabatan 
               FROM tb_agenda a 
               LEFT JOIN tb_pejabat p ON a.pejabat = p.id 
               WHERE a.id_agenda = $id_agenda";
$result_agenda = $koneksi->query($sql_agenda);
$agenda = $result_agenda->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> Tindak Lanjut</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .agenda-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }

        .agenda-info h3 {
            margin-top: 0;
            color: #1565C0;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            min-width: 150px;
            font-weight: bold;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .required::after {
            content: " *";
            color: red;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .file-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            border: 1px dashed #dee2e6;
        }

        .current-file {
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }

        .btn-delete-file {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #4361ee;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-edit {
            background: #ffc107;
            color: #212529;
        }

        .badge-new {
            background: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-header">
            <h1>
                <i class="fas fa-tasks"></i>
                <?php echo $is_edit ? 'Edit Tindak Lanjut' : 'Tambah Tindak Lanjut'; ?>
            </h1>
            <span class="badge <?php echo $is_edit ? 'badge-edit' : 'badge-new'; ?>">
                <?php echo $is_edit ? 'EDIT' : 'BARU'; ?>
            </span>
        </div>

        <!-- Info Agenda -->
        <?php if ($agenda): ?>
            <div class="agenda-info">
                <h3><i class="fas fa-calendar-alt"></i> Informasi Agenda</h3>
                <div class="info-row">
                    <span class="info-label">Kegiatan:</span>
                    <span><?php echo htmlspecialchars($agenda['nama_kegiatan']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal:</span>
                    <span><?php echo date('d F Y', strtotime($agenda['tgl_agenda'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Waktu:</span>
                    <span><?php echo date('H:i', strtotime($agenda['waktu'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tempat:</span>
                    <span><?php echo htmlspecialchars($agenda['tempat_kegiatan']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pejabat:</span>
                    <span><?php echo htmlspecialchars($agenda['nama_jabatan'] . ' - ' . $agenda['nama_pejabat']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-error'; ?>">
                <i class="fas <?php echo strpos($message, 'berhasil') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_edit && $tindaklanjut_data): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Anda sedang mengedit tindak lanjut yang sudah ada.
                Tindak lanjut sebelumnya dibuat pada:
                <?php echo date('d/m/Y H:i', strtotime($tindaklanjut_data['tgl_tindaklanjut'])); ?>
            </div>
        <?php endif; ?>

        <form action="tindaklanjut.php?id=<?php echo htmlspecialchars($id_agenda); ?>" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <?php if ($is_edit && isset($tindaklanjut_data['id_tindaklanjut'])): ?>
                <input type="hidden" name="id_tindaklanjut" value="<?php echo $tindaklanjut_data['id_tindaklanjut']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="isi_tindaklanjut" class="required">Isi Tindak Lanjut:</label>
                <textarea id="isi_tindaklanjut" name="isi_tindaklanjut" rows="5" required><?php
                                                                                            echo $is_edit && isset($tindaklanjut_data['isi_tindaklanjut'])
                                                                                                ? htmlspecialchars($tindaklanjut_data['isi_tindaklanjut'])
                                                                                                : '';
                                                                                            ?></textarea>
            </div>

            <div class="form-group">
                <label for="penindaklanjut" class="required">Penindak Lanjut:</label>
                <input type="text" id="penindaklanjut" name="penindaklanjut" required
                    value="<?php
                            echo $is_edit && isset($tindaklanjut_data['penindaklanjut'])
                                ? htmlspecialchars($tindaklanjut_data['penindaklanjut'])
                                : '';
                            ?>">
            </div>

            <div class="form-group">
                <label for="lampiran_tindaklanjut">Lampiran:</label>

                <?php if ($is_edit && !empty($tindaklanjut_data['lampiran'])): ?>
                    <div class="current-file">
                        <strong>File saat ini:</strong>
                        <p>
                            <a href="uploads/<?php echo htmlspecialchars($tindaklanjut_data['lampiran']); ?>"
                                target="_blank" style="color: #2196F3;">
                                <i class="fas fa-file"></i> <?php echo htmlspecialchars($tindaklanjut_data['lampiran']); ?>
                            </a>
                            <button type="button" class="btn-delete-file" onclick="deleteFile()">
                                <i class="fas fa-trash"></i> Hapus File
                            </button>
                        </p>
                        <input type="hidden" name="delete_lampiran" id="delete_lampiran" value="0">
                    </div>
                <?php endif; ?>

                <input type="file" id="lampiran_tindaklanjut" name="lampiran_tindaklanjut"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="file-info">
                    Ukuran maksimal: 5MB. Format: PDF, DOC, DOCX, JPG, PNG
                    <?php if ($is_edit && !empty($tindaklanjut_data['lampiran'])): ?>
                        <br><small>Biarkan kosong jika tidak ingin mengganti file.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $is_edit ? 'Update Tindak Lanjut' : 'Simpan Tindak Lanjut'; ?>
                </button>

                <!-- <?php if ($is_edit): ?>
                    <a href="tindaklanjut.php?id=<?php echo $id_agenda; ?>&action=delete"
                        class="btn btn-danger"
                        onclick="return confirm('Hapus tindak lanjut ini?')">
                        <i class="fas fa-trash"></i> Hapus
                    </a>
                <?php endif; ?> -->
            </div>
        </form>
    </div>

    <script>
        function validateForm() {
            const isi = document.getElementById('isi_tindaklanjut').value.trim();
            const penindak = document.getElementById('penindaklanjut').value.trim();

            if (!isi || !penindak) {
                alert('Harap isi semua field yang wajib!');
                return false;
            }

            // File validation
            const fileInput = document.getElementById('lampiran_tindaklanjut');
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                const fileExt = file.name.split('.').pop().toLowerCase();

                if (file.size > maxSize) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    return false;
                }

                if (!allowedTypes.includes(fileExt)) {
                    alert('Tipe file tidak diizinkan. Hanya: PDF, DOC, DOCX, JPG, PNG');
                    return false;
                }
            }

            return true;
        }

        function deleteFile() {
            if (confirm('Apakah Anda yakin ingin menghapus file lampiran ini?')) {
                document.getElementById('delete_lampiran').value = '1';
                const fileDiv = document.querySelector('.current-file');
                if (fileDiv) {
                    fileDiv.style.display = 'none';
                }
                alert('File akan dihapus saat Anda menyimpan perubahan.');
            }
        }

        // Preview file info
        document.getElementById('lampiran_tindaklanjut').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                const size = (file.size / (1024 * 1024)).toFixed(2);
                const info = document.querySelector('.file-info');

                let newText = `File baru: ${file.name} (${size} MB)<br>`;
                newText += `Format: ${file.name.split('.').pop().toUpperCase()}`;

                const currentFile = document.querySelector('.current-file');
                if (currentFile) {
                    newText += `<br><small>File lama akan diganti.</small>`;
                }

                info.innerHTML = newText;
            }
        });
    </script>
</body>

</html>

<?php
// Close database connection
if (isset($koneksi)) {
    $koneksi->close();
}
?>