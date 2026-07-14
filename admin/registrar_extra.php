<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

// Apenas usuários Administrativo e Gerente podem acessar
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: extras.php");
    exit();
}

// Buscar dados do colaborador
$stmt = $conn->prepare("SELECT nome, nome_real FROM usuarios WHERE id = ? AND categoria = 'colaborador'");
$stmt->bind_param("i", $id);
$stmt->execute();
$colaborador = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$colaborador) {
    header("Location: extras.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_extra = $_POST['data_extra'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $local = $_POST['local'];
    
    if (!empty($data_extra) && !empty($hora_inicio) && !empty($hora_fim) && !empty($local)) {
        
        $novo_nome = null;
        
        // Upload de arquivo (Opcional)
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/extras/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $novo_nome = uniqid('extra_') . '.' . $ext;
            $caminho = $upload_dir . $novo_nome;
            
            if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
                $mensagem = "Erro ao fazer upload do arquivo.";
                $mensagem_tipo = "error";
            }
        }
        
        if (empty($mensagem)) {
            $registrado_por = $_SESSION['usuario_id'];
            // Inserir no banco
            $stmt = $conn->prepare("INSERT INTO extras (usuario_id, data_extra, hora_inicio, hora_fim, local, arquivo, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssi", $id, $data_extra, $hora_inicio, $hora_fim, $local, $novo_nome, $registrado_por);
            
            if ($stmt->execute()) {
                $mensagem = "Extra registrado com sucesso!";
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
    <title>Registrar Extra | Blindado Soluções</title>
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
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Registrar Extra</h1>
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
                                <?php renderModernCalendar('data_extra', date('Y-m-d'), 'Data do Extra *'); ?>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="form-label">Hora Início *</label>
                                    <input type="time" name="hora_inicio" class="form-input" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Hora Fim *</label>
                                    <input type="time" name="hora_fim" class="form-input" required>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Local *</label>
                                <input type="text" name="local" class="form-input" placeholder="Ex: Base Matriz, Cliente X..." required>
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <label class="form-label">Arquivo/Comprovante (Opcional)</label>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-all">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 mb-3"></i>
                                            <p class="mb-2 text-sm text-slate-500"><span class="font-bold">Clique para selecionar</span> ou arraste e solte</p>
                                            <p class="text-xs text-slate-400">PDF, JPG ou PNG</p>
                                        </div>
                                        <input type="file" name="arquivo" id="arquivo-input" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                    </label>
                                </div>
                                <div id="file-preview" class="text-xs font-medium text-primary-600 text-center"></div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Registrar Extra</span>
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
