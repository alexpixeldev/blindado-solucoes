<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_nome = $_SESSION['usuario_nome_real'] ?: ($_SESSION['usuario_nome'] ?: 'Usuário');
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

// Níveis de acesso
$is_gerente = ($usuario_categoria === 'gerente');
$is_diretor = ($usuario_categoria === 'diretor');
$is_tecnico = ($usuario_categoria === 'tecnico');
$is_administrativo = ($usuario_categoria === 'administrativo');
$is_colaborador = ($usuario_categoria === 'colaborador');
$is_supervisor = ($usuario_categoria === 'supervisor');
$is_operador = ($usuario_categoria === 'operador');
$is_rondante = ($usuario_categoria === 'rondante');

// Função auxiliar para verificar se o item está ativo
function isActive($page, $current_page) {
    return $page === $current_page ? 'nav-item nav-active' : 'nav-item';
}

// Get first letter for avatar
$avatar_letter = strtoupper(substr($usuario_nome, 0, 1));

// Translate categories
$categoria_labels = [
    'gerente' => 'Gerente',
    'diretor' => 'Diretor',
    'tecnico' => 'Técnico',
    'administrativo' => 'Administrativo',
    'colaborador' => 'Colaborador',
    'supervisor' => 'Supervisor',
    'operador' => 'Operador',
    'rondante' => 'Rondante'
];
$categoria_label = $categoria_labels[$usuario_categoria] ?? 'Usuário';
?>

<aside id="admin-sidebar" class="flex w-72 flex-col text-white" style="background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); border-right: 1px solid var(--border);">
    <!-- Sidebar Header -->
    <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
        <a href="index.php" class="flex items-center justify-center w-full">
            <div class="flex h-14 w-full items-center justify-center">
                <img src="../img/logo-blindado-sync-horizontal-otimizado.svg" alt="Blindado" class="h-full w-auto object-contain">
            </div>
        </a>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-0.5">
        <?php if ($is_rondante): ?>
            <!-- Rondante: acesso restrito -->
            <div class="pt-3">
                <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Rondante</p>
                <div class="space-y-0.5">
                    <a href="rondante.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('rondante.php', $current_page); ?>">
                        <i class="fas fa-motorcycle text-lg"></i>
                        <span>Ronda Atual</span>
                    </a>
                    <a href="feedback.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('feedback.php', $current_page); ?>">
                        <i class="fas fa-comment-dots text-lg"></i>
                        <span>Feedback</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
        <!-- Dashboard -->
        <a href="index.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('index.php', $current_page); ?>">
            <i class="fas fa-th-large text-lg"></i>
            <span>Dashboard</span>
        </a>

        <!-- Operacional Section -->
        <?php if (!in_array($usuario_categoria, ['colaborador', 'administrativo'])): ?>
        <div class="pt-3">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Operacional</p>
            <div class="space-y-0.5">
                <a href="listar_locacoes.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('listar_locacoes.php', $current_page); ?>">
                    <i class="fas fa-key text-lg"></i>
                    <span>Locações</span>
                </a>
                <?php if (in_array($usuario_categoria, ['operador', 'supervisor', 'gerente', 'diretor'])): ?>
                    <a href="registrar_ocorrencia.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('registrar_ocorrencia.php', $current_page); ?>">
                        <i class="fas fa-edit text-lg"></i>
                        <span>Registrar Ocorrência</span>
                    </a>
                    <a href="consultar_ocorrencia.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('consultar_ocorrencia.php', $current_page); ?>">
                        <i class="fas fa-search text-lg"></i>
                        <span>Consultar Ocorrências</span>
                    </a>
                    <a href="feedback.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('feedback.php', $current_page); ?>">
                        <i class="fas fa-comment-dots text-lg"></i>
                        <span>Feedback</span>
                    </a>
                    <?php if (in_array($usuario_categoria, ['gerente', 'diretor'])): ?>
                    <a href="relatorios_gerenciais.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('relatorios_gerenciais.php', $current_page); ?>">
                        <i class="fas fa-chart-line text-lg"></i>
                        <span>Relatórios Gerenciais</span>
                    </a>
                    <a href="consultar_relatorios_gerenciais.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('consultar_relatorios_gerenciais.php', $current_page); ?>">
                        <i class="fas fa-search text-lg"></i>
                        <span>Consulta Relatórios Gerenciais</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($usuario_categoria, ['rondante', 'gerente', 'diretor'])): ?>
                    <a href="rondante_validacao.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('rondante_validacao.php', $current_page); ?>">
                        <i class="fas fa-clipboard-check text-lg"></i>
                        <span>Validação de Ronda</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Portaria Section -->
        <?php if (!in_array($usuario_categoria, ['administrativo', 'colaborador'])): ?>
        <div class="pt-3">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Portaria</p>
            <div class="space-y-0.5">
                <a href="registrar_entrega.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('registrar_entrega.php', $current_page); ?>">
                    <i class="fas fa-box text-lg"></i>
                    <span>Registro de entregas</span>
                </a>
                <!-- 'Consultar Entregas' lateral menu intentionally hidden per request; page remains available at consultar_entrega.php -->
                <a href="registrar_prestador.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('registrar_prestador.php', $current_page); ?>">
                    <i class="fas fa-user-shield text-lg"></i>
                    <span>Registro de prestador</span>
                </a>
                <!-- 'Consultar Prestadores' lateral menu intentionally hidden per request; page remains available at consultar_prestador.php -->
                <?php if (in_array($usuario_categoria, ['supervisor', 'gerente', 'diretor'])): ?>
                    <a href="configurar_entregas.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('configurar_entregas.php', $current_page); ?>">
                        <i class="fas fa-cog text-lg"></i>
                        <span>Configurações Portaria</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Infraestrutura Section -->
        <?php if (!in_array($usuario_categoria, ['colaborador', 'administrativo'])): ?>
        <div class="pt-3">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Infraestrutura</p>
            <div class="space-y-0.5">
                <a href="controle_dados.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('controle_dados.php', $current_page); ?>">
                    <i class="fas fa-database text-lg"></i>
                    <span>Controle de Dados</span>
                </a>
                <a href="edificios.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('edificios.php', $current_page); ?>">
                    <i class="fas fa-building text-lg"></i>
                    <span>Edifícios</span>
                </a>
                <a href="splendia_cadastros.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('splendia_cadastros.php', $current_page); ?>">
                    <i class="fas fa-clipboard-list text-lg"></i>
                    <span>Cadastros Splendia</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- RH Section -->
        <?php if (in_array($usuario_categoria, ['administrativo', 'colaborador', 'gerente', 'diretor', 'supervisor'])): ?>
        <div class="pt-3">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Recursos Humanos</p>
            <div class="space-y-0.5">
                <?php if ($is_administrativo || $is_gerente || $is_diretor): ?>
                    <a href="listar_colaboradores.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo in_array($current_page, ['listar_colaboradores.php', 'criar_colaborador.php', 'editar_colaborador.php']) ? 'nav-item nav-active' : 'nav-item'; ?>">
                        <i class="fas fa-users text-lg"></i>
                        <span>Colaboradores</span>
                    </a>
                    <a href="contracheques_admin.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('contracheques_admin.php', $current_page); ?>">
                        <i class="fas fa-file-invoice-dollar text-lg"></i>
                        <span>Gestão Contracheques</span>
                    </a>
                    <a href="ferias_admin.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('ferias_admin.php', $current_page); ?>">
                        <i class="fas fa-umbrella-beach text-lg"></i>
                        <span>Gestão Férias</span>
                    </a>
                    <a href="gestao_faltas.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('gestao_faltas.php', $current_page); ?>">
                        <i class="fas fa-user-clock text-lg"></i>
                        <span>Gestão de Faltas</span>
                    </a>
                    <a href="acoes_disciplinares.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('acoes_disciplinares.php', $current_page); ?>">
                        <i class="fas fa-gavel text-lg"></i>
                        <span>Ações Disciplinares</span>
                    </a>
                    <a href="extras.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('extras.php', $current_page); ?>">
                        <i class="fas fa-plus-circle text-lg"></i>
                        <span>Extras</span>
                    </a>
                <?php elseif ($is_supervisor): ?>
                    <a href="extras.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('extras.php', $current_page); ?>">
                        <i class="fas fa-plus-circle text-lg"></i>
                        <span>Extras</span>
                    </a>
                <?php endif; ?>
                
                <?php if ($is_colaborador): ?>
                    <a href="colaboradores.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('colaboradores.php', $current_page); ?>">
                        <i class="fas fa-user-circle text-lg"></i>
                        <span>Minha Área</span>
                    </a>
                    <a href="minhas_ferias.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('minhas_ferias.php', $current_page); ?>">
                        <i class="fas fa-calendar-alt text-lg"></i>
                        <span>Minhas Férias</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Sistema Section -->
        <div class="pt-3">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Minha Conta</p>
            <div class="space-y-0.5">
                <a href="perfil.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('perfil.php', $current_page); ?>">
                    <i class="fas fa-user-circle text-lg"></i>
                    <span>Meu Perfil</span>
                </a>
                <?php if ($is_gerente || $is_diretor || $is_supervisor): ?>
                    <a href="usuarios.php" class="group flex items-center gap-3 rounded-xl px-4 py-2 text-sm font-semibold transition-all <?php echo isActive('usuarios.php', $current_page); ?>">
                        <i class="fas fa-user-cog text-lg"></i>
                        <span>Gerenciar Usuários</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="mt-auto border-t border-white/10 p-6">
        <div class="flex items-center gap-4 rounded-2xl bg-white/5 p-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">
                <?php echo $avatar_letter; ?>
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="truncate text-sm font-bold text-white"><?php echo htmlspecialchars($usuario_nome); ?></p>
                <p class="truncate text-[10px] font-medium uppercase tracking-wider text-slate-400"><?php echo $categoria_label; ?></p>
            </div>
        </div>
    </div>
</aside>

<style>
    .sidebar-scroll::-webkit-scrollbar {
        display: none;
    }
    .sidebar-scroll {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
