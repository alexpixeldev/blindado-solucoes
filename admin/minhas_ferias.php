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

// Get collaborator vacations
$stmt = $conn->prepare("
    SELECT * FROM ferias 
    WHERE usuario_id = ? 
    ORDER BY data_inicio DESC
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$minhas_ferias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Férias | Blindado Soluções</title>
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
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Minhas Férias</h1>
                    <p class="mt-1 text-slate-500">Consulte seus períodos de férias e documentos disponíveis.</p>
                </div>

                <div class="max-w-4xl animate-slide-up">
                    <div class="admin-card p-0 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Período de Férias</th>
                                        <th>Status</th>
                                        <th class="text-center">Documento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($minhas_ferias)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-12 text-slate-500 italic">
                                                <div class="flex flex-col items-center gap-2">
                                                    <i class="fas fa-calendar-times text-4xl text-slate-200"></i>
                                                    <p>Nenhum registro de férias disponível.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($minhas_ferias as $f): ?>
                                            <tr class="group">
                                                <td class="font-bold text-slate-900">
                                                    <div class="flex items-center gap-3">
                                                        <div class="h-10 w-10 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </div>
                                                        <span>
                                                            <?php echo date('d/m/Y', strtotime($f['data_inicio'])); ?> 
                                                            <span class="text-slate-400 font-normal mx-1">até</span> 
                                                            <?php echo date('d/m/Y', strtotime($f['data_fim'])); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $hoje = date('Y-m-d');
                                                        $status = ($hoje >= $f['data_inicio'] && $hoje <= $f['data_fim']) ? 'Em curso' : ($hoje > $f['data_fim'] ? 'Concluída' : 'Agendada');
                                                        $color = ($status === 'Em curso') ? 'bg-green-100 text-green-700' : (($status === 'Concluída') ? 'bg-slate-100 text-slate-700' : 'bg-blue-100 text-blue-700');
                                                    ?>
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold <?php echo $color; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="../uploads/ferias/<?php echo htmlspecialchars($f['arquivo']); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-sm">
                                                        <i class="fas fa-file-pdf"></i>
                                                        <span>Visualizar</span>
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
