<?php
require_once '../blindadosync/conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cadastro = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM splendia_cadastros WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cadastro = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Enviado | Edifício Splendia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full bg-gradient-to-br from-green-50 via-white to-green-100 text-slate-800 antialiased overflow-x-hidden">
    <div class="min-h-full flex flex-col items-center justify-center py-12 px-4">
        <div class="inline-flex items-center justify-center p-3 bg-white rounded-2xl shadow-sm mb-6">
            <img src="../img/logo_horizontal.png" alt="Blindado Soluções" class="h-10 w-auto">
        </div>
        <div class="glass w-full max-w-lg rounded-3xl shadow-xl p-8 text-center" style="background: rgba(255,255,255,0.92);">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <i class="fas fa-check-circle text-3xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Cadastro enviado com sucesso!</h1>
            <p class="mt-2 text-sm text-slate-500">
                <?php if ($cadastro): ?>
                    As informações do apartamento <strong><?php echo htmlspecialchars($cadastro['apartamento']); ?></strong>
                    foram salvas e enviadas para a nossa equipe via WhatsApp.
                <?php else: ?>
                    Suas informações foram salvas e enviadas para a nossa equipe via WhatsApp.
                <?php endif; ?>
            </p>
            <a href="index.php" class="mt-6 inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-primary-600 rounded-xl hover:bg-primary-700 shadow-lg transition-all">
                <i class="fas fa-plus"></i> Preencher outro cadastro
            </a>
        </div>
    </div>
</body>
</html>