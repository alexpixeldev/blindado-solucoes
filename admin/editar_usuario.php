<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: usuarios.php");
    exit();
}

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $nome_real = trim($_POST['nome_real'] ?? '');
    $categoria = $_POST['categoria'];
    $senha = $_POST['senha'];

    if (!empty($senha)) {
        // Se a senha foi preenchida, atualiza com hash
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, nome_real = ?, categoria = ?, senha = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nome, $nome_real, $categoria, $senhaHash, $id);
    } else {
        // Se a senha está vazia, mantém a antiga
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, nome_real = ?, categoria = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $nome_real, $categoria, $id);
    }

    if ($stmt->execute()) {
        header("Location: usuarios.php");
        exit();
    } else {
        $erro = "Erro ao atualizar: " . $conn->error;
    }
}

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: usuarios.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário | Blindado Soluções</title>
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
                    <div class="flex items-center gap-4">
                        <a href="usuarios.php" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Usuário</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações de acesso de <?= htmlspecialchars($usuario['nome_real'] ?: $usuario['nome']) ?>.</p>
                        </div>
                    </div>
                </div>

                <?php if (isset($erro)): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= $erro ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <div class="space-y-2">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome_real" class="form-input" value="<?= htmlspecialchars($usuario['nome_real'] ?? '') ?>" placeholder="Ex: João Silva">
                            </div>
                            
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Nome de Usuário (Login)</label>
                                    <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Categoria / Nível</label>
                                    <div class="relative">
                                        <select name="categoria" class="form-input appearance-none pr-10" required>
                                            <option value="gerente" <?= $usuario['categoria'] == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                            <option value="diretor" <?= $usuario['categoria'] == 'diretor' ? 'selected' : '' ?>>Diretor</option>
                                            <option value="tecnico" <?= $usuario['categoria'] == 'tecnico' ? 'selected' : '' ?>>Técnico</option>
                                            <option value="supervisor" <?= $usuario['categoria'] == 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                            <option value="administrativo" <?= $usuario['categoria'] == 'administrativo' ? 'selected' : '' ?>>Administrativo</option>
                                            <option value="operador" <?= $usuario['categoria'] == 'operador' ? 'selected' : '' ?>>Operador</option>
                                            <option value="colaborador" <?= $usuario['categoria'] == 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="senha" class="form-input" placeholder="Deixe em branco para manter a senha atual">
                                <p class="text-[10px] text-slate-400 italic">Preencha apenas se desejar alterar a senha de acesso deste usuário.</p>
                            </div>

                            <div class="pt-4 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                                <a href="usuarios.php" class="btn-secondary text-center">
                                    <span>Cancelar</span>
                                </a>
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
