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

    // Se não tiver extensão, tenta detectar pelo tipo MIME
    if (empty($extension)) {
        $finfo = finfo_open(FILEINFO_EXTENSION);
        $extension = finfo_file($finfo, $temp, FILEINFO_EXTENSION);
        finfo_close($finfo);
    }

    // Se ainda não tiver extensão, usa uma padrão
    if (empty($extension)) {
        $extension = 'bin';
    }

    $new_filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $name);
    $target_file = $upload_dir . $new_filename;

    // Tipos MIME permitidos
    $allowed_mime_images = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff', 'image/svg+xml'];
    $allowed_mime_videos = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/x-flv', 'video/x-ms-wmv', 'video/mp2t'];
    $allowed_mime_audio = ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4', 'audio/aac', 'audio/flac', 'audio/x-ms-wma', 'audio/aiff', 'audio/opus'];

    // Extensões permitidas (fallback)
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];
    $allowed_videos = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'm4v'];
    $allowed_audio = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'wma', 'aiff', 'opus'];

    // Detecta o tipo MIME real do arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $temp);
    finfo_close($finfo);

    // Verifica se é um tipo MIME permitido OU se a extensão é permitida (fallback)
    $mime_allowed = in_array($mime_type, array_merge($allowed_mime_images, $allowed_mime_videos, $allowed_mime_audio));
    $extension_allowed = in_array($extension, array_merge($allowed_images, $allowed_videos, $allowed_audio));

    if ($mime_allowed || $extension_allowed) {
        if (move_uploaded_file($temp, $target_file)) {
            $file_url = '../uploads/ocorrencias/' . $new_filename;

            // Determina o tipo baseado no MIME
            if (in_array($mime_type, $allowed_mime_videos)) {
                $file_type = 'video';
            } elseif (in_array($mime_type, $allowed_mime_audio)) {
                $file_type = 'audio';
            } else {
                $file_type = 'image';
            }

            // Retorno compatível com TinyMCE e outros editores
            echo json_encode([
                'location' => $file_url,
                'success' => 1,
                'file' => [
                    'url' => $file_url,
                    'type' => $file_type
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
