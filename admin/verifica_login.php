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
?>
