<?php
ob_start(); // Prevents "Headers already sent" errors and buffers output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only checks if the user is logged in. 
// Control of what they see is done individually on each page or in the sidebar.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
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
