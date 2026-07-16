<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

if (!in_array($usuario_categoria, ['operador', 'supervisor', 'gerente'])) {
    header("Location: index.php");
    exit();
}

$mensagem = '';
$mensagem_tipo = 'info';

// Buscar edifícios e bases para seleção
$edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id WHERE b.status = 'ativo' ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
$bases = $conn->query("SELECT id, nome FROM bases WHERE status = 'ativo' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_plantao'])) {
    $supervisor = trim($_POST['supervisor_nome']);
    $operadores = trim($_POST['operadores_nomes']);
    $periodo = $_POST['periodo_dia'];
    $data_plantao = $_POST['data_ocorrencia'];
    $local_id_raw = $_POST['local_id'];
    $descricao_rica = $_POST['descricao_rica'];

    $parts = explode('_', $local_id_raw);
    $tipo = $parts[0];
    $id_ref = intval($parts[1]);
    $edificio_id = ($tipo === 'e') ? $id_ref : null;
    $base_id = ($tipo === 'b') ? $id_ref : null;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO ocorrencias (usuario_id, supervisor_nome, operadores_nomes, edificio_id, base_id, descricao, periodo_dia, data_ocorrencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiisss", $usuario_id, $supervisor, $operadores, $edificio_id, $base_id, $descricao_rica, $periodo, $data_plantao);
        $stmt->execute();
        $conn->commit();
        $mensagem = "Relatório salvo com sucesso!";
        $mensagem_tipo = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = "Erro ao salvar: " . $e->getMessage();
        $mensagem_tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-white">
<head>
    <link rel="icon" type="image/png" href="img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Relatório de Plantão | Blindado Soluções</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.2.1/dist/compressor.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; color: #37352f; margin: 0; padding: 0; }
        .page-wrapper { width: 100%; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .page-content { width: 100%; max-width: 900px; background: #ffffff; margin: 40px 0 100px 0; padding: 40px 40px 80px 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f1f1; }
        .notion-header-static { font-size: 2.2rem; font-weight: 800; margin-bottom: 1.5rem; color: #1a1a1a; padding: 0; line-height: 1.2; pointer-events: none; user-select: none; }
        .notion-meta { display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #f1f1f1; }
        .meta-row { display: flex; align-items: center; font-size: 0.9rem; }
        .meta-label { color: #999; width: 140px; display: flex; align-items: center; gap: 0.6rem; }
        .meta-value { flex: 1; }
        .meta-value input, .meta-value select { border: none; background: transparent; font-weight: 600; color: #333; outline: none; padding: 4px 8px; border-radius: 4px; width: 100%; max-width: 400px; }
        .meta-value input:hover, .meta-value select:hover { background: #f5f5f5; }
        .tox-tinymce { border: 1px solid #e2e8f0 !important; border-radius: 8px !important; width: 100% !important; min-height: 600px !important; box-shadow: none !important; }
        .top-bar { position: sticky; top: 0; z-index: 1000; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border-bottom: 1px solid #f1f1f1; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; width: 100%; }
        
        /* Ajuste para o calendário moderno dentro da meta-row */
        .modern-calendar-container { width: 100%; max-width: 400px; }
        .modern-calendar-container .form-input { border: none !important; background: transparent !important; font-weight: 600 !important; color: #333 !important; padding: 4px 8px !important; box-shadow: none !important; }
        .modern-calendar-container .form-input:hover { background: #f5f5f5 !important; }
        .modern-calendar-container .form-label { display: none; }
        /* Remove o ícone extra que estava bagunçando o layout na meta-row */
        .meta-value .modern-calendar-container .absolute.inset-y-0.left-0 { display: none; }
        .meta-value .modern-calendar-container .form-input { padding-left: 8px !important; }

        /* Estilos para botões de mídia */
        .media-wrapper {
            position: relative;
            display: inline-block;
            margin: 10px 0;
        }
        .media-wrapper:hover .media-actions {
            opacity: 1;
        }
        .media-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 10;
        }
        .media-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .media-action-btn.download {
            background: rgba(59, 130, 246, 0.9);
            color: white;
        }
        .media-action-btn.download:hover {
            background: rgba(59, 130, 246, 1);
            transform: scale(1.1);
        }
        .media-action-btn.delete {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }
        .media-action-btn.delete:hover {
            background: rgba(239, 68, 68, 1);
            transform: scale(1.1);
        }
        .media-action-btn i {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex-1 flex flex-col page-wrapper">
            <header class="top-bar">
                <div class="flex items-center gap-2 text-gray-400 text-sm">
                    <i class="fas fa-file-alt"></i>
                    <span class="font-medium text-gray-900">Novo Relatório</span>
                </div>
                <div class="flex gap-3">
                    <a href="consultar_ocorrencia.php" class="px-4 py-2 text-gray-500 hover:text-gray-900 font-bold text-sm">Descartar</a>
                    <button onclick="salvarRelatorio()" class="px-5 py-2 bg-black text-white rounded-lg hover:bg-gray-800 font-bold text-sm shadow-sm">Finalizar Relatório</button>
                </div>
            </header>
            <main class="page-content">
                <?php if ($mensagem): ?>
                    <div class="mb-8 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-bold"><?= $mensagem ?></span>
                    </div>
                <?php endif; ?>
                <form id="form-plantao" method="POST">
                    <input type="hidden" name="salvar_plantao" value="1">
                    <div id="dynamic-title" class="notion-header-static">Carregando título...</div>
                    <div class="notion-meta">
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user-tie"></i> Supervisor</span><div class="meta-value"><input type="text" name="supervisor_nome" placeholder="Nome do Supervisor" required></div></div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-users"></i> Equipe</span><div class="meta-value"><input type="text" name="operadores_nomes" placeholder="Operadores em turno" required></div></div>
                        <div class="meta-row">
                            <span class="meta-label"><i class="fas fa-calendar-alt"></i> Data</span>
                            <div class="meta-value">
                                <?php renderModernCalendar('data_ocorrencia', date('Y-m-d'), ''); ?>
                                <script>
                                    document.getElementById('value_calendar_data_ocorrencia').setAttribute('onchange', 'updateTitle()');
                                </script>
                            </div>
                        </div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-clock"></i> Turno</span><div class="meta-value"><select name="periodo_dia" id="periodo_dia" required onchange="updateTitle()"><option value="dia" selected>Diurno</option><option value="noite">Noturno</option></select></div></div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-map-marker-alt"></i> Local</span><div class="meta-value"><select name="local_id" required><option value="">Selecione o Local</option><optgroup label="Bases"><?php foreach ($bases as $b): ?><option value="b_<?= $b['id'] ?>">Base: <?= htmlspecialchars($b['nome']) ?></option><?php endforeach; ?></optgroup><optgroup label="Edifícios"><?php foreach ($edificios as $ed): ?><option value="e_<?= $ed['id'] ?>"><?= htmlspecialchars($ed['nome']) ?></option><?php endforeach; ?></optgroup></select></div></div>
                    </div>
                    <div class="w-full">
                        <textarea id="descricao_rica" name="descricao_rica" placeholder="Comece a escrever seu relatório aqui... Arraste fotos ou vídeos para dentro."></textarea>
                    </div>
                </form>
            </main>
        </div>
    </div>
    <script>
        // Função para comprimir imagem
        async function compressImage(file) {
            const options = {
                maxSizeMB: 1,
                maxWidthOrHeight: 1920,
                useWebWorker: true,
                initialQuality: 0.7
            };

            try {
                const compressedFile = await imageCompression(file, options);
                console.log('Imagem comprimida:', file.size, '->', compressedFile.size, '(' + Math.round((1 - compressedFile.size / file.size) * 100) + '% redução)');
                return compressedFile;
            } catch (error) {
                console.error('Erro ao comprimir imagem:', error);
                return file; // Retorna original se falhar
            }
        }

        // Função para comprimir áudio
        async function compressAudio(file) {
            return new Promise((resolve, reject) => {
                new Compressor(file, {
                    quality: 0.6,
                    mimeType: 'audio/mp3',
                    success(result) {
                        console.log('Áudio comprimido:', file.size, '->', result.size, '(' + Math.round((1 - result.size / file.size) * 100) + '% redução)');
                        resolve(result);
                    },
                    error(err) {
                        console.error('Erro ao comprimir áudio:', err);
                        resolve(file); // Retorna original se falhar
                    }
                });
            });
        }

        // Função para comprimir vídeo
        async function compressVideo(file) {
            return new Promise((resolve, reject) => {
                new Compressor(file, {
                    quality: 0.6,
                    mimeType: 'video/mp4',
                    success(result) {
                        console.log('Vídeo comprimido:', file.size, '->', result.size, '(' + Math.round((1 - result.size / file.size) * 100) + '% redução)');
                        resolve(result);
                    },
                    error(err) {
                        console.error('Erro ao comprimir vídeo:', err);
                        resolve(file); // Retorna original se falhar
                    }
                });
            });
        }

        // Função principal para comprimir arquivo baseado no tipo
        async function compressFile(file) {
            const extension = file.name.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            const audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
            const videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];

            if (imageExtensions.includes(extension)) {
                return await compressImage(file);
            } else if (audioExtensions.includes(extension)) {
                return await compressAudio(file);
            } else if (videoExtensions.includes(extension)) {
                return await compressVideo(file);
            }

            return file; // Retorna original se não for suportado
        }

        // Função para criar wrapper com botões de ação
        function createMediaWrapper(content, url, type) {
            const wrapperId = 'media-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const downloadIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>`;
            const trashIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`;
            return `
                <div class="media-wrapper" id="${wrapperId}" data-url="${url}" data-type="${type}">
                    ${content}
                    <div class="media-actions">
                        <button class="media-action-btn download" onclick="window.parent.downloadMedia('${url}', '${type}')" title="Download">
                            ${downloadIcon}
                        </button>
                        <button class="media-action-btn delete" onclick="window.parent.deleteMedia('${wrapperId}')" title="Excluir">
                            ${trashIcon}
                        </button>
                    </div>
                </div>
                <p>&nbsp;</p>
            `;
        }

        // Função para download de mídia
        window.downloadMedia = function(url, type) {
            const link = document.createElement('a');
            link.href = url;
            link.download = 'media_' + Date.now() + '.' + (type === 'video' ? 'mp4' : (type === 'audio' ? 'mp3' : 'jpg'));
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Função para excluir mídia
        window.deleteMedia = function(wrapperId) {
            if (confirm('Deseja realmente excluir esta mídia?')) {
                const editor = tinymce.get('descricao_rica');
                if (editor) {
                    const wrapper = editor.dom.get(wrapperId);
                    if (wrapper) {
                        editor.dom.remove(wrapper);
                    }
                }
            }
        }

        function updateTitle() {
            const dataInput = document.getElementById('value_calendar_data_ocorrencia').value;
            const turnoSelect = document.getElementById('periodo_dia');
            const turnoText = turnoSelect.options[turnoSelect.selectedIndex].text;
            const titleElement = document.getElementById('dynamic-title');
            if (dataInput) {
                const [year, month, day] = dataInput.split('-');
                const dateObj = new Date(year, month - 1, day);
                const diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                const diaSemana = diasSemana[dateObj.getDay()];
                const dataFormatada = `${day}/${month}/${year}`;
                titleElement.innerText = `${diaSemana} - ${dataFormatada} - ${turnoText}`;
            }
        }
        window.onload = updateTitle;

        tinymce.init({
            selector: '#descricao_rica',
            height: 600,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount autoresize emoticons',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | uploadbtn | hr blockquote | emoticons | removeformat help',
            menubar: false,
            language: 'pt_BR',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@23.10.9/langs6/pt_BR.js',
            images_upload_url: 'api_upload_imagem.php',
            automatic_uploads: true,
            file_picker_types: 'image media',

            setup: function (editor) {
                editor.ui.registry.addButton('uploadbtn', {
                    icon: 'image',
                    tooltip: 'Inserir Imagem, Vídeo ou Áudio',
                    onAction: async function () {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*,video/*,audio/*');
                        input.onchange = async function () {
                            const file = this.files[0];
                            if (!file) return;

                            console.log('Comprimindo arquivo:', file.name, 'Tamanho original:', (file.size / 1024 / 1024).toFixed(2) + ' MB');

                            const compressedFile = await compressFile(file);
                            const formData = new FormData();
                            formData.append('file', compressedFile);

                            fetch('api_upload_imagem.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.location) {
                                    const extension = file.name.split('.').pop().toLowerCase();
                                    const videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];
                                    const audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
                                    if (videoExtensions.includes(extension)) {
                                        const videoContent = `<video controls width="100%"><source src="${result.location}" type="video/${extension}"></video>`;
                                        editor.insertContent(createMediaWrapper(videoContent, result.location, 'video'));
                                    } else if (audioExtensions.includes(extension)) {
                                        const audioContent = `<audio controls style="width:100%; margin: 10px 0;"><source src="${result.location}" type="audio/${extension}"></audio>`;
                                        editor.insertContent(createMediaWrapper(audioContent, result.location, 'audio'));
                                    } else {
                                        const imageContent = `<img src="${result.location}" style="max-width:100%; height:auto;" />`;
                                        editor.insertContent(createMediaWrapper(imageContent, result.location, 'image'));
                                    }
                                } else {
                                    alert('Erro no upload: ' + (result.message || 'Erro desconhecido'));
                                }
                            })
                            .catch(err => alert('Erro ao enviar arquivo.'));
                        };
                        input.click();
                    }
                });

                editor.on('drop', async function (e) {
                    const files = e.dataTransfer.files;
                    if (files && files.length > 0) {
                        e.preventDefault();
                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];
                            console.log('Comprimindo arquivo (drop):', file.name, 'Tamanho original:', (file.size / 1024 / 1024).toFixed(2) + ' MB');

                            const compressedFile = await compressFile(file);
                            const formData = new FormData();
                            formData.append('file', compressedFile);
                            fetch('api_upload_imagem.php', { method: 'POST', body: formData })
                            .then(response => response.json())
                            .then(result => {
                                if (result.location) {
                                    const ext = file.name.split('.').pop().toLowerCase();
                                    const videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];
                                    const audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
                                    if (videoExtensions.includes(ext)) {
                                        const videoContent = `<video controls width="100%"><source src="${result.location}" type="video/${ext}"></video>`;
                                        editor.insertContent(createMediaWrapper(videoContent, result.location, 'video'));
                                    } else if (audioExtensions.includes(ext)) {
                                        const audioContent = `<audio controls style="width:100%; margin: 10px 0;"><source src="${result.location}" type="audio/${ext}"></audio>`;
                                        editor.insertContent(createMediaWrapper(audioContent, result.location, 'audio'));
                                    } else {
                                        const imageContent = `<img src="${result.location}" style="max-width:100%; height:auto;" />`;
                                        editor.insertContent(createMediaWrapper(imageContent, result.location, 'image'));
                                    }
                                }
                            });
                        }
                    }
                });
            },
            paste_data_images: true,
            autoresize_bottom_margin: 50,
            valid_elements: '*[*]',
            extended_valid_elements: 'div[*],span[*],button[*],i[*]',
            content_style: "body { font-family: 'Inter', sans-serif; font-size: 16px; line-height: 1.6; color: #37352f; padding: 10px; } video, img, audio { max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0; } .media-wrapper { position: relative; display: inline-block; margin: 10px 0; } .media-wrapper:hover .media-actions { opacity: 1 !important; } .media-actions { position: absolute; top: 10px; right: 10px; display: flex; gap: 8px; opacity: 0; transition: opacity 0.2s ease; z-index: 10; } .media-action-btn { width: 36px; height: 36px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2); } .media-action-btn.download { background: rgba(59, 130, 246, 0.9); color: white; } .media-action-btn.download:hover { background: rgba(59, 130, 246, 1); transform: scale(1.1); } .media-action-btn.delete { background: rgba(239, 68, 68, 0.9); color: white; } .media-action-btn.delete:hover { background: rgba(239, 68, 68, 1); transform: scale(1.1); } .media-action-btn i { font-size: 14px; }"
        });

        function salvarRelatorio() {
            tinymce.triggerSave();
            document.getElementById('form-plantao').submit();
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
