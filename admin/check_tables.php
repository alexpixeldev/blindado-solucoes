<?php
require_once 'conexao.php';

echo "<h2>Estrutura das tabelas</h2>";

$tables = ['controle_faciais', 'controle_ata', 'controle_radio_fibra', 'controle_dvr'];

foreach ($tables as $table) {
    echo "<h3>Tabela: $table</h3>";
    $result = $conn->query("DESCRIBE $table");
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table><br>";
}

$conn->close();
?>
