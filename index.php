<?php
require_once 'includes/config.php';
require_once 'includes/riwayat_helpers.php';
require_login();

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
if (!valid_date($tanggal)) {
    $tanggal = date('Y-m-d');
}
$modeHistoris = $tanggal !== date('Y-m-d');

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && csrf_ok()
    && isset($_POST['hapus'])
    && !$modeHistoris
) {
    $pdo->prepare('DELETE FROM pegawai WHERE id_pegawai = ?')
        ->execute([(int) $_POST['hapus']]);

    flash('Data pegawai dihapus.');
    header('Location: index.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$bln = $_GET['bulan'] ?? '';
$jumlah = $_GET['jumlah'] ?? '25';
if (!in_array($jumlah, ['25', '50', '100', 'semua'], true)) {
    $jumlah = '25';
}
$filter = [
    'golongan' => $_GET['golongan'] ?? '',
    'pendidikan' => $_GET['pendidikan'] ?? '',
    'jabatan' => $_GET['jabatan'] ?? '',
    'kelompok' => $_GET['kelompok'] ?? '',
];
$sortColumns = [
    'nomor' => 'p.id_pegawai',
    'nama' => 'p.nama_lengkap',
    'jabatan' => 'j.nama_jabatan',
];
$sort = $_GET['sort'] ?? '';
$arah = $_GET['arah'] ?? '';
if (!isset($sortColumns[$sort]) || !in_array($arah, ['asc', 'desc'], true)) {
    $sort = '';
    $arah = '';
}
$sortUrl = function ($kolom) use ($q, $bln, $tanggal, $filter, $jumlah, $sort, $arah) {
    $query = array_merge(
        ['q' => $q, 'bulan' => $bln, 'tanggal' => $tanggal],
        $filter,
        ['jumlah' => $jumlah]
    );
    if ($sort !== $kolom) {
        $query['sort'] = $kolom;
        $query['arah'] = 'asc';
    } elseif ($arah === 'asc') {
        $query['sort'] = $kolom;
        $query['arah'] = 'desc';
    }
    return 'index.php?' . http_build_query($query);
};
$pangkatList = daftar_pangkat();
$jabatanList = daftar_jabatan();
$pendidikanList = daftar_pendidikan();
$kelompokList = array_values(array_unique(array_column($jabatanList, 'kategori')));
$jabatanById = [];
foreach ($jabatanList as $jabatan) {
    $jabatanById[(int) $jabatan['id_jabatan']] = $jabatan;
}
$pangkatById = [];
foreach ($pangkatList as $pangkat) {
    $pangkatById[(int) $pangkat['id_pangkat']] = $pangkat;
}

$pegawaiTanggal = pegawai_per_tanggal($tanggal);
foreach ($pegawaiTanggal as &$pegawai) {
    $jabatan = $jabatanById[(int) $pegawai['id_jabatan']] ?? null;
    $pangkat = $pegawai['id_pangkat'] === null
        ? null
        : ($pangkatById[(int) $pegawai['id_pangkat']] ?? null);
    $pegawai['jabatan'] = $jabatan['nama_jabatan'] ?? null;
    $pegawai['kategori'] = $jabatan['kategori'] ?? null;
    $pegawai['golongan'] = $pangkat['golongan'] ?? null;
    $pegawai['nama_pangkat'] = $pangkat['nama_pangkat'] ?? null;
}
unset($pegawai);

$rows = array_values(array_filter($pegawaiTanggal, static function ($pegawai) use ($q, $bln, $filter) {
    if ($q !== '' && stripos($pegawai['nama'] . ' ' . $pegawai['nip'], $q) === false) {
        return false;
    }
    if ($bln !== '' && (
        $pegawai['tmt'] === null
        || (int) date('n', strtotime($pegawai['tmt'])) !== (int) $bln
    )) {
        return false;
    }
    return ($filter['golongan'] === '' || $pegawai['golongan'] === $filter['golongan'])
        && ($filter['pendidikan'] === '' || $pegawai['pendidikan'] === $filter['pendidikan'])
        && ($filter['jabatan'] === '' || $pegawai['jabatan'] === $filter['jabatan'])
        && ($filter['kelompok'] === '' || $pegawai['kategori'] === $filter['kelompok']);
}));

usort($rows, static function ($a, $b) use ($sort, $arah) {
    if ($sort === 'nomor') {
        $hasil = (int) $a['id'] <=> (int) $b['id'];
    } elseif ($sort === 'nama') {
        $hasil = strcasecmp($a['nama'], $b['nama']);
    } elseif ($sort === 'jabatan') {
        $hasil = strcasecmp((string) $a['jabatan'], (string) $b['jabatan']);
    } else {
        $hasil = (int) $a['id_jabatan'] <=> (int) $b['id_jabatan'];
        if ($hasil === 0) {
            $hasil = strcasecmp($a['nama'], $b['nama']);
        }
    }
    if ($sort !== '' && $arah === 'desc') {
        $hasil *= -1;
    }
    return $hasil !== 0 ? $hasil : ((int) $a['id'] <=> (int) $b['id']);
});
$total = count($rows);
if ($jumlah !== 'semua') {
    $rows = array_slice($rows, 0, (int) $jumlah);
}

$formatUnduh = $_GET['unduh'] ?? '';
if (in_array($formatUnduh, ['doc', 'xls'], true)) {
    require_once 'includes/pegawai_export.php';
    unduh_data_pegawai($rows, $tanggal, $formatUnduh);
    exit;
}

$parameterUnduh = array_merge(
    ['tanggal' => $tanggal, 'q' => $q, 'bulan' => $bln],
    $filter,
    ['jumlah' => $jumlah]
);
if ($sort !== '') {
    $parameterUnduh['sort'] = $sort;
    $parameterUnduh['arah'] = $arah;
}
$urlUnduhWord = 'index.php?' . http_build_query(array_merge($parameterUnduh, ['unduh' => 'doc']));
$urlUnduhExcel = 'index.php?' . http_build_query(array_merge($parameterUnduh, ['unduh' => 'xls']));

$aktif = array_values(array_filter($pegawaiTanggal, static function ($pegawai) {
    return $pegawai['status'] === 'Aktif';
}));
$jumlahKategori = [
    'Hakim' => 0,
    'Panitera' => 0,
    'Juru Sita' => 0,
    'Kesekretariatan' => 0,
    'Fungsional/Pelaksana' => 0,
    'PPNPN' => 0,
    'Lainnya' => 0,
];
foreach ($aktif as $pegawai) {
    $kategori = $pegawai['kategori'];
    $namaJabatan = $pegawai['jabatan'] ?? '';
    if ($kategori === 'Hakim') {
        $kategoriStat = 'Hakim';
    } elseif ($kategori === 'PPNPN') {
        $kategoriStat = 'PPNPN';
    } elseif (strpos($namaJabatan, 'Panitera') === 0) {
        $kategoriStat = 'Panitera';
    } elseif (strpos($namaJabatan, 'Juru Sita') === 0) {
        $kategoriStat = 'Juru Sita';
    } elseif ($namaJabatan === 'Sekretaris' || strpos($namaJabatan, 'Kepala Sub Bagian') !== false) {
        $kategoriStat = 'Kesekretariatan';
    } elseif ($kategori === 'Pegawai') {
        $kategoriStat = 'Fungsional/Pelaksana';
    } else {
        $kategoriStat = 'Lainnya';
    }
    $jumlahKategori[$kategoriStat]++;
}
$statsJabatan = [];
foreach ($jumlahKategori as $kategori => $jumlahKategoriStat) {
    if ($jumlahKategoriStat > 0) {
        $statsJabatan[] = ['kategori' => $kategori, 'jumlah' => $jumlahKategoriStat];
    }
}
$countPromosi = $pdo->prepare(
    "SELECT COUNT(*) FROM riwayat_promosi
     WHERE (CASE WHEN jenis_promosi = 'Keluar' AND tanggal_keluar IS NOT NULL
                 THEN tanggal_keluar ELSE tanggal_sk END) <= ?"
);
$countPromosi->execute([$tanggal]);
$countMutasi = $pdo->prepare(
    "SELECT COUNT(*) FROM riwayat_mutasi
     WHERE (CASE WHEN jenis_mutasi = 'Keluar' AND tanggal_keluar IS NOT NULL
                 THEN tanggal_keluar ELSE tanggal_sk END) <= ?"
);
$countMutasi->execute([$tanggal]);
$stats = [
    'aktif' => count($aktif),
    'hakim' => $jumlahKategori['Hakim'],
    'promosi' => (int) $countPromosi->fetchColumn(),
    'mutasi' => (int) $countMutasi->fetchColumn(),
];

$judul = 'Daftar pegawai';
include 'includes/header.php';
?>

<div class="stats dashboard-stats">
    <div>
        <b><?= $stats['aktif'] ?></b>
        <span>Pegawai aktif</span>
    </div>
    <div>
        <b><?= $stats['hakim'] ?></b>
        <span>Hakim aktif</span>
    </div>
    <div>
        <b><?= $stats['promosi'] ?></b>
        <span>Promosi tercatat</span>
    </div>
    <div>
        <b><?= $stats['mutasi'] ?></b>
        <span>Mutasi tercatat</span>
    </div>
    <?php foreach ($statsJabatan as $kategoriStat): ?>
        <div>
            <b><?= (int) $kategoriStat['jumlah'] ?></b>
            <span><?= e($kategoriStat['kategori']) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h2>Cari dan filter</h2>

    <form method="get" class="fl">
        <?php if ($sort !== ''): ?>
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="arah" value="<?= e($arah) ?>">
        <?php endif; ?>
        <label>
            Nama atau NIP
            <input name="q" value="<?= e($q) ?>" placeholder="Ketik nama / NIP">
        </label>

        <label>
            Kondisi per tanggal
            <input type="date" name="tanggal" value="<?= e($tanggal) ?>" required>
        </label>

        <label>
            Bulan masuk (TMT)
            <select name="bulan"><?= bln_opts($bln) ?></select>
        </label>

        <label>
            Pangkat/golongan
            <select name="golongan">
                <option value="">Semua golongan</option>
                <?php foreach ($pangkatList as $pangkat): ?>
                    <option value="<?= e($pangkat['golongan']) ?>" <?= $filter['golongan'] === $pangkat['golongan'] ? 'selected' : '' ?>>
                        <?= e($pangkat['golongan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Pendidikan
            <select name="pendidikan">
                <?= opts($pendidikanList, $filter['pendidikan'], 'Semua pendidikan') ?>
            </select>
        </label>

        <label>
            Jabatan
            <select name="jabatan">
                <option value="">Semua jabatan</option>
                <?php foreach ($jabatanList as $jabatan): ?>
                    <option value="<?= e($jabatan['nama_jabatan']) ?>" <?= $filter['jabatan'] === $jabatan['nama_jabatan'] ? 'selected' : '' ?>>
                        <?= e($jabatan['nama_jabatan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Kelompok
            <select name="kelompok">
                <?= opts($kelompokList, $filter['kelompok'], 'Semua') ?>
            </select>
        </label>

        <label>
            Jumlah data
            <select name="jumlah">
                <option value="25" <?= $jumlah === '25' ? 'selected' : '' ?>>25 data</option>
                <option value="50" <?= $jumlah === '50' ? 'selected' : '' ?>>50 data</option>
                <option value="100" <?= $jumlah === '100' ? 'selected' : '' ?>>100 data</option>
                <option value="semua" <?= $jumlah === 'semua' ? 'selected' : '' ?>>Semua data</option>
            </select>
        </label>

        <div>
            <br>
            <button class="btn">Terapkan</button>
            <a class="btn g" style="color:var(--mu)" href="index.php">Reset</a>
        </div>
    </form>

    <div class="row-actions" style="margin-top:16px">
        <a class="btn" href="<?= e($urlUnduhWord) ?>">Unduh Word</a>
        <a class="btn" href="<?= e($urlUnduhExcel) ?>">Unduh Excel</a>
    </div>

    <?php if ($modeHistoris): ?>
        <p class="em">
            Riwayat perubahan melalui edit tersedia mulai setelah migrasi riwayat edit dipasang.
            Perubahan sebelum migrasi tidak dapat dipulihkan otomatis.
        </p>
    <?php endif; ?>

    <div class="cnt"><?= count($rows) ?> dari <?= $total ?> data hasil pencarian ditampilkan</div>

    <div class="scroll">
        <table>
            <thead>
                <tr>
                    <?php foreach ([
                        'nomor' => 'No',
                        'nama' => 'Nama',
                        'nip' => 'NIP',
                        'jabatan' => 'Jabatan',
                        'golongan' => 'Pangkat/gol.',
                        'pendidikan' => 'Pendidikan',
                        'tmt' => 'TMT',
                        'status' => 'Status',
                    ] as $kolom => $judulKolom): ?>
                        <?php if (isset($sortColumns[$kolom])): ?>
                        <th <?= $sort === $kolom ? 'aria-sort="' . ($arah === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
                            <a
                                class="table-sort"
                                href="<?= e($sortUrl($kolom)) ?>"
                            >
                                <?= e($judulKolom) ?>
                                <?php if ($sort === $kolom): ?>
                                    <span aria-hidden="true"><?= $arah === 'asc' ? '▲' : '▼' ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <?php else: ?>
                            <th><?= e($judulKolom) ?></th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$modeHistoris): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="<?= $modeHistoris ? 8 : 9 ?>" class="em">
                            Tidak ada pegawai yang cocok. Ubah atau kosongkan filter.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= $sort === 'nomor' && $arah === 'desc' ? $total - $index : $index + 1 ?></td>
                        <td><div class="nm"><?= e($row['nama']) ?></div></td>
                        <td><?= e($row['nip']) ?></td>
                        <td><?= e($row['jabatan'] ?? '-') ?></td>
                        <td>
                            <?= e($row['nama_pangkat'] ?? '-') ?>
                            <?= !empty($row['golongan']) ? ' (' . e($row['golongan']) . ')' : '' ?>
                        </td>
                        <td><?= e($row['pendidikan'] ?? '-') ?></td>
                        <td><?= tgl_id($row['tmt']) ?></td>
                        <td>
                            <span class="tg<?= $row['status'] === 'Aktif' ? '' : ' m' ?>">
                                <?= e($row['status']) ?>
                            </span>
                        </td>
                        <?php if (!$modeHistoris): ?>
                            <td>
                                <div class="row-actions">
                                    <a class="btn edit" href="edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                                    <form
                                        method="post"
                                        data-confirm-title="Yakin ingin menghapus data pegawai?"
                                        data-confirm-message="Data pegawai beserta riwayatnya akan dihapus."
                                        data-confirm-button="Ya, Hapus"
                                    >
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hapus" value="<?= $row['id'] ?>">
                                        <button class="btn g" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
