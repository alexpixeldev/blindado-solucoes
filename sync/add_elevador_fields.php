<?php
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Adicionar Campos - Elevador</title>";
echo "</head>";
echo "<body>";

// Adicionar campos elevador_empresa e elevador_contato na tabela edificios
$sql = "ALTER TABLE edificios ADD COLUMN elevador_empresa VARCHAR(255) NULL AFTER sindico_contato";
if ($conn->query($sql)) {
    echo "Campo 'elevador_empresa' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'elevador_empresa': " . $conn->error . "<br>";
}

$sql = "ALTER TABLE edificios ADD COLUMN elevador_contato VARCHAR(255) NULL AFTER elevador_empresa";
if ($conn->query($sql)) {
    echo "Campo 'elevador_contato' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'elevador_contato': " . $conn->error . "<br>";
}

echo "</body>";
echo "</html>";

$conn->close();
?>
