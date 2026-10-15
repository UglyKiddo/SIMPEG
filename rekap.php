<?php
require_once 'includes/config.php';
require_once 'includes/riwayat_helpers.php';
require_login();

$tipe = ($_GET['tipe'] ?? '') === 'mutasi' ? 'mutasi' : 'promosi';
$periode = $_GET['periode'] ?? 'bulanan';
$bulan = $_GET['bulan'] ?? '';
$triwulan = $_GET['triwulan'] ?? '';
$tahun = $_GET['tahun'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_riwayat'])) {
    $tipeHapus = $_POST['tipe'] ?? '';
    $idHapus = filter_input(INPUT_POST, 'hapus_riwayat', FILTER_VALIDATE_INT);
    if (!csrf_ok() || !in_array($tipeHapus, ['promosi', 'mutasi'], true) || !$idHapus) {
        flash('Permintaan hapus tidak valid. Muat ulang halaman dan coba lagi.', true);
        header('Location: rekap.php?tipe=' . $tipe);
        exit;
    }

    $tabelRiwayatHapus = $tipeHapus === 'promosi' ? 'riwayat_promosi' : 'riwayat_mutasi';
    $kolomIdHapus = $tipeHapus === 'promosi' ? 'id_promosi' : 'id_mutasi';
    $fileFisikHapus = [];
    try {
        $pdo->beginTransaction();
        $ambilRiwayat = $pdo->prepare(
            "SELECT * FROM $tabelRiwayatHapus WHERE $kolomIdHapus = ? FOR UPDATE"
        );
        $ambilRiwayat->execute([$idHapus]);
        $riwayatHapus = $ambilRiwayat->fetch();
        if (!$riwayatHapus) {
            throw new RuntimeException('Riwayat tidak ditemukan.');
        }

        $pegawaiIdHapus = (int) $riwayatHapus['id_pegawai'];
        if (tidak_ada_perubahan_riwayat_setelah($pegawaiIdHapus, $tipeHapus, $idHapus)) {
            $ambilPegawai = $pdo->prepare(
                'SELECT id_jabatan, id_pangkat, status_aktif
                 FROM pegawai WHERE id_pegawai = ? FOR UPDATE'
            );
            $ambilPegawai->execute([$pegawaiIdHapus]);
            $pegawaiHapus = $ambilPegawai->fetch();
            if (!$pegawaiHapus) {
                throw new RuntimeException('Pegawai terkait tidak ditemukan.');
            }

            if ($tipeHapus === 'promosi') {
                $kondisiPangkatSama = $pegawaiHapus['id_pangkat'] === null
                    ? $riwayatHapus['id_pangkat_baru'] === null
                    : (int) $pegawaiHapus['id_pangkat'] === (int) $riwayatHapus['id_pangkat_baru'];
                if (
                    (int) $pegawaiHapus['id_jabatan'] !== (int) $riwayatHapus['id_jabatan_baru']
                    || !$kondisiPangkatSama
                ) {
                    throw new RuntimeException(
                        'Data jabatan/pangkat pegawai sudah berubah. Sesuaikan data pegawai secara manual sebelum menghapus promosi terakhir.'
                    );
                }
                $pdo->prepare(
                    'UPDATE pegawai SET id_jabatan = ?, id_pangkat = ? WHERE id_pegawai = ?'
                )->execute([
                    $riwayatHapus['id_jabatan_lama'],
                    $riwayatHapus['id_pangkat_lama'],
                    $pegawaiIdHapus,
                ]);
            } elseif ($riwayatHapus['jenis_mutasi'] === 'Masuk') {
                if ($pegawaiHapus['status_aktif'] !== 'Aktif') {
                    throw new RuntimeException(
                        'Status pegawai sudah berubah. Sesuaikan status pegawai secara manual sebelum menghapus mutasi terakhir.'
                    );
                }
                $pdo->prepare(
                    "UPDATE pegawai SET status_aktif = 'Non-Aktif' WHERE id_pegawai = ?"
                )->execute([$pegawaiIdHapus]);
            } elseif ($riwayatHapus['jenis_mutasi'] === 'Keluar') {
                if ($pegawaiHapus['status_aktif'] !== 'Non-Aktif') {
                    throw new RuntimeException(
                        'Status pegawai sudah berubah. Sesuaikan status pegawai secara manual sebelum menghapus mutasi terakhir.'
                    );
                }
                $pdo->prepare(
                    "UPDATE pegawai SET status_aktif = 'Aktif' WHERE id_pegawai = ?"
                )->execute([$pegawaiIdHapus]);
            }
        }

        $lampiranHapus = $pdo->prepare(
            'SELECT file_path, nama_file
             FROM lampiran_transaksi
             WHERE id_transaksi = ?
               AND (tipe_transaksi = ? OR (tipe_transaksi IS NULL AND jenis_transaksi = ?))'
        );
        $lampiranHapus->execute([$idHapus, $tipeHapus, $tipeHapus]);
        foreach ($lampiranHapus->fetchAll() as $fileHapus) {
            $pathHapus = path_file_lampiran($fileHapus['file_path'] ?? '', $fileHapus['nama_file']);
            if ($pathHapus !== null) {
                $fileFisikHapus[] = $pathHapus;
            }
        }
        $pdo->prepare(
            'DELETE FROM lampiran_transaksi
             WHERE id_transaksi = ?
               AND (tipe_transaksi = ? OR (tipe_transaksi IS NULL AND jenis_transaksi = ?))'
        )->execute([$idHapus, $tipeHapus, $tipeHapus]);
        $pdo->prepare(
            "DELETE FROM $tabelRiwayatHapus WHERE $kolomIdHapus = ?"
        )->execute([$idHapus]);
        $pdo->commit();

        $pembersihanFileGagal = false;
        foreach ($fileFisikHapus as $pathHapus) {
            if (is_file($pathHapus) && !unlink($pathHapus)) {
                error_log('Gagal menghapus file lampiran riwayat: ' . $pathHapus);
                $pembersihanFileGagal = true;
            }
        }
        flash(
            $pembersihanFileGagal
                ? 'Riwayat dihapus, tetapi sebagian file lampiran gagal dibersihkan.'
                : 'Riwayat ' . $tipeHapus . ' berhasil dihapus.',
            $pembersihanFileGagal
        );
    } catch (RuntimeException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash($exception->getMessage(), true);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Gagal menghapus riwayat transaksi: ' . $exception->getMessage());
        flash('Gagal menghapus riwayat. Periksa struktur database dan lampiran.', true);
    }

    $redirectQuery = array_filter([
        'tipe' => $tipe,
        'periode' => $periode,
        'bulan' => $bulan,
        'triwulan' => $triwulan,
        'tahun' => $tahun,
    ], static function ($value) {
        return $value !== '';
    });
    header('Location: rekap.php?' . http_build_query($redirectQuery));
    exit;
}

$params = [];

if ($tipe === 'promosi') {
    $sql = 'SELECT r.id_promosi AS id, r.id_pegawai, r.no_sk, r.tanggal_sk, r.tujuan, r.keterangan,
                p.nama_lengkap AS nama, p.nip,
                j_lama.nama_jabatan AS jabatan_lama,
                pg_lama.golongan AS golongan_lama,
                j_baru.nama_jabatan AS jabatan_baru,
                pg_baru.golongan AS golongan_baru
            FROM riwayat_promosi r
            JOIN pegawai p ON p.id_pegawai = r.id_pegawai
            LEFT JOIN jabatan j_lama ON j_lama.id_jabatan = r.id_jabatan_lama
            LEFT JOIN pangkat_golongan pg_lama ON pg_lama.id_pangkat = r.id_pangkat_lama
            JOIN jabatan j_baru ON j_baru.id_jabatan = r.id_jabatan_baru
            LEFT JOIN pangkat_golongan pg_baru ON pg_baru.id_pangkat = r.id_pangkat_baru';
} else {
    $sql = 'SELECT r.id_mutasi AS id, r.id_pegawai, r.no_sk, r.tanggal_sk, r.jenis_mutasi, r.tujuan, r.keterangan,
                p.nama_lengkap AS nama, p.nip,
                j.nama_jabatan AS jabatan_lama, pg.golongan AS golongan_lama
            FROM riwayat_mutasi r
            JOIN pegawai p ON p.id_pegawai = r.id_pegawai
            LEFT JOIN jabatan j ON j.id_jabatan = r.id_jabatan_saat_mutasi
            LEFT JOIN pangkat_golongan pg ON pg.id_pangkat = r.id_pangkat_saat_mutasi';
}

$periodePilihan = ['bulanan', 'triwulan', 'tahunan'];
if (!in_array($periode, $periodePilihan, true)) {
    $periode = 'bulanan';
}

$tabelRiwayat = $tipe === 'promosi' ? 'riwayat_promosi' : 'riwayat_mutasi';
$tahunList = $pdo->query(
    "SELECT DISTINCT YEAR(tanggal_sk) AS tahun
     FROM $tabelRiwayat
     WHERE tanggal_sk IS NOT NULL
     ORDER BY tahun DESC"
)->fetchAll(PDO::FETCH_COLUMN);
$tahunList = array_map('intval', $tahunList);
$tahunList[] = (int) date('Y');
$tahunList = array_values(array_unique($tahunList));
rsort($tahunList);

$kondisiPeriode = [];
if ($periode === 'bulanan') {
    if (ctype_digit((string) $bulan) && (int) $bulan >= 1 && (int) $bulan <= 12) {
        $kondisiPeriode[] = 'MONTH(r.tanggal_sk) = ?';
        $params[] = (int) $bulan;
    } else {
        $bulan = '';
    }
} elseif ($periode === 'triwulan') {
    if (ctype_digit((string) $triwulan) && (int) $triwulan >= 1 && (int) $triwulan <= 4) {
        $kondisiPeriode[] = 'QUARTER(r.tanggal_sk) = ?';
        $params[] = (int) $triwulan;
    } else {
        $triwulan = '';
    }
} else {
    $bulan = '';
    $triwulan = '';
}

if ($tahun !== '' && ctype_digit((string) $tahun) && in_array((int) $tahun, $tahunList, true)) {
    $kondisiPeriode[] = 'YEAR(r.tanggal_sk) = ?';
    $params[] = (int) $tahun;
} else {
    $tahun = '';
}

if ($kondisiPeriode) {
    $sql .= ' WHERE ' . implode(' AND ', $kondisiPeriode);
}

if ($periode !== 'bulanan') {
    $bulan = '';
}

$labelPeriode = '';
if ($periode === 'bulanan' && $bulan !== '') {
    $labelPeriode = 'bulan ' . BLN[(int) $bulan - 1];
} elseif ($periode === 'triwulan' && $triwulan !== '') {
    $labelPeriode = 'triwulan ' . $triwulan;
} elseif ($periode === 'tahunan') {
    $labelPeriode = 'tahun';
}
if ($tahun !== '') {
    $labelPeriode .= ($labelPeriode !== '' ? ' ' : '') . $tahun;
}

$sql .= ' ORDER BY r.tanggal_sk DESC, r.' . ($tipe === 'promosi' ? 'id_promosi' : 'id_mutasi') . ' DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$rows = $statement->fetchAll();

$lampiranPerTransaksi = [];
if ($rows) {
    $idTransaksi = array_map('intval', array_column($rows, 'id'));
    $placeholder = implode(',', array_fill(0, count($idTransaksi), '?'));
    $lampiranStatement = $pdo->prepare(
        "SELECT id_lampiran, id_transaksi, nama_asli
         FROM lampiran_transaksi
         WHERE tipe_transaksi = ? AND id_transaksi IN ($placeholder)
         ORDER BY id_lampiran"
    );
    $lampiranStatement->execute(array_merge([$tipe], $idTransaksi));
    foreach ($lampiranStatement->fetchAll() as $lampiran) {
        $lampiranPerTransaksi[(int) $lampiran['id_transaksi']][] = $lampiran;
    }
}

$isPromosi = $tipe === 'promosi';
$judul = 'Rekap ' . $tipe;
include 'includes/header.php';
?>

<div class="card">
    <h2>Rekap pegawai <?= e($tipe) ?></h2>

    <form method="get" class="fl report-filters">
        <input type="hidden" name="tipe" value="<?= e($tipe) ?>">
        <label>
            Periode laporan
            <select name="periode" id="periode-laporan">
                <option value="bulanan" <?= $periode === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                <option value="triwulan" <?= $periode === 'triwulan' ? 'selected' : '' ?>>Triwulan</option>
                <option value="tahunan" <?= $periode === 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
            </select>
        </label>

        <label data-period-field="bulanan" <?= $periode !== 'bulanan' ? 'hidden' : '' ?>>
            Bulan SK
            <select name="bulan">
                <?= bln_opts($bulan) ?>
            </select>
        </label>

        <label data-period-field="triwulan" <?= $periode !== 'triwulan' ? 'hidden' : '' ?>>
            Triwulan
            <select name="triwulan">
                <option value="">Semua triwulan</option>
                <?php foreach ([1, 2, 3, 4] as $nomorTriwulan): ?>
                    <option value="<?= $nomorTriwulan ?>" <?= (string) $triwulan === (string) $nomorTriwulan ? 'selected' : '' ?>>
                        Triwulan <?= $nomorTriwulan ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Tahun
            <select name="tahun">
                <option value="">Semua tahun</option>
                <?php foreach ($tahunList as $pilihanTahun): ?>
                    <option value="<?= $pilihanTahun ?>" <?= (string) $tahun === (string) $pilihanTahun ? 'selected' : '' ?>>
                        <?= $pilihanTahun ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div>
            <br>
            <button class="btn" type="submit">Terapkan</button>
            <a class="btn g" style="color:var(--mu)" href="rekap.php?tipe=<?= e($tipe) ?>">Reset</a>
        </div>
    </form>

    <div class="cnt">
        <?= count($rows) ?> data<?= $labelPeriode !== '' ? ' pada ' . e($labelPeriode) : '' ?>
    </div>

    <div class="scroll">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <?php if ($isPromosi): ?>
                        <th>Nomor SK</th>
                        <th>Hari/tanggal</th>
                        <th>Dari</th>
                        <th>Menjadi</th>
                        <th>Tujuan</th>
                        <th>Keterangan</th>
                    <?php else: ?>
                        <th>Nomor SK</th>
                        <th>Hari/tanggal</th>
                        <th>Jenis mutasi</th>
                        <th>Jabatan/golongan</th>
                        <th>Tujuan</th>
                        <th>Keterangan</th>
                    <?php endif; ?>
                    <th>Lampiran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="10" class="em">
                            Belum ada data<?= $labelPeriode !== '' ? ' untuk ' . e($labelPeriode) : '' ?>.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <div class="nm"><?= e($row['nama']) ?></div>
                            <div class="sub">NIP <?= e($row['nip']) ?></div>
                        </td>
                        <?php if ($isPromosi): ?>
                            <td><?= e($row['no_sk']) ?></td>
                            <td><?= e(tgl_id($row['tanggal_sk'])) ?></td>
                            <td>
                                <?= e($row['golongan_lama'] ?? '-') ?>
                                <div class="sub"><?= e($row['jabatan_lama'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?= e($row['golongan_baru'] ?? '-') ?>
                                <div class="sub"><?= e($row['jabatan_baru']) ?></div>
                            </td>
                            <td><?= e($row['tujuan']) ?></td>
                            <td><?= $row['keterangan'] !== null && $row['keterangan'] !== '' ? nl2br(e($row['keterangan'])) : '-' ?></td>
                        <?php else: ?>
                            <td><?= e($row['no_sk']) ?></td>
                            <td><?= e(tgl_id($row['tanggal_sk'])) ?></td>
                            <td><?= e($row['jenis_mutasi']) ?></td>
                            <td>
                                <?= e($row['golongan_lama'] ?? '-') ?>
                                <div class="sub"><?= e($row['jabatan_lama']) ?></div>
                            </td>
                            <td><?= e($row['tujuan']) ?></td>
                            <td><?= $row['keterangan'] !== null && $row['keterangan'] !== '' ? nl2br(e($row['keterangan'])) : '-' ?></td>
                        <?php endif; ?>
                        <td>
                            <?php foreach ($lampiranPerTransaksi[(int) $row['id']] ?? [] as $lampiran): ?>
                                <a
                                    class="attachment-link"
                                    href="lampiran.php?id=<?= (int) $lampiran['id_lampiran'] ?>"
                                    title="<?= e($lampiran['nama_asli']) ?>"
                                    aria-label="Lihat lampiran: <?= e($lampiran['nama_asli']) ?>"
                                >
                                    Lihat lampiran
                                </a><br>
                            <?php endforeach; ?>
                            <?php if (empty($lampiranPerTransaksi[(int) $row['id']])): ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a
                                    class="btn edit"
                                    href="edit_riwayat.php?tipe=<?= e($tipe) ?>&amp;id=<?= (int) $row['id'] ?>"
                                >Edit</a>
                                <form
                                    method="post"
                                    onsubmit="return confirm('Hapus riwayat ini? Jika merupakan transaksi terakhir, data pegawai akan dikembalikan ke kondisi sebelumnya.')"
                                >
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tipe" value="<?= e($tipe) ?>">
                                    <input type="hidden" name="hapus_riwayat" value="<?= (int) $row['id'] ?>">
                                    <button class="btn g" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const periodeLaporan = document.getElementById('periode-laporan');
    const fieldPeriode = document.querySelectorAll('[data-period-field]');

    function tampilkanFilterPeriode() {
        fieldPeriode.forEach((field) => {
            field.hidden = field.dataset.periodField !== periodeLaporan.value;
        });
    }

    periodeLaporan.addEventListener('change', tampilkanFilterPeriode);
</script>

<?php include 'includes/footer.php'; ?>
