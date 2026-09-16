<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas usuários Administrativo, Gerente e Supervisor podem acessar
if (!in_array($_SESSION['usuario_categoria'], ['administrativo', 'gerente', 'supervisor'])) {
    header("Location: index.php");
    exit();
}

// Exclusão de colaborador (apenas Administrativo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_colaborador']) && $_SESSION['usuario_categoria'] === 'administrativo') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND categoria = 'colaborador'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Colaborador excluído com sucesso!";
        $_SESSION['mensagem_tipo'] = "success";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir colaborador: " . $conn->error;
        $_SESSION['mensagem_tipo'] = "error";
    }
    $stmt->close();
    header("Location: listar_colaboradores.php");
    exit();
}

$mensagem = $_SESSION['mensagem'] ?? '';
$mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);

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
    <title>Colaboradores | Blindado Soluções</title>
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
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 animate-fade-in">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Colaboradores</h1>
                            <p class="mt-1 text-slate-500">Visualize e gerencie os colaboradores da empresa.</p>
                        </div>
                        <?php if ($_SESSION['usuario_categoria'] === 'administrativo'): ?>
                        <a href="criar_colaborador.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>
                            <span>Cadastrar Colaborador</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

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
                                            <td class="text-right flex justify-end gap-2">
                                                <a href="visualizar_colaborador.php?id=<?= $colab['id'] ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                    <span>Ver Detalhes</span>
                                                </a>
                                                <?php if ($_SESSION['usuario_categoria'] === 'administrativo'): ?>
                                                <a href="editar_colaborador.php?id=<?= $colab['id'] ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                    <span>Editar</span>
                                                </a>
                                                <form method="POST" class="flex" onsubmit="return confirm('Excluir este colaborador? Esta ação não pode ser desfeita.');">
                                                    <input type="hidden" name="id" value="<?= $colab['id'] ?>">
                                                    <button type="submit" name="delete_colaborador" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                        <span>Excluir</span>
                                                    </button>
                                                </form>
                                                <a href="registrar_acao_disciplinar.php?id=<?= $colab['id'] ?>" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-gavel"></i>
                                                    <span>Registrar Disciplina</span>
                                                </a>
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
