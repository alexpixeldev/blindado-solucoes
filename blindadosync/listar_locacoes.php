<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

function btnCopiar($texto) {
    $js = json_encode($texto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return '<button type="button" class="icon-btn" onclick=\'copiarTexto(' . $js . ', this)\' onmouseover="this.style.background=\'#64748b\'" onmouseout="this.style.background=\'#94a3b8\'" title="Copiar"><i class="fas fa-copy"></i></button>';
}

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
$mensagem = '';
$mensagem_tipo = '';

// Process single deletion (Manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_locacao'])) {
    if ($usuario_categoria === 'gerente') {
        $id_delete = filter_input(INPUT_POST, 'id_delete', FILTER_VALIDATE_INT);
        if ($id_delete) {
            $stmt = $conn->prepare("DELETE FROM locacoes_inquilinos WHERE locacao_id = ?");
            $stmt->bind_param("i", $id_delete);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM locacoes_veiculos WHERE locacao_id = ?");
            $stmt->bind_param("i", $id_delete);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM locacoes WHERE id = ?");
            $stmt->bind_param("i", $id_delete);
            if ($stmt->execute()) {
                $mensagem = "Locação excluída com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao excluir locação: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        }
    }
}

// Process bulk deletion (Manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_locacoes'])) {
    if ($usuario_categoria === 'gerente') {
        $ids_raw = $_POST['locacao_ids'] ?? [];
        $ids = array_filter(array_map('intval', $ids_raw));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));

            $stmt = $conn->prepare("DELETE FROM locacoes_inquilinos WHERE locacao_id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM locacoes_veiculos WHERE locacao_id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM locacoes WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);
            if ($stmt->execute()) {
                $mensagem = count($ids) . " locação(ões) excluída(s) com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao excluir locações: " . $conn->error;
                $mensagem_tipo = "error";
            }
            $stmt->close();
        }
    }
}

// Initialize filter variables
$selected_filter = filter_input(INPUT_GET, 'edificio_id', FILTER_DEFAULT);
$filtro_edificio = null;
$filtro_base = null;
if ($selected_filter !== null && $selected_filter !== '') {
    if (strpos($selected_filter, 'base_') === 0) {
        $filtro_base = intval(substr($selected_filter, 5));
    } else {
        $filtro_edificio = filter_var($selected_filter, FILTER_VALIDATE_INT);
    }
}

$data_inicio = filter_input(INPUT_GET, 'data_inicio');
$data_fim = filter_input(INPUT_GET, 'data_fim');

// Fetch bases and buildings for the dropdowns
$bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$edificios = $conn->query("SELECT id, nome, base_id FROM edificios ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

// Main Query Construction
    $sql = "SELECT 
                l.*, 
                e.nome as nome_edificio
            FROM locacoes l
        LEFT JOIN edificios e ON l.edificio_id = e.id
        WHERE 1=1";

$params = [];
$types = "";

if ($filtro_base) {
    $sql .= " AND e.base_id = ?";
    $params[] = $filtro_base;
    $types .= "i";
} elseif ($filtro_edificio) {
    $sql .= " AND l.edificio_id = ?";
    $params[] = $filtro_edificio;
    $types .= "i";
}

if ($data_inicio) {
    $sql .= " AND DATE(l.data_registro) >= ?";
    $params[] = $data_inicio;
    $types .= "s";
}

if ($data_fim) {
    $sql .= " AND DATE(l.data_registro) <= ?";
    $params[] = $data_fim;
    $types .= "s";
}

$sql .= " GROUP BY l.id ORDER BY l.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$locacoes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$locacaoIds = array_column($locacoes, 'id');
$locacaoInquilinos = [];
$locacaoVeiculos = [];
if (!empty($locacaoIds)) {
    $ids = implode(',', array_map('intval', $locacaoIds));

    $inquilinoQuery = "SELECT locacao_id, nome, documento, telefone, selfie FROM locacoes_inquilinos WHERE locacao_id IN ($ids) ORDER BY locacao_id, id";
    $inquilinoResult = $conn->query($inquilinoQuery);
    if ($inquilinoResult) {
        while ($row = $inquilinoResult->fetch_assoc()) {
            $locacaoInquilinos[$row['locacao_id']][] = $row;
        }
    }

    $veiculoQuery = "SELECT locacao_id, modelo, cor, placa, acesso_garagem FROM locacoes_veiculos WHERE locacao_id IN ($ids) ORDER BY locacao_id, id";
    $veiculoResult = $conn->query($veiculoQuery);
    if ($veiculoResult) {
        while ($row = $veiculoResult->fetch_assoc()) {
            $locacaoVeiculos[$row['locacao_id']][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locações | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    
    
    
    <!-- Google Fonts & Font Awesome -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        .selfie-thumb {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .selfie-thumb:hover {
            transform: scale(1.12);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.4);
        }
        .photo-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.75);
            backdrop-filter: blur(6px);
            display: none;
            z-index: 50;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .photo-modal-backdrop.active {
            display: flex;
        }
        .photo-modal-card {
            max-width: 920px;
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 32px 90px rgba(0,0,0,0.55);
        }
        .photo-modal-card img {
            width: 100%;
            height: auto;
            display: block;
        }
        .photo-modal-footer {
            padding: 1rem 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .photo-modal-footer .modal-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .photo-modal-footer .modal-title {
            font-size: 0.95rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        .locacoes-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .loc-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            box-shadow: 0 8px 24px var(--shadow);
        }
        .loc-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            border-radius: 1rem 1rem 0 0;
        }
        .loc-card-title {
            font-weight: 700;
            font-size: 0.8rem;
            color: #25A937;
        }
        .loc-card-apt {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }
        .loc-card-date {
            margin-left: auto;
            font-size: 0.7rem;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        .loc-card-delete {
            flex-shrink: 0;
        }
        .loc-card-body {
            padding: 0.75rem 1rem 1rem 1rem;
        }
        .loc-section + .loc-section {
            border-top: 1px solid var(--border);
            margin-top: 6px;
            padding-top: 6px;
        }
        .loc-inq-row > div {
            transition: background 0.15s ease;
            border-radius: 0.25rem;
            margin: 0 -0.25rem;
            padding: 0.125rem 0.25rem;
        }
        .loc-inq-row > div:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        .loc-inq-row + .loc-inq-row {
            border-top: 1px dashed rgba(255, 255, 255, 0.12);
            margin-top: 3px;
            padding-top: 3px;
        }
        @media (max-width: 640px) {
            .locacoes-list {
                gap: 1.25rem;
            }
            .loc-card-header {
                padding: 0.5rem 0.75rem;
                gap: 0.5rem;
                flex-wrap: wrap;
            }
            .loc-card-body {
                padding: 0.5rem 0.75rem 0.75rem 0.75rem;
            }
        }
    </style>
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Registros de Locações</h1>
                    <p class="mt-1 text-slate-500">Visualize e gerencie as fichas de locação preenchidas pelos usuários.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Filters Card -->
                <div class="mb-8 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Edifício</label>
                                <div class="relative">
                                    <select name="edificio_id" id="edificio_id" class="form-input appearance-none pr-10">
                                        <option value="">Todos os Edifícios</option>
                                        <?php foreach ($bases as $base): ?>
                                            <option value="base_<?= $base['id'] ?>" <?= $filtro_base == $base['id'] ? 'selected' : '' ?>>
                                                Base: <?= htmlspecialchars($base['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (!empty($bases)): ?>
                                            <option disabled>──────────</option>
                                        <?php endif; ?>
                                        <?php foreach ($edificios as $ed): ?>
                                            <option value="<?= $ed['id'] ?>" <?= $filtro_edificio == $ed['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ed['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <?php renderModernCalendar('data_inicio', $data_inicio, 'Data Início'); ?>
                            </div>

                            <div class="space-y-2">
                                <?php renderModernCalendar('data_fim', $data_fim, 'Data Fim'); ?>
                            </div>

                            <div>
                                <a href="listar_locacoes.php" class="icon-btn" title="Limpar Filtros"><i class="fas fa-sync-alt" style="font-size:10px"></i></a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Script para busca automática -->
                <script>
                    // Busca automática quando os filtros são alterados
                    function autoSubmit() {
                        const form = document.querySelector('form[method="GET"]');
                        if (form) {
                            // Remove o botão de submit se existir
                            const submitBtn = form.querySelector('button[type="submit"]');
                            if (submitBtn) {
                                submitBtn.remove();
                            }
                            
                            // Adiciona evento de change nos campos
                            const inputs = form.querySelectorAll('select, input:not([type="checkbox"])');
                            inputs.forEach(input => {
                                input.addEventListener('change', function() {
                                    form.submit();
                                });
                            });
                        }
                    }
                    
                    // Inicializa quando o DOM estiver pronto
                    document.addEventListener('DOMContentLoaded', autoSubmit);
                </script>

                <!-- Bulk Action Bar (only for gerente) -->
                <?php if ($usuario_categoria === 'gerente'): ?>
                <div id="bulkActionBar" class="mb-4 hidden animate-fade-in">
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <i class="fas fa-trash-alt text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-red-800"><span id="selectedCount">0</span> locação(ões) selecionada(s)</p>
                                <p class="text-xs text-red-500">Selecione os registros que deseja excluir</p>
                            </div>
                        </div>
                        <div class="flex gap-2 ml-auto">
                            <button type="button" onclick="clearSelection()" class="btn btn-cancel btn-sm">
                                <i class="fas fa-times"></i> Limpar
                            </button>
                            <button type="button" onclick="bulkDelete()" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt"></i> Excluir Selecionadas
                            </button>
                        </div>
                    </div>
                </div>
                <form id="bulkDeleteForm" method="POST" class="hidden">
                    <input type="hidden" name="bulk_delete_locacoes" value="1">
                </form>
                <?php endif; ?>

                <!-- Locacoes List -->
                <div class="locacoes-list animate-slide-up" style="animation-delay: 0.1s;" data-copy-only>

                    <?php if (empty($locacoes)): ?>
                        <div class="loc-card">
                            <div class="loc-card-body text-center py-12 text-slate-500">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-key text-4xl text-slate-200"></i>
                                    <p>Nenhum registro de locação encontrado.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($usuario_categoria === 'gerente'): ?>
                        <div class="mb-4 flex items-center gap-3 px-1">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                            <label for="selectAll" class="text-sm text-slate-600 font-medium">Selecionar todas</label>
                        </div>
                        <?php endif; ?>
                        <?php foreach ($locacoes as $loc): ?>
                        <div class="loc-card">
                            <div class="loc-card-header">
                                <?php if ($usuario_categoria === 'gerente'): ?>
                                    <input type="checkbox" name="locacao_select[]" value="<?= $loc['id'] ?>" class="locacao-checkbox rounded border-slate-300 text-primary-600 focus:ring-primary-500 shrink-0">
                                <?php endif; ?>
                                <div class="flex items-baseline gap-2 min-w-0">
                                    <span class="loc-card-title"><?= htmlspecialchars($loc['nome_edificio']) ?></span>
                                    <span class="loc-card-apt">Apt <?= htmlspecialchars($loc['numero_apartamento']) ?></span>
                                </div>
                                <span class="loc-card-date"><?= date('d/m/Y', strtotime($loc['data_registro'] ?? $loc['data_locacao'] ?? 'now')) ?> <?= date('H:i', strtotime($loc['data_registro'] ?? $loc['data_locacao'] ?? 'now')) ?></span>
                                <?php if ($usuario_categoria === 'gerente'): ?>
                                    <form method="POST" class="loc-card-delete" onsubmit="return confirm('Tem certeza que deseja excluir esta locação?');">
                                        <input type="hidden" name="id_delete" value="<?= $loc['id'] ?>">
                                        <button type="submit" name="delete_locacao" class="icon-btn-red" title="Excluir"><i class="fas fa-trash" style="font-size:10px"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="loc-card-body">
                                <div class="loc-section">
                                    <?php if (!empty($locacaoInquilinos[$loc['id']])): ?>
                                         <?php foreach ($locacaoInquilinos[$loc['id']] as $inquilino): ?>
                                             <div class="loc-inq-row">
                                              <div style="display:grid;grid-template-columns:<?= !empty($inquilino['selfie']) ? '1fr auto auto' : '1fr auto' ?>;align-items:center;gap:4px">
                                                      <div class="font-semibold text-slate-900" style="font-size:0.75rem">
                                                          <?= htmlspecialchars($inquilino['nome']) ?>
                                                          <?php if (!empty($inquilino['selfie'])): ?>
                                                          <img class="selfie-thumb inline-block ml-2 rounded-full cursor-pointer align-middle" style="width:24px;height:24px;object-fit:cover" data-image-src="<?= htmlspecialchars($inquilino['selfie']) ?>" data-image-name="<?= htmlspecialchars($inquilino['nome']) ?>" src="<?= htmlspecialchars($inquilino['selfie']) ?>" alt="Selfie">
                                                          <?php endif; ?>
                                                      </div>
                                                      <?php if (!empty($inquilino['selfie'])): ?>
                                                      <a href="<?= htmlspecialchars($inquilino['selfie']) ?>" download="selfie-<?= preg_replace('/[^a-zA-Z0-9]/', '_', $inquilino['nome']) ?>.jpg" class="icon-btn-blue" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" title="Salvar imagem">
                                                          <i class="fas fa-download" style="font-size:10px"></i>
                                                      </a>
                                                      <?php endif; ?>
                                                      <?= btnCopiar($inquilino['nome']) ?>
                                                  </div>
                                                  <div style="display:grid;grid-template-columns:<?= !empty($inquilino['documento']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px">
                                                      <span style="font-size:0.65rem;color:#64748b">Doc: <?= htmlspecialchars($inquilino['documento'] ?: '---') ?></span>
                                                      <?php if (!empty($inquilino['documento'])): ?><?= btnCopiar($inquilino['documento']) ?><?php endif; ?>
                                                  </div>
                                                  <div style="display:grid;grid-template-columns:<?= !empty($inquilino['telefone']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px">
                                                      <span style="font-size:0.65rem;color:#64748b">Tel: <?= htmlspecialchars($inquilino['telefone'] ?: '---') ?></span>
                                                      <?php if (!empty($inquilino['telefone'])): ?><?= btnCopiar($inquilino['telefone']) ?><?php endif; ?>
                                                  </div>
                                             </div>
                                         <?php endforeach; ?>
                                     <?php else: ?>
                                         <span class="text-slate-400" style="font-size:0.7rem">Nenhum ocupante</span>
                                     <?php endif; ?>
                                </div>

                                <div class="loc-section">
                                    <?php if (!empty($locacaoVeiculos[$loc['id']])): ?>
                                         <?php foreach ($locacaoVeiculos[$loc['id']] as $veiculo): ?>
                                              <div class="loc-inq-row">
                                                  <div style="display:grid;grid-template-columns:<?= !empty($veiculo['modelo']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px">
                                                      <div class="font-semibold text-slate-900" style="font-size:0.75rem"><?= htmlspecialchars($veiculo['modelo'] ?: 'N/I') ?></div>
                                                      <?php if (!empty($veiculo['modelo'])): ?><?= btnCopiar($veiculo['modelo']) ?><?php endif; ?>
                                                  </div>
                                                  <div style="display:grid;grid-template-columns:<?= !empty($veiculo['cor']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px">
                                                      <span style="font-size:0.65rem;color:#64748b">Cor: <?= htmlspecialchars($veiculo['cor'] ?: '---') ?></span>
                                                      <?php if (!empty($veiculo['cor'])): ?><?= btnCopiar($veiculo['cor']) ?><?php endif; ?>
                                                  </div>
                                                  <div style="display:grid;grid-template-columns:<?= !empty($veiculo['placa']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px">
                                                      <span style="font-size:0.65rem;color:#64748b">Placa: <?= htmlspecialchars($veiculo['placa'] ?: '---') ?></span>
                                                      <?php if (!empty($veiculo['placa'])): ?><?= btnCopiar($veiculo['placa']) ?><?php endif; ?>
                                                  </div>
                                              </div>
                                         <?php endforeach; ?>
                                     <?php else: ?>
                                         <span class="text-slate-400" style="font-size:0.7rem">Nenhum veículo</span>
                                     <?php endif; ?>
                                </div>

                                <div class="loc-section" style="font-size:0.7rem">
                                    <div style="display:grid;grid-template-columns:<?= !empty($loc['data_entrada']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px">
                                        <span class="font-semibold text-slate-600">Entrada: <?= $loc['data_entrada'] ? date('d/m/Y', strtotime($loc['data_entrada'])) : '---' ?></span>
                                        <?php if (!empty($loc['data_entrada'])): ?><?= btnCopiar(date('d/m/Y', strtotime($loc['data_entrada']))) ?><?php endif; ?>
                                    </div>
                                    <div style="display:grid;grid-template-columns:<?= !empty($loc['data_saida']) ? '1fr auto' : '1fr' ?>;align-items:center;gap:4px;margin-top:2px">
                                        <span class="font-semibold text-slate-600">Saída: <?= $loc['data_saida'] ? date('d/m/Y', strtotime($loc['data_saida'])) : '---' ?></span>
                                        <?php if (!empty($loc['data_saida'])): ?><?= btnCopiar(date('d/m/Y', strtotime($loc['data_saida']))) ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="photoModal" class="photo-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="photoModalTitle">
        <div class="photo-modal-card">
            <div id="photoModalContent"></div>
            <div class="photo-modal-footer">
                <div>
                    <div id="photoModalTitle" class="modal-title">Foto</div>
                    <div id="photoModalSubtitle" class="text-sm text-slate-500">Clique no botão para salvar.</div>
                </div>
                <div class="modal-actions">
                    <a id="photoModalSave" class="icon-btn-blue" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" title="Salvar" download="selfie.jpg" href="#">
                        <i class="fas fa-download" style="font-size:10px"></i>
                    </a>
                    <button type="button" id="photoModalClose" class="btn-secondary inline-flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const photoModal = document.getElementById('photoModal');
            const photoModalContent = document.getElementById('photoModalContent');
            const photoModalSave = document.getElementById('photoModalSave');
            const photoModalClose = document.getElementById('photoModalClose');
            const photoModalTitle = document.getElementById('photoModalTitle');
            const photoModalSubtitle = document.getElementById('photoModalSubtitle');

            function openPhotoModal(src, name) {
                photoModalContent.innerHTML = `<img src="${src}" alt="Selfie ${name}" />`;
                photoModalSave.href = src;
                photoModalSave.download = `selfie-${name.replace(/\s+/g, '_').toLowerCase()}.jpg`;
                photoModalTitle.textContent = `Selfie de ${name}`;
                photoModalSubtitle.textContent = 'Clique em salvar para baixar a imagem.';
                photoModal.classList.add('active');
            }

            function closePhotoModal() {
                photoModal.classList.remove('active');
            }

            document.querySelectorAll('.selfie-thumb').forEach(img => {
                img.addEventListener('click', function() {
                    openPhotoModal(this.dataset.imageSrc, this.dataset.imageName || 'visualização');
                });
            });

            photoModalClose.addEventListener('click', closePhotoModal);
            photoModal.addEventListener('click', function(event) {
                if (event.target === photoModal) {
                    closePhotoModal();
                }
            });
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && photoModal.classList.contains('active')) {
                    closePhotoModal();
                }
            });

            // Bulk delete functionality
            const selectAll = document.getElementById('selectAll');
            const bulkActionBar = document.getElementById('bulkActionBar');
            const selectedCountEl = document.getElementById('selectedCount');
            const checkboxes = document.querySelectorAll('.locacao-checkbox');

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateBulkBar();
                });

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        const total = checkboxes.length;
                        const checked = document.querySelectorAll('.locacao-checkbox:checked').length;
                        selectAll.checked = total > 0 && checked === total;
                        selectAll.indeterminate = checked > 0 && checked < total;
                        updateBulkBar();
                    });
                });
            }

            function updateBulkBar() {
                const checked = document.querySelectorAll('.locacao-checkbox:checked').length;
                if (bulkActionBar) {
                    bulkActionBar.classList.toggle('hidden', checked === 0);
                    if (selectedCountEl) selectedCountEl.textContent = checked;
                }
            }
        });

        function clearSelection() {
            document.querySelectorAll('.locacao-checkbox').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAll');
            if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
            const bar = document.getElementById('bulkActionBar');
            if (bar) bar.classList.add('hidden');
        }

        function bulkDelete() {
            const checked = document.querySelectorAll('.locacao-checkbox:checked');
            if (checked.length === 0) return;

            if (!confirm('Tem certeza que deseja excluir ' + checked.length + ' locação(ões)? Esta ação não pode ser desfeita.')) return;

            const form = document.getElementById('bulkDeleteForm');
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'locacao_ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            form.submit();
        }

        function copiarTexto(texto, btn) {
            const origHTML = btn.innerHTML;
            navigator.clipboard.writeText(texto).then(() => {
                btn.innerHTML = '<i class="fas fa-check" style="font-size:10px"></i>';
                setTimeout(() => {
                    btn.innerHTML = origHTML;
                }, 1500);
            }).catch(() => {
                btn.innerHTML = '<i class="fas fa-times" style="font-size:10px"></i>';
                setTimeout(() => {
                    btn.innerHTML = origHTML;
                }, 1500);
            });
        }
    </script>
</body>
</html>
