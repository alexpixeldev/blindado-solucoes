<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if ($_SESSION['usuario_categoria'] !== 'gerente') {
    die("Apenas gerente pode executar esta migration.");
}

$check = $conn->query("SHOW COLUMNS FROM ocorrencias LIKE 'locais_ids'");
if ($check && $check->num_rows > 0) {
    echo "Coluna locais_ids já existe.";
} else {
    $conn->query("ALTER TABLE ocorrencias ADD COLUMN locais_ids VARCHAR(500) NULL DEFAULT NULL AFTER base_id");
    if ($conn->error) {
        echo "Erro ao adicionar coluna: " . $conn->error;
        $conn->close();
        exit;
    }
    echo "Coluna locais_ids adicionada com sucesso!<br>";
}

$conn->query("UPDATE ocorrencias SET locais_ids = CONCAT('e_', edificio_id) WHERE edificio_id IS NOT NULL AND (locais_ids IS NULL OR locais_ids = '')");
$conn->query("UPDATE ocorrencias SET locais_ids = CONCAT('b_', base_id) WHERE base_id IS NOT NULL AND (locais_ids IS NULL OR locais_ids = '')");
echo "Registros existentes preenchidos.";
$conn->close();
