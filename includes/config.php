<?php
// Sesuaikan dengan server MySQL Anda (XAMPP default: root tanpa password)
$DB = ['host'=>'localhost','name'=>'pn_yogya','user'=>'root','pass'=>''];
session_name('SIMPEG_PN_YOGYAKARTA');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime'=>0,
    'path'=>'/',
    'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
    'httponly'=>true,
    'samesite'=>'Lax'
]);
session_start();
try {
    $pdo = new PDO("mysql:host={$DB['host']};dbname={$DB['name']};charset=utf8mb4", $DB['user'], $DB['pass'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (PDOException $x) {
    die('Koneksi ke database pn_yogya gagal. Periksa konfigurasi MySQL di includes/config.php.');
}
const BLN  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const HARI = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tgl_id($d){ if(!$d) return '-'; $t=strtotime($d); return HARI[date('w',$t)].', '.date('j',$t).' '.BLN[date('n',$t)-1].' '.date('Y',$t); }
function opts(array $l, $sel='', $all=null){
    $h = $all!==null ? '<option value="">'.e($all).'</option>' : '';
    foreach($l as $v) $h .= '<option'.($v===$sel?' selected':'').'>'.e($v).'</option>';
    return $h;
}
function bln_opts($sel){
    $h = '<option value="">Semua bulan</option>';
    foreach(BLN as $i=>$b) $h .= '<option value="'.($i+1).'"'.((string)$sel===(string)($i+1)?' selected':'').'>'.$b.'</option>';
    return $h;
}
function csrf_field(){ $_SESSION['t'] = $_SESSION['t'] ?? bin2hex(random_bytes(16)); return '<input type="hidden" name="t" value="'.$_SESSION['t'].'">'; }
function csrf_ok(){ return isset($_POST['t'],$_SESSION['t']) && hash_equals($_SESSION['t'],$_POST['t']); }
function flash($m,$err=false){ $_SESSION['f']=[$m,$err]; }
function valid_date($d){ $x=DateTime::createFromFormat('Y-m-d',$d); return $x && $x->format('Y-m-d')===$d; }
function require_login(){
    if(empty($_SESSION['id_user'])){
        header('Location: login.php');
        exit;
    }
}

function daftar_jabatan($kategori = null){
    global $pdo;
    $sql = 'SELECT id_jabatan, nama_jabatan, kategori FROM jabatan';
    $params = [];
    if($kategori !== null){
        $sql .= ' WHERE kategori = ?';
        $params[] = $kategori;
    }
    $sql .= ' ORDER BY nama_jabatan';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function daftar_pangkat(){
    global $pdo;
    return $pdo->query(
        'SELECT id_pangkat, nama_pangkat, golongan FROM pangkat_golongan ORDER BY id_pangkat'
    )->fetchAll();
}

function daftar_pendidikan(){
    global $pdo;
    $standar = ['D3', 'S1', 'S2', 'S3'];
    $tersimpan = $pdo->query(
        "SELECT DISTINCT pendidikan_terakhir
         FROM pegawai
         WHERE pendidikan_terakhir IS NOT NULL AND pendidikan_terakhir <> ''
         ORDER BY pendidikan_terakhir"
    )->fetchAll(PDO::FETCH_COLUMN);
    $lainnya = array_values(array_diff($tersimpan, $standar));
    sort($lainnya, SORT_NATURAL | SORT_FLAG_CASE);
    return array_merge($standar, $lainnya);
}
