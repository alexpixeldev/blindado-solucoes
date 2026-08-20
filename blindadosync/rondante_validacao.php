<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

$data_filtro = $_GET['data'] ?? date('Y-m-d');
$rondante_id = isset($_GET['rondante_id']) ? intval($_GET['rondante_id']) : 0;

// Excluir relatório de ronda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_ronda'])) {
    $ronda_id = intval($_POST['excluir_ronda']);
    $stmt = $conn->prepare("DELETE FROM ronda_escaneamentos WHERE ronda_id = ?");
    $stmt->bind_param('i', $ronda_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM rondas WHERE id = ?");
    $stmt->bind_param('i', $ronda_id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Relatório de ronda excluído com sucesso!';
        $_SESSION['mensagem_tipo'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir relatório: ' . $conn->error;
        $_SESSION['mensagem_tipo'] = 'error';
    }
    $stmt->close();
    header("Location: rondante_validacao.php?data=" . urlencode($data_filtro) . "&rondante_id=" . $rondante_id);
    exit();
}

// Lista de rondantes para o filtro
$rondantes = $conn->query("SELECT id, nome_real, nome FROM usuarios WHERE categoria = 'rondante' ORDER BY nome_real")->fetch_all(MYSQLI_ASSOC);
if ($rondante_id === 0 && !empty($rondantes)) {
    $rondante_id = intval($rondantes[0]['id']);
}

$rondante_nome = '';
$rondas = [];
if ($rondante_id > 0) {
    $stmt = $conn->prepare("SELECT nome_real, nome FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $rondante_id);
    $stmt->execute();
    $ru = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $rondante_nome = $ru ? ($ru['nome_real'] ?: $ru['nome']) : '';

    $stmt = $conn->prepare("SELECT r.* FROM rondas r WHERE r.usuario_id = ? AND r.data_ronda = ? ORDER BY r.id ASC");
    $stmt->bind_param('is', $rondante_id, $data_filtro);
    $stmt->execute();
    $rondas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Escaneamentos por ronda
$escaneamentos_por_ronda = [];
if (!empty($rondas)) {
    $ids = array_column($rondas, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT re.ronda_id, re.escaneado_em, e.id AS edificio_id, e.nome AS edificio_nome
                            FROM ronda_escaneamentos re
                            JOIN edificios e ON re.edificio_id = e.id
                            WHERE re.ronda_id IN ($placeholders)
                            ORDER BY re.ronda_id ASC, re.escaneado_em ASC");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $escaneamentos_por_ronda[$row['ronda_id']][] = $row;
    }
    $stmt->close();
}

// Nomes ordinais em português
function nome_ordinal_ronda($n) {
    $ordinais = [1 => 'Primeira', 2 => 'Segunda', 3 => 'Terceira', 4 => 'Quarta', 5 => 'Quinta',
                 6 => 'Sexta', 7 => 'Sétima', 8 => 'Oitava', 9 => 'Nona', 10 => 'Décima'];
    if (isset($ordinais[$n])) {
        return $ordinais[$n] . ' Ronda';
    }
    return $n . 'ª Ronda';
}

$total_rondas = count($rondas);
$total_escaneados_total = array_sum(array_map('count', $escaneamentos_por_ronda));
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</noscript>
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <div class="flex min-w-0 flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="min-w-0 flex-1 p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Validação de Ronda</h1>
                        <p class="mt-1 text-slate-500">Lista de rondas realizadas pelo rondante e os edifícios escaneados.</p>
                    </div>
                    <a href="rondante.php" class="icon-btn" title="Voltar ao Rondante"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                </div>

                <!-- Submenus -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <?php if ($usuario_categoria === 'rondante'): ?>
                    <a href="rondante.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Ronda Atual</a>
                    <?php endif; ?>
                    <a href="rondante_qrcodes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">QR Codes</a>
                    <a href="rondante_validacao.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-primary-500 text-primary-600">Validação de Ronda</a>
                    <a href="rondante_gerentes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Gerentes de Ronda</a>
                </div>

                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="mb-6 p-4 <?php echo $_SESSION['mensagem_tipo'] === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $_SESSION['mensagem_tipo'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['mensagem']); ?></div>
                    </div>
                    <?php unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']); ?>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Data</label>
                                <input type="date" name="data" class="form-input" value="<?= htmlspecialchars($data_filtro) ?>">
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Rondante</label>
                                <select name="rondante_id" class="form-input">
                                    <?php foreach ($rondantes as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= $rondante_id == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nome_real'] ?: $r['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="icon-btn-blue" title="Filtrar"><i class="fas fa-filter" style="font-size:10px"></i></button>
                        </form>
                    </div>
                </div>

                <!-- Resumo -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3 animate-slide-up">
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Rondante</p>
                        <p class="mt-1 text-sm font-bold text-slate-900"><?= htmlspecialchars($rondante_nome ?: '—') ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Rondas na data</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= $total_rondas ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total de escaneamentos</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= $total_escaneados_total ?></p>
                    </div>
                </div>

                <!-- Rondas -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="admin-card">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-lg font-bold text-slate-900">Rondas do rondante</h2>
                            <span class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">
                                <?= date('d/m/Y', strtotime($data_filtro)) ?>
                            </span>
                        </div>
                        <?php if (empty($rondas)): ?>
                            <div class="text-center py-10 text-slate-500 italic">Nenhuma ronda registrada para este rondante nesta data.</div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($rondas as $i => $r): ?>
                                    <?php
                                        $ronda_id = intval($r['id']);
                                        $escans = $escaneamentos_por_ronda[$ronda_id] ?? [];
                                        $tem_escans = !empty($escans);
                                        $finalizada = ($r['status'] === 'finalizada');
                                    ?>
                                    <div class="ronda-item overflow-hidden rounded-2xl border border-slate-200 transition-all">
                                        <button type="button" onclick="toggleRonda(this)" class="ronda-toggle w-full flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-5 py-4 text-left bg-white hover:bg-slate-50 transition-all">
                                            <div class="flex items-center gap-4">
                                                <div class="flex h-11 w-11 items-center justify-center rounded-xl <?= $finalizada ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600' ?>">
                                                    <i class="fas fa-route text-lg"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900"><?= nome_ordinal_ronda($i + 1) ?></p>
                                                    <p class="text-xs text-slate-500">
                                                        Início <?= date('d/m/Y H:i:s', strtotime($r['hora_inicio'])) ?>
                                                        <?php if ($r['hora_fim']): ?>• Fim <?= date('H:i:s', strtotime($r['hora_fim'])) ?><?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div class="text-right">
                                                    <p class="text-sm font-bold text-slate-900"><?= count($escans) ?> escaneamento(s)</p>
                                                    <p class="text-xs <?= $tem_escans ? 'text-green-600 font-semibold' : 'text-red-500' ?>">
                                                        <?= $tem_escans ? 'Primeira: ' . date('H:i:s', strtotime($escans[0]['escaneado_em'])) : 'Sem escaneamentos' ?>
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= $finalizada ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>">
                                                    <?= $finalizada ? 'Finalizada' : 'Em andamento' ?>
                                                </span>
                                                <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform"></i>
                                            </div>
                                        </button>
                                        <div class="ronda-body hidden border-t border-slate-100 bg-slate-50/60">
                                            <?php if ($tem_escans): ?>
                                                <div class="overflow-x-auto p-4">
                                                    <table class="admin-table" data-no-cell-copy>
                                                        <thead>
                                                            <tr>
                                                                <th>Edifício</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($escans as $esc): ?>
                                                                <tr>
                                                                    <td class="font-semibold text-slate-900"><?= htmlspecialchars($esc['edificio_nome']) ?></td>
                                                                    <td>
                                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-green-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">
                                                                            <i class="fas fa-check-circle"></i> Escaneado às <?= date('H:i:s', strtotime($esc['escaneado_em'])) ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <p class="px-5 py-6 text-center text-sm text-slate-500 italic">Esta ronda não possui escaneamentos registrados.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex justify-end border-t border-slate-100 bg-white px-5 py-3">
                                            <form method="POST" onsubmit="return confirm('Deseja realmente excluir este relatório de ronda? Esta ação não pode ser desfeita.');" class="inline">
                                                <input type="hidden" name="excluir_ronda" value="<?= $ronda_id ?>">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100 transition-all">
                                                    <i class="fas fa-trash-alt" style="font-size:10px"></i> Excluir relatório
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
        function toggleRonda(btn) {
            const item = btn.closest('.ronda-item');
            const body = item.querySelector('.ronda-body');
            const chevron = btn.querySelector('.fa-chevron-down');
            const isHidden = body.classList.contains('hidden');
            body.classList.toggle('hidden');
            if (chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
