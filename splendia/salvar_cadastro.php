<?php
require_once '../blindadosync/conexao.php';

header('Content-Type: application/json; charset=utf-8');

function sendError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Método não permitido.');
}

$edificio_id = 61; // Edifício Splendia

$apartamento = trim($_POST['apartamento'] ?? '');
if ($apartamento === '') {
    sendError('Informe o número do apartamento.');
}

$pessoas = $_POST['pessoas'] ?? [];
if (!is_array($pessoas) || count($pessoas) === 0) {
    sendError('Adicione pelo menos um morador.');
}

$pessoasValidas = [];
foreach ($pessoas as $p) {
    $nome = trim($p['nome'] ?? '');
    if ($nome === '') continue;
    $documento = trim($p['documento'] ?? '');
    $locatario_anual = isset($p['locatario_anual']) && $p['locatario_anual'] == 1 ? 1 : 0;
    $pessoasValidas[] = ['nome' => $nome, 'documento' => $documento, 'locatario_anual' => $locatario_anual];
}

if (count($pessoasValidas) === 0) {
    sendError('Preencha o nome de pelo menos um morador.');
}

// Garantir coluna locatario_anual (por segurança)
$check = $conn->query("SHOW COLUMNS FROM locacoes_inquilinos LIKE 'locatario_anual'");
if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE locacoes_inquilinos ADD COLUMN locatario_anual TINYINT(1) NOT NULL DEFAULT 0 AFTER documento");
}

$data_locacao = date('Y-m-d');

// 1. Inserir na tabela principal (locacoes)
$stmt = $conn->prepare("INSERT INTO locacoes (edificio_id, tipo_usuario, numero_apartamento, data_locacao) VALUES (?, ?, ?, ?)");
$tipo_usuario = 'proprietario';
$stmt->bind_param('isss', $edificio_id, $tipo_usuario, $apartamento, $data_locacao);
if (!$stmt->execute()) {
    sendError('Erro ao salvar: ' . $conn->error);
}
$locacao_id = $stmt->insert_id;
$stmt->close();

// 2. Inserir moradores
$stmt = $conn->prepare("INSERT INTO locacoes_inquilinos (locacao_id, nome, documento, locatario_anual) VALUES (?, ?, ?, ?)");
foreach ($pessoasValidas as $p) {
    $stmt->bind_param('issi', $locacao_id, $p['nome'], $p['documento'], $p['locatario_anual']);
    if (!$stmt->execute()) {
        sendError('Erro ao salvar morador: ' . $conn->error);
    }
}
$stmt->close();

echo json_encode(['status' => 'success', 'locacao_id' => $locacao_id]);