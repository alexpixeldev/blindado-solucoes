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

// Função auxiliar para verificar se o item está ativo
function isActive($page, $current_page) {
    return $page === $current_page ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'text-slate-300 hover:bg-white/10 hover:text-white';
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
    'operador' => 'Operador'
];
$categoria_label = $categoria_labels[$usuario_categoria] ?? 'Usuário';
?>

<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full flex-col bg-slate-900 text-white transition-transform duration-300 ease-in-out lg:static lg:flex lg:translate-x-0">
    <!-- Sidebar Header -->
    <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
        <a href="index.php" class="flex items-center justify-center w-full">
            <div class="flex h-14 w-full items-center justify-center">
                <img src="../img/logo-blindado-branco.svg" alt="Blindado" class="h-full w-auto object-contain">
            </div>
        </a>
        <button id="mobile-sidebar-close" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6 custom-scrollbar">
        <!-- Dashboard -->
        <a href="index.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('index.php', $current_page); ?>">
            <i class="fas fa-th-large text-lg"></i>
            <span>Dashboard</span>
        </a>

        <!-- Operacional Section -->
        <?php if (!in_array($usuario_categoria, ['colaborador', 'administrativo'])): ?>
        <div class="pt-4">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Operacional</p>
            <div class="space-y-1">
                <a href="listar_locacoes.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('listar_locacoes.php', $current_page); ?>">
                    <i class="fas fa-key text-lg"></i>
                    <span>Locações</span>
                </a>
                <?php if (in_array($usuario_categoria, ['operador', 'supervisor', 'gerente', 'diretor'])): ?>
                    <a href="registrar_ocorrencia.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('registrar_ocorrencia.php', $current_page); ?>">
                        <i class="fas fa-edit text-lg"></i>
                        <span>Registrar Ocorrência</span>
                    </a>
                    <a href="consultar_ocorrencia.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('consultar_ocorrencia.php', $current_page); ?>">
                        <i class="fas fa-search text-lg"></i>
                        <span>Consultar Ocorrências</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Portaria Section -->
        <?php if (!in_array($usuario_categoria, ['administrativo', 'colaborador'])): ?>
        <div class="pt-4">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Portaria</p>
            <div class="space-y-1">
                <a href="registrar_entrega.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('registrar_entrega.php', $current_page); ?>">
                    <i class="fas fa-box text-lg"></i>
                    <span>Registrar Entrega</span>
                </a>
                <a href="consultar_entrega.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('consultar_entrega.php', $current_page); ?>">
                    <i class="fas fa-boxes text-lg"></i>
                    <span>Consultar Entregas</span>
                </a>
                <a href="registrar_prestador.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('registrar_prestador.php', $current_page); ?>">
                    <i class="fas fa-user-shield text-lg"></i>
                    <span>Registrar Prestador</span>
                </a>
                <a href="consultar_prestador.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('consultar_prestador.php', $current_page); ?>">
                    <i class="fas fa-id-card text-lg"></i>
                    <span>Consultar Prestadores</span>
                </a>
                <?php if (in_array($usuario_categoria, ['supervisor', 'gerente', 'diretor'])): ?>
                    <a href="configurar_entregas.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('configurar_entregas.php', $current_page); ?>">
                        <i class="fas fa-cog text-lg"></i>
                        <span>Configurar Entregas</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Infraestrutura Section -->
        <?php if (!in_array($usuario_categoria, ['colaborador', 'administrativo'])): ?>
        <div class="pt-4">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Infraestrutura</p>
            <div class="space-y-1">
                <a href="controle_dados.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('controle_dados.php', $current_page); ?>">
                    <i class="fas fa-database text-lg"></i>
                    <span>Controle de Dados</span>
                </a>
                <a href="edificios.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('edificios.php', $current_page); ?>">
                    <i class="fas fa-building text-lg"></i>
                    <span>Edifícios</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- RH Section -->
        <?php if (in_array($usuario_categoria, ['administrativo', 'colaborador', 'gerente', 'diretor', 'supervisor'])): ?>
        <div class="pt-4">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Recursos Humanos</p>
            <div class="space-y-1">
                <?php if ($is_administrativo || $is_gerente || $is_diretor): ?>
                    <a href="listar_colaboradores.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo in_array($current_page, ['listar_colaboradores.php', 'criar_colaborador.php', 'editar_colaborador.php']) ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'text-slate-300 hover:bg-white/10 hover:text-white'; ?>">
                        <i class="fas fa-users text-lg"></i>
                        <span>Colaboradores</span>
                    </a>
                    <a href="contracheques_admin.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('contracheques_admin.php', $current_page); ?>">
                        <i class="fas fa-file-invoice-dollar text-lg"></i>
                        <span>Gestão Contracheques</span>
                    </a>
                    <a href="ferias_admin.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('ferias_admin.php', $current_page); ?>">
                        <i class="fas fa-umbrella-beach text-lg"></i>
                        <span>Gestão Férias</span>
                    </a>
                    <a href="gestao_faltas.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('gestao_faltas.php', $current_page); ?>">
                        <i class="fas fa-user-clock text-lg"></i>
                        <span>Gestão de Faltas</span>
                    </a>
                    <a href="acoes_disciplinares.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('acoes_disciplinares.php', $current_page); ?>">
                        <i class="fas fa-gavel text-lg"></i>
                        <span>Ações Disciplinares</span>
                    </a>
                    <a href="extras.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('extras.php', $current_page); ?>">
                        <i class="fas fa-plus-circle text-lg"></i>
                        <span>Extras</span>
                    </a>
                <?php elseif ($is_supervisor): ?>
                    <a href="listar_colaboradores.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo in_array($current_page, ['listar_colaboradores.php', 'criar_colaborador.php', 'editar_colaborador.php']) ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'text-slate-300 hover:bg-white/10 hover:text-white'; ?>">
                        <i class="fas fa-users text-lg"></i>
                        <span>Colaboradores</span>
                    </a>
                    <a href="extras.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('extras.php', $current_page); ?>">
                        <i class="fas fa-plus-circle text-lg"></i>
                        <span>Extras</span>
                    </a>
                <?php endif; ?>
                
                <?php if ($is_colaborador): ?>
                    <a href="colaboradores.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('colaboradores.php', $current_page); ?>">
                        <i class="fas fa-user-circle text-lg"></i>
                        <span>Minha Área</span>
                    </a>
                    <a href="minhas_ferias.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('minhas_ferias.php', $current_page); ?>">
                        <i class="fas fa-calendar-alt text-lg"></i>
                        <span>Minhas Férias</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sistema Section -->
        <div class="pt-4">
            <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Minha Conta</p>
            <div class="space-y-1">
                <a href="perfil.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('perfil.php', $current_page); ?>">
                    <i class="fas fa-user-circle text-lg"></i>
                    <span>Meu Perfil</span>
                </a>
                <?php if ($is_gerente || $is_diretor): ?>
                    <a href="usuarios.php" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all <?php echo isActive('usuarios.php', $current_page); ?>">
                        <i class="fas fa-user-cog text-lg"></i>
                        <span>Gerenciar Usuários</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="border-t border-white/10 p-6">
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

<!-- Overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleBtn = document.getElementById('mobile-sidebar-toggle');
    const closeBtn = document.getElementById('mobile-sidebar-close');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
        document.body.classList.toggle('overflow-hidden');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);
});
</script>
