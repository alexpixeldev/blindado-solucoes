<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../sync/conexao.php';

$has_selfie_col = false;
$check = @$conn->query("SHOW COLUMNS FROM edificios LIKE 'requer_selfie'");
if ($check && $check->num_rows > 0) $has_selfie_col = true;

$selfie_field = $has_selfie_col ? 'e.requer_selfie,' : '0 AS requer_selfie,';

$result = @$conn->query("
    SELECT e.id, e.nome AS nome_edificio, e.localizacao, $selfie_field b.nome AS nome_base, b.telefone
    FROM edificios e
    JOIN bases b ON e.base_id = b.id
    WHERE b.status = 'ativo'
    ORDER BY e.nome ASC
");

$edificios = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

echo json_encode($edificios, JSON_UNESCAPED_UNICODE);
