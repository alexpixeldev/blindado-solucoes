<?php
/**
 * Arquivo de conexão centralizado e seguro.
 * Utiliza PDO para melhor suporte a Prepared Statements e segurança.
 */

// Detectar ambiente (local vs produção)
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || 
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === 0;

if ($isLocalhost) {
    // Configurações para XAMPP local
    $host = 'localhost';
    $user = 'alexpicsilva';
    $pass = '200502';
    $dbname = 'alex8076_blindado';
    $port = 3306;
} else {
    // Configurações para produção (hospedagem)
    // Carregar variáveis de ambiente se o arquivo existir
    if (file_exists(__DIR__ . '/.env')) {
        $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $_ENV[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $user = $_ENV['DB_USER'] ?? 'alex8076_alexpicsilva';
    $pass = $_ENV['DB_PASS'] ?? 'p1anoBar*';
    $dbname = $_ENV['DB_NAME'] ?? 'alex8076_blindado';
    $port = $_ENV['DB_PORT'] ?? 3306;
}

try {
    // Log para debug (remover em produção se necessário)
    error_log("Ambiente: " . ($isLocalhost ? "DESENVOLVIMENTO (XAMPP)" : "PRODUÇÃO"));
    error_log("Conectando ao banco: $dbname@$host:$port com usuário: $user");
    
    // Conexão via PDO (Recomendado para segurança moderna)
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Manter compatibilidade com código legado que usa mysqli ($conn)
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    if ($conn->connect_error) {
        throw new Exception("Erro na conexão MySQLi: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    // Em produção, nunca exibir o erro real para o usuário
    error_log("Erro de conexão: " . $e->getMessage());
    die("Desculpe, ocorreu um erro técnico. Por favor, tente novamente mais tarde.");
}

/**
 * Função auxiliar para manter compatibilidade com fetch_all_assoc legado
 */
if (!function_exists('fetch_all_assoc')) {
    function fetch_all_assoc($result) {
        $data = [];
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>
