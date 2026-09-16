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

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; }
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 80% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
        .pop-in { animation: popIn 0.5s ease-out; }

        @keyframes floatBalloon { 0% { transform: translateY(30px) scale(0.95); opacity: 0; } 100% { transform: translateY(0) scale(1); opacity: 1; } }
        .baloon-anim { animation: floatBalloon 0.35s ease-out; }

        #tela-aviso { display: none; }
        #tela-aviso.visivel { display: block; }

        /* Balão de fala (WhatsApp) */
        .baloon {
            position: fixed;
            z-index: 999;
            bottom: 24px;
            left: 24px;
            max-width: 340px;
            width: calc(100% - 48px);
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.20), 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .baloon::before {
            content: '';
            position: absolute;
            left: 26px;
            bottom: -12px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 12px solid #ffffff;
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-green-50 via-white to-green-100 text-slate-800 antialiased overflow-x-hidden">

    <!-- Background Decorative Elements -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-green-200/30 blur-3xl animate-pulse-slow"></div>
        <div class="absolute top-[60%] -right-[5%] w-[30%] h-[30%] rounded-full bg-green-300/20 blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <?php $duplicado = isset($_GET['duplicado']); ?>

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

        <!-- Main Card -->
        <main class="flex-1 animate-slide-up" style="animation-delay: 0.2s;">
            <div class="glass rounded-3xl shadow-xl shadow-green-900/5 overflow-hidden border border-white/50">
                <div class="p-6 sm:p-12 text-center">

                    <div id="tela-sucesso">
                        <div class="pop-in text-[5em] text-green-600 mb-5">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Parabéns!</h1>
                        <p class="mt-2 text-base sm:text-lg text-slate-600">Sua locação foi enviada com sucesso!</p>

                        <div id="area-whatsapp" style="display:none; margin-top: 8px;">
                            <p class="text-slate-600 text-sm sm:text-base">Para finalizar o atendimento, envie os dados pelo WhatsApp:</p>
                            <button type="button" id="btn-finalizar-whatsapp" class="mt-4 inline-flex items-center justify-center gap-3 px-6 py-3 text-sm font-bold text-white bg-[#25d366] rounded-xl shadow-lg shadow-[#25d366]/30 hover:bg-[#1ebe5b] transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                                <i class="fab fa-whatsapp text-lg"></i> Finalizar no WhatsApp
                            </button>
                            <p id="aviso-popup" class="hidden mt-3 text-sm text-slate-500">
                                Se o WhatsApp não abriu automaticamente, clique no botão acima.
                            </p>
                        </div>
                    </div>

                    <div id="tela-aviso">
                        <div class="pop-in text-[5em] text-amber-500 mb-5">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Atenção!</h1>
                        <p class="mt-2 text-base sm:text-lg text-slate-600">
                            Estas informações já foram enviadas anteriormente para este apartamento nesta data.
                        </p>
                        <div class="mt-6 max-w-lg mx-auto flex items-start gap-3 p-4 bg-amber-50 border border-amber-300 rounded-2xl text-left">
                            <i class="fas fa-info-circle text-amber-500 mt-0.5"></i>
                            <div class="text-sm text-amber-900 leading-relaxed">
                                <strong>Nada foi registrado novamente.</strong>
                                <span>Se você ainda precisa da informação, escolha reenviar via WhatsApp abaixo.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="index.php" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white bg-primary-600 rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-600/20 hover:shadow-primary-600/30 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                            Preencher Novo Formulário
                        </a>
                    </div>

                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-8 text-center text-sm text-slate-500 animate-fade-in" style="animation-delay: 0.4s;">
            <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
        </footer>
    </div>

    <?php if ($duplicado): ?>
    <div class="baloon baloon-anim" id="baloon-dup">
        <div class="p-5">
            <div class="flex items-center gap-2.5 mb-3 text-[#075e54] font-bold text-[15px]">
                <i class="fab fa-whatsapp text-2xl text-[#25d366]"></i>
                Reenviar via WhatsApp
            </div>
            <p class="mb-4 text-sm text-slate-600 leading-relaxed">
                Deseja reenviar as informações preenchidas pelo WhatsApp?
            </p>
            <div class="flex gap-2.5">
                <button type="button" id="btn-reeenviar" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#25d366] text-white text-[13px] font-bold hover:bg-[#1ebe5b] transition-colors cursor-pointer">
                    <i class="fab fa-whatsapp"></i> Sim, reenviar
                </button>
                <button type="button" id="btn-nao" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-600 text-[13px] font-semibold hover:bg-slate-200 transition-colors cursor-pointer">
                    Não
                </button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var url = sessionStorage.getItem('reativacao_locacao');
            if (!url) {
                document.getElementById('baloon-dup').style.display = 'none';
                return;
            }
            var telaSucesso = document.getElementById('tela-sucesso');
            var telaAviso = document.getElementById('tela-aviso');
            if (telaSucesso) telaSucesso.style.display = 'none';
            if (telaAviso) telaAviso.classList.add('visivel');

            document.getElementById('btn-reeenviar').addEventListener('click', function () {
                sessionStorage.removeItem('reativacao_locacao');
                window.location.href = url;
            });
            document.getElementById('btn-nao').addEventListener('click', function () {
                sessionStorage.removeItem('reativacao_locacao');
                document.getElementById('baloon-dup').style.display = 'none';
            });
        })();
    </script>
    <?php else: ?>
    <script>
        (function () {
            var url = sessionStorage.getItem('whatsapp_locacao');
            var area = document.getElementById('area-whatsapp');
            var btn = document.getElementById('btn-finalizar-whatsapp');
            var aviso = document.getElementById('aviso-popup');
            if (!url || !area || !btn) return;

            area.style.display = 'block';
            btn.addEventListener('click', function () {
                sessionStorage.removeItem('whatsapp_locacao');
                window.location.href = url;
            });

            var janela = window.open(url, '_blank');
            if (!janela) {
                aviso.classList.remove('hidden');
            }
        })();
    </script>
    <?php endif; ?>
</body>
</html>