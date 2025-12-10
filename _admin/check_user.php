<?php
// File: check_user.php
include 'koneksi.php';

echo "<h3>Checking Database and Users</h3>";

// 1. Check if tb_users exists
$sql = "SHOW TABLES LIKE 'tb_users'";
$result = $koneksi->query($sql);
echo "Table tb_users exists: " . ($result->num_rows > 0 ? "YES" : "NO") . "<br>";

// 2. Check user count
$sql = "SELECT COUNT(*) as count FROM tb_users";
$result = $koneksi->query($sql);
$row = $result->fetch_assoc();
echo "Total users: " . $row['count'] . "<br>";

// 3. List all users
$sql = "SELECT id, username, email, role, is_active FROM tb_users";
$result = $koneksi->query($sql);
echo "<h4>User List:</h4>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th></tr>";
while ($user = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['email'] . "</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "<td>" . $user['is_active'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Test password for superadmin
$sql = "SELECT password FROM tb_users WHERE username = 'superadmin'";
$result = $koneksi->query($sql);
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<h4>Password hash for superadmin:</h4>";
    echo $user['password'];

    // Test password verification
    $test_password = "Admin123!";
    if (password_verify($test_password, $user['password'])) {
        echo "<p style='color:green;'>✓ Password verification SUCCESS</p>";
    } else {
        echo "<p style='color:red;'>✗ Password verification FAILED</p>";
    }
}

// 5. Check session table
$sql = "SHOW TABLES LIKE 'tb_user_sessions'";
$result = $koneksi->query($sql);
echo "<br>Table tb_user_sessions exists: " . ($result->num_rows > 0 ? "YES" : "NO") . "<br>";

// 6. Check permission tables
$sql = "SHOW TABLES LIKE 'tb_permissions'";
$result = $koneksi->query($sql);
echo "Table tb_permissions exists: " . ($result->num_rows > 0 ? "YES" : "NO") . "<br>";

$sql = "SHOW TABLES LIKE 'tb_role_permissions'";
$result = $koneksi->query($sql);
echo "Table tb_role_permissions exists: " . ($result->num_rows > 0 ? "YES" : "NO") . "<br>";

echo "<hr>";
echo "<h4>Session Status:</h4>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
