<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['rondante', 'gerente', 'diretor'])) {
    header("Location: login.php");
    exit();
}

$token = trim($_GET['token'] ?? '');
$edificio = null;
$erro = '';
$sucesso = '';

if ($token !== '') {
    $stmt = $conn->prepare("SELECT id, nome, base_id, latitude, longitude, retirada_lixo FROM edificios WHERE qr_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $edificio = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edificio) {
        $erro = "QR code inválido ou não cadastrado.";
    }
}

// Processar POST (escanear com GPS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edificio) {
    $lat = $_POST['lat'] ?? '';
    $lng = $_POST['lng'] ?? '';
    $interfones = isset($_POST['interfones']) ? '1' : '0';
    $lixo = isset($_POST['lixo']) ? '1' : '0';

    $ch = curl_init();
    session_write_close();
    $base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?: 'localhost') . $base_path . '/rondante_api.php';
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'action' => 'escanear',
            'ronda_id' => $_POST['ronda_id'] ?? 0,
            'edificio_id' => $edificio['id'],
            'lat' => $lat,
            'lng' => $lng,
            'interfones' => $interfones,
            'lixo' => $lixo
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_COOKIE => session_name() . '=' . session_id()
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($data && !empty($data['success'])) {
        $sucesso = $data['message'];
    } else {
        $erro = $data['message'] ?? 'Erro ao escanear QR code.';
    }
}

// Verificar se o rondante tem ronda ativa
$ronda_ativa = null;
$stmt = $conn->prepare("SELECT id, data_ronda, hora_inicio FROM rondas WHERE usuario_id = ? AND status = 'ativa' ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $_SESSION['usuario_id']);
$stmt->execute();
$ronda_ativa = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR Code | Rondante</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="h-full text-slate-800 antialiased bg-gradient-to-br from-[#061328] via-[#010B1E] to-[#0a1f3d]">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md animate-fade-in">
            <div class="glass rounded-3xl p-8 shadow-2xl">
                <div class="mb-6 text-center">
                    <img src="../img/logo-blindado-sync-horizontal-otimizado.svg" alt="Blindado Soluções" class="h-10 w-auto mx-auto object-contain">
                    <h1 class="mt-4 text-xl font-bold text-white">Escaneamento de Ronda</h1>
                    <p class="text-sm text-slate-400"><?= htmlspecialchars($_SESSION['usuario_nome_real'] ?: $_SESSION['usuario_nome']) ?></p>
                </div>

                <?php if (!$ronda_ativa): ?>
                    <div class="rounded-2xl bg-red-500/10 border border-red-500/30 p-5 text-center">
                        <i class="fas fa-stop-circle text-2xl text-red-400"></i>
                        <p class="mt-2 text-sm font-semibold text-red-200">Nenhuma ronda em andamento.</p>
                        <p class="mt-1 text-xs text-slate-400">Inicie a ronda na página do Rondante antes de escanear.</p>
                        <a href="rondante.php" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500 transition-all">
                            <i class="fas fa-arrow-left text-xs"></i> Ir para o Rondante
                        </a>
                    </div>
                <?php elseif ($sucesso): ?>
                    <div class="rounded-2xl bg-green-500/10 border border-green-500/30 p-5 text-center">
                        <i class="fas fa-check-circle text-3xl text-green-400"></i>
                        <p class="mt-2 text-sm font-bold text-green-200"><?= htmlspecialchars($sucesso) ?></p>
                        <p class="mt-1 text-xs text-slate-400">Escanee o próximo QR code ou escaneie este novamente se necessário.</p>
                    </div>
                <?php elseif ($erro): ?>
                    <div class="rounded-2xl bg-red-500/10 border border-red-500/30 p-5 text-center">
                        <i class="fas fa-exclamation-circle text-2xl text-red-400"></i>
                        <p class="mt-2 text-sm font-semibold text-red-200"><?= htmlspecialchars($erro) ?></p>
                    </div>
                <?php elseif ($edificio): ?>
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-400">
                            <i class="fas fa-building text-2xl"></i>
                        </div>
                        <h2 class="text-lg font-bold text-white"><?= htmlspecialchars($edificio['nome']) ?></h2>
                        <p class="text-xs text-slate-400 mt-1">Ronda em andamento desde <?= date('d/m H:i', strtotime($ronda_ativa['hora_inicio'])) ?></p>

                        <p class="mt-5 text-xs font-semibold uppercase tracking-widest text-slate-500">Obtendo sua localização...</p>
                        <p id="gps-msg" class="mt-1 text-xs text-slate-400">Aguarde enquanto validamos que você está no edifício.</p>

                        <form id="scan-form" method="POST" class="mt-5">
                            <input type="hidden" name="ronda_id" value="<?= $ronda_ativa['id'] ?>">
                            <input type="hidden" name="lat" id="lat">
                            <input type="hidden" name="lng" id="lng">
                            <div class="space-y-3 text-left">
                                <label class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 p-3 cursor-pointer">
                                    <input type="checkbox" name="interfones" id="interfones" class="h-5 w-5 rounded accent-primary-500">
                                    <span class="text-sm text-slate-200"><i class="fas fa-phone mr-2 text-primary-400"></i>Interfones testados</span>
                                </label>
                                <label class="flex items-center gap-3 rounded-xl bg-white/5 border border-white/10 p-3 cursor-pointer">
                                    <input type="checkbox" name="lixo" id="lixo" class="h-5 w-5 rounded accent-primary-500">
                                    <span class="text-sm text-slate-200"><i class="fas fa-trash-alt mr-2 text-primary-400"></i>Lixo retirado</span>
                                </label>
                            </div>
                            <button type="submit" id="scan-btn" disabled class="mt-5 w-full flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white shadow-lg hover:bg-primary-500 active:scale-[0.98] transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                <i class="fas fa-qrcode text-xs"></i> Confirmar Escaneamento
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="rounded-2xl bg-red-500/10 border border-red-500/30 p-5 text-center">
                        <i class="fas fa-exclamation-circle text-2xl text-red-400"></i>
                        <p class="mt-2 text-sm font-semibold text-red-200"><?= $erro ?: 'QR code inválido.' ?></p>
                    </div>
                <?php endif; ?>

                <a href="rondante.php" class="mt-6 block text-center text-xs text-slate-500 hover:text-slate-300 transition-all">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar ao Rondante
                </a>
            </div>
        </div>
    </div>

    <style>
        .glass {
            background: rgba(18, 37, 66, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('scan-form');
            if (!form) return;

            const btn = document.getElementById('scan-btn');
            const gpsMsg = document.getElementById('gps-msg');

            function enableBtn(lat, lng) {
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                gpsMsg.textContent = 'Localização obtida. Você pode confirmar o escaneamento.';
                gpsMsg.classList.remove('text-slate-400');
                gpsMsg.classList.add('text-green-400');
                btn.disabled = false;
            }

            if (!navigator.geolocation) {
                gpsMsg.textContent = 'Seu navegador não suporta geolocalização.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                pos => enableBtn(pos.coords.latitude, pos.coords.longitude),
                err => {
                    gpsMsg.textContent = 'Não foi possível obter a localização. Verifique se o GPS está ativado e tente novamente.';
                    gpsMsg.classList.add('text-red-400');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
