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

        // Apagar arquivos de mídia do relatório antes de remover o registro
        $stmt = $conn->prepare("SELECT descricao FROM ocorrencias WHERE id = ?");
        $stmt->bind_param("i", $id_delete);
        $stmt->execute();
        $stmt->bind_result($descricao);
        if ($stmt->fetch()) {
            preg_match_all('/uploads\/ocorrencias\/([^"\'\\s<>]+)/', $descricao, $matches);
            foreach ($matches[1] as $media) {
                $dir_upload = realpath(__DIR__ . '/../uploads/ocorrencias/');
                if ($dir_upload) {
                    $arquivo = $dir_upload . DIRECTORY_SEPARATOR . basename($media);
                    if (is_file($arquivo)) {
                        @unlink($arquivo);
                    }
                }
            }
        }
        $stmt->close();

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
    
    
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        .plantao-card { background: var(--bg-card); border-radius: 1.5rem; border: 1px solid var(--border); margin-bottom: 2rem; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 8px 32px var(--shadow); }
        .plantao-card:hover { box-shadow: 0 12px 28px var(--shadow); }
        .plantao-header { background: var(--bg-secondary); padding: 1.5rem; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .relatorio-body { padding: 2.5rem; }
        .mention { background: #D8F5DE; color: #1E8C2E; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-decoration: none; }
        .prose p { margin-bottom: 1rem; line-height: 1.7; font-size: 1.05rem; color: var(--text-secondary); }
        .prose img, .prose video { max-width: 240px !important; max-height: 180px !important; border-radius: 10px !important; margin: 0.5rem 0 !important; box-shadow: 0 2px 6px rgba(0,0,0,0.3); cursor: zoom-in; border: 1px solid var(--border); display: block; transition: border-color 0.15s, box-shadow 0.15s; }
        .prose img:hover, .prose video:hover { border-color: var(--secondary-hover); box-shadow: 0 4px 12px rgba(7, 146, 242, 0.2); }
        .prose audio { max-width: 340px; width: 100%; margin: 0.5rem 0; display: block; }
        .prose a.media-link { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; color: var(--secondary-light); text-decoration: none; }
        .lightbox { position: fixed; inset: 0; background: rgba(15,23,42,0.92); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 24px; }
        .lightbox.open { display: flex; }
        .lightbox img { max-width: 92vw; max-height: 88vh; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .lightbox video { max-width: 92vw; max-height: 88vh; border-radius: 8px; outline: none; }
        .lightbox audio { max-width: 80vw; }
        .lightbox-close { position: absolute; top: 16px; right: 16px; width: 40px; height: 40px; border: none; border-radius: 50%; background: rgba(255,255,255,0.15); color: #fff; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .lightbox-close:hover { background: rgba(255,255,255,0.3); }
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
                        <a href="registrar_ocorrencia.php" class="icon-btn-green" title="Novo Registro"><i class="fas fa-plus" style="font-size:10px"></i></a>
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
                                    <select name="local_id" class="form-input pl-11 appearance-none pr-10" onchange="this.form.submit()">
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
                                                            <a href="editar_ocorrencia.php?id=<?= $reg['id'] ?>" class="icon-btn" title="Editar"><i class="fas fa-edit" style="font-size:10px"></i></a>
                                                            <?php if ($usuario_categoria === 'gerente'): ?>
                                                                <form method="POST" onsubmit="return confirm('Excluir este registro?');" class="inline">
                                                                    <input type="hidden" name="id_delete" value="<?= $reg['id'] ?>">
                                                                    <button type="submit" name="delete_ocorrencia" class="icon-btn-red" title="Excluir"><i class="fas fa-trash-alt" style="font-size:10px"></i></button>
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

    <div class="lightbox" id="lightbox">
        <button type="button" class="lightbox-close" id="lightbox-close" title="Fechar (ESC)"><i class="fas fa-times"></i></button>
        <div id="lightbox-content"></div>
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

        // Lightbox para expandir mídia na consulta
        (function() {
            const lightbox = document.getElementById('lightbox');
            const content = document.getElementById('lightbox-content');
            const closeBtn = document.getElementById('lightbox-close');

            function closeLightbox() {
                lightbox.classList.remove('open');
                content.innerHTML = '';
            }

            function openMedia(el) {
                content.innerHTML = '';
                const clone = el.cloneNode(true);
                clone.removeAttribute('style');
                clone.style.maxWidth = '92vw';
                clone.style.maxHeight = '88vh';
                clone.style.borderRadius = '8px';
                clone.style.margin = '0';
                clone.style.boxShadow = 'none';
                if (clone.tagName === 'VIDEO' || clone.tagName === 'AUDIO') {
                    clone.controls = true;
                }
                content.appendChild(clone);
                lightbox.classList.add('open');
                const media = content.querySelector('video, audio');
                if (media) media.play().catch(function(){});
            }

            document.addEventListener('click', function(e) {
                const target = e.target;
                if (target.closest('.prose') && (target.tagName === 'IMG' || target.tagName === 'VIDEO')) {
                    e.preventDefault();
                    openMedia(target);
                }
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });

            closeBtn.addEventListener('click', closeLightbox);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeLightbox();
            });
        })();
    </script>
</body>
</html>
