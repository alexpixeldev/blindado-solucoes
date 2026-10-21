<?php
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<link rel='icon' type='image/png' href='../img/escudo.png'>";
echo "<title>Adicionar Campo - E-mail do Síndico</title>";
echo "</head>";
echo "<body>";

// Adicionar campo sindico_email na tabela edificios
$check = @$conn->query("SHOW COLUMNS FROM edificios LIKE 'sindico_email'");
if ($check && $check->num_rows > 0) {
    echo "Campo 'sindico_email' já existe na tabela edificios.<br>";
} else {
    $sql = "ALTER TABLE edificios ADD COLUMN sindico_email VARCHAR(255) NULL AFTER sindico_contato";
    if ($conn->query($sql)) {
        echo "Campo 'sindico_email' adicionado com sucesso!<br>";
    } else {
        echo "Erro ao adicionar campo 'sindico_email': " . $conn->error . "<br>";
    }
}

echo "</body>";
echo "</html>";

$conn->close();
?>
