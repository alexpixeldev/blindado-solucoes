<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

if ($q === '') {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$check = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'base_id'");
$hasBaseId = $check && $check->num_rows > 0;
$baseIdField = $hasBaseId ? ', base_id' : '';
$term = '%' . $q . '%';

$sql = "SELECT id, nome, nome_real$baseIdField FROM usuarios WHERE (nome_real LIKE ? OR nome LIKE ?)";
$params = [$term, $term];
$types = 'ss';

if ($categoria !== '') {
    $cats = array_map('trim', explode(',', $categoria));
    if (count($cats) === 1) {
        $sql .= " AND categoria = ?";
        $params[] = $cats[0];
        $types .= 's';
    } else {
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $sql .= " AND categoria IN ($placeholders)";
        $params = array_merge($params, $cats);
        $types .= str_repeat('s', count($cats));
    }
}

$sql .= " ORDER BY nome_real ASC LIMIT 15";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = [
        'id' => $row['id'],
        'label' => $row['nome_real'] ?: $row['nome'],
        'base_id' => $hasBaseId ? ($row['base_id'] ?? null) : null
    ];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($out);
