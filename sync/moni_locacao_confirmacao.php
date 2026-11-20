<?php
/**
 * Envio da locação confirmada ao Moni.
 *
 * Chamado no momento da confirmação (listar_locacoes.php) e também pela página
 * de confirmadas, para reenviar um registro que já estava confirmado.
 * Guarda o resultado em locacoes.moni_envio para evitar duplicatas.
 */

require_once __DIR__ . '/../formulario/moni_locacao.php';

function locmoni_garantir_colunas($conn): void
{
    foreach ([
        'cliente_moni_id' => 'INT NULL DEFAULT NULL',
        'moni_envio' => 'TEXT NULL',
    ] as $coluna => $definicao) {
        $chk = @$conn->query("SHOW COLUMNS FROM locacoes LIKE '$coluna'");
        if ($chk && $chk->num_rows === 0) {
            @$conn->query("ALTER TABLE locacoes ADD COLUMN $coluna $definicao");
        }
    }
}

function locmoni_norm(string $s): string
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

/**
 * Cliente do Moni:usa o ID gravado no salvamento; para registros antigos,
 * localiza o cliente pelo nome do edifício.
 */
function locmoni_cliente_id($conn, array $locacao): int
{
    $id = (int) ($locacao['cliente_moni_id'] ?? 0);
    if ($id > 0) {
        return $id;
    }

    $nome = trim((string) ($locacao['nome_edificio'] ?? ''));
    if ($nome === '') {
        return 0;
    }

    try {
        $api = new MoniApi();
        $res = $api->customers('', true, 1, 500);
        if (!$res['ok']) {
            return 0;
        }
        $alvo = locmoni_norm($nome);
        foreach ($res['data'] as $cliente) {
            if (locmoni_norm((string) ($cliente['name'] ?? '')) === $alvo) {
                return (int) $cliente['id'];
            }
        }
    } catch (Throwable $ex) {
        error_log('[moni_confirmacao] cliente por nome: ' . $ex->getMessage());
    }
    return 0;
}

function locmoni_carregar($conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT l.*, e.nome AS nome_edificio
        FROM locacoes l LEFT JOIN edificios e ON l.edificio_id = e.id
        WHERE l.id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$loc) {
        return null;
    }

    $loc['inquilinos'] = [];
    $stmt = $conn->prepare("SELECT nome, documento, telefone, selfie FROM locacoes_inquilinos WHERE locacao_id = ? ORDER BY id");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $loc['inquilinos'][] = $row;
        }
        $stmt->close();
    }

    $loc['veiculos'] = [];
    $stmt = $conn->prepare("SELECT modelo, cor, placa, acesso_garagem FROM locacoes_veiculos WHERE locacao_id = ? ORDER BY id");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $loc['veiculos'][] = $row;
        }
        $stmt->close();
    }

    return $loc;
}

/** Caminho absoluto da selfie gravada pelo formulário. */
function locmoni_selfie_path(string $selfie): string
{
    $s = trim($selfie);
    if ($s === '') {
        return '';
    }
    $raiz = dirname(__DIR__); // public_html
    $candidatos = [];
    if (strpos($s, '../') === 0) {
        $candidatos[] = $raiz . '/' . substr($s, 3);
    }
    if (isset($s[0]) && $s[0] === '/') {
        $candidatos[] = $s;
    }
    $candidatos[] = __DIR__ . '/' . ltrim($s, './');
    $candidatos[] = $raiz . '/' . ltrim($s, './');
    foreach ($candidatos as $c) {
        if (is_file($c)) {
            return $c;
        }
    }
    return '';
}

function locmoni_data(string $valor): string
{
    $v = trim($valor);
    if ($v === '') {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $v) ?: DateTime::createFromFormat('d/m/Y', $v);
    return $dt ? $dt->format('d/m/Y') : $v;
}

/**
 * Envia a locação ao Moni. Já enviada sem erro não é reenviada, a menos que
 * $forcar seja true (botão de reenvio na página de confirmadas).
 */
function locmoni_enviar_locacao($conn, int $id, bool $forcar = false): array
{
    locmoni_garantir_colunas($conn);
    $loc = locmoni_carregar($conn, $id);
    if (!$loc) {
        return ['ok' => false, 'erro' => 'Locação não encontrada.'];
    }

    if (!$forcar) {
        $anterior = json_decode((string) ($loc['moni_envio'] ?? ''), true);
        if (is_array($anterior) && !empty($anterior['ok']) && !empty($anterior['visitantes'])) {
            return ['ok' => true, 'ja_enviado' => true, 'visitantes' => $anterior['visitantes']];
        }
    }

    $cliente = locmoni_cliente_id($conn, $loc);
    if ($cliente <= 0) {
        $erro = 'Cliente do Moni não identificado para o edifício "' . (string) ($loc['nome_edificio'] ?? '') . '".';
        locmoni_registrar($conn, $id, ['ok' => false, 'erros' => [$erro], 'visitantes' => []]);
        return ['ok' => false, 'erro' => $erro];
    }

    $inquilinos = [];
    foreach ($loc['inquilinos'] as $inq) {
        $nome = trim((string) ($inq['nome'] ?? ''));
        if ($nome === '') {
            continue;
        }
        $inquilinos[] = [
            'nome' => $nome,
            'documento' => (string) ($inq['documento'] ?? ''),
            'telefone' => (string) ($inq['telefone'] ?? ''),
            'selfie' => locmoni_selfie_path((string) ($inq['selfie'] ?? '')),
        ];
    }

    if (!$inquilinos) {
        $erro = 'A locação não tem hóspedes cadastrados.';
        locmoni_registrar($conn, $id, ['ok' => false, 'erros' => [$erro], 'visitantes' => []]);
        return ['ok' => false, 'erro' => $erro];
    }

    $res = f2moni_adicionar_locacao([
        'cliente_moni_id' => $cliente,
        'apartamento' => (string) ($loc['numero_apartamento'] ?? ''),
        'data_entrada' => locmoni_data((string) ($loc['data_entrada'] ?? '')),
        'data_saida' => locmoni_data((string) ($loc['data_saida'] ?? '')),
        'inquilinos' => $inquilinos,
        'user_type' => (string) ($loc['tipo_usuario'] ?? ''),
        'locador_nome' => (string) ($loc['locador_nome'] ?? ''),
        'locador_telefone' => (string) ($loc['locador_telefone'] ?? ''),
        'registrador_nome' => (string) ($loc['registrador_nome'] ?? ''),
        'observacoes' => (string) ($loc['observacoes'] ?? ''),
        'veiculos' => $loc['veiculos'],
    ]);

    $registro = [
        'ok' => !empty($res['ok']),
        'enviado_em' => date('Y-m-d H:i:s'),
        'cliente_moni_id' => $cliente,
        'realty_id' => $res['realty_id'] ?? null,
        'visitantes' => $res['visitantes'] ?? [],
        'erros' => array_merge(
            $res['erros'] ?? [],
            !empty($res['erro']) ? [$res['erro']] : []
        ),
    ];
    locmoni_registrar($conn, $id, $registro);

    return $registro;
}

function locmoni_registrar($conn, int $id, array $registro): void
{
    $json = json_encode($registro, JSON_UNESCAPED_UNICODE);
    $stmt = @$conn->prepare("UPDATE locacoes SET moni_envio = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('si', $json, $id);
        $stmt->execute();
        $stmt->close();
    }
    error_log('[moni_confirmacao] locacao=' . $id . ' ' . $json);
}

/** Texto curto para exibir na página de confirmadas. */
function locmoni_status_resumo($locacao): string
{
    $envio = json_decode((string) ($locacao['moni_envio'] ?? ''), true);
    if (!is_array($envio)) {
        return '';
    }
    if (!empty($envio['ok'])) {
        return 'Enviado ao Moni' . (!empty($envio['enviado_em']) ? ' em ' . date('d/m/Y H:i', strtotime($envio['enviado_em'])) : '');
    }
    return 'Falha no envio ao Moni';
}
