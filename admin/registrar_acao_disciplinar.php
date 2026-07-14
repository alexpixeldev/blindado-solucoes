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
    header("Location: acoes_disciplinares.php");
    exit();
}

// Buscar dados do colaborador
$stmt = $conn->prepare("SELECT nome, nome_real FROM usuarios WHERE id = ? AND categoria = 'colaborador'");
$stmt->bind_param("i", $id);
$stmt->execute();
$colaborador = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$colaborador) {
    header("Location: acoes_disciplinares.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datas = $_POST['datas']; // String com datas separadas por vírgula
    $tipo = $_POST['tipo']; // advertencia ou suspensao
    $motivo = $_POST['motivo'];
    $descricao = $_POST['descricao'];
    
    if (!empty($datas) && !empty($tipo) && !empty($motivo) && !empty($descricao)) {
        
        $novo_nome = null;
        
        // Upload de arquivo (Opcional)
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/disciplina/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $novo_nome = uniqid('disciplina_') . '.' . $ext;
            $caminho = $upload_dir . $novo_nome;
            
            if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
                $mensagem = "Erro ao fazer upload do arquivo.";
                $mensagem_tipo = "error";
            }
        }
        
        if (empty($mensagem)) {
            $stmt = $conn->prepare("INSERT INTO acoes_disciplinares (usuario_id, datas, tipo, motivo, descricao, arquivo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $id, $datas, $tipo, $motivo, $descricao, $novo_nome);
            
            if ($stmt->execute()) {
                $mensagem = "Ação disciplinar registrada com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao salvar no banco: " . $conn->error;
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
    <title>Registrar Disciplina | Blindado Soluções</title>
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
                        <a href="visualizar_colaborador.php?id=<?= $id ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Registrar Disciplina</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($colaborador['nome_real']) ?></span></p>
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
                            <div class="space-y-2">
                                <?php renderModernCalendar('datas', date('Y-m-d'), 'Data do Ocorrido *'); ?>
                            </div>

                            <div class="space-y-4">
                                <label class="form-label">Tipo de Ação *</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label class="relative flex items-center p-4 rounded-xl border-2 border-slate-100 cursor-pointer hover:bg-slate-50 transition-all group has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                                        <input type="radio" name="tipo" value="advertencia" checked onchange="toggleArquivoLabel(this.value)" class="sr-only">
                                        <div class="h-10 w-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center mr-4 group-has-[:checked]:bg-white group-has-[:checked]:text-amber-600 transition-all">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-700 group-has-[:checked]:text-amber-700">Advertência</p>
                                            <p class="text-[10px] text-slate-400">Aviso formal por conduta</p>
                                        </div>
                                    </label>
                                    <label class="relative flex items-center p-4 rounded-xl border-2 border-slate-100 cursor-pointer hover:bg-slate-50 transition-all group has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                                        <input type="radio" name="tipo" value="suspensao" onchange="toggleArquivoLabel(this.value)" class="sr-only">
                                        <div class="h-10 w-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center mr-4 group-has-[:checked]:bg-white group-has-[:checked]:text-red-600 transition-all">
                                            <i class="fas fa-ban"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-700 group-has-[:checked]:text-red-700">Suspensão</p>
                                            <p class="text-[10px] text-slate-400">Afastamento temporário</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Motivo *</label>
                                <input type="text" name="motivo" class="form-input" placeholder="Ex: Atrasos constantes, Insubordinação..." required>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Descrição Detalhada *</label>
                                <textarea name="descricao" class="form-input" rows="4" placeholder="Descreva o ocorrido com detalhes..." required></textarea>
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <label class="form-label" id="label-arquivo">Arquivo da Advertência (PDF/Imagem)</label>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-all">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
                                            <p class="mb-2 text-sm text-slate-500"><span class="font-bold">Clique para selecionar</span> ou arraste e solte</p>
                                            <p class="text-xs text-slate-400">PDF, JPG ou PNG (Opcional)</p>
                                        </div>
                                        <input type="file" name="arquivo" id="arquivo-input" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                                <div id="file-preview" class="text-xs font-medium text-primary-600 text-center"></div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Registrar Disciplina</span>
                                </button>
                                <a href="visualizar_colaborador.php?id=<?= $id ?>" class="btn-secondary text-center">
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
    <script>
        function toggleArquivoLabel(tipo) {
            const label = document.getElementById('label-arquivo');
            if (label) {
                if (tipo === 'advertencia') {
                    label.textContent = 'Arquivo da Advertência (PDF/Imagem)';
                } else {
                    label.textContent = 'Arquivo da Suspensão (PDF/Imagem)';
                }
            }
        }

        const fileInput = document.getElementById('arquivo-input');
        const filePreview = document.getElementById('file-preview');

        if (fileInput) {
            fileInput.addEventListener('change', e => {
                if (e.target.files.length > 0) {
                    filePreview.innerHTML = `<i class="fas fa-file-alt mr-1"></i> Arquivo selecionado: ${e.target.files[0].name}`;
                }
            });
        }
    </script>
</body>
</html>
