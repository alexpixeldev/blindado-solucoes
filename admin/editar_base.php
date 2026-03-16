<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$mensagem = '';
$base = null;
$base_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// If no ID, or invalid, redirect
if (!$base_id) {
    $_SESSION['mensagem'] = "Erro: ID da base inválido.";
    $_SESSION['mensagem_tipo'] = "error";
    header('Location: edificios.php?tab=bases');
    exit();
}

// Logic to UPDATE data in database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $id = $_POST['id'];

    if (empty($nome)) {
        $mensagem = "O nome da base não pode ficar em branco.";
    } else {
        $stmt = $conn->prepare("UPDATE bases SET nome = ?, telefone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nome, $telefone, $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Base atualizada com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            header('Location: edificios.php?tab=bases');
            exit();
        } else {
            $mensagem = "Erro ao atualizar a base: " . $conn->error;
        }
        $stmt->close();
    }
}

// Logic to FETCH base data to fill the form
$stmt = $conn->prepare("SELECT * FROM bases WHERE id = ?");
$stmt->bind_param("i", $base_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $base = $result->fetch_assoc();
} else {
    $_SESSION['mensagem'] = "Base não encontrada.";
    $_SESSION['mensagem_tipo'] = "error";
    header('Location: edificios.php?tab=bases');
    exit();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Base | Blindado Soluções</title>
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
                        <a href="edificios.php?tab=bases" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Base</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações da base operacional <?= htmlspecialchars($base['nome']) ?>.</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= $mensagem ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="id" value="<?php echo $base['id']; ?>">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Nome da Base *</label>
                                    <input type="text" name="nome" class="form-input" value="<?php echo htmlspecialchars($base['nome']); ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Telefone de Contato</label>
                                    <input type="text" name="telefone" class="form-input" value="<?php echo htmlspecialchars($base['telefone']); ?>">
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                                <a href="edificios.php?tab=bases" class="btn-secondary text-center">
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
<?php $conn->close(); ?>
