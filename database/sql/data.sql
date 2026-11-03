USE klinik_hewan_NIM;

INSERT INTO jenis_hewan (nama_jenis) VALUES
    ('Kucing'),
    ('Anjing');

INSERT INTO pasien_hewan
    (kode_pasien, nama_hewan, nomor_rekam, usia_tahun, jenis_hewan_id)
VALUES
    ('PH00000001', 'Raja', 'RMH-0001', 3, 1),
    ('PH00000002', 'Boli', 'RMH-0002', 5, 2),
    ('PH00000003', 'Ucup', 'RMH-0003', 2, 1);

SELECT t.kode_pasien, t.nama_hewan, t.nomor_rekam, t.usia_tahun, m.nama_jenis
FROM pasien_hewan AS t
JOIN jenis_hewan AS m ON m.id = t.jenis_hewan_id
ORDER BY t.kode_pasien;
