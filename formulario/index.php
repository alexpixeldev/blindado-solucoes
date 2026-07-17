<?php
require_once '../admin/conexao.php';

$has_selfie_col = false;
$check = @$conn->query("SHOW COLUMNS FROM edificios LIKE 'requer_selfie'");
if ($check && $check->num_rows > 0) $has_selfie_col = true;

$selfie_field = $has_selfie_col ? 'e.requer_selfie,' : '0 AS requer_selfie,';

$result = @$conn->query("
    SELECT e.id, e.nome AS nome_edificio, e.localizacao, $selfie_field b.nome AS nome_base, b.telefone
    FROM edificios e
    JOIN bases b ON e.base_id = b.id
    WHERE b.status = 'ativo'
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
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; }
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

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 480px) {
            /* Stepper: smaller circles, tighter spacing */
            .step-circle { width: 28px !important; height: 28px !important; min-width: 28px !important; font-size: 10px !important; }
            ol[role="list"] > li { padding-right: 2px !important; }
            ol[role="list"] > li > div > .ml-4 { margin-left: 4px !important; }

            /* Form padding tighter on mobile */
            #multi-step-form { padding: 1rem !important; }

            /* Navigation buttons stack better */
            #btn-prev, #btn-next { padding: 0.625rem 1rem !important; font-size: 13px !important; }

            /* Flatpickr day cells smaller */
            .flatpickr-day { width: 30px !important; max-width: 30px !important; height: 30px !important; line-height: 30px !important; font-size: 12px !important; margin: 0 !important; }
            .flatpickr-days { max-width: 100% !important; }
            .dayContainer { max-width: 100% !important; }
        }

        /* ===== FLATPICKR CALENDAR CUSTOM ===== */
        .flatpickr-calendar {
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12), 0 8px 20px rgba(22, 163, 74, 0.08);
            width: 320px;
            max-width: calc(100vw - 2rem);
            overflow: hidden;
            border: 1px solid rgba(22, 163, 74, 0.1);
        }

        .flatpickr-months {
            padding: 16px 12px 10px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            border-radius: 16px 16px 0 0;
        }

        .flatpickr-months .flatpickr-month {
            color: white;
            font-weight: 600;
            font-size: 15px;
            height: auto;
            line-height: 1;
        }

        .flatpickr-months .flatpickr-month-dropdown {
            background: transparent;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 15px;
        }

        .flatpickr-months .flatpickr-month-dropdown option {
            background: #fff;
            color: #1a1a1a;
        }

        .flatpickr-current-month {
            font-weight: 600;
            color: white;
            padding: 0;
        }

        .flatpickr-current-month input.cur-year {
            font-weight: 700;
            color: white;
            font-size: 15px;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            font-weight: 600;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months option {
            background: white;
            color: #334155;
            font-weight: 500;
            padding: 8px;
        }

        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            color: white;
            fill: white;
            padding: 6px;
            border-radius: 8px;
            transition: background 0.2s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .flatpickr-months .flatpickr-prev-month svg,
        .flatpickr-months .flatpickr-next-month svg {
            fill: white;
            width: 14px;
            height: 14px;
        }

        .flatpickr-innerContainer {
            padding: 8px 10px 12px;
            background: #ffffff;
        }

        .flatpickr-weekdays {
            padding: 8px 10px 0;
            background: #ffffff;
        }

        span.flatpickr-weekday {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: transparent;
            border-radius: 8px;
            padding: 6px 0;
            height: auto;
            line-height: 1;
        }

        .flatpickr-days {
            border: none;
            border-radius: 12px;
            width: 100%;
            max-width: 296px;
        }

        .flatpickr-day {
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            border: none;
            line-height: 38px;
            height: 38px;
            margin: 1px;
            width: 38px;
            max-width: 38px;
            transition: all 0.15s ease;
        }

        .flatpickr-day:hover {
            background: #f0fdf4;
            border-color: transparent;
            color: #16a34a;
        }

        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border-color: transparent;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
        }

        .flatpickr-day.today {
            border-color: rgba(22, 163, 74, 0.3);
            font-weight: 600;
        }

        .flatpickr-day.today:hover {
            background: #f0fdf4;
        }

        .flatpickr-day.selected.today {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
        }

        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #cbd5e1;
            background: transparent;
            cursor: not-allowed;
        }

        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: #e2e8f0;
        }

        .flatpickr-day.inRange {
            background: rgba(22, 163, 74, 0.08);
            border-color: transparent;
            box-shadow: none;
            color: #15803d;
        }

        .flatpickr-day:hover.inRange {
            background: rgba(22, 163, 74, 0.15);
        }

        .flatpickr-day.flatpickr-weekday {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: transparent;
            border: none;
        }

        .flatpickr-day.flatpickr-weekday:hover {
            background: transparent;
            cursor: default;
        }

        .flatpickr-day.single {
            border-radius: 10px;
        }

        .flatpickr-day.range-start,
        .flatpickr-day.range-end {
            border-radius: 10px;
        }

        .flatpickr-day.range-start {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .flatpickr-day.range-end {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .dayContainer {
            max-width: 296px;
        }

        /* Animação de abertura */
        .flatpickr-calendar.animate_open {
            animation: calendarOpen 0.2s ease-out;
        }

        @keyframes calendarOpen {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-green-50 via-white to-green-100 text-slate-800 antialiased overflow-x-hidden">

    <!-- Background Decorative Elements -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-green-200/30 blur-3xl animate-pulse-slow"></div>
        <div class="absolute top-[60%] -right-[5%] w-[30%] h-[30%] rounded-full bg-green-300/20 blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="min-h-full flex flex-col py-4 sm:py-12 px-3 sm:px-6 lg:px-8 max-w-4xl mx-auto overflow-hidden">
        
        <!-- Header -->
        <header class="text-center mb-8 animate-fade-in">
            <div class="inline-flex items-center justify-center p-3 bg-white rounded-2xl shadow-sm mb-4 max-w-full">
                <img src="../img/logo_horizontal.png" alt="Blindado Soluções" class="h-10 sm:h-12 w-auto max-w-full object-contain">
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                Formulário de Locação
            </h1>
            <p class="mt-2 text-base sm:text-lg text-slate-600">Preencha os dados abaixo para registrar sua locação.</p>
        </header>

        <!-- Progress Stepper -->
        <nav aria-label="Progress" class="mb-8 sm:mb-10 animate-fade-in px-1" style="animation-delay: 0.1s;">
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

                <form id="multi-step-form" action="salvar_locacao.php" method="POST" enctype="multipart/form-data" class="p-6 sm:p-10">
                    
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
                    <div class="mt-8 sm:mt-12 flex items-center justify-between gap-3 border-t border-slate-100 pt-6 sm:pt-8">
                        <button type="button" id="btn-prev" class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left mr-1 sm:mr-2"></i>
                            Anterior
                        </button>
                        
                        <button type="button" id="btn-next" class="inline-flex items-center px-6 sm:px-8 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-white bg-primary-600 rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-600/20 hover:shadow-primary-600/30 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                            <span>Próxima</span>
                            <i class="fas fa-arrow-right ml-1 sm:ml-2"></i>
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
    <!-- tracking.js para detecção facial -->
    <script src="https://trackingjs.com/bower/tracking.js/build/tracking-min.js"></script>
    <script src="https://trackingjs.com/bower/tracking.js/build/data/face-min.js"></script>
    
    <!-- Passando dados do PHP para o JS -->
    <script>
        const EDIFICIOS_DATA = <?php echo json_encode($edificios); ?>;
    </script>
    
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</body>
</html>
