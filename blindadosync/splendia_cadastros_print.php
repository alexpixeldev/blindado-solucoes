<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if (!in_array($usuario_categoria, ['gerente', 'diretor'])) {
    header("Location: index.php");
    exit();
}

$filtro_apartamento = trim($_GET['apartamento'] ?? '');
$filtro_tipo = $_GET['tipo'] ?? '';

$where = "l.edificio_id = 61";
$params = [];
$types = '';

if ($filtro_apartamento !== '') {
    $where .= " AND l.numero_apartamento LIKE ?";
    $params[] = '%' . $filtro_apartamento . '%';
    $types .= 's';
}
if ($filtro_tipo === 'proprietario') {
    $where .= " AND EXISTS (SELECT 1 FROM locacoes_inquilinos p2 WHERE p2.locacao_id = l.id AND p2.locatario_anual = 0)";
} elseif ($filtro_tipo === 'locatario') {
    $where .= " AND EXISTS (SELECT 1 FROM locacoes_inquilinos p2 WHERE p2.locacao_id = l.id AND p2.locatario_anual = 1)";
}

$sql = "SELECT l.*, COUNT(p.id) AS total_pessoas
        FROM locacoes l
        LEFT JOIN locacoes_inquilinos p ON p.locacao_id = l.id
        WHERE " . $where . "
        GROUP BY l.id
        ORDER BY l.data_registro DESC, l.id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $cadastros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $cadastros = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Agrupar pessoas por locação
$pessoasPorLocacao = [];
if (!empty($cadastros)) {
    $ids = implode(',', array_map('intval', array_column($cadastros, 'id')));
    $stmt = $conn->prepare("SELECT locacao_id, nome, documento, locatario_anual FROM locacoes_inquilinos WHERE locacao_id IN ($ids) ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pessoasPorLocacao[$row['locacao_id']][] = $row;
    }
    $stmt->close();
}

$total_cadastros = count($cadastros);
$total_apartamentos = $conn->query("SELECT COUNT(DISTINCT numero_apartamento) AS n FROM locacoes WHERE edificio_id = 61")->fetch_assoc()['n'] ?? 0;
$total_pessoas = $conn->query("SELECT COUNT(*) AS n FROM locacoes_inquilinos qi JOIN locacoes l ON qi.locacao_id = l.id WHERE l.edificio_id = 61")->fetch_assoc()['n'] ?? 0;

$data_geracao = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Cadastros - Edifício Splendia</title>
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
            margin-bottom: 14px;
        }
        .header img { height: 34px; }
        .header h1 { font-size: 18px; color: #14532d; }
        .header .sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .header .right { margin-left: auto; text-align: right; font-size: 11px; color: #64748b; }
        .stats {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }
        .stat {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .stat b { display: block; font-size: 16px; color: #14532d; }
        .stat span { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .filtros {
            font-size: 11px;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 14px;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f1f5f9;
            color: #334155;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 11px;
        }
        .apto { font-weight: bold; color: #14532d; }
        .pessoa { padding: 2px 0; }
        .pessoa .badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 4px;
        }
        .badge-loc { background: #fef3c7; color: #92400e; }
        .badge-prop { background: #dcfce7; color: #166534; }
        .data { white-space: nowrap; color: #64748b; }
        .empty { text-align: center; color: #94a3b8; padding: 30px; font-style: italic; }
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../img/logo_horizontal.png" alt="Blindado Soluções">
        <div>
            <h1>Cadastros de Moradores - Edifício Splendia</h1>
            <div class="sub">Relatório de cadastros enviados pelo formulário</div>
        </div>
        <div class="right">
            <div>Gerado em: <b><?= $data_geracao ?></b></div>
            <div>Responsável: <b><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></b></div>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><b><?= $total_cadastros ?></b><span>Cadastros</span></div>
        <div class="stat"><b><?= $total_apartamentos ?></b><span>Apartamentos</span></div>
        <div class="stat"><b><?= $total_pessoas ?></b><span>Pessoas</span></div>
    </div>

    <?php if ($filtro_apartamento !== '' || $filtro_tipo !== ''): ?>
        <div class="filtros">
            <b>Filtros aplicados:</b>
            <?php if ($filtro_apartamento !== ''): ?> Apartamento contém "<?= htmlspecialchars($filtro_apartamento) ?>"<?php endif; ?>
            <?php if ($filtro_tipo === 'proprietario'): ?> | Com proprietário<?php endif; ?>
            <?php if ($filtro_tipo === 'locatario'): ?> | Com locatário anual<?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cadastros)): ?>
        <div class="empty">Nenhum cadastro encontrado para os filtros selecionados.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Apartamento</th>
                    <th>Moradores</th>
                    <th>Data de envio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cadastros as $cad): ?>
                    <?php $pessoas = $pessoasPorLocacao[$cad['id']] ?? []; ?>
                    <tr>
                        <td class="apto">Apto <?= htmlspecialchars($cad['numero_apartamento']) ?></td>
                        <td>
                            <?php foreach ($pessoas as $p): ?>
                                <div class="pessoa">
                                    <span><?= htmlspecialchars($p['nome']) ?></span>
                                    <?php if ($p['documento']): ?>
                                        <span class="data">• <?= htmlspecialchars($p['documento']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge <?= $p['locatario_anual'] ? 'badge-loc' : 'badge-prop' ?>"><?= $p['locatario_anual'] ? 'Locatário anual' : 'Proprietário' ?></span>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td class="data"><?= date('d/m/Y H:i', strtotime($cad['data_registro'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

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