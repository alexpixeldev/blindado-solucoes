<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

header('Content-Type: application/json');

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['supervisor', 'gerente'])) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para alterar esta configuração']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edificio_id = $_POST['edificio_id'] ?? 0;
    $valor = $_POST['valor'] ?? 0;

    if (!$edificio_id) {
        echo json_encode(['success' => false, 'message' => 'ID do edifício não fornecido']);
        exit;
    }

    $check = @$conn->query("SHOW COLUMNS FROM edificios LIKE 'enviar_email_adm'");
    if (!$check || $check->num_rows == 0) {
        @$conn->query("ALTER TABLE edificios ADD COLUMN enviar_email_adm TINYINT(1) DEFAULT 0 AFTER retirada_lixo");
    }

    $stmt = $conn->prepare("UPDATE edificios SET enviar_email_adm = ? WHERE id = ?");
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