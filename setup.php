<?php
require_once 'includes/config.php';

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

if(!empty($_SESSION['id_user'])){
    header('Location: index.php');
    exit;
}

if((int)$pdo->query('SELECT COUNT(*) FROM users WHERE nip IS NOT NULL')->fetchColumn()>0){
    header('Location: login.php');
    exit;
}

$nama='';
$nip='';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nama=trim($_POST['nama']??'');
    $nip=trim($_POST['nip']??'');
    $password=$_POST['password']??'';
    $konfirmasi=$_POST['konfirmasi']??'';
    if(!csrf_ok()){
        $error='Sesi tidak valid. Muat ulang halaman dan coba lagi.';
    }elseif($nama===''||mb_strlen($nama)>120){
        $error='Nama wajib diisi dan maksimal 120 karakter.';
    }elseif(!preg_match('/^\d{18}$/',$nip)){
        $error='NIP harus terdiri dari 18 angka.';
    }elseif($password!==$konfirmasi){
        $error='Konfirmasi kata sandi tidak sama.';
    }else{
        $locked=false;
        try{
            $locked=(int)$pdo->query("SELECT GET_LOCK('pn_yogya_setup', 5)")->fetchColumn()===1;
            if(!$locked){
                $error='Setup akun sedang digunakan. Muat ulang halaman dan coba lagi.';
            }else{
                $pdo->beginTransaction();
                $jumlah=(int)$pdo->query('SELECT COUNT(*) FROM users WHERE nip IS NOT NULL')->fetchColumn();
                if($jumlah>0){
                    $pdo->rollBack();
                    $error='Akun awal sudah dibuat. Silakan masuk.';
                }else{
                    $st=$pdo->prepare(
                        "INSERT INTO users (username,nip,password,nama_user,role) VALUES (?,?,?,?, 'Pegawai')"
                    );
                    $st->execute([$nip,$nip,password_hash($password,PASSWORD_DEFAULT),$nama]);
                    $pdo->commit();
                    flash('Akun Pengelola Kepegawaian berhasil dibuat. Silakan masuk.');
                    header('Location: login.php');
                    exit;
                }
            }
        }catch(PDOException $x){
            if($pdo->inTransaction()) $pdo->rollBack();
            $error='Gagal membuat akun. Periksa koneksi database atau gunakan nama pengguna lain.';
        }finally{
            if($locked) $pdo->query("SELECT RELEASE_LOCK('pn_yogya_setup')");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Akun - SIMPEG PN Yogyakarta</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-screen">
    <main class="auth-container auth-container-setup">
        <div class="auth-top-bar"></div>

        <header class="auth-brand">
            <img src="assets/img/logo.png" alt="Logo Pengadilan Negeri Yogyakarta">
            <h1>SIMPEG</h1>
            <p>Sistem Informasi Manajemen Kepegawaian<br>Pengadilan Negeri Yogyakarta</p>
        </header>

        <h2 class="auth-title">Buat Akun Pengelola</h2>
        <p class="auth-description">
            Akun ini menggunakan satu-satunya role aplikasi: Pengelola Kepegawaian.
            Halaman setup terkunci setelah akun pertama dibuat.
        </p>

        <?php if ($error !== ''): ?>
            <div class="auth-alert is-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <?= csrf_field() ?>

            <div class="auth-field">
                <label for="nama">Nama Lengkap</label>
                <input class="auth-input auth-input-plain" id="nama" name="nama" autocomplete="name" required maxlength="120" value="<?= e($nama) ?>">
            </div>

            <div class="auth-field">
                <label for="nip">NIP</label>
                <input class="auth-input auth-input-plain" id="nip" name="nip" inputmode="numeric" autocomplete="username" required minlength="18" maxlength="18" pattern="[0-9]{18}" value="<?= e($nip) ?>">
            </div>

            <div class="auth-field">
                <label for="password">Kata Sandi</label>
                <input class="auth-input auth-input-plain" id="password" type="password" name="password" autocomplete="new-password" required>
            </div>

            <div class="auth-field">
                <label for="konfirmasi">Ulangi Kata Sandi</label>
                <input class="auth-input auth-input-plain" id="konfirmasi" type="password" name="konfirmasi" autocomplete="new-password" required>
            </div>

            <button class="auth-submit" type="submit">
                <span>BUAT AKUN</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M13 5 20 12l-7 7-1.4-1.4 4.6-4.6H4v-2h12.2l-4.6-4.6L13 5Z"/>
                </svg>
            </button>
        </form>

        <footer class="auth-footer">
            &copy; <?= date('Y') ?> Pengadilan Negeri Yogyakarta
        </footer>
    </main>
</body>
</html>
