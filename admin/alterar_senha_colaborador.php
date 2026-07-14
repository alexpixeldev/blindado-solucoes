<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Verifica permissão: Colaborador ou Gerente
if ($_SESSION['usuario_categoria'] !== 'colaborador' && $_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $usuario_id = $_SESSION['usuario_id'];

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
        $mensagem_tipo = "error";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas não coincidem.";
        $mensagem_tipo = "error";
    } elseif (strlen($nova_senha) < 6) {
        $mensagem = "A nova senha deve ter pelo menos 6 caracteres.";
        $mensagem_tipo = "error";
    } else {
        // Buscar senha atual no banco
        $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($senha_atual, $usuario['senha'])) {
            // Atualizar senha
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt_update->bind_param("si", $nova_senha_hash, $usuario_id);
            
            if ($stmt_update->execute()) {
                $mensagem = "Senha alterada com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao atualizar a senha: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt_update->close();
        } else {
            $mensagem = "Senha atual incorreta.";
            $mensagem_tipo = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Alterar Senha</h1>
                    <p class="mt-1 text-slate-500">Mantenha sua conta segura atualizando sua senha periodicamente.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Form Card -->
                <div class="mx-auto max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <div class="space-y-2">
                                <label class="form-label">Senha Atual</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="password" name="senha_atual" class="form-input pl-11" placeholder="Digite sua senha atual" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Nova Senha</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-key text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="password" name="nova_senha" class="form-input pl-11" placeholder="Mínimo 6 caracteres" required minlength="6">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="form-label">Confirmar Nova Senha</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-check-double text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="password" name="confirmar_senha" class="form-input pl-11" placeholder="Repita a nova senha" required minlength="6">
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 pt-6 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-end">
                                <a href="index.php" class="btn-secondary order-2 sm:order-1">
                                    <i class="fas fa-times"></i>
                                    <span>Cancelar</span>
                                </a>
                                <button type="submit" class="btn-primary order-1 sm:order-2">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Nova Senha</span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-100 flex items-start gap-3 animate-fade-in" style="animation-delay: 0.2s;">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <div class="text-xs text-blue-700 leading-relaxed">
                            <strong>Dica de Segurança:</strong> Use uma combinação de letras maiúsculas, minúsculas, números e símbolos para criar uma senha forte e difícil de adivinhar.
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
