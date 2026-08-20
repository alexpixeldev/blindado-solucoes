<?php
require_once '../blindadosync/conexao.php';

// Edifício Splendia (id 61) e base Nova Guaraparí (id 1)
$edificio = $conn->query("SELECT e.id, e.nome, b.nome AS nome_base, b.telefone
                          FROM edificios e JOIN bases b ON e.base_id = b.id
                          WHERE e.id = 61 LIMIT 1")->fetch_assoc();
if (!$edificio) {
    die('Edifício Splendia não encontrado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Moradores | Edifício Splendia</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac',
                            400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d',
                            800: '#166534', 900: '#14532d',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } }
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; }
        .glass {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.4s ease-out; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #22c55e; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #16a34a; }
        .input-focus-effect:focus {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
            border-color: #22c55e;
        }
        .pessoa-row { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-green-50 via-white to-green-100 text-slate-800 antialiased overflow-x-hidden">

    <!-- Background Decor -->
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
                Atualização de Cadastro
            </h1>
            <p class="mt-2 text-base sm:text-lg text-slate-600">
                Edifício <strong class="text-primary-700">Splendia</strong> — Preencha os dados dos moradores do seu apartamento.
            </p>
        </header>

        <!-- Progress Stepper -->
        <nav aria-label="Progress" class="mb-10 sm:mb-12 animate-fade-in px-1" style="animation-delay: 0.1s;">
            <ol role="list" class="flex items-center w-full">
                <?php for($i=1; $i<=3; $i++): ?>
                <li class="relative flex-1 flex flex-col items-center">
                    <div class="flex w-full items-center" id="step-indicator-<?php echo $i; ?>">
                        <div class="<?php echo $i === 1 ? 'invisible' : ''; ?> h-0.5 flex-1 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 transition-all duration-500 step-line" id="line-l-<?php echo $i; ?>" style="width: 0%"></div>
                        </div>
                        <span class="mx-1 sm:mx-2 flex items-center justify-center w-10 h-10 shrink-0 rounded-full border-2 transition-all duration-300 step-circle <?php echo $i === 1 ? 'bg-primary-600 border-primary-600 text-white' : 'bg-white border-slate-300 text-slate-500'; ?>" id="circle-<?php echo $i; ?>">
                            <span class="text-sm font-bold"><?php echo $i; ?></span>
                        </span>
                        <div class="<?php echo $i === 3 ? 'invisible' : ''; ?> h-0.5 flex-1 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 transition-all duration-500 step-line" id="line-r-<?php echo $i; ?>" style="width: 0%"></div>
                        </div>
                    </div>
                    <span class="mt-3 text-[10px] font-medium uppercase tracking-wider text-slate-400 hidden sm:block">
                        <?php $titles = ["Apartamento", "Moradores", "Revisão"]; echo $titles[$i-1]; ?>
                    </span>
                </li>
                <?php endfor; ?>
            </ol>
        </nav>

        <!-- Form -->
        <main class="flex-1 animate-slide-up" style="animation-delay: 0.2s;">
            <div class="glass rounded-3xl shadow-xl shadow-green-900/5 overflow-hidden border border-white/50">

                <div id="global-error-message" class="hidden m-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-start gap-3 animate-fade-in">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div class="text-sm text-red-700 font-medium"></div>
                </div>

                <form id="splendia-form" action="salvar_cadastro.php" method="POST" enctype="multipart/form-data" class="p-6 sm:p-10">

                    <div id="steps-container">
                        <!-- STEP 1: Apartamento -->
                        <section class="step-content active" data-step="1">
                            <h2 class="text-xl font-bold text-slate-900 mb-1 text-center">Qual é o seu apartamento?</h2>
                            <p class="text-sm text-slate-500 mb-6 text-center">Informe o número do apartamento que você representa (ex: 101, 202, 304).</p>
                            <div class="space-y-2 max-w-md mx-auto">
                                <label class="form-label text-sm font-semibold text-slate-700 text-center block" for="apartamento">Número do Apartamento *</label>
                                <input type="text" name="apartamento" id="apartamento" class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder-slate-400 input-focus-effect outline-none transition-all text-center" placeholder="Ex: 101" inputmode="numeric">
                            </div>
                        </section>

                        <!-- STEP 2: Pessoas -->
                        <section class="step-content" data-step="2">
                            <div class="flex flex-col gap-1 mb-6">
                                <h2 class="text-xl font-bold text-slate-900">Quem mora no apartamento <?php echo '<span id="apt-resumo" class="text-primary-600"></span>'; ?></h2>
                                <p class="text-sm text-slate-500">Adicione todos os moradores. Marque "Locatário anual" quando a pessoa for inquilino com contrato anual.</p>
                            </div>

                            <div id="pessoas-container" class="space-y-4">
                                <!-- rows injetados via JS -->
                            </div>

                            <button type="button" id="btn-add-pessoa" class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-primary-700 bg-primary-50 border border-primary-200 rounded-xl hover:bg-primary-100 transition-all">
                                <i class="fas fa-plus"></i> Adicionar morador
                            </button>
                        </section>

                        <!-- STEP 3: Revisão -->
                        <section class="step-content" data-step="3">
                            <h2 class="text-xl font-bold text-slate-900 mb-1">Confira os dados</h2>
                            <p class="text-sm text-slate-500 mb-6">Revise as informações antes de enviar.</p>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Apartamento</p>
                                    <p class="text-lg font-bold text-slate-900" id="revisao-apartamento">—</p>
                                </div>
                                <div class="border-t border-slate-200 pt-4">
                                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Moradores (<span id="revisao-qtd">0</span>)</p>
                                    <div id="revisao-pessoas" class="space-y-2"></div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 mb-6">
                                <p class="text-xs font-bold uppercase tracking-widest text-primary-600 mb-2">Ao finalizar</p>
                                <p class="text-sm text-slate-600">
                                    Seus dados serão salvos em nosso sistema e enviados para o WhatsApp da
                                    <strong>Base Nova Guaraparí</strong> para atualização do cadastro do edifício.
                                </p>
                            </div>
                        </section>
                    </div>

                    <!-- Navigation -->
                    <div class="mt-8 sm:mt-12 flex items-center justify-between gap-3 border-t border-slate-100 pt-6 sm:pt-8">
                        <button type="button" id="btn-prev" class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left mr-1 sm:mr-2"></i> Anterior
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

    <script>
        const EDIFICIO_TELEFONE = <?php echo json_encode($edificio['telefone']); ?>;
        const EDIFICIO_NOME = <?php echo json_encode($edificio['nome']); ?>;
    </script>
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</body>
</html>