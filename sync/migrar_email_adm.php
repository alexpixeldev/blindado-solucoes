<?php
require_once __DIR__ . '/conexao.php';

$check = $conn->query("SHOW COLUMNS FROM edificios LIKE 'enviar_email_adm'");
if (!$check || $check->num_rows == 0) {
    if ($conn->query("ALTER TABLE edificios ADD COLUMN enviar_email_adm TINYINT(1) DEFAULT 0 AFTER retirada_lixo")) {
        echo "OK: coluna enviar_email_adm adicionada.\n";
    } else {
        echo "ERRO: " . $conn->error . "\n";
    }
} else {
    echo "OK: coluna enviar_email_adm já existe.\n";
}

// Confirmar estrutura
$show = $conn->query("SHOW COLUMNS FROM edificios LIKE 'enviar_email_adm'");
if ($show) {
    while ($row = $show->fetch_assoc()) {
        echo implode(' | ', $row) . "\n";
    }
}
$conn->close();