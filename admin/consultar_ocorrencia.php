<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

if (in_array($usuario_categoria, ['administrador', 'colaborador'])) {
    header("Location: index.php");
    exit();
}

// Process deletion (Manager only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ocorrencia'])) {
    if ($usuario_categoria === 'gerente') {
        $id_delete = intval($_POST['id_delete']);
        $stmt = $conn->prepare("DELETE FROM ocorrencias WHERE id = ?");
        $stmt->bind_param("i", $id_delete);
        $stmt->execute();
        $stmt->close();
        header("Location: consultar_ocorrencia.php");
        exit();
    }
}

$data_filtro = $_GET['data'] ?? ''; // Sem filtro de data por padrão
$local_filtro = $_GET['local_id'] ?? '';

$query = "
    SELECT o.*, 
           e.nome as edificio_nome, 
           b.nome as base_nome, 
           b_direta.nome as base_direta_nome,
           u.nome as autor_nome
    FROM ocorrencias o
    LEFT JOIN edificios e ON o.edificio_id = e.id
    LEFT JOIN bases b ON e.base_id = b.id
    LEFT JOIN bases b_direta ON o.base_id = b_direta.id
    JOIN usuarios u ON o.usuario_id = u.id
    WHERE 1=1
";

if ($data_filtro) $query .= " AND o.data_ocorrencia = '$data_filtro'";
if ($local_filtro) {
    if (strpos($local_filtro, 'b_') === 0) {
        $bid = intval(substr($local_filtro, 2));
        $query .= " AND o.base_id = $bid";
    } else {
        $eid = intval(substr($local_filtro, 2));
        $query .= " AND o.edificio_id = $eid";
    }
}

$query .= " ORDER BY o.data_ocorrencia DESC, o.periodo_dia DESC, o.id DESC";
$result = $conn->query($query);
$ocorrencias_raw = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$livro_plantoes = [];
foreach ($ocorrencias_raw as $row) {
    $key = $row['data_ocorrencia'] . '_' . $row['periodo_dia'] . '_' . $row['usuario_id'];
    if (!isset($livro_plantoes[$key])) {
        $livro_plantoes[$key] = ['info' => $row, 'registros' => []];
    }
    $livro_plantoes[$key]['registros'][] = $row;
}

$edificios = $conn->query("SELECT id, nome FROM edificios ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Ocorrências | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <style>
        .plantao-card { background: white; border-radius: 1.5rem; border: 1px solid #f1f5f9; margin-bottom: 2rem; overflow: hidden; transition: all 0.3s ease; }
        .plantao-card:hover { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        .plantao-header { background: #f8fafc; padding: 1.5rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .relatorio-body { padding: 2.5rem; }
        .mention { background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-decoration: none; }
        .prose img, .prose video { max-width: 100%; border-radius: 1rem; margin: 1.5rem 0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .prose p { margin-bottom: 1rem; line-height: 1.7; font-size: 1.05rem; color: #475569; }
    </style>
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="max-w-5xl mx-auto">
                    <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 animate-fade-in">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-900">Livro de Ocorrências</h1>
                            <p class="text-slate-500">Histórico de plantões e registros operacionais.</p>
                        </div>
                        <a href="registrar_ocorrencia.php" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>Novo Registro</span>
                        </a>
                    </div>

                    <!-- Filtros Modernos -->
                    <div class="admin-card mb-10 animate-slide-up">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6" id="filterForm">
                            <div class="space-y-2">
                                <?php renderModernCalendar('data', $data_filtro, 'Filtrar por Data'); ?>
                                <script>
                                    // Adicionar submissão automática ao mudar a data
                                    document.getElementById('value_calendar_data').setAttribute('onchange', 'this.form.submit()');
                                </script>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Filtrar por Local</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-map-marker-alt text-slate-400 text-sm"></i>
                                    </div>
                                    <select name="local_id" class="form-input pl-11 appearance-none" onchange="this.form.submit()">
                                        <option value="">Todos os Locais</option>
                                        <optgroup label="Bases">
                                            <?php foreach ($bases as $b): ?>
                                                <option value="b_<?= $b['id'] ?>" <?= $local_filtro == 'b_'.$b['id'] ? 'selected' : '' ?>>Base: <?= htmlspecialchars($b['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Edifícios">
                                            <?php foreach ($edificios as $ed): ?>
                                                <option value="e_<?= $ed['id'] ?>" <?= $local_filtro == 'e_'.$ed['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ed['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if (empty($livro_plantoes)): ?>
                        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-200 animate-fade-in">
                            <div class="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-times text-slate-300 text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Nenhum registro encontrado</h3>
                            <p class="text-slate-400">Tente selecionar outra data ou local.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6 animate-slide-up">
                            <?php foreach ($livro_plantoes as $key => $plantao): $meta = $plantao['info']; ?>
                                <div class="plantao-card">
                                    <div class="plantao-header" onclick="toggleDetails('<?= $key ?>')">
                                        <div class="flex items-center gap-4">
                                            <div class="h-12 w-12 rounded-2xl <?= $meta['periodo_dia'] == 'dia' ? 'bg-orange-50 text-orange-500' : 'bg-blue-50 text-blue-500' ?> flex items-center justify-center shadow-sm">
                                                <i class="fas <?= $meta['periodo_dia'] == 'dia' ? 'fa-sun text-xl' : 'fa-moon text-xl' ?>"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-slate-900 text-lg">Plantão <?= date('d/m/Y', strtotime($meta['data_ocorrencia'])) ?> — <?= ucfirst($meta['periodo_dia']) ?></h3>
                                                <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Registrado por <?= htmlspecialchars($meta['autor_nome']) ?></p>
                                            </div>
                                        </div>
                                        <div class="h-8 w-8 rounded-full hover:bg-slate-200 flex items-center justify-center transition-colors" id="btn-icon-<?= $key ?>">
                                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="icon-<?= $key ?>"></i>
                                        </div>
                                    </div>

                                    <div id="details-<?= $key ?>" class="hidden overflow-hidden transition-all duration-500">
                                        <?php foreach ($plantao['registros'] as $reg): ?>
                                            <div class="relatorio-body border-b border-slate-50 last:border-0">
                                                <div class="flex justify-between items-start mb-8">
                                                    <div>
                                                        <span class="text-[10px] font-bold text-primary-600 uppercase tracking-widest block mb-1">Local do Registro</span>
                                                        <h4 class="text-2xl font-black text-slate-900">
                                                            <?= htmlspecialchars($reg['edificio_nome'] ?: 'Base: ' . $reg['base_direta_nome']) ?>
                                                        </h4>
                                                    </div>
                                                    <?php if ($reg['usuario_id'] == $_SESSION['usuario_id'] || $usuario_categoria === 'gerente'): ?>
                                                        <div class="flex gap-2">
                                                            <a href="editar_ocorrencia.php?id=<?= $reg['id'] ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-primary-600 hover:text-white transition-all shadow-sm"><i class="fas fa-edit text-sm"></i></a>
                                                            <?php if ($usuario_categoria === 'gerente'): ?>
                                                                <form method="POST" onsubmit="return confirm('Excluir este registro?');" class="inline">
                                                                    <input type="hidden" name="id_delete" value="<?= $reg['id'] ?>">
                                                                    <button type="submit" name="delete_ocorrencia" class="h-10 w-10 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-600 hover:text-white transition-all shadow-sm"><i class="fas fa-trash-alt text-sm"></i></button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="prose max-w-none">
                                                    <?php 
                                                        $desc = $reg['descricao'];
                                                        $desc = preg_replace('/@(\d{2}\/\d{2}\/\d{4})/', '<a href="consultar_ocorrencia.php?data=' . date('Y-m-d', strtotime(str_replace('/', '-', '$1'))) . '" class="mention">@$1</a>', $desc);
                                                        echo nl2br($desc); 
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <footer class="border-t border-slate-200 bg-white p-6 text-center text-xs font-medium text-slate-400">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Tecnologia em Segurança.</p>
            </footer>
        </div>
    </div>

    <script>
        function toggleDetails(key) {
            const details = document.getElementById('details-' + key);
            const icon = document.getElementById('icon-' + key);
            const isHidden = details.classList.contains('hidden');
            
            if (isHidden) {
                details.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                details.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</body>
</html>
