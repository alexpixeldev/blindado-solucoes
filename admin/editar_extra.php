<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: listar_colaboradores.php");
    exit();
}

$stmt = $conn->prepare("SELECT e.*, u.nome_real FROM extras e JOIN usuarios u ON e.usuario_id = u.id WHERE e.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$extra = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$extra) {
    header("Location: listar_colaboradores.php");
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
        $novo_nome = $extra['arquivo'];
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/extras/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $novo_nome_arquivo = uniqid('extra_') . '.' . $ext;
            $caminho = $upload_dir . $novo_nome_arquivo;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
                if ($extra['arquivo'] && file_exists($upload_dir . $extra['arquivo'])) {
                    @unlink($upload_dir . $extra['arquivo']);
                }
                $novo_nome = $novo_nome_arquivo;
            } else {
                $mensagem = "Erro ao fazer upload do arquivo.";
                $mensagem_tipo = "error";
            }
        }
        
        if (empty($mensagem)) {
            $usuario_atual = $_SESSION['usuario_id'];
            $stmt = $conn->prepare("UPDATE extras SET data_extra = ?, hora_inicio = ?, hora_fim = ?, local = ?, arquivo = ?, atualizado_por = ?, data_atualizacao = NOW() WHERE id = ?");
            $stmt->bind_param("sssssii", $data_extra, $hora_inicio, $hora_fim, $local, $novo_nome, $usuario_atual, $id);
            
            if ($stmt->execute()) {
                $mensagem = "Extra atualizado com sucesso!";
                $mensagem_tipo = "success";
                $extra['data_extra'] = $data_extra;
                $extra['hora_inicio'] = $hora_inicio;
                $extra['hora_fim'] = $hora_fim;
                $extra['local'] = $local;
                $extra['arquivo'] = $novo_nome;
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
    <title>Editar Extra | Blindado Soluções</title>
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
                        <a href="ver_extras.php?id=<?= $extra['usuario_id'] ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Hora Extra</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($extra['nome_real']) ?></span></p>
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
                                <?php renderModernCalendar('data_extra', $extra['data_extra'], 'Data do Extra *'); ?>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Hora Início *</label>
                                    <input type="time" name="hora_inicio" class="form-input" value="<?= htmlspecialchars($extra['hora_inicio']) ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Hora Fim *</label>
                                    <input type="time" name="hora_fim" class="form-input" value="<?= htmlspecialchars($extra['hora_fim']) ?>" required>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Local *</label>
                                <input type="text" name="local" class="form-input" value="<?= htmlspecialchars($extra['local']) ?>" required placeholder="Ex: Edifício Solar">
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="form-label">Documento Anexo</label>
                                    <?php if ($extra['arquivo']): ?>
                                        <a href="../uploads/extras/<?= htmlspecialchars($extra['arquivo']) ?>" target="_blank" class="text-xs font-bold text-primary-600 hover:text-primary-700 flex items-center gap-1">
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
                                <a href="ver_extras.php?id=<?= $extra['usuario_id'] ?>" class="btn-secondary text-center">
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
