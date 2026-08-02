<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

// Pode regenerar? Só gerente/diretor (QR é físico/impresso, não pode mudar por acidente).
$pode_regenerar = in_array($usuario_categoria, ['gerente', 'diretor']);

// Regenerar token de um edifício (somente gerente/diretor)
if ($pode_regenerar && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate'])) {
    $id = intval($_POST['regenerate']);
    $token = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("UPDATE edificios SET qr_token = ? WHERE id = ?");
    $stmt->bind_param('si', $token, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: rondante_qrcodes.php");
    exit();
}

// Garantir token para todos os edifícios
$edificios = $conn->query("SELECT e.id, e.nome, b.nome AS nome_base, e.qr_token
                           FROM edificios e JOIN bases b ON e.base_id = b.id
                           ORDER BY b.nome, e.nome")->fetch_all(MYSQLI_ASSOC);

foreach ($edificios as $k => $ed) {
    if (empty($ed['qr_token'])) {
        $token = bin2hex(random_bytes(16));
        $stmt = $conn->prepare("UPDATE edificios SET qr_token = ? WHERE id = ?");
        $stmt->bind_param('si', $token, $ed['id']);
        $stmt->execute();
        $stmt->close();
        $edificios[$k]['qr_token'] = $token;
    }
}

$base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?: 'localhost') . $base_path . '/rondante_scan.php';
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes dos Edifícios | Rondante</title>
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
                        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">QR Codes dos Edifícios</h1>
                        <p class="mt-1 text-slate-500">Cada edifício possui um QR code único. Selecione e salve em PDF para imprimir e fixar nos locais indicados.</p>
                    </div>
                </div>

                <!-- Submenus -->
                <div class="mb-8 flex flex-wrap gap-2 border-b border-slate-200 animate-fade-in">
                    <a href="rondante.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Ronda Atual</a>
                    <a href="rondante_qrcodes.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-primary-500 text-primary-600">QR Codes</a>
                    <a href="rondante_validacao.php" class="px-6 py-3 text-sm font-bold transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-700">Validação de Ronda</a>
                </div>

                <div class="animate-slide-up">
                    <div class="admin-card">
                        <!-- Barra de seleção / PDF -->
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            <button type="button" onclick="selecionarTodos(true)" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition-all">
                                <i class="fas fa-check-double" style="font-size:10px"></i> Selecionar todos
                            </button>
                            <button type="button" onclick="selecionarTodos(false)" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition-all">
                                <i class="fas fa-undo" style="font-size:10px"></i> Limpar seleção
                            </button>
                            <span id="qtd-selecionados" class="text-xs font-bold text-slate-500">0 selecionado(s)</span>
                            <button type="button" onclick="imprimirSelecionados()" class="ml-auto inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-500 transition-all shadow-sm">
                                <i class="fas fa-file-pdf" style="font-size:12px"></i> Salvar PDF dos selecionados
                            </button>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($edificios as $ed): ?>
                                <?php
                                    $qr_url = $base_url . '?token=' . urlencode($ed['qr_token']);
                                ?>
                                <div class="qr-card rounded-2xl border border-slate-200 bg-white p-4 text-center transition-all cursor-pointer select-none hover:border-primary-300 hover:shadow-sm" onclick="toggleCard(this)" data-selected="false">
                                    <label class="inline-flex cursor-pointer items-center gap-2" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="qr-select h-4 w-4 rounded accent-primary-500" onchange="atualizarContador()" onclick="event.stopPropagation()">
                                        <span class="text-xs font-semibold text-slate-500">Selecionar</span>
                                    </label>
                                    <p class="mt-2 text-sm font-bold text-slate-900"><?= htmlspecialchars($ed['nome']) ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($ed['nome_base']) ?></p>
                                    <div class="mx-auto mt-3 flex items-center justify-center qr-render" style="width:300px;height:300px" data-qr='<?= htmlspecialchars($qr_url, ENT_QUOTES) ?>' data-nome='<?= htmlspecialchars($ed['nome'], ENT_QUOTES) ?>' data-base='<?= htmlspecialchars($ed['nome_base'], ENT_QUOTES) ?>'></div>
                                    <?php if ($pode_regenerar): ?>
                                        <form method="POST" class="mt-3" onsubmit="return confirm('ATENÇÃO: o QR code é físico e impresso. Gerar um novo INVALIDARÁ o QR que já estiver colado no local. Deseja continuar?');">
                                            <input type="hidden" name="regenerate" value="<?= $ed['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition-all" title="Gerar novo QR code (invalida o atual)">
                                                <i class="fas fa-sync-alt" style="font-size:10px"></i> Gerar novo
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p class="mt-3 text-[10px] font-semibold uppercase tracking-wider text-slate-300"><i class="fas fa-lock mr-1"></i>QR fixo</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>

    <script src="assets/js/qrcode.js"></script>
    <script>
        function renderQR(el, cell, margin) {
            var url = el.getAttribute('data-qr');
            var qr = qrcode(0, 'M');
            qr.addData(url);
            qr.make();
            el.innerHTML = qr.createImgTag(cell, margin);
            var img = el.querySelector('img');
            if (img) {
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.qr-render').forEach(function(el) {
                renderQR(el, 10, 24);
            });
        });

        function atualizarContador() {
            var n = document.querySelectorAll('.qr-select:checked').length;
            document.getElementById('qtd-selecionados').textContent = n + ' selecionado(s)';
            document.querySelectorAll('.qr-card').forEach(function(card) {
                var on = card.querySelector('.qr-select').checked;
                card.setAttribute('data-selected', on ? 'true' : 'false');
                if (on) {
                    card.classList.add('border-primary-500', 'bg-primary-50', 'ring-2', 'ring-primary-200');
                } else {
                    card.classList.remove('border-primary-500', 'bg-primary-50', 'ring-2', 'ring-primary-200');
                }
            });
        }

        function toggleCard(card) {
            var cb = card.querySelector('.qr-select');
            cb.checked = !cb.checked;
            atualizarContador();
        }

        function selecionarTodos(v) {
            document.querySelectorAll('.qr-select').forEach(function(cb) { cb.checked = v; });
            atualizarContador();
        }

        function imprimirSelecionados() {
            var selecionados = Array.from(document.querySelectorAll('.qr-select:checked')).map(function(cb) {
                return cb.closest('.qr-card').querySelector('.qr-render');
            });
            if (!selecionados.length) {
                alert('Selecione ao menos um QR code.');
                return;
            }
            var scriptPath = location.pathname.substring(0, location.pathname.lastIndexOf('/') + 1) + 'assets/js/qrcode.js';
            var cards = '<div class="qr-print-grid">' + selecionados.map(function(el) {
                return '<div class="qr-print-card">' +
                    '<h2>' + el.getAttribute('data-nome') + '</h2>' +
                    '<p class="base">' + el.getAttribute('data-base') + '</p>' +
                    '<div class="qr-print" data-qr="' + el.getAttribute('data-qr') + '"></div>' +
                    '</div>';
            }).join('') + '</div>';

            var win = window.open('', '_blank');
            win.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>QR Codes - Impressão</title>' +
                '<style>' +
                'body { font-family: Arial, Helvetica, sans-serif; margin: 10mm; }' +
                '.qr-print-grid { display: flex; flex-wrap: wrap; gap: 6mm; }' +
                '.qr-print-card { width: 8cm; text-align: center; page-break-inside: avoid; margin: 0 auto 4mm auto; }' +
                '.qr-print-card h2 { margin: 0 0 1mm 0; font-size: 12pt; color: #111; }' +
                '.qr-print-card p.base { margin: 0 0 2mm 0; font-size: 9pt; color: #555; }' +
                '.qr-print-card .qr-print { margin: 0 auto; }' +
                '.qr-print-card .qr-print img { width: 7cm; height: 7cm; display: block; margin: 0 auto; }' +
                '@media print { @page { size: A4 portrait; margin: 8mm; } }' +
                '</style></head><body>' + cards +
                '<script src="' + scriptPath + '">' + '<\/script>' +
                '<script>' +
                'document.querySelectorAll(".qr-print").forEach(function(el){' +
                'var qr = qrcode(0,"M"); qr.addData(el.getAttribute("data-qr")); qr.make();' +
                'el.innerHTML = qr.createImgTag(14, 30);' +
                '});' +
                'setTimeout(function(){ window.print(); }, 400);' +
                '<\/script>' +
                '</body></html>');
            win.document.close();
        }
    </script>

    <?php include 'components/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>
