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

// Deslogar forçado nos horários de troca de plantão (18:50 e 06:50),
// para evitar que o próximo operador utilize a sessão de quem esqueceu logado.
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
} else {
    $now = time();
    $cutoff = null;
    foreach ([-1, 0] as $offset_dias) {
        $dia = strtotime(date('Y-m-d', $now + $offset_dias * 86400));
        foreach ([6 * 3600 + 50 * 60, 18 * 3600 + 50 * 60] as $segundos) {
            $momento = $dia + $segundos;
            if ($momento <= $now && ($cutoff === null || $momento > $cutoff)) {
                $cutoff = $momento;
            }
        }
    }
    if ($cutoff !== null && $_SESSION['login_time'] < $cutoff) {
        session_unset();
        session_destroy();
        header("Location: login.php?expirada=1");
        exit();
    }
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
