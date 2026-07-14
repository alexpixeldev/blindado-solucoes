<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

// Apenas usuários Administrativo e Gerente podem acessar
if ($_SESSION['usuario_categoria'] !== 'administrativo' && $_SESSION['usuario_categoria'] !== 'gerente') {
    header("Location: index.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: listar_colaboradores.php");
    exit();
}

// Buscar dados do colaborador
$stmt = $conn->prepare("SELECT nome, nome_real FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$colaborador = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$colaborador) {
    header("Location: listar_colaboradores.php");
    exit();
}

// Buscar ações disciplinares
$stmt = $conn->prepare("SELECT * FROM acoes_disciplinares WHERE usuario_id = ? ORDER BY data_registro DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$acoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Disciplinar | Blindado Soluções</title>
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
                <div class="mb-8 animate-fade-in flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="visualizar_colaborador.php?id=<?= $id ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Histórico Disciplinar</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($colaborador['nome_real']) ?></span></p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="registrar_acao_disciplinar.php?id=<?= $id ?>" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>Nova Ação</span>
                        </a>
                    </div>
                </div>

                <div class="admin-card overflow-hidden animate-slide-up">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data(s) do Ocorrido</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tipo</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data Registro</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($acoes)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center text-slate-400">
                                                <i class="fas fa-gavel text-4xl mb-4 opacity-20"></i>
                                                <p class="text-sm font-medium">Nenhuma ação disciplinar registrada para este colaborador.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($acoes as $a): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors group">
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($a['datas']) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($a['tipo'] === 'advertencia'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> Advertência
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                                        <i class="fas fa-ban mr-1"></i> Suspensão
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs text-slate-500"><?= date('d/m/Y H:i', strtotime($a['data_registro'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <?php if (!empty($a['arquivo'])): ?>
                                                        <a href="../uploads/disciplina/<?= htmlspecialchars($a['arquivo']) ?>" target="_blank" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-primary-100 hover:text-primary-600 transition-all" title="Ver Anexo">
                                                            <i class="fas fa-paperclip text-xs"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="editar_disciplina.php?id=<?= $a['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-amber-100 hover:text-amber-600 transition-all" title="Editar">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </a>
                                                </div>
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
