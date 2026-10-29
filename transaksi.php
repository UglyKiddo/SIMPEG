<?php
require_once 'includes/config.php';
require_login();

$jabatanList = daftar_jabatan();
$pangkatList = daftar_pangkat();
$tipePilihan = ['promosi', 'mutasi'];
$jenisPromosiPilihan = ['Naik Jabatan', 'Keluar'];
$jenisMutasiPilihan = ['Masuk'];
$pilihanJabatanLainnya = '__jabatan_lainnya__';
$pilihanPendidikanLainnya = '__pendidikan_lainnya__';
$pendidikanList = array_values(array_diff(daftar_pendidikan(), ['Lainnya']));
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
    $jenisPromosi = $_POST['jenis_promosi'] ?? 'Naik Jabatan';
    $jenisMutasi = $_POST['jenis_mutasi'] ?? 'Masuk';
    $pegawaiIdInput = trim($_POST['pegawai_id'] ?? '');
    $pegawaiBaru = $tipe === 'mutasi'
        && $jenisMutasi === 'Masuk'
        && $pegawaiIdInput === '__pegawai_baru__';
    $pegawaiId = $pegawaiBaru ? 0 : (int) $pegawaiIdInput;
    $namaPegawaiBaru = trim($_POST['nama_pegawai_baru'] ?? '');
    $nipPegawaiBaru = trim($_POST['nip_pegawai_baru'] ?? '');
    $idJabatanPegawaiBaruInput = trim($_POST['id_jabatan_pegawai_baru'] ?? '');
    $idPangkatPegawaiBaruInput = trim($_POST['id_pangkat_pegawai_baru'] ?? '');
    $pendidikanPegawaiBaru = trim($_POST['pendidikan_pegawai_baru'] ?? '');
    $pendidikanLainnyaPegawaiBaru = trim($_POST['pendidikan_lainnya_pegawai_baru'] ?? '');
    $tmtPegawaiBaru = trim($_POST['tmt_pegawai_baru'] ?? '');
    $noSk = trim($_POST['no_sk'] ?? '');
    $tanggalSk = $_POST['tanggal_sk'] ?? '';
    $tanggalKeluar = trim($_POST['tanggal_keluar'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jabatanBaruInput = trim($_POST['id_jabatan_baru'] ?? '');
    $gunakanJabatanLainnya = $jabatanBaruInput === $pilihanJabatanLainnya;
    $jabatanLainnya = trim($_POST['jabatan_lainnya'] ?? '');
    $jabatanBaru = $gunakanJabatanLainnya ? 0 : (int) $jabatanBaruInput;
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

    $pegawai = null;
    if ($pegawaiId > 0) {
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
    }

    $jabatanValid = $gunakanJabatanLainnya
        ? $jabatanLainnya !== '' && mb_strlen($jabatanLainnya) <= 100
        : false;
    if (!$gunakanJabatanLainnya) {
        foreach ($jabatanList as $jabatan) {
            if ((string) $jabatan['id_jabatan'] === $jabatanBaruInput) {
                $jabatanValid = true;
                break;
            }
        }
    }

    $pangkatValid = false;
    foreach ($pangkatList as $pangkat) {
        if ((int) $pangkat['id_pangkat'] === $pangkatBaru) {
            $pangkatValid = true;
            break;
        }
    }

    $jabatanPegawaiBaruValid = false;
    foreach ($jabatanList as $jabatan) {
        if ((string) $jabatan['id_jabatan'] === $idJabatanPegawaiBaruInput) {
            $jabatanPegawaiBaruValid = true;
            break;
        }
    }

    $pangkatPegawaiBaruValid = false;
    foreach ($pangkatList as $pangkat) {
        if ((string) $pangkat['id_pangkat'] === $idPangkatPegawaiBaruInput) {
            $pangkatPegawaiBaruValid = true;
            break;
        }
    }

    $pendidikanPegawaiBaruValid = $pendidikanPegawaiBaru === $pilihanPendidikanLainnya
        ? $pendidikanLainnyaPegawaiBaru !== '' && mb_strlen($pendidikanLainnyaPegawaiBaru) <= 100
        : in_array($pendidikanPegawaiBaru, $pendidikanList, true);
    $profilPegawaiBaruValid = $namaPegawaiBaru !== ''
        && mb_strlen($namaPegawaiBaru) <= 150
        && preg_match('/^\d{18}$/', $nipPegawaiBaru)
        && $jabatanPegawaiBaruValid
        && $pangkatPegawaiBaruValid
        && $pendidikanPegawaiBaruValid
        && valid_date($tmtPegawaiBaru);

    $validPegawaiStatus = $pegawaiBaru
        ? $profilPegawaiBaruValid
        : ($pegawai && (
            $tipe === 'promosi'
                ? $pegawai['status_aktif'] === 'Aktif'
                : $jenisMutasi === 'Masuk' && $pegawai['status_aktif'] === 'Non-Aktif'
        ));

    if (!csrf_ok()) {
        flash('Sesi tidak valid, muat ulang halaman.', true);
    } elseif ($pegawaiBaru && !$profilPegawaiBaruValid) {
        flash('Lengkapi data pegawai baru dengan benar sebelum mencatat mutasi masuk.', true);
    } elseif (!in_array($tipe, $tipePilihan, true) || !$validPegawaiStatus) {
        flash('Pilih jenis transaksi dan pegawai dengan status yang sesuai.', true);
    } elseif (
        $noSk === ''
        || $tujuan === ''
        || mb_strlen($keterangan) > 5000
        || !valid_date($tanggalSk)
        || (
            $tipe === 'promosi'
            && $jenisPromosi === 'Keluar'
            && !valid_date($tanggalKeluar)
        )
    ) {
        flash('Nomor SK, tanggal SK, tanggal keluar, tujuan, atau keterangan tidak valid.', true);
    } elseif (
        $tipe === 'promosi'
        && !in_array($jenisPromosi, $jenisPromosiPilihan, true)
    ) {
        flash('Jenis promosi tidak valid.', true);
    } elseif (
        $tipe === 'promosi'
        && $jenisPromosi === 'Naik Jabatan'
        && (!$jabatanValid || !$pangkatValid)
    ) {
        flash('Jabatan atau pangkat/golongan baru tidak valid.', true);
    } elseif (
        $tipe === 'promosi'
        && $jenisPromosi === 'Naik Jabatan'
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

            if (!$pegawaiBaru) {
                $kunciPegawai = $pdo->prepare(
                    'SELECT p.id_pegawai, p.nip, p.nama_lengkap, p.id_jabatan, p.id_pangkat,
                            p.status_aktif, j.nama_jabatan, pg.golongan
                     FROM pegawai p
                     JOIN jabatan j ON j.id_jabatan = p.id_jabatan
                     LEFT JOIN pangkat_golongan pg ON pg.id_pangkat = p.id_pangkat
                     WHERE p.id_pegawai = ?
                     FOR UPDATE'
                );
                $kunciPegawai->execute([$pegawaiId]);
                $pegawai = $kunciPegawai->fetch();
                $statusSesuai = $tipe === 'promosi'
                    ? ($pegawai && $pegawai['status_aktif'] === 'Aktif')
                    : ($pegawai
                        && $jenisMutasi === 'Masuk'
                        && $pegawai['status_aktif'] === 'Non-Aktif');
                if (!$statusSesuai) {
                    throw new RuntimeException(
                        'Status pegawai berubah atau tidak sesuai. Muat ulang halaman dan coba lagi.'
                    );
                }
            }

            if ($pegawaiBaru) {
                $pendidikanTersimpan = $pendidikanPegawaiBaru === $pilihanPendidikanLainnya
                    ? $pendidikanLainnyaPegawaiBaru
                    : $pendidikanPegawaiBaru;
                $pdo->prepare(
                    'INSERT INTO pegawai
                        (nip, nama_lengkap, id_jabatan, id_pangkat, pendidikan_terakhir,
                         status_aktif, tmt, dibuat_oleh)
                     VALUES (?, ?, ?, ?, ?, \'Aktif\', ?, ?)'
                )->execute([
                    $nipPegawaiBaru,
                    $namaPegawaiBaru,
                    (int) $idJabatanPegawaiBaruInput,
                    (int) $idPangkatPegawaiBaruInput,
                    $pendidikanTersimpan,
                    $tmtPegawaiBaru,
                    (int) $_SESSION['id_user'],
                ]);
                $pegawaiId = (int) $pdo->lastInsertId();
                $pegawai = [
                    'id_pegawai' => $pegawaiId,
                    'nip' => $nipPegawaiBaru,
                    'nama_lengkap' => $namaPegawaiBaru,
                    'id_jabatan' => (int) $idJabatanPegawaiBaruInput,
                    'id_pangkat' => (int) $idPangkatPegawaiBaruInput,
                    'status_aktif' => 'Aktif',
                ];
            }

            if ($tipe === 'promosi') {
                if ($jenisPromosi === 'Naik Jabatan' && $gunakanJabatanLainnya) {
                    $cariJabatan = $pdo->prepare(
                        'SELECT id_jabatan
                         FROM jabatan
                         WHERE nama_jabatan = ? AND kategori = ?
                         LIMIT 1'
                    );
                    $cariJabatan->execute([$jabatanLainnya, 'Pegawai']);
                    $idJabatanTersimpan = $cariJabatan->fetchColumn();
                    if ($idJabatanTersimpan === false) {
                        $buatJabatan = $pdo->prepare(
                            'INSERT INTO jabatan (nama_jabatan, kategori) VALUES (?, ?)'
                        );
                        $buatJabatan->execute([$jabatanLainnya, 'Pegawai']);
                        $jabatanBaru = (int) $pdo->lastInsertId();
                    } else {
                        $jabatanBaru = (int) $idJabatanTersimpan;
                    }
                }

                if (
                    $jenisPromosi === 'Naik Jabatan'
                    && $jabatanBaru === (int) $pegawai['id_jabatan']
                    && $pangkatBaru === (int) $pegawai['id_pangkat']
                ) {
                    throw new RuntimeException('Jabatan atau pangkat/golongan promosi harus berubah.');
                }

                $insert = $pdo->prepare(
                    'INSERT INTO riwayat_promosi
                        (id_pegawai, nama_pegawai, nip_pegawai, jenis_promosi, no_sk, tanggal_sk,
                         tanggal_keluar, id_jabatan_lama, id_pangkat_lama, id_jabatan_baru,
                         id_pangkat_baru, tujuan, keterangan)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $jabatanBaru = $jenisPromosi === 'Keluar'
                    ? (int) $pegawai['id_jabatan']
                    : $jabatanBaru;
                $pangkatBaru = $jenisPromosi === 'Keluar'
                    ? $pegawai['id_pangkat']
                    : $pangkatBaru;
                $insert->execute([
                    $pegawai['id_pegawai'],
                    $pegawai['nama_lengkap'],
                    $pegawai['nip'],
                    $jenisPromosi,
                    $noSk,
                    $tanggalSk,
                    $jenisPromosi === 'Keluar' ? $tanggalKeluar : null,
                    $pegawai['id_jabatan'],
                    $pegawai['id_pangkat'],
                    $jabatanBaru,
                    $pangkatBaru,
                    $tujuan,
                    $keterangan !== '' ? $keterangan : null,
                ]);
                $idTransaksi = (int) $pdo->lastInsertId();

                if ($jenisPromosi === 'Keluar') {
                    $pdo->prepare(
                        'UPDATE riwayat_promosi
                         SET nama_pegawai = ?, nip_pegawai = ?
                         WHERE id_pegawai = ?'
                    )->execute([
                        $pegawai['nama_lengkap'],
                        $pegawai['nip'],
                        $pegawai['id_pegawai'],
                    ]);
                    $pdo->prepare(
                        'UPDATE riwayat_mutasi
                         SET nama_pegawai = ?, nip_pegawai = ?
                         WHERE id_pegawai = ?'
                    )->execute([
                        $pegawai['nama_lengkap'],
                        $pegawai['nip'],
                        $pegawai['id_pegawai'],
                    ]);
                    $hapusPegawai = $pdo->prepare('DELETE FROM pegawai WHERE id_pegawai = ?');
                    $hapusPegawai->execute([$pegawai['id_pegawai']]);
                    if ($hapusPegawai->rowCount() !== 1) {
                        throw new RuntimeException('Data pegawai tidak berhasil dihapus.');
                    }
                } else {
                    $update = $pdo->prepare(
                        'UPDATE pegawai SET id_jabatan = ?, id_pangkat = ? WHERE id_pegawai = ?'
                    );
                    $update->execute([$jabatanBaru, $pangkatBaru, $pegawai['id_pegawai']]);
                }
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO riwayat_mutasi
                        (id_pegawai, nama_pegawai, nip_pegawai, no_sk, tanggal_sk, tanggal_keluar, jenis_mutasi,
                         id_jabatan_saat_mutasi, id_pangkat_saat_mutasi, tujuan, keterangan)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $pegawai['id_pegawai'],
                    $pegawai['nama_lengkap'],
                    $pegawai['nip'],
                    $noSk,
                    $tanggalSk,
                    null,
                    $jenisMutasi,
                    $pegawai['id_jabatan'],
                    $pegawai['id_pangkat'],
                    $tujuan,
                    $keterangan !== '' ? $keterangan : null,
                ]);
                $idTransaksi = (int) $pdo->lastInsertId();

                $pdo->prepare(
                    "UPDATE pegawai SET status_aktif = 'Aktif' WHERE id_pegawai = ?"
                )->execute([$pegawai['id_pegawai']]);
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
                        $tipe === 'promosi' && $jenisPromosi === 'Keluar'
                            ? null
                            : $pegawai['id_pegawai'],
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
            flash(
                $tipe === 'promosi'
                    ? ($jenisPromosi === 'Keluar'
                        ? 'Pegawai keluar; data pegawai dihapus dan riwayat serta lampiran tetap disimpan.'
                        : 'Promosi disimpan.')
                    : ($pegawaiBaru
                        ? 'Mutasi masuk dan data pegawai baru berhasil disimpan.'
                        : 'Mutasi disimpan.')
            );
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
            flash(
                $pegawaiBaru && $exception->getCode() === '23000'
                    ? 'NIP ' . $nipPegawaiBaru . ' sudah terdaftar.'
                    : 'Gagal menyimpan transaksi atau lampiran. Pastikan migrasi database_lampiran.sql dan database_promosi_keluar.sql sudah dijalankan.',
                true
            );
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
    <form method="post" class="fl two" enctype="multipart/form-data" id="transaksi-form">
        <?= csrf_field() ?>

        <label>
            TPM
            <select name="tipe" id="tipe">
                <option value="promosi" <?= ($_POST['tipe'] ?? '') === 'promosi' ? 'selected' : '' ?>>Promosi</option>
                <option value="mutasi" <?= ($_POST['tipe'] ?? '') === 'mutasi' ? 'selected' : '' ?>>Mutasi</option>
            </select>
        </label>

        <label class="mutation-field" hidden>
            Jenis mutasi
            <select name="jenis_mutasi" id="jenis-mutasi">
                <?php foreach ($jenisMutasiPilihan as $jenis): ?>
                    <option value="<?= e($jenis) ?>" <?= ($_POST['jenis_mutasi'] ?? 'Masuk') === $jenis ? 'selected' : '' ?>>
                        <?= e($jenis) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="promotion-type-field" hidden>
            Jenis promosi
            <select name="jenis_promosi" id="jenis-promosi">
                <?php foreach ($jenisPromosiPilihan as $jenis): ?>
                    <option
                        value="<?= e($jenis) ?>"
                        <?= ($_POST['jenis_promosi'] ?? 'Naik Jabatan') === $jenis ? 'selected' : '' ?>
                    >
                        <?= e($jenis) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label id="tanggal-keluar-field" hidden>
            Tanggal keluar
            <input
                type="date"
                name="tanggal_keluar"
                id="tanggal-keluar"
                value="<?= e($_POST['tanggal_keluar'] ?? '') ?>"
                disabled
                required
            >
        </label>

        <label>
            Pegawai
            <select name="pegawai_id" id="pegawai" required>
                <option value="">Pilih pegawai</option>
                <option
                    value="__pegawai_baru__"
                    <?= ($_POST['pegawai_id'] ?? '') === '__pegawai_baru__' ? 'selected' : '' ?>
                >
                    Pegawai baru (belum terdaftar)
                </option>
                <?php foreach ($pegawaiList as $item): ?>
                    <option
                        value="<?= (int) $item['id_pegawai'] ?>"
                        data-status="<?= e($item['status_aktif']) ?>"
                        data-jabatan="<?= (int) $item['id_jabatan'] ?>"
                        data-nama-jabatan="<?= e($item['nama_jabatan']) ?>"
                        data-pangkat="<?= (int) $item['id_pangkat'] ?>"
                        <?= (string) ($item['id_pegawai']) === ($_POST['pegawai_id'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($item['nama_lengkap']) ?> · <?= e($item['nip']) ?> (<?= e($item['status_aktif']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="hint">Pilih pegawai baru jika belum tercatat; TMT menentukan bulan masuk pada filter daftar pegawai.</span>
        </label>

        <label class="new-employee-field" hidden>
            Nama lengkap dan gelar
            <input name="nama_pegawai_baru" maxlength="150" value="<?= e($_POST['nama_pegawai_baru'] ?? '') ?>" disabled>
        </label>

        <label class="new-employee-field" hidden>
            NIP
            <input
                name="nip_pegawai_baru"
                inputmode="numeric"
                pattern="[0-9]{18}"
                minlength="18"
                maxlength="18"
                value="<?= e($_POST['nip_pegawai_baru'] ?? '') ?>"
                disabled
            >
        </label>

        <label class="new-employee-field" hidden>
            Jabatan
            <select name="id_jabatan_pegawai_baru" disabled>
                <option value="">Pilih jabatan</option>
                <?php foreach ($jabatanList as $jabatan): ?>
                    <option
                        value="<?= (int) $jabatan['id_jabatan'] ?>"
                        <?= (string) $jabatan['id_jabatan'] === ($_POST['id_jabatan_pegawai_baru'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($jabatan['nama_jabatan']) ?> (<?= e($jabatan['kategori']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="new-employee-field" hidden>
            Pangkat/golongan
            <select name="id_pangkat_pegawai_baru" disabled>
                <option value="">Pilih pangkat/golongan</option>
                <?php foreach ($pangkatList as $pangkat): ?>
                    <option
                        value="<?= (int) $pangkat['id_pangkat'] ?>"
                        <?= (string) $pangkat['id_pangkat'] === ($_POST['id_pangkat_pegawai_baru'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($pangkat['golongan']) ?> — <?= e($pangkat['nama_pangkat']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="new-employee-field" hidden>
            Pendidikan terakhir
            <select name="pendidikan_pegawai_baru" id="pendidikan-pegawai-baru" disabled>
                <option value="">Pilih pendidikan</option>
                <?php foreach ($pendidikanList as $pendidikan): ?>
                    <option
                        value="<?= e($pendidikan) ?>"
                        <?= ($_POST['pendidikan_pegawai_baru'] ?? '') === $pendidikan ? 'selected' : '' ?>
                    >
                        <?= e($pendidikan) ?>
                    </option>
                <?php endforeach; ?>
                <option
                    value="<?= e($pilihanPendidikanLainnya) ?>"
                    <?= ($_POST['pendidikan_pegawai_baru'] ?? '') === $pilihanPendidikanLainnya ? 'selected' : '' ?>
                >
                    Lainnya...
                </option>
            </select>
        </label>

        <label class="new-employee-other-field" hidden>
            Pendidikan lainnya
            <input
                name="pendidikan_lainnya_pegawai_baru"
                maxlength="100"
                value="<?= e($_POST['pendidikan_lainnya_pegawai_baru'] ?? '') ?>"
                disabled
            >
        </label>

        <label class="new-employee-field" hidden>
            TMT di PN Yogyakarta
            <input
                type="date"
                name="tmt_pegawai_baru"
                value="<?= e($_POST['tmt_pegawai_baru'] ?? date('Y-m-d')) ?>"
                disabled
            >
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

        <label class="promotion-field" hidden>
            Jabatan lama
            <input id="jabatan-lama" readonly disabled>
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
            <select name="id_jabatan_baru" id="jabatan-baru" required>
                <option value="">Pilih jabatan</option>
                <?php foreach ($jabatanList as $jabatan): ?>
                    <option
                        value="<?= (int) $jabatan['id_jabatan'] ?>"
                        <?= (string) $jabatan['id_jabatan'] === ($_POST['id_jabatan_baru'] ?? '') ? 'selected' : '' ?>
                    >
                        <?= e($jabatan['nama_jabatan']) ?> (<?= e($jabatan['kategori']) ?>)
                    </option>
                <?php endforeach; ?>
                <option
                    value="<?= e($pilihanJabatanLainnya) ?>"
                    <?= ($_POST['id_jabatan_baru'] ?? '') === $pilihanJabatanLainnya ? 'selected' : '' ?>
                >
                    Lainnya...
                </option>
            </select>
        </label>

        <label class="promotion-field" id="jabatan-lainnya-field" hidden>
            Jabatan baru lainnya
            <input
                id="jabatan-lainnya"
                name="jabatan_lainnya"
                maxlength="100"
                value="<?= e($_POST['jabatan_lainnya'] ?? '') ?>"
                disabled
                placeholder="Masukkan nama jabatan"
            >
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
                Promosi memperbarui pangkat dan jabatan. Pegawai yang keluar dihapus dari data pegawai, tetapi riwayat dan lampiran tetap disimpan.
            </p>
        </div>
    </form>
</div>

<script>
    const tipeSelect = document.getElementById('tipe');
    const formTransaksi = document.getElementById('transaksi-form');
    const jenisMutasi = document.getElementById('jenis-mutasi');
    const jenisPromosi = document.getElementById('jenis-promosi');
    const tanggalKeluarField = document.getElementById('tanggal-keluar-field');
    const tanggalKeluarInput = document.getElementById('tanggal-keluar');
    const pegawaiSelect = document.getElementById('pegawai');
    const pendidikanPegawaiBaru = document.getElementById('pendidikan-pegawai-baru');
    const pendidikanLainnyaField = document.querySelector('.new-employee-other-field');
    const pendidikanLainnyaInput = pendidikanLainnyaField.querySelector('input');
    const pangkatSelect = document.getElementById('pangkat-baru');
    const jabatanSelect = document.getElementById('jabatan-baru');
    const jabatanLamaInput = document.getElementById('jabatan-lama');
    const jabatanLainnyaField = document.getElementById('jabatan-lainnya-field');
    const jabatanLainnyaInput = document.getElementById('jabatan-lainnya');
    const pilihanJabatanLainnya = <?= json_encode($pilihanJabatanLainnya) ?>;
    const tanggalSk = document.getElementById('tanggal-sk');
    const hari = document.getElementById('hari');
    const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    function sinkronkanForm(perbaruiJabatan = false) {
        const promosi = tipeSelect.value === 'promosi';
        const mutasiMasuk = !promosi && jenisMutasi.value === 'Masuk';
        const promosiNaikJabatan = promosi && jenisPromosi.value === 'Naik Jabatan';
        const promosiKeluar = promosi && jenisPromosi.value === 'Keluar';
        const pegawaiBaru = mutasiMasuk && pegawaiSelect.value === '__pegawai_baru__';
        document.querySelectorAll('.new-employee-field').forEach((field) => {
            field.hidden = !pegawaiBaru;
            const control = field.querySelector('select, input');
            if (control) {
                control.disabled = !pegawaiBaru;
                control.required = pegawaiBaru;
            }
        });
        const pendidikanLainnya = pegawaiBaru
            && pendidikanPegawaiBaru.value === <?= json_encode($pilihanPendidikanLainnya) ?>;
        pendidikanLainnyaField.hidden = !pendidikanLainnya;
        pendidikanLainnyaInput.disabled = !pendidikanLainnya;
        pendidikanLainnyaInput.required = pendidikanLainnya;

        const pilihanPegawaiBaru = pegawaiSelect.querySelector('option[value="__pegawai_baru__"]');
        pilihanPegawaiBaru.disabled = !mutasiMasuk;
        if (!mutasiMasuk && pilihanPegawaiBaru.selected) {
            pegawaiSelect.value = '';
        }

        document.querySelector('.promotion-type-field').hidden = !promosi;
        document.querySelectorAll('.promotion-field').forEach((field) => {
            field.hidden = !promosiNaikJabatan;
            const control = field.querySelector('select, input');
            if (control) {
                control.disabled = !promosiNaikJabatan;
            }
        });
        document.querySelectorAll('.mutation-field').forEach((field) => {
            field.hidden = promosi;
            const control = field.querySelector('select, input');
            if (control && field !== tanggalKeluarField) {
                control.disabled = promosi;
            }
        });
        tanggalKeluarField.hidden = !promosiKeluar;
        tanggalKeluarInput.disabled = !promosiKeluar;
        tanggalKeluarInput.required = promosiKeluar;

        document.getElementById('tujuan-label').textContent = promosi
            ? (promosiKeluar ? 'Tujuan/instansi keluar' : 'Penempatan setelah promosi')
            : 'Tujuan mutasi';
        document.getElementById('transaksi-keterangan').textContent = promosi
            ? (promosiKeluar
                ? 'Data pegawai akan dihapus setelah disimpan; riwayat dan lampiran tetap disimpan.'
                : 'Promosi memperbarui pangkat dan jabatan pegawai.')
            : 'Mutasi Masuk mencatat pegawai masuk atau mengaktifkan kembali pegawai Non-Aktif.';
        if (promosiKeluar) {
            formTransaksi.dataset.confirmTitle = 'Konfirmasi pegawai keluar?';
            formTransaksi.dataset.confirmMessage =
                'Data pegawai akan dihapus permanen dari daftar. Riwayat dan lampiran tetap disimpan.';
            formTransaksi.dataset.confirmButton = 'Ya, Hapus Pegawai';
        } else {
            delete formTransaksi.dataset.confirmTitle;
            delete formTransaksi.dataset.confirmMessage;
            delete formTransaksi.dataset.confirmButton;
        }

        const option = pegawaiSelect.selectedOptions[0];
        if (option && option.value && option.value !== '__pegawai_baru__') {
            pangkatSelect.value = option.dataset.pangkat;
            jabatanLamaInput.value = option.dataset.namaJabatan;
            if (perbaruiJabatan) {
                jabatanSelect.value = option.dataset.jabatan;
            }
        } else {
            jabatanLamaInput.value = '';
        }

        const tampilkanJabatanLainnya = promosiNaikJabatan
            && jabatanSelect.value === pilihanJabatanLainnya;
        jabatanLainnyaField.hidden = !tampilkanJabatanLainnya;
        jabatanLainnyaInput.disabled = !tampilkanJabatanLainnya;
        jabatanLainnyaInput.required = tampilkanJabatanLainnya;
    }

    tipeSelect.addEventListener('change', () => sinkronkanForm(false));
    pegawaiSelect.addEventListener('change', () => sinkronkanForm(true));
    jenisMutasi.addEventListener('change', () => sinkronkanForm(false));
    jenisPromosi.addEventListener('change', () => sinkronkanForm(false));
    jabatanSelect.addEventListener('change', () => sinkronkanForm(false));
    pendidikanPegawaiBaru.addEventListener('change', () => sinkronkanForm(false));
    sinkronkanForm();
    tanggalSk.addEventListener('change', () => {
        hari.textContent = tanggalSk.value
            ? namaHari[new Date(`${tanggalSk.value}T00:00:00`).getDay()]
            : '';
    });
    sinkronkanForm(false);
    tanggalSk.dispatchEvent(new Event('change'));
</script>

<?php include 'includes/footer.php'; ?>
