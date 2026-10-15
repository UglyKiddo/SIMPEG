<?php
require_once 'includes/config.php';
require_login();

$jabatanList = daftar_jabatan();
$pangkatList = daftar_pangkat();
$tipePilihan = ['promosi', 'mutasi'];
$jenisMutasiPilihan = ['Masuk', 'Keluar', 'Internal'];
$maxUkuranLampiran = 5 * 1024 * 1024;
$maxJumlahLampiran = 10;
$tipeLampiranDiizinkan = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe = $_POST['tipe'] ?? '';
    $jenisMutasi = $_POST['jenis_mutasi'] ?? 'Keluar';
    $pegawaiId = (int) ($_POST['pegawai_id'] ?? 0);
    $noSk = trim($_POST['no_sk'] ?? '');
    $tanggalSk = $_POST['tanggal_sk'] ?? '';
    $tujuan = trim($_POST['tujuan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jabatanBaru = (int) ($_POST['id_jabatan_baru'] ?? 0);
    $pangkatBaru = (int) ($_POST['id_pangkat_baru'] ?? 0);
    $lampiranValid = true;
    $pesanLampiran = '';
    $lampiranTervalidasi = [];
    $jumlahUkuranLampiran = 0;

    if (isset($_FILES['lampiran'])) {
        $fileLampiran = $_FILES['lampiran'];
        if (!is_array($fileLampiran['name']) || count($fileLampiran['name']) > $maxJumlahLampiran) {
            $lampiranValid = false;
            $pesanLampiran = 'Unggah maksimal 10 file sekaligus.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            foreach ($fileLampiran['name'] as $index => $namaAsli) {
                $errorUpload = $fileLampiran['error'][$index];
                if ($errorUpload === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($errorUpload !== UPLOAD_ERR_OK) {
                    $lampiranValid = false;
                    $pesanLampiran = $errorUpload === UPLOAD_ERR_INI_SIZE || $errorUpload === UPLOAD_ERR_FORM_SIZE
                        ? 'Ukuran file melebihi batas upload server.'
                        : 'Salah satu lampiran gagal diunggah. Coba pilih ulang file.';
                    break;
                }

                $pathSementara = $fileLampiran['tmp_name'][$index];
                $ukuran = (int) $fileLampiran['size'][$index];
                if (
                    !is_uploaded_file($pathSementara)
                    || $ukuran < 1
                    || $ukuran > $maxUkuranLampiran
                ) {
                    $lampiranValid = false;
                    $pesanLampiran = 'Setiap file harus berukuran maksimal 5 MB.';
                    break;
                }

                $mime = $finfo->file($pathSementara);
                if (!isset($tipeLampiranDiizinkan[$mime])) {
                    $lampiranValid = false;
                    $pesanLampiran = 'Format lampiran harus JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, atau XLSX.';
                    break;
                }

                $jumlahUkuranLampiran += $ukuran;
                if ($jumlahUkuranLampiran > $maxJumlahLampiran * $maxUkuranLampiran) {
                    $lampiranValid = false;
                    $pesanLampiran = 'Total ukuran lampiran maksimal 50 MB per transaksi.';
                    break;
                }

                $namaAsli = basename(str_replace('\\', '/', (string) $namaAsli));
                $namaAsli = preg_replace('/[\x00-\x1F\x7F]/', '', $namaAsli);
                $namaAsli = mb_scrub($namaAsli, 'UTF-8');
                $lampiranTervalidasi[] = [
                    'tmp_name' => $pathSementara,
                    'nama_asli' => mb_strcut($namaAsli, 0, 255, 'UTF-8'),
                    'mime' => $mime,
                    'extension' => $tipeLampiranDiizinkan[$mime],
                    'ukuran' => $ukuran,
                ];
            }
        }
    }

    $statement = $pdo->prepare(
        'SELECT p.id_pegawai, p.nip, p.nama_lengkap, p.id_jabatan, p.id_pangkat,
                p.status_aktif, j.nama_jabatan, pg.golongan
         FROM pegawai p
         JOIN jabatan j ON j.id_jabatan = p.id_jabatan
         LEFT JOIN pangkat_golongan pg ON pg.id_pangkat = p.id_pangkat
         WHERE p.id_pegawai = ?'
    );
    $statement->execute([$pegawaiId]);
    $pegawai = $statement->fetch();

    $jabatanValid = false;
    foreach ($jabatanList as $jabatan) {
        if ((int) $jabatan['id_jabatan'] === $jabatanBaru) {
            $jabatanValid = true;
            break;
        }
    }

    $pangkatValid = false;
    foreach ($pangkatList as $pangkat) {
        if ((int) $pangkat['id_pangkat'] === $pangkatBaru) {
            $pangkatValid = true;
            break;
        }
    }

    $validPegawaiStatus = $pegawai
        && (
            $tipe === 'promosi'
                ? $pegawai['status_aktif'] === 'Aktif'
                : (
                    ($jenisMutasi === 'Masuk' && $pegawai['status_aktif'] === 'Non-Aktif')
                    || ($jenisMutasi !== 'Masuk' && $pegawai['status_aktif'] === 'Aktif')
                )
        );

    if (!csrf_ok()) {
        flash('Sesi tidak valid, muat ulang halaman.', true);
    } elseif (!in_array($tipe, $tipePilihan, true) || !$validPegawaiStatus) {
        flash('Pilih jenis transaksi dan pegawai dengan status yang sesuai.', true);
    } elseif (
        $noSk === ''
        || $tujuan === ''
        || mb_strlen($keterangan) > 5000
        || !valid_date($tanggalSk)
    ) {
        flash('Nomor SK, tanggal SK, tujuan, atau keterangan tidak valid.', true);
    } elseif (
        $tipe === 'promosi'
        && (!$jabatanValid || !$pangkatValid)
    ) {
        flash('Jabatan atau pangkat/golongan baru tidak valid.', true);
    } elseif (
        $tipe === 'promosi'
        && $jabatanBaru === (int) $pegawai['id_jabatan']
        && $pangkatBaru === (int) $pegawai['id_pangkat']
    ) {
        flash('Jabatan atau pangkat/golongan promosi harus berubah.', true);
    } elseif (
        $tipe === 'mutasi'
        && !in_array($jenisMutasi, $jenisMutasiPilihan, true)
    ) {
        flash('Jenis mutasi tidak valid.', true);
    } elseif (!$lampiranValid) {
        flash($pesanLampiran, true);
    } else {
        $fileTersimpan = [];
        try {
            $pdo->beginTransaction();

            if ($tipe === 'promosi') {
                $insert = $pdo->prepare(
                    'INSERT INTO riwayat_promosi
                        (id_pegawai, no_sk, tanggal_sk, id_jabatan_lama, id_pangkat_lama,
                         id_jabatan_baru, id_pangkat_baru, tujuan, keterangan)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $pegawai['id_pegawai'],
                    $noSk,
                    $tanggalSk,
                    $pegawai['id_jabatan'],
                    $pegawai['id_pangkat'],
                    $jabatanBaru,
                    $pangkatBaru,
                    $tujuan,
                    $keterangan !== '' ? $keterangan : null,
                ]);
                $idTransaksi = (int) $pdo->lastInsertId();

                $update = $pdo->prepare(
                    'UPDATE pegawai SET id_jabatan = ?, id_pangkat = ? WHERE id_pegawai = ?'
                );
                $update->execute([$jabatanBaru, $pangkatBaru, $pegawai['id_pegawai']]);
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO riwayat_mutasi
                        (id_pegawai, no_sk, tanggal_sk, jenis_mutasi,
                         id_jabatan_saat_mutasi, id_pangkat_saat_mutasi, tujuan, keterangan)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $pegawai['id_pegawai'],
                    $noSk,
                    $tanggalSk,
                    $jenisMutasi,
                    $pegawai['id_jabatan'],
                    $pegawai['id_pangkat'],
                    $tujuan,
                    $keterangan !== '' ? $keterangan : null,
                ]);
                $idTransaksi = (int) $pdo->lastInsertId();

                if ($jenisMutasi === 'Keluar') {
                    $pdo->prepare(
                        "UPDATE pegawai SET status_aktif = 'Non-Aktif' WHERE id_pegawai = ?"
                    )->execute([$pegawai['id_pegawai']]);
                } elseif ($jenisMutasi === 'Masuk') {
                    $pdo->prepare(
                        "UPDATE pegawai SET status_aktif = 'Aktif' WHERE id_pegawai = ?"
                    )->execute([$pegawai['id_pegawai']]);
                }
            }

            if ($lampiranTervalidasi) {
                $direktoriLampiran = dirname(__DIR__, 2)
                    . DIRECTORY_SEPARATOR . 'simpeg-pn-yogya-storage'
                    . DIRECTORY_SEPARATOR . 'lampiran-transaksi';
                if (
                    !is_dir($direktoriLampiran)
                    && !mkdir($direktoriLampiran, 0750, true)
                    && !is_dir($direktoriLampiran)
                ) {
                    throw new RuntimeException('Folder penyimpanan lampiran tidak dapat dibuat.');
                }

                $simpanLampiran = $pdo->prepare(
                    'INSERT INTO lampiran_transaksi
                        (jenis_transaksi, tipe_transaksi, id_transaksi, id_pegawai, nama_asli,
                         nama_file, file_path, tipe_file, mime_type, ukuran_file, ukuran)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $fileSkUtama = null;

                foreach ($lampiranTervalidasi as $lampiran) {
                    $namaFile = bin2hex(random_bytes(16)) . '.' . $lampiran['extension'];
                    $pathTujuan = $direktoriLampiran . '/' . $namaFile;
                    $pathRelatif = 'private/lampiran-transaksi/' . $namaFile;
                    if (!move_uploaded_file($lampiran['tmp_name'], $pathTujuan)) {
                        throw new RuntimeException('Gagal menyimpan salah satu lampiran. Silakan coba lagi.');
                    }

                    $fileTersimpan[] = $pathTujuan;
                    $fileSkUtama = $fileSkUtama ?? $pathRelatif;
                    $simpanLampiran->execute([
                        $tipe,
                        $tipe,
                        $idTransaksi,
                        $pegawai['id_pegawai'],
                        $lampiran['nama_asli'],
                        $namaFile,
                        $pathRelatif,
                        $lampiran['extension'],
                        $lampiran['mime'],
                        $lampiran['ukuran'],
                        $lampiran['ukuran'],
                    ]);
                }

                if ($fileSkUtama !== null) {
                    $tabelTransaksi = $tipe === 'promosi' ? 'riwayat_promosi' : 'riwayat_mutasi';
                    $kolomIdTransaksi = $tipe === 'promosi' ? 'id_promosi' : 'id_mutasi';
                    $pdo->prepare(
                        "UPDATE $tabelTransaksi SET file_sk = ? WHERE $kolomIdTransaksi = ?"
                    )->execute([$fileSkUtama, $idTransaksi]);
                }
            }

            $pdo->commit();
            flash($tipe === 'promosi' ? 'Promosi disimpan.' : 'Mutasi disimpan.');
            header('Location: rekap.php?tipe=' . $tipe);
            exit;
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($fileTersimpan as $pathTersimpan) {
                if (is_file($pathTersimpan)) {
                    unlink($pathTersimpan);
                }
            }
            flash($exception->getMessage(), true);
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($fileTersimpan as $pathTersimpan) {
                if (is_file($pathTersimpan)) {
                    unlink($pathTersimpan);
                }
            }
            error_log('Gagal menyimpan transaksi/lampiran: ' . $exception->getMessage());
            flash('Gagal menyimpan transaksi atau lampiran. Pastikan migrasi database_lampiran.sql sudah dijalankan.', true);
        }
    }
}

$pegawaiList = $pdo->query(
    'SELECT p.id_pegawai, p.nip, p.nama_lengkap, p.id_jabatan, p.id_pangkat, p.status_aktif,
            j.nama_jabatan, pg.golongan
     FROM pegawai p
     JOIN jabatan j ON j.id_jabatan = p.id_jabatan
     LEFT JOIN pangkat_golongan pg ON pg.id_pangkat = p.id_pangkat
     ORDER BY p.nama_lengkap'
)->fetchAll();

$judul = 'Mutasi & promosi';
include 'includes/header.php';
?>

<div class="card">
    <h2>Catat mutasi atau promosi</h2>
    <form method="post" class="fl two" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label>
            Jenis transaksi
            <select name="tipe" id="tipe">
                <option value="promosi" <?= ($_POST['tipe'] ?? '') === 'promosi' ? 'selected' : '' ?>>Promosi</option>
                <option value="mutasi" <?= ($_POST['tipe'] ?? '') === 'mutasi' ? 'selected' : '' ?>>Mutasi</option>
            </select>
        </label>

        <label class="mutation-field" hidden>
            Jenis mutasi
            <select name="jenis_mutasi" id="jenis-mutasi">
                <?php foreach ($jenisMutasiPilihan as $jenis): ?>
                    <option value="<?= e($jenis) ?>" <?= ($_POST['jenis_mutasi'] ?? 'Keluar') === $jenis ? 'selected' : '' ?>>
                        <?= e($jenis) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Pegawai
            <select name="pegawai_id" id="pegawai" required>
                <option value="">Pilih pegawai</option>
                <?php foreach ($pegawaiList as $item): ?>
                    <option
                        value="<?= (int) $item['id_pegawai'] ?>"
                        data-status="<?= e($item['status_aktif']) ?>"
                        data-jabatan="<?= (int) $item['id_jabatan'] ?>"
                        data-pangkat="<?= (int) $item['id_pangkat'] ?>"
                        <?= (string) ($item['id_pegawai']) === ($_POST['pegawai_id'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($item['nama_lengkap']) ?> · <?= e($item['nip']) ?> (<?= e($item['status_aktif']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Nomor SK
            <input name="no_sk" required placeholder="mis. 123/KMA/SK/I/2026" value="<?= e($_POST['no_sk'] ?? '') ?>">
        </label>

        <label>
            Tanggal SK
            <input type="date" name="tanggal_sk" id="tanggal-sk" required value="<?= e($_POST['tanggal_sk'] ?? '') ?>">
            <span class="hint" id="hari"></span>
        </label>

        <label class="promotion-field">
            Pangkat/golongan baru
            <select name="id_pangkat_baru" id="pangkat-baru">
                <?php foreach ($pangkatList as $pangkat): ?>
                    <option
                        value="<?= (int) $pangkat['id_pangkat'] ?>"
                        <?= (string) $pangkat['id_pangkat'] === ($_POST['id_pangkat_baru'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($pangkat['golongan']) ?> — <?= e($pangkat['nama_pangkat']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="promotion-field">
            Jabatan baru
            <select name="id_jabatan_baru" id="jabatan-baru">
                <?php foreach ($jabatanList as $jabatan): ?>
                    <option
                        value="<?= (int) $jabatan['id_jabatan'] ?>"
                        <?= (string) $jabatan['id_jabatan'] === ($_POST['id_jabatan_baru'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($jabatan['nama_jabatan']) ?> (<?= e($jabatan['kategori']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="full">
            <span id="tujuan-label">Penempatan setelah promosi</span>
            <input name="tujuan" required value="<?= e($_POST['tujuan'] ?? '') ?>">
        </label>

        <label class="full">
            Keterangan
            <textarea name="keterangan" rows="3" maxlength="5000"><?= e($_POST['keterangan'] ?? '') ?></textarea>
        </label>

        <label class="full">
            Foto atau dokumen pendukung
            <input
                type="file"
                name="lampiran[]"
                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                multiple
            >
            <span class="hint">Maksimal 10 file, masing-masing 5 MB. Format: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX.</span>
        </label>

        <div class="full">
            <button class="btn" type="submit">Simpan</button>
            <p class="hint" id="transaksi-keterangan">
                Promosi memperbarui pangkat dan jabatan. Mutasi keluar mengubah status pegawai menjadi Non-Aktif.
            </p>
        </div>
    </form>
</div>

<script>
    const tipeSelect = document.getElementById('tipe');
    const jenisMutasi = document.getElementById('jenis-mutasi');
    const pegawaiSelect = document.getElementById('pegawai');
    const pangkatSelect = document.getElementById('pangkat-baru');
    const jabatanSelect = document.getElementById('jabatan-baru');
    const tanggalSk = document.getElementById('tanggal-sk');
    const hari = document.getElementById('hari');
    const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    function sinkronkanForm() {
        const promosi = tipeSelect.value === 'promosi';
        document.querySelectorAll('.promotion-field').forEach((field) => {
            field.hidden = !promosi;
            field.querySelector('select').disabled = !promosi;
        });
        document.querySelectorAll('.mutation-field').forEach((field) => {
            field.hidden = promosi;
            field.querySelector('select').disabled = promosi;
        });

        document.getElementById('tujuan-label').textContent = promosi
            ? 'Penempatan setelah promosi'
            : 'Tujuan mutasi';
        document.getElementById('transaksi-keterangan').textContent = promosi
            ? 'Promosi memperbarui pangkat dan jabatan pegawai.'
            : 'Mutasi Keluar menonaktifkan pegawai; mutasi Masuk mengaktifkannya kembali.';

        const option = pegawaiSelect.selectedOptions[0];
        if (option && option.value) {
            pangkatSelect.value = option.dataset.pangkat;
            jabatanSelect.value = option.dataset.jabatan;
        }
    }

    tipeSelect.addEventListener('change', sinkronkanForm);
    pegawaiSelect.addEventListener('change', sinkronkanForm);
    tanggalSk.addEventListener('change', () => {
        hari.textContent = tanggalSk.value
            ? namaHari[new Date(`${tanggalSk.value}T00:00:00`).getDay()]
            : '';
    });
    sinkronkanForm();
    tanggalSk.dispatchEvent(new Event('change'));
</script>

<?php include 'includes/footer.php'; ?>
