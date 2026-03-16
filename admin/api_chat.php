<?php
/**
 * API do Chat Interno - VERSÃO CORRIGIDA E TESTADA
 * Gerencia todas as operações de chat: envio, recebimento, edição, exclusão e upload de arquivos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'verifica_login.php';
require_once 'conexao.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

// Rejeitar colaboradores do chat
if ($usuario_categoria === 'colaborador') {
    http_response_code(403);
    echo json_encode(['erro' => 'Colaboradores não têm acesso ao chat']);
    exit;
}

// Diretório para armazenar arquivos do chat
$upload_dir = '../uploads/chat/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'enviar_mensagem':
        enviar_mensagem();
        break;
    case 'obter_mensagens':
        obter_mensagens();
        break;
    case 'obter_usuarios':
        obter_usuarios();
        break;
    case 'obter_status_usuarios':
        obter_status_usuarios();
        break;
    case 'atualizar_status':
        atualizar_status();
        break;
    case 'editar_mensagem':
        editar_mensagem();
        break;
    case 'deletar_mensagem':
        deletar_mensagem();
        break;
    case 'upload_arquivo':
        upload_arquivo();
        break;
    case 'marcar_como_lida':
        marcar_como_lida();
        break;
    case 'buscar_mensagens':
        buscar_mensagens();
        break;

    default:
        http_response_code(400);
        echo json_encode(['erro' => 'Ação não reconhecida']);
        break;
}

/**
 * Enviar uma nova mensagem
 */
function enviar_mensagem() {
    global $conn, $usuario_id, $upload_dir;
    
    $destinatario_id = intval($_POST['destinatario_id'] ?? 0);
    $mensagem = trim($_POST['mensagem'] ?? '');
    $arquivo_caminho = null;
    $arquivo_tipo = null;
    $arquivo_nome_original = null;
    
    if ($destinatario_id <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'Destinatário inválido']);
        exit;
    }
    
    // Verificar se o destinatário existe e não é colaborador
    $stmt = $conn->prepare("SELECT categoria FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $destinatario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['erro' => 'Destinatário não encontrado']);
        exit;
    }
    
    $destinatario = $result->fetch_assoc();
    if ($destinatario['categoria'] === 'colaborador') {
        http_response_code(403);
        echo json_encode(['erro' => 'Não é possível enviar mensagens para colaboradores']);
        exit;
    }
    
    // Processar arquivo se houver
    if (isset($_FILES['arquivo'])) {
        $file = $_FILES['arquivo'];
        $arquivo_nome_original = $file['name'];
        
        // Determinar o tipo de arquivo
        $extensao = strtolower(pathinfo($arquivo_nome_original, PATHINFO_EXTENSION));
        $tipos_permitidos = [
            'imagem' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'avi', 'mov', 'mkv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'webm'],
            'documento' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']
        ];
        
        $arquivo_tipo = 'documento';
        foreach ($tipos_permitidos as $tipo => $extensoes) {
            if (in_array($extensao, $extensoes)) {
                $arquivo_tipo = $tipo;
                break;
            }
        }
        
        // Validar tamanho (máximo 50MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['erro' => 'Arquivo muito grande (máximo 50MB)']);
            exit;
        }
        
        // Gerar nome único para o arquivo
        $nome_arquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $arquivo_nome_original);
        $caminho_arquivo = $upload_dir . $nome_arquivo;
        
        if (!move_uploaded_file($file['tmp_name'], $caminho_arquivo)) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao fazer upload do arquivo']);
            exit;
        }
        
        // Se for áudio, garantir que o arquivo foi salvo corretamente e tem tamanho
        if ($arquivo_tipo === 'audio' && filesize($caminho_arquivo) === 0) {
            unlink($caminho_arquivo);
            http_response_code(500);
            echo json_encode(['erro' => 'Arquivo de áudio vazio recebido']);
            exit;
        }
        
        $arquivo_caminho = 'uploads/chat/' . $nome_arquivo;
    }
    
    // Validar se há mensagem de texto ou arquivo
    if (empty($mensagem) && empty($arquivo_caminho)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Mensagem vazia e sem arquivo']);
        exit;
    }
    
    // Inserir mensagem no banco de dados
    $stmt = $conn->prepare("INSERT INTO chat_mensagens (remetente_id, destinatario_id, mensagem, arquivo_caminho, arquivo_tipo, arquivo_nome_original, data_envio, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'enviada')");
    $stmt->bind_param("iissss", $usuario_id, $destinatario_id, $mensagem, $arquivo_caminho, $arquivo_tipo, $arquivo_nome_original);
    
    if ($stmt->execute()) {
        $mensagem_id = $conn->insert_id;
        echo json_encode([
            'sucesso' => true,
            'mensagem_id' => $mensagem_id,
            'mensagem' => 'Mensagem enviada com sucesso'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao enviar mensagem: ' . $conn->error]);
    }
    exit;
}

/**
 * Obter mensagens entre dois usuários
 */
function obter_mensagens() {
    global $conn, $usuario_id;
    
    $outro_usuario_id = intval($_GET['outro_usuario_id'] ?? 0);
    
    if ($outro_usuario_id <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'Outro usuário inválido']);
        exit;
    }
    
    $sql = "SELECT 
                m.id,
                m.remetente_id,
                m.destinatario_id,
                m.mensagem,
                m.arquivo_caminho,
                m.arquivo_tipo,
                m.arquivo_nome_original,
                m.data_envio,
                m.data_edicao,
                m.status,
                m.lida,
                u.nome as remetente_nome
            FROM chat_mensagens m
            JOIN usuarios u ON m.remetente_id = u.id
            WHERE 
                (m.remetente_id = ? AND m.destinatario_id = ?)
                OR
                (m.remetente_id = ? AND m.destinatario_id = ?)
            ORDER BY m.data_envio ASC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $usuario_id, $outro_usuario_id, $outro_usuario_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao buscar mensagens']);
        exit;
    }
    
    $mensagens = [];
    while ($row = $result->fetch_assoc()) {
        $mensagens[] = $row;
    }
    
    echo json_encode(['mensagens' => $mensagens]);
    exit;
}

/**
 * Obter lista de usuários (exceto colaboradores e o próprio usuário)
 */
function obter_usuarios() {
    global $conn, $usuario_id;
    
    $sql = "SELECT 
                u.id,
                u.nome,
                u.nome_real,
                u.categoria,
                u.status_chat,
                u.ultimo_acesso,
                (SELECT COUNT(*) FROM chat_mensagens WHERE remetente_id = u.id AND destinatario_id = ? AND lida = 0) as nao_lidas
            FROM usuarios u
            WHERE 
                u.id != ? 
                AND u.categoria IN ('operador', 'supervisor', 'administrativo', 'gerente')
            ORDER BY u.nome ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao buscar usuários']);
        exit;
    }
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    
    echo json_encode(['usuarios' => $usuarios]);
    exit;
}

/**
 * Obter status dos usuários e conversas recentes
 */
function obter_status_usuarios() {
    global $conn, $usuario_id;
    
    // 1. Obter todos os usuários permitidos para o chat
    $sql_users = "SELECT 
                    u.id,
                    u.nome,
                    u.categoria,
                    u.status_chat,
                    u.ultimo_acesso,
                    (SELECT COUNT(*) FROM chat_mensagens WHERE remetente_id = u.id AND destinatario_id = ? AND lida = 0) as nao_lidas
                FROM usuarios u
                WHERE u.id != ? 
                    AND u.categoria IN ('operador', 'supervisor', 'administrativo', 'gerente')
                ORDER BY u.nome ASC";
    
    $stmt_users = $conn->prepare($sql_users);
    $stmt_users->bind_param("ii", $usuario_id, $usuario_id);
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    
    $usuarios = [];
    while ($row = $result_users->fetch_assoc()) {
        $ultimo_acesso = strtotime($row['ultimo_acesso']);
        $agora = time();
        if (abs($agora - $ultimo_acesso) > 25) {
            $row['status_chat'] = 'offline';
        } else {
            $row['status_chat'] = 'online';
        }
        $usuarios[] = $row;
    }
    
    // 2. Obter conversas recentes (histórico de interações)
    // Esta query busca o último contato com cada usuário, seja como remetente ou destinatário
    $sql_recentes = "SELECT 
                        u.id,
                        u.nome,
                        u.status_chat,
                        u.ultimo_acesso,
                        MAX(m.data_envio) as data_ultima,
                        (SELECT mensagem FROM chat_mensagens WHERE (remetente_id = u.id AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = u.id) ORDER BY data_envio DESC LIMIT 1) as ultima_msg,
                        (SELECT COUNT(*) FROM chat_mensagens WHERE remetente_id = u.id AND destinatario_id = ? AND lida = 0) as nao_lidas
                    FROM chat_mensagens m
                    JOIN usuarios u ON (m.remetente_id = u.id OR m.destinatario_id = u.id)
                    WHERE (m.remetente_id = ? OR m.destinatario_id = ?)
                        AND u.id != ?
                        AND u.categoria IN ('operador', 'supervisor', 'administrativo', 'gerente')
                    GROUP BY u.id
                    ORDER BY data_ultima DESC";

    $stmt_recentes = $conn->prepare($sql_recentes);
    $stmt_recentes->bind_param("iiiiii", $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id);
    $stmt_recentes->execute();
    $result_recentes = $stmt_recentes->get_result();

    $conversas_recentes = [];
    while ($row_recentes = $result_recentes->fetch_assoc()) {
        $ultimo_acesso = strtotime($row_recentes['ultimo_acesso']);
        $agora = time();
        if (abs($agora - $ultimo_acesso) > 25) {
            $row_recentes['status_chat'] = 'offline';
        } else {
            $row_recentes['status_chat'] = 'online';
        }
        $conversas_recentes[] = $row_recentes;
    }
    
    echo json_encode([
        'usuarios' => $usuarios,
        'conversas_recentes' => $conversas_recentes
    ]);
    exit;
}

/**
 * Atualizar status do usuário no chat
 */
function atualizar_status() {
    global $conn, $usuario_id;
    
    $status = trim($_POST['status'] ?? 'online'); // 'online', 'ausente', 'ocupado'
    
    $stmt = $conn->prepare("UPDATE usuarios SET status_chat = ?, ultimo_acesso = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $usuario_id);
    
    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'status' => $status]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao atualizar status: ' . $conn->error]);
    }
    exit;
}

/**
 * Editar uma mensagem
 */
function editar_mensagem() {
    global $conn, $usuario_id;
    
    $mensagem_id = intval($_POST['mensagem_id'] ?? 0);
    $nova_mensagem = trim($_POST['nova_mensagem'] ?? '');
    
    if ($mensagem_id <= 0 || empty($nova_mensagem)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados inválidos para edição']);
        exit;
    }
    
    // Verificar se o usuário é o remetente da mensagem
    $stmt = $conn->prepare("SELECT remetente_id FROM chat_mensagens WHERE id = ?");
    $stmt->bind_param("i", $mensagem_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mensagem_info = $result->fetch_assoc();
    
    if (!$mensagem_info || $mensagem_info['remetente_id'] != $usuario_id) {
        http_response_code(403);
        echo json_encode(['erro' => 'Você não tem permissão para editar esta mensagem']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE chat_mensagens SET mensagem = ?, data_edicao = NOW() WHERE id = ?");
    $stmt->bind_param("si", $nova_mensagem, $mensagem_id);
    
    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem editada com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao editar mensagem: ' . $conn->error]);
    }
    exit;
}

/**
 * Deletar uma mensagem
 */
function deletar_mensagem() {
    global $conn, $usuario_id;
    
    $mensagem_id = intval($_POST['mensagem_id'] ?? 0);
    
    if ($mensagem_id <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID da mensagem inválido']);
        exit;
    }
    
    // Verificar se o usuário é o remetente da mensagem
    $stmt = $conn->prepare("SELECT remetente_id FROM chat_mensagens WHERE id = ?");
    $stmt->bind_param("i", $mensagem_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mensagem_info = $result->fetch_assoc();
    
    if (!$mensagem_info || $mensagem_info['remetente_id'] != $usuario_id) {
        http_response_code(403);
        echo json_encode(['erro' => 'Você não tem permissão para deletar esta mensagem']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM chat_mensagens WHERE id = ?");
    $stmt->bind_param("i", $mensagem_id);
    
    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem deletada com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao deletar mensagem: ' . $conn->error]);
    }
    exit;
}

/**
 * Upload de arquivo no chat
 */
function upload_arquivo() {
    global $conn, $usuario_id, $upload_dir;

    $destinatario_id = intval($_POST['destinatario_id'] ?? 0);
    if ($destinatario_id <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'Destinatário inválido']);
        exit;
    }

    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['erro' => 'Nenhum arquivo enviado ou erro no upload.']);
        exit;
    }

    $file = $_FILES['arquivo'];
    $arquivo_nome_original = $file['name'];
    $extensao = strtolower(pathinfo($arquivo_nome_original, PATHINFO_EXTENSION));

    $tipos_permitidos = [
        'imagem' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'avi', 'mov', 'mkv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'webm'],
        'documento' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']
    ];

    $allowed_extensions = [];
    foreach ($tipos_permitidos as $exts) {
        $allowed_extensions = array_merge($allowed_extensions, $exts);
    }

    $upload_result = validate_upload($file, $allowed_extensions, 50 * 1024 * 1024); // 50MB

    if (!$upload_result['success']) {
        http_response_code(400);
        echo json_encode(['erro' => $upload_result['error']]);
        exit;
    }

    $arquivo_tipo = 'documento'; // Default
    foreach ($tipos_permitidos as $tipo => $exts) {
        if (in_array($extensao, $exts)) {
            $arquivo_tipo = $tipo;
            break;
        }
    }

    $nome_arquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $arquivo_nome_original);
    $caminho_arquivo_destino = $upload_dir . $nome_arquivo;

    if (!move_uploaded_file($file['tmp_name'], $caminho_arquivo_destino)) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao mover o arquivo para o diretório de uploads.']);
        exit;
    }

    $arquivo_caminho_db = 'uploads/chat/' . $nome_arquivo;

    $stmt = $conn->prepare("INSERT INTO chat_mensagens (remetente_id, destinatario_id, mensagem, arquivo_caminho, arquivo_tipo, arquivo_nome_original, data_envio, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'enviada')");
    $mensagem_vazia = ''; // Mensagem vazia para upload de arquivo
    $stmt->bind_param("iissss", $usuario_id, $destinatario_id, $mensagem_vazia, $arquivo_caminho_db, $arquivo_tipo, $arquivo_nome_original);

    if ($stmt->execute()) {
        $mensagem_id = $conn->insert_id;
        echo json_encode([
            'sucesso' => true,
            'mensagem_id' => $mensagem_id,
            'mensagem' => 'Arquivo enviado com sucesso',
            'arquivo_caminho' => $arquivo_caminho_db,
            'arquivo_tipo' => $arquivo_tipo,
            'arquivo_nome_original' => $arquivo_nome_original
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao registrar arquivo no banco de dados: ' . $conn->error]);
    }
    exit;
}

/**
 * Marcar mensagem como lida
 */
function marcar_como_lida() {
    global $conn, $usuario_id;

    $outro_usuario_id = intval($_POST['outro_usuario_id'] ?? 0);

    if ($outro_usuario_id <= 0) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do usuário inválido']);
        exit;
    }

    // Marcar como lida todas as mensagens onde o usuário logado é o destinatário e o outro é o remetente
    $stmt = $conn->prepare("UPDATE chat_mensagens SET lida = 1 WHERE destinatario_id = ? AND remetente_id = ? AND lida = 0");
    $stmt->bind_param("ii", $usuario_id, $outro_usuario_id);

    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagens marcadas como lidas']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao marcar mensagens como lidas: ' . $conn->error]);
    }
    exit;
}

/**
 * Buscar mensagens
 */
function buscar_mensagens() {
    global $conn, $usuario_id;

    $termo = trim($_GET['termo'] ?? '');
    $outro_usuario_id = intval($_GET['outro_usuario_id'] ?? 0);

    if (empty($termo)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Termo de busca vazio']);
        exit;
    }

    $termo_like = '%' . $termo . '%';

    $sql = "SELECT 
                m.id,
                m.remetente_id,
                m.destinatario_id,
                m.mensagem,
                m.arquivo_caminho,
                m.arquivo_tipo,
                m.arquivo_nome_original,
                m.data_envio,
                m.data_edicao,
                m.status,
                m.lida,
                u.nome as remetente_nome
            FROM chat_mensagens m
            JOIN usuarios u ON m.remetente_id = u.id
            WHERE 
                ((m.remetente_id = ? AND m.destinatario_id = ?)
                OR
                (m.remetente_id = ? AND m.destinatario_id = ?))
                AND m.mensagem LIKE ?
            ORDER BY m.data_envio DESC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiis", $usuario_id, $outro_usuario_id, $outro_usuario_id, $usuario_id, $termo_like);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao buscar mensagens: ' . $conn->error]);
        exit;
    }

    $mensagens = [];
    while ($row = $result->fetch_assoc()) {
        $mensagens[] = $row;
    }

    echo json_encode(['mensagens' => $mensagens]);
    exit;
}

?>
