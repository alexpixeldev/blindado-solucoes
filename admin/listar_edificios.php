<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if (isset($_POST['delete_edificio'])) {
    $edificio_id = $_POST['edificio_id'];
    
    // Primeiro, pegar o sindico_id do edifício que será excluído
    $stmt_get = $conn->prepare("SELECT sindico_id FROM edificios WHERE id = ?");
    $stmt_get->bind_param("i", $edificio_id);
    $stmt_get->execute();
    $edificio = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();
    
    // Verificar se este síndico tem outros edifícios
    if ($edificio && $edificio['sindico_id']) {
        $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM edificios WHERE sindico_id = ? AND id != ?");
        $stmt_check->bind_param("ii", $edificio['sindico_id'], $edificio_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        // Se não tiver outros edifícios, limpar o vinculo do síndico
        if ($result['total'] == 0) {
            $stmt_update = $conn->prepare("UPDATE sindicos SET edificio_id = NULL WHERE id = ?");
            $stmt_update->bind_param("i", $edificio['sindico_id']);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    
    // Agora deletar o edifício
    $stmt = $conn->prepare("DELETE FROM edificios WHERE id = ?");
    $stmt->bind_param("i", $edificio_id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Edifício excluído com sucesso!";
        $_SESSION['mensagem_tipo'] = "success";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir edifício: " . $conn->error;
        $_SESSION['mensagem_tipo'] = "error";
    }
    $stmt->close();
    header("Location: edificios.php?tab=edificios");
    exit();
}

$edificios = $conn->query("
    SELECT e.id, e.nome AS nome_edificio, e.endereco, e.sindico_nome, e.sindico_contato, e.administradora_id, 
           b.nome AS nome_base, a.nome AS nome_administradora
    FROM edificios e 
    JOIN bases b ON e.base_id = b.id 
    LEFT JOIN administradoras a ON e.administradora_id = a.id
    ORDER BY b.nome, e.nome
")->fetch_all(MYSQLI_ASSOC);

$mensagem = '';
$mensagem_tipo = 'info';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edifícios Cadastrados | Blindado Soluções</title>
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
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Edifícios Cadastrados</h1>
                        <p class="mt-1 text-slate-500">Gerencie os edifícios vinculados às bases operacionais.</p>
                    </div>
                    <a href="cadastrar_edificio.php" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        <span>Novo Edifício</span>
                    </a>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Data Table -->
                <div class="animate-slide-up">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Base</th>
                                    <th>Edifício</th>
                                    <th>Endereço</th>
                                    <th>Síndico / Contato</th>
                                    <th>Administradora</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($edificios)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-12 text-slate-500 italic">Nenhum edifício cadastrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($edificios as $edificio): ?>
                                        <tr class="group">
                                            <td><span class="text-xs font-bold text-slate-400">#<?= $edificio['id'] ?></span></td>
                                            <td>
                                                <span class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                                    <?= htmlspecialchars($edificio['nome_base']) ?>
                                                </span>
                                            </td>
                                            <td class="font-bold text-slate-900"><?= htmlspecialchars($edificio['nome_edificio']) ?></td>
                                            <td class="text-xs text-slate-500 max-w-xs truncate"><?= htmlspecialchars($edificio['endereco'] ?? '-') ?></td>
                                            <td>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($edificio['sindico_nome'] ?? '-') ?></span>
                                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars($edificio['sindico_contato'] ?? '-') ?></span>
                                                </div>
                                            </td>
                                            <td class="text-sm text-slate-600"><?= htmlspecialchars($edificio['nome_administradora'] ?? '-') ?></td>
                                            <td class="text-right">
                                                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <a href="editar_edificio.php?id=<?= $edificio['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Deseja realmente excluir este edifício?');" class="inline">
                                                        <input type="hidden" name="edificio_id" value="<?= $edificio['id'] ?>">
                                                        <button type="submit" name="delete_edificio" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
