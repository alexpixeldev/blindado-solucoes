<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Migration - Rondas</title>";
echo "</head>";
echo "<body>";

if ($_SESSION['usuario_categoria'] !== 'gerente') {
    die("Apenas gerente pode executar esta migration.");
}

$ok = true;

// 1) Colunas de localização em bases
$check = $conn->query("SHOW COLUMNS FROM bases LIKE 'latitude'");
if ($check && $check->num_rows > 0) {
    echo "bases.latitude já existe.<br>";
} else {
    $conn->query("ALTER TABLE bases ADD COLUMN latitude DECIMAL(10,7) NULL DEFAULT NULL AFTER telefone");
    $ok = $conn->error ? false : $ok;
    echo $conn->error ? ("Erro bases.latitude: " . $conn->error . "<br>") : "bases.latitude adicionada.<br>";
}
$check = $conn->query("SHOW COLUMNS FROM bases LIKE 'longitude'");
if (!$check || $check->num_rows == 0) {
    $conn->query("ALTER TABLE bases ADD COLUMN longitude DECIMAL(10,7) NULL DEFAULT NULL AFTER latitude");
    $ok = $conn->error ? false : $ok;
    echo $conn->error ? ("Erro bases.longitude: " . $conn->error . "<br>") : "bases.longitude adicionada.<br>";
}
$check = $conn->query("SHOW COLUMNS FROM bases LIKE 'raio_perimetro'");
if (!$check || $check->num_rows == 0) {
    $conn->query("ALTER TABLE bases ADD COLUMN raio_perimetro INT NULL DEFAULT 200 AFTER longitude");
    $ok = $conn->error ? false : $ok;
    echo $conn->error ? ("Erro bases.raio_perimetro: " . $conn->error . "<br>") : "bases.raio_perimetro adicionada.<br>";
}

// 2) Colunas em edificios (qr_token, lat/lng para validar scan)
$check = $conn->query("SHOW COLUMNS FROM edificios LIKE 'qr_token'");
if ($check && $check->num_rows > 0) {
    echo "edificios.qr_token já existe.<br>";
} else {
    $conn->query("ALTER TABLE edificios ADD COLUMN qr_token VARCHAR(64) NULL DEFAULT NULL AFTER retirada_lixo, ADD UNIQUE KEY uq_qr_token (qr_token)");
    $ok = $conn->error ? false : $ok;
    echo $conn->error ? ("Erro edificios.qr_token: " . $conn->error . "<br>") : "edificios.qr_token adicionada.<br>";
}
$check = $conn->query("SHOW COLUMNS FROM edificios LIKE 'latitude'");
if ($check && $check->num_rows > 0) {
    echo "edificios.latitude já existe.<br>";
} else {
    $conn->query("ALTER TABLE edificios ADD COLUMN latitude DECIMAL(10,7) NULL DEFAULT NULL AFTER qr_token, ADD COLUMN longitude DECIMAL(10,7) NULL DEFAULT NULL AFTER latitude");
    $ok = $conn->error ? false : $ok;
    echo $conn->error ? ("Erro edificios.lat/lng: " . $conn->error . "<br>") : "edificios.latitude/longitude adicionadas.<br>";
}

// 3) Tabela rondas
$conn->query("CREATE TABLE IF NOT EXISTS rondas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    base_id INT NULL,
    data_ronda DATE NOT NULL,
    hora_inicio DATETIME NULL,
    hora_fim DATETIME NULL,
    status ENUM('ativa','finalizada') DEFAULT 'ativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo $conn->error ? ("Erro rondas: " . $conn->error . "<br>") : "Tabela rondas criada.<br>";

// 4) Tabela ronda_escaneamentos
$conn->query("CREATE TABLE IF NOT EXISTS ronda_escaneamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ronda_id INT NOT NULL,
    edificio_id INT NOT NULL,
    escaneado_em DATETIME NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    interfones_ok TINYINT(1) DEFAULT 0,
    lixo_retirado TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ronda (ronda_id),
    INDEX idx_edificio (edificio_id),
    UNIQUE KEY uq_ronda_edificio (ronda_id, edificio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo $conn->error ? ("Erro ronda_escaneamentos: " . $conn->error . "<br>") : "Tabela ronda_escaneamentos criada.<br>";

if ($ok) {
    echo "<br><strong>Migration concluída com sucesso!</strong>";
} else {
    echo "<br><strong>Migration concluída com erros (veja acima).</strong>";
}

echo "</body>";
echo "</html>";

$conn->close();
