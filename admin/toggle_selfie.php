<?php
require_once 'conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edificio_id = $_POST['edificio_id'] ?? 0;
    $valor = $_POST['valor'] ?? 0;
    
    if (!$edificio_id) {
        echo json_encode(['success' => false, 'message' => 'ID do edifício não fornecido']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE edificios SET requer_selfie = ? WHERE id = ?");
    $stmt->bind_param("ii", $valor, $edificio_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}

$conn->close();
?>
