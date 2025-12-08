<?php
// unlock_status.php - Hanya untuk admin jika perlu membuka kunci status
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

session_start();

// Cek apakah user adalah admin (sesuaikan dengan sistem auth Anda)
// Untuk sementara, kita buat simple password check
$is_admin = false;

if (isset($_POST['admin_password'])) {
    // Password admin sederhana (ganti dengan yang lebih aman di production)
    if ($_POST['admin_password'] === 'admin123') {
        $is_admin = true;
        $_SESSION['is_admin'] = true;
    }
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $is_admin = true;
}

if (!$is_admin) {
    // Tampilkan form login admin
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Akses Admin - Unlock Status</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
            }

            .container {
                max-width: 400px;
                margin: 50px auto;
            }

            .login-form {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            label {
                display: block;
                margin-bottom: 5px;
            }

            input {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            button {
                background: #dc3545;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            .alert {
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }

            .alert-error {
                background: #f8d7da;
                color: #721c24;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h2>Akses Admin - Unlock Status</h2>
            <div class="login-form">
                <form method="POST">
                    <div class="form-group">
                        <label>Password Admin:</label>
                        <input type="password" name="admin_password" required>
                    </div>
                    <?php if (isset($_POST['admin_password'])): ?>
                        <div class="alert alert-error">
                            Password salah!
                        </div>
                    <?php endif; ?>
                    <button type="submit">Login</button>
                </form>
            </div>
            <p style="text-align: center; margin-top: 20px;">
                <a href="index.php">Kembali ke aplikasi</a>
            </p>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Jika sudah login admin
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];

    // Hapus semua tindak lanjut terkait agenda ini
    $sql_delete_tl = "DELETE FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
    $koneksi->query($sql_delete_tl);

    // Update status agenda kembali ke "Belum Mulai"
    $sql_update = "UPDATE tb_agenda SET id_status = 6 WHERE id_agenda = $id_agenda";
    $koneksi->query($sql_update);

    echo "<div style='background: #d4edda; color: #155724; padding: 20px; margin: 20px; border-radius: 8px;'>
            <h3><i class='fas fa-unlock'></i> Status Berhasil Dibuka Kunci!</h3>
            <p>Semua tindak lanjut untuk agenda ID: $id_agenda telah dihapus.</p>
            <p>Status agenda telah direset ke 'Belum Mulai'.</p>
            <p><a href='edit.php?id=$id_agenda'>Edit Agenda</a> | 
               <a href='index.php'>Kembali ke Daftar</a></p>
          </div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 8px;'>
            <h3><i class='fas fa-exclamation-triangle'></i> Error</h3>
            <p>ID agenda tidak valid.</p>
            <p><a href='index.php'>Kembali</a></p>
          </div>";
}

$koneksi->close();
?>