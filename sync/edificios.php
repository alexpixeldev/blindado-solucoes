<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'localizacao_helper.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if ($usuario_categoria === 'colaborador') { header('Location: index.php'); exit(); }
$pode_editar = in_array($usuario_categoria, ['supervisor', 'gerente']);

$usuario_base_id = null;
if (in_array($usuario_categoria, ['operador', 'supervisor'])) {
    $row_b = $conn->query("SELECT base_id FROM usuarios WHERE id = " . intval($_SESSION['usuario_id'] ?? 0))->fetch_assoc();
    $usuario_base_id = $row_b['base_id'] ?? null;
}

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

if ($pode_editar && isset($_POST['create_base'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $coords = extrair_coordenadas_google_maps($localizacao);
    $latitude = $coords['latitude'] ?? null;
    $longitude = $coords['longitude'] ?? null;
    $raio_perimetro = !empty($_POST['raio_perimetro']) ? intval($_POST['raio_perimetro']) : 200;

    if (!empty($nome)) {
        $stmt = $conn->prepare("INSERT INTO bases (nome, telefone, latitude, longitude, localizacao, raio_perimetro) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddsi", $nome, $telefone, $latitude, $longitude, $localizacao, $raio_perimetro);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Base '$nome' cadastrada com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao cadastrar base: " . $conn->error;
            $_SESSION['mensagem_tipo'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "O nome da base é obrigatório.";
        $_SESSION['mensagem_tipo'] = "error";
    }
    header("Location: edificios.php?tab=bases");
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

function table_exists($conn, $table) {
    $r = $conn->query("SHOW TABLES LIKE '$table'");
    return $r && $r->num_rows > 0;
}

function column_exists($conn, $table, $column) {
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $r && $r->num_rows > 0;
}

$has_localizacao = column_exists($conn, 'edificios', 'localizacao');
$has_elevador = column_exists($conn, 'edificios', 'elevador_empresa');
$has_selfie_col = column_exists($conn, 'edificios', 'requer_selfie');
$has_retirada_col = column_exists($conn, 'edificios', 'retirada_lixo');
$has_email_adm_col = column_exists($conn, 'edificios', 'enviar_email_adm');

$has_controle_faciais = table_exists($conn, 'controle_faciais');
$has_controle_ata = table_exists($conn, 'controle_ata');
$has_controle_radio = table_exists($conn, 'controle_radio_fibra');
$has_controle_dvr = table_exists($conn, 'controle_dvr');
$has_controle_ramais = table_exists($conn, 'controle_ramais');
$has_categorias_ramais = table_exists($conn, 'categorias_ramais');

switch ($tab) {
    case 'edificios':
        $extra_cols = '';
        if ($has_localizacao) $extra_cols .= "e.localizacao,";
        if ($has_elevador) $extra_cols .= "e.elevador_empresa, e.elevador_contato,";
        if ($has_retirada_col) $extra_cols .= "e.retirada_lixo,";

        $sub_facial = $has_controle_faciais ? "
            (SELECT cf.marca_equipamento FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_marca,
            (SELECT cf.acessos FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_acessos,
            (SELECT cf.status FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_status,
            (SELECT cf.observacao FROM controle_faciais cf WHERE cf.edificio_id = e.id ORDER BY cf.id DESC LIMIT 1) AS facial_observacao" : '';

        $sub_ata = $has_controle_ata ? "
            (SELECT ca.itens_ata FROM controle_ata ca WHERE ca.edificio_id = e.id ORDER BY ca.id DESC LIMIT 1) AS ata_itens,
            (SELECT ca.status FROM controle_ata ca WHERE ca.edificio_id = e.id ORDER BY ca.id DESC LIMIT 1) AS ata_status,
            (SELECT ca.observacao FROM controle_ata ca WHERE ca.edificio_id = e.id ORDER BY ca.id DESC LIMIT 1) AS ata_observacao" : '';

        $sub_radio = $has_controle_radio ? "
            (SELECT crf.ip FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_ip,
            (SELECT crf.local_detalhe FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_local,
            (SELECT crf.modo FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_modo,
            (SELECT crf.marca FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_marca,
            (SELECT crf.modelo FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_modelo,
            (SELECT crf.login FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_login,
            (SELECT crf.status FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_status,
            (SELECT crf.observacao FROM controle_radio_fibra crf WHERE crf.edificio_id = e.id ORDER BY crf.id DESC LIMIT 1) AS radio_observacao" : '';

        $sub_dvr = $has_controle_dvr ? "
            (SELECT cd.ip_dominio FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_ip,
            (SELECT cd.cloud FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_cloud,
            (SELECT cd.porta_tcp FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_porta_tcp,
            (SELECT cd.porta_http FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_porta_http,
            (SELECT cd.login FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_login,
            (SELECT cd.modelo FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_modelo,
            (SELECT cd.status FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_status,
            (SELECT cd.observacao FROM controle_dvr cd WHERE cd.edificio_id = e.id ORDER BY cd.id DESC LIMIT 1) AS dvr_observacao" : '';

        $sub_ramais = $has_controle_ramais ? "
            (SELECT cr.numero_ramal FROM controle_ramais cr WHERE cr.edificio_id = e.id ORDER BY cr.id DESC LIMIT 1) AS ramal_numero,
            (SELECT cr.status FROM controle_ramais cr WHERE cr.edificio_id = e.id ORDER BY cr.id DESC LIMIT 1) AS ramal_status" : '';

        $sub_ramal_cat = ($has_controle_ramais && $has_categorias_ramais) ? "
            (SELECT cat.nome FROM controle_ramais cr LEFT JOIN categorias_ramais cat ON cr.categoria_id = cat.id WHERE cr.edificio_id = e.id ORDER BY cr.id DESC LIMIT 1) AS ramal_categoria" : '';

        $all_subs = array_filter([$sub_facial, $sub_ata, $sub_radio, $sub_dvr, $sub_ramais, $sub_ramal_cat]);
        $subquery_block = $all_subs ? ',' . implode(',', $all_subs) : '';

        $query = "SELECT e.id, e.nome AS nome_edificio, e.endereco, e.sindico_nome, e.sindico_contato, e.administradora_id,
                         $extra_cols
                         b.nome AS nome_base, b.status AS status, a.nome AS nome_administradora,
                         a.telefone AS administradora_telefone, a.email AS administradora_email
                         $subquery_block
                  FROM edificios e
                  JOIN bases b ON e.base_id = b.id
                  LEFT JOIN administradoras a ON e.administradora_id = a.id";

        if ($filtro_base) $where_clauses[] = "e.base_id = " . intval($filtro_base);
        if (intval($usuario_base_id) > 0) $where_clauses[] = "e.base_id = " . intval($usuario_base_id);
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
        $selfie_col = $has_selfie_col ? 'e.requer_selfie' : '0 AS requer_selfie';
        $email_adm_col = $has_email_adm_col ? 'e.enviar_email_adm' : '0 AS enviar_email_adm';
        $facial_base_cond = intval($usuario_base_id) > 0 ? " WHERE b.id = " . intval($usuario_base_id) : '';
        $query = "SELECT e.id, e.nome, $selfie_col, $email_adm_col, b.nome AS nome_base
                  FROM edificios e
                  JOIN bases b ON e.base_id = b.id
                  $facial_base_cond
                  ORDER BY b.nome, e.nome";
        $result = $conn->query($query);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        break;

    case 'retirada_lixo':
        $retirada_col = $has_retirada_col ? 'e.retirada_lixo' : '0 AS retirada_lixo';
        $retirada_base_cond = intval($usuario_base_id) > 0 ? " WHERE b.id = " . intval($usuario_base_id) : '';
        $query = "SELECT e.id, e.nome, $retirada_col, b.nome AS nome_base
                  FROM edificios e
                  JOIN bases b ON e.base_id = b.id
                  $retirada_base_cond
                  ORDER BY b.nome, e.nome";
        $result = $conn->query($query);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        break;
        
    case 'bases':
        $query = "SELECT b.id, b.nome, b.telefone, b.localizacao, b.status, COUNT(e.id) as total_edificios
                  FROM bases b
                  LEFT JOIN edificios e ON b.id = e.base_id";
        if ($search) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(b.nome LIKE '%$s%' OR b.telefone LIKE '%$s%')";
        }
        if (intval($usuario_base_id) > 0) $where_clauses[] = "b.id = " . intval($usuario_base_id);
        if (!empty($where_clauses)) $query .= " WHERE " . implode(" AND ", $where_clauses);
        $query .= " GROUP BY b.id, b.nome, b.telefone, b.localizacao, b.status ORDER BY b.nome";
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
        $result = $conn->query($query);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
        $result = $conn->query($query);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        break;
}

try {
    $bases_filter_sql = intval($usuario_base_id) > 0 ? " WHERE id = " . intval($usuario_base_id) : "";
    $bases_result = $conn->query("SELECT id, nome FROM bases$bases_filter_sql ORDER BY nome");
    $bases = $bases_result ? $bases_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    $bases = [];
}

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
    
    
    
    <!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</noscript>
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css?v=2">
    <style>
        @media (min-width: 1024px) {
            .usuario-card { margin-top: 66px; }
        }
    </style>
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
                        <?php elseif ($tab === 'retirada_lixo'): ?>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Retirada de Lixo</h1>
                            <p class="mt-1 text-slate-500">Configure quais edifícios a ronda deve realizar a retirada de lixo.</p>
                        <?php else: ?>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Edifícios</h1>
                            <p class="mt-1 text-slate-500">Gerencie edifícios, bases, administradoras e síndicos.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($pode_editar && $tab !== 'faciais_locacao' && $tab !== 'retirada_lixo'): ?>
                        <?php
                            $add_links = [
                                'edificios' => ['url' => 'cadastrar_edificio.php', 'label' => 'Novo Edifício'],
                                'bases' => ['url' => 'cadastrar_base.php', 'label' => 'Nova Base'],
                                'administradoras' => ['url' => 'cadastrar_administradora.php', 'label' => 'Nova Administradora'],
                                'sindicos' => ['url' => 'cadastrar_sindico.php', 'label' => 'Novo Síndico']
                            ];
                            $current_add = $add_links[$tab] ?? $add_links['edificios'];
                        ?>
                        <a href="<?= $current_add['url'] ?>" class="icon-btn-green" title="<?= $current_add['label'] ?>"><i class="fas fa-plus" style="font-size:10px"></i></a>
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
                    <a href="?tab=bases" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === 'bases' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Bases
                    </a>
                    <a href="?tab=administradoras" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === 'administradoras' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Administradoras
                    </a>
                    <a href="?tab=faciais_locacao" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === 'faciais_locacao' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Faciais Locação
                    </a>
                    <a href="?tab=retirada_lixo" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab === 'retirada_lixo' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Retirada de Lixo
                    </a>
                </div>

                <!-- Filters & Search -->
                <?php if ($tab === 'edificios'): ?>
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

                            <div class="space-y-2">
                                <label class="form-label">Filtrar por Base</label>
                                <div class="relative">
                                    <select name="base" class="form-input appearance-none pr-10" onchange="this.form.submit()" <?= $tab !== 'edificios' ? 'disabled' : '' ?>>
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

                            <a href="?tab=edificios" class="icon-btn" title="Limpar Filtros"><i class="fas fa-sync-alt" style="font-size:10px"></i></a>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

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
                                            <div class="flex items-center gap-3 sm:gap-4">
                                                <?php if ($pode_editar): ?>
                                                <!-- Toggle Selfie -->
                                                <div class="flex flex-col items-start gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" style="width:170px; flex-shrink:0;">
                                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                        <i class="fas fa-camera text-slate-400"></i> Selfie
                                                    </span>
                                                    <label class="relative inline-flex items-center cursor-pointer" title="Selfie obrigatória no formulário">
                                                        <input type="checkbox" class="sr-only peer" onchange="toggleSelfie(<?= $item['id'] ?>, this)" <?= $item['requer_selfie'] ? 'checked' : '' ?>>
                                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                                        <span class="ml-2 text-xs font-medium <?= $item['requer_selfie'] ? 'text-primary-600' : 'text-slate-400' ?>"><?= $item['requer_selfie'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                                    </label>
                                                </div>
                                                <div class="w-px self-stretch bg-slate-200"></div>
                                                <!-- Toggle E-mail Administradora -->
                                                <div class="flex flex-col items-start gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" style="width:170px; flex-shrink:0;">
                                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                        <i class="fas fa-envelope text-slate-400"></i> E-mail Adm
                                                    </span>
                                                    <label class="relative inline-flex items-center cursor-pointer" title="Enviar e-mail à administradora ao registrar locação">
                                                        <input type="checkbox" class="sr-only peer" onchange="toggleEmailAdm(<?= $item['id'] ?>, this)" <?= $item['enviar_email_adm'] ? 'checked' : '' ?>>
                                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                                        <span class="ml-2 text-xs font-medium <?= $item['enviar_email_adm'] ? 'text-primary-600' : 'text-slate-400' ?>"><?= $item['enviar_email_adm'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                                    </label>
                                                </div>
                                                <?php else: ?>
                                                <div class="flex flex-col items-start gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" style="width:170px; flex-shrink:0;">
                                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                        <i class="fas fa-camera text-slate-400"></i> Selfie
                                                    </span>
                                                    <span class="text-sm font-medium <?= $item['requer_selfie'] ? 'text-green-600' : 'text-slate-400' ?>"><?= $item['requer_selfie'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                                </div>
                                                <div class="w-px self-stretch bg-slate-200"></div>
                                                <div class="flex flex-col items-start gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" style="width:170px; flex-shrink:0;">
                                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                        <i class="fas fa-envelope text-slate-400"></i> E-mail Adm
                                                    </span>
                                                    <span class="text-sm font-medium <?= $item['enviar_email_adm'] ? 'text-green-600' : 'text-slate-400' ?>"><?= $item['enviar_email_adm'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($tab === 'retirada_lixo'): ?>
                        <div class="admin-card">
                            <h2 class="text-lg font-bold text-slate-900 mb-4">Retirada de Lixo por Edifício</h2>
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
                                            <div class="flex items-center gap-3 sm:gap-4">
                                                <?php if ($pode_editar): ?>
                                                <div class="flex flex-col items-start gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" style="width:170px; flex-shrink:0;">
                                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                        <i class="fas fa-trash-alt text-slate-400"></i> Retirada Lixo
                                                    </span>
                                                    <label class="relative inline-flex items-center cursor-pointer" title="Retirada de lixo obrigatória">
                                                        <input type="checkbox" class="sr-only peer" onchange="toggleRetirada(<?= $item['id'] ?>, this)" <?= $item['retirada_lixo'] ? 'checked' : '' ?>>
                                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                                        <span class="ml-2 text-xs font-medium <?= $item['retirada_lixo'] ? 'text-primary-600' : 'text-slate-400' ?>"><?= $item['retirada_lixo'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                                    </label>
                                                </div>
                                                <?php else: ?>
                                                <div class="flex flex-col items-start gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" style="width:170px; flex-shrink:0;">
                                                    <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                        <i class="fas fa-trash-alt text-slate-400"></i> Retirada Lixo
                                                    </span>
                                                    <span class="text-sm font-medium <?= $item['retirada_lixo'] ? 'text-green-600' : 'text-slate-400' ?>"><?= $item['retirada_lixo'] ? 'Habilitado' : 'Desabilitado' ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($tab === 'bases'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <!-- Nova Base Form -->
                            <div class="animate-slide-up">
                                <div class="admin-card sticky top-24 usuario-card">
                                    <h2 class="mb-6 text-lg font-bold text-slate-900">Adicionar Base</h2>
                                    <?php if (!$pode_editar): ?>
                                        <p class="text-sm text-slate-500">Você não tem permissão para criar bases.</p>
                                    <?php else: ?>
                                        <form method="POST" class="space-y-4">
                                            <input type="hidden" name="create_base" value="1">
                                            <div class="space-y-2">
                                                <label class="form-label">Nome da Base *</label>
                                                <input type="text" name="nome" class="form-input" placeholder="Ex: Base Centro" required>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="form-label">Telefone de Contato</label>
                                                <input type="text" name="telefone" class="form-input" placeholder="(00) 0000-0000">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="form-label">Localização (Google Maps)</label>
                                                <input type="url" name="localizacao" class="form-input" placeholder="https://maps.app.goo.gl/..." required>
                                                <p class="text-[10px] text-slate-400 italic">Cole o link de compartilhamento do Google Maps da base. Latitude e longitude são extraídas automaticamente.</p>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="form-label">Raio do Perímetro (metros)</label>
                                                <input type="number" name="raio_perimetro" class="form-input" min="1" value="200">
                                                <p class="text-[10px] text-slate-400 italic">Distância máxima da base para iniciar/finalizar a ronda.</p>
                                            </div>
                                            <button type="submit" name="create_base" class="icon-btn-green" title="Criar Base"><i class="fas fa-save" style="font-size:10px"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Bases List -->
                            <div class="lg:col-span-2 animate-slide-up" style="animation-delay: 0.1s;">
                                <?php if (empty($data)): ?>
                                    <div class="admin-card text-center py-12 text-slate-500 italic">
                                        Nenhuma base cadastrada.
                                    </div>
                                <?php else: ?>
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <?php foreach ($data as $base): ?>
                                            <article class="admin-card border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                                                <div class="flex flex-col gap-3">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex items-center gap-3 min-w-0">
                                                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 shrink-0">
                                                                <i class="fas fa-flag"></i>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <h2 class="truncate text-lg font-bold text-slate-900"><?= htmlspecialchars($base['nome']) ?></h2>
                                                            </div>
                                                        </div>
                                                        <?php if (($base['status'] ?? 'ativo') === 'ativo'): ?>
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-green-700 shrink-0">
                                                                <i class="fas fa-check-circle"></i> Ativo
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500 shrink-0">
                                                                <i class="fas fa-pause-circle"></i> Inativo
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                                                        <p class="truncate text-xs text-slate-500"><i class="fas fa-phone mr-1.5 text-slate-400"></i><?= htmlspecialchars($base['telefone'] ?: '—') ?></p>
                                                        <?php if (!empty($base['localizacao'])): ?>
                                                            <a href="<?= htmlspecialchars($base['localizacao']) ?>" target="_blank" class="block truncate text-xs text-primary-600 hover:text-primary-700">
                                                                <i class="fas fa-map-marker-alt mr-1.5 text-slate-400"></i> Ver no Google Maps
                                                            </a>
                                                        <?php else: ?>
                                                            <p class="truncate text-xs text-slate-500"><i class="fas fa-map-marker-alt mr-1.5 text-slate-400"></i>—</p>
                                                        <?php endif; ?>
                                                        <p class="truncate text-xs text-slate-500"><i class="fas fa-building mr-1.5 text-slate-400"></i><?= intval($base['total_edificios']) ?> edifício(s)</p>
                                                    </div>

                                                    <?php if ($pode_editar): ?>
                                                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                                                            <a href="editar_base.php?id=<?= $base['id'] ?>" class="icon-btn" title="Editar"><i class="fas fa-edit" style="font-size:10px"></i></a>
                                                            <form method="POST" class="inline">
                                                                <input type="hidden" name="id_deactivate" value="<?= $base['id'] ?>">
                                                                <input type="hidden" name="current_tab" value="bases">
                                                                <button type="submit" name="deactivate_item" class="icon-btn" title="<?= ($base['status'] ?? 'ativo') === 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                                                    <i class="fas <?= ($base['status'] ?? 'ativo') === 'ativo' ? 'fa-pause' : 'fa-play' ?>" style="font-size:10px"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente excluir a base <?= addslashes(htmlspecialchars($base['nome'])) ?>?');">
                                                                <input type="hidden" name="id_delete" value="<?= $base['id'] ?>">
                                                                <input type="hidden" name="tipo_delete" value="base">
                                                                <input type="hidden" name="current_tab" value="bases">
                                                                <button type="submit" name="delete_item" class="icon-btn-red" title="Excluir"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($tab === 'administradoras'): ?>
                        <?php if (empty($data)): ?>
                            <div class="admin-card text-center py-12 text-slate-500 italic">
                                Nenhuma administradora cadastrada.
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4 lg:grid-cols-2">
                                <?php foreach ($data as $adm): ?>
                                    <article class="admin-card border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                                        <div class="flex flex-col gap-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 shrink-0">
                                                        <i class="fas fa-handshake"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h2 class="truncate text-lg font-bold text-slate-900"><?= htmlspecialchars($adm['nome']) ?></h2>
                                                    </div>
                                                </div>
                                                <span class="shrink-0 rounded-full bg-green-100 px-3 py-1 text-[11px] font-bold text-green-700">
                                                    <?= intval($adm['total_edificios']) ?> edifício(s)
                                                </span>
                                            </div>

                                            <?php if (!empty($adm['telefone']) || !empty($adm['email'])): ?>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <?php if (!empty($adm['telefone'])): ?>
                                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                            <p class="truncate text-xs text-slate-500"><i class="fas fa-phone mr-1.5 text-slate-400"></i><?= htmlspecialchars($adm['telefone']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($adm['email'])): ?>
                                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                            <p class="truncate text-xs text-slate-500"><i class="fas fa-envelope mr-1.5 text-slate-400"></i><?= htmlspecialchars($adm['email']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Edifícios administrados</p>
                                                <?php if (!empty(trim((string)$adm['edificios_administrados']))): ?>
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        <?php $ed_nomes = array_map('trim', explode(',', $adm['edificios_administrados'])); ?>
                                                        <?php foreach ($ed_nomes as $ed_nome): ?>
                                                            <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-700 border border-slate-200">
                                                                <i class="fas fa-building mr-1.5 text-slate-300" style="font-size:9px"></i><?= htmlspecialchars($ed_nome) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="mt-1 text-xs text-slate-400 italic">Nenhum edifício vinculado a esta administradora.</p>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($pode_editar): ?>
                                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                                                    <a href="editar_administradora.php?id=<?= $adm['id'] ?>" class="icon-btn" title="Editar"><i class="fas fa-edit" style="font-size:10px"></i></a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente excluir a administradora <?= addslashes(htmlspecialchars($adm['nome'])) ?>?');">
                                                        <input type="hidden" name="id_delete" value="<?= $adm['id'] ?>">
                                                        <input type="hidden" name="tipo_delete" value="administradora">
                                                        <input type="hidden" name="current_tab" value="administradoras">
                                                        <button type="submit" name="delete_item" class="icon-btn-red" title="Excluir"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif (empty($data)): ?>
                            <div class="admin-card text-center py-12 text-slate-500 italic">
                                Nenhum edifício encontrado.
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4 lg:grid-cols-2">
                                <?php foreach ($data as $item): ?>
                                    <article class="admin-card border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                                        <div class="flex flex-col gap-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Edifício</p>
                                                    <h2 class="mt-0.5 truncate text-lg font-bold text-slate-900"><?= htmlspecialchars($item['nome_edificio'] ?? $item['nome'] ?? 'Nome não informado') ?></h2>
                                                    <p class="mt-0.5 truncate text-xs text-slate-500"><?= htmlspecialchars($item['endereco'] ?: 'Endereço não informado') ?></p>
                                                    <?php if (!empty($item['localizacao']) && $item['localizacao'] !== 'NULL'): ?>
                                                        <a href="<?= htmlspecialchars($item['localizacao']) ?>" target="_blank" class="mt-1 inline-flex items-center text-[11px] text-primary-600 hover:text-primary-700">
                                                            <i class="fas fa-map-marker-alt mr-1"></i>Ver no Google Maps
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex flex-col items-end gap-1.5">
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-700"><?= render_card_value($item['nome_base'] ?? 'Base não informada') ?></span>
                                                    <?php if ($has_retirada_col): ?>
                                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold <?= ($item['retirada_lixo'] ?? 0) ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                                                            <i class="fas <?= ($item['retirada_lixo'] ?? 0) ? 'fa-trash-alt' : 'fa-trash' ?>" style="font-size:10px"></i>
                                                            Lixo: <?= ($item['retirada_lixo'] ?? 0) ? 'Sim' : 'Não' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Administradora</p>
                                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900"><?= render_card_value($item['nome_administradora'] ?? 'Administradora não informada') ?></p>
                                                    <?php if (!empty($item['administradora_telefone'])): ?>
                                                        <p class="truncate text-xs text-slate-500 mt-1"><i class="fas fa-phone mr-1.5 text-slate-400"></i><?= htmlspecialchars($item['administradora_telefone']) ?></p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['administradora_email'])): ?>
                                                        <p class="truncate text-xs text-slate-500 mt-1"><i class="fas fa-envelope mr-1.5 text-slate-400"></i><?= htmlspecialchars($item['administradora_email']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Síndico</p>
                                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900"><?= render_card_value($item['sindico_nome'] ?? 'Síndico não informado') ?></p>
                                                    <?php if (!empty($item['sindico_contato'])): ?>
                                                        <p class="truncate text-xs text-slate-500 mt-1"><?= htmlspecialchars($item['sindico_contato']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-3">
                                                <div class="bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Elevador</p>
                                                    <p class="truncate text-xs text-slate-700 mt-1">Empresa: <span class="font-semibold text-slate-900"><?= render_card_value($item['elevador_empresa'] ?? 'Não informada') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Contato: <span class="font-semibold text-slate-900"><?= render_card_value($item['elevador_contato'] ?? 'Não informado') ?></span></p>
                                                </div>
                                                <div class="bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">DVR</p>
                                                    <p class="truncate text-xs text-slate-700 mt-1">IP: <span class="font-semibold text-slate-900"><?= render_card_value($item['dvr_ip'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Modelo: <span class="font-semibold text-slate-900"><?= render_card_value($item['dvr_modelo'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">TCP: <span class="font-semibold text-slate-900"><?= render_card_value($item['dvr_porta_tcp'] ?? 'Não informado') ?></span></p>
                                                </div>
                                                <div class="bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Ramais</p>
                                                    <p class="truncate text-xs text-slate-700 mt-1">Nº: <span class="font-semibold text-slate-900"><?= render_card_value($item['ramal_numero'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Categoria: <span class="font-semibold text-slate-900"><?= render_card_value($item['ramal_categoria'] ?? 'Não informado') ?></span></p>
                                                </div>
                                            </div>

                                            <div class="grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-3">
                                                <div class="bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Facial</p>
                                                    <p class="truncate text-xs text-slate-700 mt-1">Marca: <span class="font-semibold text-slate-900"><?= render_card_value($item['facial_marca'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Acessos: <span class="font-semibold text-slate-900"><?= render_card_accessos($item['facial_acessos']) ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Obs: <span class="font-semibold text-slate-900"><?= render_card_value($item['facial_observacao'] ?? '') ?></span></p>
                                                </div>
                                                <div class="bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">ATA</p>
                                                    <p class="truncate text-xs text-slate-700 mt-1">Itens: <span class="font-semibold text-slate-900"><?= render_card_value($item['ata_itens'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Obs: <span class="font-semibold text-slate-900"><?= render_card_value($item['ata_observacao'] ?? '') ?></span></p>
                                                </div>
                                                <div class="bg-slate-50 p-3">
                                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Rádio / Fibra</p>
                                                    <p class="truncate text-xs text-slate-700 mt-1">IP: <span class="font-semibold text-slate-900"><?= render_card_value($item['radio_ip'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Modo: <span class="font-semibold text-slate-900"><?= render_card_value($item['radio_modo'] ?? 'Não informado') ?></span></p>
                                                    <p class="truncate text-xs text-slate-700">Marca: <span class="font-semibold text-slate-900"><?= render_card_value($item['radio_marca'] ?? 'Não informado') ?></span></p>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                                                <?php if ($pode_editar): ?>
                                                    <a href="editar_edificio.php?id=<?= $item['id'] ?>" class="icon-btn" title="Editar"><i class="fas fa-edit" style="font-size:10px"></i></a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este edifício?');">
                                                        <input type="hidden" name="id_delete" value="<?= $item['id'] ?>">
                                                        <input type="hidden" name="tipo_delete" value="edificio">
                                                        <input type="hidden" name="current_tab" value="<?= $tab ?>">
                                                        <button type="submit" name="delete_item" class="icon-btn-red" title="Excluir"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
                                                    </form>
                                                <?php endif; ?>
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

        function toggleRetirada(edificioId, checkbox) {
            const newValue = checkbox.checked ? 1 : 0;
            const statusText = checkbox.checked ? 'Sim' : 'Não';
            
            fetch('toggle_retirada_lixo.php', {
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

        function toggleEmailAdm(edificioId, checkbox) {
            const newValue = checkbox.checked ? 1 : 0;

            fetch('toggle_email_adm.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'edificio_id=' + edificioId + '&valor=' + newValue
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Erro ao atualizar: ' + data.message);
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar envio de e-mail');
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
