<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas usuários Administrativo, Gerente e Supervisor podem acessar
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT e.*, u.nome_real as colaborador_nome, u.nome as colaborador_login, \n+               COALESCE(ua.nome, '-') as registrador\n+        FROM extras e\n+        LEFT JOIN usuarios u ON e.usuario_id = u.id\n+        LEFT JOIN usuarios ua ON e.registrado_por = ua.id\n+        WHERE 1=1";

if ($search) {
    $sql .= " AND (u.nome_real LIKE ? OR u.nome LIKE ? OR e.data_extra LIKE ? OR e.local LIKE ? )";
}

$sql .= " ORDER BY e.data_extra DESC, e.hora_inicio DESC";

$stmt = $conn->prepare($sql);
if ($search) {
    $term = "%" . $search . "%";
    $stmt->bind_param('ssss', $term, $term, $term, $term);
}
$stmt->execute();
$extras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Extras (Todos) | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_modern.css">
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="mb-8 animate-fade-in">
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Histórico de Extras (Todos os Colaboradores)</h1>
                    <p class="mt-1 text-slate-500">Exibe todos os registros de horas extras.</p>
                </div>

                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="flex gap-4">
                            <input type="text" name="search" class="form-input flex-1" placeholder="Buscar por nome, data ou local..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn-primary">Buscar</button>
                            <?php if ($search): ?>
                                <a href="ver_todos_extras.php" class="btn-secondary">Limpar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="admin-card overflow-hidden animate-slide-up">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Colaborador</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Data</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Horário</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Local</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500">Registrado por</th>
                                    <th class="px-4 py-3 text-sm font-bold text-slate-500 text-center">Anexo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($extras)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">Nenhum registro encontrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($extras as $e): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($e['colaborador_nome'] ?? $e['colaborador_login'] ?? '-') ?></td>
                                            <td class="px-4 py-3"><?= date('d/m/Y', strtotime($e['data_extra'])) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars(substr($e['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($e['hora_fim'],0,5)) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($e['local']) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($e['registrador']) ?></td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if (!empty($e['arquivo'])): ?>
                                                    <a href="../uploads/extras/<?= htmlspecialchars($e['arquivo']) ?>" target="_blank" class="inline-flex items-center justify-center h-8 w-8 rounded bg-slate-100 text-slate-600">
                                                        <i class="fas fa-paperclip"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400">—</span>
                                                <?php endif; ?>
                                            </td>
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
</body>
</html>
