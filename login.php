<?php
require_once 'includes/config.php';

if (!empty($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}

if ((int) $pdo->query('SELECT COUNT(*) FROM users WHERE nip IS NOT NULL')->fetchColumn() === 0) {
    header('Location: setup.php');
    exit;
}

$nip = '';
$error = '';
$notice = '';
$noticeError = false;

if (isset($_SESSION['f'])) {
    [$notice, $noticeError] = $_SESSION['f'];
    unset($_SESSION['f']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['nip'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_ok()) {
        $error = 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (!preg_match('/^\d{18}$/', $nip)) {
        $error = 'NIP harus terdiri dari 18 angka.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id_user, nama_user, password FROM users WHERE nip = ?'
        );
        $statement->execute([$nip]);
        $pengguna = $statement->fetch();

        if ($pengguna && password_verify($password, $pengguna['password'])) {
            session_regenerate_id(true);
            $_SESSION['id_user'] = (int) $pengguna['id_user'];
            $_SESSION['pengguna_nama'] = $pengguna['nama_user'];
            unset($_SESSION['t']);

            header('Location: index.php');
            exit;
        }

        $error = 'Nama pengguna atau kata sandi tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - SIMPEG PN Yogyakarta</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-screen">
    <main class="auth-container">
        <div class="auth-top-bar"></div>

        <header class="auth-brand">
            <img src="assets/img/logo.png" alt="Logo Pengadilan Negeri Yogyakarta">
            <h1>SIMPEG</h1>
            <p>Sistem Informasi Manajemen Kepegawaian<br>Pengadilan Negeri Yogyakarta</p>
        </header>

        <?php if ($notice !== ''): ?>
            <div class="auth-alert<?= $noticeError ? ' is-error' : ' is-success' ?>" role="status">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="auth-alert is-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <?= csrf_field() ?>

            <div class="auth-field">
                <label for="nip">NIP</label>
                <div class="auth-input-wrap">
                    <svg class="auth-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0H5Z"/>
                    </svg>
                    <input
                        class="auth-input"
                        id="nip"
                        name="nip"
                        type="text"
                        placeholder="Masukkan NIP"
                        inputmode="numeric"
                        pattern="[0-9]{18}"
                        autocomplete="username"
                        maxlength="18"
                        value="<?= e($nip) ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="auth-field">
                <label for="password">Kata Sandi</label>
                <div class="auth-input-wrap">
                    <svg class="auth-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v9h14v-9a2 2 0 0 0-2-2ZM10 7a2 2 0 1 1 4 0v2h-4V7Zm2 10a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>
                    </svg>
                    <input
                        class="auth-input auth-password-input"
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Masukkan kata sandi"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        class="auth-password-toggle"
                        id="password-toggle"
                        type="button"
                        aria-label="Tampilkan kata sandi"
                        aria-pressed="false"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path id="password-toggle-icon" d="M3 3 21 21M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c5 0 9 4.5 10 7a12 12 0 0 1-3 4M6.2 6.2C4 7.7 2.6 9.8 2 12c1 2.5 5 7 10 7a10.5 10.5 0 0 0 4-.8"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button class="auth-submit" type="submit">
                <span>MASUK</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M13 5 20 12l-7 7-1.4-1.4 4.6-4.6H4v-2h12.2l-4.6-4.6L13 5Z"/>
                </svg>
            </button>
        </form>

        <footer class="auth-footer">
            &copy; <?= date('Y') ?> Pengadilan Negeri Yogyakarta
        </footer>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');
        const passwordToggleIcon = document.getElementById('password-toggle-icon');

        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(showPassword));
            passwordToggle.setAttribute(
                'aria-label',
                showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
            );
            passwordToggleIcon.setAttribute(
                'd',
                showPassword
                    ? 'M12 5c-5 0-9 4.5-10 7 1 2.5 5 7 10 7s9-4.5 10-7c-1-2.5-5-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z'
                    : 'M3 3 21 21M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c5 0 9 4.5 10 7a12 12 0 0 1-3 4M6.2 6.2C4 7.7 2.6 9.8 2 12c1 2.5 5 7 10 7a10.5 10.5 0 0 0 4-.8'
            );
        });
    </script>
</body>
</html>
