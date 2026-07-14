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

// Processar exclusão de falta
if (isset($_POST['delete_falta'])) {
    $falta_id = $_POST['falta_id'];
    $stmt = $conn->prepare("DELETE FROM faltas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $falta_id, $id);
    if ($stmt->execute()) {
        $mensagem = "Falta excluída com sucesso.";
        $mensagem_tipo = "success";
    } else {
        $mensagem = "Erro ao excluir: " . $stmt->error;
        $mensagem_tipo = "error";
    }
    $stmt->close();
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

// Buscar faltas
$stmt = $conn->prepare("SELECT * FROM faltas WHERE usuario_id = ? ORDER BY datas DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$faltas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Faltas | Blindado Soluções</title>
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
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Histórico de Faltas</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($colaborador['nome_real']) ?></span></p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="registrar_falta.php?id=<?= $id ?>" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>Nova Falta</span>
                        </a>
                    </div>
                </div>

                <?php if (isset($mensagem)): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <div class="admin-card overflow-hidden animate-slide-up">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data(s) da Falta</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tipo</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Data Registro</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($faltas)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center text-slate-400">
                                                <i class="fas fa-calendar-times text-4xl mb-4 opacity-20"></i>
                                                <p class="text-sm font-medium">Nenhuma falta registrada para este colaborador.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($faltas as $f): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors group">
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($f['datas']) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($f['tipo'] === 'justificada'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                                        <i class="fas fa-check-circle mr-1"></i> Justificada
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                                        <i class="fas fa-times-circle mr-1"></i> Não Justificada
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs text-slate-500"><?= date('d/m/Y H:i', strtotime($f['data_registro'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <?php if (!empty($f['arquivo'])): ?>
                                                        <a href="../uploads/faltas/<?= htmlspecialchars($f['arquivo']) ?>" target="_blank" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-primary-100 hover:text-primary-600 transition-all" title="Ver Anexo">
                                                            <i class="fas fa-paperclip text-xs"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="editar_falta.php?id=<?= $f['id'] ?>" class="h-8 w-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-amber-100 hover:text-amber-600 transition-all" title="Editar">
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
