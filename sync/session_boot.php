<?php
if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

$session_lifetime = 60 * 60 * 12;
if (isset($_POST['remember-me']) && $_POST['remember-me'] !== '') {
    $session_lifetime = 60 * 60 * 24 * 30;
}

$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'], true);

$candidates = [];
if (!$is_local) {
    $candidates[] = dirname(__DIR__, 2) . '/.blindado_sessions';
}
$candidates[] = __DIR__ . '/.sessions';

$session_dir = null;
foreach ($candidates as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        $session_dir = $dir;
        break;
    }
}

if ($session_dir !== null) {
    ini_set('session.save_path', $session_dir);
    if ($session_dir === __DIR__ . '/.sessions' && !file_exists($session_dir . '/.htaccess')) {
        @file_put_contents(
            $session_dir . '/.htaccess',
            "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n"
        );
    }
}

ini_set('session.gc_maxlifetime', (string) $session_lifetime);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.cookie_lifetime', (string) ($session_lifetime > 60 * 60 * 12 ? $session_lifetime : 0));
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', '1');

$https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_secure', $https ? '1' : '0');

session_start();
