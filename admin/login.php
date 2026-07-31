<?php
require_once 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Erro de validação CSRF. Por favor, recarregue a página.');
    }
    
    $nome = trim($_POST['nome']);
    $senha = $_POST['senha'];

    $stmt = $conn->prepare('SELECT id, nome, nome_real, senha, categoria, base_id FROM usuarios WHERE nome = ?');
    $stmt->bind_param('s', $nome);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nome_real'] = $usuario['nome_real'];
        $_SESSION['usuario_categoria'] = strtolower(trim($usuario['categoria']));
        $_SESSION['usuario_base_id'] = $usuario['base_id'] ?? null;

        header('Location: index.php');
        exit();
    } else {
        $erro = 'Usuário ou senha incorretos.';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#EDFBF0', 100: '#D8F5DE', 200: '#B4EAC0', 300: '#8ADF9E', 400: '#5BD077',
                            500: '#3BBE55', 600: '#25A937', 700: '#1E8C2E', 800: '#186F24', 900: '#114A19',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(18, 37, 66, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-[#061328] via-[#010B1E] to-[#0a1f3d] text-slate-200 antialiased overflow-y-auto sm:overflow-hidden">

    <!-- Background Decorative Elements -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-[#25A937]/15 blur-3xl animate-pulse"></div>
        <div class="absolute top-[60%] -right-[5%] w-[30%] h-[30%] rounded-full bg-[#0792F2]/15 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    <div class="flex min-h-full flex-col justify-center py-6 px-4 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md animate-fade-in">
            <div class="flex justify-center">
                <div class="inline-flex items-center justify-center p-3 rounded-2xl shadow-lg bg-[#122542] border border-white/10">
                    <img src="../img/logo-blindado-sync-horizontal-otimizado.svg" alt="Blindado Soluções" class="h-12 w-auto object-contain">
                </div>
            </div>
            <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-slate-100">Acesse o Painel</h2>
            <p class="mt-1 text-center text-xs text-slate-400">Entre com suas credenciais administrativas</p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-[400px] animate-slide-up">
            <div class="glass px-6 py-8 shadow-xl shadow-black/40 sm:rounded-3xl sm:px-10">
                
                <?php if ($erro): ?>
                    <div class="mb-4 p-3 bg-red-500/10 border-l-4 border-red-500 rounded-r-lg flex items-start gap-2 animate-fade-in">
                        <i class="fas fa-exclamation-circle text-red-400 mt-0.5 text-sm"></i>
                        <div class="text-xs text-red-300 font-medium"><?php echo $erro; ?></div>
                    </div>
                <?php endif; ?>

                <form class="space-y-4" action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div>
                        <label for="nome" class="block text-xs font-semibold text-slate-300">Usuário</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-slate-500 text-xs"></i>
                            </div>
                            <input id="nome" name="nome" type="text" required autofocus
                                class="block w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-9 pr-3 text-slate-100 placeholder-slate-500 transition-all focus:border-primary-500 focus:bg-white/10 focus:outline-none focus:ring-4 focus:ring-primary-500/15 text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="senha" class="block text-xs font-semibold text-slate-300">Senha</label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-500 text-xs"></i>
                            </div>
                            <input id="senha" name="senha" type="password" required
                                class="block w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-9 pr-3 text-slate-100 placeholder-slate-500 transition-all focus:border-primary-500 focus:bg-white/10 focus:outline-none focus:ring-4 focus:ring-primary-500/15 text-sm">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" class="h-3.5 w-3.5 rounded border-white/25 bg-white/5 text-primary-500 focus:ring-primary-500 focus:ring-offset-[#010B1E]">
                            <label for="remember-me" class="ml-2 block text-xs text-slate-400">Lembrar-me</label>
                        </div>
                        <div class="text-xs">
                            <a href="#" class="font-semibold text-primary-400 hover:text-primary-300">Esqueceu a senha?</a>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="flex w-full justify-center items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-black/30 transition-all hover:bg-primary-500 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-[#010B1E]">
                            <span>Entrar no Painel</span>
                            <i class="fas fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <p class="mt-6 text-center text-[10px] text-slate-500 uppercase tracking-widest font-medium">
                &copy; <?php echo date('Y'); ?> Blindado Soluções. Tecnologia em Segurança.
            </p>
        </div>
    </div>

</body>
</html>
