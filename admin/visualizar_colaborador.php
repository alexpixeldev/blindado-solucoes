<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas usuários Administrativo e Gerente podem acessar
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $id = $_SESSION['usuario_id'];
}

$usuario_id = $id;

// Buscar dados do colaborador
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$colaborador = $stmt->get_result()->fetch_assoc();
$stmt->close();

// DEBUG: Verificar campos de arquivos
error_log("DEBUG Campos do colaborador: " . print_r([
    'foto_colaborador' => $colaborador['foto_colaborador'] ?? 'NULL',
    'arquivo_rg' => $colaborador['arquivo_rg'] ?? 'NULL',
    'arquivo_cpf' => $colaborador['arquivo_cpf'] ?? 'NULL'
], true));

if (!$colaborador) {
    header("Location: listar_colaboradores.php");
    exit();
}

// Buscar contagens para os cards (Padrão da colaboradores.php)
$total_contracheques = 0;
$total_faltas = 0;
$total_ferias = 0;
$total_extras = 0;
$total_advertencias = 0;
$total_suspensões = 0;

$recent_activities = [];

try {
    $res = $conn->query("SELECT COUNT(*) as c FROM contracheques WHERE usuario_id = $id");
    if ($res) $total_contracheques = $res->fetch_assoc()["c"];

    $res = $conn->query("SELECT COUNT(*) as c FROM faltas WHERE usuario_id = $id");
    if ($res) $total_faltas = $res->fetch_assoc()["c"];

    $res = $conn->query("SELECT COUNT(*) as c FROM ferias WHERE usuario_id = $id");
    if ($res) $total_ferias = $res->fetch_assoc()["c"];

    $res = $conn->query("SELECT COUNT(*) as c FROM extras WHERE usuario_id = $id");
    if ($res) $total_extras = $res->fetch_assoc()["c"];

    // Contagem de advertências
    $res = $conn->query("SELECT COUNT(*) as c FROM acoes_disciplinares WHERE usuario_id = $id AND (tipo = 'advertencia' OR tipo = 'advertência' OR tipo = 'Advertência' OR tipo = 'Advertencia')");
    if ($res) $total_advertencias = $res->fetch_assoc()["c"];

    // Contagem de suspensões
    $res = $conn->query("SELECT COUNT(*) as c FROM acoes_disciplinares WHERE usuario_id = $id AND (tipo = 'suspensao' OR tipo = 'suspensão' OR tipo = 'Suspensão' OR tipo = 'Suspensao')");
    if ($res) $total_suspensões = $res->fetch_assoc()["c"];

    // DEBUG: Informações do ambiente
    error_log("DEBUG PHP Version: " . PHP_VERSION);
    error_log("DEBUG MySQL Version: " . $conn->server_info);
    error_log("DEBUG ID do colaborador: " . $id);
    
    // Buscar e unificar informações recentes
    // Contracheques
    $stmt = $conn->prepare("SELECT ano, mes FROM contracheques WHERE usuario_id = ? ORDER BY ano DESC, mes DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contracheques_count = 0;
    while ($row = $result->fetch_assoc()) {
        $contracheques_count++;
        $recent_activities[] = [
            'type' => 'Contracheque',
            'date' => date('Y-m-d', strtotime($row['ano'] . '-' . $row['mes'] . '-01')),
            'description' => 'Contracheque Mensal',
            'icon' => 'fa-file-invoice-dollar',
            'color' => 'blue'
        ];
    }
    $stmt->close();
    error_log("DEBUG Contracheques encontrados: " . $contracheques_count);

    // Faltas
    $stmt = $conn->prepare("SELECT data_registro, motivo FROM faltas WHERE usuario_id = ? ORDER BY data_registro DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = [
            'type' => 'Falta',
            'date' => $row['data_registro'],
            'description' => htmlspecialchars($row['motivo']),
            'icon' => 'fa-user-clock',
            'color' => 'red'
        ];
    }
    $stmt->close();

    // Férias
    $stmt = $conn->prepare("SELECT data_inicio, data_fim FROM ferias WHERE usuario_id = ? ORDER BY data_inicio DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = [
            'type' => 'Férias',
            'date' => $row['data_inicio'],
            'description' => 'Período de ' . date('d/m/Y', strtotime($row['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($row['data_fim'])),
            'icon' => 'fa-umbrella-beach',
            'color' => 'green'
        ];
    }
    $stmt->close();

    // Extras
    $stmt = $conn->prepare("SELECT data_extra, local FROM extras WHERE usuario_id = ? ORDER BY data_extra DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = [
            'type' => 'Extra',
            'date' => $row['data_extra'],
            'description' => 'Local: ' . htmlspecialchars($row['local']),
            'icon' => 'fa-clock',
            'color' => 'purple'
        ];
    }
    $stmt->close();

    // Ações Disciplinares (Advertências/Suspensões)
    $stmt = $conn->prepare("SELECT data_registro, tipo, motivo FROM acoes_disciplinares WHERE usuario_id = ? ORDER BY data_registro DESC LIMIT 5");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = [
            'type' => ucfirst($row['tipo']),
            'date' => $row['data_registro'],
            'description' => htmlspecialchars($row['motivo']),
            'icon' => (strpos(strtolower($row['tipo']), 'suspens') !== false) ? 'fa-ban' : 'fa-exclamation-triangle',
            'color' => (strpos(strtolower($row['tipo']), 'suspens') !== false) ? 'red' : 'amber'
        ];
    }
    $stmt->close();

    // Ordenar todas as atividades recentes cronologicamente (mais recente primeiro)
    usort($recent_activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    // Limitar a um número razoável de itens para exibição
    $recent_activities = array_slice($recent_activities, 0, 10);

} catch (Exception $e) {
    error_log("Erro ao buscar informações recentes: " . $e->getMessage());
    $recent_activities = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Colaborador | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
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
                <div class="mb-8 animate-fade-in flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="listar_colaboradores.php" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Perfil do Colaborador</h1>
                            <p class="mt-1 text-slate-500">Informações detalhadas de <?= htmlspecialchars($colaborador['nome_real'] ?? $colaborador['nome']) ?>.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="editar_colaborador.php?id=<?= $colaborador['id'] ?>" class="btn-primary">
                            <i class="fas fa-edit"></i>
                            <span>Editar Dados</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Grid (Padronizado com colaboradores.php) -->
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-8 animate-slide-up">
                    <a href="ver_contracheques.php?id=<?= $id ?>" class="admin-card p-4 block hover:border-primary-300 transition-all cursor-pointer">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <span class="text-2xl font-bold text-slate-900"><?= $total_contracheques ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Contracheques</span>
                        </div>
                    </a>
                    <a href="ver_faltas.php?id=<?= $id ?>" class="admin-card p-4 block hover:border-primary-300 transition-all cursor-pointer">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <span class="text-2xl font-bold text-slate-900"><?= $total_faltas ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Faltas</span>
                        </div>
                    </a>
                    <a href="ver_ferias.php?id=<?= $id ?>" class="admin-card p-4 block hover:border-primary-300 transition-all cursor-pointer">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="h-10 w-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                                <i class="fas fa-umbrella-beach"></i>
                            </div>
                            <span class="text-2xl font-bold text-slate-900"><?= $total_ferias ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Férias</span>
                        </div>
                    </a>
                    <a href="ver_extras.php?id=<?= $id ?>" class="admin-card p-4 block hover:border-primary-300 transition-all cursor-pointer">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="text-2xl font-bold text-slate-900"><?= $total_extras ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Extras</span>
                        </div>
                    </a>
                    <a href="ver_disciplina.php?id=<?= $id ?>" class="admin-card p-4 block hover:border-primary-300 transition-all cursor-pointer">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="h-10 w-10 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <span class="text-2xl font-bold text-slate-900"><?= $total_advertencias ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Advertências</span>
                        </div>
                    </a>
                    <a href="ver_disciplina.php?id=<?= $id ?>" class="admin-card p-4 block hover:border-primary-300 transition-all cursor-pointer">
                        <div class="flex flex-col items-center text-center gap-2">
                            <div class="h-10 w-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                                <i class="fas fa-ban"></i>
                            </div>
                            <span class="text-2xl font-bold text-slate-900"><?= $total_suspensões ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Suspensões</span>
                        </div>
                    </a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-slide-up">
                    <div class="lg:col-span-1 space-y-8">
                        <div class="admin-card">
                            <div class="flex flex-col items-center text-center pb-6 border-b border-slate-100 mb-6">
                                <?php if (!empty($colaborador['foto_colaborador'])): ?>
                                    <img src="../uploads/colaboradores/<?= htmlspecialchars($colaborador['foto_colaborador']) ?>" alt="<?= htmlspecialchars($colaborador['nome_real'] ?? $colaborador['nome']) ?>" class="h-24 w-24 rounded-full object-cover border-4 border-white shadow-sm mb-4">
                                <?php else: ?>
                                    <div class="h-24 w-24 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-3xl font-bold mb-4 border-4 border-white shadow-sm">
                                        <?= strtoupper(substr($colaborador['nome_real'] ?? $colaborador['nome'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <h2 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($colaborador['nome_real'] ?? 'Não informado') ?></h2>
                                <span class="mt-1 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-wider">
                                    <?= htmlspecialchars($colaborador['categoria']) ?>
                                </span>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Usuário (Login)</label>
                                    <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($colaborador['nome']) ?></p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ID do Sistema</label>
                                    <p class="text-sm font-medium text-slate-700">#<?= str_pad($colaborador['id'], 4, '0', STR_PAD_LEFT) ?></p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">RG</label>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($colaborador['rg'] ?? 'Não informado') ?></p>
                                        <?php if (!empty($colaborador['arquivo_rg'])): ?>
                                            <a href="../uploads/colaboradores/<?= htmlspecialchars($colaborador['arquivo_rg']) ?>" target="_blank" class="text-blue-600 hover:text-blue-700 text-xs font-medium">
                                                <i class="fas fa-file-pdf"></i> Ver RG
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">CPF</label>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($colaborador['cpf'] ?? 'Não informado') ?></p>
                                        <?php if (!empty($colaborador['arquivo_cpf'])): ?>
                                            <a href="../uploads/colaboradores/<?= htmlspecialchars($colaborador['arquivo_cpf']) ?>" target="_blank" class="text-blue-600 hover:text-blue-700 text-xs font-medium">
                                                <i class="fas fa-file-pdf"></i> Ver CPF
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data de Admissão</label>
                                    <p class="text-sm font-medium text-slate-700"><?= !empty($colaborador['data_admissao']) ? date('d/m/Y', strtotime($colaborador['data_admissao'])) : 'Não informada' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-8">
                        <div class="admin-card p-0 overflow-hidden">
                            <div class="p-6 border-b border-slate-100">
                                <h3 class="font-bold text-slate-900">Informações Recentes</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <?php if (empty($recent_activities)): ?>
                                    <p class="text-center py-8 text-slate-400 italic">Nenhuma informação recente disponível.</p>
                                <?php else: ?>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="flex items-start gap-3">
                                            <div class="h-8 w-8 rounded-full bg-<?= $activity['color'] ?>-50 text-<?= $activity['color'] ?>-600 flex items-center justify-center flex-shrink-0">
                                                <i class="fas <?= $activity['icon'] ?> text-sm"></i>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-slate-900"><?= htmlspecialchars($activity['type']) ?> em <?= date('d/m/Y', strtotime($activity['date'])) ?></p>
                                                <p class="text-xs text-slate-500"><?= htmlspecialchars($activity['description']) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
