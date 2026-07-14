<?php
/**
 * Arquivo de conexão para a pasta admin.
 * Inclui o arquivo de conexão centralizado e gerencia a sessão.
 */

// Inclui o arquivo de conexão principal (um nível acima)
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/security_lib.php';

// Inicia a sessão se ainda não estiver iniciada (boa prática)
if (session_status() == PHP_SESSION_NONE) {
    // Configurações de segurança para a sessão
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}
?>
