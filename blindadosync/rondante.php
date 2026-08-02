<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['rondante', 'gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome_real'] ?: $_SESSION['usuario_nome'];

// Ronda ativa
$ronda = null;
$stmt = $conn->prepare("SELECT * FROM rondas WHERE usuario_id = ? AND status = 'ativa' ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$ronda = $stmt->get_result()->fetch_assoc();
$stmt->close();

$scans_ronda = 0;
if ($ronda) {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM ronda_escaneamentos WHERE ronda_id = ?");
    $stmt->bind_param('i', $ronda['id']);
    $stmt->execute();
    $scans_ronda = intval($stmt->get_result()->fetch_assoc()['c']);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rondante | Blindado Soluções</title>
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

        <div class="flex flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="flex-1 p-4 sm:p-8 custom-scrollbar">
                <!-- Page Header -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Rondante</h1>
                        <p class="mt-1 text-slate-500">Bem-vindo, <?= htmlspecialchars($usuario_nome) ?>. Gerencie suas rondas e escaneamentos.</p>
                    </div>
                </div>

                <!-- Submenus -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <a href="rondante.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-primary-500 text-primary-600">Ronda Atual</a>
                    <a href="rondante_qrcodes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">QR Codes</a>
                    <a href="rondante_validacao.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Validação de Ronda</a>
                </div>

                <!-- Ronda Atual -->
                <div class="animate-slide-up">
                    <div class="admin-card">
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">
                                    <?= $ronda ? 'Ronda em Andamento' : 'Nenhuma Ronda Ativa' ?>
                                </h2>
                                <?php if ($ronda): ?>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Iniciada em <span class="font-semibold text-slate-900"><?= date('d/m/Y H:i:s', strtotime($ronda['hora_inicio'])) ?></span>
                                        • <?= $scans_ronda ?> QR code(s) escaneado(s)
                                    </p>
                                    <p class="mt-2 text-xs text-slate-400">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Para escanear um QR code, aponte a câmera do celular para o código fixado no edifício.
                                    </p>
                                <?php else: ?>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Inicie uma ronda dentro do perímetro da base <span class="font-semibold text-slate-900">Praia do Morro</span> (entre 19:00 e 05:50).
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col items-stretch gap-2 sm:items-end">
                                <?php if ($ronda): ?>
                                    <button onclick="finalizarRonda()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-500 transition-all shadow-sm">
                                        <i class="fas fa-stop-circle text-xs"></i> Finalizar Ronda
                                    </button>
                                <?php else: ?>
                                    <button onclick="iniciarRonda()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-green-500 transition-all shadow-sm">
                                        <i class="fas fa-play-circle text-xs"></i> Iniciar Ronda
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- GPS status / mensagens -->
                        <div id="ronda-status" class="mt-4 rounded-xl bg-slate-50 p-4 text-sm"></div>
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
        function setStatus(msg, type) {
            const el = document.getElementById('ronda-status');
            const colors = { info: 'text-slate-600', error: 'text-red-600', success: 'text-green-600', loading: 'text-primary-600' };
            el.className = 'mt-4 rounded-xl bg-slate-50 p-4 text-sm ' + (colors[type] || colors.info);
            el.innerHTML = msg;
        }

        function getPosicao() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Seu navegador não suporta geolocalização.'));
                    return;
                }
                navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
            });
        }

        async function iniciarRonda() {
            const btn = document.querySelector('button[onclick="iniciarRonda()"]');
            btn.disabled = true;
            setStatus('<i class="fas fa-spinner fa-spin mr-2"></i>Obtendo sua localização...', 'loading');
            try {
                const pos = await getPosicao();
                setStatus('<i class="fas fa-spinner fa-spin mr-2"></i>Validando perímetro da base...', 'loading');
                const res = await fetch('rondante_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=iniciar&lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude
                });
                const data = await res.json();
                if (data.success) {
                    setStatus('<i class="fas fa-check-circle mr-2"></i>' + (data.message || 'Ronda iniciada!'), 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    setStatus('<i class="fas fa-exclamation-circle mr-2"></i>' + data.message, 'error');
                    btn.disabled = false;
                }
            } catch (e) {
                setStatus('<i class="fas fa-exclamation-circle mr-2"></i>Não foi possível obter a localização. Verifique se o GPS está ativado.', 'error');
                btn.disabled = false;
            }
        }

        async function finalizarRonda() {
            const btn = document.querySelector('button[onclick="finalizarRonda()"]');
            btn.disabled = true;
            setStatus('<i class="fas fa-spinner fa-spin mr-2"></i>Obtendo sua localização...', 'loading');
            try {
                const pos = await getPosicao();
                setStatus('<i class="fas fa-spinner fa-spin mr-2"></i>Validando perímetro da base...', 'loading');
                const res = await fetch('rondante_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=finalizar&lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude
                });
                const data = await res.json();
                if (data.success) {
                    setStatus('<i class="fas fa-check-circle mr-2"></i>' + (data.message || 'Ronda finalizada!'), 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    setStatus('<i class="fas fa-exclamation-circle mr-2"></i>' + data.message, 'error');
                    btn.disabled = false;
                }
            } catch (e) {
                setStatus('<i class="fas fa-exclamation-circle mr-2"></i>Não foi possível obter a localização. Verifique se o GPS está ativado.', 'error');
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
