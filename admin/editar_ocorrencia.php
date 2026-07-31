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
$edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e JOIN bases b ON e.base_id = b.id WHERE b.status = 'ativo' ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
$bases = $conn->query("SELECT id, nome FROM bases WHERE status = 'ativo' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

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
    <link rel="icon" type="image/png" href="../img/escudo.png">
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
    <style>
        .inline-media-toolbar { display: flex; align-items: center; gap: 6px; padding: 6px 0; flex-wrap: wrap; }
        .toolbar-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; color: #475569; font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
        .toolbar-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #f0f4ff; }
        .toolbar-btn.loading { opacity: 0.6; pointer-events: none; }
        .toolbar-btn i { font-size: 0.82rem; }

        .toast { position: fixed; bottom: 20px; right: 20px; z-index: 99999; padding: 12px 18px; border-radius: 8px; color: #fff; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 16px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 10px; opacity: 0; transform: translateY(10px); transition: opacity 0.25s, transform 0.25s; max-width: 340px; }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.success { background: #16a34a; }
        .toast.error { background: #dc2626; }
        .toast.loading { background: #2563eb; }
        .toast i { font-size: 1rem; }

        .upload-progress-wrap { margin: 2px 0 8px; height: 8px; border-radius: 5px; background: #e2e8f0; overflow: hidden; position: relative; display: none; }
        .upload-progress-wrap.active { display: block; }
        .upload-progress-fill { height: 100%; width: 0; background: #3b82f6; transition: width 0.2s; border-radius: 5px; }
        .upload-progress-wrap.done .upload-progress-fill { background: #16a34a; }

        .descricao-editor { width: 100%; min-height: 320px; padding: 18px 22px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; line-height: 1.7; color: #1a1a2e; background: #fff; outline: none; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; overflow-y: auto; position: relative; }
        .descricao-editor:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
        .descricao-editor:empty::before { content: attr(data-placeholder); color: #a0afbe; pointer-events: none; }
        .editor-media-remove { position: absolute; width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: #fff; font-size: 14px; line-height: 1; cursor: pointer; display: none; align-items: center; justify-content: center; z-index: 50; box-shadow: 0 2px 8px rgba(0,0,0,0.3); padding: 0; }
        .editor-media-remove.show { display: flex; }
        .editor-media-remove:hover { background: #dc2626; }
        .descricao-editor img.media-thumb { max-width: 140px; max-height: 110px; border-radius: 6px; margin: 6px 2px; cursor: zoom-in; border: 1.5px solid #e2e8f0; vertical-align: middle; transition: max-width 0.2s, max-height 0.2s, border-color 0.2s; }
        .descricao-editor img.media-thumb:hover { border-color: #3b82f6; }
        .descricao-editor img.media-thumb.expanded { max-width: 100%; max-height: none; cursor: zoom-out; border-color: #bfdbfe; }
        .descricao-editor video.media-thumb { max-width: 200px; max-height: 130px; border-radius: 6px; margin: 6px 2px; cursor: pointer; border: 1.5px solid #e2e8f0; background: #0f172a; vertical-align: middle; transition: max-width 0.2s, max-height 0.2s, border-color 0.2s; }
        .descricao-editor video.media-thumb:hover { border-color: #3b82f6; }
        .descricao-editor video.media-thumb.expanded { max-width: 100%; max-height: 100%; border-color: #bfdbfe; }
        .descricao-editor audio.media-audio { width: 100%; max-width: 340px; margin: 6px 2px; display: block; }
    </style>
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
                            <a href="consultar_ocorrencia.php" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:none;border-radius:3px;background:#94a3b8;color:#fff;font-size:10px;cursor:pointer;padding:0;line-height:1;flex-shrink:0" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                            <h1 class="text-3xl font-bold text-slate-900">Editar Registro</h1>
                        </div>
                        <button onclick="salvarEdicao()" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:none;border-radius:3px;background:#22c55e;color:#fff;font-size:10px;cursor:pointer;padding:0;line-height:1;flex-shrink:0" title="Salvar"><i class="fas fa-save" style="font-size:10px"></i></button>
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
                            <div class="space-y-2">
                                <div class="inline-media-toolbar">
                                    <button type="button" class="toolbar-btn btn-add-audio" title="Adicionar áudio"><i class="fas fa-microphone"></i> Áudio</button>
                                    <button type="button" class="toolbar-btn btn-add-image" title="Adicionar imagem"><i class="fas fa-image"></i> Imagem</button>
                                    <button type="button" class="toolbar-btn btn-add-video" title="Adicionar vídeo"><i class="fas fa-video"></i> Vídeo</button>
                                </div>
                                <div class="descricao-editor" id="descricao-editor" contenteditable="true" data-placeholder="Descreva detalhadamente o ocorrido..."><?= $ocorrencia['descricao'] ?></div>
                                <input type="hidden" name="descricao" id="descricao-hidden">
                            </div>
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
        function restoreSelection(editor) {
            editor.focus();
            const sel = window.getSelection();
            if (editor._lastRange) {
                sel.removeAllRanges();
                sel.addRange(editor._lastRange);
                return;
            }
            if (!sel.rangeCount) {
                const range = document.createRange();
                range.setStart(editor, editor.childNodes.length || 0);
                range.collapse(true);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }

        function insertMediaInline(editor, filepath, ext, mediaType) {
            let isVideo, isAudio, isImage;
            if (mediaType === 'audio' || mediaType === 'video' || mediaType === 'image') {
                isVideo = mediaType === 'video';
                isAudio = mediaType === 'audio';
                isImage = mediaType === 'image';
            } else {
                isVideo = ['mp4','webm','ogg','mov','avi','mkv','flv','wmv','m4v','3gp','3g2'].includes(ext);
                isAudio = ['mp3','wav','wave','ogg','oga','m4a','m4b','m4r','aac','flac','wma','aiff','opus','webm','amr','3gp','3g2','caf'].includes(ext);
                isImage = ['jpg','jpeg','png','gif','webp','bmp','svg','tiff'].includes(ext);
            }

            restoreSelection(editor);

            let el;
            if (isImage) {
                el = document.createElement('img');
                el.src = filepath;
                el.contentEditable = 'false';
                el.classList.add('media-thumb');
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.classList.toggle('expanded');
                });
            } else if (isVideo) {
                el = document.createElement('video');
                el.preload = 'metadata';
                el.contentEditable = 'false';
                el.classList.add('media-thumb');
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    const expanded = this.classList.toggle('expanded');
                    this.controls = expanded;
                    if (!expanded) this.pause();
                });
                const src = document.createElement('source');
                src.src = filepath;
                el.appendChild(src);
            } else if (isAudio) {
                el = document.createElement('audio');
                el.controls = true;
                el.preload = 'metadata';
                el.contentEditable = 'false';
                el.classList.add('media-audio');
                const src = document.createElement('source');
                src.src = filepath;
                el.appendChild(src);
            } else {
                el = document.createElement('a');
                el.href = filepath;
                el.textContent = filepath.split('/').pop();
                el.target = '_blank';
            }

            const sel = window.getSelection();
            const range = sel.getRangeAt(0);
            range.deleteContents();
            range.insertNode(el);
            range.setStartAfter(el);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);

            editor.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function initContentEditable(editor) {
            function saveRange() {
                const sel = window.getSelection();
                if (sel.rangeCount && editor.contains(sel.anchorNode)) {
                    editor._lastRange = sel.getRangeAt(0).cloneRange();
                }
            }
            editor.addEventListener('mouseup', saveRange);
            editor.addEventListener('keyup', saveRange);
            editor.addEventListener('blur', saveRange);

            editor.addEventListener('input', function() {
                this.dataset.placeholder = this.getAttribute('data-placeholder');
            });
            editor.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            });
            editor.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    document.execCommand('insertLineBreak');
                    e.preventDefault();
                }
            });
        }

        function normalizeEditorMedia(editor) {
            editor.querySelectorAll('img, video, audio').forEach(function(el) {
                el.contentEditable = 'false';
                if (el.tagName === 'IMG') {
                    el.classList.add('media-thumb');
                    el.removeAttribute('style');
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        this.classList.toggle('expanded');
                    });
                } else if (el.tagName === 'VIDEO') {
                    el.classList.add('media-thumb');
                    el.controls = false;
                    el.preload = 'metadata';
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        const expanded = this.classList.toggle('expanded');
                        this.controls = expanded;
                        if (!expanded) this.pause();
                    });
                } else if (el.tagName === 'AUDIO') {
                    el.classList.add('media-audio');
                    el.controls = true;
                    el.preload = 'metadata';
                }
            });
        }

        function sanitizeEditorContent(editor) {
            const clone = editor.cloneNode(true);
            clone.querySelectorAll('.editor-media-remove').forEach(function(btn) { btn.remove(); });
            clone.querySelectorAll('img.media-thumb, video.media-thumb, audio.media-audio').forEach(function(el) {
                el.classList.remove('media-thumb', 'expanded', 'media-audio');
                el.removeAttribute('style');
                el.removeAttribute('contenteditable');
                if (el.tagName === 'VIDEO' || el.tagName === 'AUDIO') el.controls = true;
                if (el.tagName === 'IMG') el.setAttribute('style', 'max-width:100%;height:auto;border-radius:6px');
            });
            return clone.innerHTML;
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast ' + (type || 'loading');
            const icon = document.createElement('i');
            icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-circle-notch fa-spin');
            toast.appendChild(icon);
            toast.appendChild(document.createTextNode(message));
            document.body.appendChild(toast);
            requestAnimationFrame(function() { toast.classList.add('show'); });
            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 300);
            }, type === 'error' ? 5000 : 3000);
        }

        function convertAndUpload(editor, file, tipo) {
            showToast('Convertendo áudio para MP3...', 'loading');
            BlindadoAudio.convertAudioToMp3(file, function(mp3File) {
                doUpload(editor, mp3File, tipo);
            }, function(err) {
                showToast((err && err.message) || 'Não foi possível converter o áudio.', 'error');
            });
        }

        function uploadMediaAndInsert(editor, file, tipo) {
            const ext = file.name.split('.').pop().toLowerCase();
            const isAudioFile = ['mp3','wav','wave','ogg','oga','m4a','m4b','m4r','aac','flac','wma','aiff','opus','webm','amr','3gp','3g2','caf'].includes(ext);
            if (isAudioFile) {
                convertAndUpload(editor, file, tipo);
            } else {
                doUpload(editor, file, tipo);
            }
        }

        function doUpload(editor, file, tipo) {
            const fd = new FormData();
            fd.append('file', file);

            const tipoLabel = tipo || 'arquivo';
            let barWrap = editor.nextElementSibling && editor.nextElementSibling.classList && editor.nextElementSibling.classList.contains('upload-progress-wrap')
                ? editor.nextElementSibling
                : null;
            if (!barWrap) {
                barWrap = document.createElement('div');
                barWrap.className = 'upload-progress-wrap';
                barWrap.innerHTML = '<div class="upload-progress-fill"></div>';
                editor.after(barWrap);
            }
            const fill = barWrap.querySelector('.upload-progress-fill');
            fill.style.width = '0%';
            barWrap.classList.remove('done');
            barWrap.classList.add('active');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api_upload_imagem.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    fill.style.width = Math.round((e.loaded / e.total) * 100) + '%';
                }
            };

            xhr.onload = function() {
                barWrap.classList.add('done');
                setTimeout(function() { barWrap.classList.remove('active'); }, 800);
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.location) {
                        const ext2 = file.name.split('.').pop().toLowerCase();
                        const mediaType = result.file && result.file.type ? result.file.type : null;
                        insertMediaInline(editor, result.location, ext2, mediaType);
                        showToast('Upload concluído: ' + file.name, 'success');
                    } else {
                        showToast((result.message || 'Erro no upload') + ' (' + file.name + ')', 'error');
                    }
                } catch(e) {
                    showToast('Resposta inválida do servidor: ' + xhr.responseText.substring(0, 100), 'error');
                }
            };

            xhr.onerror = function() {
                barWrap.classList.add('done');
                setTimeout(function() { barWrap.classList.remove('active'); }, 800);
                showToast('Falha de conexão ao enviar ' + file.name, 'error');
            };

            xhr.send(fd);
        }

        function initInlineToolbar(editor) {
            const toolbar = document.querySelector('.inline-media-toolbar');
            if (!toolbar || !editor) return;

            toolbar.querySelector('.btn-add-audio').addEventListener('click', function() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'audio/*';
                input.onchange = function() { if (this.files[0]) uploadMediaAndInsert(editor, this.files[0], 'áudio'); };
                input.click();
            });
            toolbar.querySelector('.btn-add-image').addEventListener('click', function() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = function() { if (this.files[0]) uploadMediaAndInsert(editor, this.files[0], 'imagem'); };
                input.click();
            });
            toolbar.querySelector('.btn-add-video').addEventListener('click', function() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'video/*';
                input.onchange = function() { if (this.files[0]) uploadMediaAndInsert(editor, this.files[0], 'vídeo'); };
                input.click();
            });
        }

        function initMediaRemoval(editor) {
            let removeBtn = null;

            function getMediaUrl(el) {
                if (el.tagName === 'A') return el.getAttribute('href') || '';
                if (el.src) return el.src;
                const s = el.querySelector('source');
                return s ? (s.getAttribute('src') || '') : '';
            }

            function deleteMediaFile(url) {
                if (!url || url.indexOf('uploads/ocorrencias/') === -1) return;
                const fd = new FormData();
                fd.append('url', url);
                fetch('api_remover_arquivo.php', { method: 'POST', body: fd }).catch(function() {});
            }

            function ensureBtn() {
                if (!removeBtn || !removeBtn.isConnected) {
                    removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'editor-media-remove';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.title = 'Remover mídia';
                    editor.appendChild(removeBtn);
                    removeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const target = this._target;
                        this.classList.remove('show');
                        if (target) {
                            deleteMediaFile(getMediaUrl(target));
                            target.remove();
                        }
                        editor.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                }
                return removeBtn;
            }

            function positionBtn(el) {
                const btn = ensureBtn();
                const editorRect = editor.getBoundingClientRect();
                const elRect = el.getBoundingClientRect();
                btn.style.left = (elRect.right - editorRect.left - 10) + 'px';
                btn.style.top = (elRect.top - editorRect.top - 10) + 'px';
                btn._target = el;
                btn.classList.add('show');
            }

            function hideBtn() {
                if (removeBtn) removeBtn.classList.remove('show');
            }

            editor.addEventListener('mouseover', function(e) {
                if (e.target.closest('.editor-media-remove')) return;
                const el = e.target.closest('img.media-thumb, video.media-thumb, audio.media-audio');
                if (el) {
                    positionBtn(el);
                } else {
                    hideBtn();
                }
            });

            editor.addEventListener('mouseleave', function() {
                hideBtn();
            });

            editor.addEventListener('click', function(e) {
                const el = e.target.closest('img.media-thumb, video.media-thumb, audio.media-audio');
                if (el) {
                    e.stopPropagation();
                    positionBtn(el);
                } else {
                    hideBtn();
                }
            });

            editor.addEventListener('scroll', hideBtn);

            document.addEventListener('click', function(e) {
                if (!editor.contains(e.target)) hideBtn();
            });
        }

        function salvarEdicao() {
            const editor = document.getElementById('descricao-editor');
            const hidden = document.getElementById('descricao-hidden');
            const html = sanitizeEditorContent(editor);
            hidden.value = html === '<br>' ? '' : html;
            document.getElementById('form-editar').submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const editor = document.getElementById('descricao-editor');
            initContentEditable(editor);
            normalizeEditorMedia(editor);
            initInlineToolbar(editor);
            initMediaRemoval(editor);
        });
    </script>
    <script src="assets/js/gsm_decoder.js"></script>
    <script src="assets/js/lame.min.js"></script>
    <script src="assets/js/gsm_audio.js"></script>
</body>
</html>
