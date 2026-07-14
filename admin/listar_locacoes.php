<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
$mensagem = '';
$mensagem_tipo = '';

// Process deletion (Manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_locacao'])) {
    if ($usuario_categoria === 'gerente') {
        $id_delete = filter_input(INPUT_POST, 'id_delete', FILTER_VALIDATE_INT);
        if ($id_delete) {
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
    <style>
        .selfie-thumb {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .selfie-thumb:hover {
            transform: scale(1.12);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
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
            background: #ffffff;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 32px 90px rgba(15,23,42,0.25);
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
            color: #0f172a;
            font-weight: 600;
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
                                <a href="listar_locacoes.php" class="btn-secondary" title="Limpar Filtros">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
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
                            const inputs = form.querySelectorAll('select, input');
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

                <!-- Table Container -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Data de Registro</th>
                                    <th>Localização</th>
                                    <th>Ocupantes</th>
                                    <th>Veículos</th>
                                    <th>Período</th>
                                    <?php if ($usuario_categoria === 'gerente'): ?>
                                        <th class="text-right">Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($locacoes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-slate-500">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fas fa-key text-4xl text-slate-200"></i>
                                                <p>Nenhum registro de locação encontrado.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($locacoes as $loc): ?>
                                        <tr class="group">
                                            <td class="whitespace-nowrap">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-slate-900"><?= date('d/m/Y', strtotime($loc['data_registro'] ?? $loc['data_locacao'] ?? 'now')) ?></span>
                                                    <span class="text-xs text-slate-500"><?= date('H:i', strtotime($loc['data_registro'] ?? $loc['data_locacao'] ?? 'now')) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-primary-700"><?= htmlspecialchars($loc['nome_edificio']) ?></span>
                                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Apt <?= htmlspecialchars($loc['numero_apartamento']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="space-y-3">
                                                    <?php if (!empty($locacaoInquilinos[$loc['id']])): ?>
                                                        <?php foreach ($locacaoInquilinos[$loc['id']] as $inquilino): ?>
                                                            <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
                                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                                    <div>
                                                                        <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($inquilino['nome']) ?></div>
                                                                        <div class="text-xs text-slate-500">Documento: <?= htmlspecialchars($inquilino['documento'] ?: '---') ?></div>
                                                                        <div class="text-xs text-slate-500">Telefone: <?= htmlspecialchars($inquilino['telefone'] ?: '---') ?></div>
                                                                    </div>
                                                                    <?php if (!empty($inquilino['selfie'])): ?>
                                                                        <img src="<?= htmlspecialchars($inquilino['selfie']) ?>" alt="Selfie <?= htmlspecialchars($inquilino['nome']) ?>" class="h-16 w-16 rounded-2xl object-cover border border-slate-200 shadow-sm selfie-thumb" data-image-src="<?= htmlspecialchars($inquilino['selfie']) ?>" data-image-name="<?= htmlspecialchars($inquilino['nome']) ?>" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-slate-400">Nenhum ocupante cadastrado.</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="space-y-3">
                                                    <?php if (!empty($locacaoVeiculos[$loc['id']])): ?>
                                                        <?php foreach ($locacaoVeiculos[$loc['id']] as $veiculo): ?>
                                                            <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
                                                                <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($veiculo['modelo'] ?: 'Modelo não informado') ?></div>
                                                                <div class="text-xs text-slate-500">Cor: <?= htmlspecialchars($veiculo['cor'] ?: '---') ?></div>
                                                                <div class="text-xs text-slate-500">Placa: <?= htmlspecialchars($veiculo['placa'] ?: '---') ?></div>
                                                                <?php if (!empty($veiculo['acesso_garagem'])): ?>
                                                                    <div class="text-xs text-primary-700 font-medium">Acesso garagem: <?= htmlspecialchars($veiculo['acesso_garagem']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-slate-400">Nenhum veículo cadastrado.</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold"><?= $loc['data_entrada'] ? date('d/m/Y', strtotime($loc['data_entrada'])) : '---' ?></span>
                                                    <i class="fas fa-arrow-right text-[10px] text-slate-300"></i>
                                                    <span class="px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold"><?= $loc['data_saida'] ? date('d/m/Y', strtotime($loc['data_saida'])) : '---' ?></span>
                                                </div>
                                            </td>
                                            <?php if ($usuario_categoria === 'gerente'): ?>
                                                <td class="text-right">
                                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta locação?');">
                                                            <input type="hidden" name="id_delete" value="<?= $loc['id'] ?>">
                                                            <button type="submit" name="delete_locacao" class="h-8 w-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 transition-all shadow-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                    <a id="photoModalSave" class="btn-primary inline-flex items-center gap-2" download="selfie.jpg" href="#">
                        <i class="fas fa-download"></i>
                        Salvar imagem
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
        });
    </script>
</body>
</html>
