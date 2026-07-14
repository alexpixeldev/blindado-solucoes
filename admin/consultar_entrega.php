<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

// Apenas usuários que não são admin ou colaborador podem acessar
if (in_array($usuario_categoria, ['administrativo', 'colaborador'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'sucesso') {
    $mensagem = "Entrega atualizada com sucesso!";
    $mensagem_tipo = "success";
}

// Lógica de exclusão
if (isset($_POST['delete_entrega'])) {
    $id_del = intval($_POST['id_delete']);
    $stmt = $conn->prepare("DELETE FROM entregas WHERE id = ?");
    $stmt->bind_param("i", $id_del);
    if ($stmt->execute()) {
        $mensagem = "Entrega excluída com sucesso!";
        $mensagem_tipo = "success";
    } else {
        $mensagem = "Erro ao excluir entrega: " . $conn->error;
        $mensagem_tipo = "error";
    }
    $stmt->close();
}

// Filtros
$filtro_edificio = isset($_GET['edificio']) ? intval($_GET['edificio']) : '';
$filtro_data = isset($_GET['data']) ? $_GET['data'] : ''; // Sem filtro de data por padrão

$query = "SELECT e.*, ed.nome as edificio_nome, b.nome as base_nome, 
                 COALESCE(ua.nome, u.nome) as usuario_nome, 
                 COALESCE(e.data_atualizacao, CONCAT(e.data_entrega, ' ', e.hora_entrega)) as data_registro
          FROM entregas e 
          JOIN edificios ed ON e.edificio_id = ed.id 
          JOIN bases b ON ed.base_id = b.id 
          JOIN usuarios u ON e.usuario_id = u.id 
          LEFT JOIN usuarios ua ON e.atualizado_por = ua.id 
          WHERE 1=1";

if (!empty($filtro_edificio)) {
    $query .= " AND e.edificio_id = " . $filtro_edificio;
}

if (!empty($filtro_data)) {
    $query .= " AND DATE(e.data_entrega) = '" . $conn->real_escape_string($filtro_data) . "'";
}

$query .= " ORDER BY e.data_entrega DESC, e.hora_entrega DESC";
$result = $conn->query($query);
$entregas = $result ? fetch_all_assoc($result) : [];

$result_edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id ORDER BY e.nome");
$edificios = $result_edificios ? fetch_all_assoc($result_edificios) : [];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Entregas | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80',
                            500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d',
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
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Consultar Entregas</h1>
                        <p class="mt-1 text-slate-500">Visualize e filtre todas as entregas registradas no sistema.</p>
                    </div>
                    <a href="registrar_entrega.php" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        <span>Registrar Entrega</span>
                    </a>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Filters Card -->
                <div class="mb-8 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Filtrar por Edifício</label>
                                <div class="relative">
                                    <select name="edificio" class="form-input appearance-none pr-10" onchange="this.form.submit()">
                                        <option value="">-- Todos os Edifícios --</option>
                                        <?php foreach ($edificios as $ed): ?>
                                            <option value="<?php echo $ed['id']; ?>" <?php echo $filtro_edificio == $ed['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ed['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <?php renderModernCalendar('data', $filtro_data, 'Filtrar por Data'); ?>
                                <script>
                                    document.getElementById('value_calendar_data').setAttribute('onchange', 'this.form.submit()');
                                </script>
                            </div>

                            <div>
                                <a href="consultar_entrega.php" class="btn-secondary" title="Limpar Filtros">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Edifício</th>
                                    <th>Apt</th>
                                    <th>Transportadora</th>
                                    <th>Situação</th>
                                    <th>Registrado por</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($entregas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-slate-500">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fas fa-box-open text-4xl text-slate-200"></i>
                                                <p>Nenhuma entrega encontrada para os filtros selecionados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entregas as $ent): ?>
                                        <tr class="group">
                                            <td class="font-semibold text-slate-700">
                                                <?php echo htmlspecialchars($ent['edificio_nome']); ?>
                                            </td>
                                            <td>
                                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-800">
                                                    <?php echo htmlspecialchars($ent['numero_apartamento']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                                    <i class="fas fa-truck text-[10px]"></i>
                                                    <?php echo htmlspecialchars($ent['transportadora']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-sm text-slate-600">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ent['situacao_recebimento'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-sm text-slate-500">
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-slate-700"><?php echo htmlspecialchars($ent['usuario_nome']); ?></span>
                                                    <span class="text-xs text-slate-400">
                                                        <?php echo date('d/m/Y H:i', strtotime($ent['data_registro'])); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                                    <a href="editar_entrega.php?id=<?= $ent['id'] ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta entrega?');" class="inline">
                                                        <input type="hidden" name="id_delete" value="<?= $ent['id'] ?>">
                                                        <button type="submit" name="delete_entrega" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <!-- Mobile actions -->
                                                <div class="flex justify-end gap-2 sm:hidden">
                                                    <a href="editar_entrega.php?id=<?= $ent['id'] ?>" class="text-slate-600 p-1"><i class="fas fa-edit"></i></a>
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
<?php
$conn->close();
?>
