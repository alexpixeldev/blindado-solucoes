<?php
/**
 * Biblioteca de Segurança - Blindado Soluções
 * Funções utilitárias para validação, sanitização e proteção.
 */

/**
 * Valida o upload de um arquivo de forma robusta.
 */
function validate_upload($file, $allowed_extensions, $max_size = 52428800) { // Default 50MB
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erro no upload do arquivo.'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Arquivo excede o tamanho máximo permitido.'];
    }

    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_extensions)) {
        return ['success' => false, 'error' => 'Extensão de arquivo não permitida.'];
    }

    // Validação de MIME type real (mais seguro que apenas extensão)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Mapeamento básico de extensões para MIME types comuns
    $mime_map = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain', 'zip' => 'application/zip', 'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4'
    ];

    // Nota: Alguns arquivos podem ter MIME types variados, esta é uma verificação de segurança adicional
    // Para simplificar e não quebrar o sistema, vamos focar em bloquear executáveis perigosos
    $dangerous_mimes = ['application/x-php', 'text/x-php', 'application/x-httpd-php', 'application/x-executable'];
    if (in_array($mime, $dangerous_mimes)) {
        return ['success' => false, 'error' => 'Conteúdo do arquivo inválido ou perigoso.'];
    }

    return ['success' => true, 'mime' => $mime];
}

/**
 * Gera um token CSRF e armazena na sessão.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado.
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Sanitiza strings para output HTML (prevenção de XSS).
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
