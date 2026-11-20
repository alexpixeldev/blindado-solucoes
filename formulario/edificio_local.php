<?php
/**
 * Resolve o edifício usado no banco local a partir do cliente do Moni.
 *
 * O formulário trabalha apenas com os clientes do Moni, mas a tabela locacoes
 * tem foreign key para edificios.id. Esta função reaproveita o edifício local
 * de mesmo nome quando ele existe e cria um registro mínimo quando não existe.
 * Ela não altera a lista exibida no passo 2.
 */

function f2_edif_norm(string $s): string
{
    $s = mb_strtoupper(trim($s), 'UTF-8');
    $mapa = [
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
        'É' => 'E', 'Ê' => 'E', 'È' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Î' => 'I', 'Ì' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ò' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Û' => 'U', 'Ù' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N', 'Ý' => 'Y',
    ];
    return preg_replace('/[^A-Z0-9]/', '', strtr($s, $mapa));
}

function f2_resolver_edificio_local($conn, $edificioId, $edificioNome): int
{
    $edificioId = (int) $edificioId;
    $nome = trim((string) $edificioNome);
    $chave = f2_edif_norm($nome);

    /* 1. Edifício local com o mesmo nome (ex.: CALIFÓRNIA do Moni -> Califórnia local). */
    if ($chave !== '') {
        $res = @$conn->query("SELECT id, nome FROM edificios");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (f2_edif_norm((string) $row['nome']) === $chave) {
                    return (int) $row['id'];
                }
            }
        }
    }

    /* 2. Formulários antigos enviam só o ID local, sem edificio_nome. */
    if ($nome === '' && $edificioId > 0) {
        $stmt = @$conn->prepare("SELECT id FROM edificios WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $edificioId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return (int) $row['id'];
            }
        }
    }

    /* 3. Cliente do Moni ainda sem edifício local: cria um registro mínimo. */
    if ($nome === '') {
        return 0;
    }

    $baseId = 1; // Nova Guaraparí
    $res = @$conn->query("SELECT id FROM bases ORDER BY id ASC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $baseId = (int) $row['id'];
    }

    $stmt = @$conn->prepare("INSERT INTO edificios (nome, base_id) VALUES (?, ?)");
    if (!$stmt) {
        error_log('[edificio_local] falha ao preparar insert: ' . $conn->error);
        return 0;
    }
    $stmt->bind_param('si', $nome, $baseId);
    if (!$stmt->execute()) {
        error_log('[edificio_local] falha ao criar edifício ' . $nome . ': ' . $stmt->error);
        $stmt->close();
        return 0;
    }
    $novoId = (int) $conn->insert_id;
    $stmt->close();
    return $novoId;
}
