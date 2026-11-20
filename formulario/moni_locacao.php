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

/**
 * Limite de resolução da credencial facial no Moni: no máximo 1280x960
 * (ou 960x1280 na vertical). Fonte: validação na API e em sync/moni.php.
 */
function f2moni_imagem_cabe(int $largura, int $altura): bool
{
    return $largura > 0
        && $altura > 0
        && max($largura, $altura) <= 1280
        && min($largura, $altura) <= 960;
}

/**
 * Reduz a selfie para caber no limite do Moni, mantendo a proporção.
 * Reaproveita o mesmo critério de sync/moni.php, com prefixo próprio para
 * não colidir com as funções daquela página. Retorna null sem GD.
 */
function f2moni_imagem_reduzir(string $origem, int $largura, int $altura): ?string
{
    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        return null;
    }
    $bin = @file_get_contents($origem);
    if ($bin === false || $bin === '') {
        return null;
    }
    $img = @imagecreatefromstring($bin);
    if (!$img) {
        return null;
    }

    $escala = min(1280 / max($largura, $altura), 960 / min($largura, $altura), 1);
    $novaLargura = max(1, (int) floor($largura * $escala));
    $novaAltura = max(1, (int) floor($altura * $escala));

    $destino = @imagecreatetruecolor($novaLargura, $novaAltura);
    if (!$destino) {
        imagedestroy($img);
        return null;
    }
    $branco = imagecolorallocate($destino, 255, 255, 255);
    imagefilledrectangle($destino, 0, 0, $novaLargura, $novaAltura, $branco);
    imagecopyresampled($destino, $img, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

    $arquivo = @tempnam(sys_get_temp_dir(), 'f2monicred');
    $ok = $arquivo !== false && @imagejpeg($destino, $arquivo, 92);
    imagedestroy($destino);
    imagedestroy($img);

    return $ok ? $arquivo : null;
}

/**
 * Prepara a selfie para envio. Devolve [path, mime, temporario, aviso].
 */
function f2moni_preparar_selfie(string $origem): array
{
    $info = @getimagesize($origem);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP], true)) {
        return ['erro' => 'A selfie não é uma imagem JPG, PNG ou BMP válida.'];
    }
    $largura = (int) $info[0];
    $altura = (int) $info[1];
    $mime = image_type_to_mime_type($info[2]);

    if (f2moni_imagem_cabe($largura, $altura)) {
        return ['path' => $origem, 'mime' => $mime, 'temporario' => false, 'aviso' => ''];
    }

    $reduzida = f2moni_imagem_reduzir($origem, $largura, $altura);
    if ($reduzida !== null) {
        return ['path' => $reduzida, 'mime' => 'image/jpeg', 'temporario' => true, 'aviso' => ''];
    }

    // Sem GD no servidor: tenta o original. Se o Moni recusar, o erro é
    // registrado no resultado, mas a credencial já foi criada.
    return [
        'path' => $origem,
        'mime' => $mime,
        'temporario' => false,
        'aviso' => 'Selfie de ' . $largura . '×' . $altura . ' px não pôde ser redimensionada neste servidor.',
    ];
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
            $visitanteId = (int) ($dados['Id'] ?? 0);
            $personId = (int) ($dados['PersonId'] ?? 0);
            $item = [
                'id' => $visitanteId,
                'person_id' => $personId,
                'nome' => $nome,
                'credencial_id' => 0,
                'credencial' => false,
                'perfil_id' => 0,
                'perfil' => false,
                'foto' => false,
            ];

            // Cria a credencial junto com a pessoa, com ou sem selfie.
            $cred = $api->credentialEnsure($cliente, $visitanteId, $nome);
            $item['credencial'] = (bool) $cred['ok'];
            if ($cred['ok']) {
                $item['credencial_id'] = (int) ($cred['id'] ?? 0);
                $item['perfil_id'] = (int) ($cred['perfil_id'] ?? 0);
                $item['perfil'] = (bool) ($cred['perfil'] ?? false);
                if (!empty($cred['aviso'])) {
                    $erros[] = $nome . ': ' . $cred['aviso'];
                }
            } else {
                $erros[] = $nome . ': credencial não criada (' . ($cred['error'] ?? 'falha') . ')';
            }

            $selfie = (string) ($inq['selfie'] ?? '');
            if ($selfie !== '' && is_file($selfie)) {
                $prep = f2moni_preparar_selfie($selfie);
                if (!empty($prep['erro'])) {
                    $erros[] = $nome . ': ' . $prep['erro'];
                } else {
                    if (!empty($prep['aviso'])) {
                        $erros[] = $nome . ': ' . $prep['aviso'];
                    }
                    $foto = $api->facialSave($personId, $prep['path'], $prep['mime'], 0);
                    if (!empty($prep['temporario']) && is_file($prep['path'])) {
                        @unlink($prep['path']);
                    }
                    $item['foto'] = (bool) $foto['ok'];
                    if (!$foto['ok']) {
                        $erros[] = $nome . ': foto não enviada (' . ($foto['message'] ?? 'falha') . ')';
                    }
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
