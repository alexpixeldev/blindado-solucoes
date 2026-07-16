<?php
require_once 'conexao.php';

// Adicionar campo localizacao na tabela edificios
$sql = "ALTER TABLE edificios ADD COLUMN localizacao VARCHAR(500) NULL AFTER endereco";
if ($conn->query($sql)) {
    echo "Campo 'localizacao' adicionado com sucesso!<br>";
} else {
    echo "Erro ao adicionar campo 'localizacao': " . $conn->error . "<br>";
}

$conn->close();
?>
