<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$mensagem = '';
$mensagem_tipo = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['categoria'], $_POST['senha'])) {
    $nome = trim($_POST['nome']);
    $nome_real = trim($_POST['nome_real'] ?? '');
    $categoria = $_POST['categoria'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, nome_real, categoria, senha) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nome, $nome_real, $categoria, $senha);
    if ($stmt->execute()) {
        $mensagem = "Usuário '$nome' criado com sucesso!";
        $mensagem_tipo = "success";
    } else {
        $mensagem = "Erro ao criar usuário: " . $conn->error;
        $mensagem_tipo = "error";
    }
    $stmt->close();
}

if (isset($_POST['delete_usuario'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Usuário excluído com sucesso!";
        $_SESSION['mensagem_tipo'] = "success";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir usuário: " . $conn->error;
        $_SESSION['mensagem_tipo'] = "error";
    }
    $stmt->close();
    header("Location: usuarios.php");
    exit();
}

$usuarios = $conn->query("SELECT * FROM usuarios WHERE categoria != 'colaborador' ORDER BY categoria, nome")->fetch_all(MYSQLI_ASSOC);

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gerenciar Usuários</h1>
                    <p class="mt-1 text-slate-500">Controle de acesso e permissões do painel administrativo.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- New User Form -->
                    <div class="lg:col-span-1 animate-slide-up">
                        <div class="admin-card sticky top-24">
                            <h2 class="mb-6 text-lg font-bold text-slate-900">Novo Usuário</h2>
                            <form method="POST" class="space-y-4">
                                <div class="space-y-2">
                                    <label class="form-label">Nome Completo</label>
                                    <input type="text" name="nome_real" class="form-input" placeholder="Ex: João Silva">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Nome de Usuário (Login)</label>
                                    <input type="text" name="nome" class="form-input" required placeholder="Ex: admin_base">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Senha</label>
                                    <input type="password" name="senha" class="form-input" required placeholder="••••••••">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Categoria / Nível</label>
                                    <div class="relative">
                                        <select name="categoria" class="form-input appearance-none pr-10" required>
                                            <option value="gerente">Gerente</option>
                                            <option value="diretor">Diretor</option>
                                            <option value="tecnico">Técnico</option>
                                            <option value="supervisor">Supervisor</option>
                                            <option value="administrativo">Administrativo</option>
                                            <option value="operador">Operador</option>
                                            <option value="colaborador">Colaborador</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary w-full mt-4">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Criar Usuário</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Users List -->
                    <div class="lg:col-span-2 animate-slide-up" style="animation-delay: 0.1s;">
                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome / Login</th>
                                        <th>Categoria</th>
                                        <th class="text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="group">
                                            <td>
                                                <span class="text-xs font-bold text-slate-400">#<?= $usuario['id'] ?></span>
                                            </td>
                                            <td>
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-slate-900"><?= htmlspecialchars($usuario['nome_real'] ?: $usuario['nome']) ?></span>
                                                    <span class="text-[10px] text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($usuario['nome']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $cat_colors = [
                                                        'gerente' => 'bg-purple-100 text-purple-700',
                                                        'diretor' => 'bg-indigo-100 text-indigo-700',
                                                        'tecnico' => 'bg-cyan-100 text-cyan-700',
                                                        'supervisor' => 'bg-blue-100 text-blue-700',
                                                        'administrativo' => 'bg-green-100 text-green-700',
                                                        'operador' => 'bg-orange-100 text-orange-700',
                                                        'colaborador' => 'bg-slate-100 text-slate-700'
                                                    ];
                                                    $color = $cat_colors[$usuario['categoria']] ?? 'bg-slate-100 text-slate-700';
                                                ?>
                                                <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider <?= $color ?>">
                                                    <?= ucfirst($usuario['categoria']) ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                                    <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Deseja realmente excluir este usuário?');" class="inline">
                                                        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                                        <button type="submit" name="delete_usuario" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <!-- Mobile actions -->
                                                <div class="flex justify-end gap-2 sm:hidden">
                                                    <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="text-slate-600 p-2"><i class="fas fa-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
