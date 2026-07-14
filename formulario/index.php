<?php
require_once '../admin/conexao.php';

$result = $conn->query("
    SELECT e.id, e.nome AS nome_edificio, b.nome AS nome_base, b.telefone
    FROM edificios e
    JOIN bases b ON e.base_id = b.id
    ORDER BY e.nome ASC
");
$edificios = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <link rel="icon" type="image/png" href="img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Locação | Blindado Soluções</title>
    
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
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
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
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.4s ease-out; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #22c55e; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #16a34a; }

        .input-focus-effect:focus {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
            border-color: #22c55e;
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-green-50 via-white to-green-100 text-slate-800 antialiased overflow-x-hidden">

    <!-- Background Decorative Elements -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-green-200/30 blur-3xl animate-pulse-slow"></div>
        <div class="absolute top-[60%] -right-[5%] w-[30%] h-[30%] rounded-full bg-green-300/20 blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="min-h-full flex flex-col py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
        
        <!-- Header -->
        <header class="text-center mb-8 animate-fade-in">
            <div class="inline-flex items-center justify-center p-3 bg-white rounded-2xl shadow-sm mb-4">
                <img src="../img/logo_horizontal.png" alt="Blindado Soluções" class="h-12 w-auto object-contain">
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                Formulário de Locação
            </h1>
            <p class="mt-2 text-lg text-slate-600">Preencha os dados abaixo para registrar sua locação.</p>
        </header>

        <!-- Progress Stepper -->
        <nav aria-label="Progress" class="mb-10 animate-fade-in" style="animation-delay: 0.1s;">
            <ol role="list" class="flex items-center justify-between w-full">
                <?php for($i=1; $i<=6; $i++): ?>
                <li class="relative flex-1 <?php echo $i < 6 ? 'pr-4' : ''; ?>">
                    <div class="flex items-center group" id="step-indicator-<?php echo $i; ?>">
                        <span class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300 step-circle <?php echo $i === 1 ? 'bg-primary-600 border-primary-600 text-white' : 'bg-white border-slate-300 text-slate-500'; ?>" id="circle-<?php echo $i; ?>">
                            <?php if($i < 1): ?>
                                <i class="fas fa-check text-sm"></i>
                            <?php else: ?>
                                <span class="text-sm font-bold"><?php echo $i; ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if($i < 6): ?>
                        <div class="ml-4 flex-1 h-0.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 transition-all duration-500 step-line" id="line-<?php echo $i; ?>" style="width: 0%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="absolute -bottom-6 left-0 text-[10px] font-medium uppercase tracking-wider text-slate-400 hidden sm:block">
                        <?php 
                            $titles = ["Tipo", "Local", "Pessoas", "Veículo", "Check-in", "Revisão"];
                            echo $titles[$i-1];
                        ?>
                    </span>
                </li>
                <?php endfor; ?>
            </ol>
        </nav>

        <!-- Main Form Container -->
        <main class="flex-1 animate-slide-up" style="animation-delay: 0.2s;">
            <div class="glass rounded-3xl shadow-xl shadow-green-900/5 overflow-hidden border border-white/50">
                
                <!-- Error Alert -->
                <div id="global-error-message" class="hidden m-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-start gap-3 animate-fade-in">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div class="text-sm text-red-700 font-medium"></div>
                </div>

                <form id="multi-step-form" action="salvar_locacao.php" method="POST" class="p-6 sm:p-10">
                    
                    <!-- Steps Container -->
                    <div id="steps-container">
                        <?php include 'step1_user_type.php'; ?>
                        <?php include 'step2_edificio_apartamento.php'; ?>
                        <?php include 'step3_inquilinos.php'; ?>
                        <?php include 'step4_veiculo.php'; ?>
                        <?php include 'step5_detalhes_adicionais.php'; ?>
                        <?php include 'step6_preferencias_contato.php'; ?>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="mt-12 flex items-center justify-between gap-4 border-t border-slate-100 pt-8">
                        <button type="button" id="btn-prev" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Anterior
                        </button>
                        
                        <button type="button" id="btn-next" class="inline-flex items-center px-8 py-3 text-sm font-semibold text-white bg-primary-600 rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-600/20 hover:shadow-primary-600/30 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                            <span>Próxima</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-8 text-center text-sm text-slate-500 animate-fade-in" style="animation-delay: 0.4s;">
            <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    
    <!-- Passando dados do PHP para o JS -->
    <script>
        const EDIFICIOS_DATA = <?php echo json_encode($edificios); ?>;
    </script>
    
    <script src="script.js"></script>
</body>
</html>
