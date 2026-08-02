<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if ($_SESSION['usuario_categoria'] !== 'diretor' && $_SESSION['usuario_categoria'] !== 'gerente') {
    die("Apenas diretor ou gerente pode executar esta migration.");
}

$ok = true;

// Criar tabela de relatórios gerenciais
$conn->query("CREATE TABLE IF NOT EXISTS relatorios_gerenciais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    supervisor_nome VARCHAR(255) NULL,
    operadores_nomes VARCHAR(255) NULL,
    edificio_id INT NULL,
    base_id INT NULL,
    locais_ids VARCHAR(255) NULL,
    descricao TEXT NOT NULL,
    periodo_dia VARCHAR(50) NULL,
    data_relatorio DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (data_relatorio),
    INDEX idx_edificio (edificio_id),
    INDEX idx_base (base_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($conn->error) {
    echo "Erro ao criar tabela relatorios_gerenciais: " . $conn->error . "<br>";
    $ok = false;
} else {
    echo "Tabela relatorios_gerenciais criada com sucesso.<br>";
}

if ($ok) {
    echo "<br><strong>Migration concluída com sucesso!</strong>";
} else {
    echo "<br><strong>Migration concluída com erros (veja acima).</strong>";
}
$conn->close();
?>