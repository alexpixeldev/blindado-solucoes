<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$mensagem = '';
$edificio = null;
$bases = [];
$edificio_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// If no ID, or invalid, redirect
if (!$edificio_id) {
    $_SESSION['mensagem'] = "Erro: ID do edifício inválido.";
    $_SESSION['mensagem_tipo'] = "error";
    header('Location: edificios.php?tab=edificios');
    exit();
}

// Logic to UPDATE data in database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $base_id = $_POST['base_id'];
    $endereco = $_POST['endereco'] ?? '';
    $sindico_nome = $_POST['sindico_nome'] ?? '';
    $sindico_contato = $_POST['sindico_contato'] ?? '';
    $administradora_id = !empty($_POST['administradora_id']) ? $_POST['administradora_id'] : null;
    $observacao_ficha_locacao = trim($_POST['observacao_ficha_locacao'] ?? '');
    $id = $_POST['id'];

    if (empty($nome) || empty($base_id)) {
        $mensagem = "O nome do edifício e a base são obrigatórios.";
    } else {
        // Pegar o nome antigo do síndico antes de atualizar
        $stmt_old = $conn->prepare("SELECT sindico_nome FROM edificios WHERE id = ?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $old_edificio = $stmt_old->get_result()->fetch_assoc();
        $old_sindico_nome = $old_edificio['sindico_nome'];
        $stmt_old->close();
        
        $stmt = $conn->prepare("UPDATE edificios SET nome = ?, base_id = ?, endereco = ?, sindico_nome = ?, sindico_contato = ?, administradora_id = ?, observacao_ficha_locacao = ? WHERE id = ?");
        $stmt->bind_param("sisssisi", $nome, $base_id, $endereco, $sindico_nome, $sindico_contato, $administradora_id, $observacao_ficha_locacao, $id);
        
        if ($stmt->execute()) {
            // Sincronizar o nome do síndico na tabela sindicos se mudou
            if (!empty($sindico_nome) && $sindico_nome !== $old_sindico_nome) {
                $stmt_sync = $conn->prepare("UPDATE sindicos SET nome = ? WHERE nome = ?");
                $stmt_sync->bind_param("ss", $sindico_nome, $old_sindico_nome);
                $stmt_sync->execute();
                $stmt_sync->close();
            }
            
            $_SESSION['mensagem'] = "Edifício atualizado com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            header('Location: edificios.php?tab=edificios');
            exit();
        } else {
            $mensagem = "Erro ao atualizar o edifício: " . $conn->error;
        }
        $stmt->close();
    }
}

// Logic to FETCH building data to fill the form
$stmt = $conn->prepare("SELECT * FROM edificios WHERE id = ?");
$stmt->bind_param("i", $edificio_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $edificio = $result->fetch_assoc();
} else {
    $_SESSION['mensagem'] = "Edifício não encontrado.";
    $_SESSION['mensagem_tipo'] = "error";
    header('Location: edificios.php?tab=edificios');
    exit();
}
$stmt->close();

// Fetch all bases for the dropdown
$bases = $conn->query("SELECT * FROM bases ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$administradoras = $conn->query("SELECT id, nome FROM administradoras ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Edifício | Blindado Soluções</title>
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
                        <a href="edificios.php?tab=edificios" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Edifício</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações do edifício <?= htmlspecialchars($edificio['nome']) ?>.</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="id" value="<?php echo $edificio['id']; ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="form-label">Selecione a Base *</label>
                                    <div class="relative">
                                        <select name="base_id" class="form-input appearance-none pr-10" required>
                                            <option value="">-- Selecione a Base --</option>
                                            <?php foreach ($bases as $base): ?>
                                                <option value="<?php echo $base['id']; ?>" <?php echo ($base['id'] == $edificio['base_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($base['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Nome do Edifício *</label>
                                    <input type="text" name="nome" class="form-input" value="<?php echo htmlspecialchars($edificio['nome']); ?>" required>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="form-label">Endereço Completo</label>
                                    <input type="text" name="endereco" class="form-input" value="<?php echo htmlspecialchars($edificio['endereco'] ?? ''); ?>">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Nome do Síndico</label>
                                    <input type="text" name="sindico_nome" class="form-input" value="<?php echo htmlspecialchars($edificio['sindico_nome'] ?? ''); ?>">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Contato do Síndico</label>
                                    <input type="text" name="sindico_contato" class="form-input" value="<?php echo htmlspecialchars($edificio['sindico_contato'] ?? ''); ?>">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="form-label">Administradora</label>
                                    <div class="relative">
                                        <select name="administradora_id" class="form-input appearance-none pr-10">
                                            <option value="">-- Selecione a Administradora --</option>
                                            <?php foreach ($administradoras as $adm): ?>
                                                <option value="<?php echo $adm['id']; ?>" <?php echo (isset($edificio['administradora_id']) && $adm['id'] == $edificio['administradora_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($adm['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="form-label">Observação ficha locação</label>
                                    <textarea name="observacao_ficha_locacao" class="form-input min-h-[120px]" placeholder="Insira aqui observações detalhadas para a ficha de locação..."><?php echo htmlspecialchars($edificio['observacao_ficha_locacao'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                                <a href="edificios.php?tab=edificios" class="btn-secondary text-center">
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
