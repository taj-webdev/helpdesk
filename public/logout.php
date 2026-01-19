<?php
// public/logout.php
session_start();

// Hapus semua data session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Arahkan kembali ke halaman login (atau index)
header('Location: login.php?logout=success');
exit;
