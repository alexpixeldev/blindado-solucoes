<?php
require_once 'conexao.php';

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

$conn->close();
?>
