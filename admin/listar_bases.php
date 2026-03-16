<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Delete base
if (isset($_POST['delete_base'])) {
    $base_id = $_POST['base_id'];
    $stmt = $conn->prepare("DELETE FROM bases WHERE id = ?");
    $stmt->bind_param("i", $base_id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Base e todos os seus edifícios foram excluídos com sucesso!";
        $_SESSION['mensagem_tipo'] = "success";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir base: " . $conn->error;
        $_SESSION['mensagem_tipo'] = "error";
    }
    $stmt->close();
    header("Location: listar_bases.php");
    exit();
}

// Fetch bases for display
$bases = $conn->query("
    SELECT b.id, b.nome, b.telefone, COUNT(e.id) as total_edificios
    FROM bases b
    LEFT JOIN edificios e ON b.id = e.base_id
    GROUP BY b.id, b.nome, b.telefone
    ORDER BY b.nome ASC
")->fetch_all(MYSQLI_ASSOC);

// Feedback message
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
    <title>Bases Cadastradas | Blindado Soluções</title>
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
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Bases Cadastradas</h1>
                        <p class="mt-1 text-slate-500">Gerencie todas as bases de atendimento operacional.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="cadastrar_base.php" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>Nova Base</span>
                        </a>
                    </div>
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
                                    <th>Nome da Base</th>
                                    <th>Telefone</th>
                                    <th>Edifícios Vinculados</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bases)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-slate-500 italic">Nenhuma base cadastrada.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bases as $base): ?>
                                        <tr class="group">
                                            <td><span class="text-xs font-bold text-slate-400">#<?= $base['id'] ?></span></td>
                                            <td class="font-bold text-slate-900"><?= htmlspecialchars($base['nome']) ?></td>
                                            <td class="text-sm text-slate-600">
                                                <?php if ($base['telefone']): ?>
                                                    <span class="flex items-center gap-2">
                                                        <i class="fas fa-phone-alt text-slate-300 text-xs"></i>
                                                        <?= htmlspecialchars($base['telefone']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-slate-300 italic">Não informado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="inline-flex items-center rounded-lg bg-primary-50 px-2.5 py-0.5 text-xs font-bold text-primary-700">
                                                    <?= $base['total_edificios'] ?> <?= $base['total_edificios'] == 1 ? 'edifício' : 'edifícios' ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <a href="editar_base.php?id=<?= $base['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('ATENÇÃO! Excluir uma base também apaga TODOS os seus edifícios (<?= $base['total_edificios'] ?> edifício(s)). Tem certeza?');" class="inline">
                                                        <input type="hidden" name="base_id" value="<?= $base['id'] ?>">
                                                        <button type="submit" name="delete_base" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
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
<?php $conn->close(); ?>
