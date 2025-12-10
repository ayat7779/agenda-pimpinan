<?php
// api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'koneksi.php';
include 'security.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get action parameter
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGetRequest($action);
        break;

    case 'POST':
        handlePostRequest();
        break;

    case 'PUT':
        handlePutRequest();
        break;

    case 'DELETE':
        handleDeleteRequest();
        break;

    case 'OPTIONS':
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

function handleGetRequest($action)
{
    global $koneksi;

    switch ($action) {
        case 'calendar':
            // Get events for calendar
            $sql = "SELECT 
                        a.id_agenda as id,
                        a.nama_kegiatan as title,
                        CONCAT(a.tgl_agenda, 'T', a.waktu) as start,
                        CONCAT(a.tgl_agenda, 'T', ADDTIME(a.waktu, '02:00:00')) as end,
                        a.tempat_kegiatan as description,
                        p.nama_pejabat,
                        CASE 
                            WHEN a.id_status = (SELECT id_status FROM tb_status WHERE nama_status = 'Selesai') THEN '#28a745'
                            WHEN a.id_status = (SELECT id_status FROM tb_status WHERE nama_status = 'Tindaklanjuti') THEN '#dc3545'
                            WHEN a.id_status = (SELECT id_status FROM tb_status WHERE nama_status = 'Proses') THEN '#17a2b8'
                            WHEN a.id_status = (SELECT id_status FROM tb_status WHERE nama_status = 'Ditunda') THEN '#6c757d'
                            ELSE '#ffc107'
                        END as backgroundColor,
                        '#ffffff' as textColor
                    FROM tb_agenda a
                    LEFT JOIN tb_pejabat p ON a.pejabat = p.id
                    WHERE a.tgl_agenda >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                    AND a.tgl_agenda <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)";

            $result = $koneksi->query($sql);
            $events = [];

            while ($row = $result->fetch_assoc()) {
                // Add tooltip with additional info
                $row['extendedProps'] = [
                    'tempat' => $row['description'],
                    'pejabat' => $row['nama_pejabat']
                ];
                unset($row['description'], $row['nama_pejabat']);

                $events[] = $row;
            }

            echo json_encode($events);
            break;

        case 'stats':
            // Get statistics
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN tgl_agenda = CURDATE() THEN 1 END) as today,
                        COUNT(CASE WHEN tgl_agenda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week,
                        COUNT(CASE WHEN id_status = 4 THEN 1 END) as completed,
                        COUNT(CASE WHEN id_status = 3 THEN 1 END) as pending
                    FROM tb_agenda";

            $result = $koneksi->query($sql);
            echo json_encode($result->fetch_assoc());
            break;

        case 'upcoming':
            // Get upcoming events
            $limit = $_GET['limit'] ?? 10;
            $sql = "SELECT 
                        a.id_agenda,
                        a.nama_kegiatan,
                        a.tgl_agenda,
                        a.waktu,
                        a.tempat_kegiatan,
                        p.nama_pejabat,
                        s.nama_status
                    FROM tb_agenda a
                    LEFT JOIN tb_pejabat p ON a.pejabat = p.id
                    LEFT JOIN tb_status s ON a.id_status = s.id_status
                    WHERE a.tgl_agenda >= CURDATE()
                    ORDER BY a.tgl_agenda, a.waktu
                    LIMIT ?";

            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }

            echo json_encode($events);
            break;

        default:
            // Get all agenda with filters
            $filter = $_GET['filter'] ?? 'all';
            $search = $_GET['search'] ?? '';

            $sql = "SELECT 
                        a.*,
                        p.nama_pejabat,
                        p.nama_jabatan,
                        s.nama_status
                    FROM tb_agenda a
                    LEFT JOIN tb_pejabat p ON a.pejabat = p.id
                    LEFT JOIN tb_status s ON a.id_status = s.id_status
                    WHERE 1=1";

            $params = [];
            $types = '';

            if ($filter === 'today') {
                $sql .= " AND a.tgl_agenda = CURDATE()";
            } elseif ($filter === 'month') {
                $sql .= " AND MONTH(a.tgl_agenda) = MONTH(CURDATE()) AND YEAR(a.tgl_agenda) = YEAR(CURDATE())";
            } elseif ($filter === 'week') {
                $sql .= " AND a.tgl_agenda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            }

            if (!empty($search)) {
                $sql .= " AND (a.nama_kegiatan LIKE ? OR a.tempat_kegiatan LIKE ? OR p.nama_pejabat LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_fill(0, 3, $searchTerm);
                $types = str_repeat('s', count($params));
            }

            $sql .= " ORDER BY a.tgl_agenda DESC, a.waktu DESC";

            $stmt = $koneksi->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $agendas = [];
            while ($row = $result->fetch_assoc()) {
                $agendas[] = $row;
            }

            echo json_encode($agendas);
            break;
    }
}

function handlePostRequest()
{
    global $koneksi;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        return;
    }

    // Validate required fields
    $required = ['tgl_agenda', 'waktu', 'nama_kegiatan', 'tempat_kegiatan', 'penanggungjawab_kegiatan', 'pakaian_kegiatan', 'pejabat'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
            return;
        }
    }

    // Insert into database
    $sql = "INSERT INTO tb_agenda (
                tgl_agenda, 
                waktu, 
                nama_kegiatan, 
                tempat_kegiatan, 
                penanggungjawab_kegiatan, 
                pakaian_kegiatan, 
                pejabat, 
                lampiran, 
                hasil_agenda, 
                id_status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param(
        "sssssssssi",
        $data['tgl_agenda'],
        $data['waktu'],
        $data['nama_kegiatan'],
        $data['tempat_kegiatan'],
        $data['penanggungjawab_kegiatan'],
        $data['pakaian_kegiatan'],
        $data['pejabat'],
        $data['lampiran'] ?? '',
        $data['hasil_agenda'] ?? '',
        $data['id_status'] ?? 6 // Default: Belum Mulai
    );

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Agenda created successfully',
            'data' => ['id' => $id]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create agenda: ' . $stmt->error]);
    }

    $stmt->close();
}

function handlePutRequest()
{
    global $koneksi;

    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
        return;
    }

    // Build update query
    $fields = [];
    $params = [];
    $types = '';

    $allowedFields = [
        'tgl_agenda',
        'waktu',
        'nama_kegiatan',
        'tempat_kegiatan',
        'penanggungjawab_kegiatan',
        'pakaian_kegiatan',
        'pejabat',
        'lampiran',
        'hasil_agenda',
        'id_status'
    ];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
            $types .= 's';
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
        return;
    }

    $fields[] = "updated_at = NOW()";

    $params[] = $id;
    $types .= 'i';

    $sql = "UPDATE tb_agenda SET " . implode(', ', $fields) . " WHERE id_agenda = ?";

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Agenda updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update agenda: ' . $stmt->error]);
    }

    $stmt->close();
}

function handleDeleteRequest()
{
    global $koneksi;

    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        return;
    }

    // Check if agenda exists
    $check_sql = "SELECT id_agenda FROM tb_agenda WHERE id_agenda = ?";
    $check_stmt = $koneksi->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows === 0) {
        $check_stmt->close();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Agenda not found']);
        return;
    }
    $check_stmt->close();

    // Delete agenda
    $sql = "DELETE FROM tb_agenda WHERE id_agenda = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Agenda deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete agenda: ' . $stmt->error]);
    }

    $stmt->close();
}

$koneksi->close();
