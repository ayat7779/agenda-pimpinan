<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lampiran_nama = '';

    // Proses upload file
    if (isset($_FILES['lampiran_file'])) {
        $target_dir = "uploads/";
        $lampiran_nama = basename($_FILES["lampiran_file"]["name"]);
        $target_file = $target_dir . $lampiran_nama;

        if (move_uploaded_file($_FILES["lampiran_file"]["tmp_name"], $target_file)) {
            // File berhasil diunggah
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file']);
            exit;
        }
    }

    // Ambil data form lainnya
    $tgl_agenda = $_POST['tgl_agenda'];
    $waktu = $_POST['waktu'];
    $nama_kegiatan = $_POST['nama_kegiatan'];
    $tempat_kegiatan = $_POST['tempat_kegiatan'];
    $penanggungjawab_kegiatan = $_POST['penanggungjawab_kegiatan'];
    $pakaian_kegiatan = $_POST['pakaian_kegiatan'];myserver
    $pejabat = $_POST['pejabat'];
    
    // Simpan ke database
    $sql = "INSERT INTO tb_agenda (tgl_agenda, waktu, nama_kegiatan, tempat_kegiatan, penanggungjawab_kegiatan, pakaian_kegiatan, pejabat, lampiran) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ssssssss", $tgl_agenda, $waktu, $nama_kegiatan, $tempat_kegiatan, $penanggungjawab_kegiatan, $pakaian_kegiatan, $pejabat, $lampiran_nama);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Agenda berhasil ditambahkan']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan agenda']);
    }
    $stmt->close();
    $koneksi->close();
}