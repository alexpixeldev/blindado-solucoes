<?php
// Evitar qualquer output antes do JSON
ob_clean();
require_once 'verifica_login.php';
header('Content-Type: application/json');

// Função para retornar JSON e sair
function return_json($data) {
    echo json_encode($data);
    exit;
}

// Tentar aumentar limites para documentos
@ini_set('upload_max_filesize', '50M');
@ini_set('post_max_size', '50M');
@ini_set('max_execution_time', '300');

$upload_dir = '../uploads/colaboradores/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Verificar se é uma ação de remoção
if (isset($_POST['action']) && $_POST['action'] === 'remover') {
    $tipo = $_POST['tipo'] ?? '';
    $arquivo = $_POST['id'] ?? '';
    
    if (empty($tipo) || empty($arquivo)) {
        return_json(['success' => 0, 'message' => 'Parâmetros inválidos.']);
    }
    
    try {
        // Buscar o nome do arquivo no banco
        $stmt = $conn->prepare("SELECT $tipo FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $arquivo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row && !empty($row[$tipo])) {
            $arquivo_path = $upload_dir . $row[$tipo];
            
            // Remover arquivo do servidor
            if (file_exists($arquivo_path)) {
                unlink($arquivo_path);
            }
            
            // Limpar campo no banco
            $stmt = $conn->prepare("UPDATE usuarios SET $tipo = NULL WHERE id = ?");
            $stmt->bind_param("si", $tipo, $arquivo);
            $stmt->execute();
            $stmt->close();
            
            return_json(['success' => 1, 'message' => 'Arquivo removido com sucesso!']);
        } else {
            return_json(['success' => 0, 'message' => 'Arquivo não encontrado.']);
        }
    } catch (Exception $e) {
        return_json(['success' => 0, 'message' => 'Erro ao remover arquivo: ' . $e->getMessage()]);
    }
}

$file_field = isset($_FILES['file']) ? 'file' : (isset($_FILES['image']) ? 'image' : null);

if ($file_field && isset($_FILES[$file_field])) {
    $file = $_FILES[$file_field];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return_json(['success' => 0, 'message' => 'Erro no upload: ' . $file['error']]);
    }
    
    $temp = $file['tmp_name'];
    $name = $file['name'];
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    // Apenas imagens e PDFs para documentos de colaborador
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    
    $new_filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $name);
    $target_file = $upload_dir . $new_filename;
    
    if (in_array($extension, $allowed_extensions)) {
        if (move_uploaded_file($temp, $target_file)) {
            $file_url = '../uploads/colaboradores/' . $new_filename;
            
            return_json([
                'success' => 1,
                'message' => 'Arquivo enviado com sucesso!',
                'file' => [
                    'url' => $file_url,
                    'name' => $new_filename,
                    'type' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'document'
                ]
            ]);
        } else {
            return_json(['success' => 0, 'message' => 'Falha ao salvar o arquivo no servidor.']);
        }
    } else {
        return_json(['success' => 0, 'message' => 'Formato de arquivo não permitido. Use: JPG, PNG, GIF, WebP ou PDF']);
    }
} else {
    return_json(['success' => 0, 'message' => 'Nenhum arquivo enviado.']);
}
?>
