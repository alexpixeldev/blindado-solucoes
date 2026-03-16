<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

if ($_SESSION['usuario_categoria'] !== 'administrativo' && $_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: listar_colaboradores.php");
    exit();
}

$stmt = $conn->prepare("SELECT a.*, u.nome_real FROM acoes_disciplinares a JOIN usuarios u ON a.usuario_id = u.id WHERE a.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$acao = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$acao) {
    header("Location: listar_colaboradores.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datas = $_POST['datas'];
    $tipo = $_POST['tipo'];
    $motivo = $_POST['motivo'];
    $descricao = $_POST['descricao'];
    
    if (!empty($datas) && !empty($tipo) && !empty($motivo) && !empty($descricao)) {
        $novo_nome = $acao['arquivo'];
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/disciplina/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $novo_nome_arquivo = uniqid('disciplina_') . '.' . $ext;
            $caminho = $upload_dir . $novo_nome_arquivo;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
                if ($acao['arquivo'] && file_exists($upload_dir . $acao['arquivo'])) {
                    @unlink($upload_dir . $acao['arquivo']);
                }
                $novo_nome = $novo_nome_arquivo;
            } else {
                $mensagem = "Erro ao fazer upload do arquivo.";
                $mensagem_tipo = "error";
            }
        }
        
        if (empty($mensagem)) {
            $stmt = $conn->prepare("UPDATE acoes_disciplinares SET datas = ?, tipo = ?, motivo = ?, descricao = ?, arquivo = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $datas, $tipo, $motivo, $descricao, $novo_nome, $id);
            
            if ($stmt->execute()) {
                $mensagem = "Ação disciplinar atualizada com sucesso!";
                $mensagem_tipo = "success";
                $acao['datas'] = $datas;
                $acao['tipo'] = $tipo;
                $acao['motivo'] = $motivo;
                $acao['descricao'] = $descricao;
                $acao['arquivo'] = $novo_nome;
            } else {
                $mensagem = "Erro ao atualizar: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        }
    } else {
        $mensagem = "Preencha todos os campos obrigatórios.";
        $mensagem_tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Disciplina | Blindado Soluções</title>
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
                        <a href="ver_disciplina.php?id=<?= $acao['usuario_id'] ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Disciplina</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($acao['nome_real']) ?></span></p>
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
                                    <?php renderModernCalendar('datas', $acao['datas'], 'Data do Ocorrido *'); ?>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Tipo de Ação *</label>
                                    <div class="relative">
                                        <select name="tipo" class="form-input appearance-none pr-10" required>
                                            <option value="advertencia" <?= $acao['tipo'] === 'advertencia' ? 'selected' : '' ?>>Advertência</option>
                                            <option value="suspensao" <?= $acao['tipo'] === 'suspensao' ? 'selected' : '' ?>>Suspensão</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Motivo *</label>
                                <input type="text" name="motivo" class="form-input" value="<?= htmlspecialchars($acao['motivo'] ?? '') ?>" required placeholder="Ex: Atraso injustificado">
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Descrição Detalhada *</label>
                                <textarea name="descricao" class="form-input min-h-[120px]" required placeholder="Descreva os detalhes do ocorrido..."><?= htmlspecialchars($acao['descricao'] ?? '') ?></textarea>
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="form-label">Documento Anexo</label>
                                    <?php if ($acao['arquivo']): ?>
                                        <a href="../uploads/disciplina/<?= htmlspecialchars($acao['arquivo']) ?>" target="_blank" class="text-xs font-bold text-primary-600 hover:text-primary-700 flex items-center gap-1">
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
                                <a href="ver_disciplina.php?id=<?= $acao['usuario_id'] ?>" class="btn-secondary text-center">
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
