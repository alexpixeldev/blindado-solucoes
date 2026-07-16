<?php
require_once 'conexao.php';

// Buscar todos os edifícios com os novos campos
$query = "SELECT id, nome, endereco, localizacao, elevador_empresa, elevador_contato FROM edificios ORDER BY nome ASC";
$result = $conn->query($query);

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Verificar Elevador e Localização</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5; }";
echo "table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background-color: #16a34a; color: white; }";
echo "tr:hover { background-color: #f5f5f5; }";
echo ".empty { color: #999; font-style: italic; }";
echo ".has-data { color: #16a34a; font-weight: bold; }";
echo "h1 { color: #16a34a; margin-bottom: 20px; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<h1>Verificação de Campos: Elevador e Localização</h1>";

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Nome do Edifício</th>";
    echo "<th>Endereço</th>";
    echo "<th>Localização</th>";
    echo "<th>Empresa de Elevadores</th>";
    echo "<th>Contato do Elevador</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($row['endereco'] ?? '<span class="empty">Não informado</span>') . "</td>";
        
        if (!empty($row['localizacao']) && $row['localizacao'] !== 'NULL') {
            echo "<td><span class='has-data'>✓</span> <a href='" . htmlspecialchars($row['localizacao']) . "' target='_blank'>" . htmlspecialchars(substr($row['localizacao'], 0, 30)) . "...</a></td>";
        } else {
            echo "<td><span class='empty'>Não informado</span></td>";
        }
        
        if (!empty($row['elevador_empresa']) && $row['elevador_empresa'] !== 'NULL') {
            echo "<td><span class='has-data'>✓</span> " . htmlspecialchars($row['elevador_empresa']) . "</td>";
        } else {
            echo "<td><span class='empty'>Não informado</span></td>";
        }
        
        if (!empty($row['elevador_contato']) && $row['elevador_contato'] !== 'NULL') {
            echo "<td><span class='has-data'>✓</span> " . htmlspecialchars($row['elevador_contato']) . "</td>";
        } else {
            echo "<td><span class='empty'>Não informado</span></td>";
        }
        
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
} else {
    echo "<p>Nenhum edifício encontrado.</p>";
}

echo "<p style='margin-top: 20px;'><a href='edificios.php?tab=edificios' style='color: #16a34a; text-decoration: none; font-weight: bold;'>← Voltar para Edifícios</a></p>";
echo "</body>";
echo "</html>";

$conn->close();
?>
