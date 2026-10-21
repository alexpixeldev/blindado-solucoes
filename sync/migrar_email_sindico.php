<?php
require_once __DIR__ . '/conexao.php';

$check = $conn->query("SHOW COLUMNS FROM edificios LIKE 'enviar_email_sindico'");
if (!$check || $check->num_rows == 0) {
    if ($conn->query("ALTER TABLE edificios ADD COLUMN enviar_email_sindico TINYINT(1) DEFAULT 0 AFTER enviar_email_adm")) {
        echo "OK: coluna enviar_email_sindico adicionada.\n";
    } else {
        echo "ERRO: " . $conn->error . "\n";
    }
} else {
    echo "OK: coluna enviar_email_sindico já existe.\n";
}

// Confirmar estrutura
$show = $conn->query("SHOW COLUMNS FROM edificios LIKE 'enviar_email_sindico'");
if ($show) {
    while ($row = $show->fetch_assoc()) {
        echo implode(' | ', $row) . "\n";
    }
}
$conn->close();
