<?php
/**
 * Edifícios e apartamentos do Moni para o formulário de locação (formulario2).
 *
 * Cada cliente do Moni é um edifício; cada imóvel do cliente é um apartamento.
 * A lista é exclusivamente formada pelos clientes do Moni, sem usar o banco
 * local de edifícios e sem criar registros.
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../sync/moni_api.php';

function edif_moni_titulo(string $s): string
{
    return mb_convert_case(trim(mb_strtolower($s, 'UTF-8')), MB_CASE_TITLE, 'UTF-8');
}

function edif_moni_apartamento(array $imovel): string
{
    $id1 = trim((string) ($imovel['id1'] ?? ''));
    $id2 = trim((string) ($imovel['id2'] ?? ''));
    return trim($id1 . ($id2 !== '' ? ' ' . $id2 : ''));
}

function edif_moni_eh_administrativo(string $rotulo): bool
{
    return $rotulo === '' || stripos($rotulo, 'ADM') !== false;
}

function edif_moni_localizacao(array $cliente): string
{
    $partes = [];
    foreach (['address', 'addressnumber', 'district', 'city.description'] as $campo) {
        $valor = trim((string) ($cliente[$campo] ?? ''));
        if ($valor !== '' && !in_array($valor, $partes, true)) {
            $partes[] = $valor;
        }
    }
    return implode(' · ', $partes);
}

$cacheArquivo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'blindado_edificios_moni_v2.json';
if (is_file($cacheArquivo) && time() - filemtime($cacheArquivo) < 900) {
    $cache = json_decode((string) file_get_contents($cacheArquivo), true);
    if (is_array($cache) && isset($cache['edificios'])) {
        echo json_encode($cache['edificios'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$edificios = [];
try {
    $api = new MoniApi();

    $resCli = $api->customers('', true, 1, 500);
    if (!$resCli['ok']) {
        throw new RuntimeException($resCli['error'] ?? 'Falha ao consultar os clientes do Moni.');
    }
    $clientes = $resCli['data'];

    $resIm = $api->allRealties();
    if (!$resIm['ok']) {
        throw new RuntimeException($resIm['error'] ?? 'Falha ao consultar os imóveis do Moni.');
    }
    $imoveis = $resIm['data'];

    $porCliente = [];
    foreach ($imoveis as $im) {
        $cid = (int) ($im['customerid'] ?? 0);
        $rotulo = edif_moni_apartamento($im);
        if ($cid <= 0 || edif_moni_eh_administrativo($rotulo)) {
            continue;
        }
        $porCliente[$cid][$rotulo] = $rotulo;
    }

    foreach ($clientes as $c) {
        $cid = (int) ($c['id'] ?? 0);
        $nome = trim((string) ($c['name'] ?? ''));
        if ($cid <= 0 || $nome === '') {
            continue;
        }

        $apts = array_values($porCliente[$cid] ?? []);
        usort($apts, 'strnatcasecmp');

        /* Clientes sem imóveis no Moni não entram na lista do formulário. */
        if (!$apts) {
            continue;
        }

        $edificios[] = [
            'id' => (string) $cid,
            'nome_edificio' => edif_moni_titulo($nome),
            'localizacao' => edif_moni_localizacao($c),
            'requer_selfie' => 0,
            'nome_base' => '',
            'telefone' => preg_replace('/\D+/', '', (string) ($c['contact'] ?? '')),
            'cliente_moni_id' => $cid,
            'apartamentos' => $apts,
        ];
    }

    usort($edificios, function ($a, $b) {
        return strnatcasecmp($a['nome_edificio'], $b['nome_edificio']);
    });

    @file_put_contents($cacheArquivo, json_encode(['edificios' => $edificios], JSON_UNESCAPED_UNICODE));
} catch (Throwable $ex) {
    error_log('[edificios_moni] ' . $ex->getMessage());
    http_response_code(502);
    echo json_encode(['erro' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($edificios, JSON_UNESCAPED_UNICODE);
