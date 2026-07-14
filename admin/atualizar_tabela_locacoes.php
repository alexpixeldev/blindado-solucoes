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
        FOREIGN KEY (locacao_id) REFERENCES locacoes(id) ON DELETE CASCADE
    )");

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
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
                            <a href="index.php" class="btn-secondary order-2 sm:order-1">
                                <i class="fas fa-arrow-left"></i>
                                <span>Voltar ao Painel</span>
                            </a>
                            <button type="submit" name="executar_setup" class="btn-primary order-1 sm:order-2" onclick="return confirm('Executar atualização estrutural?')">
                                <i class="fas fa-sync-alt"></i>
                                <span>Executar Atualização</span>
                            </button>
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
