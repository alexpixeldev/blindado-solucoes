<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if ($usuario_categoria === 'colaborador') { header('Location: index.php'); exit(); }
$pode_editar = in_array($usuario_categoria, ['supervisor', 'gerente']);

if ($pode_editar && isset($_POST['delete_item'])) {
    $id = intval($_POST['id_delete']);
    $tipo = $_POST['tipo_delete'];
    
    switch ($tipo) {
        case 'edificio':
            $stmt = $conn->prepare('DELETE FROM edificios WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['mensagem'] = 'Edifício excluído com sucesso!';
            $_SESSION['mensagem_tipo'] = 'success';
            break;
        case 'base':
            $stmt = $conn->prepare('DELETE FROM bases WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['mensagem'] = 'Base excluída com sucesso!';
            $_SESSION['mensagem_tipo'] = 'success';
            break;
        case 'administradora':
            $stmt = $conn->prepare('DELETE FROM administradoras WHERE id = ?');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['mensagem'] = "Administradora excluída com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            break;
        case 'sindico':
            $stmt = $conn->prepare("DELETE FROM sindicos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['mensagem'] = "Síndico excluído com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            break;
    }
    header("Location: edificios.php?tab=" . $_POST['current_tab']);
    exit();
}

if ($pode_editar && isset($_POST['deactivate_item'])) {
    $id = intval($_POST['id_deactivate']);

    $result = $conn->query("SHOW COLUMNS FROM bases LIKE 'status'");
    if ($result && $result->num_rows === 0) {
        $alter = $conn->query("ALTER TABLE bases ADD COLUMN status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo'");
        if (!$alter) {
            $_SESSION['mensagem'] = 'Erro ao criar coluna status: ' . $conn->error;
            $_SESSION['mensagem_tipo'] = 'error';
            header("Location: edificios.php?tab=" . $_POST['current_tab']);
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE bases SET status = CASE WHEN status = 'ativo' THEN 'inativo' ELSE 'ativo' END WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt_status = $conn->prepare("SELECT status FROM bases WHERE id = ?");
            if ($stmt_status) {
                $stmt_status->bind_param('i', $id);
                $stmt_status->execute();
                $result_status = $stmt_status->get_result()->fetch_assoc();
                $currentStatus = $result_status['status'] ?? 'inativo';
                $stmt_status->close();
            } else {
                $currentStatus = 'inativo';
            }
            if ($currentStatus === 'ativo') {
                $_SESSION['mensagem'] = 'Base ativada com sucesso!';
            } else {
                $_SESSION['mensagem'] = 'Base desativada com sucesso!';
            }
            $_SESSION['mensagem_tipo'] = 'success';
        } else {
            $_SESSION['mensagem'] = 'Erro ao alternar status da base: ' . $stmt->error;
            $_SESSION['mensagem_tipo'] = 'error';
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = 'Erro ao preparar alteração de status: ' . $conn->error;
        $_SESSION['mensagem_tipo'] = 'error';
    }

    header("Location: edificios.php?tab=" . $_POST['current_tab']);
    exit();
}

function render_card_value($value) {
    if ($value === null || $value === '') {
        return '<span class="text-red-500">Informação não registrada</span>';
    }
    return htmlspecialchars($value);
}

function render_card_accessos($value) {
    if ($value === null || $value === '') {
        return '<span class="text-red-500">Informação não registrada</span>';
    }
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $parts = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                $parts[] = htmlspecialchars((string)$entry);
                continue;
            }
            $sub = [];
            if (isset($entry['ip'])) {
                $sub[] = 'IP: ' . htmlspecialchars($entry['ip']);
            }
            if (isset($entry['obs'])) {
                $sub[] = 'Obs: ' . htmlspecialchars($entry['obs']);
            }
            if ($sub) {
                $parts[] = implode(' | ', $sub);
            }
        }
        return $parts ? implode('; ', $parts) : htmlspecialchars($value);
    }
    return htmlspecialchars($value);
}

$tab = $_GET['tab'] ?? 'edificios';
$filtro_base = $_GET['base'] ?? '';
$search = $_GET['search'] ?? '';

// Fetch data based on current tab
$data = [];
$where_clauses = [];

switch ($tab) {
    case 'edificios':
        $query = "SELECT e.id, e.nome AS nome_edificio, e.endereco, e.localizacao, e.sindico_nome, e.sindico_contato, e.administradora_id,
                         e.elevador_empresa, e.elevador_contato,
                         b.nome AS nome_base, b.status AS status, a.nome AS nome_administradora,
                         (SELECT cf.marca_equipamento FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_marca,
                         (SELECT cf.acessos FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_acessos,
                         (SELECT cf.status FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_status,
                         (SELECT cf.observacao FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_observacao,
                         (SELECT ca.itens_ata FROM controle_ata ca WHERE ca.edificio_id = e.id ORDER BY ca.id DESC LIMIT 1) AS ata_itens,
                         (SELECT ca.status FROM controle_ata ca WHERE ca.edificio_id = e.id ORDER BY ca.id DESC LIMIT 1) AS ata_status,
                         (SELECT ca.observacao FROM controle_ata ca WHERE ca.edificio_id = e.id ORDER BY ca.id DESC LIMIT 1) AS ata_observacao,
                         (SELECT crf.ip FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_ip,
                         (SELECT crf.local_detalhe FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_local,
                         (SELECT crf.modo FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_modo,
                         (SELECT crf.marca FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_marca,
                         (SELECT crf.modelo FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_modelo,
                         (SELECT crf.login FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_login,
                         (SELECT crf.status FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_status,
                         (SELECT crf.observacao FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_observacao,
                         (SELECT cd.ip_dominio FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_ip,
                         (SELECT cd.cloud FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_cloud,
                         (SELECT cd.porta_tcp FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_porta_tcp,
                         (SELECT cd.porta_http FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_porta_http,
                         (SELECT cd.login FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_login,
                         (SELECT cd.modelo FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_modelo,
                         (SELECT cd.status FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_status,
                         (SELECT cd.observacao FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_observacao,
                         (SELECT cr.numero_ramal FROM controle_ramais cr WHERE cr.edificio_id = e.id ORDER BY cr.id DESC LIMIT 1) AS ramal_numero,
                         (SELECT cr.status FROM controle_ramais cr WHERE cr.edificio_id = e.id ORDER BY cr.id DESC LIMIT 1) AS ramal_status,
                         (SELECT cat.nome FROM controle_ramais cr LEFT JOIN categorias_ramais cat ON cr.categoria_id = cat.id WHERE cr.edificio_id = e.id ORDER BY cr.id DESC LIMIT 1) AS ramal_categoria
                  FROM edificios e
                  JOIN bases b ON e.base_id = b.id
                  LEFT JOIN administradoras a ON e.administradora_id = a.id";

        if ($filtro_base) $where_clauses[] = "e.base_id = " . intval($filtro_base);
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(e.nome LIKE '%$s%' OR b.nome LIKE '%$s%' OR e.endereco LIKE '%$s%' OR e.sindico_nome LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " ORDER BY b.nome, e.nome";

        $result = $conn->query($query);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        break;

    case 'faciais_locacao':
        $query = "SELECT e.id, e.nome, e.requer_selfie, b.nome AS nome_base
                  FROM edificios e
                  JOIN bases b ON e.base_id = b.id
                  ORDER BY b.nome, e.nome";
        $result = $conn->query($query);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        break;
        
    case 'bases':
        $query = "SELECT b.id, b.nome, b.telefone, b.status, COUNT(e.id) as total_edificios
                  FROM bases b
                  LEFT JOIN edificios e ON b.id = e.base_id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(b.nome LIKE '%$s%' OR b.telefone LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY b.id, b.nome, b.telefone, b.status ORDER BY b.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'administradoras':
        $query = "SELECT a.*, 
                         GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') AS edificios_administrados,
                         COUNT(e.id) AS total_edificios
                  FROM administradoras a 
                  LEFT JOIN edificios e ON a.id = e.administradora_id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(a.nome LIKE '%$s%' OR a.telefone LIKE '%$s%' OR a.email LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY a.id, a.nome, a.telefone, a.email, a.created_at ORDER BY a.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'sindicos':
        $query = "SELECT s.*, 
                         GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') AS edificios_administrados,
                         COUNT(e.id) AS total_edificios
                  FROM sindicos s 
                  LEFT JOIN edificios e ON e.sindico_id = s.id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(s.nome LIKE '%$s%' OR s.telefone LIKE '%$s%' OR s.email LIKE '%$s%')";
        }
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY s.id, s.nome, s.telefone, s.email, s.created_at ORDER BY s.nome";
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        break;
}

$bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? '';
$mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Edifícios | Blindado Soluções</title>
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

        <div class="flex flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="flex-1 p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <?php if ($tab === 'faciais_locacao'): ?>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Envio de faciais</h1>
                            <p class="mt-1 text-slate-500">Gerencie quais edifícios podem receber fotos de faciais pela ficha de locação.</p>
                        <?php else: ?>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Edifícios</h1>
                            <p class="mt-1 text-slate-500">Gerencie edifícios, bases, administradoras e síndicos.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($pode_editar && $tab !== 'faciais_locacao'): ?>
                        <?php
                            $add_links = [
                                'edificios' => ['url' => 'cadastrar_edificio.php', 'label' => 'Novo Edifício'],
                                'bases' => ['url' => 'cadastrar_base.php', 'label' => 'Nova Base'],
                                'administradoras' => ['url' => 'cadastrar_administradora.php', 'label' => 'Nova Administradora'],
                                'sindicos' => ['url' => 'cadastrar_sindico.php', 'label' => 'Novo Síndico']
                            ];
                            $current_add = $add_links[$tab] ?? $add_links['edificios'];
                        ?>
                        <a href="<?= $current_add['url'] ?>" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span><?= $current_add['label'] ?></span>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Tabs Navigation -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <a href="?tab=edificios" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === 'edificios' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Edifícios
                    </a>
                    <a href="?tab=faciais_locacao" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === 'faciais_locacao' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Faciais Locação
                    </a>
                </div>

                <!-- Filters & Search -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <input type="hidden" name="tab" value="edificios">

                            <div class="space-y-2">
                                <label class="form-label">Filtrar por Base</label>
                                <div class="relative">
                                    <select name="base" class="form-input appearance-none pr-10" onchange="this.form.submit()">
                                        <option value="">Todas as Bases</option>
                                        <?php foreach ($bases as $b): ?>
                                            <option value="<?= $b['id'] ?>" <?= $filtro_base == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="form-label">Buscar</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="text" id="searchInput" name="search" class="form-input pl-11" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>

                            <a href="?tab=edificios" class="btn-secondary" title="Limpar Filtros">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <?php if ($tab === 'faciais_locacao'): ?>
                        <div class="admin-card">
                            <h2 class="text-lg font-bold text-slate-900 mb-4">Configuração de Faciais por Edifício</h2>
                            <?php if (empty($data)): ?>
                                <div class="text-center py-12 text-slate-500 italic">
                                    Nenhum edifício encontrado.
                                </div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($data as $item): ?>
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors">
                                            <div>
                                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($item['nome']) ?></p>
                                                <p class="text-sm text-slate-500"><?= htmlspecialchars($item['nome_base']) ?></p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" class="sr-only peer" onchange="toggleSelfie(<?= $item['id'] ?>, this)" <?= $item['requer_selfie'] ? 'checked' : '' ?>>
                                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                                <span class="ml-3 text-sm font-medium text-slate-900"><?= $item['requer_selfie'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif (empty($data)): ?>
                            <div class="admin-card text-center py-12 text-slate-500 italic">
                                Nenhum edifício encontrado.
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4 lg:grid-cols-2">
                                <?php foreach ($data as $item): ?>
                                    <article class="admin-card border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-lg">
                                        <div class="flex flex-col gap-6">
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                                <div>
                                                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Edifício</p>
                                                    <h2 class="mt-2 text-2xl font-bold text-slate-900"><?= htmlspecialchars($item['nome_edificio'] ?? $item['nome'] ?? 'Nome não informado') ?></h2>
                                                    <p class="mt-2 text-sm text-slate-500 max-w-xl"><?= htmlspecialchars($item['endereco'] ?: 'Endereço não informado') ?></p>
                                                    <?php if (!empty($item['localizacao']) && $item['localizacao'] !== 'NULL'): ?>
                                                        <a href="<?= htmlspecialchars($item['localizacao']) ?>" target="_blank" class="mt-1 inline-flex items-center text-xs text-primary-600 hover:text-primary-700">
                                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                                            Ver no Google Maps
                                                        </a>
                                                    <?php else: ?>
                                                        <p class="mt-1 text-xs text-slate-400 italic">Localização não informada</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="space-y-1 text-right">
                                                    <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Base</span>
                                                    <p class="text-sm font-semibold text-slate-900"><?= render_card_value($item['nome_base'] ?? 'Base não informada') ?></p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Administradora</p>
                                                    <p class="text-sm font-semibold text-slate-900"><?= render_card_value($item['nome_administradora'] ?? 'Administradora não informada') ?></p>
                                                </div>
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Síndico</p>
                                                    <p class="mt-2 text-sm font-semibold text-slate-900"><?= render_card_value($item['sindico_nome'] ?? 'Síndico não informado') ?></p>
                                                    <p class="text-sm text-slate-500 mt-1"><?= render_card_value($item['sindico_contato'] ?? '') ?></p>
                                                </div>
                                            </div>

                                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Elevador</p>
                                                <div class="grid gap-2 sm:grid-cols-2 mt-3 text-sm text-slate-500">
                                                    <div>Empresa: <?= render_card_value($item['elevador_empresa'] ?? 'Não informada') ?></div>
                                                    <div>Contato: <?= render_card_value($item['elevador_contato'] ?? 'Não informado') ?></div>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">DVR</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">IP: <?= render_card_value($item['dvr_ip'] ?? 'Não informado') ?></p>
                                                    <p class="text-sm text-slate-500">Cloud: <?= render_card_value($item['dvr_cloud'] ?? 'Não informado') ?></p>
                                                    <p class="text-sm text-slate-500">Modelo: <?= render_card_value($item['dvr_modelo'] ?? 'Não informado') ?></p>
                                                    <p class="text-sm text-slate-500">Porta TCP: <?= render_card_value($item['dvr_porta_tcp'] ?? 'Não informado') ?></p>
                                                </div>
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Ramais</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">Último ramal: <?= render_card_value($item['ramal_numero'] ?? 'Não informado') ?></p>
                                                    <p class="text-sm text-slate-500">Categoria: <?= render_card_value($item['ramal_categoria'] ?? 'Não informado') ?></p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Facial</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">Marca: <?= render_card_value($item['facial_marca'] ?? 'Não informado') ?></p>
                                                    <p class="text-sm text-slate-500">Acessos: <?= render_card_accessos($item['facial_acessos']) ?></p>
                                                    <p class="text-sm text-slate-500">Obs: <?= render_card_value($item['facial_observacao'] ?? 'Não informado') ?></p>
                                                </div>
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">ATA</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">Itens: <?= render_card_value($item['ata_itens'] ?? 'Não informado') ?></p>
                                                    <p class="text-sm text-slate-500">Obs: <?= render_card_value($item['ata_observacao'] ?? 'Não informado') ?></p>
                                                </div>
                                            </div>

                                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Rádio / Fibra</p>
                                                <div class="grid gap-2 sm:grid-cols-2 mt-3 text-sm text-slate-500">
                                                    <div>IP: <?= render_card_value($item['radio_ip'] ?? 'Não informado') ?></div>
                                                    <div>Local: <?= render_card_value($item['radio_local'] ?? 'Não informado') ?></div>
                                                    <div>Modo: <?= render_card_value($item['radio_modo'] ?? 'Não informado') ?></div>
                                                    <div>Marca: <?= render_card_value($item['radio_marca'] ?? 'Não informado') ?></div>
                                                    <div>Modelo: <?= render_card_value($item['radio_modelo'] ?? 'Não informado') ?></div>
                                                    <div>Login: <?= render_card_value($item['radio_login'] ?? 'Não informado') ?></div>
                                                    <div>Obs: <?= render_card_value($item['radio_observacao'] ?? 'Não informado') ?></div>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-200">
                                                <div class="text-sm text-slate-500">Base responsável: <span class="font-semibold text-slate-900"><?= render_card_value($item['nome_base'] ?? 'Base não informada') ?></span></div>
                                                <div class="flex gap-2">
                                                    <a href="editar_edificio.php?id=<?= $item['id'] ?>" class="px-4 py-2 text-xs font-bold text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">Editar</a>
                                                    <?php if ($pode_editar): ?>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este edifício?');">
                                                            <input type="hidden" name="id_delete" value="<?= $item['id'] ?>">
                                                            <input type="hidden" name="tipo_delete" value="edificio">
                                                            <input type="hidden" name="current_tab" value="<?= $tab ?>">
                                                            <button type="submit" name="delete_item" class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Excluir</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                </div>
            </main>
            
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    
    <script>
        function toggleSelfie(edificioId, checkbox) {
            const newValue = checkbox.checked ? 1 : 0;
            const statusText = checkbox.checked ? 'Habilitado' : 'Desabilitado';
            
            fetch('toggle_selfie.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'edificio_id=' + edificioId + '&valor=' + newValue
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    checkbox.nextElementSibling.nextElementSibling.textContent = statusText;
                } else {
                    alert('Erro ao atualizar: ' + data.message);
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar configuração');
                checkbox.checked = !checkbox.checked;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const baseSelect = document.querySelector('select[name="base"]');
            let searchTimeout;

            function performSearch() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const form = document.querySelector('form');
                    if (form) {
                        const formData = new FormData(form);
                        const params = new URLSearchParams();
                        
                        for (let [key, value] of formData.entries()) {
                            if (value) params.append(key, value);
                        }
                        
                        const currentTab = '<?= $tab ?>';
                        params.set('tab', currentTab);
                        
                        window.location.href = '?' + params.toString();
                    }
                }, 500);
            }
            
            if (searchInput) {
                searchInput.addEventListener('input', performSearch);
            }
            
            if (baseSelect) {
                baseSelect.addEventListener('change', performSearch);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
