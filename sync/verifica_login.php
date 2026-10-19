<?php
ob_start(); // Prevents "Headers already sent" errors and buffers output
require_once __DIR__ . '/session_boot.php';

// Only checks if the user is logged in. 
// Control of what they see is done individually on each page or in the sidebar.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Logout programado: TODO usuario logado e deslogado nos horarios de troca de
// plantao (06:50 e 18:50, horario de Brasilia), independente de quando entrou.
// A janela de cada turno comeca no horario de troca e vai ate a proxima.
date_default_timezone_set('America/Sao_Paulo');
$hora_atual = date('Y-m-d') . ' ' . ((int) date('H') * 3600 + (int) date('i') * 60 >= 18 * 3600 + 50 * 60
    ? '18:50'
    : ((int) date('H') * 3600 + (int) date('i') * 60 >= 6 * 3600 + 50 * 60 ? '06:50' : 'pre-06:50'));
if (($_SESSION['turno_corte'] ?? null) !== $hora_atual) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: login.php?expirada=1');
    exit();
}

// Categorias renomeadas: normaliza a sessao para o valor novo.
// Sem isso, uma sessao aberta antes do rename ficaria sem escopo de base
// e o usuario veria dados de todas as bases.
$cat_sessao = $_SESSION['usuario_categoria'] ?? '';
if ($cat_sessao === 'super' . 'visor') {
    $_SESSION['usuario_categoria'] = 'supervisor_monitoramento';
    $cat_sessao = 'supervisor_monitoramento';
}

// Rondante tem acesso restrito: apenas as páginas de ronda.
$usuario_categoria = $cat_sessao;
if ($usuario_categoria === 'rondante') {
    $pagina_atual = basename($_SERVER['PHP_SELF']);
    $permitidas = [
        'rondante.php',
        'rondante_scan.php',
        'rondante_api.php',
        'perfil.php',
        'logout.php'
    ];
    if (!in_array($pagina_atual, $permitidas)) {
        header("Location: rondante.php");
        exit();
    }
}
?>
