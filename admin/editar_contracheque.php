<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Verifica permissão: Administrativo ou Gerente
if ($_SESSION['usuario_categoria'] !== 'administrativo' && $_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: contracheques_admin.php");
    exit();
}

// Buscar dados do contracheque
$stmt = $conn->prepare("SELECT c.*, u.nome_real FROM contracheques c JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$contracheque = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contracheque) {
    header("Location: contracheques_admin.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/contracheques/';
        
        // Nome do novo arquivo
        $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
        $novoNome = $contracheque['usuario_id'] . '_' . $contracheque['ano'] . '_' . $contracheque['mes'] . '_' . time() . '.' . $ext;
        $caminhoCompleto = $uploadDir . $novoNome;
        
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoCompleto)) {
            // Remove o arquivo antigo se existir
            $arquivoAntigo = $uploadDir . $contracheque['arquivo'];
            if (file_exists($arquivoAntigo)) {
                unlink($arquivoAntigo);
            }
            
            // Atualiza no banco
            $stmt = $conn->prepare("UPDATE contracheques SET arquivo = ? WHERE id = ?");
            $stmt->bind_param("si", $novoNome, $id);
            
            if ($stmt->execute()) {
                $mensagem = "Arquivo atualizado com sucesso!";
                $mensagem_tipo = "success";
                // Atualiza a variável para exibir o novo arquivo
                $contracheque['arquivo'] = $novoNome;
            } else {
                $mensagem = "Erro ao atualizar no banco: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        } else {
            $mensagem = "Erro ao fazer upload do novo arquivo.";
            $mensagem_tipo = "error";
        }
    } else {
        $mensagem = "Por favor, selecione um arquivo válido.";
        $mensagem_tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contracheque | Blindado Soluções</title>
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
                        <a href="contracheques_admin.php" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Contracheque</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($contracheque['nome_real']) ?></span></p>
                            <p class="text-xs text-slate-400 mt-1">Referência: <?= $meses[$contracheque['mes']] ?>/<?= $contracheque['ano'] ?></p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-3xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="space-y-2">
                                <label class="form-label">Arquivo Atual</label>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate"><?= htmlspecialchars($contracheque['arquivo']) ?></p>
                                    </div>
                                    <a href="../uploads/contracheques/<?= htmlspecialchars($contracheque['arquivo']) ?>" target="_blank" class="btn-secondary py-1.5 px-3 text-xs">
                                        <i class="fas fa-download mr-1"></i>
                                        Baixar
                                    </a>
                                </div>
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <label class="form-label">Substituir por Novo Arquivo *</label>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-all">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
                                            <p class="mb-2 text-sm text-slate-500"><span class="font-bold">Clique para selecionar</span> ou arraste e solte</p>
                                            <p class="text-xs text-slate-400">PDF, DOC, DOCX, XLS, XLSX, JPG ou PNG</p>
                                        </div>
                                        <input type="file" name="arquivo" class="hidden" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                                <p class="text-[10px] text-slate-400 italic text-center">O novo arquivo substituirá permanentemente o contracheque atual no sistema.</p>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Novo Arquivo</span>
                                </button>
                                <a href="contracheques_admin.php" class="btn-secondary text-center">
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
