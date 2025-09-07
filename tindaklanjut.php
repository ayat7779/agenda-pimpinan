<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file koneksi database
include 'koneksi.php';

// Pastikan id_agenda ada di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID Agenda tidak ditemukan. Kembali ke halaman utama.";
    exit;
}

$id_agenda = htmlspecialchars($_GET['id']);
$message = "";

// Cek apakah form sudah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $isi_tindaklanjut = $_POST['isi_tindaklanjut'];
    $penindaklanjut = $_POST['penindaklanjut'];
    $tgl_tindaklanjut = date("Y-m-d H:i:s"); // Ambil tanggal dan waktu saat ini

    // Menangani unggahan file lampiran
    $lampiran_tindaklanjut = '';
    if (isset($_FILES['lampiran_tindaklanjut']) && $_FILES['lampiran_tindaklanjut']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['lampiran_tindaklanjut']['tmp_name'];
        $fileName = $_FILES['lampiran_tindaklanjut']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Buat nama file baru dengan format yang diminta
        $newFileName = 'tindaklanjut_' . date('YmdHis') . '_' . md5(time()) . '.' . $fileExtension;
        $uploadFileDir = './uploads/';
        $destPath = $uploadFileDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $lampiran_tindaklanjut = $newFileName;
        } else {
            $message = "Ada masalah saat mengunggah file Anda.";
        }
    }

    // Periksa apakah ada data tindak lanjut yang diinput
    if (!empty($isi_tindaklanjut) && !empty($penindaklanjut)) {
        // SQL untuk menyimpan data ke tabel tb_tindaklanjut
        $sql_insert = "INSERT INTO tb_tindaklanjut (tgl_tindaklanjut, isi_tindaklanjut, penindaklanjut, lampiran, id_agenda) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $koneksi->prepare($sql_insert)) {
            $stmt->bind_param("ssssi", $tgl_tindaklanjut, $isi_tindaklanjut, $penindaklanjut, $lampiran_tindaklanjut, $id_agenda);
            
            if ($stmt->execute()) {
                // Setelah berhasil disimpan, update status agenda menjadi "Selesai"
                $sql_update_status = "UPDATE tb_agenda SET id_status = (SELECT id_status FROM tb_status WHERE nama_status = 'Selesai') WHERE id_agenda = ?";
                if ($stmt_update = $koneksi->prepare($sql_update_status)) {
                    $stmt_update->bind_param("i", $id_agenda);
                    $stmt_update->execute();
                    $stmt_update->close();
                }

                $message = "Tindak lanjut berhasil disimpan dan status agenda diperbarui!";
                // Redirect ke halaman utama setelah sukses
                header("Location: index.php?message=" . urlencode($message));
                exit;

            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error: " . $koneksi->error;
        }
    } else {
        $message = "Harap isi semua kolom yang wajib.";
    }

    $koneksi->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Tindak Lanjut</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Form Tindak Lanjut</h2>

        <?php if (!empty($message)): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="tindaklanjut.php?id=<?php echo htmlspecialchars($id_agenda); ?>" method="post" enctype="multipart/form-data">
            
            <label for="isi_tindaklanjut">Isi Tindak Lanjut:</label>
            <textarea id="isi_tindaklanjut" name="isi_tindaklanjut" rows="5" required></textarea>

            <label for="penindaklanjut">Penindak Lanjut:</label>
            <input type="text" id="penindaklanjut" name="penindaklanjut" required>
            
            <label for="lampiran_tindaklanjut">Lampiran:</label>
            <input type="file" id="lampiran_tindaklanjut" name="lampiran_tindaklanjut">

            <input type="hidden" name="id_agenda" value="<?php echo htmlspecialchars($id_agenda); ?>">

            <button type="submit">Simpan Tindak Lanjut</button>
            <a href="index.php" class="button-kembali">Kembali</a>
        </form>
    </div>
</body>
</html>