<?php
require_once 'includes/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_login();

$tipe = ($_GET['tipe'] ?? '') === 'mutasi' ? 'mutasi' : 'promosi';
$tahunSekarang = (int) date('Y');
$tahun = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT);
if (!$tahun || $tahun < 1900 || $tahun > 2150) {
    $tahun = $tahunSekarang;
}

if ($tipe === 'promosi') {
    $tanggalEfektif = "CASE WHEN r.jenis_promosi = 'Keluar' AND r.tanggal_keluar IS NOT NULL
                             THEN r.tanggal_keluar
                             ELSE COALESCE(r.tanggal_melaksanakan, r.tanggal_sk) END";
    $sql = 'SELECT r.id_promosi AS id, r.no_sk, r.jenis_promosi AS jenis,
                   ' . $tanggalEfektif . ' AS tanggal_efektif,
                   COALESCE(p.nama_lengkap, r.nama_pegawai, CONCAT(\'Pegawai #\', r.id_pegawai)) AS nama,
                   COALESCE(p.nip, r.nip_pegawai, \'-\') AS nip,
                   j_lama.nama_jabatan AS jabatan_lama, pg_lama.golongan AS golongan_lama,
                   j_baru.nama_jabatan AS jabatan_baru, pg_baru.golongan AS golongan_baru,
                   r.tujuan
            FROM riwayat_promosi r
            LEFT JOIN pegawai p ON p.id_pegawai = r.id_pegawai
            LEFT JOIN jabatan j_lama ON j_lama.id_jabatan = r.id_jabatan_lama
            LEFT JOIN pangkat_golongan pg_lama ON pg_lama.id_pangkat = r.id_pangkat_lama
            LEFT JOIN jabatan j_baru ON j_baru.id_jabatan = r.id_jabatan_baru
            LEFT JOIN pangkat_golongan pg_baru ON pg_baru.id_pangkat = r.id_pangkat_baru
            WHERE ' . $tanggalEfektif . ' >= ? AND ' . $tanggalEfektif . ' < ?
            ORDER BY tanggal_efektif, nama, r.id_promosi';
} else {
    $tanggalEfektif = "CASE WHEN r.jenis_mutasi = 'Keluar' AND r.tanggal_keluar IS NOT NULL
                             THEN r.tanggal_keluar ELSE r.tanggal_sk END";
    $sql = 'SELECT r.id_mutasi AS id, r.no_sk, r.jenis_mutasi AS jenis,
                   ' . $tanggalEfektif . ' AS tanggal_efektif,
                   COALESCE(p.nama_lengkap, r.nama_pegawai, CONCAT(\'Pegawai #\', r.id_pegawai)) AS nama,
                   COALESCE(p.nip, r.nip_pegawai, \'-\') AS nip,
                   j.nama_jabatan AS jabatan_lama, pg.golongan AS golongan_lama,
                   r.tujuan
            FROM riwayat_mutasi r
            LEFT JOIN pegawai p ON p.id_pegawai = r.id_pegawai
            LEFT JOIN jabatan j ON j.id_jabatan = r.id_jabatan_saat_mutasi
            LEFT JOIN pangkat_golongan pg ON pg.id_pangkat = r.id_pangkat_saat_mutasi
            WHERE ' . $tanggalEfektif . ' >= ? AND ' . $tanggalEfektif . ' < ?
            ORDER BY tanggal_efektif, nama, r.id_mutasi';
}

$statement = $pdo->prepare($sql);
$statement->execute([$tahun . '-01-01', ($tahun + 1) . '-01-01']);
$rowsByMonth = array_fill(1, 12, []);
foreach ($statement->fetchAll() as $row) {
    $bulan = (int) date('n', strtotime($row['tanggal_efektif']));
    $rowsByMonth[$bulan][] = $row;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { color: #17211b; font-family: "DejaVu Sans", sans-serif; font-size: 9px; }
        h1 { margin: 0 0 4px; font-size: 16px; }
        p { margin: 0 0 14px; color: #46534b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th, td { padding: 6px; border: 1px solid #768078; text-align: left; vertical-align: top; }
        thead th { background: #e7eee9; font-size: 9px; }
        tbody th { width: 65px; background: #f1f5f2; }
        tr { page-break-inside: avoid; }
        .sub { margin-top: 3px; color: #566159; font-size: 8px; }
        .empty { color: #657168; font-style: italic; }
    </style>
</head>
<body>
    <h1>Rekap <?= e(ucfirst($tipe)) ?> Tahun <?= (int) $tahun ?></h1>
    <p>Pengadilan Negeri Yogyakarta</p>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th><?= $tipe === 'promosi' ? 'Tanggal efektif' : 'Tanggal' ?></th>
                <th>Nama / NIP</th>
                <th>Jenis perubahan</th>
                <th>Rincian</th>
                <th>Nomor SK</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rowsByMonth as $nomorBulan => $rowsBulan): ?>
                <?php if (!$rowsBulan): ?>
                    <tr>
                        <th><?= e(BLN[$nomorBulan - 1]) ?></th>
                        <td colspan="5" class="empty">Tidak ada transaksi</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rowsBulan as $index => $row): ?>
                        <tr>
                            <?php if ($index === 0): ?>
                                <th rowspan="<?= count($rowsBulan) ?>"><?= e(BLN[$nomorBulan - 1]) ?></th>
                            <?php endif; ?>
                            <td><?= e(tgl_id($row['tanggal_efektif'])) ?></td>
                            <td>
                                <strong><?= e($row['nama']) ?></strong>
                                <div class="sub">NIP <?= e($row['nip']) ?></div>
                            </td>
                            <td><?= e($row['jenis']) ?></td>
                            <td>
                                <?php if ($tipe === 'promosi'): ?>
                                    <?php if ($row['jenis'] === 'Keluar'): ?>
                                        Keluar dari <?= e($row['jabatan_lama'] ?? '-') ?>
                                    <?php else: ?>
                                        <?= e($row['jabatan_lama'] ?? '-') ?>
                                        &rarr;
                                        <?= e($row['jabatan_baru'] ?? '-') ?>
                                        <div class="sub">
                                            <?= e($row['golongan_lama'] ?? '-') ?>
                                            &rarr;
                                            <?= e($row['golongan_baru'] ?? '-') ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= e($row['jabatan_lama'] ?? '-') ?>
                                    <?= !empty($row['golongan_lama']) ? ' (' . e($row['golongan_lama']) . ')' : '' ?>
                                    <?php if (!empty($row['tujuan'])): ?>
                                        <div class="sub">Tujuan: <?= e($row['tujuan']) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['no_sk']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();
$pdf = new \Dompdf\Dompdf();
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$pdf->stream('rekap-' . $tipe . '-' . $tahun . '.pdf', ['Attachment' => true]);
exit;