<?php
require_once 'C:/xampp/htdocs/blindado/blindadosync/verifica_login.php';
require_once 'C:/xampp/htdocs/blindado/blindadosync/conexao.php';
header('Content-Type: text/plain');
echo 'categoria: ' . ($_SESSION['usuario_categoria'] ?? 'NENHUMA') . "\n";
echo 'id: ' . ($_SESSION['usuario_id'] ?? 'NENHUM') . "\n";
