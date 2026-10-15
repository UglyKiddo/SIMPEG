<?php
require_once 'includes/config.php';
require_once 'includes/riwayat_helpers.php';
require_login();

$tipe = $_GET['tipe'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!in_array($tipe, ['promosi', 'mutasi'], true) || !$id || $id < 1) {
    http_response_code(400);
    exit('Riwayat tidak valid.');
}

$tabel = $tipe === 'promosi' ? 'riwayat_promosi' : 'riwayat_mutasi';
$kolomId = $tipe === 'promosi' ? 'id_promosi' : 'id_mutasi';
$statement = $pdo->prepare(
    "SELECT r.*, p.nama_lengkap, p.nip
     FROM $tabel r
     JOIN pegawai p ON p.id_pegawai = r.id_pegawai
     WHERE r.$kolomId = ?"
);
$statement->execute([$id]);
$riwayat = $statement->fetch();
if (!$riwayat) {
    http_response_code(404);
    exit('Riwayat tidak ditemukan.');
}

$jabatanList = daftar_jabatan();
$pangkatList = daftar_pangkat();
$jenisMutasiPilihan = ['Masuk', 'Keluar', 'Internal'];
$v = [
    'no_sk' => $riwayat['no_sk'] ?? '',
    'tanggal_sk' => $riwayat['tanggal_sk'] ?? '',
    'tujuan' => $riwayat['tujuan'] ?? '',
    'keterangan' => $riwayat['keterangan'] ?? '',
    'jenis_mutasi' => $riwayat['jenis_mutasi'] ?? '',
    'id_jabatan_baru' => (string) ($riwayat['id_jabatan_baru'] ?? ''),
    'id_pangkat_baru' => (string) ($riwayat['id_pangkat_baru'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $key => $_) {
        $v[$key] = trim($_POST[$key] ?? '');
    }

    $jabatanValid = false;
    foreach ($jabatanList as $jabatan) {
        if ((string) $jabatan['id_jabatan'] === $v['id_jabatan_baru']) {
            $jabatanValid = true;
            break;
        }
    }
    $pangkatValid = $v['id_pangkat_baru'] === '';
    foreach ($pangkatList as $pangkat) {
        if ((string) $pangkat['id_pangkat'] === $v['id_pangkat_baru']) {
            $pangkatValid = true;
            break;
        }
    }

    $inputValid = $v['no_sk'] !== ''
        && mb_strlen($v['no_sk']) <= 100
        && valid_date($v['tanggal_sk'])
        && $v['tujuan'] !== ''
        && mb_strlen($v['tujuan']) <= 255
        && mb_strlen($v['keterangan']) <= 5000
        && (
            $tipe === 'promosi'
                ? ($jabatanValid && $pangkatValid)
                : in_array($v['jenis_mutasi'], $jenisMutasiPilihan, true)
        );

    if (!csrf_ok()) {
        flash('Sesi tidak valid, muat ulang halaman.', true);
    } elseif (!$inputValid) {
        flash('Periksa kembali data riwayat yang diisi.', true);
    } else {
        try {
            $pdo->beginTransaction();
            $ambilRiwayat = $pdo->prepare(
                "SELECT * FROM $tabel WHERE $kolomId = ? FOR UPDATE"
            );
            $ambilRiwayat->execute([$id]);
            $lama = $ambilRiwayat->fetch();
            if (!$lama) {
                throw new RuntimeException('Riwayat tidak ditemukan.');
            }

            $terakhir = tidak_ada_perubahan_riwayat_setelah((int) $lama['id_pegawai'], $tipe, $id);
            if ($tipe === 'promosi') {
                $jabatanBaru = (int) $v['id_jabatan_baru'];
                $pangkatBaru = $v['id_pangkat_baru'] === ''
                    ? null
                    : (int) $v['id_pangkat_baru'];
                $tujuanBerubah = $jabatanBaru !== (int) $lama['id_jabatan_baru']
                    || (
                        $pangkatBaru === null
                            ? $lama['id_pangkat_baru'] !== null
                            : (int) $lama['id_pangkat_baru'] !== $pangkatBaru
                    );

                if ($tujuanBerubah && !$terakhir) {
                    throw new RuntimeException(
                        'Jabatan/pangkat hanya dapat diubah pada promosi terakhir pegawai.'
                    );
                }

                if ($tujuanBerubah) {
                    $ambilPegawai = $pdo->prepare(
                        'SELECT id_jabatan, id_pangkat FROM pegawai WHERE id_pegawai = ? FOR UPDATE'
                    );
                    $ambilPegawai->execute([(int) $lama['id_pegawai']]);
                    $pegawai = $ambilPegawai->fetch();
                    $pangkatSaatIniSesuai = $pegawai
                        && (
                            $pegawai['id_pangkat'] === null
                                ? $lama['id_pangkat_baru'] === null
                                : (int) $pegawai['id_pangkat'] === (int) $lama['id_pangkat_baru']
                        );
                    if (
                        !$pegawai
                        || (int) $pegawai['id_jabatan'] !== (int) $lama['id_jabatan_baru']
                        || !$pangkatSaatIniSesuai
                    ) {
                        throw new RuntimeException(
                            'Jabatan/pangkat pegawai sudah tidak sesuai dengan promosi ini; data pegawai tidak diubah.'
                        );
                    }
                    $pdo->prepare(
                        'UPDATE pegawai SET id_jabatan = ?, id_pangkat = ? WHERE id_pegawai = ?'
                    )->execute([$jabatanBaru, $pangkatBaru, (int) $lama['id_pegawai']]);
                }

                $update = $pdo->prepare(
                    'UPDATE riwayat_promosi
                     SET no_sk = ?, tanggal_sk = ?, id_jabatan_baru = ?, id_pangkat_baru = ?,
                         tujuan = ?, keterangan = ?
                     WHERE id_promosi = ?'
                );
                $update->execute([
                    $v['no_sk'],
                    $v['tanggal_sk'],
                    $jabatanBaru,
                    $pangkatBaru,
                    $v['tujuan'],
                    $v['keterangan'],
                    $id,
                ]);
            } else {
                $jenisBerubah = $v['jenis_mutasi'] !== $lama['jenis_mutasi'];
                if ($jenisBerubah && !$terakhir) {
                    throw new RuntimeException(
                        'Jenis mutasi hanya dapat diubah pada mutasi terakhir pegawai.'
                    );
                }

                if ($jenisBerubah) {
                    $ambilPegawai = $pdo->prepare(
                        'SELECT status_aktif FROM pegawai WHERE id_pegawai = ? FOR UPDATE'
                    );
                    $ambilPegawai->execute([(int) $lama['id_pegawai']]);
                    $pegawai = $ambilPegawai->fetch();
                    if (!$pegawai) {
                        throw new RuntimeException('Pegawai terkait tidak ditemukan.');
                    }

                    $statusSebelumnya = $pegawai['status_aktif'];
                    if ($lama['jenis_mutasi'] === 'Masuk') {
                        if ($statusSebelumnya !== 'Aktif') {
                            throw new RuntimeException('Status pegawai tidak sesuai dengan mutasi terakhir.');
                        }
                        $statusSebelumnya = 'Non-Aktif';
                    } elseif ($lama['jenis_mutasi'] === 'Keluar') {
                        if ($statusSebelumnya !== 'Non-Aktif') {
                            throw new RuntimeException('Status pegawai tidak sesuai dengan mutasi terakhir.');
                        }
                        $statusSebelumnya = 'Aktif';
                    }

                    if (
                        ($v['jenis_mutasi'] === 'Masuk' && $statusSebelumnya !== 'Non-Aktif')
                        || ($v['jenis_mutasi'] !== 'Masuk' && $statusSebelumnya !== 'Aktif')
                    ) {
                        throw new RuntimeException(
                            'Jenis mutasi baru tidak sesuai dengan status pegawai sebelum transaksi.'
                        );
                    }

                    $statusBaru = $v['jenis_mutasi'] === 'Masuk' ? 'Aktif' : (
                        $v['jenis_mutasi'] === 'Keluar' ? 'Non-Aktif' : $statusSebelumnya
                    );
                    if ($statusBaru !== $pegawai['status_aktif']) {
                        $pdo->prepare(
                            'UPDATE pegawai SET status_aktif = ? WHERE id_pegawai = ?'
                        )->execute([$statusBaru, (int) $lama['id_pegawai']]);
                    }
                }

                $update = $pdo->prepare(
                    'UPDATE riwayat_mutasi
                     SET no_sk = ?, tanggal_sk = ?, jenis_mutasi = ?, tujuan = ?, keterangan = ?
                     WHERE id_mutasi = ?'
                );
                $update->execute([
                    $v['no_sk'],
                    $v['tanggal_sk'],
                    $v['jenis_mutasi'],
                    $v['tujuan'],
                    $v['keterangan'],
                    $id,
                ]);
            }

            $pdo->commit();
            flash('Riwayat ' . $tipe . ' berhasil diperbarui.');
            header('Location: rekap.php?tipe=' . $tipe);
            exit;
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash($exception->getMessage(), true);
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Gagal memperbarui riwayat transaksi: ' . $exception->getMessage());
            flash('Gagal memperbarui riwayat. Periksa struktur database.', true);
        }
    }
}

$judul = 'Edit rekap ' . $tipe;
include 'includes/header.php';
?>

<div class="card">
    <h2>Edit riwayat <?= e($tipe) ?></h2>
    <p><strong><?= e($riwayat['nama_lengkap']) ?></strong> <span class="sub">NIP <?= e($riwayat['nip']) ?></span></p>

    <form method="post" class="fl two">
        <?= csrf_field() ?>

        <label>
            Nomor SK
            <input name="no_sk" required maxlength="100" value="<?= e($v['no_sk']) ?>">
        </label>

        <label>
            Tanggal SK
            <input type="date" name="tanggal_sk" required value="<?= e($v['tanggal_sk']) ?>">
        </label>

        <?php if ($tipe === 'promosi'): ?>
            <label>
                Jabatan baru
                <select name="id_jabatan_baru" required>
                    <option value="">Pilih jabatan</option>
                    <?php foreach ($jabatanList as $jabatan): ?>
                        <option
                            value="<?= (int) $jabatan['id_jabatan'] ?>"
                            <?= (string) $jabatan['id_jabatan'] === $v['id_jabatan_baru'] ? 'selected' : '' ?>
                        >
                            <?= e($jabatan['nama_jabatan']) ?> (<?= e($jabatan['kategori']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Pangkat/golongan baru
                <select name="id_pangkat_baru">
                    <option value="">Tidak ditentukan</option>
                    <?php foreach ($pangkatList as $pangkat): ?>
                        <option
                            value="<?= (int) $pangkat['id_pangkat'] ?>"
                            <?= (string) $pangkat['id_pangkat'] === $v['id_pangkat_baru'] ? 'selected' : '' ?>
                        >
                            <?= e($pangkat['golongan']) ?> — <?= e($pangkat['nama_pangkat']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <label>
                Jenis mutasi
                <select name="jenis_mutasi" required>
                    <?php foreach ($jenisMutasiPilihan as $jenis): ?>
                        <option value="<?= e($jenis) ?>" <?= $v['jenis_mutasi'] === $jenis ? 'selected' : '' ?>>
                            <?= e($jenis) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <label>
            Tujuan
            <input name="tujuan" required maxlength="255" value="<?= e($v['tujuan']) ?>">
        </label>

        <label class="full">
            Keterangan
            <textarea name="keterangan" maxlength="5000"><?= e($v['keterangan']) ?></textarea>
        </label>

        <div class="full row-actions">
            <button class="btn" type="submit">Simpan perubahan</button>
            <a class="btn g" href="rekap.php?tipe=<?= e($tipe) ?>">Batal</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
