<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

// Alternar vínculo de gerente de ronda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerente_id'])) {
    $gerente_id = intval($_POST['gerente_id']);
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND categoria = 'gerente'");
    $stmt->bind_param('i', $gerente_id);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe) {
        $novo = isset($_POST['vincular']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE usuarios SET gerente_ronda = ? WHERE id = ?");
        $stmt->bind_param('ii', $novo, $gerente_id);
        if ($stmt->execute()) {
            $mensagem = $novo ? 'Gerente vinculado como Gerente de Ronda com sucesso!' : 'Gerente desvinculado de Gerente de Ronda.';
            $mensagem_tipo = 'success';
        } else {
            $mensagem = 'Erro ao atualizar: ' . $conn->error;
            $mensagem_tipo = 'error';
        }
        $stmt->close();
    }
}

// Lista de gerentes
$gerentes = $conn->query("SELECT id, nome, nome_real, whatsapp, gerente_ronda FROM usuarios WHERE categoria = 'gerente' ORDER BY gerente_ronda DESC, nome_real")->fetch_all(MYSQLI_ASSOC);

$total_vinculados = count(array_filter($gerentes, fn($g) => $g['gerente_ronda'] == 1));

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
    <title>Gerentes de Ronda | Rondante</title>
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

        <div class="flex min-w-0 flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="min-w-0 flex-1 p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gerentes de Ronda</h1>
                        <p class="mt-1 text-slate-500">Vincule gerentes para acompanhar as rondas realizadas pelos rondantes.</p>
                    </div>
                    <a href="rondante_validacao.php" class="icon-btn" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                </div>

                <!-- Submenus -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <?php if ($usuario_categoria === 'rondante'): ?>
                    <a href="rondante.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Ronda Atual</a>
                    <?php endif; ?>
                    <a href="rondante_qrcodes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">QR Codes</a>
                    <a href="rondante_validacao.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Validação de Ronda</a>
                    <a href="rondante_gerentes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-primary-500 text-primary-600">Gerentes de Ronda</a>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Resumo -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3 animate-slide-up">
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total de gerentes</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= count($gerentes) ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Vinculados como gerente de ronda</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= $total_vinculados ?></p>
                    </div>
                    <div class="admin-card p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Disponíveis para vincular</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900"><?= count($gerentes) - $total_vinculados ?></p>
                    </div>
                </div>

                <!-- Lista de Gerentes -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="admin-card">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-lg font-bold text-slate-900">Gerentes cadastrados</h2>
                        </div>
                        <?php if (empty($gerentes)): ?>
                            <div class="text-center py-10 text-slate-500 italic">Nenhum gerente cadastrado no sistema.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="admin-table" data-no-cell-copy>
                                    <thead>
                                        <tr>
                                            <th>Gerente</th>
                                            <th>WhatsApp</th>
                                            <th>Situação</th>
                                            <th class="text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gerentes as $g): ?>
                                            <?php $vinculado = $g['gerente_ronda'] == 1; ?>
                                            <tr class="<?= $vinculado ? 'bg-green-50/50' : '' ?>">
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-slate-900"><?= htmlspecialchars($g['nome_real'] ?: $g['nome']) ?></span>
                                                        <span class="text-[10px] text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($g['nome']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($g['whatsapp'])): ?>
                                                        <span class="text-xs text-slate-500"><?= htmlspecialchars($g['whatsapp']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-xs text-slate-300">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($vinculado): ?>
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-green-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">
                                                            <i class="fas fa-user-check"></i> Gerente de Ronda
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                            <i class="fas fa-user"></i> Não vinculado
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="gerente_id" value="<?= $g['id'] ?>">
                                                        <?php if ($vinculado): ?>
                                                            <button type="submit" name="desvincular" class="icon-btn-red" title="Desvincular"><i class="fas fa-user-slash" style="font-size:10px"></i></button>
                                                        <?php else: ?>
                                                            <button type="submit" name="vincular" class="icon-btn-green" title="Vincular como Gerente de Ronda"><i class="fas fa-user-check" style="font-size:10px"></i></button>
                                                        <?php endif; ?>
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
