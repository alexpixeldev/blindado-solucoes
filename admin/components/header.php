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

<header class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur-md sm:px-8">
    <div class="flex items-center gap-4">
        <button id="mobile-sidebar-toggle" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menu">
            <i class="fas fa-bars text-xl"></i>
        </button>

        <nav class="hidden items-center gap-2 text-sm font-medium text-slate-500 sm:flex" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <div class="flex items-center gap-2">
                    <?php if ($crumb['url']): ?>
                        <a href="<?php echo $crumb['url']; ?>" class="transition-colors hover:text-primary-600">
                            <?php echo $crumb['label']; ?>
                        </a>
                    <?php else: ?>
                        <span class="text-slate-900"><?php echo $crumb['label']; ?></span>
                    <?php endif; ?>
                    
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="flex items-center gap-1 sm:gap-2">
        <a href="perfil.php" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-colors hover:bg-slate-100 hover:text-primary-600" title="Meu Perfil">
            <i class="far fa-user-circle text-lg"></i>
        </a>

        <button class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-colors hover:bg-slate-100 hover:text-primary-600" title="Notificações">
            <i class="far fa-bell text-lg"></i>
            <span class="absolute right-2.5 top-2.5 flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="mx-1 h-6 w-px bg-slate-200 sm:mx-2"></div>

        <a href="logout.php" class="inline-flex items-center gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition-all hover:bg-red-100 active:scale-95 sm:px-4">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden sm:inline">Sair</span>
        </a>
    </div>
</header>
