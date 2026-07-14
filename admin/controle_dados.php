<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if ($usuario_categoria === 'colaborador') { header('Location: index.php'); exit(); }
$pode_editar = in_array($usuario_categoria, ['supervisor', 'gerente']);

if ($pode_editar && isset($_POST['delete_item'])) {
    $id_raw = $_POST['id_delete'];
    $id = is_numeric($id_raw) ? intval($id_raw) : $id_raw;
    $tabela = 'controle_' . $_POST['tipo_delete'];
    
    if ($_POST['tipo_delete'] === 'ramais') {
        $id_raw = $_POST['id_delete'];
        if (strpos($id_raw, 'e') === 0) {
            $eid = intval(substr($id_raw, 1));
            $conn->query("DELETE FROM $tabela WHERE edificio_id = $eid");
        } elseif (strpos($id_raw, 'b') === 0) {
            $bid = intval(substr($id_raw, 1));
            $conn->query("DELETE FROM $tabela WHERE base_id = $bid");
        } else {
            $conn->query("DELETE FROM $tabela WHERE id = " . intval($id_raw));
        }
    } else {
        $conn->query("DELETE FROM $tabela WHERE id = $id");
    }
}

$tipo_dados = $_GET['tipo'] ?? 'faciais';
$filtro_edificio = $_GET['edificio'] ?? '';

$query = "";
$alias = "";
switch ($tipo_dados) {
    case 'faciais':
        $query = "SELECT cf.*, e.nome as edificio_nome, b.nome as base_nome FROM controle_faciais cf LEFT JOIN edificios e ON cf.edificio_id = e.id LEFT JOIN bases b ON e.base_id = b.id";
        $alias = "cf";
        break;
    case 'ata':
        $query = "SELECT ca.*, e.nome as edificio_nome, b.nome as base_nome FROM controle_ata ca LEFT JOIN edificios e ON ca.edificio_id = e.id LEFT JOIN bases b ON e.base_id = b.id"; 
        $alias = "ca";
        break;
    case 'radio_fibra':
        $query = "SELECT crf.*, e.nome as edificio_nome, b.nome as base_nome FROM controle_radio_fibra crf LEFT JOIN edificios e ON crf.edificio_id = e.id LEFT JOIN bases b ON crf.base_id = b.id";
        $alias = "crf";
        break;
    case 'dvr':
        $query = "SELECT cd.*, e.nome as edificio_nome, b.nome as base_nome FROM controle_dvr cd LEFT JOIN edificios e ON cd.edificio_id = e.id LEFT JOIN bases b ON cd.base_id = b.id";
        $alias = "cd";
        break;
    case 'ips':
        $query = "SELECT ci.*, b.nome as base_nome, e.nome as edificio_nome FROM controle_ips ci LEFT JOIN bases b ON ci.base_id = b.id LEFT JOIN edificios e ON ci.edificio_id = e.id";
        $alias = "ci";
        break;
    case 'ramais':
        $query = "SELECT cr.*, e.nome as edificio_nome, b.nome as base_nome, cat.nome as categoria_nome FROM controle_ramais cr LEFT JOIN edificios e ON cr.edificio_id = e.id LEFT JOIN bases b ON cr.base_id = b.id LEFT JOIN categorias_ramais cat ON cr.categoria_id = cat.id";
        $alias = "cr";
        break;
}

$where_clauses = [];
$search = $_GET['search'] ?? '';

if ($filtro_edificio) {
    $where_clauses[] = "({$alias}.edificio_id = " . intval($filtro_edificio) . " OR {$alias}.base_id = " . intval($filtro_edificio) . ")";
}

if ($search) {
    $s = $conn->real_escape_string($search);
    $search_parts = ["e.nome LIKE '%$s%'", "b.nome LIKE '%$s%'"];
    
    if ($tipo_dados === 'faciais') {
        $search_parts[] = "cf.marca_equipamento LIKE '%$s%'";
        $search_parts[] = "cf.acessos LIKE '%$s%'";
    } elseif ($tipo_dados === 'ata') {
        $search_parts[] = "ca.itens_ata LIKE '%$s%'";
    } elseif ($tipo_dados === 'radio_fibra') {
        $search_parts[] = "crf.ip LIKE '%$s%'";
        $search_parts[] = "crf.local_detalhe LIKE '%$s%'";
        $search_parts[] = "crf.marca LIKE '%$s%'";
        $search_parts[] = "crf.modelo LIKE '%$s%'";
        $search_parts[] = "crf.login LIKE '%$s%'";
    } elseif ($tipo_dados === 'dvr') {
        $search_parts[] = "cd.ip_dominio LIKE '%$s%'";
        $search_parts[] = "cd.cloud LIKE '%$s%'";
        $search_parts[] = "cd.modelo LIKE '%$s%'";
        $search_parts[] = "cd.login LIKE '%$s%'";
    } elseif ($tipo_dados === 'ips') {
        $search_parts[] = "ci.estacao LIKE '%$s%'";
        $search_parts[] = "ci.ip LIKE '%$s%'";
        $search_parts[] = "ci.ramais LIKE '%$s%'";
    } elseif ($tipo_dados === 'ramais') {
        $search_parts[] = "cr.numero_ramal LIKE '%$s%'";
        $search_parts[] = "cat.nome LIKE '%$s%'";
    }
    $where_clauses[] = "(" . implode(" OR ", $search_parts) . ")";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

    if ($tipo_dados === 'ramais') {
        $query .= " ORDER BY edificio_nome, base_nome, cr.numero_ramal";
        $raw_dados = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        $dados = [];
        foreach ($raw_dados as $row) {
            $key = ($row['edificio_id'] ?? '0') . '_' . ($row['base_id'] ?? '0');
            if (!isset($dados[$key])) {
                // Para ramais, o ID no link será o edificio_id ou base_id prefixado
                $id_link = $row['edificio_id'] ? "e" . $row['edificio_id'] : "b" . $row['base_id'];
                $dados[$key] = [
                    'id' => $id_link,
                    'edificio_id' => $row['edificio_id'],
                    'base_id' => $row['base_id'],
                    'edificio_nome' => $row['edificio_nome'],
                    'base_nome' => $row['base_nome'],
                    'status' => 'ativo',
                    'ramais_grouped' => []
                ];
            }
            $dados[$key]['ramais_grouped'][] = $row;
        }
        $dados = array_values($dados);
    } else {
    $query .= " ORDER BY data_criacao DESC";
    $dados = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

$edificios = $conn->query("SELECT id, nome FROM edificios ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

/**
 * Função auxiliar para tratar codificação de dados legados
 */
function format_legacy_text($text) {
    if (empty($text)) return '';
    if (!mb_check_encoding($text, 'UTF-8')) {
        return utf8_encode($text);
    }
    return $text;
}

/**
 * Função para renderizar dados que podem ser JSON ou texto simples
 */
function render_data_field($content, $type) {
    if (empty($content)) return '<span class="text-slate-400 italic">---</span>';
    $decoded = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $html = '<div class="overflow-x-auto mt-2"><table class="min-w-full divide-y divide-slate-200 text-[11px] border border-slate-100 rounded-lg overflow-hidden">';
        if (isset($decoded[0]) && is_array($decoded[0])) {
            $headers = array_keys($decoded[0]);
            $html .= '<thead class="bg-slate-50"><tr>';
            foreach ($headers as $h) { $html .= '<th class="px-3 py-2 text-left font-bold text-slate-500 uppercase tracking-wider">' . htmlspecialchars(ucfirst($h)) . '</th>'; }
            $html .= '</tr></thead><tbody class="bg-white divide-y divide-slate-100">';
            foreach ($decoded as $item) {
                $html .= '<tr>';
                foreach ($headers as $h) {
                    $val = $item[$h] ?? '';
                    $html .= '<td class="px-3 py-2 whitespace-nowrap text-slate-700 font-medium">' . htmlspecialchars(format_legacy_text($val)) . '</td>';
                }
                $html .= '</tr>';
            }
        } else {
            $html .= '<tbody class="bg-white divide-y divide-slate-100">';
            foreach ($decoded as $key => $val) {
                if (is_array($val)) $val = json_encode($val);
                $html .= '<tr><td class="px-3 py-2 bg-slate-50 font-bold text-slate-500 w-24">' . htmlspecialchars(ucfirst($key)) . '</td><td class="px-3 py-2 text-slate-700 font-medium">' . htmlspecialchars(format_legacy_text($val)) . '</td></tr>';
            }
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
    return '<p class="text-sm font-bold text-slate-700">' . htmlspecialchars(format_legacy_text($content)) . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Dados | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Controle de Dados</h1>
                        <p class="mt-1 text-slate-500">Gerencie informações técnicas de equipamentos e acessos.</p>
                    </div>
                    <?php if ($pode_editar): ?>
                        <a href="editar_dados.php?tipo=<?php echo $tipo_dados; ?>" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>Registrar Novo</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <?php 
                        $tipos_config = ['faciais' => 'Faciais', 'ata' => 'ATA', 'radio_fibra' => 'Rádio/Fibra', 'dvr' => 'DVR', 'ips' => 'IPS', 'ramais' => 'Ramais'];
                        foreach ($tipos_config as $key => $label):
                    ?>
                        <a href="?tipo=<?= $key ?>" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tipo_dados === $key ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- POPs Map for Radio/Fibra -->
                <?php if ($tipo_dados === 'radio_fibra' && !$filtro_edificio && !$search): ?>
                    <?php 
                    $pops = $conn->query("SELECT crf.*, e.nome as edificio_nome, b.nome as base_nome FROM controle_radio_fibra crf LEFT JOIN edificios e ON crf.edificio_id = e.id LEFT JOIN bases b ON crf.base_id = b.id WHERE is_pop = 1 ORDER BY e.nome, b.nome")->fetch_all(MYSQLI_ASSOC);
                    if (!empty($pops)): 
                    ?>
                    <div class="mb-8 animate-slide-up">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Mapa de Dependências (POPs)</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                            <?php foreach ($pops as $pop): ?>
                                <div class="admin-card p-4 border-l-4 border-amber-400 bg-amber-50/30">
                                    <div class="font-bold text-amber-800 mb-3 flex items-center gap-2">
                                        <i class="fas fa-broadcast-tower text-xs"></i>
                                        <span class="truncate"><?= htmlspecialchars(format_legacy_text($pop['edificio_nome'] ?: $pop['base_nome'])) ?></span>
                                    </div>
                                    <?php 
                                    $deps = $conn->query("SELECT cpd.nome_personalizado, e.nome as edificio_nome, b.nome as base_nome FROM controle_pop_dependentes cpd LEFT JOIN edificios e ON cpd.edificio_id = e.id LEFT JOIN bases b ON e.base_id = b.id WHERE cpd.pop_id = " . $pop['id'] . " ORDER BY COALESCE(e.nome, cpd.nome_personalizado)")->fetch_all(MYSQLI_ASSOC);
                                    if (empty($deps)): ?>
                                        <p class="text-[10px] text-slate-400 italic">Sem dependentes</p>
                                    <?php else: ?>
                                        <ul class="space-y-1">
                                            <?php foreach ($deps as $dep): ?>
                                                <li class="text-[11px] text-slate-600 flex items-center gap-2">
                                                    <i class="fas fa-level-up-alt rotate-90 text-slate-300"></i>
                                                    <span class="truncate"><?= htmlspecialchars(format_legacy_text($dep['edificio_nome'] ?: $dep['nome_personalizado'])) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <input type="hidden" name="tipo" value="<?= $tipo_dados ?>">
                            <div class="space-y-2">
                                <label class="form-label">Filtrar por Edifício</label>
                                <select name="edificio" class="form-input appearance-none">
                                    <option value="">Todos os Edifícios</option>
                                    <?php foreach ($edificios as $ed): ?>
                                        <option value="<?= $ed['id'] ?>" <?= $filtro_edificio == $ed['id'] ? 'selected' : '' ?>><?= htmlspecialchars(format_legacy_text($ed['nome'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="search" id="searchInput" class="form-input" placeholder="IP, Marca, Modelo..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="flex gap-2">
                                <a href="?tipo=<?= $tipo_dados ?>" class="btn-secondary"><i class="fas fa-sync-alt"></i></a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Script para busca automática -->
                <script>
                    // Busca automática quando os campos são alterados
                    function autoSubmit() {
                        const form = document.querySelector('form[method="GET"]');
                        if (form) {
                            // Adiciona evento de input no campo de busca
                            const searchInput = document.getElementById('searchInput');
                            if (searchInput) {
                                searchInput.addEventListener('input', function() {
                                    clearTimeout(window.searchTimeout);
                                    window.searchTimeout = setTimeout(() => {
                                        form.submit();
                                    }, 500);
                                });
                            }
                            
                            // Adiciona evento de change no select de edifício
                            const edificioSelect = form.querySelector('select[name="edificio"]');
                            if (edificioSelect) {
                                edificioSelect.addEventListener('change', function() {
                                    form.submit();
                                });
                            }
                        }
                    }
                    
                    // Inicializa quando o DOM estiver pronto
                    document.addEventListener('DOMContentLoaded', autoSubmit);
                </script>

                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <?php if (empty($dados)): ?>
                        <div class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                            <i class="fas fa-database text-4xl text-slate-200 mb-3"></i>
                            <p class="text-slate-500">Nenhum registro encontrado.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-6">
                            <?php foreach ($dados as $row): ?>
                                <div class="admin-card group hover:border-primary-300 transition-all">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                                                <i class="fas <?= $tipo_dados === 'faciais' ? 'fa-user-check' : ($tipo_dados === 'ata' ? 'fa-phone-square' : ($tipo_dados === 'radio_fibra' ? 'fa-wifi' : ($tipo_dados === 'dvr' ? 'fa-video' : ($tipo_dados === 'ips' ? 'fa-network-wired' : 'fa-phone-alt')))) ?>"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-slate-900"><?= htmlspecialchars(format_legacy_text($row['edificio_nome'] ?: $row['base_nome'])) ?></h3>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= $tipo_dados ?></span>
                                            </div>
                                        </div>
                                        <?php if ($pode_editar): ?>
                                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <a href="editar_dados.php?tipo=<?= $tipo_dados ?>&id=<?= $row['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white transition-all"><i class="fas fa-edit"></i></a>
                                                <form method="POST" onsubmit="return confirm('Excluir este registro?');" class="inline">
                                                    <input type="hidden" name="id_delete" value="<?= $row['id'] ?>"><input type="hidden" name="tipo_delete" value="<?= $tipo_dados ?>">
                                                    <button type="submit" name="delete_item" class="h-8 w-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all"><i class="fas fa-trash-alt"></i></button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="space-y-4">
                                        <?php if ($tipo_dados === 'faciais'): ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Marca Equipamento</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['marca_equipamento'])) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Detalhamento de Acessos</span><?= render_data_field($row['acessos'], 'faciais') ?></div>
                                            </div>
                                        <?php elseif ($tipo_dados === 'ata'): ?>
                                            <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Configurações ATA</span><?= render_data_field($row['itens_ata'], 'ata') ?></div>
                                        <?php elseif ($tipo_dados === 'ips'): ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Estação</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['estacao'])) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">IP Estação</span><p class="text-sm font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded"><?= htmlspecialchars($row['ip']) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">IP Estação</span><p class="text-sm font-bold text-primary-600"><?= htmlspecialchars($row['ip']) ?></p></div>
                                            </div>
                                            <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Ramais Configurados</span><?= render_data_field($row['ramais'], 'ips') ?></div>
                                        <?php elseif ($tipo_dados === 'radio_fibra'): ?>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">IP</span><p class="text-sm font-bold text-primary-600"><?= htmlspecialchars($row['ip']) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Marca/Modelo</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['marca'] . ' ' . $row['modelo'])) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Login</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['login'])) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Local</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['local_detalhe'])) ?></p></div>
                                            </div>
                                        <?php elseif ($tipo_dados === 'dvr'): ?>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">IP/Domínio</span><p class="text-sm font-bold text-primary-600"><?= htmlspecialchars($row['ip_dominio']) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Cloud</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['cloud'])) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Modelo</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['modelo'])) ?></p></div>
                                                <div class="space-y-1"><span class="text-[10px] font-bold uppercase text-slate-400">Login</span><p class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($row['login'])) ?></p></div>
                                            </div>
                                        <?php elseif ($tipo_dados === 'ramais'): ?>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($row['ramais_grouped'] as $ramal): ?>
                                                    <div class="bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 flex flex-col">
                                                        <span class="text-[9px] font-bold uppercase text-slate-400"><?= htmlspecialchars(format_legacy_text($ramal['categoria_nome'])) ?></span>
                                                        <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars(format_legacy_text($ramal['numero_ramal'])) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($row['observacao'])): ?>
                                            <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                                <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Observações Gerais</span>
                                                <p class="text-xs text-slate-600 italic"><?= nl2br(htmlspecialchars(format_legacy_text($row['observacao']))) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>
