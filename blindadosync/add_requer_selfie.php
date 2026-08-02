<?php
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Adicionar Campo - Requer Selfie</title>";
echo "</head>";
echo "<body>";

// Adicionar campo requer_selfie na tabela edificios
$sql = "ALTER TABLE edificios ADD COLUMN requer_selfie TINYINT(1) DEFAULT 0 AFTER elevador_contato";
if ($conn->query($sql)) {
    echo "Campo 'requer_selfie' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'requer_selfie': " . $conn->error . "<br>";
}

// Atualizar os edifícios que atualmente requerem selfie (guy vartam, panoramic, atobá)
$edificiosParaAtualizar = ['guy vartam', 'panoramic', 'atobá'];
foreach ($edificiosParaAtualizar as $nome) {
    $stmt = $conn->prepare("UPDATE edificios SET requer_selfie = 1 WHERE nome = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $stmt->close();
    echo "Edifício '$nome' atualizado para requerer selfie.<br>";
}

echo "</body>";
echo "</html>";

$conn->close();
?>
