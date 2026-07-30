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

// Carrega a base do usuário direto do banco
$stmt = $conn->prepare("SELECT base_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$usuario_base_id = $row['base_id'] ?? null;
$_SESSION['usuario_base_id'] = $usuario_base_id;

if ($usuario_categoria === 'operador') {
    $bid = intval($usuario_base_id);
    if ($bid > 0) {
        $bases = $conn->query("SELECT id, nome FROM bases WHERE id = $bid")->fetch_all(MYSQLI_ASSOC);
        $edificios = $conn->query("SELECT e.id, e.nome FROM edificios e WHERE e.base_id = $bid ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);
    } else {
        $bases = [];
        $edificios = [];
    }
} else {
    $bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
    $edificios = $conn->query("SELECT e.id, e.nome, b.nome as base_nome FROM edificios e LEFT JOIN bases b ON e.base_id = b.id ORDER BY b.nome, e.nome")->fetch_all(MYSQLI_ASSOC);
}

$mensagem = '';
$mensagem_tipo = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_plantao'])) {
    $supervisor = trim($_POST['supervisor_nome']);
    $operador1 = trim($_POST['operador1_nome'] ?? '');
    $operador2 = trim($_POST['operador2_nome'] ?? '');
    $operadores = implode(' / ', array_filter([$operador1, $operador2]));
    $periodo = $_POST['periodo_dia'];
    $data_plantao = $_POST['data_ocorrencia'];

    $locais_edificios = $_POST['edificios'] ?? [];
    $descricoes = $_POST['descricao'] ?? [];
    $midias = $_POST['midias'] ?? [];

    $conn->begin_transaction();
    try {
        $base_id = $usuario_base_id ?? 0;
        $stmt = $conn->prepare("INSERT INTO ocorrencias (usuario_id, supervisor_nome, operadores_nomes, edificio_id, base_id, descricao, periodo_dia, data_ocorrencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($locais_edificios as $i => $edificios_block) {
            $descricao = trim($descricoes[$i] ?? '');
            $midias_bloco = isset($midias[$i]) ? (is_array($midias[$i]) ? $midias[$i] : [$midias[$i]]) : [];
            $midias_html = '';
            foreach ($midias_bloco as $m) {
                $m = trim($m);
                if ($m) {
                    $ext = strtolower(pathinfo(parse_url($m, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'flv', 'wmv'];
                    $audioExts = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'wma'];
                    if (in_array($ext, $videoExts)) {
                        $midias_html .= '<div class="media-wrapper"><video controls width="100%"><source src="' . htmlspecialchars($m) . '"></video></div>';
                    } elseif (in_array($ext, $audioExts)) {
                        $midias_html .= '<div class="media-wrapper"><audio controls style="width:100%"><source src="' . htmlspecialchars($m) . '"></audio></div>';
                    } else {
                        $midias_html .= '<div class="media-wrapper"><img src="' . htmlspecialchars($m) . '" style="max-width:100%;height:auto"></div>';
                    }
                }
            }
            $descricao_completa = $descricao . ($midias_html ? '<div class="report-media">' . $midias_html . '</div>' : '');
            if (!$descricao && !$midias_html) continue;

            if (!is_array($edificios_block)) $edificios_block = [$edificios_block];
            foreach ($edificios_block as $raw) {
                $raw = trim($raw);
                if (empty($raw)) continue;
                if (strpos($raw, 'b_') === 0) {
                    $edificio_id = 0;
                } elseif (strpos($raw, 'e_') === 0) {
                    $edificio_id = intval(substr($raw, 2));
                } else {
                    continue;
                }
                $stmt->bind_param("issiisss", $usuario_id, $supervisor, $operadores, $edificio_id, $base_id, $descricao_completa, $periodo, $data_plantao);
                $stmt->execute();
                $count++;
            }
        }
        $conn->commit();
        $mensagem = "$count relatório(s) salvo(s) com sucesso!";
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
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Relatório de Plantão | Blindado Soluções</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1a1a2e; margin: 0; padding: 0; }
        .page-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; background: #f0f2f5; }
        .page-content { width: 100%; max-width: 1200px; background: #ffffff; margin: 24px auto 80px auto; padding: 32px 36px; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .notion-header-static { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.8rem; color: #1a1a2e; padding: 0 0 16px 0; line-height: 1.3; border-bottom: 2px solid #f0f2f5; }
        .notion-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 32px; margin-bottom: 28px; padding: 20px 24px; background: #f8f9fc; border-radius: 12px; }
        .meta-row { display: flex; align-items: center; gap: 10px; }
        .meta-label { color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; min-width: 90px; display: flex; align-items: center; gap: 6px; }
        .meta-value { flex: 1; }
        .meta-value input, .meta-value select { width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 0.9rem; font-weight: 500; color: #1a1a2e; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .meta-value input:focus, .meta-value select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .meta-value input:hover, .meta-value select:hover { border-color: #94a3b8; }
        .top-bar { position: sticky; top: 0; z-index: 1000; background: #ffffff; border-bottom: 1px solid #e8eaed; padding: 14px 32px; display: flex; justify-content: space-between; align-items: center; }
        
        .top-bar-left { display: flex; align-items: center; gap: 8px; }
        .top-bar-left i { color: #3b82f6; font-size: 1.1rem; }
        .top-bar-left span { font-weight: 600; font-size: 0.95rem; color: #1a1a2e; }
        .top-bar-right { display: flex; align-items: center; gap: 12px; }
        .btn-cancel { padding: 8px 18px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.15s; text-decoration: none; }
        .btn-cancel:hover { border-color: #cbd5e1; color: #475569; }
        .btn-save { padding: 8px 20px; border: none; border-radius: 8px; background: #1a1a2e; color: #fff; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.15s; }
        .btn-save:hover { background: #2d2d4a; }

        /* Ajuste calendário */
        .modern-calendar-container { width: 100%; }
        .modern-calendar-container .form-input { border: 1.5px solid #e2e8f0 !important; border-radius: 8px !important; background: #fff !important; font-weight: 500 !important; color: #1a1a2e !important; padding: 8px 12px !important; font-size: 0.9rem !important; }
        .modern-calendar-container .form-input:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important; }
        .modern-calendar-container .form-label { display: none; }
        .meta-value .modern-calendar-container .absolute.inset-y-0.left-0 { display: none; }
        .meta-value .modern-calendar-container .form-input { padding-left: 12px !important; }

        .media-wrapper { position: relative; display: inline-block; margin: 10px 0; }
        .media-wrapper:hover .media-actions { opacity: 1; }
        .media-actions { position: absolute; top: 10px; right: 10px; display: flex; gap: 8px; opacity: 0; transition: opacity 0.2s ease; z-index: 10; }
        .media-action-btn { width: 36px; height: 36px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .media-action-btn.download { background: rgba(59,130,246,0.9); color: white; }
        .media-action-btn.download:hover { background: rgba(59,130,246,1); transform: scale(1.1); }
        .media-action-btn.delete { background: rgba(239,68,68,0.9); color: white; }
        .media-action-btn.delete:hover { background: rgba(239,68,68,1); transform: scale(1.1); }
        .media-action-btn i { font-size: 14px; }

        .report-block { background: #fff; border: 1px solid #e8eaed; border-radius: 12px; margin-bottom: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
        .report-block-header { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: #f8f9fc; border-bottom: 1px solid #e8eaed; flex-wrap: wrap; }
        .header-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; flex: 1; min-width: 100px; }
        .location-tag { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 5px; font-size: 0.78rem; font-weight: 500; color: #1e40af; }
        .location-placeholder { color: #475569; font-size: 0.9rem; font-weight: 600; }
        .tag-remove { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; border-radius: 50%; background: transparent; color: #1e40af; cursor: pointer; font-size: 14px; line-height: 1; padding: 0; opacity: 0.6; transition: opacity 0.1s; }
        .tag-remove:hover { opacity: 1; background: rgba(0,0,0,0.05); }
        .header-location-row { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
        .add-location-btn { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 1.5px dashed #cbd5e1; border-radius: 6px; background: transparent; color: #94a3b8; cursor: pointer; transition: all 0.15s; font-size: 11px; flex-shrink: 0; }
        .add-location-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #f0f4ff; }
        .remove-block { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: none; border-radius: 6px; background: #fee2e2; color: #ef4444; cursor: pointer; font-size: 12px; transition: all 0.15s; flex-shrink: 0; }
        .remove-block:hover { background: #ef4444; color: #fff; }
        .report-block-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 14px; }
        .location-autocomplete { position: relative; width: 200px; flex-shrink: 0; }
        .location-input { width: 100%; padding: 6px 9px; border: 1.5px solid #e2e8f0; border-radius: 6px; background: #fff; font-size: 0.82rem; color: #1a1a2e; outline: none; transition: border-color 0.15s; box-sizing: border-box; }
        .location-input:focus { border-color: #3b82f6; }
        .location-input::placeholder { color: #a0afbe; }
        .location-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; display: none; z-index: 100; margin-top: 2px; }
        .location-dropdown.open { display: block; }

        .upload-area { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 28px; border: 2px dashed #d0d5dd; border-radius: 12px; background: #fafbfc; cursor: pointer; transition: all 0.2s; min-height: 100px; }
        .upload-area:hover { border-color: #3b82f6; background: #f0f4ff; }
        .upload-area.has-files { border-style: solid; border-color: #22c55e; background: #f0fdf4; padding: 16px; }
        .upload-icon { font-size: 28px; color: #98a2b3; margin-bottom: 8px; transition: all 0.2s; }
        .upload-area:hover .upload-icon { color: #3b82f6; transform: scale(1.1); }
        .upload-text { font-size: 0.85rem; font-weight: 600; color: #475569; }
        .upload-hint { font-size: 0.75rem; color: #98a2b3; margin-top: 4px; }
        .upload-input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10; }
        .upload-previews { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; width: 100%; }
        .upload-preview-item { position: relative; width: 80px; height: 80px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; flex-shrink: 0; }
        .upload-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .upload-preview-item .file-icon { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #64748b; background: #f1f5f9; }
        .upload-preview-item .file-name { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; font-size: 8px; padding: 2px 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }
        .upload-preview-item .remove-file { position: absolute; top: 2px; right: 2px; width: 18px; height: 18px; border: none; border-radius: 50%; background: rgba(239,68,68,0.8); color: #fff; font-size: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 11; }
        .upload-preview-item .remove-file:hover { background: #ef4444; }
        .upload-progress { width: 100%; height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 8px; overflow: hidden; }
        .upload-progress-bar { height: 100%; background: #22c55e; border-radius: 2px; transition: width 0.3s; width: 0%; }
        .upload-hidden-inputs { display: none; }

        .descricao-textarea { width: 100%; min-height: 280px; padding: 18px 22px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; line-height: 1.7; color: #1a1a2e; background: #fff; outline: none; resize: vertical; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .descricao-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }

        .autocomplete-wrap { position: relative; width: 100%; }
        .autocomplete-input { width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 0.9rem; font-weight: 500; color: #1a1a2e; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .autocomplete-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .autocomplete-input:hover { border-color: #94a3b8; }
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); max-height: 240px; overflow-y: auto; display: none; z-index: 100; margin-top: 4px; }
        .autocomplete-dropdown.open { display: block; }
        .autocomplete-item { padding: 10px 14px; cursor: pointer; font-size: 0.875rem; color: #1e293b; transition: background 0.1s; }
        .autocomplete-item:hover { background: #f1f5f9; }
        .autocomplete-item.highlighted { background: #e2e8f0; }
        .autocomplete-loading { padding: 10px 14px; color: #94a3b8; font-size: 0.8rem; }

        .form-input { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 0.9rem; color: #1a1a2e; outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .add-report-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: 2px dashed #d0d5dd; border-radius: 10px; background: transparent; color: #64748b; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 4px; }
        .add-report-btn:hover { border-color: #3b82f6; color: #3b82f6; background: #f8faff; }
        .form-label { font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 6px; }

        @media (max-width: 768px) {
            .page-content { padding: 20px 16px !important; margin: 12px !important; }
            .notion-header-static { font-size: 1.25rem !important; }
            .notion-meta { grid-template-columns: 1fr !important; padding: 16px !important; }
            .top-bar { padding: 12px 16px !important; }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex-1 flex flex-col page-wrapper">
            <header class="top-bar">
                <div class="top-bar-left">
                    <i class="fas fa-file-alt"></i>
                    <span>Novo Relatório de Plantão</span>
                </div>
                <div class="top-bar-right">
                    <a href="consultar_ocorrencia.php" class="btn-cancel">Descartar</a>
                    <button onclick="salvarRelatorio()" class="btn-save">Finalizar Relatório</button>
                </div>
            </header>
            <main class="page-content">
                <?php if ($mensagem): ?>
                    <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-bold"><?= $mensagem ?></span>
                    </div>
                <?php endif; ?>

                <form id="form-plantao" method="POST">
                    <input type="hidden" name="salvar_plantao" value="1">
                    <div id="dynamic-title" class="notion-header-static">Carregando título...</div>
                    <div class="notion-meta">
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user-tie"></i> Supervisor</span><div class="meta-value"><div class="autocomplete-wrap"><input type="text" id="supervisor-input" class="autocomplete-input" placeholder="Digite o nome do supervisor" required autocomplete="off"><input type="hidden" name="supervisor_nome" id="supervisor-nome"><div class="autocomplete-dropdown" id="supervisor-dropdown"></div></div></div></div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user"></i> Operador 1</span><div class="meta-value"><div class="autocomplete-wrap"><input type="text" id="operador1-input" class="autocomplete-input" placeholder="Digite o nome do operador" required autocomplete="off"><input type="hidden" name="operador1_nome" id="operador1-nome"><div class="autocomplete-dropdown" id="operador1-dropdown"></div></div></div></div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user"></i> Operador 2</span><div class="meta-value"><div class="autocomplete-wrap"><input type="text" id="operador2-input" class="autocomplete-input" placeholder="Digite o nome do operador" autocomplete="off"><input type="hidden" name="operador2_nome" id="operador2-nome"><div class="autocomplete-dropdown" id="operador2-dropdown"></div></div></div></div>
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
                    </div>
                    <div class="w-full space-y-6">
                        <div id="reports-container">
                            <div class="report-block">
                                <div class="report-block-header">
                                    <div class="header-tags" id="header-tags-0"><span class="location-placeholder">Busque e adicione um local para o relatório</span></div>
                                    <div class="header-location-row">
                                        <div class="location-autocomplete">
                                            <input type="text" class="location-input" placeholder="Buscar local..." autocomplete="off">
                                            <div class="location-dropdown"></div>
                                        </div>
                                        <button type="button" class="add-location-btn" title="Adicionar local"><i class="fas fa-plus"></i></button>
                                        <button type="button" class="remove-block" title="Remover relatório" style="display:none"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <div class="report-block-body">
                                    <div class="selected-hidden" data-block="0"></div>
                                    <div class="space-y-2">
                                        <textarea name="descricao[]" class="descricao-textarea" placeholder="Descreva detalhadamente o ocorrido..."></textarea>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="form-label">Mídia / Anexo</label>
                                        <div class="upload-area" data-block="0">
                                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                            <p class="upload-text">Clique ou arraste arquivos aqui</p>
                                            <p class="upload-hint">Qualquer formato: imagens, vídeos, áudios, PDF, etc.</p>
                                            <input type="file" class="upload-input" multiple accept="*/*">
                                            <div class="upload-previews"></div>
                                            <div class="upload-hidden-inputs"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-report-btn" class="add-report-btn">
                            <i class="fas fa-plus-circle"></i> Adicionar Relatório
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>
    <script>
        // Formata nome próprio (padrão brasileiro)
        function formatName(str) {
            const lower = ['de', 'da', 'do', 'das', 'dos', 'e'];
            return str.trim().split(/\s+/).map((word, i) => {
                const w = word.toLowerCase();
                return i === 0 || !lower.includes(w)
                    ? w.charAt(0).toUpperCase() + w.slice(1)
                    : w;
            }).join(' ');
        }

        // Autocomplete para supervisor e operadores
        function initAutocomplete(inputId, hiddenId, dropdownId, categoria) {
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const dropdown = document.getElementById(dropdownId);
            let timer, selected = false;

            input.addEventListener('input', function() {
                selected = false;
                hidden.value = '';
                const val = this.value.trim();
                if (val.length < 2) { dropdown.classList.remove('open'); return; }
                clearTimeout(timer);
                timer = setTimeout(() => {
                    dropdown.innerHTML = '<div class="autocomplete-loading">Buscando...</div>';
                    dropdown.classList.add('open');
                    fetch('api_buscar_usuarios.php?q=' + encodeURIComponent(val) + '&categoria=' + encodeURIComponent(categoria))
                    .then(r => r.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.length === 0) {
                            dropdown.innerHTML = '<div class="autocomplete-item" style="color:#94a3b8;cursor:default">Nenhum usuário encontrado</div>';
                            return;
                        }
                        data.forEach((item, idx) => {
                            const div = document.createElement('div');
                            div.className = 'autocomplete-item' + (idx === 0 ? ' highlighted' : '');
                            div.textContent = item.label;
                            div.dataset.label = item.label;
                            div.addEventListener('click', function() {
                                const name = formatName(this.dataset.label);
                                input.value = name;
                                hidden.value = name;
                                dropdown.classList.remove('open');
                                selected = true;
                            });
                            dropdown.appendChild(div);
                        });
                    })
                    .catch(() => { dropdown.innerHTML = '<div class="autocomplete-item" style="color:#94a3b8;cursor:default">Erro ao buscar</div>'; });
                }, 250);
            });

            input.addEventListener('keydown', function(e) {
                const items = dropdown.querySelectorAll('.autocomplete-item:not(.autocomplete-loading)');
                if (items.length === 0) return;
                let idx = Array.from(items).findIndex(el => el.classList.contains('highlighted'));
                if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
                else if (e.key === 'Enter') { e.preventDefault(); if (idx >= 0) { items[idx].click(); } return; }
                else return;
                items.forEach(el => el.classList.remove('highlighted'));
                items[idx].classList.add('highlighted');
                items[idx].scrollIntoView({ block: 'nearest' });
            });

            input.addEventListener('blur', function() {
                setTimeout(() => dropdown.classList.remove('open'), 200);
                if (!selected && this.value.trim()) {
                    hidden.value = formatName(this.value.trim());
                }
            });

            input.addEventListener('focus', function() {
                if (this.value.trim().length >= 2 && dropdown.children.length > 0) {
                    dropdown.classList.add('open');
                }
            });
        }

        initAutocomplete('supervisor-input', 'supervisor-nome', 'supervisor-dropdown', 'supervisor,gerente,diretor');
        initAutocomplete('operador1-input', 'operador1-nome', 'operador1-dropdown', 'operador');
        initAutocomplete('operador2-input', 'operador2-nome', 'operador2-dropdown', 'operador');

        // Atualizar título
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

        // Gerenciamento de blocos de relatório
        let blockIndex = 1;

        const basesOptions = <?= json_encode(array_map(function($b) {
            return ['value' => 'b_' . $b['id'], 'label' => 'Base ' . $b['nome']];
        }, $bases)) ?>;

        const edificiosOptions = <?= json_encode(array_map(function($ed) {
            return ['value' => 'e_' . $ed['id'], 'label' => $ed['nome']];
        }, $edificios)) ?>;

        const locaisOptions = basesOptions.concat(edificiosOptions);

        function addLocationTag(blockIdx, value, label) {
            const tagsContainer = document.getElementById('header-tags-' + blockIdx);
            const hiddenContainer = document.querySelector('.selected-hidden[data-block="' + blockIdx + '"]');
            if (!tagsContainer || !hiddenContainer) return;

            const placeholder = tagsContainer.querySelector('.location-placeholder');
            if (placeholder) placeholder.style.display = 'none';

            const tag = document.createElement('span');
            tag.className = 'location-tag';
            tag.innerHTML = label + '<button type="button" class="tag-remove">&times;</button>';
            tag.querySelector('.tag-remove').addEventListener('click', function() {
                const hid = tag._hiddenInput;
                if (hid) hid.remove();
                tag.remove();
                if (placeholder && tagsContainer.querySelectorAll('.location-tag').length === 0) {
                    placeholder.style.display = '';
                }
            });

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'edificios[' + blockIdx + '][]';
            hidden.value = value;
            hiddenContainer.appendChild(hidden);
            tag._hiddenInput = hidden;

            tagsContainer.appendChild(tag);
        }

        function initLocationAutocomplete(container) {
            const input = container.querySelector('.location-input');
            const dropdown = container.querySelector('.location-dropdown');
            const block = container.closest('.report-block');
            const hiddenContainer = block.querySelector('.selected-hidden');
            let timer;

            input.addEventListener('input', function() {
                const val = this.value.trim();
                if (val.length < 1) { dropdown.classList.remove('open'); return; }
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const q = val.toLowerCase();
                    const results = locaisOptions.filter(o => o.label.toLowerCase().includes(q));
                    dropdown.innerHTML = '';
                    if (results.length === 0) {
                        dropdown.innerHTML = '<div class="autocomplete-item" style="color:#94a3b8;cursor:default">Nenhum local encontrado</div>';
                    } else {
                        results.forEach((item, idx) => {
                            const div = document.createElement('div');
                            div.className = 'autocomplete-item' + (idx === 0 ? ' highlighted' : '');
                            div.textContent = item.label;
                            div.dataset.value = item.value;
                            div.addEventListener('click', function() {
                                addLocationTag(parseInt(hiddenContainer.dataset.block), this.dataset.value, this.textContent);
                                input.value = '';
                                dropdown.classList.remove('open');
                                input.focus();
                            });
                            dropdown.appendChild(div);
                        });
                    }
                    dropdown.classList.add('open');
                }, 150);
            });

            input.addEventListener('keydown', function(e) {
                const items = dropdown.querySelectorAll('.autocomplete-item');
                if (items.length === 0 || !dropdown.classList.contains('open')) return;
                let idx = Array.from(items).findIndex(el => el.classList.contains('highlighted'));
                if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
                else if (e.key === 'Enter') { e.preventDefault(); if (idx >= 0) { items[idx].click(); } return; }
                else return;
                items.forEach(el => el.classList.remove('highlighted'));
                items[idx].classList.add('highlighted');
                items[idx].scrollIntoView({ block: 'nearest' });
            });

            input.addEventListener('blur', function() {
                setTimeout(() => dropdown.classList.remove('open'), 200);
            });

            input.addEventListener('focus', function() {
                if (this.value.trim().length >= 1 && dropdown.children.length > 0) {
                    dropdown.classList.add('open');
                }
            });
        }

        function initUploadArea(area) {
            const input = area.querySelector('.upload-input');
            const previews = area.querySelector('.upload-previews');
            const hiddenInputs = area.querySelector('.upload-hidden-inputs');

            input.addEventListener('change', function() {
                const files = Array.from(this.files);
                files.forEach(file => uploadFile(file, area));
                this.value = '';
            });

            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#22c55e';
                this.style.background = '#f0fdf4';
            });
            area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#cbd5e1';
                this.style.background = '';
            });
            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#cbd5e1';
                this.style.background = '';
                const files = Array.from(e.dataTransfer.files);
                files.forEach(file => uploadFile(file, area));
            });
        }

        function uploadFile(file, area) {
            const previews = area.querySelector('.upload-previews');
            const hiddenInputs = area.querySelector('.upload-hidden-inputs');
            const ext = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg','jpeg','png','gif','webp','bmp','svg'].includes(ext);

            const item = document.createElement('div');
            item.className = 'upload-preview-item';

            if (isImage) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    item.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
                };
                reader.readAsDataURL(file);
            } else {
                const icons = { 'pdf':'fa-file-pdf','mp3':'fa-file-audio','wav':'fa-file-audio','mp4':'fa-file-video','mov':'fa-file-video','zip':'fa-file-archive','rar':'fa-file-archive','doc':'fa-file-word','docx':'fa-file-word','xls':'fa-file-excel','xlsx':'fa-file-excel' };
                const icon = icons[ext] || 'fa-file';
                item.innerHTML = `<div class="file-icon"><i class="fas ${icon}"></i></div>`;
            }

            const nameSpan = document.createElement('div');
            nameSpan.className = 'file-name';
            nameSpan.textContent = file.name.length > 15 ? file.name.slice(0, 12) + '...' : file.name;
            item.appendChild(nameSpan);

            const progress = document.createElement('div');
            progress.className = 'upload-progress';
            const bar = document.createElement('div');
            bar.className = 'upload-progress-bar';
            progress.appendChild(bar);
            item.appendChild(progress);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-file';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                const hidden = item.querySelector('input[type="hidden"]');
                if (hidden) hidden.remove();
                item.remove();
            };
            item.appendChild(removeBtn);

            previews.appendChild(item);
            area.classList.add('has-files');

            const fd = new FormData();
            fd.append('file', file);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api_upload_imagem.php', true);
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    bar.style.width = (e.loaded / e.total * 100) + '%';
                }
            };
            xhr.onload = function() {
                bar.style.width = '100%';
                setTimeout(() => progress.remove(), 500);
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.location) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'midias[' + area.dataset.block + '][]';
                        hidden.value = result.location;
                        hiddenInputs.appendChild(hidden);
                    } else {
                        item.innerHTML = '<div class="file-icon" style="color:#ef4444"><i class="fas fa-exclamation-triangle"></i></div><div class="file-name" style="color:#ef4444">Erro</div>';
                    }
                } catch(e) {
                    item.innerHTML = '<div class="file-icon" style="color:#ef4444"><i class="fas fa-exclamation-triangle"></i></div><div class="file-name" style="color:#ef4444">Erro</div>';
                }
            };
            xhr.onerror = function() {
                item.innerHTML = '<div class="file-icon" style="color:#ef4444"><i class="fas fa-exclamation-triangle"></i></div><div class="file-name" style="color:#ef4444">Erro</div>';
            };
            xhr.send(fd);
        }

        function addReportBlock() {
            blockIndex++;
            const container = document.getElementById('reports-container');
            const blocks = container.querySelectorAll('.report-block');
            const num = blocks.length + 1;
            const idx = num - 1;

            const div = document.createElement('div');
            div.className = 'report-block';
            div.innerHTML = `
                <div class="report-block-header">
                    <div class="header-tags" id="header-tags-${idx}"><span class="location-placeholder">Busque e adicione um local para o relatório</span></div>
                    <div class="header-location-row">
                        <div class="location-autocomplete">
                            <input type="text" class="location-input" placeholder="Buscar local..." autocomplete="off">
                            <div class="location-dropdown"></div>
                        </div>
                        <button type="button" class="add-location-btn" title="Adicionar local"><i class="fas fa-plus"></i></button>
                        <button type="button" class="remove-block" title="Remover relatório"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="report-block-body">
                    <div class="selected-hidden" data-block="${idx}"></div>
                    <div class="space-y-2">
                        <textarea name="descricao[]" class="descricao-textarea" placeholder="Descreva detalhadamente o ocorrido..."></textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="form-label">Mídia / Anexo</label>
                        <div class="upload-area" data-block="${blockIndex}">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p class="upload-text">Clique ou arraste arquivos aqui</p>
                            <p class="upload-hint">Qualquer formato: imagens, vídeos, áudios, PDF, etc.</p>
                            <input type="file" class="upload-input" multiple accept="*/*">
                            <div class="upload-previews"></div>
                            <div class="upload-hidden-inputs"></div>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(div);

            const headerInput = div.querySelector('.report-block-header .location-autocomplete .location-input');
            const addBtn = div.querySelector('.add-location-btn');

            addBtn.addEventListener('click', function() {
                headerInput.focus();
                headerInput.value = '';
                headerInput.dispatchEvent(new Event('input'));
            });

            initLocationAutocomplete(div.querySelector('.report-block-header .location-autocomplete'));
            initUploadArea(div.querySelector('.upload-area'));
            initTextareaAutoResize(div.querySelector('.descricao-textarea'));

            div.querySelector('.remove-block').addEventListener('click', function() {
                div.remove();
                renumberBlocks();
            });
        }

        function renumberBlocks() {
            const container = document.getElementById('reports-container');
            container.querySelectorAll('.report-block').forEach((block, idx) => {
                block.querySelector('.upload-area').dataset.block = idx;
                const hidContainer = block.querySelector('.selected-hidden');
                if (hidContainer) hidContainer.dataset.block = idx;
                block.querySelectorAll('input[name^="edificios["]').forEach(cb => {
                    cb.name = 'edificios[' + idx + '][]';
                });
                const tagsDiv = block.querySelector('.header-tags');
                if (tagsDiv) tagsDiv.id = 'header-tags-' + idx;
            });
        }

        function initTextareaAutoResize() {
            document.querySelectorAll('.descricao-textarea').forEach(ta => {
                ta.style.height = 'auto';
                ta.style.height = Math.max(ta.scrollHeight, 320) + 'px';
                ta.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.max(this.scrollHeight, 320) + 'px';
                });
            });
        }

        function salvarRelatorio() {
            document.getElementById('form-plantao').submit();
        }

        // Inicializar primeiro bloco
        document.querySelectorAll('.upload-area').forEach(initUploadArea);
        initTextareaAutoResize();
        document.querySelectorAll('.location-autocomplete').forEach(ac => initLocationAutocomplete(ac));

        // Botão adicionar local no primeiro bloco
        document.querySelector('.add-location-btn').addEventListener('click', function() {
            const block = this.closest('.report-block');
            const input = block.querySelector('.header-location-row .location-input');
            input.focus();
            input.value = '';
            input.dispatchEvent(new Event('input'));
        });

        // Botão adicionar relatório
        document.getElementById('add-report-btn').addEventListener('click', addReportBlock);

        // Remover blocos
        document.querySelectorAll('.remove-block').forEach(btn => {
            btn.addEventListener('click', function() {
                const block = this.closest('.report-block');
                block.remove();
                renumberBlocks();
                if (document.querySelectorAll('.report-block').length === 0) {
                    addReportBlock();
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
