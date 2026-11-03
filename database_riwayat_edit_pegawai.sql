CREATE TABLE IF NOT EXISTS riwayat_edit_pegawai (
    id_riwayat BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pegawai INT NOT NULL,
    nip VARCHAR(30) NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    id_jabatan INT NOT NULL,
    id_pangkat INT NULL,
    pendidikan_terakhir VARCHAR(100) NULL,
    tmt DATE NULL,
    status_aktif VARCHAR(30) NOT NULL,
    tanggal_perubahan DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_riwayat),
    KEY idx_riwayat_edit_pegawai_tanggal (id_pegawai, tanggal_perubahan, id_riwayat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
