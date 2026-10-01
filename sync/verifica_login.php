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

// Rondante tem acesso restrito: apenas as páginas de ronda.
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
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
