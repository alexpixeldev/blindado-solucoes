<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Verifica permissão (Administrativo ou Gerente)
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = '';

// Buscar funções para o select
$funcoes = $conn->query("SELECT id, nome FROM funcoes ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_real = $_POST['nome_real']; // Nome Completo
    $usuario_login = $_POST['usuario']; // Login
    $rg = $_POST['rg']; // RG
    $cpf = $_POST['cpf']; // CPF
    $data_admissao = $_POST['data_admissao']; // Data de Admissão
    $numero_cartao = $_POST['numero_cartao']; // Número do Cartão
    $senha = $_POST['senha'];
    $funcao_id = !empty($_POST['funcao_id']) ? intval($_POST['funcao_id']) : null;

    // Verifica se o login já existe
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE nome = ?");
    $stmt_check->bind_param("s", $usuario_login);
    $stmt_check->execute();
    $stmt_check->store_result();
    $num_rows = $stmt_check->num_rows;
    $stmt_check->close(); // Fecha a verificação antes de prosseguir

    if ($num_rows > 0) {
        $mensagem = "Erro: Este usuário (login) já está em uso.";
        $mensagem_tipo = "error";
    } else {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $nova_categoria = 'colaborador';
        
        // Insere na tabela, ignorando campos de arquivo que não são processados aqui
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, nome_real, categoria, senha, rg, cpf, data_admissao, numero_cartao, funcao_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $usuario_login, $nome_real, $nova_categoria, $senhaHash, $rg, $cpf, $data_admissao, $numero_cartao, $funcao_id);

        if ($stmt->execute()) {
            header("Location: listar_colaboradores.php");
            exit();
        } else {
            $mensagem = "Erro ao criar: " . $stmt->error;
            $mensagem_tipo = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Colaborador | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Criar Novo Colaborador</h1>
                    <p class="mt-1 text-slate-500">Preencha os dados abaixo para cadastrar um novo colaborador no sistema.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div class="text-sm text-red-700 font-medium"><?= htmlspecialchars($mensagem) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Form Card -->
                <div class="mx-auto max-w-4xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-8">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Nome Completo</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="nome_real" class="form-input pl-11" required placeholder="Ex: João da Silva">
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="form-label">Usuário (Login)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-at text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="usuario" class="form-input pl-11" required placeholder="Ex: joaosilva">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Senha de Acesso</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="password" name="senha" class="form-input pl-11" required placeholder="••••••••">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">RG</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-id-card text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="rg" class="form-input pl-11" required placeholder="Ex: 12.345.678-90">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Função (Opcional)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-briefcase text-slate-400 text-sm"></i>
                                        </div>
                                        <select name="funcao_id" class="form-input pl-11 appearance-none pr-10">
                                            <option value="">-- Nenhuma --</option>
                                            <?php foreach ($funcoes as $f): ?>
                                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">CPF</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-address-card text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="cpf" class="form-input pl-11" required placeholder="Ex: 123.456.789-00">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Data de Admissão</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-calendar text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="date" name="data_admissao" class="form-input pl-11" required>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label">Número do Cartão</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-credit-card text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="numero_cartao" class="form-input pl-11" required placeholder="Ex: 0012345678901">
                                    </div>
                                </div>

                                
                                <div class="space-y-2">
                                    <label class="form-label">Upload de Documentos</label>
                                    <div class="space-y-4 border-2 border-dashed border-slate-300 rounded-lg p-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">Foto do Colaborador (Opcional)</label>
                                                <div class="flex items-center justify-center w-full h-32 bg-slate-50 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors" id="foto_colaborador_upload_area" onclick="document.getElementById('foto_colaborador_input').click()">
                                                    <div id="foto_colaborador_upload_content" class="text-center">
                                                        <i class="fas fa-camera text-slate-400 text-xl mb-2"></i>
                                                        <p class="text-sm text-slate-600">Clique para selecionar foto</p>
                                                    </div>
                                                </div>
                                                <input type="file" name="foto_colaborador" accept="image/*" class="hidden" id="foto_colaborador_input">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">CPF (Arquivo)</label>
                                                <div class="relative">
                                                    <input type="file" name="arquivo_cpf" accept="image/*,application/pdf" class="hidden" id="arquivo_cpf_input">
                                                    <div class="flex items-center justify-center w-full h-32 bg-slate-50 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors" id="arquivo_cpf_upload_area" onclick="document.getElementById('arquivo_cpf_input').click()">
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
                                                    <input type="file" name="arquivo_rg" accept="image/*,application/pdf" class="hidden" id="arquivo_rg_input">
                                                    <div class="flex items-center justify-center w-full h-32 bg-slate-50 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors" id="arquivo_rg_upload_area" onclick="document.getElementById('arquivo_rg_input').click()">
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
                                    <i class="fas fa-check"></i>
                                    <span>Criar Colaborador</span>
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
    
    <script>
        // Função para mostrar preview da imagem após seleção
        function showImagePreview(input, type) {
            console.log('showImagePreview chamada com type:', type);
            const file = input.files[0];
            if (!file) {
                console.log('Nenhum arquivo encontrado');
                return;
            }
            
            console.log('Arquivo encontrado:', file.name);
            
            const uploadContent = document.getElementById(type + '_upload_content');
            const uploadArea = document.getElementById(type + '_upload_area');
            
            console.log('Elementos encontrados:', { uploadContent: !!uploadContent, uploadArea: !!uploadArea });
            
            if (uploadContent && uploadArea) {
                const fileExtension = file.name.split('.').pop().toLowerCase();
                const fileUrl = URL.createObjectURL(file);
                
                console.log('URL do arquivo:', fileUrl);
                console.log('Extensão:', fileExtension);
                
                let contentHtml = '';
                if (fileExtension === 'pdf') {
                    contentHtml = `
                        <div class="relative group w-full h-full">
                            <div class="w-full h-32 bg-red-50 border-2 border-green-400 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-pdf text-red-500 text-3xl"></i>
                            </div>
                            <div class="absolute top-2 right-2 bg-green-500 text-white p-1 rounded-full">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="relative group w-full h-full">
                            <img src="${fileUrl}" alt="Preview" class="w-full h-32 object-cover rounded-lg border-2 border-green-400" style="display: block !important;">
                            <div class="absolute top-2 right-2 bg-green-500 text-white p-1 rounded-full">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                        </div>
                    `;
                }
                
                console.log('HTML gerado:', contentHtml);
                
                uploadContent.innerHTML = contentHtml;
                uploadContent.className = 'relative w-full h-full';
                uploadArea.classList.remove('border-slate-200');
                uploadArea.classList.add('border-green-400', 'bg-green-50');
                
                console.log('Preview aplicado com sucesso!');
            } else {
                console.error('Elementos não encontrados para o tipo:', type);
            }
        }
        
        // Função para limpar preview
        function clearPreview(type) {
            const uploadContent = document.getElementById(type + '_upload_content');
            const uploadArea = document.getElementById(type + '_upload_area');
            const fileInput = document.getElementById(type + '_input');
            
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
            
            if (fileInput) {
                fileInput.value = '';
            }
        }
        
        // Adicionar event listeners para os inputs de arquivo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, configurando event listeners...');
            
            const fileInputs = [
                { id: 'foto_colaborador_input', type: 'foto_colaborador' },
                { id: 'arquivo_cpf_input', type: 'arquivo_cpf' },
                { id: 'arquivo_rg_input', type: 'arquivo_rg' }
            ];
            
            fileInputs.forEach(input => {
                const element = document.getElementById(input.id);
                console.log(`Elemento encontrado para ${input.id}:`, !!element);
                
                if (element) {
                    element.addEventListener('change', function() {
                        console.log(`Arquivo selecionado para ${input.type}`);
                        showImagePreview(this, input.type);
                    });
                } else {
                    console.error(`Elemento não encontrado: ${input.id}`);
                }
            });
        });
    </script>
</body>
</html>
