<?php

class Auth {
    private static $user = null;
    
    public static function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['usuario_id']);
    }
    
    public static function user() {
        if (self::$user === null) {
            self::$user = [
                'id' => $_SESSION['usuario_id'] ?? null,
                'nome' => $_SESSION['usuario_nome'] ?? '',
                'categoria' => $_SESSION['usuario_categoria'] ?? '',
                'email' => $_SESSION['usuario_email'] ?? ''
            ];
        }
        
        return self::$user;
    }
    
    public static function role() {
        return self::user()['categoria'];
    }
    
    public static function can($permission) {
        return has_permission(self::role(), $permission);
    }
    
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public static function requireRole($roles) {
        self::requireAuth();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array(self::role(), $roles)) {
            header('Location: index.php');
            exit();
        }
    }
    
    public static function requirePermission($permission) {
        self::requireAuth();
        
        if (!self::can($permission)) {
            header('Location: index.php');
            exit();
        }
    }
    
    public static function login($email, $password) {
        $db = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT * FROM usuarios WHERE email = ? AND status = 'ativo'",
            [$email]
        );
        
        if ($user && password_verify($password, $user['senha'])) {
            session_start();
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_email'] = $user['email'];
            $_SESSION['usuario_categoria'] = $user['categoria'];
            
            self::$user = null;
            return true;
        }
        
        return false;
    }
    
    public static function logout() {
        session_start();
        session_destroy();
        self::$user = null;
        
        header('Location: login.php');
        exit();
    }
    
    public static function is($role) {
        return self::role() === $role;
    }
    
    public static function isOneOf($roles) {
        return in_array(self::role(), $roles);
    }
    
    public static function redirectIf($role, $to) {
        if (self::is($role)) {
            header("Location: $to");
            exit();
        }
    }
}

?>
