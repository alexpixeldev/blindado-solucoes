<?php

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$page_titles = [
    'acoes_disciplinares' => 'Ações Disciplinares',
    'adicionar_contracheque' => 'Adicionar Contracheque',
    'alterar_senha_colaborador' => 'Alterar Senha',
    'cadastrar_base' => 'Cadastrar Base',
    'cadastrar_edificio' => 'Cadastrar Edifício',
    'colaboradores' => 'Meus Contracheques',
    'configurar_entregas' => 'Configurar Entregas',
    'consultar_entrega' => 'Consultar Entregas',
    'consultar_ocorrencia' => 'Consultar Ocorrências',
    'consultar_prestador' => 'Consultar Prestadores',
    'contracheque' => 'Gerenciar Contracheques',
    'contracheques_admin' => 'Gestão de Contracheques',
    'controle_dados' => 'Controle de Dados',
    'criar_colaborador' => 'Criar Colaborador',
    'editar_base' => 'Editar Base',
    'editar_colaborador' => 'Editar Colaborador',
    'editar_contracheque' => 'Editar Contracheque',
    'editar_edificio' => 'Editar Edifício',
    'editar_usuario' => 'Editar Usuário',
    'edificios' => 'Gestão de Edifícios',
    'extras' => 'Gestão de Extras',
    'ferias_admin' => 'Gestão de Férias',
    'gestao_faltas' => 'Gestão de Faltas',
    'index' => 'Dashboard',
    'listar_bases' => 'Listar Bases',
    'listar_colaboradores' => 'Colaboradores',
    'listar_edificios' => 'Listar Edifícios',
    'listar_locacoes' => 'Registros de Locações',
    'minhas_ferias' => 'Minhas Férias',
    'perfil' => 'Meu Perfil',
    'registrar_entrega' => 'Registrar Entrega',
    'registrar_ocorrencia' => 'Registrar Ocorrência',
    'registrar_prestador' => 'Registrar Prestador',
    'usuarios' => 'Gerenciar Usuários',
    'ver_contracheques' => 'Ver Contracheques'
];

$page_title = $page_titles[$current_page] ?? 'Painel Administrativo';

$breadcrumbs = [
    ['label' => 'Início', 'url' => 'index.php']
];

if ($current_page !== 'index') {
    $breadcrumbs[] = ['label' => $page_title, 'url' => null];
}
?>

<header class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b px-4 sm:px-8"
        style="border-color: var(--border); background: rgba(6, 19, 40, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
    <div class="flex items-center gap-3">
        <button id="mobile-sidebar-toggle" class="icon-btn lg:hidden" aria-label="Abrir menu">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="hidden items-center gap-2 text-sm font-medium sm:flex" style="color: var(--text-secondary);" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <div class="flex items-center gap-2">
                    <?php if ($crumb['url']): ?>
                        <a href="<?php echo $crumb['url']; ?>" class="transition-colors" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-secondary)'">
                            <?php echo $crumb['label']; ?>
                        </a>
                    <?php else: ?>
                        <span style="color: var(--text-primary);"><?php echo $crumb['label']; ?></span>
                    <?php endif; ?>

                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <i class="fas fa-chevron-right text-[10px]" style="color: var(--text-secondary); opacity: 0.6;"></i>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="flex items-center gap-2">
        <a href="perfil.php" class="icon-btn" title="Meu Perfil"><i class="fas fa-user-circle"></i></a>

        <button class="icon-btn" title="Notificações"><i class="fas fa-bell"></i><span style="position:absolute;right:8px;top:8px;width:7px;height:7px;border-radius:50%;background:var(--danger);border:2px solid var(--bg-card)"></span></button>

        <div class="mx-1 h-6 w-px sm:mx-2" style="background: var(--border);"></div>

        <a href="logout.php" class="icon-btn-red" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</header>
