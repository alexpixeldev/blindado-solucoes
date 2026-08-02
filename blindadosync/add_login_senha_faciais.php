<?php
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Adicionar Campos - Login Senha Faciais</title>";
echo "</head>";
echo "<body>";

// Adicionar campos login e senha na tabela controle_faciais
$sql = "ALTER TABLE controle_faciais ADD COLUMN login VARCHAR(100) NULL AFTER acessos";
if ($conn->query($sql)) {
    echo "Campo 'login' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'login': " . $conn->error . "<br>";
}

$sql = "ALTER TABLE controle_faciais ADD COLUMN senha VARCHAR(100) NULL AFTER login";
if ($conn->query($sql)) {
    echo "Campo 'senha' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'senha': " . $conn->error . "<br>";
}

echo "</body>";
echo "</html>";

$conn->close();
?>
