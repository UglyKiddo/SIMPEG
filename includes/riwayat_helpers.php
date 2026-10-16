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
