<?php
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id_agenda = $_GET['id'];
    $sql = "DELETE FROM tb_agenda WHERE id_agenda = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id_agenda);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$koneksi->close();
?>