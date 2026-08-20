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
    header("Location: edificios.php?tab=administradoras");
    exit();
}

// Buscar administradora
$stmt = $conn->prepare("SELECT * FROM administradoras WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$administradora = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$administradora) {
    $_SESSION['mensagem'] = "Administradora não encontrada!";
    $_SESSION['mensagem_tipo'] = "error";
    header("Location: edificios.php?tab=administradoras");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if ($nome) {
        $stmt = $conn->prepare("UPDATE administradoras SET nome = ?, telefone = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $telefone, $email, $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Administradora atualizada com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            header("Location: edificios.php?tab=administradoras");
            exit();
        } else {
            $erro = "Erro ao atualizar administradora: " . $conn->error;
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
    <title>Editar Administradora | Blindado Soluções</title>
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
                        <a href="edificios.php?tab=administradoras" class="icon-btn" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Administradora</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações da administradora <?= htmlspecialchars($administradora['nome']) ?>.</p>
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
                                <label class="form-label">Nome da Administradora *</label>
                                <input type="text" name="nome" class="form-input" required value="<?php echo htmlspecialchars($administradora['nome']); ?>" placeholder="Ex: Administradora Central">
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Telefone de Contato</label>
                                    <input type="tel" name="telefone" class="form-input" value="<?php echo htmlspecialchars($administradora['telefone']); ?>" placeholder="(00) 00000-0000">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">E-mail de Contato</label>
                                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($administradora['email']); ?>" placeholder="contato@administradora.com">
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="icon-btn-green" title="Salvar"><i class="fas fa-save" style="font-size:10px"></i></button>
                                <a href="edificios.php?tab=administradoras" class="icon-btn" title="Cancelar"><i class="fas fa-times" style="font-size:10px"></i></a>
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
