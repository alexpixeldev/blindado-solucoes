<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Migration - Feedback</title>";
echo "</head>";
echo "<body>";

if ($_SESSION['usuario_categoria'] !== 'diretor' && $_SESSION['usuario_categoria'] !== 'gerente') {
    die("Apenas diretor ou gerente pode executar esta migration.");
}

$ok = true;

// Criar tabela de feedbacks
$conn->query("CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('sugestao', 'duvida', 'problema', 'elogio', 'outro') NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'em_analise', 'resolvido', 'fechado') DEFAULT 'pendente',
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($conn->error) {
    echo "Erro ao criar tabela feedbacks: " . $conn->error . "<br>";
    $ok = false;
} else {
    echo "Tabela feedbacks criada com sucesso.<br>";
}

if ($ok) {
    echo "<br><strong>Migration concluída com sucesso!</strong>";
} else {
    echo "<br><strong>Migration concluída com erros (veja acima).</strong>";
}

echo "</body>";
echo "</html>";

$conn->close();
?>