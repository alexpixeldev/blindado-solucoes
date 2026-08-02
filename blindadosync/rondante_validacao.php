<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['rondante', 'gerente', 'diretor', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

// Para rondante: o plantão dele vai das 19:00 de um dia até as 05:50 do dia seguinte.
// A data do plantão é a data do dia em que começou (>= 19:00).
function ronda_data_plantao() {
    $h = (int)date('H');
    $i = (int)date('i');
    if ($h >= 19) {
        return date('Y-m-d');
    }
    if ($h < 5 || ($h == 5 && $i <= 50)) {
        return date('Y-m-d', strtotime('-1 day'));
    }
    return date('Y-m-d', strtotime('-1 day')); // fora da janela: usa o plantão anterior (que iniciou ontem às 19:00)
}

$is_rondante = ($usuario_categoria === 'rondante');

if ($is_rondante) {
    $rondante_id = $_SESSION['usuario_id'];
    $data_filtro = ronda_data_plantao();
} else {
    $data_filtro = $_GET['data'] ?? date('Y-m-d');
    $rondante_id = isset($_GET['rondante_id']) ? intval($_GET['rondante_id']) : 0;
}

// Lista de rondantes (para o filtro em gerente/supervisor)
$rondantes = [];
if (!$is_rondante && in_array($usuario_categoria, ['gerente', 'diretor', 'supervisor'])) {
    $rondantes = $conn->query("SELECT id, nome_real, nome FROM usuarios WHERE categoria = 'rondante' ORDER BY nome_real")->fetch_all(MYSQLI_ASSOC);
    if ($rondante_id === 0 && !empty($rondantes)) {
        $rondante_id = intval($rondantes[0]['id']);
    }
}

// Edifícios (checklist completo)
$edificios = $conn->query("SELECT e.id, e.nome, b.nome AS nome_base, e.retirada_lixo
                           FROM edificios e JOIN bases b ON e.base_id = b.id
                           ORDER BY b.nome, e.nome")->fetch_all(MYSQLI_ASSOC);

// Escaneamentos do rondante na data selecionada
$scans = [];
$stmt = $conn->prepare("SELECT re.edificio_id, re.escaneado_em, re.interfones_ok, re.lixo_retirado, r.id AS ronda_id, r.hora_inicio, r.hora_fim
                        FROM ronda_escaneamentos re
                        JOIN rondas r ON re.ronda_id = r.id
                        WHERE r.usuario_id = ? AND r.data_ronda = ?
                        ORDER BY re.escaneado_em ASC");
$stmt->bind_param('is', $rondante_id, $data_filtro);
$stmt->execute();
$scans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Índice por edifício
$scans_por_edificio = [];
foreach ($scans as $s) {
    $scans_por_edificio[$s['edificio_id']][] = $s;
}

// Primeira e última escaneada
$primeira = !empty($scans) ? $scans[0]['escaneado_em'] : null;
$ultima = !empty($scans) ? $scans[count($scans) - 1]['escaneado_em'] : null;

// Rondas da data
$rondas = [];
$stmt = $conn->prepare("SELECT r.*, u.nome_real FROM rondas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.usuario_id = ? AND r.data_ronda = ? ORDER BY r.id ASC");
$stmt->bind_param('is', $rondante_id, $data_filtro);
$stmt->execute();
$rondas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_edificios = count($edificios);
$total_escaneados = count(array_filter(array_map(function($e) use ($scans_por_edificio) {
    return !empty($scans_por_edificio[$e['id']]);
}, $edificios)));
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Ronda | Rondante</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">

    <!-- Tailwind CSS -->



    <!-- Google Fonts & Font Awesome -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <div class="flex flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="flex-1 p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Validação de Ronda</h1>
                        <p class="mt-1 text-slate-500">Confira quais edifícios o rondante visitou e escaneou o QR code.</p>
                    </div>
                    <a href="rondante.php" class="icon-btn" title="Voltar ao Rondante"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                </div>

                <!-- Submenus -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <a href="rondante.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Ronda Atual</a>
                    <?php if (!$is_rondante): ?>
                    <a href="rondante_qrcodes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">QR Codes</a>
                    <?php endif; ?>
                    <a href="rondante_validacao.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-primary-500 text-primary-600">Validação de Ronda</a>
                </div>

                <?php if (!$is_rondante): ?>
                <!-- Filtros -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Data</label>
                                <input type="date" name="data" class="form-input" value="<?= htmlspecialchars($data_filtro) ?>">
                            </div>
                            <?php if (in_array($usuario_categoria, ['gerente', 'diretor', 'supervisor'])): ?>
                                <div class="space-y-2">
                                    <label class="form-label">Rondante</label>
                                    <select name="rondante_id" class="form-input">
                                        <?php foreach ($rondantes as $r): ?>
                                            <option value="<?= $r['id'] ?>" <?= $rondante_id == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nome_real'] ?: $r['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="icon-btn-blue" title="Filtrar"><i class="fas fa-filter" style="font-size:10px"></i></button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$is_rondante): ?>
                <!-- Resumo -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 animate-slide-up">
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Edifícios visitados</p>
                        <p class="mt-1 text-2xl font-bold <?= $total_escaneados === $total_edificios && $total_edificios > 0 ? 'text-green-600' : 'text-slate-900' ?>">
                            <?= $total_escaneados ?>/<?= $total_edificios ?>
                        </p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Primeira escaneada</p>
                        <p class="mt-1 text-sm font-bold text-slate-900"><?= $primeira ? date('d/m/Y H:i:s', strtotime($primeira)) : '<span class="text-red-500">Sem escaneamentos</span>' ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Última escaneada</p>
                        <p class="mt-1 text-sm font-bold text-slate-900"><?= $ultima ? date('d/m/Y H:i:s', strtotime($ultima)) : '<span class="text-red-500">Sem escaneamentos</span>' ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Rondas no dia</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= count($rondas) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Checklist -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="admin-card">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-lg font-bold text-slate-900">Checklist de Escaneamento</h2>
                            <?php if ($is_rondante): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary-700">
                                    <i class="fas fa-moon"></i> Plantão de <?= date('d/m/Y', strtotime($data_filtro)) ?> (19:00 às 05:50)
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($edificios)): ?>
                            <div class="text-center py-12 text-slate-500 italic">Nenhum edifício cadastrado.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="admin-table" data-no-cell-copy>
                                    <thead>
                                        <tr>
                                            <th>Edifício</th>
                                            <th>Base</th>
                                            <th>Status</th>
                                            <th>Horário do escaneamento</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($edificios as $ed): ?>
                                            <?php
                                                $scans_ed = $scans_por_edificio[$ed['id']] ?? [];
                                                $escaneado = !empty($scans_ed);
                                                $todos_horarios = array_map(function($s) { return date('H:i:s', strtotime($s['escaneado_em'])); }, $scans_ed);
                                                $interfones_ok = !empty($scans_ed) ? $scans_ed[0]['interfones_ok'] : 0;
                                                $lixo_retirado = !empty($scans_ed) ? $scans_ed[0]['lixo_retirado'] : 0;
                                            ?>
                                            <tr class="<?= $escaneado ? 'bg-green-50/50' : '' ?>">
                                                <td class="font-semibold text-slate-900"><?= htmlspecialchars($ed['nome']) ?></td>
                                                <td class="text-slate-500 text-sm"><?= htmlspecialchars($ed['nome_base']) ?></td>
                                                <td>
                                                    <?php if ($escaneado): ?>
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-green-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">
                                                            <i class="fas fa-check-circle"></i> Escaneado
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-red-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700">
                                                            <i class="fas fa-times-circle"></i> Não escaneado
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-slate-500 text-sm">
                                                    <?= $escaneado ? implode(' / ', $todos_horarios) : '<span class="text-red-500">—</span>' ?>
                                                </td>
                                                <td class="text-xs text-slate-500">
                                                    <?php if ($escaneado): ?>
                                                        <span class="inline-flex flex-wrap gap-1">
                                                            <span class="rounded bg-<?= $interfones_ok ? 'green' : 'slate' ?>-100 px-1.5 py-0.5 text-[10px] font-bold text-<?= $interfones_ok ? 'green' : 'slate' ?>-700">Interfones: <?= $interfones_ok ? 'OK' : '—' ?></span>
                                                            <?php if ($ed['retirada_lixo']): ?>
                                                                <span class="rounded bg-<?= $lixo_retirado ? 'green' : 'slate' ?>-100 px-1.5 py-0.5 text-[10px] font-bold text-<?= $lixo_retirado ? 'green' : 'slate' ?>-700">Lixo: <?= $lixo_retirado ? 'Sim' : 'Não' ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$is_rondante): ?>
                <!-- Rondas -->
                <div class="mt-6 animate-slide-up" style="animation-delay: 0.2s;">
                    <div class="admin-card">
                        <h2 class="mb-4 text-lg font-bold text-slate-900">Rondas do dia</h2>
                        <?php if (empty($rondas)): ?>
                            <div class="text-center py-8 text-slate-500 italic">Nenhuma ronda registrada nesta data.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="admin-table" data-no-cell-copy>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Início</th>
                                            <th>Fim</th>
                                            <th>Duração</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rondas as $i => $r): ?>
                                            <tr>
                                                <td class="font-bold text-slate-900"><?= $i + 1 ?></td>
                                                <td class="text-slate-500 text-sm"><?= date('d/m/Y H:i:s', strtotime($r['hora_inicio'])) ?></td>
                                                <td class="text-slate-500 text-sm"><?= $r['hora_fim'] ? date('d/m/Y H:i:s', strtotime($r['hora_fim'])) : '<span class="text-amber-500">Em andamento</span>' ?></td>
                                                <td class="text-slate-500 text-sm">
                                                    <?php
                                                        if ($r['hora_fim']) {
                                                            $diff = strtotime($r['hora_fim']) - strtotime($r['hora_inicio']);
                                                            echo sprintf('%02d:%02d:%02d', floor($diff / 3600), floor(($diff % 3600) / 60), $diff % 60);
                                                        } else {
                                                            echo '—';
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= $r['status'] === 'finalizada' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                                                        <?= $r['status'] === 'finalizada' ? 'Finalizada' : 'Em andamento' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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
