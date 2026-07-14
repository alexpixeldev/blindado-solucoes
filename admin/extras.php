<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'components/modern_calendar.php';

// Apenas usuários Administrativo, Gerente e Supervisor podem acessar
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filter_funcao = isset($_GET['funcao_id']) ? intval($_GET['funcao_id']) : 0;

// Verificar existência de coluna funcao_id em usuarios
$has_funcao = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'funcao_id'")->num_rows > 0;
if ($has_funcao) {
    $funcoes_res = $conn->query("SELECT id, nome FROM funcoes ORDER BY nome ASC");
    $funcoes = $funcoes_res ? $funcoes_res->fetch_all(MYSQLI_ASSOC) : [];
}

// Handler AJAX para registrar extra via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_extra') {
    $resp = ['success' => false, 'message' => 'Requisição inválida.'];
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $data_extra = $_POST['data_extra'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fim = $_POST['hora_fim'] ?? '';
    $local = $_POST['local'] ?? '';

    if ($usuario_id && $data_extra && $hora_inicio && $hora_fim && $local) {
        // não processamos arquivos aqui (campo removido)
        $novo_nome = null;

        $registrado_por = $_SESSION['usuario_id'];
        $stmt_ins = $conn->prepare("INSERT INTO extras (usuario_id, data_extra, hora_inicio, hora_fim, local, arquivo, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->bind_param("isssssi", $usuario_id, $data_extra, $hora_inicio, $hora_fim, $local, $novo_nome, $registrado_por);
        if ($stmt_ins->execute()) {
            $resp = ['success' => true, 'message' => 'Extra registrado com sucesso.'];
        } else {
            $resp['message'] = 'Erro ao salvar no banco: ' . $conn->error;
        }
        $stmt_ins->close();
    } else {
        $resp['message'] = 'Preencha todos os campos obrigatórios.';
    }

    header('Content-Type: application/json');
    echo json_encode($resp);
    exit();
}

// Montar query com possíveis filtros adicionais
$base = "SELECT e.*, u.nome_real, u.nome as usuario_login, COALESCE(ur.nome, '-') as registrador";
if ($has_funcao) $base .= ", f.nome as funcao_nome";
$base .= " FROM extras e LEFT JOIN usuarios u ON e.usuario_id = u.id LEFT JOIN usuarios ur ON e.registrado_por = ur.id";
if ($has_funcao) $base .= " LEFT JOIN funcoes f ON u.funcao_id = f.id";

$where = [];
$params = [];
$types = '';
if ($search) {
    $where[] = "(u.nome_real LIKE ? OR u.nome LIKE ? OR e.local LIKE ? )";
    $term = "%" . $search . "%";
    $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= 'sss';
}
if ($start_date) {
    $where[] = "e.data_extra >= ?";
    $params[] = $start_date; $types .= 's';
}
if ($end_date) {
    $where[] = "e.data_extra <= ?";
    $params[] = $end_date; $types .= 's';
}
if ($has_funcao && $filter_funcao) {
    // buscar nome da função selecionada para também comparar com usuarios.categoria
    $funcao_nome = '';
    foreach ($funcoes as $ff) {
        if ($ff['id'] == $filter_funcao) { $funcao_nome = $ff['nome']; break; }
    }
    if ($funcao_nome !== '') {
        $where[] = "(f.id = ? OR LOWER(u.categoria) = LOWER(?))";
        $params[] = $filter_funcao; $params[] = $funcao_nome; $types .= 'is';
    } else {
        $where[] = "f.id = ?";
        $params[] = $filter_funcao; $types .= 'i';
    }
}

$sql = $base . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY e.data_extra DESC, e.hora_inicio DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    // bind dynamic params
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = & $params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}
$stmt->execute();
$result = $stmt->get_result();
$extras = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lista de colaboradores para formulário de registro
$colaboradores_res = $conn->query("SELECT id, nome_real, nome FROM usuarios WHERE categoria = 'colaborador' ORDER BY nome_real ASC");
$colaboradores = $colaboradores_res ? $colaboradores_res->fetch_all(MYSQLI_ASSOC) : [];

// Endpoint AJAX para busca de colaboradores (autocomplete)
if (isset($_GET['action']) && $_GET['action'] === 'search_colaboradores') {
    $q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
    $out = [];
    if ($q !== '') {
        $term = '%' . $q . '%';
        $stmtc = $conn->prepare("SELECT id, nome_real, nome FROM usuarios WHERE categoria = 'colaborador' AND (nome_real LIKE ? OR nome LIKE ?) ORDER BY nome_real LIMIT 10");
        $stmtc->bind_param('ss', $term, $term);
        $stmtc->execute();
        $res = $stmtc->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[] = ['id' => $row['id'], 'label' => ($row['nome_real'] ?: $row['nome'])];
        }
        $stmtc->close();
    }
    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Extras | Blindado Soluções</title>
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
        
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Gestão de Extras</h1>
                    <p class="mt-1 text-slate-500">Registre e gerencie as horas extras dos colaboradores.</p>
                </div>

                <!-- Filters (left) and Registrar (right) Cards -->
                <div class="mb-6 animate-slide-up">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left: Filters -->
                        <div class="admin-card">
                            <h3 class="text-lg font-bold">Filtros</h3>
                            <form id="filters-form" class="space-y-4">
                                <div>
                                    <label class="form-label">Busca livre</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="fas fa-search text-slate-400 text-sm"></i>
                                        </div>
                                        <input type="text" name="search" class="form-input pl-11" placeholder="Nome, login ou local..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <?php renderModernCalendar('start_date', $start_date, 'Período início'); ?>
                                    </div>
                                    <div>
                                        <?php renderModernCalendar('end_date', $end_date, 'Período fim'); ?>
                                    </div>
                                </div>

                                <?php if (!empty($has_funcao) && !empty($funcoes)): ?>
                                    <div>
                                        <label class="form-label">Função</label>
                                        <select name="funcao_id" class="form-input">
                                            <option value="">Todas</option>
                                            <?php foreach ($funcoes as $f): ?>
                                                <option value="<?= $f['id'] ?>" <?= $filter_funcao == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Right: Registrar Extra -->
                        <div class="admin-card">
                            <h3 class="text-lg font-bold">Registrar Extra</h3>
                            <form id="register-form" enctype="multipart/form-data" class="space-y-4">
                                <div>
                                    <label class="form-label">Colaborador</label>
                                    <input type="text" name="colaborador_search" id="colaborador-search" class="form-input" placeholder="Digite para buscar..." autocomplete="off" required>
                                    <input type="hidden" name="usuario_id" id="usuario_id">
                                    <div id="colaborador-suggestions" class="bg-white border border-slate-200 mt-1 rounded shadow-sm max-h-48 overflow-auto hidden"></div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <?php renderModernCalendar('data_extra', date('Y-m-d'), 'Data do Extra'); ?>
                                    </div>
                                    <div></div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Hora Início</label>
                                        <input type="time" name="hora_inicio" class="form-input" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Hora Fim</label>
                                        <input type="time" name="hora_fim" class="form-input" required>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Local</label>
                                    <input type="text" name="local" class="form-input" placeholder="Ex: Base Matriz" required>
                                </div>

                                <!-- campo de arquivo removido conforme solicitado -->

                                <div class="flex gap-3">
                                    <button type="submit" class="btn-primary">Registrar</button>
                                    <button type="button" id="register-reset" class="btn-secondary">Limpar</button>
                                </div>
                                <div id="register-message" class="text-sm mt-2"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Nome</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Login</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Data</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Horário</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Local</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Registrado por</th>
                                    <!-- coluna Anexo removida -->
                                </tr>
                            </thead>
                            <tbody id="extras-tbody" class="divide-y divide-slate-100">
                                <?php if (empty($extras)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">Nenhum registro de extra encontrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($extras as $e): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors group">
                                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($e['nome_real'] ?? '-') ?></td>
                                            <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($e['usuario_login'] ?? '-') ?></td>
                                            <td class="px-4 py-3"><?= date('d/m/Y', strtotime($e['data_extra'])) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars(substr($e['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($e['hora_fim'],0,5)) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($e['local']) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($e['registrador'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
        const filtersForm = document.getElementById('filters-form');
        const registerForm = document.getElementById('register-form');
        const tbody = document.getElementById('extras-tbody');

        // Função para atualizar tabela com base nos filtros
        function updateTable() {
            const params = new URLSearchParams(new FormData(filtersForm));
            const url = window.location.pathname + '?' + params.toString();
            fetch(url)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTbody = doc.getElementById('extras-tbody');
                    if (newTbody) tbody.innerHTML = newTbody.innerHTML;
                    window.history.replaceState(null, '', url);
                })
                .catch(err => console.error('Erro ao atualizar tabela:', err));
        }

        // Auto-update quando qualquer campo de filtro muda
        if (filtersForm) {
            const inputs = filtersForm.querySelectorAll('input, select');
            let timeout = null;
            inputs.forEach(el => {
                el.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(updateTable, 300);
                });
                el.addEventListener('change', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(updateTable, 300);
                });
            });
        }

        // Autocomplete para colaboradores
        const colSearch = document.getElementById('colaborador-search');
        const suggestions = document.getElementById('colaborador-suggestions');
        const usuarioIdInput = document.getElementById('usuario_id');
        if (colSearch) {
            let sTimeout = null;
            colSearch.addEventListener('input', function() {
                usuarioIdInput.value = '';
                const q = this.value.trim();
                if (q.length < 2) {
                    suggestions.classList.add('hidden');
                    suggestions.innerHTML = '';
                    return;
                }
                clearTimeout(sTimeout);
                sTimeout = setTimeout(() => {
                    fetch(window.location.pathname + '?action=search_colaboradores&q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(list => {
                            suggestions.innerHTML = '';
                            if (!list || !list.length) {
                                suggestions.classList.add('hidden');
                                return;
                            }
                            list.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'px-3 py-2 hover:bg-slate-100 cursor-pointer text-sm';
                                div.textContent = item.label;
                                div.dataset.id = item.id;
                                div.addEventListener('click', () => {
                                    colSearch.value = item.label;
                                    usuarioIdInput.value = item.id;
                                    suggestions.classList.add('hidden');
                                });
                                suggestions.appendChild(div);
                            });
                            suggestions.classList.remove('hidden');
                        })
                        .catch(err => console.error('Erro autocomplete:', err));
                }, 250);
            });

            document.addEventListener('click', (e) => {
                if (!suggestions.contains(e.target) && e.target !== colSearch) {
                    suggestions.classList.add('hidden');
                }
            });
        }

        // Submissão AJAX do formulário de registro
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const msgEl = document.getElementById('register-message');
                msgEl.textContent = '';

                const fd = new FormData(registerForm);
                fd.append('action', 'registrar_extra');

                fetch(window.location.pathname, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(json => {
                        if (json.success) {
                            msgEl.className = 'text-sm mt-2 text-green-600';
                            msgEl.textContent = json.message;
                            registerForm.reset();
                            // atualizar tabela após registro
                            updateTable();
                        } else {
                            msgEl.className = 'text-sm mt-2 text-red-600';
                            msgEl.textContent = json.message || 'Erro';
                        }
                    })
                    .catch(err => {
                        msgEl.className = 'text-sm mt-2 text-red-600';
                        msgEl.textContent = 'Erro na submissão.';
                        console.error(err);
                    });
            });

            const resetBtn = document.getElementById('register-reset');
            if (resetBtn) resetBtn.addEventListener('click', () => registerForm.reset());
        }
    });
    </script>
</body>
</html>
