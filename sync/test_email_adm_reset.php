<?php
require_once __DIR__ . '/conexao.php';
$conn->query("UPDATE edificios SET enviar_email_adm = 0 WHERE id = 1");
echo "reset ok\n";
$conn->close();