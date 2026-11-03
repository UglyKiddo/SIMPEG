<?php
require_once 'includes/config.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordSaatIni = $_POST['password_saat_ini'] ?? '';
    $passwordBaru = $_POST['password_baru'] ?? '';
    $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

    if (!csrf_ok()) {
        $error = 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif ($passwordSaatIni === '' || $passwordBaru === '') {
        $error = 'Password saat ini dan password baru wajib diisi.';
    } elseif ($passwordBaru !== $konfirmasiPassword) {
        $error = 'Konfirmasi password baru tidak sama.';
    } else {
        try {
            $statement = $pdo->prepare('SELECT password FROM users WHERE id_user = ?');
            $statement->execute([(int) $_SESSION['id_user']]);
            $passwordTersimpan = $statement->fetchColumn();

            if ($passwordTersimpan === false || !password_verify($passwordSaatIni, $passwordTersimpan)) {
                $error = 'Password saat ini tidak sesuai.';
            } else {
                $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id_user = ?');
                $statement->execute([
                    password_hash($passwordBaru, PASSWORD_DEFAULT),
                    (int) $_SESSION['id_user'],
                ]);
                session_regenerate_id(true);
                flash('Password berhasil diganti.');
                header('Location: ganti_password.php');
                exit;
            }
        } catch (PDOException $exception) {
            error_log('Gagal mengganti password pengguna: ' . $exception->getMessage());
            $error = 'Password gagal diganti. Silakan coba lagi.';
        }
    }
}

$judul = 'Ganti password';
include 'includes/header.php';
?>

<div class="card">
    <h2>Ganti password</h2>
    <p class="hint">Masukkan password saat ini, lalu tentukan password baru untuk akun Anda.</p>

    <?php if ($error !== ''): ?>
        <div class="msg err" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="fl two">
        <?= csrf_field() ?>

        <label class="full">
            Password saat ini
            <input
                type="password"
                name="password_saat_ini"
                autocomplete="current-password"
                required
                autofocus
            >
        </label>

        <label>
            Password baru
            <input type="password" name="password_baru" autocomplete="new-password" required>
        </label>

        <label>
            Ulangi password baru
            <input type="password" name="konfirmasi_password" autocomplete="new-password" required>
        </label>

        <div class="full">
            <button class="btn" type="submit">Simpan password baru</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
