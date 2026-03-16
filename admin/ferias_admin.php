<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

// Only administrator or manager can access
if (!in_array($usuario_categoria, ['administrativo', 'gerente'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

// Process vacation upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_ferias'])) {
    $usuario_id_ferias = intval($_POST['colaborador_id']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    
    if ($usuario_id_ferias > 0 && !empty($data_inicio) && !empty($data_fim) && !empty($_FILES['arquivo_ferias']['name'])) {
        $upload_dir = '../uploads/ferias/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $nome_arquivo = uniqid() . '_' . basename($_FILES['arquivo_ferias']['name']);
        $caminho = $upload_dir . $nome_arquivo;
        
        if (move_uploaded_file($_FILES['arquivo_ferias']['tmp_name'], $caminho)) {
            $stmt = $conn->prepare("INSERT INTO ferias (usuario_id, data_inicio, data_fim, arquivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $usuario_id_ferias, $data_inicio, $data_fim, $nome_arquivo);
            
            if ($stmt->execute()) {
                $mensagem = "Documento de férias registrado com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao salvar no banco: " . $stmt->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        } else {
            $mensagem = "Erro ao fazer upload do arquivo!";
            $mensagem_tipo = "error";
        }
    } else {
        $mensagem = "Preencha todos os campos e selecione um arquivo.";
        $mensagem_tipo = "error";
    }
}

// Process deletion
if (isset($_POST['deletar_ferias'])) {
    $ferias_id = intval($_POST['ferias_id']);
    $stmt = $conn->prepare("SELECT arquivo FROM ferias WHERE id = ?");
    $stmt->bind_param("i", $ferias_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        @unlink('../uploads/ferias/' . $result['arquivo']);
        $stmt = $conn->prepare("DELETE FROM ferias WHERE id = ?");
        $stmt->bind_param("i", $ferias_id);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Registro de férias excluído com sucesso!";
        $mensagem_tipo = "success";
    }
}

// Get collaborators
$colaboradores = $conn->query("SELECT id, nome_real, nome FROM usuarios WHERE categoria = 'colaborador' ORDER BY nome_real")->fetch_all(MYSQLI_ASSOC);

// Get vacation list
$lista_ferias = $conn->query("
    SELECT f.*, u.nome_real as colaborador_nome 
    FROM ferias f 
    JOIN usuarios u ON f.usuario_id = u.id 
    ORDER BY f.data_inicio DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Férias | Blindado Soluções</title>
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
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Férias</h1>
                    <p class="mt-1 text-slate-500">Registre e gerencie os períodos de descanso dos colaboradores.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Registration Form -->
                    <div class="lg:col-span-1 animate-slide-up">
                        <div class="admin-card">
                            <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-calendar-plus text-primary-600"></i>
                                Registrar Férias
                            </h2>
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <div class="space-y-2">
                                    <label class="form-label">Colaborador</label>
                                    <div class="relative">
                                        <input type="text" id="busca_colaborador" class="form-input pl-10" placeholder="Buscar colaborador..." list="lista_colaboradores" required autocomplete="off">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-slate-400 text-sm"></i>
                                        </div>
                                        <datalist id="lista_colaboradores">
                                            <?php foreach ($colaboradores as $col): ?>
                                                <option data-id="<?php echo $col['id']; ?>" value="<?php echo htmlspecialchars($col['nome_real']) . ' (' . htmlspecialchars($col['nome']) . ')'; ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                        <input type="hidden" name="colaborador_id" id="colaborador_id">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <?php renderModernCalendar('data_inicio', date('Y-m-d'), 'Data Início'); ?>
                                    </div>
                                    <div class="space-y-2">
                                        <?php renderModernCalendar('data_fim', date('Y-m-d', strtotime('+30 days')), 'Data Fim'); ?>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Documento (PDF/Imagem)</label>
                                    <div id="upload-area" class="relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:bg-primary-50 hover:border-primary-300 transition-all cursor-pointer group">
                                        <div class="h-12 w-12 rounded-full bg-white text-slate-400 flex items-center justify-center mb-2 group-hover:text-primary-600 group-hover:scale-110 transition-all shadow-sm">
                                            <i class="fas fa-cloud-upload-alt text-xl"></i>
                                        </div>
                                        <p class="text-xs font-bold text-slate-500 group-hover:text-primary-700">Clique ou arraste o arquivo</p>
                                        <input type="file" name="arquivo_ferias" id="arquivo-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        <div id="file-preview" class="mt-2 text-[10px] text-primary-600 font-bold truncate max-w-full"></div>
                                    </div>
                                </div>

                                <button type="submit" name="upload_ferias" class="btn-primary w-full justify-center mt-4">
                                    <i class="fas fa-save"></i>
                                    <span>Registrar Férias</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="lg:col-span-2 animate-slide-up" style="animation-delay: 0.1s">
                        <div class="admin-card p-0 overflow-hidden">
                            <div class="p-6 border-b border-slate-100">
                                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <i class="fas fa-history text-primary-600"></i>
                                    Histórico de Férias
                                </h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Colaborador</th>
                                            <th>Período</th>
                                            <th>Data Registro</th>
                                            <th class="text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($lista_ferias)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-12 text-slate-500 italic">Nenhum registro de férias encontrado.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($lista_ferias as $f): ?>
                                                <tr class="group">
                                                    <td class="font-bold text-slate-900"><?php echo htmlspecialchars($f['colaborador_nome']); ?></td>
                                                    <td>
                                                        <div class="flex items-center gap-2">
                                                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold"><?php echo date('d/m/Y', strtotime($f['data_inicio'])); ?></span>
                                                            <i class="fas fa-arrow-right text-[10px] text-slate-300"></i>
                                                            <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold"><?php echo date('d/m/Y', strtotime($f['data_fim'])); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-xs text-slate-400"><?php echo date('d/m/Y H:i', strtotime($f['criado_em'])); ?></td>
                                                    <td class="text-right">
                                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                                            <a href="../uploads/ferias/<?php echo htmlspecialchars($f['arquivo']); ?>" target="_blank" class="h-8 w-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all" title="Ver Documento">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                            <a href="editar_ferias.php?id=<?php echo $f['id']; ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-amber-600 hover:border-amber-200 transition-all" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este registro de férias?');">
                                                                <input type="hidden" name="ferias_id" value="<?php echo $f['id']; ?>">
                                                                <button type="submit" name="deletar_ferias" class="h-8 w-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 transition-all" title="Excluir">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <script>
        // Update hidden collaborator ID when selecting from datalist
        const inputBusca = document.getElementById('busca_colaborador');
        const inputId = document.getElementById('colaborador_id');
        const datalist = document.getElementById('lista_colaboradores');

        inputBusca.addEventListener('input', function() {
            const val = this.value;
            const options = datalist.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === val) {
                    inputId.value = options[i].getAttribute('data-id');
                    break;
                }
            }
        });

        // File upload preview
        const fileInput = document.getElementById('arquivo-input');
        const filePreview = document.getElementById('file-preview');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                filePreview.textContent = 'Arquivo: ' + this.files[0].name;
            } else {
                filePreview.textContent = '';
            }
        });
    </script>
</body>
</html>
