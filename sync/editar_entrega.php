<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (in_array($usuario_categoria, ['administrativo', 'colaborador'])) {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: consultar_entrega.php"); exit(); }

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edificio_id = intval($_POST['edificio_id']);
    $numero_apartamento = trim($_POST['numero_apartamento']);
    $data_entrega = $_POST['data_entrega'];
    $hora_entrega = $_POST['hora_entrega'];
    $situacao_recebimento = $_POST['situacao_recebimento'];
    $transportadora = $_POST['transportadora'];
    $observacao = trim($_POST['observacao']);

    if ($edificio_id > 0 && !empty($numero_apartamento) && !empty($data_entrega) && !empty($hora_entrega)) {
        $usuario_id = $_SESSION['usuario_id'];
        $stmt = $conn->prepare("UPDATE entregas SET edificio_id = ?, numero_apartamento = ?, data_entrega = ?, hora_entrega = ?, situacao_recebimento = ?, transportadora = ?, observacao = ?, atualizado_por = ?, data_atualizacao = NOW() WHERE id = ?");
        $stmt->bind_param("issssssii", $edificio_id, $numero_apartamento, $data_entrega, $hora_entrega, $situacao_recebimento, $transportadora, $observacao, $usuario_id, $id);
        
        if ($stmt->execute()) {
            header("Location: consultar_entrega.php?msg=sucesso");
            exit();
        } else {
            $mensagem = "Erro ao atualizar entrega: " . $conn->error;
            $mensagem_tipo = "error";
        }
        $stmt->close();
    } else {
        $mensagem = "Preencha todos os campos obrigatórios!";
        $mensagem_tipo = "error";
    }
}

$stmt = $conn->prepare("SELECT * FROM entregas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$entrega) { header("Location: consultar_entrega.php"); exit(); }

$edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id WHERE b.status = 'ativo' ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
$transportadoras = $conn->query("SELECT nome FROM transportadoras ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$situacoes = $conn->query("SELECT nome FROM situacoes_entrega ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Entrega | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
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
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="mb-8 animate-fade-in">
                    <div class="flex items-center gap-4">
                        <a href="consultar_entrega.php" class="icon-btn" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Entrega</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações da entrega registrada.</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= htmlspecialchars($mensagem) ?></div>
                    </div>
                <?php endif; ?>

                <div class="mx-auto max-w-4xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-8">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Edifício</label>
                                    <div class="relative">
                                        <select name="edificio_id" class="form-input appearance-none pl-4 pr-10" required>
                                            <?php foreach ($edificios as $ed): ?>
                                                <option value="<?= $ed['id'] ?>" <?= $entrega['edificio_id'] == $ed['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ed['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Apartamento / Unidade</label>
                                    <input type="text" name="numero_apartamento" class="form-input" value="<?= htmlspecialchars($entrega['numero_apartamento']) ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <?php renderModernCalendar('data_entrega', $entrega['data_entrega'], 'Data da Entrega'); ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Hora da Entrega</label>
                                    <input type="time" name="hora_entrega" class="form-input" value="<?= $entrega['hora_entrega'] ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Situação do Recebimento</label>
                                    <div class="relative">
                                        <select name="situacao_recebimento" class="form-input appearance-none pl-4 pr-10" required>
                                            <?php foreach ($situacoes as $s): ?>
                                                <option value="<?= htmlspecialchars($s['nome']) ?>" <?= $entrega['situacao_recebimento'] == $s['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Transportadora / Entregador</label>
                                    <div class="relative">
                                        <select name="transportadora" class="form-input appearance-none pl-4 pr-10" required>
                                            <?php foreach ($transportadoras as $t): ?>
                                                <option value="<?= htmlspecialchars($t['nome']) ?>" <?= $entrega['transportadora'] == $t['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2 sm:col-span-2">
                                    <label class="form-label">Observações Adicionais</label>
                                    <textarea name="observacao" class="form-input min-h-[120px] resize-none"><?= htmlspecialchars($entrega['observacao'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="flex flex-col gap-4 pt-6 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-end">
                                <button type="submit" class="icon-btn-green" title="Salvar"><i class="fas fa-save" style="font-size:10px"></i></button>
                                <a href="consultar_entrega.php" class="icon-btn" title="Cancelar"><i class="fas fa-times" style="font-size:10px"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>
