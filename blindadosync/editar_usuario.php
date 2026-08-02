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
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $categoria = $_POST['categoria'];
    $base_id = !empty($_POST['base_id']) ? intval($_POST['base_id']) : null;
    $senha = $_POST['senha'];

    if (!empty($senha)) {
        // Se a senha foi preenchida, atualiza com hash
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, nome_real = ?, whatsapp = ?, categoria = ?, base_id = ?, senha = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $nome, $nome_real, $whatsapp, $categoria, $base_id, $senhaHash, $id);
    } else {
        // Se a senha está vazia, mantém a antiga
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, nome_real = ?, whatsapp = ?, categoria = ?, base_id = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nome, $nome_real, $whatsapp, $categoria, $base_id, $id);
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

$bases = $conn->query("SELECT id, nome FROM bases WHERE status = 'ativo' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    
    
    
    <!-- Google Fonts & Font Awesome -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
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
                            
                            <div class="space-y-2">
                                <label class="form-label">WhatsApp</label>
                                <input type="tel" name="whatsapp" class="form-input" value="<?= htmlspecialchars($usuario['whatsapp'] ?? '') ?>" placeholder="Ex: (11) 99999-9999">
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
                                            <option value="rondante" <?= $usuario['categoria'] == 'rondante' ? 'selected' : '' ?>>Rondante</option>
                                            <option value="colaborador" <?= $usuario['categoria'] == 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="base-field" class="space-y-2" style="<?= ($usuario['categoria'] ?? '') === 'operador' ? '' : 'display:none' ?>">
                                <label class="form-label">Base Vinculada</label>
                                <select name="base_id" class="form-input">
                                    <option value="">Selecione a base</option>
                                    <?php foreach ($bases as $b): ?>
                                        <option value="<?= $b['id'] ?>" <?= ($usuario['base_id'] ?? null) == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="senha" class="form-input" placeholder="Deixe em branco para manter a senha atual">
                                <p class="text-[10px] text-slate-400 italic">Preencha apenas se desejar alterar a senha de acesso deste usuário.</p>
                            </div>

                            <div class="pt-4 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="icon-btn-green" title="Salvar"><i class="fas fa-save" style="font-size:10px"></i></button>
                                <a href="usuarios.php" class="icon-btn" title="Cancelar"><i class="fas fa-times" style="font-size:10px"></i></a>
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
    <script>
        document.querySelector('select[name="categoria"]').addEventListener('change', function() {
            document.getElementById('base-field').style.display = this.value === 'operador' ? 'block' : 'none';
        });
    </script>
</body>
</html>
