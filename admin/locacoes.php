<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Get filters
$filtro_edificio = isset($_GET['edificio']) ? $_GET['edificio'] : '';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';
$filtro_data = isset($_GET['data']) ? $_GET['data'] : date('Y-m');

// Build base query
$query = "SELECT l.*, e.nome as nome_edificio, b.nome as nome_base 
          FROM locacoes l 
          JOIN edificios e ON l.edificio_id = e.id 
          JOIN bases b ON e.base_id = b.id 
          WHERE 1=1";

// Apply building filter
if (!empty($filtro_edificio)) {
    $query .= " AND l.edificio_id = " . intval($filtro_edificio);
}

// Apply period filter
switch ($filtro_periodo) {
    case 'dia':
        $query .= " AND DATE(l.data_locacao) = '" . $conn->real_escape_string($filtro_data) . "'";
        break;
    case 'semana':
        $query .= " AND YEARWEEK(l.data_locacao) = YEARWEEK('" . $conn->real_escape_string($filtro_data) . "')";
        break;
    case 'mes':
        $query .= " AND DATE_FORMAT(l.data_locacao, '%Y-%m') = '" . $conn->real_escape_string($filtro_data) . "'";
        break;
    case 'ano':
        $query .= " AND YEAR(l.data_locacao) = " . intval(substr($filtro_data, 0, 4));
        break;
}

$query .= " ORDER BY l.data_locacao DESC";
$locacoes = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get list of buildings
$edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locações Registradas | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Locações Registradas</h1>
                    <p class="mt-1 text-slate-500">Visualize e filtre todas as locações por edifício e período.</p>
                </div>

                <!-- Filters -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Edifício</label>
                                <div class="relative">
                                    <select name="edificio" class="form-input appearance-none pr-10" onchange="this.form.submit()">
                                        <option value="">Todos os Edifícios</option>
                                        <?php foreach ($edificios as $ed): ?>
                                            <option value="<?= $ed['id'] ?>" <?= $filtro_edificio == $ed['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ed['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Período</label>
                                <div class="relative">
                                    <select name="periodo" id="periodo" class="form-input appearance-none pr-10" onchange="this.form.submit()">
                                        <option value="dia" <?= $filtro_periodo == 'dia' ? 'selected' : '' ?>>Por Dia</option>
                                        <option value="semana" <?= $filtro_periodo == 'semana' ? 'selected' : '' ?>>Por Semana</option>
                                        <option value="mes" <?= $filtro_periodo == 'mes' ? 'selected' : '' ?>>Por Mês</option>
                                        <option value="ano" <?= $filtro_periodo == 'ano' ? 'selected' : '' ?>>Por Ano</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-calendar-alt text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Data / Referência</label>
                                <input type="text" name="data" id="data-input" class="form-input" value="<?= htmlspecialchars($filtro_data) ?>" placeholder="YYYY-MM-DD" onchange="this.form.submit()">
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-filter"></i>
                                    <span>Filtrar</span>
                                </button>
                                <a href="locacoes.php" class="btn-secondary" title="Limpar Filtros">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Base / Edifício</th>
                                    <th>Apto</th>
                                    <th>Morador</th>
                                    <th>Tipo</th>
                                    <th>Data Locação</th>
                                    <th>Período</th>
                                    <th>Telefone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($locacoes)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-12 text-slate-500 italic">Nenhuma locação encontrada com os filtros selecionados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($locacoes as $loc): ?>
                                        <tr class="group">
                                            <td>
                                                <div class="flex flex-col">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600"><?= htmlspecialchars($loc['nome_base']) ?></span>
                                                    <span class="font-bold text-slate-900"><?= htmlspecialchars($loc['nome_edificio']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="font-mono text-xs font-bold text-slate-500"><?= htmlspecialchars($loc['numero_apartamento'] ?? 'N/A') ?></span></td>
                                            <td class="font-bold text-slate-900"><?= htmlspecialchars($loc['locador_nome'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">
                                                    <?= ucfirst($loc['tipo_usuario']) ?>
                                                </span>
                                            </td>
                                            <td class="text-sm text-slate-600"><?= date('d/m/Y', strtotime($loc['data_locacao'])) ?></td>
                                            <td class="text-sm text-slate-600">
                                                <div class="flex items-center gap-1">
                                                    <span><?= $loc['data_entrada'] ? date('d/m/Y', strtotime($loc['data_entrada'])) : '---' ?></span>
                                                    <?php if ($loc['data_entrada'] && $loc['data_saida']): ?>
                                                        <i class="fas fa-arrow-right text-xs text-slate-300"></i>
                                                        <span><?= date('d/m/Y', strtotime($loc['data_saida'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-sm text-slate-600"><?= htmlspecialchars($loc['locador_telefone'] ?? 'N/A') ?></td>
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
        document.getElementById('periodo').addEventListener('change', function() {
            const input = document.getElementById('data-input');
            const periodo = this.value;
            const today = new Date();
            
            switch(periodo) {
                case 'dia':
                    input.type = 'date';
                    input.value = today.toISOString().split('T')[0];
                    break;
                case 'semana':
                case 'mes':
                    input.type = 'text';
                    input.placeholder = 'YYYY-MM';
                    input.value = today.toISOString().slice(0, 7);
                    break;
                case 'ano':
                    input.type = 'text';
                    input.placeholder = 'YYYY';
                    input.value = today.getFullYear();
                    break;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
