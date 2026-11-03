<?php
require_once 'includes/config.php';
require_login();

$jabatanList = daftar_jabatan();
$pangkatList = daftar_pangkat();
$pendidikanList = array_values(array_diff(daftar_pendidikan(), ['Lainnya']));
$pilihanLainnya = '__pendidikan_lainnya__';
$pendidikanLainnya = '';
$tampilkanPendidikanLainnya = false;
$kategoriJabatanList = ['Hakim', 'Pegawai', 'PPNPN'];
$kategoriJabatan = '';
$tampilkanKategoriJabatan = false;
$v = [
    'nama' => '',
    'nip' => '',
    'jabatan' => '',
    'id_pangkat' => '',
    'pendidikan' => 'S1',
    'tmt' => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $key => $_) {
        $v[$key] = trim($_POST[$key] ?? '');
    }
    $pendidikanLainnya = trim($_POST['pendidikan_lainnya'] ?? '');
    $kategoriJabatan = trim($_POST['kategori_jabatan'] ?? '');
    $tampilkanPendidikanLainnya = $v['pendidikan'] === $pilihanLainnya;
    $pendidikanTersimpan = $tampilkanPendidikanLainnya
        ? $pendidikanLainnya
        : $v['pendidikan'];

    $jabatanDitemukan = null;
    foreach ($jabatanList as $jabatan) {
        if (mb_strtolower($jabatan['nama_jabatan']) === mb_strtolower($v['jabatan'])) {
            $jabatanDitemukan = $jabatan;
            break;
        }
    }
    $tampilkanKategoriJabatan = $jabatanDitemukan === null;
    $jabatanValid = $v['jabatan'] !== ''
        && mb_strlen($v['jabatan']) <= 150
        && (
            $jabatanDitemukan !== null
            || in_array($kategoriJabatan, $kategoriJabatanList, true)
        );

    $pangkatValid = false;
    foreach ($pangkatList as $pangkat) {
        if ((string) $pangkat['id_pangkat'] === $v['id_pangkat']) {
            $pangkatValid = true;
            break;
        }
    }

    if (!csrf_ok()) {
        flash('Sesi tidak valid, muat ulang halaman.', true);
    } elseif ($v['nama'] === '') {
        flash('Nama wajib diisi.', true);
    } elseif (!preg_match('/^\d{18}$/', $v['nip'])) {
        flash('NIP harus terdiri dari 18 angka.', true);
    } elseif (
        !$jabatanValid
        || !$pangkatValid
        || (
            !$tampilkanPendidikanLainnya
            && !in_array($v['pendidikan'], $pendidikanList, true)
        )
        || (
            $tampilkanPendidikanLainnya
            && ($pendidikanLainnya === '' || mb_strlen($pendidikanLainnya) > 100)
        )
    ) {
        flash('Jabatan, pangkat/golongan, atau pendidikan tidak valid.', true);
    } elseif (!valid_date($v['tmt'])) {
        flash('Tanggal TMT tidak valid.', true);
    } else {
        try {
            $pdo->beginTransaction();
            if ($jabatanDitemukan !== null) {
                $idJabatan = (int) $jabatanDitemukan['id_jabatan'];
            } else {
                $insertJabatan = $pdo->prepare(
                    'INSERT INTO jabatan (nama_jabatan, kategori) VALUES (?, ?)'
                );
                $insertJabatan->execute([$v['jabatan'], $kategoriJabatan]);
                $idJabatan = (int) $pdo->lastInsertId();
            }

            $statement = $pdo->prepare(
                'INSERT INTO pegawai
                    (nip, nama_lengkap, id_jabatan, id_pangkat, pendidikan_terakhir, status_aktif, tmt, dibuat_oleh)
                 VALUES (?, ?, ?, ?, ?, \'Aktif\', ?, ?)'
            );
            $statement->execute([
                $v['nip'],
                $v['nama'],
                $idJabatan,
                (int) $v['id_pangkat'],
                $pendidikanTersimpan,
                $v['tmt'],
                (int) $_SESSION['id_user'],
            ]);
            $pdo->commit();

            flash('Pegawai baru disimpan.');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash(
                $exception->getCode() === '23000'
                    ? 'NIP ' . $v['nip'] . ' sudah terdaftar.'
                    : 'Gagal menyimpan data pegawai.',
                true
            );
        }
    }
}

$judul = 'Tambah pegawai';
include 'includes/header.php';
?>

<div class="card">
    <h2>Input pegawai baru</h2>
    <form method="post" class="fl two">
        <?= csrf_field() ?>

        <label class="full">
            Nama lengkap dan gelar
            <input name="nama" required maxlength="150" value="<?= e($v['nama']) ?>">
        </label>

        <label>
            NIP
            <input
                name="nip"
                required
                inputmode="numeric"
                pattern="[0-9]{18}"
                title="NIP terdiri dari 18 angka"
                placeholder="18 angka"
                maxlength="18"
                value="<?= e($v['nip']) ?>"
            >
        </label>

        <label>
            Jabatan
            <input
                name="jabatan"
                required
                maxlength="150"
                list="daftar-jabatan"
                value="<?= e($v['jabatan']) ?>"
                placeholder="Pilih atau ketik jabatan"
            >
            <datalist id="daftar-jabatan">
                <?php foreach ($jabatanList as $jabatan): ?>
                    <option value="<?= e($jabatan['nama_jabatan']) ?>">
                <?php endforeach; ?>
            </datalist>
        </label>

        <label id="kategori-jabatan-field" <?= $tampilkanKategoriJabatan ? '' : 'hidden' ?>>
            Kategori jabatan baru
            <select
                name="kategori_jabatan"
                id="kategori-jabatan"
                <?= $tampilkanKategoriJabatan ? 'required' : 'disabled' ?>
            >
                <option value="">Pilih kategori</option>
                <?php foreach ($kategoriJabatanList as $kategori): ?>
                    <option value="<?= e($kategori) ?>" <?= $kategoriJabatan === $kategori ? 'selected' : '' ?>>
                        <?= e($kategori) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Pangkat/golongan
            <select name="id_pangkat" required>
                <option value="">Pilih pangkat/golongan</option>
                <?php foreach ($pangkatList as $pangkat): ?>
                    <option
                        value="<?= (int) $pangkat['id_pangkat'] ?>"
                        <?= (string) $pangkat['id_pangkat'] === $v['id_pangkat'] ? 'selected' : '' ?>
                    >
                        <?= e($pangkat['golongan']) ?> — <?= e($pangkat['nama_pangkat']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Pendidikan
            <select name="pendidikan" required>
                <?php foreach ($pendidikanList as $pendidikan): ?>
                    <option value="<?= e($pendidikan) ?>" <?= $v['pendidikan'] === $pendidikan ? 'selected' : '' ?>>
                        <?= e($pendidikan) ?>
                    </option>
                <?php endforeach; ?>
                <option value="<?= e($pilihanLainnya) ?>" <?= $tampilkanPendidikanLainnya ? 'selected' : '' ?>>
                    Lainnya...
                </option>
            </select>
        </label>

        <label
            id="pendidikan-lainnya-field"
            <?= $tampilkanPendidikanLainnya ? '' : 'hidden' ?>
        >
            Pendidikan lainnya
            <input
                id="pendidikan-lainnya"
                name="pendidikan_lainnya"
                maxlength="100"
                value="<?= e($pendidikanLainnya) ?>"
                <?= $tampilkanPendidikanLainnya ? 'required' : 'disabled' ?>
                placeholder="Masukkan pendidikan"
            >
        </label>

        <label>
            Tanggal mulai tugas (TMT)
            <input type="date" name="tmt" required value="<?= e($v['tmt']) ?>">
        </label>

        <div class="full">
            <button class="btn" type="submit">Simpan pegawai</button>
        </div>
    </form>
</div>

<script>
    const jabatanInput = document.querySelector('[name="jabatan"]');
    const daftarJabatan = <?= json_encode(
        array_column($jabatanList, 'nama_jabatan'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
    const kategoriJabatanField = document.getElementById('kategori-jabatan-field');
    const kategoriJabatanSelect = document.getElementById('kategori-jabatan');

    function perbaruiKategoriJabatan() {
        const namaJabatan = jabatanInput.value.trim().toLocaleLowerCase();
        const sudahTerdaftar = daftarJabatan.some(
            (nama) => nama.toLocaleLowerCase() === namaJabatan
        );
        const jabatanBaru = namaJabatan !== '' && !sudahTerdaftar;
        kategoriJabatanField.hidden = !jabatanBaru;
        kategoriJabatanSelect.disabled = !jabatanBaru;
        kategoriJabatanSelect.required = jabatanBaru;
    }

    const pendidikanSelect = document.querySelector('[name="pendidikan"]');
    const pendidikanLainnyaField = document.getElementById('pendidikan-lainnya-field');
    const pendidikanLainnyaInput = document.getElementById('pendidikan-lainnya');
    const pilihanPendidikanLainnya = <?= json_encode($pilihanLainnya) ?>;

    function perbaruiPendidikanLainnya() {
        const tampilkan = pendidikanSelect.value === pilihanPendidikanLainnya;
        pendidikanLainnyaField.hidden = !tampilkan;
        pendidikanLainnyaInput.disabled = !tampilkan;
        pendidikanLainnyaInput.required = tampilkan;
    }

    jabatanInput.addEventListener('input', perbaruiKategoriJabatan);
    perbaruiKategoriJabatan();
    pendidikanSelect.addEventListener('change', perbaruiPendidikanLainnya);
</script>

<?php include 'includes/footer.php'; ?>
