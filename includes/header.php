<?php
require_once __DIR__ . '/config.php';

$hal = basename($_SERVER['SCRIPT_NAME'], '.php');
$tipe = $_GET['tipe'] ?? 'promosi';
$namaPengguna = $_SESSION['pengguna_nama'] ?? 'Pengelola';
$inisial = strtoupper(substr($namaPengguna, 0, 1));

function side_icon($name)
{
    $paths = [
        'dashboard' => '<path d="M4 13a8 8 0 1 1 16 0"/><path d="m12 13 4-4"/><path d="M5 18h14"/>',
        'add' => '<circle cx="12" cy="8" r="3"/><path d="M5 20c.5-3.1 2.9-5 7-5s6.5 1.9 7 5M19 8v6m-3-3h6"/>',
        'transfer' => '<path d="M4 8h15l-3-3m4 11H5l3 3"/>',
        'report' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7m-7 4h7"/>',
        'account' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c.4-3.2 2.3-5 6-5 2 0 3.5.6 4.5 1.7"/><path d="m18 13 .8 1.6 1.7.3-1.2 1.2.3 1.7-1.6-.8-1.6.8.3-1.7-1.2-1.2 1.7-.3z"/>',
        'logout' => '<path d="M10 5H5v14h5m4-4 4-3-4-3m4 3H9"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . ($paths[$name] ?? $paths['report'])
        . '</svg>';
}

function nav($url, $label, $icon, $active)
{
    return '<a class="side-link' . ($active ? ' active' : '') . '" href="' . $url . '"'
        . ($active ? ' aria-current="page"' : '') . '>'
        . '<span class="side-icon">' . side_icon($icon) . '</span>'
        . '<span>' . $label . '</span></a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Dashboard') ?> - SIMPEG PN Yogyakarta</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-screen">
    <aside class="sidebar">
        <a class="sidebar-brand" href="index.php">
            <img src="assets/img/logo.png" alt="">
            <span><strong>SIMPEG</strong><small>PNYK</small></span>
        </a>

        <div class="side-section">
            <span class="side-heading">MENU UTAMA</span>
            <?= nav('index.php', 'Dashboard', 'dashboard', $hal === 'index') ?>
        </div>

        <div class="side-section">
            <span class="side-heading">DATA KEPEGAWAIAN</span>
            <?= nav('tambah.php', 'Tambah Pegawai', 'add', $hal === 'tambah') ?>
            <?= nav('transaksi.php', 'Mutasi & Promosi', 'transfer', $hal === 'transaksi') ?>
        </div>

        <div class="side-section">
            <span class="side-heading">LAPORAN</span>
            <?= nav('rekap.php?tipe=promosi', 'Rekap Promosi', 'report', $hal === 'rekap' && $tipe === 'promosi') ?>
            <?= nav('rekap.php?tipe=mutasi', 'Rekap Mutasi', 'report', $hal === 'rekap' && $tipe === 'mutasi') ?>
        </div>

        <div class="side-section">
            <span class="side-heading">PENGELOLAAN AKUN</span>
            <?= nav('akun.php', 'Tambah akun', 'account', $hal === 'akun') ?>
        </div>

        <div class="side-section side-account">
            <span class="side-heading">AKUN</span>
            <form class="logout" action="logout.php" method="post">
                <?= csrf_field() ?>
                <button class="side-link" type="submit">
                    <span class="side-icon"><?= side_icon('logout') ?></span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="sidebar-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-user">
                <div class="topbar-user-text">
                    <strong><?= e($namaPengguna) ?></strong>
                    <span>Pengelola Kepegawaian</span>
                </div>
                <span class="user-avatar" aria-hidden="true"><?= e($inisial) ?></span>
            </div>
        </header>

        <main class="page-content">
            <div class="page-heading">
                <h1><?= e($judul ?? 'Dashboard') ?></h1>
                <p>Pengadilan Negeri Yogyakarta</p>
            </div>

            <?php if (!empty($_SESSION['f'])): ?>
                <?php [$pesan, $isError] = $_SESSION['f']; unset($_SESSION['f']); ?>
                <div class="msg<?= $isError ? ' err' : '' ?>" role="<?= $isError ? 'alert' : 'status' ?>">
                    <?= e($pesan) ?>
                </div>
            <?php endif; ?>
