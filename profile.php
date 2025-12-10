<?php
// profile.php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/koneksi.php';

$message = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $koneksi->real_escape_string($_POST['full_name'] ?? '');
    $phone = $koneksi->real_escape_string($_POST['phone'] ?? '');

    $userId = $_SESSION['user_id'];

    // Update profile
    $sql = "UPDATE tb_users SET full_name = ?, phone = ? WHERE id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ssi", $full_name, $phone, $userId);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $success = 'Profil berhasil diperbarui.';
    } else {
        $message = 'Gagal memperbarui profil.';
    }
}

// Get user data
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM tb_users WHERE id = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Profil Pengguna</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <h1><i class="fas fa-user"></i> Profil Pengguna</h1>

            <?php if ($message): ?>
                <div class="alert alert-error"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="avatar-large">
                        <?php
                        $initials = '';
                        $name = $user['full_name'];
                        $words = explode(' ', $name);
                        foreach ($words as $word) {
                            $initials .= strtoupper($word[0]);
                            if (strlen($initials) >= 2) break;
                        }
                        echo $initials ?: 'U';
                        ?>
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="user-role"><?php echo htmlspecialchars($user['role']); ?></p>
                </div>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Terakhir Login</label>
                        <input type="text" value="<?php echo $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?>" disabled>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>