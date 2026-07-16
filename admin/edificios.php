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
        $query = "SELECT e.id, e.nome AS nome_edificio, e.endereco, e.sindico_nome, e.sindico_contato, e.administradora_id, 
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
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
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
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Edifícios</h1>
                        <p class="mt-1 text-slate-500">Gerencie edifícios, bases, administradoras e síndicos.</p>
                    </div>
                    <?php if ($pode_editar): ?>
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
                    <?php 
                        $tabs_config = [
                            'edificios' => 'Edifícios',
                            'bases' => 'Bases',
                            'administradoras' => 'Administradoras',
                            'sindicos' => 'Síndicos'
                        ];
                        foreach ($tabs_config as $key => $label):
                    ?>
                        <a href="?tab=<?= $key ?>" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === $key ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Filters & Search -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <input type="hidden" name="tab" value="<?= $tab ?>">
                            
                            <?php if ($tab === 'edificios'): ?>
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
                            <?php endif; ?>

                            <div class="space-y-2">
                                <label class="form-label">Buscar</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="text" id="searchInput" name="search" class="form-input pl-11" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>

                            <a href="?tab=<?= $tab ?>" class="btn-secondary" title="Limpar Filtros">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <?php if ($tab === 'edificios'): ?>
                        <?php if (empty($data)): ?>
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
                                                    <h2 class="mt-2 text-2xl font-bold text-slate-900"><?= htmlspecialchars($item['nome_edificio']) ?></h2>
                                                    <p class="mt-2 text-sm text-slate-500 max-w-xl"><?= htmlspecialchars($item['endereco'] ?: 'Endereço não informado') ?></p>
                                                </div>
                                                <div class="space-y-1 text-right">
                                                    <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Base</span>
                                                    <p class="text-sm font-semibold text-slate-900"><?= render_card_value($item['nome_base']) ?></p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Administradora</p>
                                                    <p class="text-sm font-semibold text-slate-900"><?= render_card_value($item['nome_administradora']) ?></p>
                                                </div>
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Síndico</p>
                                                    <p class="mt-2 text-sm font-semibold text-slate-900"><?= render_card_value($item['sindico_nome']) ?></p>
                                                    <p class="text-sm text-slate-500 mt-1"><?= render_card_value($item['sindico_contato']) ?></p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">DVR</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">IP: <?= render_card_value($item['dvr_ip']) ?></p>
                                                    <p class="text-sm text-slate-500">Cloud: <?= render_card_value($item['dvr_cloud']) ?></p>
                                                    <p class="text-sm text-slate-500">Modelo: <?= render_card_value($item['dvr_modelo']) ?></p>
                                                    <p class="text-sm text-slate-500">Porta TCP: <?= render_card_value($item['dvr_porta_tcp']) ?></p>
                                                    <p class="text-sm text-slate-500">Status: <?= render_card_value($item['dvr_status']) ?></p>
                                                </div>
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Ramais</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">Último ramal: <?= render_card_value($item['ramal_numero']) ?></p>
                                                    <p class="text-sm text-slate-500">Categoria: <?= render_card_value($item['ramal_categoria']) ?></p>
                                                    <p class="text-sm text-slate-500">Status: <?= render_card_value($item['ramal_status']) ?></p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Facial</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">Marca: <?= render_card_value($item['facial_marca']) ?></p>
                                                    <p class="text-sm text-slate-500">Acessos: <?= render_card_accessos($item['facial_acessos']) ?></p>
                                                    <p class="text-sm text-slate-500">Status: <?= render_card_value($item['facial_status']) ?></p>
                                                    <p class="text-sm text-slate-500">Obs: <?= render_card_value($item['facial_observacao']) ?></p>
                                                </div>
                                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left">
                                                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">ATA</p>
                                                    <p class="mt-3 text-sm text-slate-900 font-semibold">Itens: <?= render_card_value($item['ata_itens']) ?></p>
                                                    <p class="text-sm text-slate-500">Status: <?= render_card_value($item['ata_status']) ?></p>
                                                    <p class="text-sm text-slate-500">Obs: <?= render_card_value($item['ata_observacao']) ?></p>
                                                </div>
                                            </div>

                                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Rádio / Fibra</p>
                                                <div class="grid gap-2 sm:grid-cols-2 mt-3 text-sm text-slate-500">
                                                    <div>IP: <?= render_card_value($item['radio_ip']) ?></div>
                                                    <div>Local: <?= render_card_value($item['radio_local']) ?></div>
                                                    <div>Modo: <?= render_card_value($item['radio_modo']) ?></div>
                                                    <div>Marca: <?= render_card_value($item['radio_marca']) ?></div>
                                                    <div>Modelo: <?= render_card_value($item['radio_modelo']) ?></div>
                                                    <div>Login: <?= render_card_value($item['radio_login']) ?></div>
                                                    <div>Status: <?= render_card_value($item['radio_status']) ?></div>
                                                    <div>Obs: <?= render_card_value($item['radio_observacao']) ?></div>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-200">
                                                <div class="text-sm text-slate-500">Base responsável: <span class="font-semibold text-slate-900"><?= render_card_value($item['nome_base']) ?></span></div>
                                                <div class="flex gap-2">
                                                    <a href="editar_edificio.php?id=<?= $item['id'] ?>" class="btn-secondary text-xs">Editar</a>
                                                    <?php if ($pode_editar): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="id_delete" value="<?= $item['id'] ?>">
                                                            <input type="hidden" name="tipo_delete" value="edificio">
                                                            <input type="hidden" name="current_tab" value="<?= $tab ?>">
                                                            <button type="submit" name="delete_item" class="btn-danger text-xs">Excluir</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <?php if ($tab === 'bases'): ?>
                                            <th>Nome da Base</th>
                                            <th>Telefone</th>
                                            <th>Status</th>
                                            <th>Total Edifícios</th>
                                        <?php elseif ($tab === 'administradoras' || $tab === 'sindicos'): ?>
                                            <th>Nome</th>
                                            <th>Contato</th>
                                            <th>Edifícios Vinculados</th>
                                        <?php endif; ?>
                                        <th class="text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-12 text-slate-500 italic">Nenhum registro encontrado.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data as $item): ?>
                                            <tr class="group">
                                                <?php if ($tab === 'bases'): ?>
                                                    <td class="font-bold text-slate-900"><?= htmlspecialchars($item['nome']) ?></td>
                                                    <td class="text-sm text-slate-600"><?= htmlspecialchars($item['telefone'] ?: 'N/A') ?></td>
                                                    <td>
                                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $item['status'] === 'inativo' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                                            <?= $item['status'] === 'inativo' ? 'Desativado' : 'Ativo' ?>
                                                        </span>
                                                    </td>
                                                    <td><span class="badge-primary"><?= $item['total_edificios'] ?> edifícios</span></td>
                                                <?php else: ?>
                                                    <td class="font-bold text-slate-900"><?= htmlspecialchars($item['nome']) ?></td>
                                                    <td>
                                                        <div class="flex flex-col">
                                                            <span class="text-sm text-slate-700"><?= htmlspecialchars($item['telefone'] ?: '') ?></span>
                                                            <span class="text-xs text-slate-400"><?= htmlspecialchars($item['email'] ?: '') ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="max-w-xs">
                                                        <span class="text-xs text-slate-500 line-clamp-2"><?= htmlspecialchars($item['edificios_administrados'] ?: 'Nenhum') ?></span>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="text-right">
                                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <?php 
                                                            $edit_url = [
                                                                'bases' => "editar_base.php?id=" . $item['id'],
                                                                'administradoras' => "editar_administradora.php?id=" . $item['id'],
                                                                'sindicos' => "editar_sindico.php?id=" . $item['id']
                                                            ][$tab];
                                                            $tipo_delete = [
                                                                'bases' => 'base',
                                                                'administradoras' => 'administradora',
                                                                'sindicos' => 'sindico'
                                                            ][$tab];
                                                        ?>
                                                        <a href="<?= $edit_url ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($pode_editar): ?>
                                                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este item?');" class="inline">
                                                                <input type="hidden" name="id_delete" value="<?= $item['id'] ?>">
                                                                <input type="hidden" name="tipo_delete" value="<?= $tipo_delete ?>">
                                                                <input type="hidden" name="current_tab" value="<?= $tab ?>">
                                                                <button type="submit" name="delete_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all" title="Excluir">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($tab === 'bases' && $pode_editar): ?>
                                                            <?php
                                                                $isActive = $item['status'] === 'ativo';
                                                                $buttonTitle = $isActive ? 'Desativar' : 'Ativar';
                                                                $buttonClass = $isActive ? 'bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white';
                                                            ?>
                                                            <form method="POST" class="inline">
                                                                <input type="hidden" name="id_deactivate" value="<?= $item['id'] ?>">
                                                                <input type="hidden" name="current_tab" value="<?= $tab ?>">
                                                                <button type="submit" name="deactivate_item" class="h-8 w-8 flex items-center justify-center rounded-lg <?= $buttonClass ?> transition-all" title="<?= $buttonTitle ?>">
                                                                    <i class="fas fa-power-off"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
