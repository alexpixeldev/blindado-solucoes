<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['supervisor', 'gerente'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: edificios.php?tab=sindicos");
    exit();
}

// Buscar síndico
$stmt = $conn->prepare("SELECT s.*, e.nome AS nome_edificio 
                        FROM sindicos s 
                        LEFT JOIN edificios e ON s.edificio_id = e.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sindico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sindico) {
    $_SESSION['mensagem'] = "Síndico não encontrado!";
    $_SESSION['mensagem_tipo'] = "error";
    header("Location: edificios.php?tab=sindicos");
    exit();
}

// Buscar edifícios para o select
$edificios = $conn->query("SELECT e.id, e.nome, b.nome AS nome_base 
                           FROM edificios e 
                           JOIN bases b ON e.base_id = b.id 
                           ORDER BY b.nome, e.nome")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $edificio_id = !empty($_POST['edificio_id']) ? $_POST['edificio_id'] : null;
    
    if ($nome) {
        // Pegar o nome antigo antes de atualizar
        $stmt_old = $conn->prepare("SELECT nome FROM sindicos WHERE id = ?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $old_sindico = $stmt_old->get_result()->fetch_assoc();
        $old_nome = $old_sindico['nome'];
        $stmt_old->close();
        
        // Atualizar o síndico
        $stmt = $conn->prepare("UPDATE sindicos SET nome = ?, telefone = ?, email = ?, edificio_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $nome, $telefone, $email, $edificio_id, $id);
        
        if ($stmt->execute()) {
            // Sincronizar o nome na tabela edifícios
            $stmt_sync = $conn->prepare("UPDATE edificios SET sindico_nome = ? WHERE sindico_nome = ?");
            $stmt_sync->bind_param("ss", $nome, $old_nome);
            $stmt_sync->execute();
            $stmt_sync->close();
            
            $_SESSION['mensagem'] = "Síndico atualizado com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            header("Location: edificios.php?tab=sindicos");
            exit();
        } else {
            $erro = "Erro ao atualizar síndico: " . $conn->error;
        }
        $stmt->close();
    } else {
        $erro = "O nome é obrigatório!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Síndico | Blindado Soluções</title>
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
                        <a href="edificios.php?tab=sindicos" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Síndico</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações do síndico <?= htmlspecialchars($sindico['nome']) ?>.</p>
                        </div>
                    </div>
                </div>

                <?php if (isset($erro)): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($erro); ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <div class="space-y-2">
                                <label class="form-label">Nome do Síndico *</label>
                                <input type="text" name="nome" class="form-input" required value="<?php echo htmlspecialchars($sindico['nome']); ?>" placeholder="Ex: João Silva">
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Telefone de Contato</label>
                                    <input type="tel" name="telefone" class="form-input" value="<?php echo htmlspecialchars($sindico['telefone']); ?>" placeholder="(00) 00000-0000">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">E-mail de Contato</label>
                                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($sindico['email']); ?>" placeholder="sindico@exemplo.com">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Edifício Vinculado</label>
                                <div class="relative">
                                    <select name="edificio_id" class="form-input appearance-none pr-10">
                                        <option value="">Selecione um edifício (opcional)</option>
                                        <?php foreach ($edificios as $edificio): ?>
                                            <option value="<?php echo $edificio['id']; ?>" 
                                                    <?php echo $sindico['edificio_id'] == $edificio['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($edificio['nome'] . ' - ' . $edificio['nome_base']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                                <a href="edificios.php?tab=sindicos" class="btn-secondary text-center">
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
