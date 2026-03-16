<?php
session_start();
require_once 'conexao.php';

// Verifica permissão: Colaborador ou Gerente
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_categoria'] !== 'colaborador' && $_SESSION['usuario_categoria'] !== 'gerente')) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

// Buscar nome real do colaborador
$stmt_nome = $conn->prepare("SELECT nome_real FROM usuarios WHERE id = ?");
$stmt_nome->bind_param("i", $usuario_id);
$stmt_nome->execute();
$res_nome = $stmt_nome->get_result();
$nome_real = ($row = $res_nome->fetch_assoc()) && !empty($row['nome_real']) ? $row['nome_real'] : $usuario_nome;
$primeiro_nome = explode(' ', trim($nome_real))[0];
$stmt_nome->close();

// Buscar contracheques do usuário logado
$stmt = $conn->prepare("SELECT * FROM contracheques WHERE usuario_id = ? ORDER BY ano DESC, mes DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$contracheques = fetch_all_assoc($result);
$stmt->close();

// Buscar faltas
$stmt = $conn->prepare("SELECT * FROM faltas WHERE usuario_id = ? ORDER BY data_registro DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$faltas = fetch_all_assoc($result);
$stmt->close();

// Buscar férias
$stmt = $conn->prepare("SELECT * FROM ferias WHERE usuario_id = ? ORDER BY data_inicio DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$ferias = fetch_all_assoc($result);
$stmt->close();

// Buscar extras
$stmt = $conn->prepare("SELECT * FROM extras WHERE usuario_id = ? ORDER BY data_extra DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$extras = fetch_all_assoc($result);
$stmt->close();

$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

/**
 * Função auxiliar para calcular diferença de horas
 */
function calcular_horas($inicio, $fim) {
    $t1 = strtotime($inicio);
    $t2 = strtotime($fim);
    if (!$t1 || !$t2) return '00:00';
    $diff = abs($t2 - $t1);
    $hours = floor($diff / 3600);
    $mins = floor(($diff % 3600) / 60);
    return sprintf('%02d:%02d', $hours, $mins);
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Área | Blindado Soluções</title>
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
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 sm:px-8 sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <img src="../img/escudo.png" alt="Logo" class="h-8 w-8">
                    <span class="font-bold text-slate-900 hidden sm:block">Blindado Soluções</span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($nome_real) ?></p>
                        <p class="text-xs text-slate-500 uppercase tracking-wider">Colaborador</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold">
                        <?= strtoupper(substr($primeiro_nome, 0, 1)) ?>
                    </div>
                    <a href="logout.php" class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all" title="Sair">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <!-- Welcome Section -->
                <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Olá, <?= htmlspecialchars($primeiro_nome) ?> 👋</h1>
                        <p class="mt-1 text-slate-500">Bem-vindo ao seu portal do colaborador. Aqui você encontra suas informações profissionais.</p>
                    </div>
                    <a href="alterar_senha_colaborador.php" class="btn-secondary">
                        <i class="fas fa-key"></i>
                        <span>Alterar Senha</span>
                    </a>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 animate-slide-up">
                    <?php 
                        $stats = [
                            ['label' => 'Contracheques', 'value' => count($contracheques), 'icon' => 'fa-file-invoice-dollar', 'color' => 'blue', 'tab' => 'contracheques'],
                            ['label' => 'Faltas', 'value' => count($faltas), 'icon' => 'fa-user-clock', 'color' => 'red', 'tab' => 'faltas'],
                            ['label' => 'Férias', 'value' => count($ferias), 'icon' => 'fa-umbrella-beach', 'color' => 'green', 'tab' => 'ferias'],
                            ['label' => 'Extras', 'value' => count($extras), 'icon' => 'fa-clock', 'color' => 'purple', 'tab' => 'extras'],
                        ];
                        foreach ($stats as $index => $stat):
                    ?>
                        <div onclick="switchTab('<?= $stat['tab'] ?>')" class="admin-card p-4 cursor-pointer hover:border-primary-300 transition-all group animate-slide-up" style="animation-delay: <?= 0.1 + ($index * 0.05) ?>s">
                            <div class="flex flex-col items-center text-center gap-2">
                                <div class="h-10 w-10 rounded-xl bg-<?= $stat['color'] ?>-50 text-<?= $stat['color'] ?>-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="fas <?= $stat['icon'] ?>"></i>
                                </div>
                                <span class="text-2xl font-bold text-slate-900"><?= $stat['value'] ?></span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= $stat['label'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Main Content Tabs -->
                <div class="animate-slide-up" style="animation-delay: 0.3s">
                    <div class="admin-card p-0 overflow-hidden">
                        <!-- Tabs Navigation -->
                        <div class="flex overflow-x-auto border-b border-slate-100 no-scrollbar">
                            <?php foreach ($stats as $stat): ?>
                                <button onclick="switchTab('<?= $stat['tab'] ?>')" id="tab-btn-<?= $stat['tab'] ?>" class="tab-btn px-6 py-4 text-sm font-bold whitespace-nowrap transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                                    <?= $stat['label'] ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Tabs Content -->
                        <div class="p-6">
                            <!-- Contracheques -->
                            <div id="tab-content-contracheques" class="tab-content hidden">
                                <div class="overflow-x-auto">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Mês/Ano</th>
                                                <th>Descrição</th>
                                                <th class="text-right">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($contracheques)): ?>
                                                <tr><td colspan="3" class="text-center py-8 text-slate-400 italic">Nenhum contracheque disponível.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($contracheques as $c): ?>
                                                    <tr>
                                                        <td class="font-bold text-slate-900"><?= $meses[$c['mes']] ?> / <?= $c['ano'] ?></td>
                                                        <td class="text-slate-500"><?= htmlspecialchars($c['descricao'] ?: 'Contracheque Mensal') ?></td>
                                                        <td class="text-right">
                                                            <a href="../uploads/contracheques/<?= $c['arquivo'] ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary-50 text-primary-700 rounded-lg font-bold text-xs hover:bg-primary-600 hover:text-white transition-all">
                                                                <i class="fas fa-download"></i>
                                                                <span>Baixar PDF</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Faltas -->
                            <div id="tab-content-faltas" class="tab-content hidden">
                                <div class="overflow-x-auto">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Motivo</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($faltas)): ?>
                                                <tr><td colspan="3" class="text-center py-8 text-slate-400 italic">Nenhuma falta registrada.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($faltas as $f): ?>
                                                    <tr>
                                                        <td class="font-bold text-slate-900"><?= date('d/m/Y', strtotime($f['data_registro'])) ?></td>
                                                        <td class="text-slate-500"><?= htmlspecialchars($f['motivo']) ?></td>
                                                        <td>
                                                            <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= $f['justificada'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                                <?= $f['justificada'] ? 'Justificada' : 'Não Justificada' ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Férias -->
                            <div id="tab-content-ferias" class="tab-content hidden">
                                <div class="overflow-x-auto">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Período</th>
                                                <th>Status</th>
                                                <th class="text-right">Documento</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($ferias)): ?>
                                                <tr><td colspan="3" class="text-center py-8 text-slate-400 italic">Nenhum registro de férias.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($ferias as $fe): ?>
                                                    <tr>
                                                        <td class="font-bold text-slate-900"><?= date('d/m/Y', strtotime($fe['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($fe['data_fim'])) ?></td>
                                                        <td>
                                                            <?php 
                                                                $hoje = date('Y-m-d');
                                                                $status = ($hoje >= $fe['data_inicio'] && $hoje <= $fe['data_fim']) ? 'Em curso' : ($hoje > $fe['data_fim'] ? 'Concluída' : 'Agendada');
                                                                $color = ($status === 'Em curso') ? 'bg-green-100 text-green-700' : (($status === 'Concluída') ? 'bg-slate-100 text-slate-700' : 'bg-blue-100 text-blue-700');
                                                            ?>
                                                            <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= $color ?>">
                                                                <?= $status ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-right">
                                                            <a href="../uploads/ferias/<?= $fe['arquivo'] ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary-50 text-primary-700 rounded-lg font-bold text-xs hover:bg-primary-600 hover:text-white transition-all">
                                                                <i class="fas fa-eye"></i>
                                                                <span>Ver</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Extras -->
                            <div id="tab-content-extras" class="tab-content hidden">
                                <div class="overflow-x-auto">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Horas</th>
                                                <th>Local</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($extras)): ?>
                                                <tr><td colspan="3" class="text-center py-8 text-slate-400 italic">Nenhum registro de horas extras.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($extras as $ex): ?>
                                                    <tr>
                                                        <td class="font-bold text-slate-900"><?= date('d/m/Y', strtotime($ex['data_extra'])) ?></td>
                                                        <td class="font-mono font-bold text-primary-600"><?= calcular_horas($ex['hora_inicio'], $ex['hora_fim']) ?>h</td>
                                                        <td class="text-slate-500 text-sm"><?= htmlspecialchars($ex['local']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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

    <script>
        function switchTab(tabId) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active state from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-primary-500', 'text-primary-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });
            
            // Show selected content
            document.getElementById('tab-content-' + tabId).classList.remove('hidden');
            
            // Set active state to selected button
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-primary-500', 'text-primary-600');
            }
            
            // Save preference
            localStorage.setItem('colab_active_tab', tabId);
        }

        // Initialize with saved tab or first tab
        document.addEventListener('DOMContentLoaded', () => {
            const savedTab = localStorage.getItem('colab_active_tab') || 'contracheques';
            // Verify if tab exists, otherwise fallback to contracheques
            if (document.getElementById('tab-btn-' + savedTab)) {
                switchTab(savedTab);
            } else {
                switchTab('contracheques');
            }
        });
    </script>
</body>
</html>
