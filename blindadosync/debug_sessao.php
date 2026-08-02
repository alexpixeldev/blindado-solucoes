<?php
require_once 'C:/xampp/htdocs/blindado/blindadosync/verifica_login.php';
require_once 'C:/xampp/htdocs/blindado/blindadosync/conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Debug Sessão</title>";
echo "</head>";
echo "<body>";
echo "<pre>";
echo 'categoria: ' . ($_SESSION['usuario_categoria'] ?? 'NENHUMA') . "\n";
echo 'id: ' . ($_SESSION['usuario_id'] ?? 'NENHUM') . "\n";
echo "</pre>";
echo "</body>";
echo "</html>";
