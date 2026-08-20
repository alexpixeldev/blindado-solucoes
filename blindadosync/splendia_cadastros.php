<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

// Excluir cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_cadastro'])) {
    $id = intval($_POST['excluir_cadastro']);
    $stmt = $conn->prepare("DELETE FROM locacoes_inquilinos WHERE locacao_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM locacoes WHERE id = ? AND edificio_id = 61");
    $stmt->bind_param('i', $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['mensagem'] = 'Cadastro excluído com sucesso!';
        $_SESSION['mensagem_tipo'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir cadastro.';
        $_SESSION['mensagem_tipo'] = 'error';
    }
    $stmt->close();
    header("Location: splendia_cadastros.php");
    exit();
}

$filtro_apartamento = trim($_GET['apartamento'] ?? '');
$filtro_tipo = $_GET['tipo'] ?? '';

// Apartamentos do Edifício Splendia
$apartamentos = [
    '401','402','403','404','501','502','503','504','601','602','603','604',
    '701','702','703','704','801','802','803','804','901','902','903','904',
    '1001','1002','1003','1004','1101','1102','1103','1104','1201','1202','1203','1204',
    '1301','1302','1303','1304','1401','1402','1403','1404','1501','1502','1503','1504'
];

// Apartamentos que já enviaram cadastro (apenas os válidos da lista)
$enviados = [];
$rEnviados = $conn->query("SELECT DISTINCT numero_apartamento FROM locacoes WHERE edificio_id = 61");
$apartamentosSet = array_flip($apartamentos);
while ($row = $rEnviados->fetch_assoc()) {
    if (isset($apartamentosSet[$row['numero_apartamento']])) {
        $enviados[] = $row['numero_apartamento'];
    }
}
$enviadosSet = array_flip($enviados);

$nao_enviados = array_values(array_filter($apartamentos, function ($apt) use ($enviadosSet) {
    return !isset($enviadosSet[$apt]);
}));

$where = "l.edificio_id = 61";
$params = [];
$types = '';

if ($filtro_apartamento !== '') {
    $where .= " AND l.numero_apartamento LIKE ?";
    $params[] = '%' . $filtro_apartamento . '%';
    $types .= 's';
}
if ($filtro_tipo === 'proprietario') {
    $where .= " AND EXISTS (SELECT 1 FROM locacoes_inquilinos p2 WHERE p2.locacao_id = l.id AND p2.locatario_anual = 0)";
} elseif ($filtro_tipo === 'locatario') {
    $where .= " AND EXISTS (SELECT 1 FROM locacoes_inquilinos p2 WHERE p2.locacao_id = l.id AND p2.locatario_anual = 1)";
}

$sql = "SELECT l.*, COUNT(p.id) AS total_pessoas
        FROM locacoes l
        LEFT JOIN locacoes_inquilinos p ON p.locacao_id = l.id
        WHERE " . $where . "
        GROUP BY l.id
        ORDER BY l.data_registro DESC, l.id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $cadastros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $cadastros = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$total_cadastros = count($cadastros);

$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastros Splendia | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">



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

        <div class="flex min-w-0 flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="min-w-0 flex-1 p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Cadastros Splendia</h1>
                        <p class="mt-1 text-slate-500">Cadastros de moradores do Edifício Splendia enviados pelo formulário.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="imprimirCadastros()" class="icon-btn" title="Imprimir ou salvar em PDF"><i class="fas fa-print" style="font-size:10px"></i></button>
                        <a href="edificios.php" class="icon-btn" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                    </div>
                </div>

                <script>
                    function imprimirCadastros() {
                        const params = new URLSearchParams(window.location.search);
                        let url = 'splendia_cadastros_print.php';
                        const qs = params.toString();
                        if (qs) url += '?' + qs;
                        window.open(url, '_blank', 'width=1100,height=800');
                    }
                </script>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Resumo -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4 animate-slide-up">
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Apartamentos do edifício</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= count($apartamentos) ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Já enviaram</p>
                        <p class="mt-1 text-2xl font-bold text-green-600"><?= count($enviados) ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Ainda não enviaram</p>
                        <p class="mt-1 text-2xl font-bold text-red-600"><?= count($nao_enviados) ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Pessoas cadastradas</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= $conn->query("SELECT COUNT(*) AS n FROM locacoes_inquilinos qi JOIN locacoes l ON qi.locacao_id = l.id WHERE l.edificio_id = 61")->fetch_assoc()['n'] ?></p>
                    </div>
                </div>

                <!-- Status dos Apartamentos -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Status dos Apartamentos</h2>
                                <p class="mt-1 text-xs text-slate-500">Verde = enviaram cadastro | Vermelho = ainda não enviaram</p>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-semibold">
                                <span class="inline-flex items-center gap-1.5 text-green-700"><span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span> <?= count($enviados) ?> enviaram</span>
                                <span class="inline-flex items-center gap-1.5 text-red-700"><span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span> <?= count($nao_enviados) ?> pendentes</span>
                            </div>
                        </div>

                        <?php
                        // Agrupar por andar (últimos dígitos = apartamento, prefixo = andar)
                        $porAndar = [];
                        foreach ($apartamentos as $apt) {
                            $andar = strlen($apt) === 3 ? substr($apt, 0, 1) : substr($apt, 0, 2);
                            $porAndar[$andar][] = $apt;
                        }
                        ksort($porAndar);
                        ?>
                        <div class="space-y-4">
                            <?php foreach ($porAndar as $andar => $aptosAndar): ?>
                                <div>
                                    <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400"><?= $andar ?>º Andar</p>
                                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-8">
                                        <?php foreach ($aptosAndar as $apt): ?>
                                            <?php $enviou = isset($enviadosSet[$apt]); ?>
                                            <div class="flex flex-col items-center gap-1 rounded-xl border p-2 <?= $enviou ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?>">
                                                <span class="text-xs font-bold <?= $enviou ? 'text-green-700' : 'text-red-700'; ?>"><?= $apt ?></span>
                                                <i class="fas <?= $enviou ? 'fa-check-circle text-green-600' : 'fa-hourglass-half text-red-500'; ?>" style="font-size:12px"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Apartamento</label>
                                <input type="text" name="apartamento" class="form-input" value="<?= htmlspecialchars($filtro_apartamento) ?>" placeholder="Ex: 101">
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Tipo de morador</label>
                                <select name="tipo" class="form-input">
                                    <option value="">Todos</option>
                                    <option value="proprietario" <?= $filtro_tipo === 'proprietario' ? 'selected' : '' ?>>Com proprietário</option>
                                    <option value="locatario" <?= $filtro_tipo === 'locatario' ? 'selected' : '' ?>>Com locatário anual</option>
                                </select>
                            </div>
                            <button type="submit" class="icon-btn-blue" title="Filtrar"><i class="fas fa-filter" style="font-size:10px"></i></button>
                        </form>
                    </div>
                </div>

                <!-- Lista -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="admin-card">
                        <?php if (empty($cadastros)): ?>
                            <div class="text-center py-10 text-slate-500 italic">Nenhum cadastro encontrado.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="admin-table" data-no-cell-copy>
                                    <thead>
                                        <tr>
                                            <th>Apartamento</th>
                                            <th>Moradores</th>
                                            <th>Data de envio</th>
                                            <th class="text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cadastros as $cad): ?>
                                            <?php
                                                $stmt = $conn->prepare("SELECT nome, documento, locatario_anual FROM locacoes_inquilinos WHERE locacao_id = ? ORDER BY id ASC");
                                                $stmt->bind_param('i', $cad['id']);
                                                $stmt->execute();
                                                $pessoas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                                $stmt->close();
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="inline-flex items-center justify-center rounded-lg bg-primary-100 px-3 py-1 text-sm font-bold text-primary-700">Apto <?= htmlspecialchars($cad['numero_apartamento']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="flex flex-col gap-1">
                                                        <?php foreach ($pessoas as $p): ?>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-sm text-slate-700 font-medium"><?= htmlspecialchars($p['nome']) ?></span>
                                                                <?php if ($p['documento']): ?>
                                                                    <span class="text-xs text-slate-400">• <?= htmlspecialchars($p['documento']) ?></span>
                                                                <?php endif; ?>
                                                                <?php if ($p['locatario_anual']): ?>
                                                                    <span class="inline-flex items-center rounded-lg bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700">Locatário anual</span>
                                                                <?php else: ?>
                                                                    <span class="inline-flex items-center rounded-lg bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">Proprietário</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-xs text-slate-500"><?= date('d/m/Y H:i', strtotime($cad['data_registro'])) ?></span>
                                                </td>
                                                <td class="text-right">
                                                    <form method="POST" onsubmit="return confirm('Deseja realmente excluir este cadastro?');" class="inline">
                                                        <input type="hidden" name="excluir_cadastro" value="<?= $cad['id'] ?>">
                                                        <button type="submit" class="icon-btn-red" title="Excluir"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
</body>
</html>