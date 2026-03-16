<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

// Apenas administrador ou gerente pode acessar
if (!in_array($usuario_categoria, ['administrativo', 'gerente'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

// Processar upload de contracheque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_contracheque'])) {
    $usuario_id_cc = intval($_POST['colaborador_id']);
    $mes = intval($_POST['mes']);
    $ano = intval($_POST['ano']);
    
    if ($usuario_id_cc > 0 && $mes > 0 && $mes <= 12 && $ano > 0 && !empty($_FILES['arquivo_contracheque']['name'])) {
        $upload_dir = '../uploads/contracheques/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $nome_arquivo = uniqid() . '_' . basename($_FILES['arquivo_contracheque']['name']);
        $caminho = $upload_dir . $nome_arquivo;
        
        if (move_uploaded_file($_FILES['arquivo_contracheque']['tmp_name'], $caminho)) {
            // Verificar se já existe contracheque para este período
            $stmt = $conn->prepare("SELECT id FROM contracheques WHERE usuario_id = ? AND mes = ? AND ano = ?");
            $stmt->bind_param("iii", $usuario_id_cc, $mes, $ano);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Atualizar
                $stmt_update = $conn->prepare("UPDATE contracheques SET arquivo = ? WHERE usuario_id = ? AND mes = ? AND ano = ?");
                $stmt_update->bind_param("siii", $nome_arquivo, $usuario_id_cc, $mes, $ano);
                $stmt_update->execute();
                $stmt_update->close();
                $mensagem = "Contracheque atualizado com sucesso!";
            } else {
                // Inserir novo
                $stmt_insert = $conn->prepare("INSERT INTO contracheques (usuario_id, mes, ano, arquivo) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("iiis", $usuario_id_cc, $mes, $ano, $nome_arquivo);
                $stmt_insert->execute();
                $stmt_insert->close();
                $mensagem = "Contracheque enviado com sucesso!";
            }
            $mensagem_tipo = "success";
            $stmt->close();
        } else {
            $mensagem = "Erro ao fazer upload do arquivo!";
            $mensagem_tipo = "error";
        }
    }
}

// Processar exclusão de contracheque
if (isset($_POST['deletar_contracheque'])) {
    $contracheque_id = intval($_POST['contracheque_id']);
    $stmt = $conn->prepare("SELECT arquivo FROM contracheques WHERE id = ?");
    $stmt->bind_param("i", $contracheque_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        @unlink('../uploads/contracheques/' . $result['arquivo']);
        $stmt = $conn->prepare("DELETE FROM contracheques WHERE id = ?");
        $stmt->bind_param("i", $contracheque_id);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Contracheque deletado com sucesso!";
        $mensagem_tipo = "success";
    }
}

// Obter colaboradores
$colaboradores = $conn->query("SELECT id, nome_real, nome FROM usuarios WHERE categoria = 'colaborador' ORDER BY nome_real")->fetch_all(MYSQLI_ASSOC);

// Obter contracheques
$contracheques = $conn->query("
    SELECT c.*, u.nome_real as colaborador_nome 
    FROM contracheques c 
    JOIN usuarios u ON c.usuario_id = u.id 
    ORDER BY c.ano DESC, c.mes DESC
")->fetch_all(MYSQLI_ASSOC);

$meses_nomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Contracheques | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Contracheques</h1>
                    <p class="mt-1 text-slate-500">Envie e gerencie os holerites mensais dos colaboradores.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Upload Form -->
                    <div class="lg:col-span-1 animate-slide-up">
                        <div class="admin-card">
                            <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="fas fa-file-upload text-primary-600"></i>
                                Enviar Contracheque
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
                                        <label class="form-label">Mês de Referência</label>
                                        <div class="relative">
                                            <select name="mes" class="form-input appearance-none pr-10" required>
                                                <option value="">Selecione</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?> - <?= $meses_nomes[$i] ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                                <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="form-label">Ano</label>
                                        <input type="number" name="ano" class="form-input" min="2020" max="<?php echo date('Y') + 1; ?>" value="<?php echo date('Y'); ?>" required>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Arquivo (PDF/Imagem)</label>
                                    <div id="upload-area" class="relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:bg-primary-50 hover:border-primary-300 transition-all cursor-pointer group">
                                        <div class="h-12 w-12 rounded-full bg-white text-slate-400 flex items-center justify-center mb-2 group-hover:text-primary-600 group-hover:scale-110 transition-all shadow-sm">
                                            <i class="fas fa-file-invoice-dollar text-xl"></i>
                                        </div>
                                        <p class="text-xs font-bold text-slate-500 group-hover:text-primary-700">Clique ou arraste o arquivo</p>
                                        <input type="file" name="arquivo_contracheque" id="arquivo-input" accept=".pdf,.jpg,.jpeg,.png" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                        <div id="file-preview" class="mt-2 text-[10px] text-primary-600 font-bold truncate max-w-full"></div>
                                    </div>
                                </div>

                                <button type="submit" name="upload_contracheque" class="btn-primary w-full justify-center mt-4">
                                    <i class="fas fa-save"></i>
                                    <span>Enviar Contracheque</span>
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
                                    Contracheques Registrados
                                </h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Colaborador</th>
                                            <th>Referência</th>
                                            <th>Data Envio</th>
                                            <th class="text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($contracheques)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-12 text-slate-500 italic">Nenhum contracheque registrado.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($contracheques as $cc): ?>
                                                <tr class="group">
                                                    <td class="font-bold text-slate-900"><?php echo htmlspecialchars($cc['colaborador_nome']); ?></td>
                                                    <td>
                                                        <span class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">
                                                            <?php echo str_pad($cc['mes'], 2, '0', STR_PAD_LEFT) . '/' . $cc['ano']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-xs text-slate-400"><?php echo date('d/m/Y H:i', strtotime($cc['data_upload'])); ?></td>
                                                    <td class="text-right">
                                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <a href="../uploads/contracheques/<?php echo $cc['arquivo']; ?>" target="_blank" class="h-8 w-8 flex items-center justify-center rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-600 hover:text-white transition-all" title="Ver Arquivo">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="editar_contracheque.php?id=<?php echo $cc['id']; ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" onsubmit="return confirm('Deseja realmente excluir este contracheque?');" class="inline">
                                                                <input type="hidden" name="contracheque_id" value="<?php echo $cc['id']; ?>">
                                                                <button type="submit" name="deletar_contracheque" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
                                                                    <i class="fas fa-trash-alt"></i>
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
            
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Collaborator search logic
            const inputBusca = document.getElementById('busca_colaborador');
            const inputHidden = document.getElementById('colaborador_id');
            const datalist = document.getElementById('lista_colaboradores');

            inputBusca.addEventListener('input', function() {
                const val = this.value;
                const options = datalist.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value === val) {
                        inputHidden.value = options[i].getAttribute('data-id');
                        break;
                    }
                }
            });

            // File preview
            const fileInput = document.getElementById('arquivo-input');
            const filePreview = document.getElementById('file-preview');
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    filePreview.textContent = 'Selecionado: ' + this.files[0].name;
                }
            });
        });
    </script>
</body>
</html>
