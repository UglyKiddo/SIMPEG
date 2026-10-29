<?php
require_once 'includes/config.php';
require_login();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && csrf_ok()
    && isset($_POST['hapus'])
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
$filterColumns = [
    'golongan' => 'pg.golongan',
    'pendidikan' => 'p.pendidikan_terakhir',
    'jabatan' => 'j.nama_jabatan',
    'kelompok' => 'j.kategori',
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
$sortUrl = function ($kolom) use ($q, $bln, $filter, $jumlah, $sort, $arah) {
    $query = array_merge(
        ['q' => $q, 'bulan' => $bln],
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

$conditions = [];
$params = [];

if ($q !== '') {
    $conditions[] = '(p.nama_lengkap LIKE ? OR p.nip LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($bln !== '') {
    $conditions[] = 'MONTH(p.tmt) = ?';
    $params[] = (int) $bln;
}

foreach ($filter as $column => $value) {
    if ($value !== '') {
        $conditions[] = $filterColumns[$column] . ' = ?';
        $params[] = $value;
    }
}

$from = ' FROM pegawai p
    LEFT JOIN jabatan j ON j.id_jabatan = p.id_jabatan
    LEFT JOIN pangkat_golongan pg ON pg.id_pangkat = p.id_pangkat';
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$countStatement = $pdo->prepare('SELECT COUNT(*)' . $from . $where);
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();

$orderBy = $sort === ''
    ? 'p.id_jabatan ASC, p.nama_lengkap ASC'
    : $sortColumns[$sort] . ' ' . strtoupper($arah) . ', p.id_pegawai ASC';
$sql = 'SELECT
        p.id_pegawai AS id,
        p.nama_lengkap AS nama,
        p.nip,
        pg.golongan,
        pg.nama_pangkat,
        p.pendidikan_terakhir AS pendidikan,
        j.nama_jabatan AS jabatan,
        p.tmt,
        p.status_aktif AS status
    ' . $from . $where . ' ORDER BY ' . $orderBy
    . ($jumlah === 'semua' ? '' : ' LIMIT ' . (int) $jumlah);
$statement = $pdo->prepare($sql);
$statement->execute($params);
$rows = $statement->fetchAll();

$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM pegawai WHERE status_aktif = 'Aktif') AS aktif,
        (SELECT COUNT(*) FROM pegawai p JOIN jabatan j ON j.id_jabatan = p.id_jabatan
            WHERE p.status_aktif = 'Aktif' AND j.kategori = 'Hakim') AS hakim,
        (SELECT COUNT(*) FROM riwayat_promosi) AS promosi,
        (SELECT COUNT(*) FROM riwayat_mutasi) AS mutasi"
)->fetch();
$statsJabatan = $pdo->query(
    "SELECT CASE
                WHEN j.kategori = 'Hakim' THEN 'Hakim'
                WHEN j.kategori = 'PPNPN' THEN 'PPNPN'
                WHEN j.nama_jabatan LIKE 'Panitera%' THEN 'Panitera'
                WHEN j.nama_jabatan LIKE 'Juru Sita%' THEN 'Juru Sita'
                WHEN j.nama_jabatan = 'Sekretaris'
                  OR j.nama_jabatan LIKE '%Kepala Sub Bagian%' THEN 'Kesekretariatan'
                WHEN j.kategori = 'Pegawai' THEN 'Fungsional/Pelaksana'
                ELSE 'Lainnya'
            END AS kategori,
            COUNT(p.id_pegawai) AS jumlah
     FROM pegawai p
     LEFT JOIN jabatan j ON j.id_jabatan = p.id_jabatan
     WHERE p.status_aktif = 'Aktif'
     GROUP BY 1
     ORDER BY FIELD(kategori, 'Hakim', 'Panitera', 'Juru Sita', 'Kesekretariatan',
                    'Fungsional/Pelaksana', 'PPNPN', 'Lainnya')"
)->fetchAll();

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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="9" class="em">
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
