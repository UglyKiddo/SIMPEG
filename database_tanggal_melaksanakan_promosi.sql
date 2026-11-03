ALTER TABLE riwayat_promosi
    ADD COLUMN tanggal_melaksanakan DATE NULL AFTER tanggal_sk;

UPDATE riwayat_promosi
SET tanggal_melaksanakan = tanggal_sk
WHERE jenis_promosi = 'Naik Jabatan'
  AND tanggal_melaksanakan IS NULL;
