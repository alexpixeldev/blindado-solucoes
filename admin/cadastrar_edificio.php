<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if (isset($_POST['add_edificio'])) {
    $nome_edificio = trim($_POST['nome_edificio']);
    $base_id = $_POST['base_id'];
    $endereco = trim($_POST['endereco'] ?? '');
    $sindico_nome = trim($_POST['sindico_nome'] ?? '');
    $sindico_contato = trim($_POST['sindico_contato'] ?? '');
    $administradora_id = !empty($_POST['administradora_id']) ? $_POST['administradora_id'] : null;
    $observacao_ficha_locacao = trim($_POST['observacao_ficha_locacao'] ?? '');
    $sindico_id = null;
    
    if (!empty($nome_edificio) && !empty($base_id)) {
        // Verificar se o nome do síndico já existe
        if (!empty($sindico_nome)) {
            $stmt_check = $conn->prepare("SELECT s.id, s.nome, GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') AS edificios 
                                         FROM sindicos s 
                                         LEFT JOIN edificios e ON e.sindico_id = s.id 
                                         WHERE s.nome = ? 
                                         GROUP BY s.id, s.nome");
            $stmt_check->bind_param("s", $sindico_nome);
            $stmt_check->execute();
            $sindico_existente = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            if ($sindico_existente) {
                $acao = $_POST['acao_sindico'] ?? '';
                if ($acao === 'usar_existente') {
                    $sindico_id = $sindico_existente['id'];
                } elseif ($acao === 'alterar_nome') {
                    $sindico_id = null;
                } else {
                    $_SESSION['mensagem'] = "Este nome já está registrado como síndico do edifício \"{$sindico_existente['edificios']}\". Deseja adicionar ele como síndico deste edifício também ou alterar o nome?";
                    $_SESSION['mensagem_tipo'] = "warning";
                    $_SESSION['sindico_duplicado'] = [
                        'nome' => $sindico_nome,
                        'edificios' => $sindico_existente['edificios'],
                        'sindico_id' => $sindico_existente['id'],
                        'form_data' => $_POST
                    ];
                    header("Location: cadastrar_edificio.php");
                    exit();
                }
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO edificios (nome, base_id, endereco, sindico_nome, sindico_contato, administradora_id, observacao_ficha_locacao, sindico_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssisi", $nome_edificio, $base_id, $endereco, $sindico_nome, $sindico_contato, $administradora_id, $observacao_ficha_locacao, $sindico_id);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Edifício '$nome_edificio' adicionado com sucesso!";
            $_SESSION['mensagem_tipo'] = "success";
            header("Location: edificios.php?tab=edificios");
            exit();
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar edifício: " . $conn->error;
            $_SESSION['mensagem_tipo'] = "error";
        }
        $stmt->close();
    }
}

$bases = $conn->query("SELECT id, nome FROM bases ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$administradoras = $conn->query("SELECT id, nome FROM administradoras ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

$mensagem = '';
$mensagem_tipo = 'info';
$sindico_duplicado = null;

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $mensagem_tipo = $_SESSION['mensagem_tipo'] ?? 'info';
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
}

if (isset($_SESSION['sindico_duplicado'])) {
    $sindico_duplicado = $_SESSION['sindico_duplicado'];
    unset($_SESSION['sindico_duplicado']);
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Edifício | Blindado Soluções</title>
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
                    <div class="flex items-center gap-4">
                        <a href="edificios.php?tab=edificios" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Cadastrar Edifício</h1>
                            <p class="mt-1 text-slate-500">Adicione um novo edifício ao sistema e vincule-o a uma base.</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 <?php echo $mensagem_tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : ($mensagem_tipo === 'warning' ? 'bg-amber-50 border-amber-500 text-amber-700' : 'bg-red-50 border-red-500 text-red-700'); ?> border-l-4 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas <?php echo $mensagem_tipo === 'success' ? 'fa-check-circle' : ($mensagem_tipo === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?> mt-0.5"></i>
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($mensagem); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($sindico_duplicado): ?>
                    <div class="mb-8 admin-card border-2 border-amber-500/50 animate-slide-up">
                        <div class="flex items-center gap-4 text-amber-600 mb-4">
                            <i class="fas fa-user-tag text-2xl"></i>
                            <h3 class="text-lg font-bold">Síndico Já Existente</h3>
                        </div>
                        <p class="text-slate-600 mb-6">O nome <strong>"<?php echo htmlspecialchars($sindico_duplicado['nome']); ?>"</strong> já está registrado como síndico do edifício <strong>"<?php echo htmlspecialchars($sindico_duplicado['edificios']); ?>"</strong>.</p>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <form method="POST" class="flex-1">
                                <?php foreach ($sindico_duplicado['form_data'] as $key => $value): ?>
                                    <?php if ($key !== 'acao_sindico'): ?>
                                        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value ?? ''); ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <input type="hidden" name="acao_sindico" value="usar_existente">
                                <button type="submit" name="add_edificio" class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-sm transition-all">
                                    Usar Síndico Existente
                                </button>
                            </form>
                            
                            <form method="POST" class="flex-1 flex gap-2">
                                <?php foreach ($sindico_duplicado['form_data'] as $key => $value): ?>
                                    <?php if ($key !== 'acao_sindico' && $key !== 'sindico_nome'): ?>
                                        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value ?? ''); ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <input type="hidden" name="acao_sindico" value="alterar_nome">
                                <input type="text" name="sindico_nome" required placeholder="Novo nome para o síndico" class="flex-1 form-input">
                                <button type="submit" name="add_edificio" class="px-6 py-3 bg-slate-700 hover:bg-slate-800 text-white rounded-xl font-bold text-sm transition-all">
                                    Alterar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="form-label">Selecione a Base *</label>
                                    <div class="relative">
                                        <select name="base_id" class="form-input appearance-none pr-10" required>
                                            <option value="">-- Selecione a Base --</option>
                                            <?php foreach ($bases as $base): ?>
                                                <option value="<?php echo $base['id']; ?>"><?php echo htmlspecialchars($base['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Nome do Edifício *</label>
                                    <input type="text" name="nome_edificio" class="form-input" placeholder="Ex: Edifício Solar" required>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="form-label">Endereço Completo</label>
                                    <input type="text" name="endereco" class="form-input" placeholder="Ex: Rua das Flores, 123 - Centro">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Nome do Síndico</label>
                                    <input type="text" name="sindico_nome" class="form-input" placeholder="Nome do síndico do edifício">
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Contato do Síndico</label>
                                    <input type="text" name="sindico_contato" class="form-input" placeholder="WhatsApp ou Telefone">
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="form-label">Administradora</label>
                                    <div class="relative">
                                        <select name="administradora_id" class="form-input appearance-none pr-10">
                                            <option value="">-- Selecione a Administradora (Opcional) --</option>
                                            <?php foreach ($administradoras as $adm): ?>
                                                <option value="<?php echo $adm['id']; ?>"><?php echo htmlspecialchars($adm['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="form-label">Observação ficha locação</label>
                                    <textarea name="observacao_ficha_locacao" class="form-input min-h-[120px]" placeholder="Insira aqui observações detalhadas para a ficha de locação..."></textarea>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" name="add_edificio" class="btn-primary flex-1">
                                    <i class="fas fa-save"></i>
                                    <span>Cadastrar Edifício</span>
                                </button>
                                <a href="edificios.php?tab=edificios" class="btn-secondary text-center">
                                    <span>Cancelar</span>
                                </a>
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
