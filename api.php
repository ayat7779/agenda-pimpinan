<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Sertakan file koneksi database
include 'koneksi.php';

// Ambil metode permintaan
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Operasi GET (mengambil semua data agenda)
        $sql = "SELECT * FROM tb_agenda ORDER BY tgl_agenda DESC";
        $result = $koneksi->query($sql);
        $data = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        break;

    case 'POST':
        // Operasi POST (menambah agenda baru)
        $json_data = file_get_contents("php://input");
        $post_data = json_decode($json_data, true);

        $tgl_agenda = $post_data['tgl_agenda'];
        $waktu = $post_data['waktu'];
        $nama_kegiatan = $post_data['nama_kegiatan'];
        $tempat_kegiatan = $post_data['tempat_kegiatan'];
        $penanggungjawab_kegiatan = $post_data['penanggungjawab_kegiatan'];
        $pakaian_kegiatan = $post_data['pakaian_kegiatan'];
        $pejabat = $post_data['pejabat'];
        $lampiran = $post_data['lampiran'];

        $sql = "INSERT INTO tb_agenda (tgl_agenda, waktu, nama_kegiatan, tempat_kegiatan, penanggungjawab_kegiatan, pakaian_kegiatan, pejabat, lampiran) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ssssssss", $tgl_agenda, $waktu, $nama_kegiatan, $tempat_kegiatan, $penanggungjawab_kegiatan, $pakaian_kegiatan, $pejabat, $lampiran);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Agenda berhasil ditambahkan."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Gagal menambahkan agenda: " . $stmt->error));
        }
        $stmt->close();
        break;

    case 'PUT':
        // Operasi PUT (memperbarui agenda)
        parse_str(file_get_contents("php://input"), $put_data);

        $id_agenda = isset($_GET['id']) ? $_GET['id'] : null;
        if (!$id_agenda) {
            http_response_code(400);
            echo json_encode(array("message" => "ID agenda tidak ditemukan."));
            break;
        }

        $tgl_agenda = $put_data['tgl_agenda'];
        $waktu = $put_data['waktu'];
        $nama_kegiatan = $put_data['nama_kegiatan'];
        $tempat_kegiatan = $put_data['tempat_kegiatan'];
        $penanggungjawab_kegiatan = $put_data['penanggungjawab_kegiatan'];
        $pakaian_kegiatan = $put_data['pakaian_kegiatan'];
        $pejabat = $put_data['pejabat'];
        $lampiran = $put_data['lampiran'];

        $sql = "UPDATE tb_agenda SET tgl_agenda=?, waktu=?, nama_kegiatan=?, tempat_kegiatan=?, penanggungjawab_kegiatan=?, pakaian_kegiatan=?, pejabat=?, lampiran=? WHERE id_agenda=?";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ssssssssi", $tgl_agenda, $waktu, $nama_kegiatan, $tempat_kegiatan, $penanggungjawab_kegiatan, $pakaian_kegiatan, $pejabat, $lampiran, $id_agenda);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Agenda berhasil diperbarui."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Gagal memperbarui agenda: " . $stmt->error));
        }
        $stmt->close();
        break;

    case 'DELETE':
        // Operasi DELETE (menghapus agenda)
        $id_agenda = isset($_GET['id']) ? $_GET['id'] : null;
        if (!$id_agenda) {
            http_response_code(400);
            echo json_encode(array("message" => "ID agenda tidak ditemukan."));
            break;
        }

        $sql = "DELETE FROM tb_agenda WHERE id_agenda=?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("i", $id_agenda);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Agenda berhasil dihapus."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Gagal menghapus agenda: " . $stmt->error));
        }
        $stmt->close();
        break;

    case 'OPTIONS':
        // Tangani pra-penerbangan CORS
        http_response_code(200);
        break;

    default:
        // Metode permintaan tidak didukung
        http_response_code(405);
        echo json_encode(array("message" => "Metode tidak diizinkan."));
        break;
}

$koneksi->close();