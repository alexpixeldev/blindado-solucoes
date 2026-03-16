<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas Supervisor e Gerente
if (!in_array($_SESSION['usuario_categoria'], ['supervisor', 'gerente'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

// Processar Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adicionar
    if (isset($_POST['add_item'])) {
        $tipo = $_POST['tipo']; // 'transportadora' ou 'situacao'
        $nome = trim($_POST['nome']);
        $tabela = ($tipo === 'transportadora') ? 'transportadoras' : 'situacoes_entrega';
        
        if (!empty($nome)) {
            $stmt = $conn->prepare("INSERT INTO $tabela (nome) VALUES (?)");
            $stmt->bind_param("s", $nome);
            if ($stmt->execute()) {
                $mensagem = "Item adicionado com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao adicionar: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        }
    }
    // Excluir
    elseif (isset($_POST['delete_item'])) {
        $id = intval($_POST['id']);
        $tipo = $_POST['tipo'];
        $tabela = ($tipo === 'transportadora') ? 'transportadoras' : 'situacoes_entrega';
        
        $conn->query("DELETE FROM $tabela WHERE id = $id");
        $mensagem = "Item removido com sucesso!";
        $mensagem_tipo = "success";
    }
    // Editar
    elseif (isset($_POST['edit_item'])) {
        $id = intval($_POST['id']);
        $nome = trim($_POST['nome']);
        $tipo = $_POST['tipo'];
        $tabela = ($tipo === 'transportadora') ? 'transportadoras' : 'situacoes_entrega';
        
        $stmt = $conn->prepare("UPDATE $tabela SET nome = ? WHERE id = ?");
        $stmt->bind_param("si", $nome, $id);
        if ($stmt->execute()) {
            $mensagem = "Item atualizado com sucesso!";
            $mensagem_tipo = "success";
        }
        $stmt->close();
    }
}

$result_transp = $conn->query("SELECT * FROM transportadoras ORDER BY nome");
$transportadoras = $result_transp ? fetch_all_assoc($result_transp) : [];
$result_sit = $conn->query("SELECT * FROM situacoes_entrega ORDER BY nome");
$situacoes = $result_sit ? fetch_all_assoc($result_sit) : [];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Entrega | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Configurações de Entrega</h1>
                    <p class="mt-1 text-slate-500">Gerencie as listas de transportadoras e situações de recebimento do sistema.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Transportadoras -->
                    <div class="animate-slide-up">
                        <div class="admin-card">
                            <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-truck text-primary-600"></i>
                                Transportadoras
                            </h2>
                            
                            <form method="POST" class="flex gap-2 mb-6">
                                <input type="hidden" name="tipo" value="transportadora">
                                <input type="text" name="nome" class="form-input" placeholder="Nova Transportadora" required>
                                <button type="submit" name="add_item" class="btn-primary whitespace-nowrap">
                                    <i class="fas fa-plus"></i>
                                    <span>Adicionar</span>
                                </button>
                            </form>
                            
                            <div class="max-h-[400px] overflow-y-auto custom-scrollbar border border-slate-100 rounded-xl">
                                <table class="w-full text-left border-collapse">
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if (empty($transportadoras)): ?>
                                            <tr>
                                                <td class="p-8 text-center text-slate-400 italic text-sm">Nenhuma transportadora cadastrada.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($transportadoras as $t): ?>
                                                <tr class="group hover:bg-slate-50 transition-colors">
                                                    <td class="p-3">
                                                        <form method="POST" class="flex gap-2 items-center">
                                                            <input type="hidden" name="tipo" value="transportadora">
                                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                            <input type="text" name="nome" value="<?= htmlspecialchars($t['nome']) ?>" class="flex-1 bg-transparent border-none focus:ring-2 focus:ring-primary-500 rounded-lg px-2 py-1 text-sm font-medium text-slate-700 transition-all">
                                                            
                                                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <button type="submit" name="edit_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all" title="Salvar">
                                                                    <i class="fas fa-check text-xs"></i>
                                                                </button>
                                                                <button type="submit" name="delete_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir" onclick="return confirm('Excluir este item?')">
                                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Situações -->
                    <div class="animate-slide-up" style="animation-delay: 0.1s;">
                        <div class="admin-card">
                            <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-info-circle text-primary-600"></i>
                                Situações de Recebimento
                            </h2>
                            
                            <form method="POST" class="flex gap-2 mb-6">
                                <input type="hidden" name="tipo" value="situacao">
                                <input type="text" name="nome" class="form-input" placeholder="Nova Situação" required>
                                <button type="submit" name="add_item" class="btn-primary whitespace-nowrap">
                                    <i class="fas fa-plus"></i>
                                    <span>Adicionar</span>
                                </button>
                            </form>
                            
                            <div class="max-h-[400px] overflow-y-auto custom-scrollbar border border-slate-100 rounded-xl">
                                <table class="w-full text-left border-collapse">
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if (empty($situacoes)): ?>
                                            <tr>
                                                <td class="p-8 text-center text-slate-400 italic text-sm">Nenhuma situação cadastrada.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($situacoes as $s): ?>
                                                <tr class="group hover:bg-slate-50 transition-colors">
                                                    <td class="p-3">
                                                        <form method="POST" class="flex gap-2 items-center">
                                                            <input type="hidden" name="tipo" value="situacao">
                                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                            <input type="text" name="nome" value="<?= htmlspecialchars($s['nome']) ?>" class="flex-1 bg-transparent border-none focus:ring-2 focus:ring-primary-500 rounded-lg px-2 py-1 text-sm font-medium text-slate-700 transition-all">
                                                            
                                                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <button type="submit" name="edit_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all" title="Salvar">
                                                                    <i class="fas fa-check text-xs"></i>
                                                                </button>
                                                                <button type="submit" name="delete_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir" onclick="return confirm('Excluir este item?')">
                                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
