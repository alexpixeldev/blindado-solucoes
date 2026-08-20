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
            $check = $conn->prepare("SELECT id FROM $tabela WHERE nome = ? LIMIT 1");
            $check->bind_param("s", $nome);
            $check->execute();
            $ja_existe = (bool)$check->get_result()->fetch_assoc();
            $check->close();

            if ($ja_existe) {
                $mensagem = "Já existe um item com o nome \"$nome\".";
                $mensagem_tipo = "error";
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO $tabela (nome) VALUES (?)");
                    $stmt->bind_param("s", $nome);
                    $stmt->execute();
                    $stmt->close();
                    $mensagem = "Item adicionado com sucesso!";
                    $mensagem_tipo = "success";
                } catch (Exception $e) {
                    $mensagem = "Não foi possível adicionar: já existe um item com esse nome.";
                    $mensagem_tipo = "error";
                }
            }
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
        try {
            $stmt->execute();
            $stmt->close();
            $mensagem = "Item atualizado com sucesso!";
            $mensagem_tipo = "success";
        } catch (Exception $e) {
            $mensagem = "Não foi possível atualizar: já existe outro item com esse nome.";
            $mensagem_tipo = "error";
        }
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
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar flex flex-col">
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

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 flex-1 items-stretch">
                    <!-- Transportadoras -->
                    <div class="animate-slide-up h-full">
                        <div class="admin-card h-full flex flex-col">
                            <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-truck text-primary-600"></i>
                                Transportadoras
                            </h2>
                            
                            <form method="POST" class="flex gap-2 mb-6">
                                <input type="hidden" name="tipo" value="transportadora">
                                <input type="text" name="nome" class="form-input" placeholder="Nova Transportadora" required>
                                <button type="submit" name="add_item" class="icon-btn-green" title="Adicionar"><i class="fas fa-plus" style="font-size:10px"></i></button>
                            </form>
                            
                            <div class="flex-1 min-h-[200px] overflow-y-auto custom-scrollbar border border-slate-100 rounded-xl">
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
                                                                <button type="submit" name="edit_item" class="icon-btn-green" title="Salvar"><i class="fas fa-check" style="font-size:10px"></i></button>
                                                                <button type="submit" name="delete_item" class="icon-btn-red" title="Excluir" onclick="return confirm('Excluir este item?')"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
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
                    <div class="animate-slide-up h-full" style="animation-delay: 0.1s;">
                        <div class="admin-card h-full flex flex-col">
                            <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-info-circle text-primary-600"></i>
                                Situações de Recebimento
                            </h2>
                            
                            <form method="POST" class="flex gap-2 mb-6">
                                <input type="hidden" name="tipo" value="situacao">
                                <input type="text" name="nome" class="form-input" placeholder="Nova Situação" required>
                                <button type="submit" name="add_item" class="icon-btn-green" title="Adicionar"><i class="fas fa-plus" style="font-size:10px"></i></button>
                            </form>
                            
                            <div class="flex-1 min-h-[200px] overflow-y-auto custom-scrollbar border border-slate-100 rounded-xl">
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
                                                                <button type="submit" name="edit_item" class="icon-btn-green" title="Salvar"><i class="fas fa-check" style="font-size:10px"></i></button>
                                                                <button type="submit" name="delete_item" class="icon-btn-red" title="Excluir" onclick="return confirm('Excluir este item?')"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
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
