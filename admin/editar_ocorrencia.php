<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$usuario_id = $_SESSION['usuario_id'];
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

if (!$id) {
    header("Location: consultar_ocorrencia.php");
    exit();
}

// Buscar ocorrência e verificar permissão
$stmt = $conn->prepare("SELECT * FROM ocorrencias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ocorrencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ocorrencia || ($ocorrencia['usuario_id'] != $usuario_id && $usuario_categoria !== 'gerente')) {
    header("Location: consultar_ocorrencia.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

// Buscar edifícios para o select
$edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
$bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supervisor = trim($_POST['supervisor_nome']);
    $operadores = trim($_POST['operadores_nomes']);
    $periodo = $_POST['periodo_dia'];
    $data_plantao = $_POST['data_ocorrencia'];
    $local_val = $_POST['local_id'];
    $descricao = $_POST['descricao']; // HTML rico do TinyMCE

    if (!empty($supervisor) && !empty($operadores) && !empty($local_val) && !empty($descricao)) {
        $parts = explode('_', $local_val);
        $tipo = $parts[0];
        $id_ref = intval($parts[1]);
        $edificio_id = ($tipo === 'e') ? $id_ref : null;
        $base_id = ($tipo === 'b') ? $id_ref : null;

        $stmt = $conn->prepare("UPDATE ocorrencias SET supervisor_nome=?, operadores_nomes=?, edificio_id=?, base_id=?, descricao=?, periodo_dia=?, data_ocorrencia=?, atualizado_por=?, data_atualizacao=NOW() WHERE id=?");
        $stmt->bind_param("ssiisssii", $supervisor, $operadores, $edificio_id, $base_id, $descricao, $periodo, $data_plantao, $usuario_id, $id);
        
        if ($stmt->execute()) {
            $mensagem = "Ocorrência atualizada com sucesso!";
            $mensagem_tipo = "success";

            // Processar novas mídias se houver
            if (isset($_FILES['nova_midia']) && !empty($_FILES['nova_midia']['name'][0])) {
                $upload_dir = '../uploads/ocorrencias/';
                foreach ($_FILES['nova_midia']['name'] as $key => $name) {
                    if ($_FILES['nova_midia']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['nova_midia']['tmp_name'][$key];
                        $nome_arquivo = uniqid() . '_' . basename($name);
                        if (move_uploaded_file($tmp_name, $upload_dir . $nome_arquivo)) {
                            $tipo_arquivo = mime_content_type($upload_dir . $nome_arquivo);
                            $tipo_midia = (strpos($tipo_arquivo, 'video') !== false) ? 'video' : 'imagem';
                            $stmt_m = $conn->prepare("INSERT INTO ocorrencias_midia (ocorrencia_id, tipo_midia, caminho_arquivo) VALUES (?, ?, ?)");
                            $stmt_m->bind_param("iss", $id, $tipo_midia, $nome_arquivo);
                            $stmt_m->execute();
                        }
                    }
                }
            }
            
            // Recarregar dados
            $ocorrencia['supervisor_nome'] = $supervisor;
            $ocorrencia['operadores_nomes'] = $operadores;
            $ocorrencia['edificio_id'] = $edificio_id;
            $ocorrencia['base_id'] = $base_id;
            $ocorrencia['descricao'] = $descricao;
            $ocorrencia['periodo_dia'] = $periodo;
            $ocorrencia['data_ocorrencia'] = $data_plantao;
        } else {
            $mensagem = "Erro ao atualizar: " . $conn->error;
            $mensagem_tipo = "error";
        }
    }
}

$midias = $conn->query("SELECT * FROM ocorrencias_midia WHERE ocorrencia_id = $id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <link rel="icon" type="image/png" href="img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ocorrência | Blindado Soluções</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="max-w-[1000px] mx-auto">
                    <div class="mb-8 flex items-center justify-between animate-fade-in">
                        <div class="flex items-center gap-4">
                            <a href="consultar_ocorrencia.php" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 transition-all shadow-sm">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-3xl font-bold text-slate-900">Editar Registro</h1>
                        </div>
                        <button onclick="salvarEdicao()" class="btn-primary">
                            <i class="fas fa-save"></i>
                            <span>Salvar Alterações</span>
                        </button>
                    </div>

                    <?php if ($mensagem): ?>
                        <div class="mb-6 p-4 rounded-xl border-l-4 animate-fade-in <?= $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700' ?>">
                            <div class="flex items-center gap-3">
                                <i class="fas <?= $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                <span class="font-bold"><?= $mensagem ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="form-editar" method="POST" enctype="multipart/form-data" class="space-y-6 animate-slide-up">
                        <div class="admin-card grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="form-label">Supervisor</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-user-tie text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="text" name="supervisor_nome" class="form-input pl-11" value="<?= htmlspecialchars($ocorrencia['supervisor_nome']) ?>" required>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Equipe</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-users text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="text" name="operadores_nomes" class="form-input pl-11" value="<?= htmlspecialchars($ocorrencia['operadores_nomes']) ?>" required>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <?php renderModernCalendar('data_ocorrencia', $ocorrencia['data_ocorrencia'], 'Data do Plantão'); ?>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Turno</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-clock text-slate-400 text-sm"></i>
                                    </div>
                                    <select name="periodo_dia" class="form-input pl-11 appearance-none" required>
                                        <option value="dia" <?= $ocorrencia['periodo_dia'] == 'dia' ? 'selected' : '' ?>>Diurno</option>
                                        <option value="noite" <?= $ocorrencia['periodo_dia'] == 'noite' ? 'selected' : '' ?>>Noturno</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-1 md:col-span-2 space-y-2">
                                <label class="form-label">Local</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-map-marker-alt text-slate-400 text-sm"></i>
                                    </div>
                                    <select name="local_id" class="form-input pl-11 appearance-none" required>
                                        <optgroup label="Bases">
                                            <?php foreach ($bases as $b): ?>
                                                <option value="b_<?= $b['id'] ?>" <?= $ocorrencia['base_id'] == $b['id'] ? 'selected' : '' ?>>Base: <?= htmlspecialchars($b['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Edifícios">
                                            <?php foreach ($edificios as $ed): ?>
                                                <option value="e_<?= $ed['id'] ?>" <?= $ocorrencia['edificio_id'] == $ed['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ed['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card">
                            <label class="form-label mb-4">Relatório do Plantão</label>
                            <textarea id="descricao" name="descricao"><?= htmlspecialchars($ocorrencia['descricao']) ?></textarea>
                        </div>
                    </form>
                </div>
            </main>
            
            <footer class="border-t border-slate-200 bg-white p-6 text-center text-xs font-medium text-slate-400">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Tecnologia em Segurança.</p>
            </footer>
        </div>
    </div>

    <script>
        tinymce.init({
            selector: '#descricao',
            height: 600,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount autoresize emoticons',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image media | hr blockquote | emoticons | removeformat',
            menubar: false,
            language: 'pt_BR',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@23.10.9/langs6/pt_BR.js',
            images_upload_url: 'api_upload_imagem.php',
            automatic_uploads: true,
            content_style: "body { font-family: 'Inter', sans-serif; font-size: 16px; line-height: 1.6; color: #37352f; padding: 10px; } img, video { max-width: 100%; height: auto; border-radius: 8px; }"
        });

        function salvarEdicao() {
            tinymce.triggerSave();
            document.getElementById('form-editar').submit();
        }
    </script>
</body>
</html>
