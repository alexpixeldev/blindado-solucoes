<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$pageTitle = 'Dashboard';
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

if ($usuario_categoria === 'colaborador') {
    header('Location: colaboradores.php');
    exit();
}

if ($usuario_categoria === 'rondante') {
    header('Location: rondante.php');
    exit();
}

$stats = [];
$mes_selecionado = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: date('n');
$ano_selecionado = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: date('Y');
$inicio_mes = sprintf('%04d-%02d-01', $ano_selecionado, $mes_selecionado);
$fim_mes = date('Y-m-01', strtotime($inicio_mes . ' +1 month'));

if ($usuario_categoria === 'gerente') {
    $stats['total_edificios'] = $conn->query("SELECT COUNT(*) as total FROM edificios")->fetch_assoc()['total'] ?? 0;
    $stats['total_locacoes'] = $conn->query("SELECT COUNT(*) as total FROM locacoes")->fetch_assoc()['total'] ?? 0;
    $stats['total_entregas'] = $conn->query("SELECT COUNT(*) as total FROM entregas")->fetch_assoc()['total'] ?? 0;
    $stats['total_extras'] = $conn->query("SELECT COUNT(*) as total FROM extras")->fetch_assoc()['total'] ?? 0;
    
    $chart_data = [];
    $dias_no_mes = date('t', mktime(0, 0, 0, $mes_selecionado, 1, $ano_selecionado));
    
    for ($i = 1; $i <= $dias_no_mes; $i++) {
        $chart_data[$i] = 0;
    }
    
    $result = $conn->query("
        SELECT DAY(data_criacao) as dia, COUNT(*) as total 
        FROM locacoes 
        WHERE data_criacao >= '$inicio_mes'
        AND data_criacao < '$fim_mes'
        GROUP BY DAY(data_criacao)
        ORDER BY dia
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chart_data[(int)$row['dia']] = (int)$row['total'];
        }
    }
    
    $previsao_data = [];
    $previsao_entradas = [];
    $previsao_saidas = [];
    
    for ($i = 1; $i <= $dias_no_mes; $i++) {
        $previsao_data[$i] = 0;
        $previsao_entradas[$i] = 0;
        $previsao_saidas[$i] = 0;
    }
    
    $result_entradas = $conn->query("
        SELECT 
            DAY(data_entrada) as dia,
            COUNT(*) as total_entradas
        FROM locacoes 
        WHERE data_entrada >= '$inicio_mes'
        AND data_entrada < '$fim_mes'
        GROUP BY DAY(data_entrada)
        ORDER BY dia
    ");
    
    if ($result_entradas) {
        while ($row = $result_entradas->fetch_assoc()) {
            $previsao_entradas[(int)$row['dia']] = (int)$row['total_entradas'];
        }
    }
    
    $result_saidas = $conn->query("
        SELECT 
            DAY(data_saida) as dia,
            COUNT(*) as total_saidas
        FROM locacoes 
        WHERE data_saida >= '$inicio_mes'
        AND data_saida < '$fim_mes'
        GROUP BY DAY(data_saida)
        ORDER BY dia
    ");
    
    if ($result_saidas) {
        while ($row = $result_saidas->fetch_assoc()) {
            $previsao_saidas[(int)$row['dia']] = (int)$row['total_saidas'];
        }
    }
}

if ($usuario_categoria === 'administrativo') {
    $stats['total_colaboradores'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE categoria = 'colaborador'")->fetch_assoc()['total'] ?? 0;
    $stats['ferias_mes'] = $conn->query("SELECT COUNT(*) as total FROM ferias WHERE YEAR(data_inicio) = $ano_selecionado AND MONTH(data_inicio) = $mes_selecionado")->fetch_assoc()['total'] ?? 0;
    $stats['faltas_mes'] = $conn->query("SELECT COUNT(*) as total FROM faltas WHERE YEAR(data_registro) = $ano_selecionado AND MONTH(data_registro) = $mes_selecionado")->fetch_assoc()['total'] ?? 0;
    $stats['extras_mes'] = $conn->query("SELECT COUNT(*) as total FROM extras WHERE YEAR(data_registro) = $ano_selecionado AND MONTH(data_registro) = $mes_selecionado")->fetch_assoc()['total'] ?? 0;

    $proximas_ferias = [];
    $r = $conn->query("SELECT f.id, f.data_inicio, f.data_fim, u.nome, u.nome_real FROM ferias f JOIN usuarios u ON f.usuario_id = u.id WHERE f.data_inicio >= CURDATE() ORDER BY f.data_inicio ASC LIMIT 6");
    if ($r) $proximas_ferias = $r->fetch_all(MYSQLI_ASSOC);

    $faltas_recentes = [];
    $r = $conn->query("SELECT f.id, f.data_registro, f.tipo, f.motivo, u.nome, u.nome_real FROM faltas f JOIN usuarios u ON f.usuario_id = u.id ORDER BY f.data_registro DESC LIMIT 6");
    if ($r) $faltas_recentes = $r->fetch_all(MYSQLI_ASSOC);

    $novos_colaboradores = [];
    $r = $conn->query("SELECT id, nome, nome_real, data_admissao FROM usuarios WHERE categoria = 'colaborador' AND data_admissao IS NOT NULL ORDER BY data_admissao DESC LIMIT 6");
    if ($r) $novos_colaboradores = $r->fetch_all(MYSQLI_ASSOC);

    // Filtro de mês/ano para o dashboard administrativo
    $filtro_mes_admin = $mes_selecionado;
    $filtro_ano_admin = $ano_selecionado;
}

if (in_array($usuario_categoria, ['operador', 'supervisor'])) {
    $usuario_id_op = intval($_SESSION['usuario_id'] ?? 0);
    $base_id_op = 0;
    $row_b = $conn->query("SELECT base_id FROM usuarios WHERE id = $usuario_id_op")->fetch_assoc();
    if ($row_b) $base_id_op = intval($row_b['base_id'] ?? 0);

    // Listas
    $cond_base = intval($base_id_op) > 0 ? "e.base_id = $base_id_op AND " : "";

    $ultimas_locacoes_list = [];
    $r = $conn->query("SELECT l.id, e.nome as edificio_nome, l.numero_apartamento, l.nome_morador, l.data_registro FROM locacoes l JOIN edificios e ON l.edificio_id = e.id WHERE {$cond_base}1 ORDER BY l.data_registro DESC LIMIT 6");
    if ($r) $ultimas_locacoes_list = $r->fetch_all(MYSQLI_ASSOC);

    $ultimas_entregas_list = [];
    $r = $conn->query("SELECT en.id, e.nome as edificio_nome, en.numero_apartamento, en.transportadora, en.situacao_recebimento, en.data_criacao FROM entregas en JOIN edificios e ON en.edificio_id = e.id WHERE {$cond_base}1 ORDER BY en.data_criacao DESC, en.id DESC LIMIT 6");
    if ($r) $ultimas_entregas_list = $r->fetch_all(MYSQLI_ASSOC);

    $ultimos_prestadores_list = [];
    $r = $conn->query("SELECT ps.id, e.nome as edificio_nome, ps.numero_apartamento, ps.nome_empresa, ps.nome_funcionario, ps.tipo_servico, ps.data_servico, ps.data_criacao FROM prestadores_servico ps JOIN edificios e ON ps.edificio_id = e.id WHERE {$cond_base}1 ORDER BY ps.data_criacao DESC, ps.id DESC LIMIT 6");
    if ($r) $ultimos_prestadores_list = $r->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full" style="background: var(--bg-primary);">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Blindado Soluções</title>
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
    
    <!-- Variáveis CSS para ApexCharts -->
    <link rel="stylesheet" href="apexcharts-flyonui.min.css">
    <style>
        :root {
            --color-base-content: #F2F2F3;
            --color-base-100: #122542;
            --color-base-200: #061328;
            --color-info: #3BBE55;
            --color-accent: #29A7F7;
            --color-success: #3BBE55;
            --color-error: #ef4444;
        }
    </style>
    
    <!-- ApexCharts -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- FlyonUI ApexCharts Helper -->
    <script src="helper-apexcharts.min.js"></script>
</head>
<body class="h-full text-slate-800 antialiased" style="background: var(--bg-primary);">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Dashboard</h1>
                    <p class="mt-1 text-slate-500">Bem-vindo de volta, <span class="font-semibold text-primary-600"><?php echo htmlspecialchars($usuario_nome); ?></span>.</p>
                </div>

                <?php if (!empty($stats)): ?>
                <!-- Stats Grid -->
                <div class="mb-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 animate-slide-up">
                    <?php if (isset($stats['total_edificios'])): ?>
                    <a href="edificios.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total de Edifícios</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['total_edificios']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                                <i class="fas fa-building text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($stats['total_locacoes'])): ?>
                    <a href="listar_locacoes.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Locações</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['total_locacoes']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-600 group-hover:text-white">
                                <i class="fas fa-key text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($stats['total_entregas'])): ?>
                    <a href="consultar_entrega.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Entregas</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['total_entregas']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 transition-colors group-hover:bg-orange-600 group-hover:text-white">
                                <i class="fas fa-box text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($stats['total_extras'])): ?>
                    <a href="extras.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Extras</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['total_extras']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 transition-colors group-hover:bg-red-600 group-hover:text-white">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($stats['total_colaboradores'])): ?>
                    <div class="admin-card group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Colaboradores</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['total_colaboradores']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-50 text-green-600 transition-colors group-hover:bg-green-600 group-hover:text-white">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($stats['ferias_mes'])): ?>
                    <a href="ferias_admin.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Férias no mês</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['ferias_mes']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                                <i class="fas fa-umbrella-beach text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($stats['faltas_mes'])): ?>
                    <a href="gestao_faltas.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Faltas no mês</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['faltas_mes']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-600 group-hover:text-white">
                                <i class="fas fa-user-clock text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($stats['extras_mes'])): ?>
                    <a href="extras.php" class="admin-card group block cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Extras no mês</p>
                                <h3 class="mt-1 text-3xl font-bold text-slate-900"><?php echo $stats['extras_mes']; ?></h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 transition-colors group-hover:bg-orange-600 group-hover:text-white">
                                <i class="fas fa-plus-circle text-xl"></i>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($usuario_categoria === 'administrativo'): ?>
                <!-- Filtro de mês para o dashboard administrativo -->
                <div class="mb-6 flex flex-wrap items-center justify-end gap-3 animate-fade-in">
                    <div class="flex items-center gap-2">
                        <label for="mesAdmin" class="text-sm font-medium text-slate-700">Mês:</label>
                        <select id="mesAdmin" name="mes" onchange="location.href='?mes=' + this.value + '&ano=' + document.getElementById('anoAdmin').value" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            <?php
                            $meses = [
                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                            ];
                            foreach ($meses as $num => $nome) {
                                $selected = ($num == $mes_selecionado) ? 'selected' : '';
                                echo "<option value='$num' $selected>$nome</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="anoAdmin" class="text-sm font-medium text-slate-700">Ano:</label>
                        <select id="anoAdmin" name="ano" onchange="location.href='?mes=' + document.getElementById('mesAdmin').value + '&ano=' + this.value" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            <?php
                            for ($ano = date('Y'); $ano >= 2020; $ano--) {
                                $selected = ($ano == $ano_selecionado) ? 'selected' : '';
                                echo "<option value='$ano' $selected>$ano</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Listas do RH -->
                <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3 animate-slide-up">
                    <!-- Próximas Férias -->
                    <div class="admin-card flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-umbrella-beach text-amber-500"></i> Próximas Férias
                            </h2>
                        </div>
                        <?php if (empty($proximas_ferias)): ?>
                            <p class="text-sm text-slate-400 flex-1 flex items-center justify-center py-6">Nenhuma férias agendada.</p>
                        <?php else: ?>
                        <div class="flex-1 min-h-[200px] overflow-y-auto space-y-3">
                            <?php foreach ($proximas_ferias as $f): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($f['nome_real'] ?: $f['nome']) ?></p>
                                <p class="mt-1 text-xs text-slate-500">
                                    <?= date('d/m/Y', strtotime($f['data_inicio'])) ?> → <?= date('d/m/Y', strtotime($f['data_fim'])) ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Faltas Recentes -->
                    <div class="admin-card flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-user-clock text-rose-500"></i> Faltas Recentes
                            </h2>
                        </div>
                        <?php if (empty($faltas_recentes)): ?>
                            <p class="text-sm text-slate-400 flex-1 flex items-center justify-center py-6">Nenhuma falta registrada.</p>
                        <?php else: ?>
                        <div class="flex-1 min-h-[200px] overflow-y-auto space-y-3">
                            <?php foreach ($faltas_recentes as $fl): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($fl['nome_real'] ?: $fl['nome']) ?></p>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase <?= $fl['tipo'] === 'injustificada' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' ?>"><?= htmlspecialchars($fl['tipo']) ?></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    <?= date('d/m/Y', strtotime($fl['data_registro'])) ?><?= $fl['motivo'] ? ' — ' . htmlspecialchars($fl['motivo']) : '' ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Novos Colaboradores -->
                    <div class="admin-card flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-user-plus text-green-500"></i> Novos Colaboradores
                            </h2>
                        </div>
                        <?php if (empty($novos_colaboradores)): ?>
                            <p class="text-sm text-slate-400 flex-1 flex items-center justify-center py-6">Nenhum colaborador cadastrado.</p>
                        <?php else: ?>
                        <div class="flex-1 min-h-[200px] overflow-y-auto space-y-3">
                            <?php foreach ($novos_colaboradores as $nc): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($nc['nome_real'] ?: $nc['nome']) ?></p>
                                <p class="mt-1 text-xs text-slate-500">Admitido em <?= $nc['data_admissao'] ? date('d/m/Y', strtotime($nc['data_admissao'])) : '—' ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif (in_array($usuario_categoria, ['operador', 'supervisor'])): ?>

                <!-- Ações Rápidas -->
                <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4 animate-slide-up">
                    <a href="registrar_entrega.php" class="admin-card group block cursor-pointer hover:border-orange-300">
                        <div class="flex flex-col items-center gap-2 py-4 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 transition-colors group-hover:bg-orange-600 group-hover:text-white">
                                <i class="fas fa-box text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Registrar Entrega</span>
                        </div>
                    </a>
                    <a href="registrar_prestador.php" class="admin-card group block cursor-pointer hover:border-green-300">
                        <div class="flex flex-col items-center gap-2 py-4 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-50 text-green-600 transition-colors group-hover:bg-green-600 group-hover:text-white">
                                <i class="fas fa-user-shield text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Registrar Prestador</span>
                        </div>
                    </a>
                    <a href="registrar_ocorrencia.php" class="admin-card group block cursor-pointer hover:border-blue-300">
                        <div class="flex flex-col items-center gap-2 py-4 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                                <i class="fas fa-edit text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Registrar Ocorrência</span>
                        </div>
                    </a>
                    <a href="consultar_ocorrencia.php" class="admin-card group block cursor-pointer hover:border-purple-300">
                        <div class="flex flex-col items-center gap-2 py-4 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-600 group-hover:text-white">
                                <i class="fas fa-search text-xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Consultar Ocorrências</span>
                        </div>
                    </a>
                </div>

                <!-- Listas do Operador -->
                <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3 animate-slide-up">
                    <!-- Últimas Locações -->
                    <div class="admin-card flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-key text-purple-500"></i> Últimas Locações
                            </h2>
                        </div>
                        <?php if (empty($ultimas_locacoes_list)): ?>
                            <p class="text-sm text-slate-400 flex-1 flex items-center justify-center py-6">Nenhuma locação cadastrada.</p>
                        <?php else: ?>
                        <div class="flex-1 min-h-[200px] overflow-y-auto space-y-3">
                            <?php foreach ($ultimas_locacoes_list as $la): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($la['edificio_nome']) ?><?= $la['numero_apartamento'] ? ' — Ap. ' . htmlspecialchars($la['numero_apartamento']) : '' ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?= $la['data_registro'] ? date('d/m/Y H:i', strtotime($la['data_registro'])) : '—' ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Últimas Entregas -->
                    <div class="admin-card flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-box text-orange-500"></i> Últimas Entregas
                            </h2>
                        </div>
                        <?php if (empty($ultimas_entregas_list)): ?>
                            <p class="text-sm text-slate-400 flex-1 flex items-center justify-center py-6">Nenhuma entrega registrada.</p>
                        <?php else: ?>
                        <div class="flex-1 min-h-[200px] overflow-y-auto space-y-3">
                            <?php foreach ($ultimas_entregas_list as $en): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($en['edificio_nome']) ?><?= $en['numero_apartamento'] ? ' — Ap. ' . htmlspecialchars($en['numero_apartamento']) : '' ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($en['transportadora']) ?> · <?= htmlspecialchars($en['situacao_recebimento']) ?> · <?= $en['data_criacao'] ? date('d/m H:i', strtotime($en['data_criacao'])) : '' ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Últimos Prestadores -->
                    <div class="admin-card flex h-full flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-user-shield text-green-500"></i> Últimos Prestadores
                            </h2>
                        </div>
                        <?php if (empty($ultimos_prestadores_list)): ?>
                            <p class="text-sm text-slate-400 flex-1 flex items-center justify-center py-6">Nenhum prestador registrado.</p>
                        <?php else: ?>
                        <div class="flex-1 min-h-[200px] overflow-y-auto space-y-3">
                            <?php foreach ($ultimos_prestadores_list as $ps): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($ps['edificio_nome']) ?><?= $ps['numero_apartamento'] ? ' — Ap. ' . htmlspecialchars($ps['numero_apartamento']) : '' ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($ps['nome_empresa'] ?: $ps['nome_funcionario']) ?><?= $ps['tipo_servico'] ? ' · ' . htmlspecialchars($ps['tipo_servico']) : '' ?><?= $ps['data_servico'] ? ' · ' . date('d/m/Y', strtotime($ps['data_servico'])) : '' ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php else: ?>

                <!-- Gráfico de Locações por Dia do Mês -->
                <div class="mb-8">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-3">
                                <i class="fas fa-chart-line text-primary-600"></i>
                                Locações por Dia
                            </h2>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <label for="mesSelector" class="text-sm font-medium text-slate-700">Mês:</label>
                                    <select id="mesSelector" name="mes" onchange="location.href='?mes=' + this.value + '&ano=' + document.getElementById('anoSelector').value" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                                        <?php
                                        $meses = [
                                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                        ];
                                        foreach ($meses as $num => $nome) {
                                            $selected = ($num == $mes_selecionado) ? 'selected' : '';
                                            echo "<option value='$num' $selected>$nome</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label for="anoSelector" class="text-sm font-medium text-slate-700">Ano:</label>
                                    <select id="anoSelector" name="ano" onchange="location.href='?mes=' + document.getElementById('mesSelector').value + '&ano=' + this.value" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                                        <?php
                                        for ($ano = date('Y'); $ano >= 2020; $ano--) {
                                            $selected = ($ano == $ano_selecionado) ? 'selected' : '';
                                            echo "<option value='$ano' $selected>$ano</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <div id="locacoesChart" class="w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Previsão de maior movimento -->
                <div class="mb-8">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-3">
                                <i class="fas fa-chart-area text-blue-600"></i>
                                Previsão de maior movimento
                            </h2>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <label for="mesSelector2" class="text-sm font-medium text-slate-700">Mês:</label>
                                    <select id="mesSelector2" name="mes" onchange="location.href='?mes=' + this.value + '&ano=' + document.getElementById('anoSelector2').value" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                                        <?php
                                        $meses = [
                                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                        ];
                                        foreach ($meses as $num => $nome) {
                                            $selected = ($num == $mes_selecionado) ? 'selected' : '';
                                            echo "<option value='$num' $selected>$nome</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label for="anoSelector2" class="text-sm font-medium text-slate-700">Ano:</label>
                                    <select id="anoSelector2" name="ano" onchange="location.href='?mes=' + document.getElementById('mesSelector2').value + '&ano=' + this.value" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                                        <?php
                                        for ($ano = date('Y'); $ano >= 2020; $ano--) {
                                            $selected = ($ano == $ano_selecionado) ? 'selected' : '';
                                            echo "<option value='$ano' $selected>$ano</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <div id="previsaoChart" class="w-full"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
            
            <footer class="border-t p-4 text-center text-xs" style="border-color: var(--border); color: var(--text-secondary); background: var(--bg-secondary);">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <?php if (isset($chart_data)): ?>
    <script>
        window.addEventListener('load', function() {
            ;(function() {
                // Gráfico de Locações por Dia - Estilo FlyonUI
                buildChart('#locacoesChart', () => ({
                    chart: {
                        height: 400,
                        type: 'area',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        }
                    },
                    series: [{
                        name: 'Locações',
                        data: Object.values(<?php echo json_encode($chart_data); ?>)
                    }],
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    grid: {
                        strokeDashArray: 2,
                        borderColor: 'color-mix(in oklab, var(--color-base-content) 40%, transparent)'
                    },
                    colors: ['var(--color-info)'],
                    fill: {
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            gradientToColors: ['var(--color-base-100)'],
                            opacityTo: 0.3,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: {
                        type: 'category',
                        tickPlacement: 'on',
                        categories: Object.keys(<?php echo json_encode($chart_data); ?>),
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        crosshairs: {
                            stroke: {
                                dashArray: 0
                            },
                            dropShadow: {
                                show: false
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        labels: {
                            style: {
                                colors: 'var(--color-base-content)',
                                fontSize: '12px',
                                fontWeight: 400
                            },
                            formatter: function(title) {
                                return title;
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            align: 'left',
                            minWidth: 0,
                            maxWidth: 140,
                            style: {
                                colors: 'var(--color-base-content)',
                                fontSize: '12px',
                                fontWeight: 400
                            },
                            formatter: function(value) {
                                return value >= 1000 ? (value / 1000) + 'k' : value;
                            }
                        }
                    },
                    tooltip: {
                        x: { show: true },
                        y: { show: true },
                        custom: function(props) {
                            return buildTooltip(props, {
                                title: 'Locações',
                                valuePrefix: '',
                                hasTextLabel: true,
                                wrapperExtClasses: '',
                                markerExtClasses: ''
                            });
                        }
                    },
                    responsive: [
                        {
                            breakpoint: 568,
                            options: {
                                chart: { height: 300 },
                                labels: {
                                    style: {
                                        fontSize: '10px',
                                        colors: 'var(--color-base-content)'
                                    },
                                    offsetX: -2
                                },
                                yaxis: {
                                    labels: {
                                        align: 'left',
                                        minWidth: 0,
                                        maxWidth: 140,
                                        style: {
                                            fontSize: '10px',
                                            colors: 'var(--color-base-content)'
                                        },
                                        formatter: function(value) {
                                            return value >= 1000 ? (value / 1000) + 'k' : value;
                                        }
                                    }
                                }
                            }
                        }
                    ]
                }));

                // Gráfico de Previsão de maior movimento - Estilo FlyonUI
                buildChart('#previsaoChart', () => ({
                    chart: {
                        height: 400,
                        type: 'area',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        }
                    },
                    series: [
                        {
                            name: 'Entradas',
                            data: Object.values(<?php echo json_encode($previsao_entradas); ?>)
                        },
                        {
                            name: 'Saídas',
                            data: Object.values(<?php echo json_encode($previsao_saidas); ?>)
                        }
                    ],
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'right',
                        labels: {
                            useSeriesColors: true
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    grid: {
                        strokeDashArray: 2,
                        borderColor: 'color-mix(in oklab, var(--color-base-content) 40%, transparent)'
                    },
                    colors: ['var(--color-info)', 'var(--color-accent)'],
                    fill: {
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            gradientToColors: ['var(--color-base-100)'],
                            opacityTo: 0.3,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: {
                        type: 'category',
                        tickPlacement: 'on',
                        categories: Object.keys(<?php echo json_encode($previsao_data); ?>),
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        crosshairs: {
                            stroke: {
                                dashArray: 0
                            },
                            dropShadow: {
                                show: false
                            }
                        },
                        tooltip: {
                            enabled: false
                        },
                        labels: {
                            style: {
                                colors: 'var(--color-base-content)',
                                fontSize: '12px',
                                fontWeight: 400
                            },
                            formatter: function(title) {
                                return title;
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            align: 'left',
                            minWidth: 0,
                            maxWidth: 140,
                            style: {
                                colors: 'var(--color-base-content)',
                                fontSize: '12px',
                                fontWeight: 400
                            },
                            formatter: function(value) {
                                return value >= 1000 ? (value / 1000) + 'k' : value;
                            }
                        }
                    },
                    tooltip: {
                        x: { show: true },
                        y: { show: true },
                        custom: function(props) {
                            return buildTooltipCompareTwoAlt(props, {
                                title: 'Movimento',
                                valuePrefix: '',
                                hasTextLabel: true,
                                wrapperExtClasses: '',
                                markerExtClasses: ''
                            });
                        }
                    },
                    responsive: [
                        {
                            breakpoint: 568,
                            options: {
                                chart: { height: 300 },
                                labels: {
                                    style: {
                                        fontSize: '10px',
                                        colors: 'var(--color-base-content)'
                                    },
                                    offsetX: -2
                                },
                                yaxis: {
                                    labels: {
                                        align: 'left',
                                        minWidth: 0,
                                        maxWidth: 140,
                                        style: {
                                            fontSize: '10px',
                                            colors: 'var(--color-base-content)'
                                        },
                                        formatter: function(value) {
                                            return value >= 1000 ? (value / 1000) + 'k' : value;
                                        }
                                    }
                                }
                            }
                        }
                    ]
                }));
            })();
        });
    </script>
    <?php endif; ?>

    <?php include 'components/footer.php'; ?>
