<?php
require_once 'verifica_login.php';
require_once 'conexao.php';
require_once 'localizacao_helper.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['supervisor', 'gerente'])) {
    header("Location: edificios.php");
    exit();
}

$mensagem = '';
$base = null;
$base_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// If no ID, or invalid, redirect
if (!$base_id) {
    $_SESSION['mensagem'] = "Erro: ID da base inválido.";
    $_SESSION['mensagem_tipo'] = "error";
    header('Location: edificios.php?tab=bases');
    exit();
}

// Logic to UPDATE data in database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $localizacao = trim($_POST['localizacao'] ?? '');
    $coords = extrair_coordenadas_google_maps($localizacao);
    $latitude = $coords['latitude'] ?? null;
    $longitude = $coords['longitude'] ?? null;
    $raio_perimetro = !empty($_POST['raio_perimetro']) ? intval($_POST['raio_perimetro']) : 200;
    $id = $_POST['id'];

    if (empty($nome)) {
        $mensagem = "O nome da base não pode ficar em branco.";
    } else {
        $stmt = $conn->prepare("UPDATE bases SET nome = ?, telefone = ?, latitude = ?, longitude = ?, localizacao = ?, raio_perimetro = ? WHERE id = ?");
        $stmt->bind_param("ssddsii", $nome, $telefone, $latitude, $longitude, $localizacao, $raio_perimetro, $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Base atualizada com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            header('Location: edificios.php?tab=bases');
            exit();
        } else {
            $mensagem = "Erro ao atualizar a base: " . $conn->error;
        }
        $stmt->close();
    }
}

// Logic to FETCH base data to fill the form
$stmt = $conn->prepare("SELECT * FROM bases WHERE id = ?");
$stmt->bind_param("i", $base_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $base = $result->fetch_assoc();
} else {
    $_SESSION['mensagem'] = "Base não encontrada.";
    $_SESSION['mensagem_tipo'] = "error";
    header('Location: edificios.php?tab=bases');
    exit();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Base | Blindado Soluções</title>
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
                    <div class="flex items-center gap-4">
                        <a href="edificios.php?tab=bases" class="icon-btn" title="Voltar"><i class="fas fa-arrow-left" style="font-size:10px"></i></a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Editar Base</h1>
                            <p class="mt-1 text-slate-500">Atualize as informações da base operacional <?= htmlspecialchars($base['nome']) ?>.</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= $mensagem ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-2xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="id" value="<?php echo $base['id']; ?>">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Nome da Base *</label>
                                    <input type="text" name="nome" class="form-input" value="<?php echo htmlspecialchars($base['nome']); ?>" required>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Telefone de Contato</label>
                                    <input type="text" name="telefone" class="form-input" value="<?php echo htmlspecialchars($base['telefone']); ?>">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Localização (Google Maps)</label>
                                <input type="url" name="localizacao" class="form-input" placeholder="https://maps.app.goo.gl/..." value="<?php echo htmlspecialchars($base['localizacao'] ?? ''); ?>" required>
                                <p class="text-[10px] text-slate-400 italic">Cole o link de compartilhamento do Google Maps da base. Latitude e longitude são extraídas automaticamente.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="form-label">Raio do Perímetro (metros)</label>
                                <input type="number" name="raio_perimetro" class="form-input" min="1" value="<?php echo htmlspecialchars($base['raio_perimetro'] ?? '200'); ?>">
                                <p class="text-[10px] text-slate-400 italic">Distância máxima da base para iniciar/finalizar a ronda.</p>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="icon-btn-green" title="Salvar"><i class="fas fa-save" style="font-size:10px"></i></button>
                                <a href="edificios.php?tab=bases" class="icon-btn" title="Cancelar"><i class="fas fa-times" style="font-size:10px"></i></a>
                            </div>
                        </form>
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
<?php $conn->close(); ?>
