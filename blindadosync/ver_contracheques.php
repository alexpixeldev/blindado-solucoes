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
    header("Location: contracheque.php");
    exit();
}

// Fetch collaborator data
$stmt = $conn->prepare("SELECT nome, nome_real FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$colaborador = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$colaborador) {
    header("Location: contracheque.php");
    exit();
}

// Fetch collaborator paychecks
$stmt = $conn->prepare("SELECT * FROM contracheques WHERE usuario_id = ? ORDER BY ano DESC, mes DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$contracheques = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Contracheques | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Tailwind CSS -->
    
    
    
    <!-- Google Fonts & Font Awesome -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <div class="mb-8 animate-fade-in flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="visualizar_colaborador.php?id=<?= $id ?>" class="icon-btn" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Contracheques</h1>
                            <p class="mt-1 text-slate-500">Colaborador: <span class="font-bold text-slate-900"><?= htmlspecialchars($colaborador['nome_real']) ?></span></p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="adicionar_contracheque.php?id=<?= $id ?>" class="icon-btn-green" title="Adicionar Novo"><i class="fas fa-plus" style="font-size:10px"></i></a>
                    </div>
                </div>

                <div class="admin-card overflow-hidden animate-slide-up">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ano</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Mês</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Arquivo</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($contracheques)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center text-slate-400">
                                                <i class="fas fa-file-invoice-dollar text-4xl mb-4 opacity-20"></i>
                                                <p class="text-sm font-medium">Nenhum contracheque encontrado para este colaborador.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($contracheques as $cc): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors group">
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($cc['ano']) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-medium text-slate-600"><?= htmlspecialchars($meses[$cc['mes']]) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="../uploads/contracheques/<?= htmlspecialchars($cc['arquivo']) ?>" target="_blank" class="icon-btn-blue" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" title="Visualizar"><i class="fas fa-external-link-alt" style="font-size:10px"></i></a>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="editar_contracheque.php?id=<?= $cc['id'] ?>" class="icon-btn-blue" title="Trocar Arquivo"><i class="fas fa-exchange-alt" style="font-size:10px"></i></a>
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
