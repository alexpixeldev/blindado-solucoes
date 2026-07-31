<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if ($_SESSION['usuario_categoria'] !== 'gerente') {
    die("Apenas gerente pode executar esta migration.");
}

$check = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'base_id'");
if ($check && $check->num_rows > 0) {
    echo "Coluna base_id já existe.";
} else {
    $conn->query("ALTER TABLE usuarios ADD COLUMN base_id INT NULL DEFAULT NULL AFTER categoria");
    if ($conn->error) {
        echo "Erro: " . $conn->error;
    } else {
        echo "Coluna base_id adicionada com sucesso!";
    }
}
$conn->close();
