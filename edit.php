<?php
// edit.php - Updated with authentication
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== AUTH CHECK =====
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/koneksi.php';

$pesan_error = "";
$agenda = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_agenda = (int)$_GET['id'];
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'staff';

// Get agenda data
$sql = "SELECT a.*, p.nama_jabatan, p.nama_pejabat 
        FROM tb_agenda a 
        LEFT JOIN tb_pejabat p ON a.pejabat = p.id 
        WHERE a.id_agenda = $id_agenda";
$result = $koneksi->query($sql);

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$agenda = $result->fetch_assoc();

// ===== PERMISSION CHECK =====
$canEdit = false;

if ($userRole === 'super_admin' || $userRole === 'admin') {
    $canEdit = true;
} elseif ($userRole === 'pimpinan') {
    // Pimpinan can edit if they created it or if it involves them
    if ($agenda['created_by'] == $userId || 
        strpos($agenda['penanggungjawab_kegiatan'], $_SESSION['full_name']) !== false) {
        $canEdit = true;
    }
} elseif ($userRole === 'staff') {
    // Staff can only edit if they created it
    if ($agenda['created_by'] == $userId) {
        $canEdit = true;
    }
}

if (!$canEdit) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengedit agenda ini.';
    header("Location: index.php");
    exit();
}

// Cek apakah sudah ada tindak lanjut
$sql_check_tl = "SELECT COUNT(*) as total FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
$result_tl = $koneksi->query($sql_check_tl);
$has_tindaklanjut = $result_tl->fetch_assoc()['total'] > 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Permission check for form submission
    if (!$canEdit) {
        $pesan_error = "Anda tidak memiliki izin untuk mengedit agenda ini.";
    } else {
    // Sanitize inputs
    $tgl_agenda = $koneksi->real_escape_string($_POST['tgl_agenda'] ?? '');
    $waktu = $koneksi->real_escape_string($_POST['waktu'] ?? '');
    $nama_kegiatan = $koneksi->real_escape_string($_POST['nama_kegiatan'] ?? '');
    $tempat_kegiatan = $koneksi->real_escape_string($_POST['tempat_kegiatan'] ?? '');
    $penanggungjawab_kegiatan = $koneksi->real_escape_string($_POST['penanggungjawab_kegiatan'] ?? '');
    $pakaian_kegiatan = $koneksi->real_escape_string($_POST['pakaian_kegiatan'] ?? '');
    $pejabat = $koneksi->real_escape_string($_POST['pejabat'] ?? '');
    $hasil_agenda = $koneksi->real_escape_string($_POST['hasil_agenda'] ?? '');

    // Jika sudah ada tindak lanjut, pertahankan status yang ada
    if ($has_tindaklanjut) {
        $status = $agenda['id_status']; // Ambil dari data lama
    } else {
        $status = $koneksi->real_escape_string($_POST['status'] ?? '6');
    }
    // Handle file
    $lampiran = $agenda['lampiran'] ?? '';

    if (isset($_POST['delete_file']) && $_POST['delete_file'] == '1') {
        if (!empty($lampiran) && file_exists('uploads/' . $lampiran)) {
            unlink('uploads/' . $lampiran);
        }
        $lampiran = '';
    }

    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        $file_name = $_FILES['lampiran']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['lampiran']['size'];

        if (in_array($file_ext, $allowed)) {
            if ($file_size <= 5 * 1024 * 1024) {
                $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                $upload_path = 'uploads/' . $new_filename;

                if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_path)) {
                    $lampiran = $new_filename;
                } else {
                    $pesan_error = "Gagal mengupload file.";
                }
            } else {
                $pesan_error = "Ukuran file terlalu besar (max 5MB).";
            }
        } else {
            $pesan_error = "Tipe file tidak diizinkan.";
        }
    }

    if (empty($pesan_error)) {
        $sql = "UPDATE tb_agenda SET 
                tgl_agenda = '$tgl_agenda',
                waktu = '$waktu',
                nama_kegiatan = '$nama_kegiatan',
                tempat_kegiatan = '$tempat_kegiatan',
                penanggungjawab_kegiatan = '$penanggungjawab_kegiatan',
                pakaian_kegiatan = '$pakaian_kegiatan',
                pejabat = '$pejabat',
                lampiran = '$lampiran',
                hasil_agenda = '$hasil_agenda',
                id_status = '$status'
                WHERE id_agenda = $id_agenda";

        if ($koneksi->query($sql)) {
            header("Location: index.php?updated=1");
            exit();
        } else {
            $pesan_error = "Error: " . $koneksi->error;
        }
    }
}

// Get dropdown data
$sql_status = "SELECT * FROM tb_status ORDER BY nama_status ASC";
$result_status = $koneksi->query($sql_status);

$sql_pejabat = "SELECT * FROM tb_pejabat ORDER BY kode_pejabat ASC";
$result_pejabat = $koneksi->query($sql_pejabat);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Agenda</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .required {
            color: red;
        }

        .current-file {
            background: #e8f4fd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #2196F3;
        }

        .file-actions {
            margin-top: 10px;
        }

        .btn-delete-file {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
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

        .btn:hover {
            opacity: 0.9;
        }

        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
         .permission-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .permission-notice i {
            font-size: 18px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
                    <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Agenda
            </a>
            
            <!-- Permission Notice -->
            <div class="permission-notice">
                <i class="fas fa-info-circle"></i>
                Anda sedang mengedit agenda sebagai: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                (<?php echo htmlspecialchars($_SESSION['role']); ?>)
            </div>

            <h1><i class="fas fa-edit"></i> Edit Agenda</h1>

            <?php if (!empty($pesan_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($pesan_error); ?>
                </div>
            <?php endif; ?>

            <?php if ($agenda && $canEdit): ?>
                <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="id_agenda" value="<?php echo $id_agenda; ?>">

                    <div class="form-group">
                        <label for="tgl_agenda"><span class="required">*</span> Tanggal Agenda</label>
                        <input type="date" id="tgl_agenda" name="tgl_agenda" required
                            value="<?php echo htmlspecialchars($agenda['tgl_agenda'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="waktu"><span class="required">*</span> Waktu</label>
                        <input type="time" id="waktu" name="waktu" required
                            value="<?php echo htmlspecialchars(substr($agenda['waktu'] ?? '', 0, 5)); ?>">
                    </div>

                    <div class="form-group">
                        <label for="nama_kegiatan"><span class="required">*</span> Nama Kegiatan</label>
                        <input type="text" id="nama_kegiatan" name="nama_kegiatan" required
                            value="<?php echo htmlspecialchars($agenda['nama_kegiatan'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tempat_kegiatan"><span class="required">*</span> Tempat Kegiatan</label>
                        <input type="text" id="tempat_kegiatan" name="tempat_kegiatan" required
                            value="<?php echo htmlspecialchars($agenda['tempat_kegiatan'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="penanggungjawab_kegiatan"><span class="required">*</span> Penanggung Jawab</label>
                        <input type="text" id="penanggungjawab_kegiatan" name="penanggungjawab_kegiatan" required
                            value="<?php echo htmlspecialchars($agenda['penanggungjawab_kegiatan'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pakaian_kegiatan"><span class="required">*</span> Pakaian</label>
                        <input type="text" id="pakaian_kegiatan" name="pakaian_kegiatan" required
                            value="<?php echo htmlspecialchars($agenda['pakaian_kegiatan'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pejabat"><span class="required">*</span> Pejabat</label>
                        <select id="pejabat" name="pejabat" required>
                            <option value="">-- Pilih Pejabat --</option>
                            <?php
                            if ($result_pejabat && $result_pejabat->num_rows > 0):
                                $result_pejabat->data_seek(0); // Reset pointer
                                while ($row = $result_pejabat->fetch_assoc()):
                            ?>
                                    <option value="<?php echo htmlspecialchars($row['id']); ?>"
                                        <?php echo ($row['id'] == ($agenda['pejabat'] ?? '')) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['nama_jabatan'] . ' - ' . $row['nama_pejabat']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-triangle"></i> Anda tidak memiliki izin untuk mengedit agenda ini.</div>
                                <option value="">Data pejabat tidak ditemukan</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status"><span class="required">*</span> Status</label>
                        <select id="status" name="status" required>
                            <option value="">-- Pilih Status --</option>
                            <?php
                            if ($result_status && $result_status->num_rows > 0):
                                $result_status->data_seek(0); // Reset pointer
                                while ($row = $result_status->fetch_assoc()):
                            ?>
                                    <option value="<?php echo htmlspecialchars($row['id_status']); ?>"
                                        <?php echo ($row['id_status'] == ($agenda['id_status'] ?? '')) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['nama_status']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="6">Belum Mulai</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lampiran">Lampiran</label>

                        <?php if (!empty($agenda['lampiran'])): ?>
                            <div class="current-file">
                                <strong>File saat ini:</strong>
                                <p>
                                    <a href="uploads/<?php echo htmlspecialchars($agenda['lampiran']); ?>"
                                        target="_blank" style="color: #2196F3;">
                                        <i class="fas fa-file"></i> <?php echo htmlspecialchars($agenda['lampiran']); ?>
                                    </a>
                                </p>
                                <div class="file-actions">
                                    <button type="button" class="btn-delete-file" onclick="deleteFile()">
                                        <i class="fas fa-trash"></i> Hapus File
                                    </button>
                                    <input type="hidden" name="delete_file" id="delete_file" value="0">
                                </div>
                            </div>
                        <?php endif; ?>

                        <input type="file" id="lampiran" name="lampiran"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="file-info">
                            Biarkan kosong jika tidak ingin mengganti file.<br>
                            Ukuran maksimal: 5MB. Format: PDF, DOC, DOCX, JPG, PNG
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="hasil_agenda">Hasil yang Dicapai</label>
                        <textarea id="hasil_agenda" name="hasil_agenda"><?php echo htmlspecialchars($agenda['hasil_agenda'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Agenda
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function validateForm() {
            let valid = true;
            const requiredFields = document.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'red';

                    // Show error
                    let error = field.parentNode.querySelector('.field-error');
                    if (!error) {
                        error = document.createElement('div');
                        error.className = 'field-error';
                        error.style.color = 'red';
                        error.style.fontSize = '12px';
                        error.style.marginTop = '5px';
                        field.parentNode.appendChild(error);
                    }
                    error.textContent = 'Field ini wajib diisi';
                } else {
                    field.style.borderColor = '';

                    // Remove error
                    const error = field.parentNode.querySelector('.field-error');
                    if (error) {
                        error.remove();
                    }
                }
            });

            if (!valid) {
                alert('Harap lengkapi semua field yang wajib diisi!');
                return false;
            }

            // File validation
            const fileInput = document.getElementById('lampiran');
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
                document.getElementById('delete_file').value = '1';
                const fileDiv = document.querySelector('.current-file');
                if (fileDiv) {
                    fileDiv.style.display = 'none';
                }
                alert('File akan dihapus saat Anda menyimpan perubahan.');
            }
        }

        // Set minimum date to today
        document.getElementById('tgl_agenda').min = new Date().toISOString().split('T')[0];

        // Preview file info
        document.getElementById('lampiran').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                const size = (file.size / (1024 * 1024)).toFixed(2);
                const info = document.querySelector('.file-info');
                info.innerHTML = `File baru: ${file.name} (${size} MB)<br>
                                  File lama akan diganti.`;
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