SELECT id, username, full_name, role FROM tb_users 
WHERE username IN ('pimpinan1', 'staff1');

SELECT id_agenda, tgl_agenda, nama_kegiatan, created_by, penanggungjawab_kegiatan, pejabat
FROM tb_agenda 
WHERE created_by = 1; 

SELECT id_agenda, tgl_agenda, nama_kegiatan, penanggungjawab_kegiatan
FROM tb_agenda 
WHERE penanggungjawab_kegiatan LIKE '%protokol%'; 

SELECT a.*,b.nama_status,c.isi_tindaklanjut,c.tgl_tindaklanjut,p.nama_pejabat,p.nama_jabatan,u.full_name AS created_by_name
FROM tb_agenda AS a
LEFT JOIN tb_status AS b ON a.id_status = b.id_status 
LEFT JOIN tb_tindaklanjut AS c ON a.id_agenda = c.id_agenda
LEFT JOIN tb_pejabat AS p ON a.pejabat = p.id    
LEFT JOIN tb_users AS u ON a.created_by = u.id
ORDER BY a.tgl_agenda DESC, a.waktu DESC