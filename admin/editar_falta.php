<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

// Apenas usuários Administrativo e Gerente podem acessar
if ($_SESSION['usuario_categoria'] !== 'administrativo' && $_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: listar_colaboradores.php");
    exit();
}

// Buscar dados da falta
$stmt = $conn->prepare("SELECT f.*, u.nome_real FROM faltas f JOIN usuarios u ON f.usuario_id = u.id WHERE f.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$falta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$falta) {
    header("Location: listar_colaboradores.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datas = $_POST['datas'];
    $tipo = $_POST['tipo'];
    
    if (!empty($datas) && !empty($tipo)) {
        $novo_nome = $falta['arquivo']; // Mantém o arquivo atual por padrão
        
        // Upload de novo arquivo (Opcional)
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/faltas/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $novo_nome_arquivo = uniqid('falta_') . '.' . $ext;
            $caminho = $upload_dir . $novo_nome_arquivo;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
                // Remove arquivo antigo se existir e for diferente
                if ($falta['arquivo'] && file_exists($upload_dir . $falta['arquivo'])) {
                    @unlink($upload_dir . $falta['arquivo']);
                }
                $novo_nome = $novo_nome_arquivo;
            } else {
                $mensagem = "Erro ao fazer upload do arquivo.";
                $mensagem_tipo = "error";
            }
        }
        
        if (empty($mensagem)) {
            $stmt = $conn->prepare("UPDATE faltas SET datas = ?, tipo = ?, arquivo = ? WHERE id = ?");
            $stmt->bind_param("sssi", $datas, $tipo, $novo_nome, $id);
            
            if ($stmt->execute()) {
                $mensagem = "Falta atualizada com sucesso!";
                $mensagem_tipo = "success";
                // Atualiza dados na variável para exibir no formulário
                $falta['datas'] = $datas;
                $falta['tipo'] = $tipo;
                $falta['arquivo'] = $novo_nome;
            } else {
                $mensagem = "Erro ao atualizar no banco: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        $mensagem_tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Falta | Blindado Soluções</title>
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
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in max-w-3xl mx-auto">
                    <div class="flex items-center gap-4">
                        <a href="ver_faltas.php?id=<?= $falta['usuario_id'] ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Falta</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($falta['nome_real']) ?></span></p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mx-auto max-w-3xl mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="mx-auto max-w-3xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <?php renderModernCalendar('datas', $falta['datas'], 'Data da Falta *'); ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Tipo de Falta *</label>
                                    <div class="relative">
                                        <select name="tipo" class="form-input appearance-none pr-10" required>
                                            <option value="justificada" <?= $falta['tipo'] === 'justificada' ? 'selected' : '' ?>>Justificada</option>
                                            <option value="injustificada" <?= $falta['tipo'] === 'injustificada' ? 'selected' : '' ?>>Não justificada</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="form-label">Documento Anexo</label>
                                    <?php if ($falta['arquivo']): ?>
                                        <a href="../uploads/faltas/<?= htmlspecialchars($falta['arquivo']) ?>" target="_blank" class="text-xs font-bold text-primary-600 hover:text-primary-700 flex items-center gap-1">
                                            <i class="fas fa-external-link-alt"></i>
                                            Visualizar Atual
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-all">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="fas fa-file-upload text-3xl text-slate-400 mb-3"></i>
                                            <p class="mb-2 text-sm text-slate-500"><span class="font-bold">Clique para trocar</span> ou arraste e solte</p>
                                            <p class="text-xs text-slate-400">PDF, JPG ou PNG (Opcional)</p>
                                        </div>
                                        <input type="file" name="arquivo" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                                <a href="ver_faltas.php?id=<?= $falta['usuario_id'] ?>" class="btn-secondary text-center">
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
