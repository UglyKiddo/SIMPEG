<?php
require_once 'includes/config.php';
require_login();

if($_SERVER['REQUEST_METHOD']!=='POST'||!csrf_ok()){
    flash('Permintaan keluar tidak valid. Coba lagi.',true);
    header('Location: index.php');
    exit;
}

$_SESSION=[];
if(ini_get('session.use_cookies')){
    $params=session_get_cookie_params();
    setcookie(session_name(),'',[
        'expires'=>time()-42000,
        'path'=>$params['path'],
        'domain'=>$params['domain'],
        'secure'=>$params['secure'],
        'httponly'=>$params['httponly'],
        'samesite'=>$params['samesite']??'Lax'
    ]);
}
session_destroy();
header('Location: login.php');
exit;
