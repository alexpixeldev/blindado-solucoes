<?php
/**
 * Integração do formulário de locação com o Moni.
 *
 * Recebe os dados já salvos localmente e cria, para cada hóspede, um
 * visitante no cliente (edifício) e no imóvel (apartamento) selecionados.
 */

require_once __DIR__ . '/../sync/moni_api.php';

function f2moni_somente_digitos(string $s): string
{
    return preg_replace('/\D+/', '', $s);
}

/**
 * Divide o telefone em DDD (MobilePhonePrefix) e número (MobilePhone),
 * no mesmo formato usado pelos visitantes existentes no Moni.
 */
function f2moni_telefone(string $tel): array
{
    $d = f2moni_somente_digitos($tel);
    if (strlen($d) > 11 && substr($d, 0, 2) === '55') {
        $d = substr($d, 2);
    }
    if (strlen($d) >= 10) {
        return [
            'MobilePhonePrefix' => (int) substr($d, 0, 2),
            'MobilePhone' => (int) substr($d, 2),
        ];
    }
    return [
        'MobilePhonePrefix' => null,
        'MobilePhone' => $d !== '' ? (int) $d : null,
    ];
}

/**
 * O formulário tem um único campo "documento". CPF vai em Cpf; os demais
 * formatos vão em Rg, que é o que o Moni exige quando não há CPF.
 */
function f2moni_documento(string $doc): array
{
    $limpo = trim(preg_replace('/[^\p{L}\p{N}.\-\/ ]/u', '', $doc));
    $digitos = f2moni_somente_digitos($doc);
    if (strlen($digitos) === 11) {
        return ['Cpf' => (int) $digitos, 'Rg' => null];
    }
    return ['Cpf' => null, 'Rg' => $limpo !== '' ? $limpo : null];
}

function f2moni_mime_imagem(string $path): string
{
    $mime = function_exists('mime_content_type') ? @mime_content_type($path) : '';
    if (in_array($mime, ['image/jpeg', 'image/png', 'image/bmp'], true)) {
        return $mime;
    }
    return 'image/jpeg';
}

function f2moni_nota(array $args): string
{
    $linhas = [];
    if (!empty($args['user_type']) && $args['user_type'] === 'locatario') {
        if (!empty($args['locador_nome'])) {
            $linhas[] = 'Locador: ' . trim((string) $args['locador_nome']);
        }
        if (!empty($args['locador_telefone'])) {
            $linhas[] = 'WhatsApp do locador: ' . trim((string) $args['locador_telefone']);
        }
    }
    if (!empty($args['registrador_nome'])) {
        $linhas[] = 'Locação registrada por: ' . trim((string) $args['registrador_nome']);
    }
    if (!empty($args['observacoes'])) {
        $linhas[] = 'Observações: ' . trim((string) $args['observacoes']);
    }
    foreach ($args['veiculos'] ?? [] as $v) {
        if (empty($v['modelo'])) {
            continue;
        }
        $linha = 'Veículo: ' . trim((string) $v['modelo']);
        if (!empty($v['cor'])) {
            $linha .= ' · ' . trim((string) $v['cor']);
        }
        if (!empty($v['placa'])) {
            $linha .= ' · Placa ' . mb_strtoupper(trim((string) $v['placa']));
        }
        if (!empty($v['acesso_garagem'])) {
            $linha .= ' · Acesso: ' . trim((string) $v['acesso_garagem']);
        }
        $linhas[] = $linha;
    }
    $nota = implode("\n", $linhas);
    return mb_substr($nota, 0, 1000);
}

/**
 * Cria os visitantes no Moni.
 *
 * $args = [
 *   'cliente_moni_id' => int,
 *   'apartamento' => string (rótulo exibido no formulário),
 *   'data_entrada' => 'd/m/Y',
 *   'data_saida' => 'd/m/Y',
 *   'inquilinos' => [['nome'=>, 'documento'=>, 'telefone'=>, 'selfie'=> caminho absoluto|null], ...],
 *   'user_type', 'locador_nome', 'locador_telefone', 'registrador_nome',
 *   'observacoes', 'veiculos'
 * ]
 */
function f2moni_adicionar_locacao(array $args): array
{
    $cliente = (int) ($args['cliente_moni_id'] ?? 0);
    $apartamento = trim((string) ($args['apartamento'] ?? ''));
    $inquilinos = $args['inquilinos'] ?? [];

    if ($cliente <= 0 || $apartamento === '') {
        return ['ok' => false, 'erro' => 'Edifício ou apartamento do Moni não informado.', 'visitantes' => []];
    }
    if (!$inquilinos) {
        return ['ok' => false, 'erro' => 'Nenhum hóspede para enviar ao Moni.', 'visitantes' => []];
    }

    try {
        $api = new MoniApi();

        $imovel = $api->realtyByLabel($cliente, $apartamento);
        if (!$imovel) {
            return [
                'ok' => false,
                'erro' => 'Apartamento "' . $apartamento . '" não encontrado no Moni para este edifício.',
                'visitantes' => [],
            ];
        }
        $realtyId = (int) ($imovel['code'] ?? 0);
        if ($realtyId <= 0) {
            return ['ok' => false, 'erro' => 'Imóvel sem código válido no Moni.', 'visitantes' => []];
        }

        $nota = f2moni_nota($args);
        $criados = [];
        $erros = [];

        foreach ($inquilinos as $inq) {
            $nome = trim((string) ($inq['nome'] ?? ''));
            if ($nome === '') {
                continue;
            }

            $doc = f2moni_documento((string) ($inq['documento'] ?? ''));
            if (!$doc['Cpf'] && !$doc['Rg']) {
                $erros[] = $nome . ': documento inválido';
                continue;
            }

            $tel = f2moni_telefone((string) ($inq['telefone'] ?? ''));
            $body = [
                'CustomerId' => $cliente,
                'RealtyId' => $realtyId,
                'Name' => mb_substr($nome, 0, 255),
                'Start' => $args['data_entrada'] ?? null,
                'End' => $args['data_saida'] ?? null,
                'MobilePhonePrefix' => $tel['MobilePhonePrefix'],
                'MobilePhone' => $tel['MobilePhone'],
                'Cpf' => $doc['Cpf'],
                'Rg' => $doc['Rg'],
                'Note' => $nota !== '' ? $nota : null,
            ];
            $body = array_filter($body, function ($v) {
                return $v !== null && $v !== '';
            });

            $r = $api->createVisitor($body);
            if (!$r['ok']) {
                $erros[] = $nome . ': ' . ($r['error'] ?? 'falha ao criar visitante');
                continue;
            }

            $dados = $r['data'];
            $item = [
                'id' => (int) ($dados['Id'] ?? 0),
                'person_id' => (int) ($dados['PersonId'] ?? 0),
                'nome' => $nome,
            ];

            $selfie = (string) ($inq['selfie'] ?? '');
            if ($selfie !== '' && is_file($selfie)) {
                $foto = $api->facialSave($item['person_id'], $selfie, f2moni_mime_imagem($selfie), 0);
                $item['foto'] = $foto['ok'] ? true : false;
                if (!$foto['ok']) {
                    $erros[] = $nome . ': foto não enviada (' . ($foto['message'] ?? 'falha') . ')';
                }
            }

            $criados[] = $item;
        }

        return [
            'ok' => count($criados) > 0,
            'realty_id' => $realtyId,
            'visitantes' => $criados,
            'erros' => $erros,
        ];
    } catch (Throwable $ex) {
        error_log('[moni_locacao] ' . $ex->getMessage());
        return ['ok' => false, 'erro' => $ex->getMessage(), 'visitantes' => []];
    }
}
