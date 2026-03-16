<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$mensagem = '';
$mensagem_tipo = 'info';

// Buscar dados atuais do usuário
$stmt = $conn->prepare("SELECT nome, nome_real FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_real = trim($_POST['nome_real'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Verificar se a senha atual está correta
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($senha_atual, $res['senha'])) {
        $mensagem = "A senha atual informada está incorreta.";
        $mensagem_tipo = "error";
    } elseif (!empty($nova_senha)) {
        if ($nova_senha !== $confirmar_senha) {
            $mensagem = "A nova senha e a confirmação não coincidem.";
            $mensagem_tipo = "error";
        } else {
            // Atualizar nome real e senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET nome_real = ?, senha = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nome_real, $senha_hash, $usuario_id);
            if ($stmt->execute()) {
                $mensagem = "Perfil e senha atualizados com sucesso!";
                $mensagem_tipo = "success";
                $_SESSION['usuario_nome_real'] = $nome_real;
            } else {
                $mensagem = "Erro ao atualizar perfil: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        }
    } else {
        // Atualizar apenas o nome real
        $stmt = $conn->prepare("UPDATE usuarios SET nome_real = ? WHERE id = ?");
        $stmt->bind_param("si", $nome_real, $usuario_id);
        if ($stmt->execute()) {
            $mensagem = "Perfil atualizado com sucesso!";
            $mensagem_tipo = "success";
            $_SESSION['usuario_nome_real'] = $nome_real;
        } else {
            $mensagem = "Erro ao atualizar perfil: " . $conn->error;
            $mensagem_tipo = "error";
        }
        $stmt->close();
    }
    
    // Recarregar dados do usuário após atualização
    $usuario['nome_real'] = $nome_real;
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80',
                            500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d',
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
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Meu Perfil</h1>
                    <p class="mt-1 text-slate-500">Gerencie suas informações pessoais e segurança da conta.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <div class="space-y-4">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <i class="fas fa-user-circle text-primary-600"></i>
                                    Informações Pessoais
                                </h2>
                                
                                <div class="space-y-2">
                                    <label class="form-label">Nome Completo</label>
                                    <input type="text" name="nome_real" class="form-input" value="<?= htmlspecialchars($usuario['nome_real'] ?? '') ?>" placeholder="Seu nome completo">
                                </div>
                                
                                <div class="space-y-2 opacity-60">
                                    <label class="form-label">Nome de Usuário (Login)</label>
                                    <input type="text" class="form-input bg-slate-100 cursor-not-allowed" value="<?= htmlspecialchars($usuario['nome']) ?>" readonly>
                                    <p class="text-[10px] text-slate-400 italic">O nome de usuário não pode ser alterado por questões de segurança.</p>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 space-y-4">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <i class="fas fa-shield-alt text-primary-600"></i>
                                    Segurança e Senha
                                </h2>
                                
                                <div class="space-y-2">
                                    <label class="form-label">Senha Atual</label>
                                    <input type="password" name="senha_atual" class="form-input" required placeholder="Digite sua senha atual para confirmar">
                                </div>
                                
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <label class="form-label">Nova Senha</label>
                                        <input type="password" name="nova_senha" class="form-input" placeholder="Deixe em branco para manter">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="form-label">Confirmar Nova Senha</label>
                                        <input type="password" name="confirmar_senha" class="form-input" placeholder="Repita a nova senha">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6">
                                <button type="submit" class="btn-primary w-full sm:w-auto px-8">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                            </div>
                        </form>
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
