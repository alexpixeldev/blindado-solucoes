<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: listar_colaboradores.php"); exit(); }

// Buscar funções para o select
$funcoes = $conn->query("SELECT id, nome FROM funcoes ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_real = $_POST['nome_real'];
    $usuario_login = $_POST['usuario'];
    $rg = $_POST['rg']; // RG
    $cpf = $_POST['cpf']; // CPF
    $data_admissao = $_POST['data_admissao']; // Data de Admissão
    $numero_cartao = $_POST['numero_cartao']; // Número do Cartão
    $senha = $_POST['senha'];
    $funcao_id = !empty($_POST['funcao_id']) ? intval($_POST['funcao_id']) : null;

    // Verifica duplicidade de login (exceto o próprio)
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE nome = ? AND id != ?");
    $stmt_check->bind_param("si", $usuario_login, $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $num_rows = $result_check->num_rows;
    $stmt_check->close();
    
    if ($num_rows > 0) {
        $erro = "Este usuário já está em uso.";
    } else {
        if (!empty($senha)) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, nome_real = ?, rg = ?, cpf = ?, data_admissao = ?, numero_cartao = ?, funcao_id = ?, senha = ? WHERE id = ? AND categoria = 'colaborador'");
            $stmt->bind_param("ssssssisi", $usuario_login, $nome_real, $rg, $cpf, $data_admissao, $numero_cartao, $funcao_id, $senhaHash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, nome_real = ?, rg = ?, cpf = ?, data_admissao = ?, numero_cartao = ?, funcao_id = ? WHERE id = ? AND categoria = 'colaborador'");
            $stmt->bind_param("ssssssii", $usuario_login, $nome_real, $rg, $cpf, $data_admissao, $numero_cartao, $funcao_id, $id);
        }
        
        if ($stmt->execute()) {
            header("Location: listar_colaboradores.php");
            exit();
        } else {
            $erro = "Erro ao atualizar: " . $stmt->error;
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? AND categoria = 'colaborador'");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) { header("Location: listar_colaboradores.php"); exit(); }
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Colaborador | Blindado Soluções</title>
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
                        <a href="listar_colaboradores.php" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Colaborador</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações de <?= htmlspecialchars($usuario['nome_real'] ?? $usuario['nome']) ?>.</p>
                        </div>
                    </div>
                </div>

                <?php if ($erro): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= htmlspecialchars($erro) ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-4xl mx-auto animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6" enctype="multipart/form-data">
                            <!-- Inputs hidden para armazenar nomes dos arquivos -->
                            <input type="hidden" name="foto_colaborador" id="foto_colaborador_input" value="<?= htmlspecialchars($usuario['foto_colaborador'] ?? '') ?>">
                            <input type="hidden" name="arquivo_cpf" id="arquivo_cpf_input" value="<?= htmlspecialchars($usuario['arquivo_cpf'] ?? '') ?>">
                            <input type="hidden" name="arquivo_rg" id="arquivo_rg_input" value="<?= htmlspecialchars($usuario['arquivo_rg'] ?? '') ?>">
                            
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Nome Completo</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="nome_real" class="form-input pl-11" required placeholder="Ex: João da Silva" value="<?= htmlspecialchars($usuario['nome_real'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="form-label">Usuário (Login)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-at text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="usuario" class="form-input pl-11" required placeholder="Ex: joaosilva" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Senha de Acesso</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="password" name="senha" class="form-input pl-11" placeholder="Deixe em branco para manter a senha atual">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">RG</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-id-card text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="rg" class="form-input pl-11" value="<?= htmlspecialchars($usuario['rg'] ?? '') ?>" placeholder="Ex: 12.345.678-90">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">CPF</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-address-card text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="cpf" class="form-input pl-11" value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>" placeholder="Ex: 123.456.789-00">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Data de Admissão</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-calendar text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="date" name="data_admissao" class="form-input pl-11" value="<?= htmlspecialchars($usuario['data_admissao'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Número do Cartão</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-credit-card text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="numero_cartao" class="form-input pl-11" value="<?= htmlspecialchars($usuario['numero_cartao'] ?? '') ?>" placeholder="Ex: 0012345678901">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Função</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-briefcase text-slate-400 text-sm"></i>
                                        </div>
                                        <select name="funcao_id" class="form-input pl-11 appearance-none pr-10">
                                            <option value="">-- Nenhuma --</option>
                                            <?php foreach ($funcoes as $f): ?>
                                                <option value="<?= $f['id'] ?>" <?= ($usuario['funcao_id'] ?? null) == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Upload de Documentos</label>
                                    <div class="space-y-4 border-2 border-dashed border-slate-300 rounded-lg p-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">Foto do Colaborador (Opcional)</label>
                                                <div class="relative">
                                                    <input type="file" name="foto_colaborador" accept="image/*" class="hidden" id="foto_colaborador_file" onchange="uploadFile(this, 'foto_colaborador')">
                                                    <div id="foto_colaborador_preview" class="mb-2">
                                                        <?php if (!empty($usuario['foto_colaborador'])): ?>
                                                            <div class="relative group">
                                                                <img src="../uploads/colaboradores/<?= htmlspecialchars($usuario['foto_colaborador']) ?>" alt="Foto atual" class="w-full h-32 object-cover rounded-lg border-2 border-slate-200">
                                                                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                                                    <button type="button" onclick="viewImage('../uploads/colaboradores/<?= htmlspecialchars($usuario['foto_colaborador']) ?>')" class="bg-white text-slate-700 p-2 rounded-full mx-1 hover:bg-slate-100">
                                                                        <i class="fas fa-eye text-sm"></i>
                                                                    </button>
                                                                    <button type="button" onclick="removerArquivo('foto_colaborador')" class="bg-white text-red-600 p-2 rounded-full mx-1 hover:bg-red-50">
                                                                        <i class="fas fa-trash text-sm"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex items-center justify-center w-full h-32 bg-slate-50 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors" id="foto_colaborador_upload_area" onclick="document.getElementById('foto_colaborador_file').click()">
                                                        <div id="foto_colaborador_upload_content" class="text-center">
                                                            <i class="fas fa-camera text-slate-400 text-xl mb-2"></i>
                                                            <p class="text-sm text-slate-600">Clique para selecionar foto</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">CPF (Arquivo)</label>
                                                <div class="relative">
                                                    <input type="file" name="arquivo_cpf" accept="image/*,application/pdf" class="hidden" id="arquivo_cpf_file" onchange="uploadFile(this, 'arquivo_cpf')">
                                                    <div id="arquivo_cpf_preview" class="mb-2">
                                                        <?php if (!empty($usuario['arquivo_cpf'])): ?>
                                                            <div class="relative group">
                                                                <?php if (strtolower(pathinfo($usuario['arquivo_cpf'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                                                    <div class="w-full h-32 bg-red-50 border-2 border-red-200 rounded-lg flex items-center justify-center">
                                                                        <i class="fas fa-file-pdf text-red-500 text-3xl"></i>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <img src="../uploads/colaboradores/<?= htmlspecialchars($usuario['arquivo_cpf']) ?>" alt="CPF atual" class="w-full h-32 object-cover rounded-lg border-2 border-slate-200">
                                                                <?php endif; ?>
                                                                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                                                    <button type="button" onclick="viewImage('../uploads/colaboradores/<?= htmlspecialchars($usuario['arquivo_cpf']) ?>')" class="bg-white text-slate-700 p-2 rounded-full mx-1 hover:bg-slate-100">
                                                                        <i class="fas fa-eye text-sm"></i>
                                                                    </button>
                                                                    <button type="button" onclick="removerArquivo('arquivo_cpf')" class="bg-white text-red-600 p-2 rounded-full mx-1 hover:bg-red-50">
                                                                        <i class="fas fa-trash text-sm"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex items-center justify-center w-full h-32 bg-slate-50 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors" id="arquivo_cpf_upload_area" onclick="document.getElementById('arquivo_cpf_file').click()">
                                                        <div id="arquivo_cpf_upload_content" class="text-center">
                                                            <i class="fas fa-file-upload text-slate-400 text-xl mb-2"></i>
                                                            <p class="text-sm text-slate-600">Clique para selecionar CPF</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">RG (Arquivo)</label>
                                                <div class="relative">
                                                    <input type="file" name="arquivo_rg" accept="image/*,application/pdf" class="hidden" id="arquivo_rg_file" onchange="uploadFile(this, 'arquivo_rg')">
                                                    <div id="arquivo_rg_preview" class="mb-2">
                                                        <?php if (!empty($usuario['arquivo_rg'])): ?>
                                                            <div class="relative group">
                                                                <?php if (strtolower(pathinfo($usuario['arquivo_rg'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                                                    <div class="w-full h-32 bg-red-50 border-2 border-red-200 rounded-lg flex items-center justify-center">
                                                                        <i class="fas fa-file-pdf text-red-500 text-3xl"></i>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <img src="../uploads/colaboradores/<?= htmlspecialchars($usuario['arquivo_rg']) ?>" alt="RG atual" class="w-full h-32 object-cover rounded-lg border-2 border-slate-200">
                                                                <?php endif; ?>
                                                                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                                                    <button type="button" onclick="viewImage('../uploads/colaboradores/<?= htmlspecialchars($usuario['arquivo_rg']) ?>')" class="bg-white text-slate-700 p-2 rounded-full mx-1 hover:bg-slate-100">
                                                                        <i class="fas fa-eye text-sm"></i>
                                                                    </button>
                                                                    <button type="button" onclick="removerArquivo('arquivo_rg')" class="bg-white text-red-600 p-2 rounded-full mx-1 hover:bg-red-50">
                                                                        <i class="fas fa-trash text-sm"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex items-center justify-center w-full h-32 bg-slate-50 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors" id="arquivo_rg_upload_area" onclick="document.getElementById('arquivo_rg_file').click()">
                                                        <div id="arquivo_rg_upload_content" class="text-center">
                                                            <i class="fas fa-file-upload text-slate-400 text-xl mb-2"></i>
                                                            <p class="text-sm text-slate-600">Clique para selecionar RG</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 pt-6 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-end">
                                <a href="listar_colaboradores.php" class="btn-secondary order-2 sm:order-1">
                                    <i class="fas fa-times"></i>
                                    <span>Cancelar</span>
                                </a>
                                <button type="submit" class="btn-primary order-1 sm:order-2">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
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
    
    <!-- Modal de Visualização de Imagens -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Visualizar Documento</h3>
                    <button onclick="closeImageModal()" class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="imagePreview" class="flex justify-center p-4">
                    <!-- A imagem será carregada aqui -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para upload de arquivos
        function uploadFile(fileInput, type) {
            const file = fileInput.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);
            
            fetch('api_upload_colaborador.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Verificar se a resposta é JSON válido
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta inválida do servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Atualiza o input hidden correspondente
                    if (type === 'foto_colaborador') {
                        document.getElementById('foto_colaborador_input').value = data.file.name;
                        showImagePreview('foto_colaborador', data.file.name);
                    } else if (type === 'arquivo_cpf') {
                        document.getElementById('arquivo_cpf_input').value = data.file.name;
                        showImagePreview('arquivo_cpf', data.file.name);
                    } else if (type === 'arquivo_rg') {
                        document.getElementById('arquivo_rg_input').value = data.file.name;
                        showImagePreview('arquivo_rg', data.file.name);
                    }
                    
                    // Mostra mensagem de sucesso
                    showNotification('success', 'Arquivo enviado com sucesso!');
                } else {
                    showNotification('error', data.message || 'Erro no upload');
                }
            })
            .catch(error => {
                console.error('Erro no upload:', error);
                showNotification('error', 'Erro no upload: ' + error.message);
            });
        }
        
        // Função para mostrar preview da imagem após upload
        function showImagePreview(type, fileName) {
            const uploadContent = document.getElementById(type + '_upload_content');
            const uploadArea = document.getElementById(type + '_upload_area');
            if (uploadContent && uploadArea) {
                const imagePath = `../uploads/colaboradores/${fileName}`;
                const fileExtension = fileName.split('.').pop().toLowerCase();
                
                let contentHtml = '';
                if (fileExtension === 'pdf') {
                    contentHtml = `
                        <div class="relative group w-full h-full">
                            <div class="w-full h-32 bg-red-50 border-2 border-green-400 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500 text-3xl"></i>
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                <button type="button" onclick="event.stopPropagation(); viewImage('${imagePath}')" class="bg-white text-slate-700 p-2 rounded-full mx-1 hover:bg-slate-100">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                <button type="button" onclick="event.stopPropagation(); removerArquivo('${type}')" class="bg-white text-red-600 p-2 rounded-full mx-1 hover:bg-red-50">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                            <div class="absolute top-2 right-2 bg-green-500 text-white p-1 rounded-full">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="relative group w-full h-full">
                            <img src="${imagePath}" alt="Preview" class="w-full h-32 object-cover rounded-lg border-2 border-green-400">
                            <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                <button type="button" onclick="event.stopPropagation(); viewImage('${imagePath}')" class="bg-white text-slate-700 p-2 rounded-full mx-1 hover:bg-slate-100">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                <button type="button" onclick="event.stopPropagation(); removerArquivo('${type}')" class="bg-white text-red-600 p-2 rounded-full mx-1 hover:bg-red-50">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                            <div class="absolute top-2 right-2 bg-green-500 text-white p-1 rounded-full">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                        </div>
                    `;
                }
                
                uploadContent.innerHTML = contentHtml;
                uploadArea.classList.remove('border-slate-200');
                uploadArea.classList.add('border-green-400', 'bg-green-50');
            }
        }
        
        // Função para visualizar imagem
        function viewImage(imagePath) {
            document.getElementById('imagePreview').innerHTML = `
                <img src="${imagePath}" alt="Documento" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-lg">
            `;
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
        }
        
        // Função para fechar modal
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.getElementById('imageModal').classList.remove('flex');
            
            // Limpa a imagem do preview
            document.getElementById('imagePreview').innerHTML = '';
        }
        
        // Função para remover arquivo
        function removerArquivo(type) {
            if (confirm('Tem certeza que deseja remover este arquivo?')) {
                fetch('api_upload_colaborador.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=remover&tipo=${type}&id=${type === 'foto_colaborador' ? document.getElementById('foto_colaborador_input').value : (type === 'arquivo_cpf' ? document.getElementById('arquivo_cpf_input').value : (type === 'arquivo_rg' ? document.getElementById('arquivo_rg_input').value : ''))}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Limpa o input correspondente
                        if (type === 'foto_colaborador') {
                            document.getElementById('foto_colaborador_input').value = '';
                        } else if (type === 'arquivo_cpf') {
                            document.getElementById('arquivo_cpf_input').value = '';
                        } else if (type === 'arquivo_rg') {
                            document.getElementById('arquivo_rg_input').value = '';
                        }
                        
                        // Limpa a área de upload e restaura o estado original
                        const uploadContent = document.getElementById(type + '_upload_content');
                        const uploadArea = document.getElementById(type + '_upload_area');
                        if (uploadContent && uploadArea) {
                            let originalContent = '';
                            if (type === 'foto_colaborador') {
                                originalContent = '<i class="fas fa-camera text-slate-400 text-xl mb-2"></i><p class="text-sm text-slate-600">Clique para selecionar foto</p>';
                            } else if (type === 'arquivo_cpf') {
                                originalContent = '<i class="fas fa-file-upload text-slate-400 text-xl mb-2"></i><p class="text-sm text-slate-600">Clique para selecionar CPF</p>';
                            } else if (type === 'arquivo_rg') {
                                originalContent = '<i class="fas fa-file-upload text-slate-400 text-xl mb-2"></i><p class="text-sm text-slate-600">Clique para selecionar RG</p>';
                            }
                            uploadContent.innerHTML = originalContent;
                            uploadContent.className = 'text-center';
                            uploadArea.classList.remove('border-green-400', 'bg-green-50');
                            uploadArea.classList.add('border-slate-200');
                        }
                        
                        showNotification('success', 'Arquivo removido com sucesso!');
                    } else {
                        showNotification('error', data.message);
                    }
                })
                .catch(error => {
                    showNotification('error', 'Erro ao remover arquivo: ' + error.message);
                });
            }
        }
        
        // Função de notificação
        function showNotification(type, message) {
            // Criar elemento de notificação
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remover após 3 segundos
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Fecha modal ao clicar fora
        document.getElementById('imageModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeImageModal();
            }
        });
        
        // Fecha modal com tecla ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
