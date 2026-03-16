<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
$mensagem = '';
$mensagem_tipo = 'info';

// Only users who are not admin or collaborator can access
if (in_array($usuario_categoria, ['administrativo', 'colaborador'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edificio_id = intval($_POST['edificio_id']);
    $numero_apartamento = trim($_POST['numero_apartamento']);
    $hora_entrega = $_POST['hora_entrega'];
    $situacao_recebimento = $_POST['situacao_recebimento'];
    $transportadora = $_POST['transportadora'];
    $observacao = trim($_POST['observacao']);
    $data_entrega = !empty($_POST['data_entrega']) ? $_POST['data_entrega'] : date('Y-m-d');

    if ($edificio_id > 0 && !empty($numero_apartamento) && !empty($hora_entrega) && !empty($situacao_recebimento) && !empty($transportadora)) {
        $stmt = $conn->prepare("INSERT INTO entregas (edificio_id, numero_apartamento, data_entrega, hora_entrega, situacao_recebimento, transportadora, usuario_id, observacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $edificio_id, $numero_apartamento, $data_entrega, $hora_entrega, $situacao_recebimento, $transportadora, $usuario_id, $observacao);
        
        if ($stmt->execute()) {
            $mensagem = "Entrega registrada com sucesso!";
            $mensagem_tipo = "success";
        } else {
            $mensagem = "Erro ao registrar entrega: " . $conn->error;
            $mensagem_tipo = "error";
        }
        $stmt->close();
    } else {
        $mensagem = "Preencha todos os campos obrigatórios!";
        $mensagem_tipo = "error";
    }
}

$edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
$transportadoras = $conn->query("SELECT nome FROM transportadoras ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$situacoes = $conn->query("SELECT nome FROM situacoes_entrega ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrega | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
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
    
    <!-- Google Fonts & Font Awesome -->
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
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in max-w-4xl mx-auto">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Registrar Entrega</h1>
                    <p class="mt-1 text-slate-500">Registre uma nova encomenda ou pacote recebido para os moradores.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mx-auto max-w-4xl mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Form Card -->
                <div class="mx-auto max-w-4xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-8">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Edifício</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-building text-slate-400 text-sm"></i>
                                        </div>
                                        <select name="edificio_id" class="form-input pl-11 appearance-none" required>
                                            <option value="">-- Selecione o Edifício --</option>
                                            <?php foreach ($edificios as $ed): ?>
                                                <option value="<?php echo $ed['id']; ?>">
                                                    <?php echo htmlspecialchars($ed['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="form-label">Apartamento / Unidade</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-door-open text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="numero_apartamento" class="form-input pl-11" placeholder="Ex: 101" required>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <?php renderModernCalendar('data_entrega', date('Y-m-d'), 'Data da Entrega'); ?>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Hora da Entrega</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-clock text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="time" name="hora_entrega" class="form-input pl-11" value="<?php echo date('H:i'); ?>" required>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Situação do Recebimento</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-info-circle text-slate-400 text-sm"></i>
                                        </div>
                                        <select name="situacao_recebimento" class="form-input pl-11 appearance-none" required>
                                            <option value="">-- Selecione --</option>
                                            <?php foreach ($situacoes as $s): ?>
                                                <option value="<?= htmlspecialchars($s['nome']) ?>"><?= htmlspecialchars($s['nome']) ?></option>
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
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-truck text-slate-400 text-sm"></i>
                                        </div>
                                        <select name="transportadora" class="form-input pl-11 appearance-none" required>
                                            <option value="">-- Selecione --</option>
                                            <?php foreach ($transportadoras as $t): ?>
                                                <option value="<?= htmlspecialchars($t['nome']) ?>"><?= htmlspecialchars($t['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <label class="form-label">Observações Adicionais</label>
                                    <textarea name="observacao" class="form-input min-h-[120px] resize-none" placeholder="Descreva detalhes do pacote, se há avarias, etc..."></textarea>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 pt-6 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-end">
                                <a href="consultar_entrega.php" class="btn-secondary order-2 sm:order-1">
                                    <i class="fas fa-search"></i>
                                    <span>Consultar Entregas</span>
                                </a>
                                <button type="submit" class="btn-primary order-1 sm:order-2">
                                    <i class="fas fa-check"></i>
                                    <span>Registrar Entrega</span>
                                </button>
                            </div>
                        </form>
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
<?php
$conn->close();
?>
