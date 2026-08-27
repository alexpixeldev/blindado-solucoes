<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['supervisor', 'gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['categoria'], $_POST['senha'])) {
    $nome = trim($_POST['nome']);
    $nome_real = trim($_POST['nome_real'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    // Supervisor só pode criar usuários do tipo Colaborador
    $categoria = $usuario_categoria === 'supervisor' ? 'colaborador' : $_POST['categoria'];
    $base_id = !empty($_POST['base_id']) ? intval($_POST['base_id']) : null;
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, nome_real, whatsapp, categoria, base_id, senha) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $nome, $nome_real, $whatsapp, $categoria, $base_id, $senha);
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

$bases = $conn->query("SELECT id, nome FROM bases WHERE status = 'ativo' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$check = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'base_id'");
$hasBaseId = $check && $check->num_rows > 0;
if ($hasBaseId) {
    $usuarios = $conn->query("SELECT u.*, b.nome as base_nome FROM usuarios u LEFT JOIN bases b ON u.base_id = b.id WHERE u.categoria != 'colaborador' ORDER BY u.categoria, u.nome")->fetch_all(MYSQLI_ASSOC);
} else {
    $usuarios = $conn->query("SELECT * FROM usuarios WHERE categoria != 'colaborador' ORDER BY categoria, nome")->fetch_all(MYSQLI_ASSOC);
}

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
    
    
    
    <!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</noscript>
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        @media (min-width: 1024px) {
            .usuario-card { margin-top: 66px; }
        }
    </style>
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
                        <div class="admin-card sticky top-24 usuario-card">
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
                                    <label class="form-label">WhatsApp</label>
                                    <input type="tel" name="whatsapp" class="form-input" placeholder="Ex: (11) 99999-9999">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Senha</label>
                                    <input type="password" name="senha" class="form-input" required placeholder="••••••••">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Categoria / Nível</label>
                                    <div class="relative">
                                        <select name="categoria" class="form-input appearance-none pr-10" required <?= $usuario_categoria === 'supervisor' ? 'disabled' : '' ?>>
                                            <option value="colaborador" <?= $usuario_categoria === 'supervisor' ? 'selected' : '' ?>>Colaborador</option>
                                            <?php if ($usuario_categoria !== 'supervisor'): ?>
                                                <option value="gerente">Gerente</option>
                                                <option value="diretor">Diretor</option>
                                                <option value="tecnico">Técnico</option>
                                                <option value="supervisor">Supervisor</option>
                                                <option value="administrativo">Administrativo</option>
                                                <option value="operador">Operador</option>
                                                <option value="rondante">Rondante</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php if ($usuario_categoria === 'supervisor'): ?>
                                            <input type="hidden" name="categoria" value="colaborador">
                                            <p class="mt-2 text-xs text-slate-500">Supervisores podem criar apenas usuários do tipo <strong>Colaborador</strong>.</p>
                                        <?php endif; ?>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div id="base-field" class="space-y-2" style="display:none">
                                    <label class="form-label">Base Vinculada</label>
                                    <select name="base_id" class="form-input">
                                        <option value="">Selecione a base</option>
                                        <?php foreach ($bases as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="icon-btn-green" title="Criar Usuário"><i class="fas fa-user-plus" style="font-size:10px"></i></button>
                            </form>
                        </div>
                    </div>

                    <script>
                        document.querySelector('select[name="categoria"]').addEventListener('change', function() {
                            document.getElementById('base-field').style.display = this.value === 'operador' ? 'block' : 'none';
                        });
                    </script>

                    <!-- Users List -->
                    <div class="lg:col-span-2 animate-slide-up" style="animation-delay: 0.1s;">
                        <div class="overflow-x-auto">
                            <table class="admin-table" data-no-cell-copy>
                                <thead>
                                    <tr>
                                        <th>Nome / Login</th>
                                        <th>Categoria</th>
                                        <th>Base</th>
                                        <th class="text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="group">
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
                                                        'rondante' => 'bg-amber-100 text-amber-700',
                                                        'colaborador' => 'bg-slate-100 text-slate-700'
                                                    ];
                                                    $color = $cat_colors[$usuario['categoria']] ?? 'bg-slate-100 text-slate-700';
                                                ?>
                                                <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider <?= $color ?>">
                                                    <?= ucfirst($usuario['categoria']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($usuario['categoria'] === 'operador' && !empty($usuario['base_nome'])): ?>
                                                    <span class="text-xs text-slate-500"><?= htmlspecialchars($usuario['base_nome']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-300">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                                    <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="icon-btn" title="Editar"><i class="fas fa-edit" style="font-size:10px"></i></a>
                                                    <form method="POST" onsubmit="return confirm('Deseja realmente excluir este usuário?');" class="inline">
                                                        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                                        <button type="submit" name="delete_usuario" class="icon-btn-red" title="Excluir"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
                                                    </form>
                                                </div>
                                                <!-- Mobile actions -->
                                                <div class="flex justify-end gap-2 sm:hidden">
                                                    <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="icon-btn" title="Editar"><i class="fas fa-edit" style="font-size:10px"></i></a>
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
