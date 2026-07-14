<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';

// Only collaborator or manager can access
if ($usuario_categoria !== 'colaborador' && $usuario_categoria !== 'gerente') {
    header("Location: index.php");
    exit();
}

// Get collaborator paychecks
$stmt = $conn->prepare("
    SELECT c.* 
    FROM contracheques c 
    WHERE c.usuario_id = ? 
    ORDER BY c.ano DESC, c.mes DESC
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$contracheques = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Filter by month/year
$filtro_mes = isset($_GET['mes']) ? intval($_GET['mes']) : null;
$filtro_ano = isset($_GET['ano']) ? intval($_GET['ano']) : null;

if ($filtro_mes && $filtro_ano) {
    $contracheques = array_filter($contracheques, function($cc) use ($filtro_mes, $filtro_ano) {
        return $cc['mes'] == $filtro_mes && $cc['ano'] == $filtro_ano;
    });
}

$meses_nomes = [
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
    <title>Meus Contracheques | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Meus Contracheques</h1>
                    <p class="mt-1 text-slate-500">Consulte seus contracheques mensais e faça o download dos arquivos.</p>
                </div>

                <!-- Filters Card -->
                <div class="mb-8 animate-slide-up">
                    <div class="admin-card">
                        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                            <div class="space-y-2">
                                <label class="form-label">Mês</label>
                                <div class="relative">
                                    <select name="mes" class="form-input appearance-none pr-10">
                                        <option value="">-- Todos os Meses --</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $filtro_mes == $i ? 'selected' : ''; ?>>
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT) . ' - ' . $meses_nomes[$i]; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="form-label">Ano</label>
                                <div class="relative">
                                    <select name="ano" class="form-input appearance-none pr-10">
                                        <option value="">-- Todos os Anos --</option>
                                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                            <option value="<?php echo $y; ?>" <?php echo $filtro_ano == $y ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-filter"></i>
                                    <span>Filtrar</span>
                                </button>
                                <a href="meus_contracheques.php" class="btn-secondary" title="Limpar Filtros">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="max-w-4xl animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="admin-card p-0 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Mês/Ano de Referência</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($contracheques)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center py-12 text-slate-500 italic">
                                                <div class="flex flex-col items-center gap-2">
                                                    <i class="fas fa-file-invoice-dollar text-4xl text-slate-200"></i>
                                                    <p>Nenhum contracheque disponível para os filtros selecionados.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($contracheques as $cc): ?>
                                            <tr class="group">
                                                <td class="font-bold text-slate-900">
                                                    <div class="flex items-center gap-3">
                                                        <div class="h-10 w-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </div>
                                                        <span>
                                                            <?php echo $meses_nomes[$cc['mes']]; ?> 
                                                            <span class="text-slate-400 font-normal mx-1">de</span> 
                                                            <?php echo $cc['ano']; ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="../uploads/contracheques/<?php echo htmlspecialchars($cc['arquivo']); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-sm">
                                                        <i class="fas fa-download"></i>
                                                        <span>Visualizar / Baixar</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
<?php
$conn->close();
?>
