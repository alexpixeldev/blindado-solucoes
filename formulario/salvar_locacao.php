<?php
require_once '../admin/conexao.php';

// Definir cabeçalho para resposta JSON se for AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Iniciar buffer de saída para capturar avisos/mensagens inesperadas
ob_start();

$debugLogPath = __DIR__ . '/../admin/debug_locacao.log';
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $debugLogPath);

function sendJsonError($message, $debug = null) {
    global $is_ajax;
    if ($is_ajax) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $resp = ['status' => 'error', 'message' => $message];
        if ($debug !== null) {
            $resp['debug'] = substr($debug, 0, 2000);
        }
        echo json_encode($resp);
        exit;
    }
    echo $message;
    exit;
}

set_error_handler(function($severity, $message, $file, $line) use ($debugLogPath) {
    $logEntry = sprintf("[%s] PHP error: %s in %s on line %d\n", date('Y-m-d H:i:s'), $message, $file, $line);
    file_put_contents($debugLogPath, $logEntry, FILE_APPEND | LOCK_EX);
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($exception) use ($debugLogPath, $is_ajax) {
    $logEntry = sprintf("[%s] Uncaught exception: %s in %s on line %d\n", date('Y-m-d H:i:s'), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    file_put_contents($debugLogPath, $logEntry, FILE_APPEND | LOCK_EX);
    if ($is_ajax && !headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Erro inesperado no servidor.']);
    } else {
        echo 'Erro inesperado no servidor.';
    }
    exit;
});

register_shutdown_function(function() use ($debugLogPath, $is_ajax) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR], true)) {
        $logEntry = sprintf("[%s] Shutdown error: %s in %s on line %d\n", date('Y-m-d H:i:s'), $error['message'], $error['file'], $error['line']);
        file_put_contents($debugLogPath, $logEntry, FILE_APPEND | LOCK_EX);
        if ($is_ajax && !headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Erro fatal no servidor ao salvar a locação.']);
        }
    }
});

function ensure_locacoes_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS locacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        edificio_id INT NOT NULL,
        tipo_usuario VARCHAR(50) DEFAULT 'locatario',
        numero_apartamento VARCHAR(20),
        locador_nome VARCHAR(255),
        locador_telefone VARCHAR(50),
        data_entrada DATE,
        data_saida DATE,
        observacoes TEXT,
        data_locacao DATE,
        data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $requiredColumns = [
        'tipo_usuario' => "VARCHAR(50) DEFAULT 'locatario'",
        'locador_nome' => 'VARCHAR(255)',
        'locador_telefone' => 'VARCHAR(50)',
        'data_entrada' => 'DATE',
        'data_saida' => 'DATE',
        'observacoes' => 'TEXT',
        'data_locacao' => 'DATE',
        'data_registro' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];

    foreach ($requiredColumns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM locacoes LIKE '$column'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE locacoes ADD COLUMN $column $definition");
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS locacoes_inquilinos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        locacao_id INT NOT NULL,
        nome VARCHAR(255),
        documento VARCHAR(50),
        telefone VARCHAR(50),
        selfie LONGTEXT,
        FOREIGN KEY (locacao_id) REFERENCES locacoes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $checkSelfie = $conn->query("SHOW COLUMNS FROM locacoes_inquilinos LIKE 'selfie'");
    if ($checkSelfie && $checkSelfie->num_rows == 0) {
        $conn->query("ALTER TABLE locacoes_inquilinos ADD COLUMN selfie LONGTEXT");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS locacoes_veiculos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        locacao_id INT NOT NULL,
        modelo VARCHAR(100),
        cor VARCHAR(50),
        placa VARCHAR(20),
        acesso_garagem VARCHAR(50),
        FOREIGN KEY (locacao_id) REFERENCES locacoes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function assert_prepare($stmt, $conn, $message) {
    if (!$stmt) {
        $error = $conn->error ?: 'unknown error';
        error_log("salvar_locacao.php prepare error ({$message}): $error");
        sendJsonError('Erro interno no servidor ao preparar a consulta: ' . $error, $error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_locacoes_schema($conn);
    if (!$conn->ping()) {
        error_log('salvar_locacao.php: MySQL ping failed before insert');
        sendJsonError('Erro de conexão com o banco de dados. Tente novamente mais tarde.');
    }
    // DEBUG LOG: registrar resumo do POST/FILES para diagnosticar problemas de upload/JSON
    $debugLogPath = __DIR__ . '/../admin/debug_locacao.log';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] Início de request POST\n";
    $logEntry .= "POST keys: " . implode(', ', array_keys($_POST)) . "\n";
    $filesSummary = [];
    foreach ($_FILES as $k => $f) {
        if (is_array($f['name'])) {
            $filesSummary[] = "$k: nested file array";
        } else {
            $filesSummary[] = "$k: name={$f['name']} size={$f['size']} error={$f['error']}";
        }
    }
    $logEntry .= "FILES summary: " . implode(' | ', $filesSummary) . "\n";
    $logEntry .= "post_max_size=" . ini_get('post_max_size') . " upload_max_filesize=" . ini_get('upload_max_filesize') . "\n";
    file_put_contents($debugLogPath, $logEntry, FILE_APPEND | LOCK_EX);
    // Coletar dados básicos
    $edificio_id = $_POST['edificio_id'] ?? null;
    $tipo_usuario = $_POST['user_type'] ?? '';
    $numero_apartamento = $_POST['numero_apartamento'] ?? '';
    $locador_nome = $_POST['locador_nome'] ?? null;
    $locador_ddi = $_POST['locador_ddi'] ?? '';
    $locador_telefone = $_POST['locador_telefone'] ?? null;
    
    // Concatenar DDI e Telefone para salvar no banco se necessário, 
    // ou você pode salvar apenas o telefone se preferir manter a estrutura atual.
    // Aqui vamos concatenar para garantir que o número completo seja salvo.
    if ($locador_telefone) {
        $locador_telefone = $locador_ddi . ' ' . $locador_telefone;
    }

    $data_entrada = $_POST['data_entrada'] ?? null;
    $data_saida = $_POST['data_saida'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';

    // Converter datas de d/m/Y para Y-m-d (formato do banco)
    if (!empty($data_entrada)) {
        $dt = DateTime::createFromFormat('d/m/Y', $data_entrada);
        $data_entrada = $dt ? $dt->format('Y-m-d') : null;
    } else {
        $data_entrada = null;
    }
    
    if (!empty($data_saida)) {
        $dt = DateTime::createFromFormat('d/m/Y', $data_saida);
        $data_saida = $dt ? $dt->format('Y-m-d') : null;
    } else {
        $data_saida = null;
    }

    // Validação básica
    if (!$edificio_id || !$numero_apartamento) {
        if ($is_ajax) {
            echo json_encode(['status' => 'error', 'message' => 'Edifício e Apartamento são obrigatórios.']);
            exit;
        }
        die("Erro: Edifício e Apartamento são obrigatórios.");
    }

    // 1. Inserir na tabela principal (locacoes)
    $data_locacao = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO locacoes (edificio_id, tipo_usuario, numero_apartamento, locador_nome, locador_telefone, data_entrada, data_saida, observacoes, data_locacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    assert_prepare($stmt, $conn, 'locacoes insert');
    $stmt->bind_param("issssssss", $edificio_id, $tipo_usuario, $numero_apartamento, $locador_nome, $locador_telefone, $data_entrada, $data_saida, $observacoes, $data_locacao);
    
    if ($stmt->execute()) {
        $locacao_id = $stmt->insert_id;

        // 2. Inserir Inquilinos
        $files_inquilinos = $_FILES['inquilinos'] ?? null;
        if (isset($_POST['inquilinos']) && is_array($_POST['inquilinos'])) {
            $stmt_inq = $conn->prepare("INSERT INTO locacoes_inquilinos (locacao_id, nome, documento, telefone, selfie) VALUES (?, ?, ?, ?, ?)");
            assert_prepare($stmt_inq, $conn, 'locacoes_inquilinos insert');
            foreach ($_POST['inquilinos'] as $index => $inquilino) {
                if (!empty($inquilino['nome'])) {
                    $tel_inq = $inquilino['telefone'] ?? null;
                    $selfieData = null;

                    if ($files_inquilinos && isset($files_inquilinos['tmp_name'][$index]['selfie']) && $files_inquilinos['error'][$index]['selfie'] === UPLOAD_ERR_OK) {
                        $tmpName = $files_inquilinos['tmp_name'][$index]['selfie'];
                        $mimeType = mime_content_type($tmpName) ?: $files_inquilinos['type'][$index]['selfie'];
                        $allowedTypes = [
                            'image/jpeg' => '.jpg',
                            'image/png' => '.png',
                            'image/webp' => '.webp',
                            'image/gif' => '.gif'
                        ];

                        if (isset($allowedTypes[$mimeType])) {
                            $uploadDir = __DIR__ . '/../uploads/selfies_inquilinos';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            $extension = $allowedTypes[$mimeType];
                            $fileName = 'selfie_' . time() . '_' . uniqid() . $extension;
                            $targetPath = $uploadDir . '/' . $fileName;

                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $selfieData = '../uploads/selfies_inquilinos/' . $fileName;
                            } else {
                                error_log("Falha ao mover selfie para $targetPath");
                            }
                        } else {
                            error_log("Tipo de arquivo selfie não permitido: $mimeType");
                        }
                    }

                    $stmt_inq->bind_param("issss", $locacao_id, $inquilino['nome'], $inquilino['documento'], $tel_inq, $selfieData);
                    if (!$stmt_inq->execute()) {
                        $queryError = $stmt_inq->error ?: $conn->error;
                        error_log("salvar_locacao.php locacoes_inquilinos execute error: $queryError");
                        if ($is_ajax) {
                            $buffer = ob_get_clean();
                            $resp = ['status' => 'error', 'message' => 'Erro ao salvar hóspede: ' . $queryError];
                            if (!empty($buffer)) {
                                $resp['debug'] = substr($buffer, 0, 2000);
                            }
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode($resp);
                            exit;
                        } else {
                            throw new Exception('Erro ao salvar hóspede: ' . $queryError);
                        }
                    }
                }
            }
        }

        // 3. Inserir Veículos
        if (isset($_POST['veiculos']) && is_array($_POST['veiculos'])) {
            $stmt_vei = $conn->prepare("INSERT INTO locacoes_veiculos (locacao_id, modelo, cor, placa, acesso_garagem) VALUES (?, ?, ?, ?, ?)");
            assert_prepare($stmt_vei, $conn, 'locacoes_veiculos insert');
            foreach ($_POST['veiculos'] as $veiculo) {
                if (!empty($veiculo['modelo'])) {
                    $stmt_vei->bind_param("issss", $locacao_id, $veiculo['modelo'], $veiculo['cor'], $veiculo['placa'], $veiculo['acesso_garagem']);
                    if (!$stmt_vei->execute()) {
                        error_log("salvar_locacao.php locacoes_veiculos execute error: " . $stmt_vei->error);
                    }
                }
            }
        }

        if ($is_ajax) {
            $buffer = ob_get_clean();
            $resp = ['status' => 'success', 'locacao_id' => $locacao_id];
            if (!empty($buffer)) {
                $resp['debug'] = substr($buffer, 0, 2000);
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resp);
        } else {
            // limpar buffer antes do redirect
            ob_end_clean();
            header("Location: sucesso.php");
        }
    } else {
        if ($is_ajax) {
            $buffer = ob_get_clean();
            $resp = ['status' => 'error', 'message' => 'Erro ao salvar no banco de dados: ' . $conn->error];
            if (!empty($buffer)) {
                $resp['debug'] = substr($buffer, 0, 2000);
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resp);
        } else {
            $buffer = ob_get_clean();
            if (!empty($buffer)) echo $buffer;
            echo "Erro ao salvar: " . $conn->error;
        }
    }
}
?>
