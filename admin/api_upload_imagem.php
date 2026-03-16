<?php
require_once 'verifica_login.php';
header('Content-Type: application/json');

// Tentar aumentar limites para vídeos pesados
@ini_set('upload_max_filesize', '100M');
@ini_set('post_max_size', '100M');
@ini_set('max_execution_time', '300');

$upload_dir = '../uploads/ocorrencias/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file_field = isset($_FILES['file']) ? 'file' : (isset($_FILES['image']) ? 'image' : null);

if ($file_field && isset($_FILES[$file_field])) {
    $file = $_FILES[$file_field];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => 0, 'message' => 'Erro no upload: ' . $file['error']]);
        exit;
    }

    $temp = $file['tmp_name'];
    $name = $file['name'];
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $name);
    $target_file = $upload_dir . $new_filename;

    // Extensões permitidas (Imagens e Vídeos)
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_videos = ['mp4', 'webm', 'ogg', 'mov'];
    
    if (in_array($extension, array_merge($allowed_images, $allowed_videos))) {
        if (move_uploaded_file($temp, $target_file)) {
            $file_url = '../uploads/ocorrencias/' . $new_filename;
            
            // Retorno compatível com TinyMCE e outros editores
            echo json_encode([
                'location' => $file_url,
                'success' => 1,
                'file' => [
                    'url' => $file_url,
                    'type' => in_array($extension, $allowed_videos) ? 'video' : 'image'
                ]
            ]);
        } else {
            echo json_encode(['success' => 0, 'message' => 'Falha ao salvar o arquivo no servidor.']);
        }
    } else {
        echo json_encode(['success' => 0, 'message' => 'Formato de arquivo não permitido: ' . $extension]);
    }
} else {
    echo json_encode(['success' => 0, 'message' => 'Nenhum arquivo recebido.']);
}
?>
