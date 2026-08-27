<?php
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Adicionar Campo - Localização</title>";
echo "</head>";
echo "<body>";

// Adicionar campo localizacao na tabela edificios
$sql = "ALTER TABLE edificios ADD COLUMN localizacao VARCHAR(500) NULL AFTER endereco";
if ($conn->query($sql)) {
    echo "Campo 'localizacao' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'localizacao': " . $conn->error . "<br>";
}

echo "</body>";
echo "</html>";

$conn->close();
?>
