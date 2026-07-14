<?php
require_once '../admin/conexao.php';

// Definir cabeçalho para resposta JSON se for AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados básicos
    $edificio_id = $_POST['edificio_id'] ?? null;
    $tipo_usuario = $_POST['user_type'] ?? '';
    $numero_apartamento = $_POST['numero_apartamento'] ?? '';
    $locador_nome = $_POST['locador_nome'] ?? null;
    $locador_ddi = $_POST['locador_ddi'] ?? '';
    $locador_telefone = $_POST['locador_telefone'] ?? null;
    
    // Concatenar DDI e Telefone para salvar no banco se necessário, 
    // ou você pode salvar apenas o telefone se preferir manter a estrutura atual.
    // Aqui vamos concatenar para garantir que o número completo seja salvo.
    if ($locador_telefone) {
        $locador_telefone = $locador_ddi . ' ' . $locador_telefone;
    }

    $data_entrada = $_POST['data_entrada'] ?? null;
    $data_saida = $_POST['data_saida'] ?? null;
    $observacoes = $_POST['observacoes'] ?? '';

    // Converter datas de d/m/Y para Y-m-d (formato do banco)
    if (!empty($data_entrada)) {
        $dt = DateTime::createFromFormat('d/m/Y', $data_entrada);
        $data_entrada = $dt ? $dt->format('Y-m-d') : null;
    } else {
        $data_entrada = null;
    }
    
    if (!empty($data_saida)) {
        $dt = DateTime::createFromFormat('d/m/Y', $data_saida);
        $data_saida = $dt ? $dt->format('Y-m-d') : null;
    } else {
        $data_saida = null;
    }

    // Validação básica
    if (!$edificio_id || !$numero_apartamento) {
        if ($is_ajax) {
            echo json_encode(['status' => 'error', 'message' => 'Edifício e Apartamento são obrigatórios.']);
            exit;
        }
        die("Erro: Edifício e Apartamento são obrigatórios.");
    }

    // 1. Inserir na tabela principal (locacoes)
    $data_locacao = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO locacoes (edificio_id, tipo_usuario, numero_apartamento, locador_nome, locador_telefone, data_entrada, data_saida, observacoes, data_locacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $edificio_id, $tipo_usuario, $numero_apartamento, $locador_nome, $locador_telefone, $data_entrada, $data_saida, $observacoes, $data_locacao);
    
    if ($stmt->execute()) {
        $locacao_id = $stmt->insert_id;

        // 2. Inserir Inquilinos
        if (isset($_POST['inquilinos']) && is_array($_POST['inquilinos'])) {
            $stmt_inq = $conn->prepare("INSERT INTO locacoes_inquilinos (locacao_id, nome, documento, telefone) VALUES (?, ?, ?, ?)");
            foreach ($_POST['inquilinos'] as $inquilino) {
                if (!empty($inquilino['nome'])) {
                    $tel_inq = $inquilino['telefone'] ?? null;
                    $stmt_inq->bind_param("isss", $locacao_id, $inquilino['nome'], $inquilino['documento'], $tel_inq);
                    $stmt_inq->execute();
                }
            }
        }

        // 3. Inserir Veículos
        if (isset($_POST['veiculos']) && is_array($_POST['veiculos'])) {
            $stmt_vei = $conn->prepare("INSERT INTO locacoes_veiculos (locacao_id, modelo, cor, placa, acesso_garagem) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['veiculos'] as $veiculo) {
                if (!empty($veiculo['modelo'])) {
                    $stmt_vei->bind_param("issss", $locacao_id, $veiculo['modelo'], $veiculo['cor'], $veiculo['placa'], $veiculo['acesso_garagem']);
                    $stmt_vei->execute();
                }
            }
        }

        if ($is_ajax) {
            echo json_encode(['status' => 'success', 'locacao_id' => $locacao_id]);
        } else {
            header("Location: sucesso.php");
        }
    } else {
        if ($is_ajax) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar no banco de dados: ' . $conn->error]);
        } else {
            echo "Erro ao salvar: " . $conn->error;
        }
    }
}
?>
