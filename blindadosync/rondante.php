<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['rondante', 'gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}
if ($usuario_categoria !== 'rondante') {
    header("Location: rondante_validacao.php");
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
$checklist = [];
if ($ronda) {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM ronda_escaneamentos WHERE ronda_id = ?");
    $stmt->bind_param('i', $ronda['id']);
    $stmt->execute();
    $scans_ronda = intval($stmt->get_result()->fetch_assoc()['c']);
    $stmt->close();

    $stmt = $conn->prepare("SELECT e.id, e.nome, e.retirada_lixo,
                            re.escaneado_em, re.interfones_ok, re.lixo_retirado
                            FROM edificios e
                            LEFT JOIN ronda_escaneamentos re ON re.edificio_id = e.id AND re.ronda_id = ?
                            ORDER BY e.nome");
    $stmt->bind_param('i', $ronda['id']);
    $stmt->execute();
    $checklist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$total_edificios = count($checklist);
$total_escaneados = count(array_filter($checklist, fn($c) => !empty($c['escaneado_em'])));
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

        <div class="flex min-w-0 flex-1 flex-col">
            <?php include 'components/header.php'; ?>

            <main class="min-w-0 flex-1 p-4 sm:p-8 custom-scrollbar">
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
                    <?php if ($usuario_categoria === 'gerente' || $usuario_categoria === 'diretor'): ?>
                    <a href="rondante_qrcodes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">QR Codes</a>
                    <?php endif; ?>
                    <?php if ($usuario_categoria === 'gerente' || $usuario_categoria === 'diretor'): ?>
                    <a href="rondante_validacao.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Validação de Ronda</a>
                    <a href="rondante_gerentes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Gerentes de Ronda</a>
                    <?php endif; ?>
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
                                        Inicie uma ronda dentro do perímetro da base <span class="font-semibold text-slate-900">Praia do Morro</span>.
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col items-stretch gap-2 sm:items-end">
                                <?php if ($ronda): ?>
                                    <button onclick="abrirScanner()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500 transition-all shadow-sm">
                                        <i class="fas fa-camera text-xs"></i> Escanear QR Code
                                    </button>
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
                        <div id="ronda-status" class="mt-4 rounded-xl bg-slate-50 p-4 text-sm break-words"></div>
                    </div>
                </div>

                <!-- Checklist de Escaneamento -->
                <?php if ($ronda): ?>
                <div class="mt-6 animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="admin-card">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-lg font-bold text-slate-900">Checklist de Escaneamento</h2>
                            <span class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">
                                <?= $total_escaneados ?>/<?= $total_edificios ?> edifícios
                            </span>
                        </div>
                        <?php if (empty($checklist)): ?>
                            <div class="text-center py-10 text-slate-500 italic">Nenhum edifício cadastrado.</div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="admin-table" data-no-cell-copy>
                                    <thead>
                                        <tr>
                                            <th>Edifício</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($checklist as $c): ?>
                                            <?php $escaneado = !empty($c['escaneado_em']); ?>
                                            <tr class="<?= $escaneado ? 'bg-green-50/50' : '' ?>">
                                                <td class="font-semibold text-slate-900"><?= htmlspecialchars($c['nome']) ?></td>
                                                <td>
                                                    <?php if ($escaneado): ?>
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-green-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">
                                                            <i class="fas fa-check-circle"></i> Escaneado
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 rounded-lg bg-red-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700">
                                                            <i class="fas fa-times-circle"></i> Não escaneado
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>

            <!-- Modal Scanner QR Code -->
            <div id="scanner-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-sm p-4">
                <div class="w-full max-w-md animate-slide-up">
                    <div class="rounded-3xl bg-white p-6 shadow-2xl">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900">Escanear QR Code</h3>
                            <button onclick="fecharScanner()" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition-all" title="Fechar">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                        <div class="relative overflow-hidden rounded-2xl bg-black aspect-square">
                            <video id="qr-video" playsinline muted class="h-full w-full object-cover"></video>
                            <div class="absolute inset-0 pointer-events-none">
                                <div class="absolute left-1/2 top-1/2 h-48 w-48 -translate-x-1/2 -translate-y-1/2 rounded-2xl border-4 border-primary-400/80" id="scan-frame"></div>
                            </div>
                            <div id="scanner-loading" class="absolute inset-0 flex items-center justify-center bg-black/60 text-white text-sm font-semibold">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Ativando câmera...
                            </div>
                        </div>
                        <p id="scanner-msg" class="mt-4 text-center text-sm text-slate-500">Aponte a câmera para o QR code do edifício.</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 p-3 cursor-pointer">
                                <input type="checkbox" id="scanner-interfones" class="h-4 w-4 rounded accent-primary-500">
                                <span class="text-xs font-semibold text-slate-700">Interfones testados</span>
                            </label>
                            <label class="flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 p-3 cursor-pointer">
                                <input type="checkbox" id="scanner-lixo" class="h-4 w-4 rounded accent-primary-500">
                                <span class="text-xs font-semibold text-slate-700">Lixo retirado</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="assets/js/jsqr.js"></script>
    <script>
        let scannerStream = null;
        let scannerTicking = false;
        let scannerProcessed = false;
        let scannerRondaId = <?= $ronda ? intval($ronda['id']) : 0 ?>;

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
                const opcoes = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };
                const fallback = () => {
                    navigator.geolocation.getCurrentPosition(
                        resolve,
                        reject,
                        { enableHighAccuracy: false, timeout: 20000, maximumAge: 30000 }
                    );
                };
                try {
                    navigator.geolocation.getCurrentPosition(resolve, fallback, opcoes);
                } catch (e) {
                    fallback();
                }
            });
        }

        function erroLocalizacao(err) {
            if (!err || !err.code) return 'Não foi possível obter a localização. Verifique se o GPS está ativado.';
            if (err.code === err.PERMISSION_DENIED) return 'Permissão de localização negada. Habilite o acesso à localização no navegador.';
            if (err.code === err.TIMEOUT) return 'O GPS demorou para responder. Tente novamente.';
            if (err.code === err.POSITION_UNAVAILABLE) return 'Localização indisponível. Verifique se o GPS está ativado.';
            return 'Não foi possível obter a localização. Tente novamente.';
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
                setStatus('<i class="fas fa-exclamation-circle mr-2"></i>' + erroLocalizacao(e), 'error');
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
                    if (data.whatsapp_links && data.whatsapp_links.length) {
                        const msg = data.terceira_ronda 
                            ? '<i class="fas fa-file-alt mr-2"></i>Enviando relatório consolidado das 3 rondas para o WhatsApp dos gerentes...'
                            : '<i class="fas fa-paper-plane mr-2"></i>Enviando relatório para o WhatsApp dos gerentes...';
                        setStatus(msg, 'success');
                        const openLink = (url, i) => {
                            setTimeout(() => window.open(url, '_blank'), i * 800);
                        };
                        data.whatsapp_links.forEach(openLink);
                    }
                    setTimeout(() => location.reload(), 2000);
                } else {
                    setStatus('<i class="fas fa-exclamation-circle mr-2"></i>' + data.message, 'error');
                    btn.disabled = false;
                }
            } catch (e) {
                setStatus('<i class="fas fa-exclamation-circle mr-2"></i>' + erroLocalizacao(e), 'error');
                btn.disabled = false;
            }
        }

        function abrirScanner() {
            if (!scannerRondaId) {
                setStatus('<i class="fas fa-exclamation-circle mr-2"></i>Inicie a ronda antes de escanear.', 'error');
                return;
            }
            const modal = document.getElementById('scanner-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('scanner-msg').textContent = 'Aponte a câmera para o QR code do edifício.';
            document.getElementById('scanner-msg').classList.remove('text-green-600', 'text-red-600');
            document.getElementById('scanner-msg').classList.add('text-slate-500');
            document.getElementById('scanner-loading').style.display = 'flex';
            document.getElementById('scanner-interfones').checked = false;
            document.getElementById('scanner-lixo').checked = false;
            scannerProcessed = false;
            abrirCamera();
        }

        function fecharScanner() {
            scannerTicking = false;
            scannerProcessed = true;
            if (scannerStream) {
                scannerStream.getTracks().forEach(t => t.stop());
                scannerStream = null;
            }
            document.getElementById('scanner-modal').classList.add('hidden');
            document.getElementById('scanner-modal').classList.remove('flex');
        }

        async function abrirCamera() {
            const video = document.getElementById('qr-video');
            try {
                scannerStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                video.srcObject = scannerStream;
                await video.play();
                document.getElementById('scanner-loading').style.display = 'none';
                scannerTicking = true;
                requestAnimationFrame(scannerTick);
            } catch (e) {
                document.getElementById('scanner-loading').style.display = 'none';
                const msg = document.getElementById('scanner-msg');
                msg.textContent = 'Não foi possível acessar a câmera. Verifique as permissões.';
                msg.classList.remove('text-slate-500');
                msg.classList.add('text-red-600');
            }
        }

        function scannerTick() {
            if (!scannerTicking || scannerProcessed) return;
            const video = document.getElementById('qr-video');
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert'
                });
                if (code && code.data) {
                    scannerProcessed = true;
                    scannerTicking = false;
                    processarQR(code.data);
                    return;
                }
            }
            requestAnimationFrame(scannerTick);
        }

        function processarQR(dado) {
            let token = '';
            try {
                const url = new URL(dado);
                token = url.searchParams.get('token') || '';
            } catch (e) {
                const m = dado.match(/[?&]token=([^&]+)/);
                token = m ? decodeURIComponent(m[1]) : dado;
            }
            const msg = document.getElementById('scanner-msg');
            if (!token) {
                msg.textContent = 'QR code inválido. Aponte para o QR fixado no edifício.';
                msg.classList.remove('text-slate-500');
                msg.classList.add('text-red-600');
                setTimeout(() => {
                    scannerProcessed = false;
                    scannerTicking = true;
                    requestAnimationFrame(scannerTick);
                }, 1800);
                return;
            }
            msg.textContent = 'QR lido! Validando localização...';
            msg.classList.remove('text-slate-500', 'text-red-600');
            msg.classList.add('text-green-600');
            registrarEscaneamento(token);
        }

        async function registrarEscaneamento(token) {
            const msg = document.getElementById('scanner-msg');
            try {
                const pos = await getPosicao();
                const interfones = document.getElementById('scanner-interfones').checked ? 1 : 0;
                const lixo = document.getElementById('scanner-lixo').checked ? 1 : 0;
                const res = await fetch('rondante_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=escanear_token&token=' + encodeURIComponent(token) +
                          '&lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude +
                          '&interfones=' + interfones + '&lixo=' + lixo
                });
                const data = await res.json();
                if (data.success) {
                    msg.textContent = data.message;
                    msg.classList.remove('text-slate-500', 'text-red-600');
                    msg.classList.add('text-green-600');
                    setStatus('<i class="fas fa-check-circle mr-2"></i>' + data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    msg.textContent = data.message;
                    msg.classList.remove('text-slate-500');
                    msg.classList.add('text-red-600');
                    setTimeout(() => {
                        scannerProcessed = false;
                        scannerTicking = true;
                        requestAnimationFrame(scannerTick);
                    }, 2500);
                }
            } catch (e) {
                msg.textContent = erroLocalizacao(e);
                msg.classList.remove('text-slate-500');
                msg.classList.add('text-red-600');
                setTimeout(() => {
                    scannerProcessed = false;
                    scannerTicking = true;
                    requestAnimationFrame(scannerTick);
                }, 2500);
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
