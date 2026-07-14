<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas usuários Administrativo e Gerente podem acessar
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente'])) {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Buscar apenas colaboradores
$sql = "SELECT * FROM usuarios WHERE categoria = 'colaborador'";

if ($search) {
    $sql .= " AND (nome LIKE ? OR nome_real LIKE ?)";
}
$sql .= " ORDER BY nome_real ASC";

$stmt = $conn->prepare($sql);
if ($search) {
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();
$colaboradores = fetch_all_assoc($result);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ações Disciplinares | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Ações Disciplinares</h1>
                    <p class="mt-1 text-slate-500">Gerencie e registre as ocorrências disciplinares dos colaboradores.</p>
                </div>

                <!-- Search Card -->
                <div class="mb-6 animate-slide-up">
                    <div class="admin-card">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-search text-slate-400 text-sm"></i>
                            </div>
                            <input type="text" name="search" class="form-input pl-11" placeholder="Buscar por nome ou login..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <?php if ($search): ?>
                            <a href="listar_colaboradores.php" class="btn-secondary mt-4" title="Limpar Filtros">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nome Completo</th>
                                    <th>Usuário (Login)</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="colaboradores-tbody">
                                <?php if (empty($colaboradores)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-12 text-slate-500">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fas fa-user-slash text-4xl text-slate-200"></i>
                                                <p>Nenhum colaborador encontrado.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($colaboradores as $colab): ?>
                                        <tr class="group">
                                            <td class="font-bold text-slate-900"><?= htmlspecialchars(!empty($colab['nome_real']) ? $colab['nome_real'] : 'N/A') ?></td>
                                            <td class="text-slate-500"><?= htmlspecialchars($colab['nome']) ?></td>
                                            <td class="text-right">
                                                <a href="registrar_acao_disciplinar.php?id=<?= $colab['id'] ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-xl font-bold text-xs hover:bg-red-600 hover:text-white transition-all">
                                                    <i class="fas fa-gavel"></i>
                                                    <span>Registrar Disciplina</span>
                                                </a>
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        const tbody = document.getElementById('colaboradores-tbody');
        
        if (searchInput && tbody) {
            let timeout = null;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const params = new URLSearchParams(new FormData(searchInput.closest('form')));
                    const url = window.location.pathname + '?' + params.toString();
                    
                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newTbody = doc.getElementById('colaboradores-tbody');
                            if (newTbody) {
                                tbody.innerHTML = newTbody.innerHTML;
                            }
                            window.history.replaceState(null, '', url);
                        })
                        .catch(err => console.error('Erro na busca:', err));
                }, 300);
            });
        }
    });
    </script>
</body>
</html>
