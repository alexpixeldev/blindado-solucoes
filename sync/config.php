<?php

define('APP_NAME', 'Blindado Soluções');
define('APP_VERSION', '1.0.0');

define('DB_HOST', 'localhost');
define('DB_NAME', 'blindado_solucoes');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_PATH', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

define('USER_ROLES', [
    'administrador' => 'Administrador',
    'gerente' => 'Gerente', 
    'supervisor' => 'Supervisor',
    'administrativo' => 'Administrativo',
    'colaborador' => 'Colaborador'
]);

define('PERMISSIONS', [
    'administrador' => ['all'],
    'gerente' => ['manage_users', 'manage_data', 'view_reports', 'delete_records'],
    'supervisor' => ['manage_data', 'view_reports'],
    'administrativo' => ['view_reports', 'manage_contracheques'],
    'colaborador' => ['view_own_data']
]);

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function get_user_role($role_key) {
    return USER_ROLES[$role_key] ?? 'Desconhecido';
}

function has_permission($user_role, $permission) {
    if (!isset(PERMISSIONS[$user_role])) {
        return false;
    }
    
    return in_array('all', PERMISSIONS[$user_role]) || 
           in_array($permission, PERMISSIONS[$user_role]);
}

?>
