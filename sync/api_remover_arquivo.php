<?php
require_once 'verifica_login.php';
header('Content-Type: application/json');

$upload_dir = realpath(__DIR__ . '/../uploads/ocorrencias/');
if (!$upload_dir) {
    echo json_encode(['success' => 0, 'message' => 'Diretório de upload não encontrado.']);
    exit;
}

$url = $_POST['url'] ?? $_GET['url'] ?? '';

// Extrai apenas o nome do arquivo dentro de uploads/ocorrencias/
if (preg_match('#uploads/ocorrencias/([^/"\'\\s<>]+)$#i', $url, $m)) {
    $filename = $m[1];
} else {
    $filename = basename($url);
}

if (empty($filename) || $filename === '.' || $filename === '..') {
    echo json_encode(['success' => 0, 'message' => 'Arquivo não informado.']);
    exit;
}

$arquivo = $upload_dir . DIRECTORY_SEPARATOR . $filename;

if (!is_file($arquivo)) {
    echo json_encode(['success' => 0, 'message' => 'Arquivo não encontrado.']);
    exit;
}

if (unlink($arquivo)) {
    echo json_encode(['success' => 1, 'message' => 'Arquivo removido.']);
} else {
    echo json_encode(['success' => 0, 'message' => 'Falha ao remover o arquivo.']);
}
?>
