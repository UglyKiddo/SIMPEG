<?php
require_once 'includes/config.php';
require_login();

$nama = '';
$nip = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (!csrf_ok()) {
        $error = 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif ($nama === '' || mb_strlen($nama) > 100) {
        $error = 'Nama wajib diisi dan maksimal 100 karakter.';
    } elseif (!preg_match('/^\d{18}$/', $nip)) {
        $error = 'NIP harus terdiri dari 18 angka.';
    } elseif ($password === '') {
        $error = 'Kata sandi wajib diisi.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak sama.';
    } else {
        try {
            $statement = $pdo->prepare(
                "INSERT INTO users (username, nip, password, nama_user, role)
                 VALUES (?, ?, ?, ?, 'Pegawai')"
            );
            $statement->execute([
                $nip,
                $nip,
                password_hash($password, PASSWORD_DEFAULT),
                $nama,
            ]);

            flash('Akun Pengelola Kepegawaian berhasil ditambahkan.');
            header('Location: akun.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'NIP tersebut sudah digunakan oleh akun lain.';
            } else {
                error_log('Gagal menambahkan akun SIMPEG: ' . $exception->getMessage());
                $error = 'Gagal menambahkan akun. Silakan coba lagi.';
            }
        }
    }
}

$judul = 'Tambah akun';
include 'includes/header.php';
?>

<div class="card">
    <h2>Buat akun pengguna</h2>
    <p class="hint">
        Akun baru menggunakan role Pengelola Kepegawaian yang sama dan dapat masuk
        menggunakan NIP serta kata sandi yang dibuat di sini.
    </p>

    <?php if ($error !== ''): ?>
        <div class="msg err" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="fl two">
        <?= csrf_field() ?>

        <label class="full">
            Nama lengkap
            <input name="nama" required maxlength="100" autocomplete="name" value="<?= e($nama) ?>">
        </label>

        <label>
            NIP
            <input
                name="nip"
                required
                inputmode="numeric"
                pattern="[0-9]{18}"
                title="NIP terdiri dari 18 angka"
                maxlength="18"
                autocomplete="username"
                value="<?= e($nip) ?>"
            >
        </label>

        <label>
            Kata sandi
            <input type="password" name="password" required autocomplete="new-password">
        </label>

        <label>
            Ulangi kata sandi
            <input type="password" name="konfirmasi" required autocomplete="new-password">
        </label>

        <div class="full">
            <button class="btn" type="submit">Tambah akun</button>
        </div>
    </form>
</div>
