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

    $conn->begin_transaction();
    try {
        $base_id = $usuario_base_id ?? 0;
        $stmt = $conn->prepare("INSERT INTO ocorrencias (usuario_id, supervisor_nome, operadores_nomes, edificio_id, base_id, descricao, periodo_dia, data_ocorrencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $count = 0;
        foreach ($locais_edificios as $i => $edificios_block) {
            $descricao = trim($descricoes[$i] ?? '');
            $descricao_completa = $descricao;
            if (!$descricao) continue;

            if (!is_array($edificios_block)) $edificios_block = [$edificios_block];
            foreach ($edificios_block as $raw) {
                $raw = trim($raw);
                if (empty($raw)) continue;
                if (strpos($raw, 'b_') === 0) {
                    $edificio_id = null;
                    $base_id = intval(substr($raw, 2));
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
<html lang="pt-br" class="h-full">
<head>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Relatório de Plantão | Blindado Soluções</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-primary); margin: 0; padding: 0; }
        .page-wrapper { width: 100%; display: flex; flex-direction: column; min-height: 100vh; background: var(--bg-primary); }
        .page-content { width: 100%; max-width: 1200px; background: var(--bg-card); margin: 24px auto 80px auto; padding: 32px 36px; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 8px 32px var(--shadow); }
        .notion-header-static { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.8rem; color: var(--text-primary); padding: 0 0 16px 0; line-height: 1.3; border-bottom: 1px solid var(--border); }
        .notion-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 32px; margin-bottom: 28px; padding: 20px 24px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; }
        .meta-row { display: flex; align-items: center; gap: 10px; }
        .meta-label { color: var(--text-secondary); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; min-width: 90px; display: flex; align-items: center; gap: 6px; }
        .meta-value { flex: 1; }
        .meta-value input, .meta-value select { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-secondary); font-size: 0.9rem; font-weight: 500; color: var(--text-primary); outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .meta-value input:focus, .meta-value select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 169, 55, 0.18); }
        .meta-value input:hover, .meta-value select:hover { border-color: rgba(255, 255, 255, 0.25); }
        .meta-value-row { display: flex; align-items: center; gap: 8px; }
        .meta-value-row .modern-calendar-container { flex: 1; }
        .turno-select { width: auto !important; min-width: 110px; flex-shrink: 0; }
        .top-bar { position: sticky; top: 0; z-index: 1000; background: rgba(6, 19, 40, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 14px 32px; display: flex; justify-content: space-between; align-items: center; }

        .top-bar-left { display: flex; align-items: center; gap: 8px; }
        .top-bar-left i { color: var(--primary-light); font-size: 1.1rem; }
        .top-bar-left span { font-weight: 600; font-size: 0.95rem; color: var(--text-primary); }
        .top-bar-right { display: flex; align-items: center; gap: 12px; }
        .form-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 28px; padding-top: 22px; border-top: 1px solid var(--border); }

        /* Ajuste calendário */
        .modern-calendar-container { width: 100%; }
        .modern-calendar-container .form-input { border: 1px solid var(--border) !important; border-radius: 10px !important; background: var(--bg-secondary) !important; font-weight: 500 !important; color: var(--text-primary) !important; padding: 8px 12px !important; font-size: 0.9rem !important; min-height: 42px !important; box-sizing: border-box !important; }
        .modern-calendar-container .form-input:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px rgba(37, 169, 55, 0.18) !important; }
        .modern-calendar-container .form-label { display: none; }
        .meta-value .modern-calendar-container .absolute.inset-y-0.left-0 { display: none; }
        .meta-value .modern-calendar-container .form-input { padding-left: 12px !important; }

        .media-wrapper { position: relative; display: inline-block; margin: 10px 0; }
        .media-wrapper:hover .media-actions { opacity: 1; }
        .media-actions { position: absolute; top: 10px; right: 10px; display: flex; gap: 8px; opacity: 0; transition: opacity 0.2s ease; z-index: 10; }
        .media-action-btn { width: 36px; height: 36px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
        .media-action-btn.download { background: var(--secondary); color: white; }
        .media-action-btn.download:hover { background: var(--secondary-hover); transform: scale(1.1); }
        .media-action-btn.delete { background: rgba(239, 68, 68, 0.9); color: white; }
        .media-action-btn.delete:hover { background: var(--danger-hover); transform: scale(1.1); }
        .media-action-btn i { font-size: 14px; }

        .report-block { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 18px; overflow: hidden; box-shadow: 0 4px 20px var(--shadow); }
        .report-block-header { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid var(--border); flex-wrap: wrap; }
        .header-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; flex: 1; min-width: 100px; }
        .location-tag { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; background: rgba(1, 103, 219, 0.18); border: 1px solid rgba(7, 146, 242, 0.4); border-radius: 5px; font-size: 0.78rem; font-weight: 500; color: var(--secondary-light); }
        .location-placeholder { color: var(--text-secondary); font-size: 0.9rem; font-weight: 600; }
        .tag-remove { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; border-radius: 50%; background: transparent; color: var(--secondary-light); cursor: pointer; font-size: 14px; line-height: 1; padding: 0; opacity: 0.6; transition: opacity 0.1s; }
        .tag-remove:hover { opacity: 1; background: rgba(255, 255, 255, 0.1); }
        .header-location-row { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
        .add-location-btn { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 1.5px dashed rgba(255, 255, 255, 0.25); border-radius: 6px; background: transparent; color: var(--text-secondary); cursor: pointer; transition: all 0.15s; font-size: 11px; flex-shrink: 0; }
        .add-location-btn:hover { border-color: var(--primary-light); color: var(--primary-light); background: rgba(37, 169, 55, 0.1); }
        .remove-block { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: none; border-radius: 6px; background: rgba(239, 68, 68, 0.15); color: #f87171; cursor: pointer; font-size: 12px; transition: all 0.15s; flex-shrink: 0; }
        .remove-block:hover { background: var(--danger); color: #fff; }
        .report-block-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 14px; }
        .location-autocomplete { position: relative; width: 200px; flex-shrink: 0; }
        .location-input { width: 100%; padding: 6px 9px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-secondary); font-size: 0.82rem; color: var(--text-primary); outline: none; transition: border-color 0.15s; box-sizing: border-box; }
        .location-input:focus { border-color: var(--primary); }
        .location-input::placeholder { color: var(--text-secondary); opacity: 0.7; }
        .location-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-card); border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 8px 24px var(--shadow); max-height: 200px; overflow-y: auto; display: none; z-index: 100; margin-top: 2px; }
        .location-dropdown.open { display: block; }

        .inline-media-toolbar { display: flex; align-items: center; gap: 6px; padding: 6px 0; flex-wrap: wrap; }

        .toast { position: fixed; bottom: 20px; right: 20px; z-index: 99999; padding: 12px 18px; border-radius: 8px; color: #fff; font-size: 0.85rem; font-weight: 600; box-shadow: 0 8px 24px var(--shadow); display: flex; align-items: center; gap: 10px; opacity: 0; transform: translateY(10px); transition: opacity 0.25s, transform 0.25s; max-width: 340px; }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.success { background: var(--primary); }
        .toast.error { background: var(--danger-hover); }
        .toast.loading { background: var(--secondary); }
        .toast i { font-size: 1rem; }

        .upload-progress-wrap { margin: 2px 0 8px; height: 8px; border-radius: 5px; background: rgba(255, 255, 255, 0.1); overflow: hidden; position: relative; display: none; }
        .upload-progress-wrap.active { display: block; }
        .upload-progress-fill { height: 100%; width: 0; background: var(--secondary); transition: width 0.2s; border-radius: 5px; }
        .upload-progress-wrap.done .upload-progress-fill { background: var(--primary); }

        .descricao-editor { width: 100%; min-height: 280px; padding: 18px 22px; border: 1px solid var(--border); border-radius: 10px; font-size: 0.95rem; line-height: 1.7; color: var(--text-primary); background: var(--bg-secondary); outline: none; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; overflow-y: auto; position: relative; }
        .descricao-editor:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 169, 55, 0.12); }
        .descricao-editor:empty::before { content: attr(data-placeholder); color: var(--text-secondary); opacity: 0.7; pointer-events: none; }
        .editor-media-remove { position: absolute; width: 24px; height: 24px; border: none; border-radius: 50%; background: var(--danger); color: #fff; font-size: 14px; line-height: 1; cursor: pointer; display: none; align-items: center; justify-content: center; z-index: 50; box-shadow: 0 2px 8px rgba(0,0,0,0.4); padding: 0; }
        .editor-media-remove.show { display: flex; }
        .editor-media-remove:hover { background: var(--danger-hover); }
        .descricao-editor img.media-thumb { max-width: 140px; max-height: 110px; border-radius: 6px; margin: 6px 2px; cursor: zoom-in; border: 1px solid var(--border); vertical-align: middle; transition: max-width 0.2s, max-height 0.2s, border-color 0.2s; }
        .descricao-editor img.media-thumb:hover { border-color: var(--primary); }
        .descricao-editor img.media-thumb.expanded { max-width: 100%; max-height: none; cursor: zoom-out; border-color: rgba(7, 146, 242, 0.6); }
        .descricao-editor video.media-thumb { max-width: 200px; max-height: 130px; border-radius: 6px; margin: 6px 2px; cursor: pointer; border: 1px solid var(--border); background: #0b1d3f; vertical-align: middle; transition: max-width 0.2s, max-height 0.2s, border-color 0.2s; }
        .descricao-editor video.media-thumb:hover { border-color: var(--primary); }
        .descricao-editor video.media-thumb.expanded { max-width: 100%; max-height: 100%; border-color: rgba(7, 146, 242, 0.6); }
        .descricao-editor audio.media-audio { width: 100%; max-width: 340px; margin: 6px 2px; display: block; }

        .autocomplete-wrap { position: relative; width: 100%; }
        .autocomplete-input { width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-secondary); font-size: 0.9rem; font-weight: 500; color: var(--text-primary); outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .autocomplete-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 169, 55, 0.18); }
        .autocomplete-input:hover { border-color: rgba(255, 255, 255, 0.25); }
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 12px 32px var(--shadow); max-height: 240px; overflow-y: auto; display: none; z-index: 100; margin-top: 4px; }
        .autocomplete-dropdown.open { display: block; }
        .autocomplete-item { padding: 10px 14px; cursor: pointer; font-size: 0.875rem; color: var(--text-primary); transition: background 0.1s; }
        .autocomplete-item:hover { background: rgba(255, 255, 255, 0.06); }
        .autocomplete-item.highlighted { background: rgba(255, 255, 255, 0.1); }
        .autocomplete-loading { padding: 10px 14px; color: var(--text-secondary); font-size: 0.8rem; }

        .form-input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-secondary); font-size: 0.9rem; color: var(--text-primary); outline: none; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
        .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 169, 55, 0.18); }
        .add-report-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: 2px dashed rgba(255, 255, 255, 0.25); border-radius: 10px; background: transparent; color: var(--text-secondary); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 4px; }
        .add-report-btn:hover { border-color: var(--primary-light); color: var(--primary-light); background: rgba(37, 169, 55, 0.08); }
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 6px; }

        .draft-indicator { font-size: 0.78rem; color: var(--primary-light); font-weight: 600; display: none; white-space: nowrap; }
        .draft-indicator.saved { display: inline; }
        #draft-banner { position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: var(--primary); color: #fff; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 0.9rem; box-shadow: 0 2px 12px var(--shadow); flex-wrap: wrap; }

        @media (max-width: 768px) {
            .page-content { padding: 20px 16px !important; margin: 12px !important; }
            .notion-header-static { font-size: 1.25rem !important; }
            .notion-meta { grid-template-columns: 1fr !important; padding: 16px !important; }
            .meta-value-row { flex-direction: column; align-items: stretch; }
            .turno-select { width: 100% !important; }
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
                    <span id="draft-indicator" class="draft-indicator"></span>
                </div>
            </header>
            <main class="page-content">
                <?php if ($mensagem): ?>
                    <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-bold"><?= $mensagem ?></span>
                    </div>
                    <?php if ($mensagem_tipo === 'success'): ?>
                        <script>try { localStorage.removeItem('rascunho_plantao_<?= (int)$usuario_id ?>'); } catch (e) {}</script>
                    <?php endif; ?>
                <?php endif; ?>

                <form id="form-plantao" method="POST">
                    <input type="hidden" name="salvar_plantao" value="1">
                    <div id="dynamic-title" class="notion-header-static">Carregando título...</div>
                    <div class="notion-meta">
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user-tie"></i> Supervisor</span><div class="meta-value"><div class="autocomplete-wrap"><input type="text" id="supervisor-input" class="autocomplete-input" placeholder="Digite o nome do supervisor" required autocomplete="off"><input type="hidden" name="supervisor_nome" id="supervisor-nome"><div class="autocomplete-dropdown" id="supervisor-dropdown"></div></div></div></div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user"></i> Operador 1</span><div class="meta-value"><div class="autocomplete-wrap"><input type="text" id="operador1-input" class="autocomplete-input" placeholder="Digite o nome do operador" required autocomplete="off"><input type="hidden" name="operador1_nome" id="operador1-nome"><div class="autocomplete-dropdown" id="operador1-dropdown"></div></div></div></div>
                        <div class="meta-row">
                            <span class="meta-label"><i class="fas fa-calendar-alt"></i> Data / Turno</span>
                            <div class="meta-value meta-value-row">
                                <?php renderModernCalendar('data_ocorrencia', date('Y-m-d'), ''); ?>
                                <script>
                                    document.getElementById('value_calendar_data_ocorrencia').setAttribute('onchange', 'updateTitle()');
                                </script>
                                <select name="periodo_dia" id="periodo_dia" required onchange="updateTitle()" class="turno-select">
                                    <option value="dia" selected>Diurno</option>
                                    <option value="noite">Noturno</option>
                                </select>
                            </div>
                        </div>
                        <div class="meta-row"><span class="meta-label"><i class="fas fa-user"></i> Operador 2</span><div class="meta-value"><div class="autocomplete-wrap"><input type="text" id="operador2-input" class="autocomplete-input" placeholder="Digite o nome do operador" autocomplete="off"><input type="hidden" name="operador2_nome" id="operador2-nome"><div class="autocomplete-dropdown" id="operador2-dropdown"></div></div></div></div>
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
                                        <div class="inline-media-toolbar">
                                            <button type="button" class="toolbar-btn btn-add-audio" title="Adicionar áudio"><i class="fas fa-microphone"></i> Áudio</button>
                                            <button type="button" class="toolbar-btn btn-add-image" title="Adicionar imagem"><i class="fas fa-image"></i> Imagem</button>
                                            <button type="button" class="toolbar-btn btn-add-video" title="Adicionar vídeo"><i class="fas fa-video"></i> Vídeo</button>
                                        </div>
                                        <div class="descricao-editor" contenteditable="true" data-block="0" data-placeholder="Descreva detalhadamente o ocorrido..."></div>
                                        <input type="hidden" name="descricao[]" class="descricao-hidden">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-report-btn" class="add-report-btn">
                            <i class="fas fa-plus-circle"></i> Adicionar Relatório
                        </button>
                    </div>
                    <div class="form-actions">
                        <a href="consultar_ocorrencia.php" class="btn-cancel">Descartar</a>
                        <button type="button" onclick="salvarRelatorio()" class="btn-save">Finalizar Relatório</button>
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
                                scheduleDraftSave();
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
                scheduleDraftSave();
            });

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'edificios[' + blockIdx + '][]';
            hidden.value = value;
            hiddenContainer.appendChild(hidden);
            tag._hiddenInput = hidden;

            tagsContainer.appendChild(tag);
            scheduleDraftSave();
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

        // ── Contenteditable / Inline Media ──

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
            // Salvar posição do cursor para inserir mídia no lugar certo
            function saveRange() {
                const sel = window.getSelection();
                if (sel.rangeCount && editor.contains(sel.anchorNode)) {
                    editor._lastRange = sel.getRangeAt(0).cloneRange();
                }
            }
            editor.addEventListener('mouseup', saveRange);
            editor.addEventListener('keyup', saveRange);
            editor.addEventListener('blur', saveRange);

            // Placeholder via CSS :empty::before
            editor.addEventListener('input', function() {
                this.dataset.placeholder = this.getAttribute('data-placeholder');
            });
            // Colar como texto puro
            editor.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            });
            // Enter = <br> (parágrafo simples)
            editor.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    document.execCommand('insertLineBreak');
                    e.preventDefault();
                }
            });
        }

        function sanitizeEditorContent(editor) {
            const clone = editor.cloneNode(true);
            clone.querySelectorAll('.editor-media-remove').forEach(function(btn) { btn.remove(); });
            clone.querySelectorAll('img.media-thumb, video.media-thumb, audio.media-audio').forEach(el => {
                el.classList.remove('media-thumb', 'expanded', 'media-audio');
                el.removeAttribute('style');
                el.removeAttribute('contenteditable');
                if (el.tagName === 'VIDEO' || el.tagName === 'AUDIO') el.controls = true;
                if (el.tagName === 'IMG') el.setAttribute('style', 'max-width:100%;height:auto;border-radius:6px');
            });
            return clone.innerHTML;
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

        function syncContentEditable() {
            document.querySelectorAll('.report-block').forEach(block => {
                const editor = block.querySelector('.descricao-editor');
                const hidden = block.querySelector('.descricao-hidden');
                if (editor && hidden) {
                    const html = sanitizeEditorContent(editor);
                    hidden.value = html === '<br>' ? '' : html;
                }
            });
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

        function initInlineToolbar(block) {
            const editor = block.querySelector('.descricao-editor');
            const toolbar = block.querySelector('.inline-media-toolbar');
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
                        <div class="inline-media-toolbar">
                            <button type="button" class="toolbar-btn btn-add-audio" title="Adicionar áudio"><i class="fas fa-microphone"></i> Áudio</button>
                            <button type="button" class="toolbar-btn btn-add-image" title="Adicionar imagem"><i class="fas fa-image"></i> Imagem</button>
                            <button type="button" class="toolbar-btn btn-add-video" title="Adicionar vídeo"><i class="fas fa-video"></i> Vídeo</button>
                        </div>
                        <div class="descricao-editor" contenteditable="true" data-block="${idx}" data-placeholder="Descreva detalhadamente o ocorrido..."></div>
                        <input type="hidden" name="descricao[]" class="descricao-hidden">
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
            initContentEditable(div.querySelector('.descricao-editor'));
            initInlineToolbar(div);
            initMediaRemoval(div.querySelector('.descricao-editor'));

            div.querySelector('.remove-block').addEventListener('click', function() {
                div.remove();
                renumberBlocks();
                scheduleDraftSave();
            });

            return div;
        }

        function renumberBlocks() {
            const container = document.getElementById('reports-container');
            container.querySelectorAll('.report-block').forEach((block, idx) => {
                const hidContainer = block.querySelector('.selected-hidden');
                if (hidContainer) hidContainer.dataset.block = idx;
                const editor = block.querySelector('.descricao-editor');
                if (editor) editor.dataset.block = idx;
                block.querySelectorAll('input[name^="edificios["]').forEach(cb => {
                    cb.name = 'edificios[' + idx + '][]';
                });
                const tagsDiv = block.querySelector('.header-tags');
                if (tagsDiv) tagsDiv.id = 'header-tags-' + idx;
            });
        }

        function salvarRelatorio() {
            syncContentEditable();
            document.getElementById('form-plantao').submit();
        }

        // ── Autosave de rascunho (localStorage) ──
        const DRAFT_KEY = 'rascunho_plantao_' + <?= json_encode($usuario_id) ?>;
        let draftTimer = null;
        let draftSavedAt = 0;

        function collectDraft() {
            syncContentEditable();
            const blocks = [];
            document.querySelectorAll('.report-block').forEach(function(block) {
                const hiddenContainer = block.querySelector('.selected-hidden');
                const locais = [];
                if (hiddenContainer) {
                    hiddenContainer.querySelectorAll('input').forEach(function(inp) {
                        if (inp.value) locais.push(inp.value);
                    });
                }
                const descInput = block.querySelector('.descricao-hidden');
                blocks.push({ locais: locais, descricao: descInput ? descInput.value : '' });
            });
            return {
                savedAt: Date.now(),
                meta: {
                    supervisor: document.getElementById('supervisor-nome').value,
                    supervisorText: document.getElementById('supervisor-input').value,
                    operador1: document.getElementById('operador1-nome').value,
                    operador1Text: document.getElementById('operador1-input').value,
                    operador2: document.getElementById('operador2-nome').value,
                    operador2Text: document.getElementById('operador2-input').value,
                    data_ocorrencia: document.getElementById('value_calendar_data_ocorrencia').value,
                    periodo_dia: document.getElementById('periodo_dia').value
                },
                blocks: blocks
            };
        }

        function hasDraftContent(draft) {
            if (draft.meta.supervisorText || draft.meta.operador1Text || draft.meta.operador2Text) return true;
            if (draft.meta.data_ocorrencia !== document.getElementById('value_calendar_data_ocorrencia').value) return true;
            return draft.blocks.some(function(b) { return b.descricao || b.locais.length > 0; });
        }

        function saveDraftNow() {
            try {
                const draft = collectDraft();
                if (!hasDraftContent(draft)) return;
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
                draftSavedAt = draft.savedAt;
                updateDraftIndicator();
            } catch (e) {}
        }

        function scheduleDraftSave() {
            clearTimeout(draftTimer);
            draftTimer = setTimeout(saveDraftNow, 800);
        }

        function updateDraftIndicator() {
            const el = document.getElementById('draft-indicator');
            if (!el) return;
            if (draftSavedAt) {
                const d = new Date(draftSavedAt);
                const pad = n => String(n).padStart(2, '0');
                el.textContent = 'Rascunho salvo às ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
                el.classList.add('saved');
            } else {
                el.textContent = '';
                el.classList.remove('saved');
            }
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

        function restoreDraft(draft) {
            document.getElementById('supervisor-nome').value = draft.meta.supervisor || '';
            document.getElementById('supervisor-input').value = draft.meta.supervisorText || '';
            document.getElementById('operador1-nome').value = draft.meta.operador1 || '';
            document.getElementById('operador1-input').value = draft.meta.operador1Text || '';
            document.getElementById('operador2-nome').value = draft.meta.operador2 || '';
            document.getElementById('operador2-input').value = draft.meta.operador2Text || '';

            if (draft.meta.data_ocorrencia) {
                const hid = document.getElementById('value_calendar_data_ocorrencia');
                const disp = document.getElementById('display_calendar_data_ocorrencia');
                if (hid) hid.value = draft.meta.data_ocorrencia;
                if (disp) {
                    const p = draft.meta.data_ocorrencia.split('-');
                    disp.value = p[2] + '/' + p[1] + '/' + p[0];
                }
                if (window.modernCalendars && modernCalendars['calendar_data_ocorrencia']) {
                    modernCalendars['calendar_data_ocorrencia'].selectedDate = draft.meta.data_ocorrencia;
                }
            }
            if (draft.meta.periodo_dia) {
                document.getElementById('periodo_dia').value = draft.meta.periodo_dia;
            }

            const container = document.getElementById('reports-container');
            container.querySelectorAll('.report-block').forEach(function(b) { b.remove(); });

            if (!draft.blocks || draft.blocks.length === 0) {
                addReportBlock();
            } else {
                draft.blocks.forEach(function(block) {
                    const div = addReportBlock();
                    const editor = div.querySelector('.descricao-editor');
                    if (block.descricao) {
                        editor.innerHTML = block.descricao;
                        normalizeEditorMedia(editor);
                    }
                    const blockIdx = parseInt(div.querySelector('.selected-hidden').dataset.block);
                    block.locais.forEach(function(v) {
                        const found = locaisOptions.find(function(o) { return o.value === v; });
                        addLocationTag(blockIdx, v, found ? found.label : v);
                    });
                });
            }

            if (typeof updateTitle === 'function') updateTitle();
            draftSavedAt = draft.savedAt || Date.now();
            updateDraftIndicator();
        }

        function discardDraft() {
            try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
            draftSavedAt = 0;
            updateDraftIndicator();
        }

        function checkSavedDraft() {
            let draft = null;
            try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY)); } catch (e) { return; }
            if (!draft || !draft.blocks || !draft.meta) return;
            const d = new Date(draft.savedAt || Date.now());
            const pad = n => String(n).padStart(2, '0');
            const when = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());

            const banner = document.createElement('div');
            banner.id = 'draft-banner';
            banner.innerHTML =
                '<span style="display:flex;align-items:center;gap:8px"><i class="fas fa-file-pen"></i> Você tem um rascunho salvo às ' + when + '. Deseja continuar?</span>' +
                '<span style="display:flex;gap:8px">' +
                    '<button type="button" id="draft-restore" class="btn btn-sm" style="background:#fff;color:var(--primary);border:none">Restaurar</button>' +
                    '<button type="button" id="draft-discard" class="btn btn-cancel btn-sm" style="border-color:rgba(255,255,255,0.6)">Descartar</button>' +
                '</span>';
            document.body.appendChild(banner);
            banner.querySelector('#draft-restore').addEventListener('click', function() {
                restoreDraft(draft);
                banner.remove();
                showToast('Rascunho restaurado.', 'success');
            });
            banner.querySelector('#draft-discard').addEventListener('click', function() {
                discardDraft();
                banner.remove();
                showToast('Rascunho descartado.', 'success');
            });
        }

        // Autosave em mudanças do formulário (inputs, selects, editores e mídia)
        document.getElementById('form-plantao').addEventListener('input', scheduleDraftSave);
        document.getElementById('form-plantao').addEventListener('change', scheduleDraftSave);

        // "Descartar" (cancelar) remove o rascunho antes de sair
        const cancelLink = document.querySelector('.btn-cancel');
        if (cancelLink) {
            cancelLink.addEventListener('click', function() {
                discardDraft();
            });
        }

        checkSavedDraft();

        // Inicializar primeiro bloco
        document.querySelectorAll('.descricao-editor').forEach(function(ed) {
            initContentEditable(ed);
            initMediaRemoval(ed);
        });
        document.querySelectorAll('.location-autocomplete').forEach(ac => initLocationAutocomplete(ac));
        document.querySelectorAll('.report-block').forEach(block => initInlineToolbar(block));

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
                scheduleDraftSave();
            });
        });
    </script>
    <script src="assets/js/gsm_decoder.js"></script>
    <script src="assets/js/lame.min.js"></script>
    <script src="assets/js/gsm_audio.js"></script>
</body>
</html>
<?php
$conn->close();
?>
