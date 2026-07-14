<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

if (!in_array($_SESSION['usuario_categoria'], ['supervisor', 'gerente'])) {
    header("Location: controle_dados.php");
    exit();
}

$tipo = $_GET['tipo'] ?? 'faciais';
$id_raw = $_GET['id'] ?? null;
$id = $id_raw; // Aceitar string para ramais (e1, b1) e int para outros
$mensagem = '';
$dados = [];

// Buscar edifícios e bases para seleção unificada
$edificios = $conn->query("SELECT id, nome, 'edificio' as tipo_origem FROM edificios ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$bases = $conn->query("SELECT id, nome, 'base' as tipo_origem FROM bases ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

// Mesclar e ordenar
$lista_selecao = array_merge($edificios, $bases);
usort($lista_selecao, function($a, $b) { return strcmp($a['nome'], $b['nome']); });

$categorias_ramais = $conn->query("SELECT id, nome FROM categorias_ramais ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $status = $_POST['status'] ?? 'ativo';
    $observacao = $_POST['observacao'] ?? '';
    
    // Lógica para identificar se selecionou Edifício ou Base
    $selecao_raw = $_POST['selecao_id'] ?? '';
    $parts = explode('|', $selecao_raw);
    $target_id = $parts[0] ?? null;
    $target_type = $parts[1] ?? 'edificio';

    $edificio_id = ($target_type === 'edificio') ? $target_id : null;
    $base_id = ($target_type === 'base') ? $target_id : null;

    if ($tipo === 'faciais') {
        $marca = $_POST['marca_equipamento'];
        $ips = $_POST['ip'] ?? [];
        $obs = $_POST['obs'] ?? [];
        $acessos_array = [];
        for ($i = 0; $i < count($ips); $i++) {
            if (!empty($ips[$i])) $acessos_array[] = ['ip' => $ips[$i], 'obs' => $obs[$i] ?? ''];
        }
        $acessos_json = json_encode($acessos_array);
        if ($id) {
            $stmt = $conn->prepare("UPDATE controle_faciais SET edificio_id=?, marca_equipamento=?, acessos=?, status=?, observacao=?, usuario_id=? WHERE id=?");
            $stmt->bind_param("issssii", $edificio_id, $marca, $acessos_json, $status, $observacao, $usuario_id, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO controle_faciais (edificio_id, marca_equipamento, acessos, status, observacao, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $edificio_id, $marca, $acessos_json, $status, $observacao, $usuario_id);
        }
    } elseif ($tipo === 'ata') {
        $marcas = $_POST['marca_modelo'] ?? [];
        $ips = $_POST['ip'] ?? [];
        $descs = $_POST['descricao'] ?? [];
        $users = $_POST['usuario'] ?? [];
        $senhas = $_POST['senha'] ?? [];
        $itens_array = [];
        for ($i = 0; $i < count($marcas); $i++) {
            if (!empty($marcas[$i])) {
                $itens_array[] = [
                    'marca_modelo' => $marcas[$i],
                    'ip' => $ips[$i] ?? '',
                    'descricao' => $descs[$i] ?? '',
                    'usuario' => $users[$i] ?? '',
                    'senha' => $senhas[$i] ?? ''
                ];
            }
        }
        $itens_json = json_encode($itens_array);
        if ($id) {
            $stmt = $conn->prepare("UPDATE controle_ata SET edificio_id=?, itens_ata=?, status=?, observacao=?, usuario_id=? WHERE id=?");
            $stmt->bind_param("isssii", $edificio_id, $itens_json, $status, $observacao, $usuario_id, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO controle_ata (edificio_id, itens_ata, status, observacao, usuario_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $edificio_id, $itens_json, $status, $observacao, $usuario_id);
        }
    } elseif ($tipo === 'radio_fibra') {
        $ip = $_POST['ip'];
        $local = $_POST['local_detalhe'];
        $modo = $_POST['modo'];
        $marca = $_POST['marca'];
        $modelo = $_POST['modelo'];
        $login = $_POST['login'];
        $senha = $_POST['senha'];
        $is_pop = isset($_POST['is_pop']) ? 1 : 0;
        $pop_id = !empty($_POST['pop_responsavel_id']) ? $_POST['pop_responsavel_id'] : null;
        if ($id) {
            $stmt = $conn->prepare("UPDATE controle_radio_fibra SET edificio_id=?, base_id=?, ip=?, local_detalhe=?, modo=?, marca=?, modelo=?, login=?, senha=?, is_pop=?, pop_responsavel_id=?, status=?, observacao=?, usuario_id=? WHERE id=?");
            $stmt->bind_param("iisssssssiisssi", $edificio_id, $base_id, $ip, $local, $modo, $marca, $modelo, $login, $senha, $is_pop, $pop_id, $status, $observacao, $usuario_id, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO controle_radio_fibra (edificio_id, base_id, ip, local_detalhe, modo, marca, modelo, login, senha, is_pop, pop_responsavel_id, status, observacao, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssssiisssi", $edificio_id, $base_id, $ip, $local, $modo, $marca, $modelo, $login, $senha, $is_pop, $pop_id, $status, $observacao, $usuario_id);
        }
    } elseif ($tipo === 'dvr') {
        $ip_dom = $_POST['ip_dominio'];
        $cloud = $_POST['cloud'];
        $p_tcp = $_POST['porta_tcp'];
        $p_http = $_POST['porta_http'];
        $login = $_POST['login'];
        $senha = $_POST['senha'];
        $modelo = $_POST['modelo'];
        $s_mibo = $_POST['senha_mibo'];
        if ($id) {
            $stmt = $conn->prepare("UPDATE controle_dvr SET edificio_id=?, base_id=?, ip_dominio=?, cloud=?, porta_tcp=?, porta_http=?, login=?, senha=?, modelo=?, senha_mibo=?, status=?, observacao=?, usuario_id=? WHERE id=?");
            $stmt->bind_param("iissssssssssii", $edificio_id, $base_id, $ip_dom, $cloud, $p_tcp, $p_http, $login, $senha, $modelo, $s_mibo, $status, $observacao, $usuario_id, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO controle_dvr (edificio_id, base_id, ip_dominio, cloud, porta_tcp, porta_http, login, senha, modelo, senha_mibo, status, observacao, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssssssss", $edificio_id, $base_id, $ip_dom, $cloud, $p_tcp, $p_http, $login, $senha, $modelo, $s_mibo, $status, $observacao, $usuario_id);
        }
    } elseif ($tipo === 'ips') {
        $estacao = $_POST['estacao'];
        $ip = $_POST['ip'];
        $nums = $_POST['ramal_num'] ?? [];
        $sens = $_POST['ramal_senha'] ?? [];
        $ramais_array = [];
        for ($i = 0; $i < count($nums); $i++) {
            if (!empty($nums[$i])) $ramais_array[] = ['numero' => $nums[$i], 'senha' => $sens[$i] ?? ''];
        }
        $ramais_json = json_encode($ramais_array);
        if ($id) {
            $stmt = $conn->prepare("UPDATE controle_ips SET base_id=?, edificio_id=?, estacao=?, ip=?, ramais=?, status=?, observacao=?, usuario_id=? WHERE id=?");
            $stmt->bind_param("iisssssii", $base_id, $edificio_id, $estacao, $ip, $ramais_json, $status, $observacao, $usuario_id, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO controle_ips (base_id, edificio_id, estacao, ip, ramais, status, observacao, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssssi", $base_id, $edificio_id, $estacao, $ip, $ramais_json, $status, $observacao, $usuario_id);
        }
    } elseif ($tipo === 'ramais') {
        $numeros = $_POST['numero_ramal'] ?? [];
        $categorias = $_POST['categoria_id'] ?? [];
        
        // Se estiver editando um grupo existente, limpar os ramais antigos para esse local
        if ($id) {
            if (strpos($id, 'e') === 0) {
                $eid = intval(substr($id, 1));
                $conn->query("DELETE FROM controle_ramais WHERE edificio_id = $eid");
            } elseif (strpos($id, 'b') === 0) {
                $bid = intval(substr($id, 1));
                $conn->query("DELETE FROM controle_ramais WHERE base_id = $bid");
            }
        }
        
        // Inserir os novos ramais
        $stmt = $conn->prepare("INSERT INTO controle_ramais (edificio_id, base_id, numero_ramal, categoria_id, status, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < count($numeros); $i++) {
            if (!empty($numeros[$i])) {
                $num = $numeros[$i];
                $cat = $categorias[$i] ?: null;
                $stmt->bind_param("iisssi", $edificio_id, $base_id, $num, $cat, $status, $usuario_id);
                $stmt->execute();
            }
        }
        header("Location: controle_dados.php?tipo=$tipo&msg=sucesso");
        exit();
    }

    if (isset($stmt) && $stmt->execute()) {
        header("Location: controle_dados.php?tipo=$tipo&msg=sucesso");
        exit();
    } else {
        $mensagem = "Erro ao salvar: " . $conn->error;
    }
}

if ($id) {
    $tabela = "controle_" . $tipo;
    if ($tipo === 'ramais') {
        if (strpos($id, 'e') === 0) {
            $eid = intval(substr($id, 1));
            $res = $conn->query("SELECT * FROM controle_ramais WHERE edificio_id = $eid");
        } elseif (strpos($id, 'b') === 0) {
            $bid = intval(substr($id, 1));
            $res = $conn->query("SELECT * FROM controle_ramais WHERE base_id = $bid");
        } else {
            $res = $conn->query("SELECT * FROM controle_ramais WHERE id = " . intval($id));
        }
        $ramais_lista = $res->fetch_all(MYSQLI_ASSOC);
        if (!empty($ramais_lista)) {
            $dados = $ramais_lista[0];
            $dados['ramais_múltiplos'] = $ramais_lista;
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM $tabela WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $dados = $stmt->get_result()->fetch_assoc();
        if (isset($dados['acessos'])) $dados['acessos_lista'] = json_decode($dados['acessos'], true) ?? [];
        if (isset($dados['itens_ata'])) $dados['itens_lista'] = json_decode($dados['itens_ata'], true) ?? [];
        if (isset($dados['ramais'])) $dados['ramais_lista'] = json_decode($dados['ramais'], true) ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Dados | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' }
                    }
                }
            }
        }
    </script>
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
                <div class="mb-8 animate-fade-in">
                    <div class="flex items-center gap-4">
                        <a href="controle_dados.php?tipo=<?= $tipo ?>" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary-600 hover:border-primary-200 transition-all"><i class="fas fa-arrow-left"></i></a>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl"><?= $id ? 'Editar' : 'Novo' ?> Registro: <?= ucfirst($tipo) ?></h1>
                            <p class="mt-1 text-slate-500">Preencha as informações técnicas abaixo.</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl flex items-start gap-3 animate-fade-in">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div class="text-sm font-medium"><?= $mensagem ?></div>
                    </div>
                <?php endif; ?>

                <div class="max-w-4xl animate-slide-up">
                    <div class="admin-card">
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="form-label">Local (Edifício ou Base) *</label>
                                    <select name="selecao_id" class="form-input appearance-none" required>
                                        <option value="">Selecione o Local</option>
                                        <?php foreach ($lista_selecao as $item): ?>
                                            <?php 
                                                $val = $item['id'] . '|' . $item['tipo_origem'];
                                                $selected = '';
                                                if ($item['tipo_origem'] === 'edificio' && ($dados['edificio_id'] ?? 0) == $item['id']) $selected = 'selected';
                                                if ($item['tipo_origem'] === 'base' && ($dados['base_id'] ?? 0) == $item['id']) $selected = 'selected';
                                            ?>
                                            <option value="<?= $val ?>" <?= $selected ?>><?= htmlspecialchars($item['nome']) ?> (<?= ucfirst($item['tipo_origem']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-input appearance-none">
                                        <option value="ativo" <?= ($dados['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="inativo" <?= ($dados['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>

                            <?php if ($tipo === 'faciais'): ?>
                                <div class="space-y-4 pt-4 border-t border-slate-100">
                                    <div class="space-y-2">
                                        <label class="form-label">Marca do Equipamento</label>
                                        <input type="text" name="marca_equipamento" class="form-input" value="<?= htmlspecialchars($dados['marca_equipamento'] ?? '') ?>">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="form-label">Acessos (IP e Observação)</label>
                                        <div id="container-acessos" class="space-y-3">
                                            <?php 
                                            $acessos = $dados['acessos_lista'] ?? [];
                                            if (empty($acessos)) {
                                                $acessos = [['ip' => '', 'obs' => '']];
                                            }
                                            foreach ($acessos as $idx => $ac): 
                                            ?>
                                                <div class="flex gap-3">
                                                    <input type="text" name="ip[]" class="form-input flex-1" placeholder="IP" value="<?= htmlspecialchars($ac['ip']) ?>">
                                                    <input type="text" name="obs[]" class="form-input flex-[2]" placeholder="Observação" value="<?= htmlspecialchars($ac['obs']) ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" onclick="addAcesso()" class="text-xs font-bold text-primary-600 hover:text-primary-700 mt-2"><i class="fas fa-plus mr-1"></i> Adicionar mais um acesso</button>
                                    </div>
                                </div>
                            <?php elseif ($tipo === 'ata'): ?>
                                <div class="space-y-4 pt-4 border-t border-slate-100">
                                    <div id="container-ata" class="space-y-6">
                                        <?php 
                                        $itens = $dados['itens_lista'] ?? [];
                                        // Filtrar apenas itens com marca_modelo não vazio e reindexar
                                        $itens = array_values(array_filter($itens, function($item) {
                                            return !empty($item['marca_modelo']);
                                        }));
                                        if (empty($itens)) {
                                            $itens = [['marca_modelo' => '', 'ip' => '', 'descricao' => '', 'usuario' => '', 'senha' => '']];
                                        }
                                        foreach ($itens as $idx => $it): 
                                        ?>
                                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-4">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Marca/Modelo</label><input type="text" name="marca_modelo[]" class="form-input" value="<?= htmlspecialchars($it['marca_modelo']) ?>"></div>
                                                    <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">IP</label><input type="text" name="ip[]" class="form-input" value="<?= htmlspecialchars($it['ip']) ?>"></div>
                                                    <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Descrição</label><input type="text" name="descricao[]" class="form-input" value="<?= htmlspecialchars($it['descricao']) ?>"></div>
                                                    <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Usuário</label><input type="text" name="usuario[]" class="form-input" value="<?= htmlspecialchars($it['usuario']) ?>"></div>
                                                    <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Senha</label><input type="text" name="senha[]" class="form-input" value="<?= htmlspecialchars($it['senha']) ?>"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" onclick="addAta()" class="text-xs font-bold text-primary-600 hover:text-primary-700 mt-2"><i class="fas fa-plus mr-1"></i> Adicionar mais um item ATA</button>
                                </div>
                            <?php elseif ($tipo === 'radio_fibra'): ?>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 pt-4 border-t border-slate-100">
                                    <div class="space-y-2"><label class="form-label">IP</label><input type="text" name="ip" class="form-input" value="<?= htmlspecialchars($dados['ip'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Local Detalhe</label><input type="text" name="local_detalhe" class="form-input" value="<?= htmlspecialchars($dados['local_detalhe'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Modo</label><input type="text" name="modo" class="form-input" value="<?= htmlspecialchars($dados['modo'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Marca</label><input type="text" name="marca" class="form-input" value="<?= htmlspecialchars($dados['marca'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-input" value="<?= htmlspecialchars($dados['modelo'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Login</label><input type="text" name="login" class="form-input" value="<?= htmlspecialchars($dados['login'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Senha</label><input type="text" name="senha" class="form-input" value="<?= htmlspecialchars($dados['senha'] ?? '') ?>"></div>
                                    <div class="flex items-center gap-3 pt-4">
                                        <input type="checkbox" name="is_pop" id="is_pop" class="h-5 w-5 rounded border-slate-300 text-primary-600 focus:ring-primary-500" <?= ($dados['is_pop'] ?? 0) ? 'checked' : '' ?>>
                                        <label for="is_pop" class="text-sm font-bold text-slate-700">Este local é um POP?</label>
                                    </div>
                                </div>
                            <?php elseif ($tipo === 'dvr'): ?>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 pt-4 border-t border-slate-100">
                                    <div class="space-y-2"><label class="form-label">IP / Domínio</label><input type="text" name="ip_dominio" class="form-input" value="<?= htmlspecialchars($dados['ip_dominio'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Cloud</label><input type="text" name="cloud" class="form-input" value="<?= htmlspecialchars($dados['cloud'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Porta TCP</label><input type="text" name="porta_tcp" class="form-input" value="<?= htmlspecialchars($dados['porta_tcp'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Porta HTTP</label><input type="text" name="porta_http" class="form-input" value="<?= htmlspecialchars($dados['porta_http'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Login</label><input type="text" name="login" class="form-input" value="<?= htmlspecialchars($dados['login'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Senha</label><input type="text" name="senha" class="form-input" value="<?= htmlspecialchars($dados['senha'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Modelo</label><input type="text" name="modelo" class="form-input" value="<?= htmlspecialchars($dados['modelo'] ?? '') ?>"></div>
                                    <div class="space-y-2"><label class="form-label">Senha Mibo</label><input type="text" name="senha_mibo" class="form-input" value="<?= htmlspecialchars($dados['senha_mibo'] ?? '') ?>"></div>
                                </div>
                            <?php elseif ($tipo === 'ips'): ?>
                                <div class="space-y-4 pt-4 border-t border-slate-100">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-2"><label class="form-label">Estação</label><input type="text" name="estacao" class="form-input" value="<?= htmlspecialchars($dados['estacao'] ?? '') ?>"></div>
                                        <div class="space-y-2"><label class="form-label">IP</label><input type="text" name="ip" class="form-input" value="<?= htmlspecialchars($dados['ip'] ?? '') ?>"></div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="form-label">Ramais (Número e Senha)</label>
                                        <div id="container-ramais" class="space-y-3">
                                            <?php 
                                            $ramais = $dados['ramais_lista'] ?? [];
                                            if (empty($ramais)) {
                                                $ramais = [['numero' => '', 'senha' => '']];
                                            }
                                            foreach ($ramais as $idx => $rm): 
                                            ?>
                                                <div class="flex gap-3">
                                                    <input type="text" name="ramal_num[]" class="form-input flex-1" placeholder="Número" value="<?= htmlspecialchars($rm['numero']) ?>">
                                                    <input type="text" name="ramal_senha[]" class="form-input flex-1" placeholder="Senha" value="<?= htmlspecialchars($rm['senha']) ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" onclick="addRamal()" class="text-xs font-bold text-primary-600 hover:text-primary-700 mt-2"><i class="fas fa-plus mr-1"></i> Adicionar mais um ramal</button>
                                    </div>
                                </div>
                            <?php elseif ($tipo === 'ramais'): ?>
                                <div class="space-y-4 pt-4 border-t border-slate-100">
                                    <div id="container-ramais-multiplos" class="space-y-4">
                                        <?php 
                                        $ramais_multi = $dados['ramais_múltiplos'] ?? [];
                                        if (empty($ramais_multi)) {
                                            $ramais_multi = [['numero_ramal' => '', 'categoria_id' => '']];
                                        }
                                        foreach ($ramais_multi as $idx => $rm): 
                                        ?>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                                                <div class="space-y-2">
                                                    <label class="text-[10px] font-bold uppercase text-slate-400">Número do Ramal</label>
                                                    <input type="text" name="numero_ramal[]" class="form-input" value="<?= htmlspecialchars($rm['numero_ramal']) ?>">
                                                </div>
                                                <div class="space-y-2">
                                                    <label class="text-[10px] font-bold uppercase text-slate-400">Categoria do Ramal</label>
                                                    <select name="categoria_id[]" class="form-input appearance-none">
                                                        <option value="">Selecione a Categoria</option>
                                                        <?php foreach ($categorias_ramais as $cat): ?>
                                                            <option value="<?= $cat['id'] ?>" <?= $rm['categoria_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" onclick="addRamalMultiplo()" class="text-xs font-bold text-primary-600 hover:text-primary-700 mt-2"><i class="fas fa-plus mr-1"></i> Adicionar mais um ramal</button>
                                </div>
                            <?php endif; ?>

                            <?php if ($tipo !== 'ramais'): ?>
                            <div class="space-y-2 pt-4 border-t border-slate-100">
                                <label class="form-label">Observações Gerais</label>
                                <textarea name="observacao" class="form-input min-h-[100px]"><?= htmlspecialchars($dados['observacao'] ?? '') ?></textarea>
                            </div>
                            <?php endif; ?>

                            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="btn-primary flex-1"><i class="fas fa-save"></i><span>Salvar Dados</span></button>
                                <a href="controle_dados.php?tipo=<?= $tipo ?>" class="btn-secondary text-center"><span>Cancelar</span></a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
            <footer class="border-t border-slate-200 bg-white p-4 text-center text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Blindado Soluções. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>
    <script>
        function addAcesso() {
            const container = document.getElementById('container-acessos');
            const div = document.createElement('div');
            div.className = 'flex gap-3';
            div.innerHTML = '<input type="text" name="ip[]" class="form-input flex-1" placeholder="IP"><input type="text" name="obs[]" class="form-input flex-[2]" placeholder="Observação">';
            container.appendChild(div);
        }
        function addAta() {
            const container = document.getElementById('container-ata');
            const div = document.createElement('div');
            div.className = 'p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-4';
            div.innerHTML = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Marca/Modelo</label><input type="text" name="marca_modelo[]" class="form-input"></div>
                <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">IP</label><input type="text" name="ip[]" class="form-input"></div>
                <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Descrição</label><input type="text" name="descricao[]" class="form-input"></div>
                <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Usuário</label><input type="text" name="usuario[]" class="form-input"></div>
                <div class="space-y-1"><label class="text-[10px] font-bold uppercase text-slate-400">Senha</label><input type="text" name="senha[]" class="form-input"></div>
            </div>`;
            container.appendChild(div);
        }
        function addRamal() {
            const container = document.getElementById('container-ramais');
            const div = document.createElement('div');
            div.className = 'flex gap-3';
            div.innerHTML = '<input type="text" name="ramal_num[]" class="form-input flex-1" placeholder="Número"><input type="text" name="ramal_senha[]" class="form-input flex-1" placeholder="Senha">';
            container.appendChild(div);
        }
        function addRamalMultiplo() {
            const container = document.getElementById('container-ramais-multiplos');
            const div = document.createElement('div');
            div.className = 'grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100';
            div.innerHTML = `
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase text-slate-400">Número do Ramal</label>
                    <input type="text" name="numero_ramal[]" class="form-input">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase text-slate-400">Categoria do Ramal</label>
                    <select name="categoria_id[]" class="form-input appearance-none">
                        <option value="">Selecione a Categoria</option>
                        <?php foreach ($categorias_ramais as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>`;
            container.appendChild(div);
        }
    </script>
    <?php include 'components/footer.php'; ?>
</body>
</html>
