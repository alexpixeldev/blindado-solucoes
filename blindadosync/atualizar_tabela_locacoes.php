<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas Gerentes podem rodar o setup manual
if ($_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}

$executar = isset($_POST['executar_setup']);
$mensagens = [];

if ($executar) {
    // 1. Add columns to locacoes
    $colunas_necessarias = [
        'data_registro' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'locador_nome' => "VARCHAR(255)",
        'locador_telefone' => "VARCHAR(50)",
        'data_entrada' => "DATE",
        'data_saida' => "DATE",
        'observacoes' => "TEXT",
        'tipo_usuario' => "VARCHAR(50) DEFAULT 'locatario'",
        'edificio_id' => "INT NOT NULL",
        'numero_apartamento' => "VARCHAR(20)"
    ];

    foreach ($colunas_necessarias as $coluna => $definicao) {
        $check = $conn->query("SHOW COLUMNS FROM locacoes LIKE '$coluna'");
        if ($check->num_rows == 0) {
            if ($conn->query("ALTER TABLE locacoes ADD COLUMN $coluna $definicao")) {
                $mensagens[] = ["success", "Coluna '$coluna' adicionada em 'locacoes'."];
            }
        }
    }

    // 2. Create tables
    $conn->query("CREATE TABLE IF NOT EXISTS locacoes_inquilinos (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        locacao_id INT NOT NULL, 
        nome VARCHAR(255), 
        documento VARCHAR(50), 
        telefone VARCHAR(50), 
        selfie LONGTEXT, 
        FOREIGN KEY (locacao_id) REFERENCES locacoes(id) ON DELETE CASCADE
    )");

    // Adiciona selfie em locacoes_inquilinos caso esteja faltando
    $checkSelfie = $conn->query("SHOW COLUMNS FROM locacoes_inquilinos LIKE 'selfie'");
    if ($checkSelfie && $checkSelfie->num_rows == 0) {
        $conn->query("ALTER TABLE locacoes_inquilinos ADD COLUMN selfie LONGTEXT");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS locacoes_veiculos (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        locacao_id INT NOT NULL, 
        modelo VARCHAR(100), 
        cor VARCHAR(50), 
        placa VARCHAR(20), 
        FOREIGN KEY (locacao_id) REFERENCES locacoes(id) ON DELETE CASCADE
    )");
    
    $mensagens[] = ["success", "Tabelas auxiliares de Locações verificadas/criadas."];
}

?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Tabelas de Locações | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    
    
    
    <!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</noscript>
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Atualização Estrutural</h1>
                    <p class="mt-1 text-slate-500">Ferramenta para sincronizar a estrutura do banco de dados de locações.</p>
                </div>

                <?php foreach ($mensagens as $m): ?>
                    <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-check-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= $m[1] ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="mx-auto max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-sm">
                                <i class="fas fa-exclamation-triangle text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Atenção Necessária</h2>
                                <p class="text-sm text-slate-500">Esta operação modifica a estrutura da tabela de locações.</p>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 mb-8">
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Use esta página apenas se notar erros de <strong>"coluna não encontrada"</strong> ao gerenciar locações. 
                                O sistema irá verificar e adicionar automaticamente as colunas e tabelas auxiliares necessárias para o funcionamento correto do módulo de locações.
                            </p>
                        </div>

                        <form method="POST" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <a href="index.php" class="icon-btn" title="Voltar ao Painel"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                            <button type="submit" name="executar_setup" class="icon-btn-green" onclick="return confirm('Executar atualização estrutural?')" title="Executar Atualização"><i class="fas fa-sync-alt" style="font-size:10px"></i></button>
                        </form>
                    </div>
                </div>
            </main>
            
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
</body>
</html>
