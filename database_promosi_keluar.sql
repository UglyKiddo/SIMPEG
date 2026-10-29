ALTER TABLE riwayat_promosi
    ADD COLUMN nama_pegawai VARCHAR(150) NULL AFTER id_pegawai,
    ADD COLUMN nip_pegawai VARCHAR(30) NULL AFTER nama_pegawai,
    ADD COLUMN jenis_promosi ENUM('Naik Jabatan', 'Keluar') NOT NULL DEFAULT 'Naik Jabatan' AFTER nip_pegawai,
    ADD COLUMN tanggal_keluar DATE NULL AFTER tanggal_sk;

ALTER TABLE riwayat_mutasi
    ADD COLUMN nama_pegawai VARCHAR(150) NULL AFTER id_pegawai,
    ADD COLUMN nip_pegawai VARCHAR(30) NULL AFTER nama_pegawai;

UPDATE riwayat_promosi r
JOIN pegawai p ON p.id_pegawai = r.id_pegawai
SET r.nama_pegawai = p.nama_lengkap,
    r.nip_pegawai = p.nip;

UPDATE riwayat_mutasi r
JOIN pegawai p ON p.id_pegawai = r.id_pegawai
SET r.nama_pegawai = p.nama_lengkap,
    r.nip_pegawai = p.nip;
