<?php
function kelompok_export_pegawai($pegawai)
{
    $jabatan = trim((string) ($pegawai['jabatan'] ?? ''));
    $jabatanLower = mb_strtolower($jabatan);
    $kategori = $pegawai['kategori'] ?? '';

    if ($kategori === 'Hakim') {
        return 'hakim';
    }
    if (strpos($jabatanLower, 'panitera pengganti') !== false) {
        return 'panitera_pengganti';
    }
    if (strpos($jabatanLower, 'juru sita') !== false || strpos($jabatanLower, 'jurusita') !== false) {
        return 'jurusita';
    }
    if ($kategori === 'PPNPN') {
        return 'ppnpn';
    }
    if (
        strpos($jabatanLower, 'panitera') === 0
        || $jabatanLower === 'sekretaris'
        || strpos($jabatanLower, 'kepala sub bagian') !== false
    ) {
        return 'struktural';
    }
    if ($kategori === 'Pegawai') {
        return 'fungsional';
    }

    return 'lainnya';
}

function kelas_warna_pendidikan_export($pendidikan)
{
    $warna = [
        'S1' => '#d9eaf7',
        'S2' => '#e6e0f8',
        'D3' => '#fce4ec',
        'SMA' => '#e0e0e0',
        'SMP' => '#fff2cc',
        'SD' => '#f4cccc',
    ];

    return $warna[strtoupper(trim((string) $pendidikan))] ?? '';
}

function unduh_data_pegawai(array $pegawai, $tanggal, $format)
{
    $kelompok = [
        'hakim' => 'KETUA, WAKIL KETUA DAN HAKIM',
        'struktural' => 'STRUKTURAL KEPANITERAAN (TEKNIS)',
        'panitera_pengganti' => 'PANITERA PENGGANTI',
        'jurusita' => 'JURUSITA',
        'fungsional' => 'FUNGSIONAL/PELAKSANA',
        'ppnpn' => 'PPNPN',
        'lainnya' => 'LAINNYA',
    ];
    $pegawaiPerKelompok = array_fill_keys(array_keys($kelompok), []);
    foreach ($pegawai as $row) {
        $pegawaiPerKelompok[kelompok_export_pegawai($row)][] = $row;
    }

    $bulan = strtoupper(BLN[(int) substr($tanggal, 5, 2) - 1]);
    $periode = $bulan . ' ' . substr($tanggal, 0, 4);
    $namaFile = 'daftar-pegawai-' . $tanggal . '.' . $format;
    if ($format === 'xls') {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    } else {
        header('Content-Type: application/msword; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    }
    header('X-Content-Type-Options: nosniff');
    echo "\xEF\xBB\xBF";
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Daftar Pegawai - <?= e($periode) ?></title>
        <style>
            @page Section1 {
                size: 21.59cm 35.56cm;
                margin: 1.3cm;
                mso-page-orientation: portrait;
            }
            div.Section1 { page: Section1; }
            body { font-family: Arial, sans-serif; font-size: 9pt; }
            h1, h2 { text-align: center; margin: 0; }
            h1 { font-size: 12pt; }
            h2 { font-size: 10pt; }
            .periode { text-align: center; font-weight: bold; margin: 4px 0 10px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #222; padding: 3px 5px; }
            th { text-align: center; background: #e8edf2; }
            td.nomor { text-align: center; }
            td.nip { mso-number-format: "\@"; white-space: nowrap; }
            td.pendidikan { text-align: center; }
            tr.kelompok td { font-weight: bold; background: #e5e5e5; }
        </style>
    </head>
    <body>
    <div class="Section1">
        <h1>DAFTAR HAKIM, PANITERA, WAKIL PANITERA, PANITERA MUDA,</h1>
        <h2>PANITERA PENGGANTI, JURU SITA, JURU SITA PENGGANTI, STAF, PPK, DAN PPNPN</h2>
        <h2>PENGADILAN NEGERI YOGYAKARTA</h2>
        <div class="periode">BULAN: <?= e($periode) ?></div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Jabatan</th>
                    <th>Pangkat</th>
                    <th>Pend.</th>
                </tr>
            </thead>
            <tbody>
                <?php $nomor = 0; ?>
                <?php foreach ($pegawaiPerKelompok as $key => $daftarKelompok): ?>
                    <?php if (!$daftarKelompok) { continue; } ?>
                    <tr class="kelompok">
                        <td colspan="7"><?= e($kelompok[$key]) ?></td>
                    </tr>
                    <?php foreach ($daftarKelompok as $nomorKelompok => $row): ?>
                        <?php $nomor++; ?>
                        <tr>
                            <td class="nomor"><?= $nomor ?></td>
                            <td class="nomor"><?= $nomorKelompok + 1 ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td class="nip"><?= e($row['nip']) ?></td>
                            <td><?= e($row['jabatan'] ?? '-') ?></td>
                            <td>
                                <?= e($row['nama_pangkat'] ?? '-') ?>
                                <?= !empty($row['golongan']) ? ' (' . e($row['golongan']) . ')' : '' ?>
                            </td>
                            <td
                                class="pendidikan"
                                style="<?= e(kelas_warna_pendidikan_export($row['pendidikan'] ?? '') !== ''
                                    ? 'background-color: ' . kelas_warna_pendidikan_export($row['pendidikan']) . ';'
                                    : '') ?>"
                            >
                                <?= e($row['pendidikan'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (!$pegawai): ?>
                    <tr>
                        <td colspan="7">Tidak ada pegawai yang cocok dengan kriteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </body>
    </html>
    <?php
}
