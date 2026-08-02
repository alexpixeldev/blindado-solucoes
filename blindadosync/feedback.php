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
        'sugestao' => 'fa-lightbulb',
        'duvida' => 'fa-question-circle',
        'problema' => 'fa-exclamation-triangle',
        'elogio' => 'fa-thumbs-up',
        'outro' => 'fa-file-alt'
    ];
    return $icons[$tipo] ?? 'fa-file-alt';
}

function getTipoFeedbackClass($tipo) {
    $classes = [
        'sugestao' => 'tag-sugestao',
        'duvida' => 'tag-duvida',
        'problema' => 'tag-problema',
        'elogio' => 'tag-elogio',
        'outro' => 'tag-outro'
    ];
    return $classes[$tipo] ?? 'tag-outro';
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
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Blindado Soluções</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-primary); margin: 0; padding: 0; }
        .page-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; background: var(--bg-primary); }
        .page-content { width: 100%; max-width: 1400px; background: var(--bg-card); margin: 24px auto 80px auto; padding: 32px 36px; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 8px 32px var(--shadow); }
        .notion-header-static { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.8rem; color: var(--text-primary); padding: 0 0 16px 0; line-height: 1.3; border-bottom: 1px solid var(--border); }
        .notion-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 32px; margin-bottom: 28px; padding: 20px 24px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; }
        .meta-row { display: flex; align-items: center; gap: 10px; }
        .meta-label { color: var(--text-secondary); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; min-width: 90px; display: flex; align-items: center; gap: 6px; }
        .meta-value { flex: 1; }
        .meta-value input, .meta-value select, .meta-value textarea { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-secondary); font-size: 0.9rem; font-weight: 500; color: var(--text-primary); outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .meta-value input:focus, .meta-value select:focus, .meta-value textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 169, 55, 0.18); }
        .meta-value input:hover, .meta-value select:hover, .meta-value textarea:hover { border-color: rgba(255, 255, 255, 0.25); }
        .meta-value .relative { position: relative; }
        .meta-value .absolute { position: absolute; }
        .meta-value .inset-y-0 { top: 0; bottom: 0; }
        .meta-value .left-0 { left: 0; }
        .meta-value .pl-4 { padding-left: 1rem; }
        .meta-value .pl-11 { padding-left: 2.75rem; }
        .meta-value .pr-4 { padding-right: 1rem; }
        .meta-value .flex { display: flex; }
        .meta-value .items-center { align-items: center; }
        .meta-value .pointer-events-none { pointer-events: none; }
        .meta-value .text-slate-400 { color: #94a3b8; }
        .meta-value .text-sm { font-size: 0.875rem; }
        .meta-value .text-xs { font-size: 0.75rem; }
        .top-bar { position: sticky; top: 0; z-index: 1000; background: rgba(6, 19, 40, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 14px 32px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar-left { display: flex; align-items: center; gap: 8px; }
        .top-bar-left i { color: var(--primary-light); font-size: 1.1rem; }
        .top-bar-left span { font-weight: 600; font-size: 0.95rem; color: var(--text-primary); }
        .form-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 28px; padding-top: 22px; border-top: 1px solid var(--border); }
        
        .feedback-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
        .feedback-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
        .feedback-card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary); padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .feedback-list { max-height: 400px; overflow-y: auto; }
        .feedback-item { padding: 12px; border-radius: 8px; background: var(--bg-primary); margin-bottom: 10px; border: 1px solid var(--border); }
        .feedback-item-date { font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .feedback-item-assunto { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
        .feedback-item-desc { font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4; }
        .feedback-tag { display: inline-flex; align-items: center; gap: 6px; padding: 3px 8px; border-radius: 5px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .feedback-tag i { font-size: 0.9rem; }
        .tag-sugestao { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
        .tag-duvida { background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3); }
        .tag-problema { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .tag-elogio { background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); }
        .tag-outro { background: rgba(100, 116, 139, 0.15); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3); }
        
        .success-message { background: rgba(37, 169, 55, 0.15); border: 1px solid rgba(37, 169, 55, 0.3); color: var(--primary-light); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
        .error-message { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
        .warning-message { background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.3); color: #eab308; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
        
        .pagination { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
        .pagination-btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .pagination-btn:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.3); }
        .pagination-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        @media (max-width: 768px) {
            .page-content { padding: 20px 16px !important; margin: 12px !important; }
            .notion-meta { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex-1 flex flex-col page-wrapper">
            <header class="top-bar">
                <div class="top-bar-left">
                    <i class="fas fa-comment-dots"></i>
                    <span>Feedback</span>
                </div>
                <div class="top-bar-right">
                </div>
            </header>
            <main class="page-content">
                <?php if ($mensagem): ?>
                    <div class="<?php echo $mensagem_tipo === 'success' ? 'success-message' : ($mensagem_tipo === 'error' ? 'error-message' : 'warning-message'); ?>">
                        <?php echo htmlspecialchars($mensagem); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$tabela_existe): ?>
                    <div class="warning-message">
                        <p><strong>Tabela de feedbacks não encontrada</strong></p>
                        <p style="margin-top: 8px;">A funcionalidade de feedback requer a criação da tabela no banco de dados. <a href="migrar_feedback.php" style="color: inherit; text-decoration: underline;">Clique aqui para executar a migration</a>.</p>
                    </div>
                <?php endif; ?>

                <h1 class="notion-header-static">Feedback</h1>

                <div class="feedback-grid">
                    <!-- Formulário de Feedback -->
                    <div class="feedback-card">
                        <div class="feedback-card-title">Novo Feedback</div>
                        <form method="POST" action="" class="space-y-4">
                            <div>
                                <label class="meta-label">Tipo de Feedback</label>
                                <div class="meta-value">
                                    <div class="relative">
                                        <select name="tipo" class="appearance-none pr-10" required>
                                            <option value="">Selecione o tipo...</option>
                                            <option value="sugestao">Sugestão</option>
                                            <option value="duvida">Dúvida</option>
                                            <option value="problema">Problema/Erro</option>
                                            <option value="elogio">Elogio</option>
                                            <option value="outro">Outro</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="meta-label">Assunto</label>
                                <div class="meta-value">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-heading text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="assunto" class="pl-11" required placeholder="Resumo do seu feedback">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="meta-label">Mensagem</label>
                                <div class="meta-value">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none" style="top: 8px;">
                                            <i class="fas fa-align-left text-slate-400 text-sm"></i>
                                        </div>
                                        <textarea name="mensagem" class="pl-11" required rows="5" placeholder="Descreva detalhadamente seu feedback..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="enviar_feedback" class="pagination-btn" style="background: var(--primary); color: white; border-color: var(--primary);">
                                    <i class="fas fa-paper-plane"></i> Enviar Feedback
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Feedbacks Anteriores -->
                    <div class="feedback-card">
                        <div class="feedback-card-title">Seus Feedbacks Recentes</div>
                        <?php if (empty($feedbacks_anteriores)): ?>
                            <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary); font-style: italic;">
                                <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 10px; color: var(--text-secondary); opacity: 0.3;"></i>
                                <p>Nenhum feedback enviado ainda.</p>
                            </div>
                        <?php else: ?>
                            <div class="feedback-list">
                                <?php foreach ($feedbacks_anteriores as $feedback): ?>
                                    <div class="feedback-item">
                                        <div class="feedback-item-date">
                                            <?php echo date('d/m/Y H:i', strtotime($feedback['data_criacao'])); ?>
                                        </div>
                                        <div class="feedback-item-assunto">
                                            <?php echo htmlspecialchars($feedback['assunto']); ?>
                                        </div>
                                        <div class="feedback-item-desc">
                                            <?php echo htmlspecialchars(substr($feedback['mensagem'], 0, 100)); ?>...
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Feedbacks da Equipe (apenas para gerentes e diretores) -->
                <?php if ($is_gerente_ou_diretor): ?>
                <div class="feedback-card" style="margin-top: 24px;">
                    <div class="feedback-card-title">Feedbacks da Equipe</div>
                    <?php if (empty($feedbacks_todos)): ?>
                        <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary); font-style: italic;">
                            <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 10px; color: var(--text-secondary); opacity: 0.3;"></i>
                            <p>Nenhum feedback enviado pela equipe ainda.</p>
                        </div>
                    <?php else: ?>
                        <div class="feedback-list">
                            <?php foreach ($feedbacks_todos as $feedback): ?>
                                <div class="feedback-item">
                                    <div class="feedback-item-date">
                                        <?php echo date('d/m/Y H:i', strtotime($feedback['data_criacao'])); ?>
                                    </div>
                                    <div class="feedback-item-assunto">
                                        <?php echo htmlspecialchars($feedback['assunto']); ?>
                                    </div>
                                    <div class="feedback-item-desc">
                                        <?php echo htmlspecialchars(substr($feedback['mensagem'], 0, 100)); ?>...
                                    </div>
                                    <div style="margin-top: 6px;">
                                        <span class="feedback-tag <?php echo getTipoFeedbackClass($feedback['tipo']); ?>">
                                            <i class="fas <?php echo getTipoFeedbackIcon($feedback['tipo']); ?>"></i>
                                            <?php echo getTipoFeedbackLabel($feedback['tipo']); ?>
                                        </span>
                                        <span class="feedback-tag" style="background: rgba(100, 116, 139, 0.15); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3);">
                                            <?php echo getCategoriaLabel($feedback['usuario_categoria']); ?>
                                        </span>
                                    </div>
                                    <div style="margin-top: 4px;">
                                        <span style="font-size: 0.75rem; color: var(--primary-light); font-weight: 500;">
                                            <?php echo htmlspecialchars($feedback['usuario_nome']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginação -->
                        <?php if ($total_paginas > 1): ?>
                        <div class="pagination">
                            <?php if ($pagina > 1): ?>
                                <a href="?pagina=<?php echo $pagina - 1; ?>" class="pagination-btn">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <?php if ($i == $pagina): ?>
                                    <span class="pagination-btn active"><?php echo $i; ?></span>
                                <?php elseif (abs($i - $pagina) <= 2 || $i == 1 || $i == $total_paginas): ?>
                                    <a href="?pagina=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                                <?php elseif (abs($i - $pagina) == 3): ?>
                                    <span style="color: var(--text-secondary);">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?pagina=<?php echo $pagina + 1; ?>" class="pagination-btn">
                                    Próximo <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>