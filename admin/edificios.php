<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if ($usuario_categoria === 'colaborador') { header('Location: index.php'); exit(); }
$pode_editar = in_array($usuario_categoria, ['supervisor', 'gerente']);

if ($pode_editar && isset($_POST['delete_item'])) {
    $id = intval($_POST['id_delete']);
    $tipo = $_POST['tipo_delete'];
    
    switch ($tipo) {
        case 'edificio':
            $stmt = $conn->prepare('DELETE FROM edificios WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['mensagem'] = 'Edifício excluído com sucesso!';
            $_SESSION['mensagem_tipo'] = 'success';
            break;
        case 'base':
            $stmt = $conn->prepare('DELETE FROM bases WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['mensagem'] = 'Base excluída com sucesso!';
            $_SESSION['mensagem_tipo'] = 'success';
            break;
        case 'administradora':
            $stmt = $conn->prepare('DELETE FROM administradoras WHERE id = ?');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['mensagem'] = "Administradora excluída com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            break;
        case 'sindico':
            $stmt = $conn->prepare("DELETE FROM sindicos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['mensagem'] = "Síndico excluído com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            break;
    }
    header("Location: edificios.php?tab=" . $_POST['current_tab']);
    exit();
}

$tab = $_GET['tab'] ?? 'edificios';
$filtro_base = $_GET['base'] ?? '';
$search = $_GET['search'] ?? '';

// Fetch data based on current tab
$data = [];
$where_clauses = [];

switch ($tab) {
    case 'edificios':
        $query = "SELECT e.id, e.nome AS nome_edificio, e.endereco, e.sindico_nome, e.sindico_contato, e.administradora_id, 
                         b.nome AS nome_base, a.nome AS nome_administradora
                  FROM edificios e 
                  JOIN bases b ON e.base_id = b.id 
                  LEFT JOIN administradoras a ON e.administradora_id = a.id";
        
        if ($filtro_base) $where_clauses[] = "e.base_id = " . intval($filtro_base);
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(e.nome LIKE '%$s%' OR b.nome LIKE '%$s%' OR e.endereco LIKE '%$s%' OR e.sindico_nome LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " ORDER BY b.nome, e.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'bases':
        $query = "SELECT b.id, b.nome, b.telefone, COUNT(e.id) as total_edificios
                  FROM bases b
                  LEFT JOIN edificios e ON b.id = e.base_id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(b.nome LIKE '%$s%' OR b.telefone LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY b.id, b.nome, b.telefone ORDER BY b.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'administradoras':
        $query = "SELECT a.*, 
                         GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') AS edificios_administrados,
                         COUNT(e.id) AS total_edificios
                  FROM administradoras a 
                  LEFT JOIN edificios e ON a.id = e.administradora_id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(a.nome LIKE '%$s%' OR a.telefone LIKE '%$s%' OR a.email LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY a.id, a.nome, a.telefone, a.email, a.created_at ORDER BY a.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'sindicos':
        $query = "SELECT s.*, 
                         GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') AS edificios_administrados,
                         COUNT(e.id) AS total_edificios
                  FROM sindicos s 
                  LEFT JOIN edificios e ON e.sindico_id = s.id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(s.nome LIKE '%$s%' OR s.telefone LIKE '%$s%' OR s.email LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY s.id, s.nome, s.telefone, s.email, s.created_at ORDER BY s.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
}

$bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? '';
$mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Edifícios | Blindado Soluções</title>
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
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Edifícios</h1>
                        <p class="mt-1 text-slate-500">Gerencie edifícios, bases, administradoras e síndicos.</p>
                    </div>
                    <?php if ($pode_editar): ?>
                        <?php 
                            $add_links = [
                                'edificios' => ['url' => 'cadastrar_edificio.php', 'label' => 'Novo Edifício'],
                                'bases' => ['url' => 'cadastrar_base.php', 'label' => 'Nova Base'],
                                'administradoras' => ['url' => 'cadastrar_administradora.php', 'label' => 'Nova Administradora'],
                                'sindicos' => ['url' => 'cadastrar_sindico.php', 'label' => 'Novo Síndico']
                            ];
                            $current_add = $add_links[$tab] ?? $add_links['edificios'];
                        ?>
                        <a href="<?= $current_add['url'] ?>" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span><?= $current_add['label'] ?></span>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Tabs Navigation -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <?php 
                        $tabs_config = [
                            'edificios' => 'Edifícios',
                            'bases' => 'Bases',
                            'administradoras' => 'Administradoras',
                            'sindicos' => 'Síndicos'
                        ];
                        foreach ($tabs_config as $key => $label):
                    ?>
                        <a href="?tab=<?= $key ?>" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === $key ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Filters & Search -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <input type="hidden" name="tab" value="<?= $tab ?>">
                            
                            <?php if ($tab === 'edificios'): ?>
                            <div class="space-y-2">
                                <label class="form-label">Filtrar por Base</label>
                                <div class="relative">
                                    <select name="base" class="form-input appearance-none pr-10" onchange="this.form.submit()">
                                        <option value="">Todas as Bases</option>
                                        <?php foreach ($bases as $b): ?>
                                            <option value="<?= $b['id'] ?>" <?= $filtro_base == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="space-y-2">
                                <label class="form-label">Buscar</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="text" id="searchInput" name="search" class="form-input pl-11" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>

                            <a href="?tab=<?= $tab ?>" class="btn-secondary" title="Limpar Filtros">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <?php if ($tab === 'edificios'): ?>
                                        <th>Edifício / Base</th>
                                        <th>Endereço</th>
                                        <th>Síndico / Contato</th>
                                        <th>Administradora</th>
                                    <?php elseif ($tab === 'bases'): ?>
                                        <th>Nome da Base</th>
                                        <th>Telefone</th>
                                        <th>Total Edifícios</th>
                                    <?php elseif ($tab === 'administradoras' || $tab === 'sindicos'): ?>
                                        <th>Nome</th>
                                        <th>Contato</th>
                                        <th>Edifícios Vinculados</th>
                                    <?php endif; ?>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-slate-500 italic">Nenhum registro encontrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data as $item): ?>
                                        <tr class="group">
                                            <?php if ($tab === 'edificios'): ?>
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-slate-900"><?= htmlspecialchars($item['nome_edificio']) ?></span>
                                                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600"><?= htmlspecialchars($item['nome_base']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-sm text-slate-500 max-w-xs truncate"><?= htmlspecialchars($item['endereco']) ?></td>
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($item['sindico_nome'] ?: 'N/A') ?></span>
                                                        <span class="text-xs text-slate-400"><?= htmlspecialchars($item['sindico_contato'] ?: '') ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="text-sm text-slate-600"><?= htmlspecialchars($item['nome_administradora'] ?: 'N/A') ?></span></td>
                                            <?php elseif ($tab === 'bases'): ?>
                                                <td class="font-bold text-slate-900"><?= htmlspecialchars($item['nome']) ?></td>
                                                <td class="text-sm text-slate-600"><?= htmlspecialchars($item['telefone'] ?: 'N/A') ?></td>
                                                <td><span class="badge-primary"><?= $item['total_edificios'] ?> edifícios</span></td>
                                            <?php elseif ($tab === 'administradoras' || $tab === 'sindicos'): ?>
                                                <td class="font-bold text-slate-900"><?= htmlspecialchars($item['nome']) ?></td>
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm text-slate-700"><?= htmlspecialchars($item['telefone'] ?: '') ?></span>
                                                        <span class="text-xs text-slate-400"><?= htmlspecialchars($item['email'] ?: '') ?></span>
                                                    </div>
                                                </td>
                                                <td class="max-w-xs">
                                                    <span class="text-xs text-slate-500 line-clamp-2"><?= htmlspecialchars($item['edificios_administrados'] ?: 'Nenhum') ?></span>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <td class="text-right">
                                                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <?php 
                                                        $edit_url = [
                                                            'edificios' => "editar_edificio.php?id=" . $item['id'],
                                                            'bases' => "editar_base.php?id=" . $item['id'],
                                                            'administradoras' => "editar_administradora.php?id=" . $item['id'],
                                                            'sindicos' => "editar_sindico.php?id=" . $item['id']
                                                        ][$tab];
                                                        
                                                        $tipo_delete = [
                                                            'edificios' => 'edificio',
                                                            'bases' => 'base',
                                                            'administradoras' => 'administradora',
                                                            'sindicos' => 'sindico'
                                                        ][$tab];
                                                    ?>
                                                    <a href="<?= $edit_url ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($pode_editar): ?>
                                                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este item?');" class="inline">
                                                            <input type="hidden" name="id_delete" value="<?= $item['id'] ?>">
                                                            <input type="hidden" name="tipo_delete" value="<?= $tipo_delete ?>">
                                                            <input type="hidden" name="current_tab" value="<?= $tab ?>">
                                                            <button type="submit" name="delete_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const baseSelect = document.querySelector('select[name="base"]');
            let searchTimeout;
            
            function performSearch() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const form = document.querySelector('form');
                    if (form) {
                        const formData = new FormData(form);
                        const params = new URLSearchParams();
                        
                        for (let [key, value] of formData.entries()) {
                            if (value) params.append(key, value);
                        }
                        
                        const currentTab = '<?= $tab ?>';
                        params.set('tab', currentTab);
                        
                        window.location.href = '?' + params.toString();
                    }
                }, 500);
            }
            
            if (searchInput) {
                searchInput.addEventListener('input', performSearch);
            }
            
            if (baseSelect) {
                baseSelect.addEventListener('change', performSearch);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
