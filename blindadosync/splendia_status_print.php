<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

// Apartamentos do Edifício Splendia
$apartamentos = [
    '401','402','403','404','501','502','503','504','601','602','603','604',
    '701','702','703','704','801','802','803','804','901','902','903','904',
    '1001','1002','1003','1004','1101','1102','1103','1104','1201','1202','1203','1204',
    '1301','1302','1303','1304','1401','1402','1403','1404','1501','1502','1503','1504'
];

// Apartamentos que já enviaram cadastro
$enviados = [];
$rEnviados = $conn->query("SELECT DISTINCT numero_apartamento FROM locacoes WHERE edificio_id = 61");
$apartamentosSet = array_flip($apartamentos);
while ($row = $rEnviados->fetch_assoc()) {
    if (isset($apartamentosSet[$row['numero_apartamento']])) {
        $enviados[] = $row['numero_apartamento'];
    }
}
$enviadosSet = array_flip($enviados);

$nao_enviados = array_values(array_filter($apartamentos, function ($apt) use ($enviadosSet) {
    return !isset($enviadosSet[$apt]);
}));

// Agrupar por andar
$porAndar = [];
foreach ($apartamentos as $apt) {
    $andar = strlen($apt) === 3 ? substr($apt, 0, 1) : substr($apt, 0, 2);
    $porAndar[$andar][] = $apt;
}
ksort($porAndar);

$data_geracao = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Status dos Apartamentos - Edifício Splendia</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            padding: 10mm;
            font-size: 12px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 3px solid #16a34a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header img { height: 34px; }
        .header h1 { font-size: 18px; color: #14532d; }
        .header .sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .header .right { margin-left: auto; text-align: right; font-size: 11px; color: #64748b; }
        .stats {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .stat {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .stat b { display: block; font-size: 16px; }
        .stat span { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .stat.enviados b { color: #166534; }
        .stat.pendentes b { color: #b91c1c; }
        .legenda {
            display: flex;
            gap: 16px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 12px;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
        }
        .legenda .item { display: inline-flex; align-items: center; gap: 6px; }
        .dot { display: inline-block; width: 12px; height: 12px; border-radius: 3px; }
        .dot-ok { background: #16a34a; }
        .dot-faltando { background: #dc2626; }
        .andar { margin-bottom: 14px; page-break-inside: avoid; }
        .status-table { width: 100%; border-collapse: separate; border-spacing: 0 6px; table-layout: fixed; }
        .status-table td {
            padding: 9px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            border-radius: 6px;
        }
        .status-table td.andar-label {
            width: 70px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            background: transparent;
            border: none;
            font-weight: bold;
            vertical-align: middle;
        }
        .status-table td.apto.ok { background: #dcfce7; border: 2px solid #16a34a; color: #14532d; }
        .status-table td.apto.faltando { background: #fee2e2; border: 2px solid #dc2626; color: #7f1d1d; }
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../img/logo_horizontal.png" alt="Blindado Soluções">
        <div>
            <h1>Status dos Apartamentos - Edifício Splendia</h1>
            <div class="sub">Controle de envio dos cadastros de moradores</div>
        </div>
        <div class="right">
            <div>Gerado em: <b><?= $data_geracao ?></b></div>
            <div>Responsável: <b><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></b></div>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><b><?= count($apartamentos) ?></b><span>Apartamentos</span></div>
        <div class="stat enviados"><b><?= count($enviados) ?></b><span>Enviaram</span></div>
        <div class="stat pendentes"><b><?= count($nao_enviados) ?></b><span>Pendentes</span></div>
    </div>

    <div class="legenda">
        <span class="item"><span class="dot dot-ok"></span> Enviou cadastro</span>
        <span class="item"><span class="dot dot-faltando"></span> Ainda não enviou</span>
    </div>

    <?php foreach ($porAndar as $andar => $aptosAndar): ?>
        <div class="andar">
            <table class="status-table">
                <tbody>
                    <tr>
                        <td class="andar-label"><?= $andar ?>º Andar</td>
                        <?php foreach ($aptosAndar as $apt): ?>
                            <?php $enviou = isset($enviadosSet[$apt]); ?>
                            <td class="apto <?= $enviou ? 'ok' : 'faltando' ?>"><?= $apt ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="footer">
        <p>&copy; <?= date('Y') ?> Blindado Soluções - Edifício Splendia</p>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>