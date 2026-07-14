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

$stats = [];
$mes_selecionado = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: date('n');
$ano_selecionado = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: date('Y');

if (in_array($usuario_categoria, ['gerente', 'supervisor'])) {
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
        WHERE MONTH(data_criacao) = $mes_selecionado 
        AND YEAR(data_criacao) = $ano_selecionado
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
        WHERE MONTH(data_entrada) = $mes_selecionado 
        AND YEAR(data_entrada) = $ano_selecionado
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
        WHERE MONTH(data_saida) = $mes_selecionado 
        AND YEAR(data_saida) = $ano_selecionado
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
    
    // Buscar dados para o gráfico de locações por dia do mês
    $chart_data = [];
    
    // Buscar todos os dias do mês selecionado
    $dias_no_mes = date('t', mktime(0, 0, 0, $mes_selecionado, $ano_selecionado)); // número de dias no mês selecionado
    
    for ($i = 1; $i <= $dias_no_mes; $i++) {
        $chart_data[$i] = 0; // inicializar todos os dias com 0
    }
    
    $result = $conn->query("
        SELECT DAY(data_criacao) as dia, COUNT(*) as total 
        FROM locacoes 
        WHERE MONTH(data_criacao) = $mes_selecionado 
        AND YEAR(data_criacao) = $ano_selecionado
        GROUP BY DAY(data_criacao)
        ORDER BY dia
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chart_data[(int)$row['dia']] = (int)$row['total'];
        }
    }
    
    // Buscar dados para o gráfico de previsão (baseado em check-ins e check-outs)
    $previsao_data = [];
    $previsao_entradas = [];
    $previsao_saidas = [];
    
    for ($i = 1; $i <= $dias_no_mes; $i++) {
        $previsao_data[$i] = 0; // inicializar todos os dias com 0
        $previsao_entradas[$i] = 0;
        $previsao_saidas[$i] = 0;
    }
    
    // Contar entradas por dia
    $result_entradas = $conn->query("
        SELECT 
            DAY(data_entrada) as dia,
            COUNT(*) as total_entradas
        FROM locacoes 
        WHERE MONTH(data_entrada) = $mes_selecionado 
        AND YEAR(data_entrada) = $ano_selecionado
        GROUP BY DAY(data_entrada)
        ORDER BY dia
    ");
    
    if ($result_entradas) {
        while ($row = $result_entradas->fetch_assoc()) {
            $previsao_entradas[(int)$row['dia']] = (int)$row['total_entradas'];
        }
    }
    
    // Contar saídas por dia
    $result_saidas = $conn->query("
        SELECT 
            DAY(data_saida) as dia,
            COUNT(*) as total_saidas
        FROM locacoes 
        WHERE MONTH(data_saida) = $mes_selecionado 
        AND YEAR(data_saida) = $ano_selecionado
        GROUP BY DAY(data_saida)
        ORDER BY dia
    ");
    
    if ($result_saidas) {
        while ($row = $result_saidas->fetch_assoc()) {
            $previsao_saidas[(int)$row['dia']] = (int)$row['total_saidas'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Blindado Soluções</title>
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
    
    <!-- Variáveis CSS para ApexCharts -->
    <link rel="stylesheet" href="apexcharts-flyonui.min.css">
    <style>
        :root {
            --color-base-content: #1e293b;
            --color-base-100: #ffffff;
            --color-base-200: #f1f5f9;
            --color-info: #22c55e;
            --color-accent: #3b82f6;
            --color-success: #22c55e;
            --color-error: #ef4444;
        }
    </style>
    
    <!-- Modo Noturno -->
    <?php include 'components/dark_mode_header.php'; ?>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- FlyonUI ApexCharts Helper -->
    <script src="helper-apexcharts.min.js"></script>
</head>
<body class="h-full text-slate-800 antialiased">
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
                </div>
                <?php endif; ?>

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
            </main>
            
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <?php include 'components/base_footer.php'; ?>

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
</html>
