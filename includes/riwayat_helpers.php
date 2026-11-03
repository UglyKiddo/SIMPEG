<?php
function tidak_ada_perubahan_riwayat_setelah($pegawaiId, $tipe, $transaksiId)
{
    global $pdo;

    $tabel = $tipe === 'promosi' ? 'riwayat_promosi' : 'riwayat_mutasi';
    $kolomId = $tipe === 'promosi' ? 'id_promosi' : 'id_mutasi';
    $kondisiMutasi = $tipe === 'mutasi' ? " AND sesudah.jenis_mutasi <> 'Internal'" : '';
    $statement = $pdo->prepare(
        "SELECT NOT EXISTS (
             SELECT 1
             FROM $tabel sesudah
             JOIN $tabel target ON target.$kolomId = ?
             WHERE sesudah.id_pegawai = ?
               AND sesudah.$kolomId <> target.$kolomId
               $kondisiMutasi
               AND (
                   sesudah.created_at > target.created_at
                   OR (
                       sesudah.created_at = target.created_at
                       AND sesudah.$kolomId > target.$kolomId
                   )
               )
         )"
    );
    $statement->execute([$transaksiId, $pegawaiId]);

    return (bool) $statement->fetchColumn();
}

function pegawai_per_tanggal($tanggal)
{
    global $pdo;

    $pegawai = [];
    foreach ($pdo->query(
        'SELECT id_pegawai AS id, nama_lengkap AS nama, nip, id_jabatan, id_pangkat,
                pendidikan_terakhir AS pendidikan, tmt, status_aktif AS status
         FROM pegawai'
    )->fetchAll() as $row) {
        $pegawai[(int) $row['id']] = $row;
    }

    $keluarTerakhir = $pdo->query(
        "SELECT r.id_promosi, r.id_pegawai, r.nama_pegawai AS nama, r.nip_pegawai AS nip,
                r.id_jabatan_lama AS id_jabatan, r.id_pangkat_lama AS id_pangkat,
                COALESCE(r.tanggal_keluar, r.tanggal_sk) AS tanggal_efektif
         FROM riwayat_promosi r
         LEFT JOIN pegawai p ON p.id_pegawai = r.id_pegawai
         WHERE p.id_pegawai IS NULL AND r.jenis_promosi = 'Keluar'
         ORDER BY tanggal_efektif DESC, r.id_promosi DESC"
    )->fetchAll();
    foreach ($keluarTerakhir as $row) {
        $id = (int) $row['id_pegawai'];
        if (isset($pegawai[$id]) || !isset($row['tanggal_efektif']) || $tanggal >= $row['tanggal_efektif']) {
            continue;
        }
        $pegawai[$id] = [
            'id' => $id,
            'nama' => $row['nama'] ?? '',
            'nip' => $row['nip'] ?? '',
            'id_jabatan' => $row['id_jabatan'],
            'id_pangkat' => $row['id_pangkat'],
            'pendidikan' => null,
            'tmt' => null,
            'status' => 'Non-Aktif',
        ];
    }

    $snapshot = $pdo->prepare(
        'SELECT id_pegawai AS id, nip, nama_lengkap AS nama, id_jabatan, id_pangkat,
                pendidikan_terakhir AS pendidikan, tmt, status_aktif AS status
         FROM riwayat_edit_pegawai
        WHERE tanggal_perubahan > ?
        ORDER BY id_pegawai, tanggal_perubahan, id_riwayat'
    );
    $snapshot->execute([$tanggal]);
    $snapshotDiterapkan = [];
    foreach ($snapshot->fetchAll() as $row) {
        $id = (int) $row['id'];
        if (isset($pegawai[$id]) && !isset($snapshotDiterapkan[$id])) {
            $pegawai[$id] = $row;
            $snapshotDiterapkan[$id] = true;
        }
    }

    $perubahan = [];
    foreach ($pdo->query(
        'SELECT id_pegawai, jenis_promosi AS jenis, tanggal_sk, tanggal_melaksanakan, tanggal_keluar,
                id_jabatan_lama, id_pangkat_lama, created_at, id_promosi AS id_transaksi,
                \'promosi\' AS tipe
         FROM riwayat_promosi'
    )->fetchAll() as $row) {
        $row['tanggal_efektif'] = $row['jenis'] === 'Keluar' && $row['tanggal_keluar']
            ? $row['tanggal_keluar']
            : ($row['tanggal_melaksanakan'] ?? $row['tanggal_sk']);
        $perubahan[] = $row;
    }
    foreach ($pdo->query(
        'SELECT id_pegawai, jenis_mutasi AS jenis, tanggal_sk, tanggal_keluar,
                created_at, id_mutasi AS id_transaksi, \'mutasi\' AS tipe
         FROM riwayat_mutasi'
    )->fetchAll() as $row) {
        $row['tanggal_efektif'] = $row['jenis'] === 'Keluar' && $row['tanggal_keluar']
            ? $row['tanggal_keluar']
            : $row['tanggal_sk'];
        $perubahan[] = $row;
    }

    usort($perubahan, static function ($a, $b) {
        $urutTanggal = strcmp((string) $b['tanggal_efektif'], (string) $a['tanggal_efektif']);
        if ($urutTanggal !== 0) {
            return $urutTanggal;
        }
        $urutDibuat = strcmp((string) $b['created_at'], (string) $a['created_at']);
        return $urutDibuat !== 0
            ? $urutDibuat
            : (int) $b['id_transaksi'] <=> (int) $a['id_transaksi'];
    });

    foreach ($pegawai as &$row) {
        if ($row['tmt'] !== null && $row['tmt'] > $tanggal) {
            unset($pegawai[(int) $row['id']]);
            continue;
        }
        foreach ($perubahan as $event) {
            if ((int) $event['id_pegawai'] !== (int) $row['id'] || $event['tanggal_efektif'] <= $tanggal) {
                continue;
            }
            if ($event['tipe'] === 'promosi') {
                if ($event['jenis'] === 'Keluar') {
                    $row['status'] = 'Aktif';
                } else {
                    $row['id_jabatan'] = $event['id_jabatan_lama'];
                    $row['id_pangkat'] = $event['id_pangkat_lama'];
                }
            } elseif ($event['jenis'] === 'Masuk') {
                $row['status'] = 'Non-Aktif';
            } elseif ($event['jenis'] === 'Keluar') {
                $row['status'] = 'Aktif';
            }
        }
    }
    unset($row);

    return array_values($pegawai);
}

function path_file_lampiran($filePath, $fileName)
{
    if (!preg_match('/\A[a-f0-9]{32}\.[a-z0-9]+\z/', $fileName)) {
        return null;
    }

    if ($filePath === 'private/lampiran-transaksi/' . $fileName) {
        return dirname(__DIR__, 3)
            . DIRECTORY_SEPARATOR . 'simpeg-pn-yogya-storage'
            . DIRECTORY_SEPARATOR . 'lampiran-transaksi'
            . DIRECTORY_SEPARATOR . $fileName;
    }

    return null;
}
