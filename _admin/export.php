<?php
// export.php
include 'koneksi.php';
include 'security.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=agenda-pimpinan-' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Header
$header = [
    'ID',
    'Tanggal',
    'Waktu',
    'Nama Kegiatan',
    'Tempat',
    'Penanggung Jawab',
    'Pakaian',
    'Pejabat',
    'Jabatan',
    'Status',
    'Hasil Dicapai',
    'Tindak Lanjut',
    'Lampiran',
    'Dibuat Pada',
    'Diupdate Pada'
];

fputcsv($output, $header, ';');

// Query data
$sql = "SELECT 
            a.id_agenda,
            a.tgl_agenda,
            a.waktu,
            a.nama_kegiatan,
            a.tempat_kegiatan,
            a.penanggungjawab_kegiatan,
            a.pakaian_kegiatan,
            p.nama_pejabat,
            p.nama_jabatan,
            s.nama_status,
            a.hasil_agenda,
            t.isi_tindaklanjut,
            a.lampiran,
            a.created_at,
            a.updated_at
        FROM tb_agenda a
        LEFT JOIN tb_pejabat p ON a.pejabat = p.id
        LEFT JOIN tb_status s ON a.id_status = s.id_status
        LEFT JOIN tb_tindaklanjut t ON a.id_agenda = t.id_agenda
        ORDER BY a.tgl_agenda DESC, a.waktu DESC";

$result = $koneksi->query($sql);

while ($row = $result->fetch_assoc()) {
    // Format date
    $row['tgl_agenda'] = date('d/m/Y', strtotime($row['tgl_agenda']));
    $row['waktu'] = date('H:i', strtotime($row['waktu']));

    // Format timestamps
    $row['created_at'] = $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '';
    $row['updated_at'] = $row['updated_at'] ? date('d/m/Y H:i', strtotime($row['updated_at'])) : '';

    // Clean newlines for CSV
    $row['hasil_agenda'] = str_replace(["\r", "\n"], ' ', $row['hasil_agenda']);
    $row['isi_tindaklanjut'] = str_replace(["\r", "\n"], ' ', $row['isi_tindaklanjut']);

    fputcsv($output, $row, ';');
}

fclose($output);
exit();
