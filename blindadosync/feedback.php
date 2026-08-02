<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_nome = $_SESSION['usuario_nome_real'] ?: ($_SESSION['usuario_nome'] ?? 'Usuário');
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

$mensagem = '';
$mensagem_tipo = 'info';

// Processar envio do feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_feedback'])) {
    $tipo = $_POST['tipo'] ?? '';
    $assunto = $_POST['assunto'] ?? '';
    $mensagem_feedback = $_POST['mensagem'] ?? '';
    
    if (empty($tipo) || empty($assunto) || empty($mensagem_feedback)) {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
        $mensagem_tipo = 'error';
    } else {
        // Verificar se a tabela existe
        $tabela_existe = $conn->query("SHOW TABLES LIKE 'feedbacks'")->num_rows > 0;
        
        if (!$tabela_existe) {
            $mensagem = 'A tabela de feedbacks ainda não foi criada. Entre em contato com o administrador.';
            $mensagem_tipo = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO feedbacks (usuario_id, tipo, assunto, mensagem, data_criacao) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param('isss', $usuario_id, $tipo, $assunto, $mensagem_feedback);
            
            if ($stmt->execute()) {
                $mensagem = 'Feedback enviado com sucesso! Obrigado por sua contribuição.';
                $mensagem_tipo = 'success';
            } else {
                $mensagem = 'Erro ao enviar feedback: ' . $conn->error;
                $mensagem_tipo = 'error';
            }
            $stmt->close();
        }
    }
}

// Verificar se a tabela feedbacks existe
$tabela_existe = $conn->query("SHOW TABLES LIKE 'feedbacks'")->num_rows > 0;

// Buscar feedbacks anteriores do usuário
$feedbacks_anteriores = [];
if ($tabela_existe) {
    $stmt = $conn->prepare("SELECT f.*, u.nome as usuario_nome FROM feedbacks f JOIN usuarios u ON f.usuario_id = u.id WHERE f.usuario_id = ? ORDER BY f.data_criacao DESC LIMIT 10");
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $feedbacks_anteriores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Buscar todos os feedbacks (apenas para gerentes e diretores)
$feedbacks_todos = [];
$is_gerente_ou_diretor = in_array($usuario_categoria, ['gerente', 'diretor']);
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

if ($tabela_existe && $is_gerente_ou_diretor) {
    // Contar total de feedbacks
    $total_feedbacks = $conn->query("SELECT COUNT(*) as total FROM feedbacks")->fetch_assoc()['total'] ?? 0;
    $total_paginas = ceil($total_feedbacks / $por_pagina);
    
    // Buscar feedbacks com paginação
    $stmt = $conn->prepare("SELECT f.*, u.nome as usuario_nome, u.categoria as usuario_categoria FROM feedbacks f JOIN usuarios u ON f.usuario_id = u.id ORDER BY f.data_criacao DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $por_pagina, $offset);
    $stmt->execute();
    $feedbacks_todos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Blindado Soluções</title>
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
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>

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
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Feedback</h1>
                        <p class="mt-1 text-slate-500">Envie suas sugestões, dúvidas ou reporte problemas para melhorar nosso sistema.</p>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!$tabela_existe): ?>
                    <div class="mb-6 p-4 bg-yellow-50 border-yellow-500 text-yellow-700 border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <div class="text-sm">
                            <p class="font-medium">Tabela de feedbacks não encontrada</p>
                            <p class="mt-1">A funcionalidade de feedback requer a criação da tabela no banco de dados. <a href="migrar_feedback.php" class="underline font-bold">Clique aqui para executar a migration</a>.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Formulário de Feedback -->
                    <div class="animate-slide-up">
                        <div class="admin-card">
                            <div class="mb-6">
                                <h2 class="text-lg font-bold text-slate-900">Novo Feedback</h2>
                                <p class="text-sm text-slate-500">Preencha o formulário abaixo para enviar seu feedback.</p>
                            </div>

                            <form method="POST" action="" class="space-y-4">
                                <div>
                                    <label for="tipo" class="block text-sm font-medium text-slate-700 mb-1">Tipo de Feedback *</label>
                                    <select id="tipo" name="tipo" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
                                        <option value="">Selecione o tipo...</option>
                                        <option value="sugestao">💡 Sugestão</option>
                                        <option value="duvida">❓ Dúvida</option>
                                        <option value="problema">⚠️ Problema/Erro</option>
                                        <option value="elogio">👍 Elogio</option>
                                        <option value="outro">📝 Outro</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="assunto" class="block text-sm font-medium text-slate-700 mb-1">Assunto *</label>
                                    <input type="text" id="assunto" name="assunto" required placeholder="Resumo do seu feedback" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all">
                                </div>

                                <div>
                                    <label for="mensagem" class="block text-sm font-medium text-slate-700 mb-1">Mensagem *</label>
                                    <textarea id="mensagem" name="mensagem" required rows="5" placeholder="Descreva detalhadamente seu feedback..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all resize-none"></textarea>
                                </div>

                                <button type="submit" name="enviar_feedback" <?php echo !$tabela_existe ? 'disabled class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-400 px-6 py-3 text-sm font-bold text-white cursor-not-allowed w-full sm:w-auto"' : 'class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-bold text-white hover:bg-primary-500 transition-all shadow-sm w-full sm:w-auto"'; ?>>
                                    <i class="fas fa-paper-plane"></i>
                                    <?= !$tabela_existe ? 'Tabela não criada - Execute a migration' : 'Enviar Feedback' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Feedbacks Anteriores -->
                    <div class="animate-slide-up" style="animation-delay: 0.1s;">
                        <div class="admin-card">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-lg font-bold text-slate-900">Seus Feedbacks Recentes</h2>
                                <span class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">
                                    <?= count($feedbacks_anteriores) ?> feedback(s)
                                </span>
                            </div>

                            <?php if (empty($feedbacks_anteriores)): ?>
                                <div class="text-center py-10 text-slate-500 italic">
                                    <i class="fas fa-comment-slash text-4xl mb-3 text-slate-300"></i>
                                    <p>Nenhum feedback enviado ainda.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                                    <?php foreach ($feedbacks_anteriores as $feedback): ?>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                            <div class="flex items-start justify-between gap-2 mb-2">
                                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold <?= getTipoFeedbackClass($feedback['tipo']) ?>">
                                                    <?= getTipoFeedbackIcon($feedback['tipo']) ?>
                                                    <?= getTipoFeedbackLabel($feedback['tipo']) ?>
                                                </span>
                                                <span class="text-xs text-slate-400">
                                                    <?= date('d/m/Y H:i', strtotime($feedback['data_criacao'])) ?>
                                                </span>
                                            </div>
                                            <h3 class="font-semibold text-slate-900 text-sm mb-1"><?= htmlspecialchars($feedback['assunto']) ?></h3>
                                            <p class="text-sm text-slate-600 line-clamp-2"><?= htmlspecialchars($feedback['mensagem']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Feedbacks de Todos (apenas para gerentes e diretores) -->
                            <?php if ($is_gerente_ou_diretor): ?>
                            <div class="mt-6 pt-6 border-t border-slate-200">
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-lg font-bold text-slate-900">Feedbacks da Equipe</h2>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">
                                            <?= $total_feedbacks ?? 0 ?> feedback(s) total
                                        </span>
                                        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                                            <span class="inline-flex items-center gap-2 rounded-lg bg-primary-100 px-3 py-1.5 text-xs font-bold text-primary-700">
                                                Página <?= $pagina ?> de <?= $total_paginas ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (empty($feedbacks_todos)): ?>
                                    <div class="text-center py-10 text-slate-500 italic">
                                        <i class="fas fa-comments text-4xl mb-3 text-slate-300"></i>
                                        <p>Nenhum feedback enviado pela equipe ainda.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                                        <?php foreach ($feedbacks_todos as $feedback): ?>
                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex items-start justify-between gap-2 mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold <?= getTipoFeedbackClass($feedback['tipo']) ?>">
                                                            <?= getTipoFeedbackIcon($feedback['tipo']) ?>
                                                            <?= getTipoFeedbackLabel($feedback['tipo']) ?>
                                                        </span>
                                                        <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold bg-slate-200 text-slate-700">
                                                            <?= getCategoriaLabel($feedback['usuario_categoria']) ?>
                                                        </span>
                                                    </div>
                                                    <span class="text-xs text-slate-400">
                                                        <?= date('d/m/Y H:i', strtotime($feedback['data_criacao'])) ?>
                                                    </span>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="text-xs font-medium text-slate-500"><?= htmlspecialchars($feedback['usuario_nome']) ?></span>
                                                </div>
                                                <h3 class="font-semibold text-slate-900 text-sm mb-1"><?= htmlspecialchars($feedback['assunto']) ?></h3>
                                                <p class="text-sm text-slate-600 line-clamp-2"><?= htmlspecialchars($feedback['mensagem']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Paginação -->
                                    <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 pt-4 border-t border-slate-200">
                                        <div class="text-sm text-slate-500">
                                            Mostrando <?= min($por_pagina, count($feedbacks_todos)) ?> de <?= $total_feedbacks ?> feedbacks
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <?php if ($pagina > 1): ?>
                                                <a href="?pagina=<?= $pagina - 1 ?>" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200 transition-all">
                                                    <i class="fas fa-chevron-left"></i>
                                                    Anterior
                                                </a>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center gap-1">
                                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                                    <?php if ($i == $pagina): ?>
                                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary-600 text-white text-sm font-bold">
                                                            <?= $i ?>
                                                        </span>
                                                    <?php elseif (abs($i - $pagina) <= 2 || $i == 1 || $i == $total_paginas): ?>
                                                        <a href="?pagina=<?= $i ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold hover:bg-slate-200 transition-all">
                                                            <?= $i ?>
                                                        </a>
                                                    <?php elseif (abs($i - $pagina) == 3): ?>
                                                        <span class="text-slate-400">...</span>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            
                                            <?php if ($pagina < $total_paginas): ?>
                                                <a href="?pagina=<?= $pagina + 1 ?>" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200 transition-all">
                                                    Próximo
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php
    function getTipoFeedbackLabel($tipo) {
        $labels = [
            'sugestao' => 'Sugestão',
            'duvida' => 'Dúvida',
            'problema' => 'Problema',
            'elogio' => 'Elogio',
            'outro' => 'Outro'
        ];
        return $labels[$tipo] ?? 'Outro';
    }

    function getTipoFeedbackIcon($tipo) {
        $icons = [
            'sugestao' => '💡',
            'duvida' => '❓',
            'problema' => '⚠️',
            'elogio' => '👍',
            'outro' => '📝'
        ];
        return $icons[$tipo] ?? '📝';
    }

    function getTipoFeedbackClass($tipo) {
        $classes = [
            'sugestao' => 'bg-blue-100 text-blue-700',
            'duvida' => 'bg-yellow-100 text-yellow-700',
            'problema' => 'bg-red-100 text-red-700',
            'elogio' => 'bg-green-100 text-green-700',
            'outro' => 'bg-slate-200 text-slate-700'
        ];
        return $classes[$tipo] ?? 'bg-slate-200 text-slate-700';
    }

    function getCategoriaLabel($categoria) {
        $labels = [
            'gerente' => 'Gerente',
            'diretor' => 'Diretor',
            'tecnico' => 'Técnico',
            'administrativo' => 'Administrativo',
            'colaborador' => 'Colaborador',
            'supervisor' => 'Supervisor',
            'operador' => 'Operador',
            'rondante' => 'Rondante'
        ];
        return $labels[$categoria] ?? 'Usuário';
    }
    ?>
</body>
</html>
<?php $conn->close(); ?>