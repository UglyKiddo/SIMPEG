<?php
require_once 'includes/config.php';
require_login();

$idPegawai = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idPegawai || $idPegawai < 1) {
    http_response_code(400);
    exit('ID pegawai tidak valid.');
}

$statement = $pdo->prepare(
    'SELECT id_pegawai, nip, nama_lengkap, id_jabatan, id_pangkat,
            pendidikan_terakhir, status_aktif, tmt
     FROM pegawai
     WHERE id_pegawai = ?'
);
$statement->execute([$idPegawai]);
$pegawai = $statement->fetch();
if (!$pegawai) {
    http_response_code(404);
    exit('Data pegawai tidak ditemukan.');
}

$jabatanList = daftar_jabatan();
$pangkatList = daftar_pangkat();
$pendidikanList = daftar_pendidikan();
if (
    $pegawai['pendidikan_terakhir'] !== null
    && $pegawai['pendidikan_terakhir'] !== ''
    && !in_array($pegawai['pendidikan_terakhir'], $pendidikanList, true)
) {
    $pendidikanList[] = $pegawai['pendidikan_terakhir'];
}
$statusPilihan = ['Aktif', 'Non-Aktif'];
$v = [
    'nip' => $pegawai['nip'],
    'nama' => $pegawai['nama_lengkap'],
    'id_jabatan' => (string) $pegawai['id_jabatan'],
    'id_pangkat' => (string) ($pegawai['id_pangkat'] ?? ''),
    'pendidikan' => $pegawai['pendidikan_terakhir'] ?? '',
    'tmt' => $pegawai['tmt'] ?? '',
    'status' => $pegawai['status_aktif'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $key => $_) {
        $v[$key] = trim($_POST[$key] ?? '');
    }

    $jabatanValid = false;
    foreach ($jabatanList as $jabatan) {
        if ((string) $jabatan['id_jabatan'] === $v['id_jabatan']) {
            $jabatanValid = true;
            break;
        }
    }
    $pangkatValid = $v['id_pangkat'] === '';
    foreach ($pangkatList as $pangkat) {
        if ((string) $pangkat['id_pangkat'] === $v['id_pangkat']) {
            $pangkatValid = true;
            break;
        }
    }

    if (!csrf_ok()) {
        flash('Sesi tidak valid, muat ulang halaman.', true);
    } elseif ($v['nama'] === '' || mb_strlen($v['nama']) > 150) {
        flash('Nama wajib diisi dan maksimal 150 karakter.', true);
    } elseif ($v['nip'] === '' || mb_strlen($v['nip']) > 30) {
        flash('NIP wajib diisi dan maksimal 30 karakter.', true);
    } elseif (
        !$jabatanValid
        || !$pangkatValid
        || !in_array($v['pendidikan'], $pendidikanList, true)
        || !in_array($v['status'], $statusPilihan, true)
    ) {
        flash('Jabatan, pangkat/golongan, pendidikan, atau status tidak valid.', true);
    } elseif ($v['tmt'] !== '' && !valid_date($v['tmt'])) {
        flash('Tanggal TMT tidak valid.', true);
    } else {
        try {
            $update = $pdo->prepare(
                'UPDATE pegawai
                 SET nip = ?, nama_lengkap = ?, id_jabatan = ?, id_pangkat = ?,
                     pendidikan_terakhir = ?, tmt = ?, status_aktif = ?
                 WHERE id_pegawai = ?'
            );
            $update->execute([
                $v['nip'],
                $v['nama'],
                (int) $v['id_jabatan'],
                $v['id_pangkat'] === '' ? null : (int) $v['id_pangkat'],
                $v['pendidikan'],
                $v['tmt'] === '' ? null : $v['tmt'],
                $v['status'],
                $idPegawai,
            ]);

            flash('Data pegawai berhasil diperbarui.');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('NIP tersebut sudah digunakan pegawai lain.', true);
            } else {
                error_log('Gagal memperbarui data pegawai: ' . $exception->getMessage());
                flash('Gagal memperbarui data pegawai.', true);
            }
        }
    }
}

$judul = 'Edit pegawai';
include 'includes/header.php';
?>

<div class="card">
    <h2>Perbarui data pegawai</h2>
    <form method="post" class="fl two">
        <?= csrf_field() ?>

        <label class="full">
            Nama lengkap dan gelar
            <input name="nama" required maxlength="150" value="<?= e($v['nama']) ?>">
        </label>

        <label>
            NIP
            <input name="nip" required maxlength="30" value="<?= e($v['nip']) ?>">
        </label>

        <label>
            Jabatan
            <select name="id_jabatan" required>
                <option value="">Pilih jabatan</option>
                <?php foreach ($jabatanList as $jabatan): ?>
                    <option
                        value="<?= (int) $jabatan['id_jabatan'] ?>"
                        <?= (string) $jabatan['id_jabatan'] === $v['id_jabatan'] ? 'selected' : '' ?>
                    >
                        <?= e($jabatan['nama_jabatan']) ?> (<?= e($jabatan['kategori']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Pangkat/golongan
            <select name="id_pangkat">
                <option value="">Tidak ditentukan</option>
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
                <option value="">Pilih pendidikan</option>
                <?php foreach ($pendidikanList as $pendidikan): ?>
                    <option value="<?= e($pendidikan) ?>" <?= $v['pendidikan'] === $pendidikan ? 'selected' : '' ?>>
                        <?= e($pendidikan) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Tanggal mulai tugas (TMT)
            <input type="date" name="tmt" value="<?= e($v['tmt']) ?>">
        </label>

        <label>
            Status
            <select name="status" required>
                <?php foreach ($statusPilihan as $status): ?>
                    <option value="<?= e($status) ?>" <?= $v['status'] === $status ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="full row-actions">
            <button class="btn" type="submit">Simpan perubahan</button>
            <a class="btn g" href="index.php">Batal</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
