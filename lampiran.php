<?php
require_once 'includes/config.php';
require_login();

$idLampiran = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idLampiran || $idLampiran < 1) {
    http_response_code(400);
    exit('Permintaan lampiran tidak valid.');
}

$statement = $pdo->prepare(
    'SELECT nama_asli, nama_file, file_path, mime_type, COALESCE(ukuran, ukuran_file) AS ukuran
     FROM lampiran_transaksi
     WHERE id_lampiran = ?'
);
$statement->execute([$idLampiran]);
$lampiran = $statement->fetch();

if (!$lampiran || !preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp|pdf|doc|docx|xls|xlsx)\z/', $lampiran['nama_file'])) {
    http_response_code(404);
    exit('Lampiran tidak ditemukan.');
}

$pathRelatif = $lampiran['file_path'] ?? '';
if ($pathRelatif === 'private/lampiran-transaksi/' . $lampiran['nama_file']) {
    $path = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'simpeg-pn-yogya-storage'
        . DIRECTORY_SEPARATOR . 'lampiran-transaksi'
        . DIRECTORY_SEPARATOR . $lampiran['nama_file'];
} elseif ($pathRelatif === 'uploads/sk/' . $lampiran['nama_file']) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'sk'
        . DIRECTORY_SEPARATOR . $lampiran['nama_file'];
} else {
    http_response_code(404);
    exit('Lokasi file lampiran tidak valid.');
}
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('File lampiran tidak tersedia.');
}

$namaAsli = str_replace(["\r", "\n", '"'], '', $lampiran['nama_asli']);
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header(
    "Content-Disposition: attachment; filename=\"lampiran\"; filename*=UTF-8''"
    . rawurlencode($namaAsli)
);
readfile($path);
