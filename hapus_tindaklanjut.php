<?php
// hapus.php - Versi dengan penghapusan tindak lanjut
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php';

if (isset($_GET['id'])) {
    $id_agenda = (int)$_GET['id'];

    // Mulai transaksi untuk memastikan semua operasi berhasil atau rollback
    $koneksi->begin_transaction();

    try {
        // 1. Hapus file lampiran dari tabel tb_tindaklanjut terlebih dahulu
        $sql_get_tindaklanjut_files = "SELECT lampiran FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
        $result_tindaklanjut = $koneksi->query($sql_get_tindaklanjut_files);

        while ($row = $result_tindaklanjut->fetch_assoc()) {
            if (!empty($row['lampiran']) && file_exists('uploads/' . $row['lampiran'])) {
                unlink('uploads/' . $row['lampiran']);
            }
        }

        // 2. Hapus data tindak lanjut
        $sql_delete_tindaklanjut = "DELETE FROM tb_tindaklanjut WHERE id_agenda = $id_agenda";
        $koneksi->query($sql_delete_tindaklanjut);

        // 3. Hapus file lampiran agenda
        $sql_get_agenda_file = "SELECT lampiran FROM tb_agenda WHERE id_agenda = $id_agenda";
        $result_agenda = $koneksi->query($sql_get_agenda_file);

        if ($result_agenda->num_rows > 0) {
            $row = $result_agenda->fetch_assoc();
            if (!empty($row['lampiran']) && file_exists('uploads/' . $row['lampiran'])) {
                unlink('uploads/' . $row['lampiran']);
            }
        }

        // 4. Hapus agenda itu sendiri
        $sql_delete_agenda = "DELETE FROM tb_agenda WHERE id_agenda = $id_agenda";

        if ($koneksi->query($sql_delete_agenda)) {
            // Commit transaksi
            $koneksi->commit();

            // Redirect dengan pesan sukses
            header("Location: index.php?deleted=1");
            exit();
        } else {
            throw new Exception("Gagal menghapus agenda: " . $koneksi->error);
        }
    } catch (Exception $e) {
        // Rollback jika ada error
        $koneksi->rollback();
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
    exit();
}

$koneksi->close();
