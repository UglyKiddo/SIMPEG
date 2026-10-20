<?php
require_once 'includes/config.php';
require_login();

function url_embed_canva($url)
{
    $bagian = parse_url($url);
    if (
        !is_array($bagian)
        || ($bagian['scheme'] ?? '') !== 'https'
        || !in_array(strtolower($bagian['host'] ?? ''), ['canva.com', 'www.canva.com'], true)
        || isset($bagian['user'])
        || isset($bagian['pass'])
        || isset($bagian['port'])
        || !preg_match(
            '#\A/design/[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*/view\z#',
            $bagian['path'] ?? ''
        )
    ) {
        return null;
    }

    return 'https://www.canva.com' . $bagian['path'] . '?embed';
}

$direktoriPengaturan = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'simpeg-pn-yogya-storage'
    . DIRECTORY_SEPARATOR . 'pengaturan';
$fileUrlCanva = $direktoriPengaturan . DIRECTORY_SEPARATOR . 'canva-url.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if (!csrf_ok()) {
        flash('Sesi tidak valid. Muat ulang halaman dan coba lagi.', true);
    } elseif ($aksi === 'hapus') {
        if (is_file($fileUrlCanva) && !unlink($fileUrlCanva)) {
            error_log('Gagal menghapus tautan presentasi Canva.');
            flash('Tautan Canva gagal dihapus. Periksa izin folder penyimpanan aplikasi.', true);
        } else {
            flash('Tautan presentasi Canva berhasil dihapus.');
        }
    } elseif ($aksi === 'simpan') {
        $urlCanva = is_string($_POST['canva_url'] ?? null)
            ? trim($_POST['canva_url'])
            : '';
        $embedUrl = url_embed_canva($urlCanva);

        if ($embedUrl === null) {
            flash('Masukkan tautan tampilan presentasi Canva yang valid (https://www.canva.com/design/.../view).', true);
        } elseif (
            (!is_dir($direktoriPengaturan) && !mkdir($direktoriPengaturan, 0750, true) && !is_dir($direktoriPengaturan))
            || !is_writable($direktoriPengaturan)
        ) {
            error_log('Direktori pengaturan Canva tidak tersedia atau tidak dapat ditulis.');
            flash('Pengaturan Canva tidak dapat disimpan. Pastikan folder penyimpanan aplikasi dapat ditulis.', true);
        } else {
            $fileSementara = tempnam($direktoriPengaturan, 'canva-');
            if (
                $fileSementara === false
                || file_put_contents($fileSementara, $urlCanva, LOCK_EX) === false
                || !rename($fileSementara, $fileUrlCanva)
            ) {
                if (is_string($fileSementara) && is_file($fileSementara)) {
                    unlink($fileSementara);
                }
                error_log('Gagal menyimpan tautan presentasi Canva.');
                flash('Tautan Canva gagal disimpan. Periksa izin folder penyimpanan aplikasi.', true);
            } else {
                flash('Tautan presentasi Canva berhasil disimpan.');
            }
        }
    } else {
        flash('Permintaan tidak valid. Muat ulang halaman dan coba lagi.', true);
    }

    header('Location: canva.php');
    exit;
}

$urlCanva = '';
$embedUrl = null;
$gagalMembacaPengaturan = false;
if (is_file($fileUrlCanva)) {
    $isiFile = file_get_contents($fileUrlCanva);
    if ($isiFile === false) {
        error_log('Gagal membaca tautan presentasi Canva.');
        $gagalMembacaPengaturan = true;
    } else {
        $urlCanva = trim($isiFile);
        $embedUrl = url_embed_canva($urlCanva);
    }
}

$judul = 'Presentasi Data Pegawai';
include 'includes/header.php';
?>

<section class="card canva-settings" aria-labelledby="canva-settings-title">
    <h2 id="canva-settings-title">Hubungkan presentasi Canva</h2>
    <p class="sub">
        Tempel tautan tampilan presentasi Canva. Presentasi harus dapat diakses oleh pengguna yang membuka SIMPEG.
    </p>

    <?php if ($gagalMembacaPengaturan): ?>
        <p class="canva-message" role="alert">Tautan Canva tidak dapat dibaca dari penyimpanan aplikasi.</p>
    <?php elseif ($urlCanva !== '' && $embedUrl === null): ?>
        <p class="canva-message" role="alert">Tautan yang tersimpan tidak valid. Masukkan kembali tautan tampilan Canva.</p>
    <?php endif; ?>

    <form method="post" class="canva-form">
        <?= csrf_field() ?>
        <label for="canva-url">Tautan presentasi Canva</label>
        <div class="canva-form-controls">
            <input
                id="canva-url"
                name="canva_url"
                type="url"
                inputmode="url"
                placeholder="https://www.canva.com/design/.../view"
                value="<?= e($urlCanva) ?>"
                required
            >
            <button class="btn" type="submit" name="aksi" value="simpan">Simpan tautan</button>
            <?php if (is_file($fileUrlCanva)): ?>
                <button
                    class="btn g"
                    type="submit"
                    name="aksi"
                    value="hapus"
                    formnovalidate
                    data-confirm-title="Yakin ingin menghapus tautan?"
                    data-confirm-message="Tautan presentasi Canva akan dihapus."
                    data-confirm-button="Ya, Hapus"
                >Hapus tautan</button>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if ($embedUrl !== null): ?>
    <section class="card canva-presentation" aria-label="Presentasi data pegawai">
        <div class="canva-presentation-heading">
            <div>
                <h2>Presentasi data pegawai</h2>
                <p class="sub">Konten mengikuti presentasi yang dikelola di Canva dan tidak tersinkron otomatis dari database SIMPEG.</p>
            </div>
            <a class="btn canva-open" href="<?= e($urlCanva) ?>" target="_blank" rel="noopener noreferrer">
                Buka di Canva
            </a>
        </div>
        <iframe
            class="canva-frame"
            src="<?= e($embedUrl) ?>"
            title="Presentasi data pegawai di Canva"
            allow="fullscreen"
            allowfullscreen
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
        ></iframe>
    </section>
<?php else: ?>
    <section class="card canva-empty">
        <h2>Presentasi belum dihubungkan</h2>
        <p>Masukkan tautan tampilan presentasi dari Canva di atas untuk menampilkannya di sini.</p>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
