<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Verifica se o usuário tem permissão (Gerente ou Administrativo)
if ($_SESSION['usuario_categoria'] !== 'administrativo' && $_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Blindado Soluções</title>
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
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Painel Administrativo</h1>
                    <p class="mt-1 text-slate-500">Bem-vindo à área de gestão administrativa da Blindado Soluções.</p>
                </div>

                <!-- Admin Dashboard Cards -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 animate-slide-up">
                    <!-- Quick Actions -->
                    <div class="admin-card p-6 flex flex-col gap-4 hover:border-primary-300 transition-all group">
                        <div class="h-12 w-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Gestão de Pessoal</h3>
                            <p class="text-sm text-slate-500 mt-1">Gerencie colaboradores, faltas, férias e contracheques.</p>
                        </div>
                        <div class="mt-auto pt-4 flex flex-wrap gap-2">
                            <a href="listar_colaboradores.php" class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-3 py-1.5 rounded-lg transition-colors">Colaboradores</a>
                            <a href="gestao_faltas.php" class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-3 py-1.5 rounded-lg transition-colors">Faltas</a>
                            <a href="ferias_admin.php" class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-3 py-1.5 rounded-lg transition-colors">Férias</a>
                        </div>
                    </div>

                    <div class="admin-card p-6 flex flex-col gap-4 hover:border-blue-300 transition-all group">
                        <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Infraestrutura</h3>
                            <p class="text-sm text-slate-500 mt-1">Controle de edifícios, bases e administradoras parceiras.</p>
                        </div>
                        <div class="mt-auto pt-4 flex flex-wrap gap-2">
                            <a href="edificios.php?tab=edificios" class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">Edifícios</a>
                            <a href="edificios.php?tab=bases" class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">Bases</a>
                        </div>
                    </div>

                    <div class="admin-card p-6 flex flex-col gap-4 hover:border-purple-300 transition-all group">
                        <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Segurança e Acesso</h3>
                            <p class="text-sm text-slate-500 mt-1">Configurações de usuários do sistema e logs de segurança.</p>
                        </div>
                        <div class="mt-auto pt-4 flex flex-wrap gap-2">
                            <a href="usuarios.php" class="text-xs font-bold text-purple-600 hover:text-purple-700 bg-purple-50 px-3 py-1.5 rounded-lg transition-colors">Usuários</a>
                            <a href="controle_dados.php" class="text-xs font-bold text-purple-600 hover:text-purple-700 bg-purple-50 px-3 py-1.5 rounded-lg transition-colors">Dados</a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Placeholder -->
                <div class="mt-12 animate-slide-up" style="animation-delay: 0.2s">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Resumo de Atividades</h2>
                    <div class="admin-card p-8 text-center">
                        <div class="max-w-sm mx-auto">
                            <i class="fas fa-chart-line text-4xl text-slate-200 mb-4"></i>
                            <p class="text-slate-500">As estatísticas e gráficos de desempenho administrativo serão exibidos aqui conforme o uso do sistema.</p>
                        </div>
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
