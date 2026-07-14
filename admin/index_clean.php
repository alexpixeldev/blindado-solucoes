<?php

require_once 'config.php';
require_once 'Database.php';
require_once 'Auth.php';

Auth::requireAuth();
Auth::redirectIf('colaborador', 'colaboradores.php');

$pageTitle = 'Dashboard';
$usuario = Auth::user();

$mes_selecionado = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: date('n');
$ano_selecionado = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: date('Y');

$stats = [];
$chart_data = [];
$previsao_entradas = [];
$previsao_saidas = [];

if (Auth::isOneOf(['gerente', 'supervisor'])) {
    $db = Database::getInstance();
    
    $stats['total_edificios'] = $db->fetchOne("SELECT COUNT(*) as total FROM edificios")['total'] ?? 0;
    $stats['total_locacoes'] = $db->fetchOne("SELECT COUNT(*) as total FROM locacoes")['total'] ?? 0;
    $stats['total_entregas'] = $db->fetchOne("SELECT COUNT(*) as total FROM entregas")['total'] ?? 0;
    
    $dias_no_mes = date('t', mktime(0, 0, 0, $mes_selecionado, 1, $ano_selecionado));
    
    for ($i = 1; $i <= $dias_no_mes; $i++) {
        $chart_data[$i] = 0;
        $previsao_entradas[$i] = 0;
        $previsao_saidas[$i] = 0;
    }
    
    $locacoes_por_dia = $db->fetchAll("
        SELECT DAY(data_criacao) as dia, COUNT(*) as total 
        FROM locacoes 
        WHERE MONTH(data_criacao) = ? AND YEAR(data_criacao) = ?
        GROUP BY DAY(data_criacao)
        ORDER BY dia
    ", [$mes_selecionado, $ano_selecionado]);
    
    foreach ($locacoes_por_dia as $row) {
        $chart_data[(int)$row['dia']] = (int)$row['total'];
    }
    
    $entradas_por_dia = $db->fetchAll("
        SELECT DAY(data_entrada) as dia, COUNT(*) as total_entradas
        FROM locacoes 
        WHERE MONTH(data_entrada) = ? AND YEAR(data_entrada) = ?
        GROUP BY DAY(data_entrada)
        ORDER BY dia
    ", [$mes_selecionado, $ano_selecionado]);
    
    foreach ($entradas_por_dia as $row) {
        $previsao_entradas[(int)$row['dia']] = (int)$row['total_entradas'];
    }
    
    $saidas_por_dia = $db->fetchAll("
        SELECT DAY(data_saida) as dia, COUNT(*) as total_saidas
        FROM locacoes 
        WHERE MONTH(data_saida) = ? AND YEAR(data_saida) = ?
        GROUP BY DAY(data_saida)
        ORDER BY dia
    ", [$mes_selecionado, $ano_selecionado]);
    
    foreach ($saidas_por_dia as $row) {
        $previsao_saidas[(int)$row['dia']] = (int)$row['total_saidas'];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <?php include 'components/dark_mode_header.php'; ?>
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Dashboard</h1>
                    <p class="mt-1 text-slate-500">Bem-vindo(a), <?= $usuario['nome'] ?>!</p>
                </div>

                <?php if (Auth::isOneOf(['gerente', 'supervisor'])): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="admin-card animate-slide-up">
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="fas fa-building text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-slate-600">Edifícios</p>
                                    <p class="text-2xl font-bold text-slate-900"><?= $stats['total_edificios'] ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card animate-slide-up" style="animation-delay: 0.1s;">
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                                    <i class="fas fa-home text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-slate-600">Locações</p>
                                    <p class="text-2xl font-bold text-slate-900"><?= $stats['total_locacoes'] ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card animate-slide-up" style="animation-delay: 0.2s;">
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                    <i class="fas fa-box text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-slate-600">Entregas</p>
                                    <p class="text-2xl font-bold text-slate-900"><?= $stats['total_entregas'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="admin-card animate-slide-up" style="animation-delay: 0.3s;">
                            <div class="mb-4 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-slate-900">Locações por Dia</h3>
                                <form method="GET" class="flex gap-2">
                                    <select name="mes" class="form-input text-sm" onchange="this.form.submit()">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= $m == $mes_selecionado ? 'selected' : '' ?>>
                                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="ano" class="form-input text-sm" onchange="this.form.submit()">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                            <option value="<?= $y ?>" <?= $y == $ano_selecionado ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </form>
                            </div>
                            <div id="chartLocacoes"></div>
                        </div>

                        <div class="admin-card animate-slide-up" style="animation-delay: 0.4s;">
                            <div class="mb-4">
                                <h3 class="text-lg font-bold text-slate-900">Previsão de Movimento</h3>
                            </div>
                            <div id="chartPrevisao"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
            <?php include 'components/base_footer.php'; ?>
        </div>
    </div>

    <?php if (Auth::isOneOf(['gerente', 'supervisor'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            const chartData = <?= json_encode(array_values($chart_data)) ?>;
            const previsaoEntradas = <?= json_encode(array_values($previsao_entradas)) ?>;
            const previsaoSaidas = <?= json_encode(array_values($previsao_saidas)) ?>;
            const diasNoMes = <?= $dias_no_mes ?>;

            const optionsLocacoes = {
                series: [{
                    name: 'Locações',
                    data: chartData
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: false }
                },
                stroke: { curve: 'smooth', width: 3 },
                markers: { size: 5, colors: ['#22c55e'] },
                xaxis: {
                    categories: Array.from({length: diasNoMes}, (_, i) => i + 1)
                }
            };

            const optionsPrevisao = {
                series: [
                    { name: 'Entradas', data: previsaoEntradas },
                    { name: 'Saídas', data: previsaoSaidas }
                ],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: false }
                },
                stroke: { curve: 'smooth', width: 3 },
                markers: { size: 4 },
                xaxis: {
                    categories: Array.from({length: diasNoMes}, (_, i) => i + 1)
                }
            };

            new ApexCharts(document.querySelector("#chartLocacoes"), optionsLocacoes).render();
            new ApexCharts(document.querySelector("#chartPrevisao"), optionsPrevisao).render();
        </script>
    <?php endif; ?>
</body>
</html>
