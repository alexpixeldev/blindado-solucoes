<?php
require_once 'verifica_login.php';
require_once 'moni_api.php';

$api = new MoniApi();

/* ---------------------------------------------------------------------- */
/* Foto da credencial facial (usada como avatar na listagem)               */
/* ---------------------------------------------------------------------- */
if (isset($_GET['foto'])) {
    $fotoPerson = max(0, (int) $_GET['foto']);
    if ($fotoPerson > 0) {
        try {
            $fotoCred = $api->facialImage($fotoPerson);
            if (!empty($fotoCred['image'])) {
                $fotoMime = (string) ($fotoCred['mime'] ?? 'image/jpeg');
                if (!in_array($fotoMime, ['image/jpeg', 'image/png', 'image/bmp'], true)) {
                    $fotoMime = 'image/jpeg';
                }
                $fotoBin = base64_decode($fotoCred['image']);
                header('Content-Type: ' . $fotoMime);
                header('Cache-Control: private, max-age=600');
                header('Content-Length: ' . strlen($fotoBin));
                echo $fotoBin;
                exit;
            }
        } catch (Throwable $ex) {
            // Sem foto: responde 404 abaixo.
        }
    }
    http_response_code(404);
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>';
    exit;
}

$flash = $_SESSION['moni_flash'] ?? null;
unset($_SESSION['moni_flash']);

if (empty($_SESSION['moni_csrf'])) {
    $_SESSION['moni_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['moni_csrf'];

$cliente = max(0, (int) ($_GET['cliente'] ?? 0));
$imovel = isset($_GET['imovel']) && $_GET['imovel'] !== '' ? max(0, (int) $_GET['imovel']) : null;
$tipo = (string) ($_GET['tipo'] ?? 'morador');
if (!isset(MoniApi::TIPOS[$tipo])) {
    $tipo = 'morador';
}
$pessoa = max(0, (int) ($_GET['pessoa'] ?? 0));
$busca = trim((string) ($_GET['busca'] ?? ''));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$todos = isset($_GET['todos']);

/* ---------------------------------------------------------------------- */
/* Helpers                                                                 */
/* ---------------------------------------------------------------------- */

function moni_e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function moni_link(array $params = []): string
{
    $params = array_filter($params, static function ($v) {
        return $v !== null && $v !== '';
    });
    return 'moni.php' . ($params ? '?' . http_build_query($params) : '');
}

function moni_flash(string $message, string $type = 'success'): void
{
    $_SESSION['moni_flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Limite de resolução da credencial facial no Moni.
 *
 * Verificado na API: a imagem precisa caber em 1280x960 ou em 960x1280
 * (retrato). 1280x960 é aceita; 1281x960, 1000x999 e 1000x1000 são
 * recusadas com "Invalid image resolution.".
 */
function moni_imagem_cabe(int $largura, int $altura): bool
{
    return $largura > 0
        && $altura > 0
        && max($largura, $altura) <= 1280
        && min($largura, $altura) <= 960;
}

/**
 * Reduz a imagem para caber no limite do Moni, mantendo a proporção.
 * Depende do GD; retorna null quando o GD não está disponível.
 */
function moni_imagem_reduzir(string $origem, int $largura, int $altura): ?string
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
    // Fundo branco: PNG/BMP com transparência não pode virar preto.
    $branco = imagecolorallocate($destino, 255, 255, 255);
    imagefilledrectangle($destino, 0, 0, $novaLargura, $novaAltura, $branco);
    imagecopyresampled($destino, $img, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

    $arquivo = tempnam(sys_get_temp_dir(), 'monicred');
    $ok = $arquivo !== false && @imagejpeg($destino, $arquivo, 92);
    imagedestroy($destino);
    imagedestroy($img);

    return $ok ? $arquivo : null;
}

/**
 * Indica se o POST trouxe uma nova foto de credencial.
 */
function moni_tem_foto(array $post): bool
{
    if (trim((string) ($post['foto_base64'] ?? '')) !== '') {
        return true;
    }
    $file = $_FILES['foto'] ?? null;
    return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
}

/**
 * Grava a credencial facial da pessoa do POST. Aceita a imagem já reduzida
 * pelo navegador (foto_base64) ou o upload direto do campo foto.
 * Lança exceção com mensagem pronta para o usuário em caso de falha.
 */
function moni_salvar_credencial(MoniApi $api, string $tipo, int $cliente, int $pessoa, array $post, array &$temporarios): string
{
    $origem = null;

    $fotoBase64 = trim((string) ($post['foto_base64'] ?? ''));
    if ($fotoBase64 !== '') {
        if (preg_match('#^data:image/[a-z0-9.+-]+;base64,#i', $fotoBase64, $m)) {
            $fotoBase64 = substr($fotoBase64, strlen($m[0]));
        }
        $bin = base64_decode(preg_replace('/\s+/', '', $fotoBase64), true);
        if ($bin === false || $bin === '') {
            throw new RuntimeException('Não foi possível processar a imagem selecionada. Tente novamente.');
        }
        if (strlen($bin) > 10 * 1024 * 1024) {
            throw new RuntimeException('A imagem deve ter no máximo 10 MB.');
        }
        $origem = tempnam(sys_get_temp_dir(), 'monifoto');
        if ($origem === false) {
            throw new RuntimeException('Não foi possível preparar a imagem para envio.');
        }
        $temporarios[] = $origem;
        if (file_put_contents($origem, $bin) === false) {
            throw new RuntimeException('Não foi possível preparar a imagem para envio.');
        }
    } else {
        $file = $_FILES['foto'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no envio da imagem (código ' . (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) . ').');
        }
        if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new RuntimeException('A imagem deve ter no máximo 10 MB.');
        }
        $origem = (string) ($file['tmp_name'] ?? '');
    }

    $info = @getimagesize($origem);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP], true)) {
        throw new RuntimeException('Envie uma imagem JPG, PNG ou BMP.');
    }

    $arquivo = $origem;
    $mime = image_type_to_mime_type($info[2]);
    if (!moni_imagem_cabe((int) $info[0], (int) $info[1])) {
        $reduzida = moni_imagem_reduzir($origem, (int) $info[0], (int) $info[1]);
        if ($reduzida === null) {
            throw new RuntimeException('A imagem tem ' . (int) $info[0] . '×' . (int) $info[1] . ' px e o Moni aceita no máximo 1280×960 (ou 960×1280 na vertical). Reduza a imagem e envie novamente.');
        }
        $temporarios[] = $reduzida;
        $arquivo = $reduzida;
        $mime = 'image/jpeg';
    }

    $atual = $api->get($tipo, $cliente, $pessoa);
    if (!$atual) {
        throw new RuntimeException('Pessoa não encontrada no Moni.');
    }
    $personId = (int) ($atual['PersonId'] ?? 0);
    if ($personId <= 0) {
        throw new RuntimeException('Esta pessoa não possui PersonId no Moni; não é possível gravar a credencial facial.');
    }

    $r = $api->facialSave($personId, $arquivo, $mime, 0);
    if (!$r['ok']) {
        $msg = (string) ($r['message'] ?? '');
        if (stripos($msg, 'resolution') !== false) {
            throw new RuntimeException('O Moni recusou o tamanho desta imagem. Use uma foto de até 1280×960 px (ou 960×1280 na vertical).');
        }
        throw new RuntimeException($msg !== '' ? $msg : 'Falha ao salvar a credencial.');
    }

    return (string) ($r['message'] ?? 'Credencial facial salva com sucesso.');
}

function moni_redirect(string $url): void
{
    if (strpos($url, 'moni.php') !== 0) {
        $url = 'moni.php';
    }
    header('Location: ' . $url);
    exit;
}

function moni_parse_dt($value): ?DateTime
{
    if ($value === null || $value === '') {
        return null;
    }
    $value = (string) $value;
    foreach (['d/m/Y H:i:s:v', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d'] as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            return $dt;
        }
    }
    $ts = strtotime($value);
    if ($ts !== false) {
        return (new DateTime())->setTimestamp($ts);
    }
    return null;
}

function moni_date_input($value): string
{
    $dt = moni_parse_dt($value);
    return $dt ? $dt->format('Y-m-d') : '';
}

/**
 * O Moni usa 0 para "CPF vazio" em alguns cadastros antigos, mas rejeita
 * qualquer PATCH enquanto o Cpf for 0 ("must be null or greater than '0'").
 * Exibimos vazio e enviamos null nesses casos.
 */
function moni_cpf_value($value): string
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    return ($digits === '' || $digits === '0') ? '' : $digits;
}

/**
 * CPF formatado para exibição (000.000.000-00).
 */
function moni_cpf_display($value): string
{
    $digits = moni_cpf_value($value);
    if ($digits === '' || strlen($digits) > 11) {
        return $digits;
    }
    // O Moni guarda o CPF como número inteiro, então zeros à esquerda se perdem.
    $digits = str_pad($digits, 11, '0', STR_PAD_LEFT);
    return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
}

function moni_datetime_input($value): string
{
    $dt = moni_parse_dt($value);
    return $dt ? $dt->format('Y-m-d\TH:i') : '';
}

function moni_display_date($value): string
{
    $dt = moni_parse_dt($value);
    return $dt ? $dt->format('d/m/Y') : '';
}

function moni_display_datetime($value): string
{
    $dt = moni_parse_dt($value);
    return $dt ? $dt->format('d/m/Y H:i') : '';
}

function moni_phone($prefix, $number): string
{
    $prefix = preg_replace('/\D+/', '', (string) $prefix);
    $number = preg_replace('/\D+/', '', (string) $number);
    if ($prefix === '0') {
        $prefix = '';
    }
    if ($number === '') {
        return '';
    }
    return ($prefix !== '' ? '(' . $prefix . ') ' : '') . $number;
}

/**
 * Junta DDD + número em um único valor para o campo de telefone do formulário.
 * Ex.: 11 + 988881234 => "(11) 98888-1234".
 */
function moni_phone_input($prefix, $number): string
{
    $prefix = preg_replace('/\D+/', '', (string) $prefix);
    $number = preg_replace('/\D+/', '', (string) $number);
    if ($prefix === '0') {
        $prefix = '';
    }
    $digits = $prefix . $number;
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 11) {
        return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
    }
    if (strlen($digits) === 10) {
        return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
    }
    return $digits;
}

function moni_tipo_cliente($type): string
{
    $mapa = [
        1 => 'Residencial',
        2 => 'Comercial',
        3 => 'Industrial',
        4 => 'Patrimônio Público',
        5 => 'Veraneio',
        6 => 'Condomínio',
        7 => 'Cooperativa',
        8 => 'Igreja',
    ];
    return $mapa[(int) $type] ?? 'Cliente';
}

function moni_endereco(array $cliente): string
{
    $partes = [];
    $cidade = trim((string) ($cliente['city']['description'] ?? ''));
    if ($cidade !== '') {
        $partes[] = $cidade;
    }
    $rua = trim((string) ($cliente['address'] ?? ''));
    $numero = trim((string) ($cliente['addressnumber'] ?? ''));
    if ($rua !== '') {
        $partes[] = $rua . ($numero !== '' ? ', ' . $numero : '');
    }
    $bairro = trim((string) ($cliente['district'] ?? ''));
    if ($bairro !== '') {
        $partes[] = $bairro;
    }
    return implode(' · ', $partes);
}

function moni_realty_label(?array $imovel, ?array $cliente): string
{
    if (!$imovel) {
        return 'Imóvel';
    }
    $id1 = trim((string) ($imovel['id1'] ?? ''));
    $id2 = trim((string) ($imovel['id2'] ?? ''));
    $nome1 = trim((string) ($cliente['idrealty1'] ?? ''));
    $nome2 = trim((string) ($cliente['idrealty2'] ?? ''));

    $p1 = $nome1 !== '' ? $nome1 . ' ' . $id1 : $id1;
    $p2 = $nome2 !== '' ? $nome2 . ' ' . $id2 : $id2;

    if ($id1 !== '' && $id2 !== '') {
        return $p1 . ' / ' . $p2;
    }
    if ($id1 !== '') {
        return $p1;
    }
    if ($id2 !== '') {
        return $p2;
    }
    return 'Imóvel ' . (int) ($imovel['code'] ?? 0);
}

function moni_pagination(int $pagina, int $total, int $limite, array $params): string
{
    $paginas = (int) ceil($total / max(1, $limite));
    if ($paginas <= 1) {
        return '';
    }
    $html = '<div class="moni-pagination">';
    if ($pagina > 1) {
        $html .= '<a href="' . moni_e(moni_link($params + ['pagina' => $pagina - 1])) . '"><i class="fas fa-chevron-left"></i> Anterior</a>';
    }
    $html .= '<span class="active">Página ' . $pagina . ' de ' . $paginas . '</span>';
    if ($pagina < $paginas) {
        $html .= '<a href="' . moni_e(moni_link($params + ['pagina' => $pagina + 1])) . '">Próxima <i class="fas fa-chevron-right"></i></a>';
    }
    return $html . '</div>';
}

function moni_input(string $name, string $label, $value, array $opts = []): void
{
    $type = $opts['type'] ?? 'text';
    $full = !empty($opts['full']);
    $required = !empty($opts['required']);
    $placeholder = $opts['placeholder'] ?? '';
    $class = trim((string) ($opts['class'] ?? ''));

    echo '<div class="moni-field' . ($full ? ' full' : '') . '">';
    echo '<label for="f_' . moni_e($name) . '">' . moni_e($label) . ($required ? ' *' : '') . '</label>';
    echo '<input type="' . moni_e($type) . '" id="f_' . moni_e($name) . '" name="' . moni_e($name) . '" value="' . moni_e($value) . '"';
    if ($class !== '') {
        echo ' class="' . moni_e($class) . '"';
    }
    if ($required) {
        echo ' required';
    }
    if ($placeholder !== '') {
        echo ' placeholder="' . moni_e($placeholder) . '"';
    }
    foreach (['step', 'min', 'max', 'maxlength', 'inputmode'] as $attr) {
        if (!empty($opts[$attr])) {
            echo ' ' . $attr . '="' . moni_e($opts[$attr]) . '"';
        }
    }
    echo '></div>';
}

function moni_textarea(string $name, string $label, $value, bool $full = true): void
{
    echo '<div class="moni-field' . ($full ? ' full' : '') . '">';
    echo '<label for="f_' . moni_e($name) . '">' . moni_e($label) . '</label>';
    echo '<textarea id="f_' . moni_e($name) . '" name="' . moni_e($name) . '">' . moni_e($value) . '</textarea>';
    echo '</div>';
}

function moni_checkbox(string $name, string $label, bool $checked): void
{
    echo '<label class="moni-check"><input type="checkbox" name="' . moni_e($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . moni_e($label) . '</label>';
}

/**
 * Avatar da credencial facial, com fallback para o ícone quando não há imagem.
 */
function moni_avatar_html(int $personId, array $credenciais, string $nome): string
{
    if ($personId > 0 && !empty($credenciais[$personId])) {
        $src = 'moni.php?foto=' . $personId . '&v=' . (int) ($_SESSION['moni_foto_versao'] ?? 0);
        return '<img class="moni-avatar" src="' . moni_e($src) . '" alt="Credencial de ' . moni_e($nome) . '" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';">'
            . '<span class="moni-avatar moni-avatar-empty" style="display:none;"><i class="fas fa-user"></i></span>';
    }
    return '<span class="moni-avatar moni-avatar-empty"><i class="fas fa-user"></i></span>';
}

function moni_same_value(string $kind, $old, $new): bool
{
    if ($kind === 'bool') {
        return (bool) $old === (bool) $new;
    }
    if ($kind === 'birth') {
        $a = moni_parse_dt($old);
        $b = moni_parse_dt($new);
        return ($a ? $a->format('Y-m-d') : null) === ($b ? $b->format('Y-m-d') : null);
    }
    if ($kind === 'datetime') {
        $a = moni_parse_dt($old);
        $b = moni_parse_dt($new);
        return ($a ? $a->format('Y-m-d H:i') : null) === ($b ? $b->format('Y-m-d H:i') : null);
    }
    if ($old === null && $new === null) {
        return true;
    }
    return trim((string) $old) === trim((string) $new);
}

function moni_build_patch(string $tipo, array $atual, array $post): array
{
    $mapa = [
        'morador' => [
            'Name' => 'string:req',
            'Rg' => 'string',
            'Cpf' => 'digits',
            'Birthday' => 'birth',
            'Email' => 'string',
            'MobilePhone' => 'phone',
            'Note' => 'string',
            'Responsible' => 'bool',
            'RelationToResponsible' => 'string',
            'UseDigitalKeys' => 'bool',
        ],
        'visitante' => [
            'Name' => 'string:req',
            'Rg' => 'string',
            'Cpf' => 'digits',
            'RelationToResponsible' => 'string',
            'MobilePhone' => 'phone',
            'Start' => 'datetime',
            'End' => 'datetime',
            'Note' => 'string',
            'UseDigitalKeys' => 'bool',
        ],
        'prestador' => [
            'Name' => 'string:req',
            'ServiceType' => 'string:req',
            'Rg' => 'string',
            'Cpf' => 'digits',
            'CreciNumber' => 'string',
            'MobilePhone' => 'phone',
            'Start' => 'datetime',
            'End' => 'datetime',
            'Note' => 'string',
            'UseDigitalKeys' => 'bool',
        ],
    ];

    if (!isset($mapa[$tipo])) {
        return [];
    }

    // Limites documentados no manual da API Moni.
    $limites = [
        'Name' => 40,
        'RelationToResponsible' => 30,
        'Email' => 120,
        'ServiceType' => 40,
        'CreciNumber' => 15,
    ];

    if (in_array($tipo, ['visitante', 'prestador'], true)) {
        $iniRaw = trim((string) ($post['Start'] ?? ''));
        $fimRaw = trim((string) ($post['End'] ?? ''));
        if ($iniRaw !== '' && $fimRaw !== '') {
            $ini = DateTime::createFromFormat('Y-m-d\TH:i', $iniRaw);
            $fim = DateTime::createFromFormat('Y-m-d\TH:i', $fimRaw);
            if ($ini && $fim && $fim <= $ini) {
                throw new RuntimeException('O fim da permissão deve ser posterior ao início.');
            }
        }
    }

    $ops = [];
    foreach ($mapa[$tipo] as $campo => $regra) {
        $partes = explode(':', $regra);
        $kind = $partes[0];
        $required = ($partes[1] ?? '') === 'req';

        if ($kind === 'bool') {
            $novo = !empty($post[$campo]);
        } elseif ($kind === 'phone') {
            $digits = preg_replace('/\D+/', '', (string) ($post[$campo] ?? ''));
            if ($digits === '') {
                $prefixo = null;
                $numero = null;
            } elseif (strlen($digits) === 10 || strlen($digits) === 11) {
                $prefixo = (int) substr($digits, 0, 2);
                $numero = (int) substr($digits, 2);
            } else {
                throw new RuntimeException('Informe o telefone com DDD, ex.: (11) 98888-1234.');
            }

            $campoPrefixo = $campo . 'Prefix';
            if (!moni_same_value('int', $atual[$campoPrefixo] ?? null, $prefixo)) {
                $ops[] = ['op' => 'replace', 'path' => '/' . $campoPrefixo, 'value' => $prefixo];
            }
            if (!moni_same_value('int', $atual[$campo] ?? null, $numero)) {
                $ops[] = ['op' => 'replace', 'path' => '/' . $campo, 'value' => $numero];
            }
            continue;
        } else {
            $raw = trim((string) ($post[$campo] ?? ''));
            if ($raw === '') {
                if ($required) {
                    throw new RuntimeException('O campo "' . $campo . '" é obrigatório.');
                }
                $novo = null;
            } else {
                switch ($kind) {
                    case 'int':
                        $novo = (int) $raw;
                        break;
                    case 'digits':
                        $digits = preg_replace('/\D+/', '', $raw);
                        $novo = $digits === '' ? null : $digits;
                        break;
                    case 'birth':
                        $dt = DateTime::createFromFormat('Y-m-d', $raw);
                        $novo = $dt ? $dt->format('d/m/Y') : $raw;
                        break;
                    case 'datetime':
                        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
                        $novo = $dt ? $dt->format('Y-m-d\TH:i:s') : $raw;
                        break;
                    default:
                        $novo = $raw;
                }
            }
        }

        if (is_string($novo) && isset($limites[$campo]) && mb_strlen($novo) > $limites[$campo]) {
            throw new RuntimeException('O campo "' . $campo . '" deve ter no máximo ' . $limites[$campo] . ' caracteres.');
        }
        if ($campo === 'Cpf' && ($novo === null || $novo === 0 || $novo === '0' || $novo === '')) {
            $novo = null;
        }

        $velho = $atual[$campo] ?? null;
        if (!moni_same_value($kind, $velho, $novo)) {
            $ops[] = ['op' => 'replace', 'path' => '/' . $campo, 'value' => $novo];
        }
    }

    // Cadastros antigos com Cpf = 0 são rejeitados pelo Moni em qualquer PATCH.
    if ($ops && (string) ($atual['Cpf'] ?? '') === '0') {
        $temCpf = false;
        foreach ($ops as $op) {
            if (($op['path'] ?? '') === '/Cpf') {
                $temCpf = true;
                break;
            }
        }
        if (!$temCpf) {
            $ops[] = ['op' => 'replace', 'path' => '/Cpf', 'value' => null];
        }
    }

    return $ops;
}

/* ---------------------------------------------------------------------- */
/* Ações POST                                                              */
/* ---------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');
    $redir = (string) ($_POST['redir'] ?? 'moni.php');
    $postCliente = max(0, (int) ($_POST['cliente'] ?? 0));
    $postImovel = isset($_POST['imovel']) && $_POST['imovel'] !== '' ? max(0, (int) $_POST['imovel']) : null;
    $postTipo = (string) ($_POST['tipo'] ?? 'morador');
    if (!isset(MoniApi::TIPOS[$postTipo])) {
        $postTipo = 'morador';
    }
    $postPessoa = max(0, (int) ($_POST['pessoa'] ?? 0));

    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        moni_flash('Sessão expirada. Recarregue a página e tente novamente.', 'error');
        moni_redirect($redir);
    }

    $temporariosFoto = [];

    try {
        switch ($acao) {
            case 'salvar_pessoa':
                $atual = $api->get($postTipo, $postCliente, $postPessoa);
                if (!$atual) {
                    throw new RuntimeException('Pessoa não encontrada no Moni.');
                }
                $ops = moni_build_patch($postTipo, $atual, $_POST);
                $temFoto = moni_tem_foto($_POST);
                if (!$ops && !$temFoto) {
                    moni_flash('Nenhuma alteração foi identificada.', 'info');
                    break;
                }

                $mensagemSalvar = '';
                if ($ops) {
                    // Nome, RG e CPF pertencem à pessoa, não ao vínculo com o imóvel.
                    // Se forem enviados no vínculo, o Moni cria uma pessoa nova e a
                    // credencial facial (presa ao PersonId) deixa de aparecer.
                    $personId = (int) ($atual['PersonId'] ?? 0);
                    $camposPessoa = ['Name', 'Rg', 'Cpf'];
                    $opsPessoa = [];
                    $opsRegistro = [];
                    foreach ($ops as $op) {
                        $campo = ltrim((string) ($op['path'] ?? ''), '/');
                        if ($personId > 0 && in_array($campo, $camposPessoa, true)) {
                            $opsPessoa[] = $op;
                        } else {
                            $opsRegistro[] = $op;
                        }
                    }

                    // Cpf = 0 em cadastros antigos bloqueia qualquer PATCH do Moni.
                    if ($opsRegistro && (string) ($atual['Cpf'] ?? '') === '0') {
                        $temCpf = false;
                        foreach ($opsRegistro as $op) {
                            if (($op['path'] ?? '') === '/Cpf') {
                                $temCpf = true;
                                break;
                            }
                        }
                        if (!$temCpf) {
                            $opsRegistro[] = ['op' => 'replace', 'path' => '/Cpf', 'value' => null];
                        }
                    }

                    if ($opsPessoa) {
                        $pessoa = $api->person($personId);
                        if ($pessoa && (string) ($pessoa['Cpf'] ?? '') === '0') {
                            $temCpf = false;
                            foreach ($opsPessoa as $op) {
                                if (($op['path'] ?? '') === '/Cpf') {
                                    $temCpf = true;
                                    break;
                                }
                            }
                            if (!$temCpf) {
                                $opsPessoa[] = ['op' => 'replace', 'path' => '/Cpf', 'value' => null];
                            }
                        }
                        $r = $api->patchPerson($personId, $opsPessoa);
                        if (!$r['ok']) {
                            throw new RuntimeException($r['error'] ?? 'Falha ao atualizar os dados da pessoa.');
                        }
                    }

                    if ($opsRegistro) {
                        $r = $api->patch($postTipo, $postCliente, $postPessoa, $opsRegistro);
                        if (!$r['ok']) {
                            throw new RuntimeException($r['error'] ?? 'Falha ao atualizar os dados.');
                        }
                    }

                    $mensagemSalvar = 'Dados atualizados com sucesso.';
                }

                if ($temFoto) {
                    moni_salvar_credencial($api, $postTipo, $postCliente, $postPessoa, $_POST, $temporariosFoto);
                    $mensagemSalvar = $mensagemSalvar === ''
                        ? 'Credencial facial atualizada com sucesso.'
                        : 'Dados e credencial facial atualizados com sucesso.';
                    $_SESSION['moni_foto_versao'] = time();
                }

                moni_flash($mensagemSalvar);
                break;

            case 'excluir_pessoa':
                $atual = $api->get($postTipo, $postCliente, $postPessoa);
                if (!$atual) {
                    throw new RuntimeException('Pessoa não encontrada no Moni.');
                }
                // Exclui a pessoa primeiro: se a exclusão falhar, a credencial facial
                // permanece intacta. A limpeza da credencial é feita depois, sem
                // impedir a exclusão caso o Moni já a tenha removido em cascata.
                $r = $api->delete($postTipo, $postCliente, $postPessoa);
                if (!$r['ok']) {
                    throw new RuntimeException($r['error'] ?? 'Falha ao excluir.');
                }
                $personIdExcluir = (int) ($atual['PersonId'] ?? 0);
                if ($personIdExcluir > 0) {
                    $api->facialDelete($personIdExcluir);
                }
                moni_flash($r['message'] ?? 'Registro excluído com sucesso.');
                $_SESSION['moni_foto_versao'] = time();
                break;

            case 'salvar_foto':
                // Compatibilidade: versões antigas da página enviavam só a foto.
                moni_flash(moni_salvar_credencial($api, $postTipo, $postCliente, $postPessoa, $_POST, $temporariosFoto));
                $_SESSION['moni_foto_versao'] = time();
                break;

            case 'excluir_foto':
                $atual = $api->get($postTipo, $postCliente, $postPessoa);
                if (!$atual) {
                    throw new RuntimeException('Pessoa não encontrada no Moni.');
                }
                $personIdFoto = (int) ($atual['PersonId'] ?? 0);
                if ($personIdFoto <= 0) {
                    throw new RuntimeException('Esta pessoa não possui PersonId no Moni.');
                }
                $r = $api->facialDelete($personIdFoto);
                if (!$r['ok']) {
                    throw new RuntimeException($r['message'] ?? 'Falha ao excluir a credencial.');
                }
                moni_flash($r['message'] ?? 'Credencial removida com sucesso.');
                $_SESSION['moni_foto_versao'] = time();
                break;

            default:
                throw new RuntimeException('Ação inválida.');
        }
    } catch (Throwable $ex) {
        moni_flash($ex->getMessage(), 'error');
    } finally {
        // Arquivos temporários da credencial (upload em base64 ou imagem reduzida).
        foreach ($temporariosFoto as $tmpFoto) {
            if (is_string($tmpFoto) && $tmpFoto !== '' && is_file($tmpFoto)) {
                @unlink($tmpFoto);
            }
        }
    }

    moni_redirect($redir);
}

/* ---------------------------------------------------------------------- */
/* Carregamento dos dados                                                  */
/* ---------------------------------------------------------------------- */

$erro_conexao = '';
$clientes = [];
$totalClientes = 0;
$realties = [];
$counts = [];
$pessoas = [];
$totalPessoas = 0;
$clienteInfo = null;
$imovelInfo = null;
$registro = null;
$credencial = ['ok' => true, 'image' => null, 'mime' => 'image/jpeg', 'info' => null, 'message' => ''];
$credenciais = [];
try {
    if ($pessoa > 0 && $cliente > 0 && $imovel !== null) {
        $clienteInfo = $api->customer($cliente);
        $imovelInfo = $api->realty($cliente, $imovel);
        $registro = $api->get($tipo, $cliente, $pessoa);
        if (!$registro) {
            throw new RuntimeException('Pessoa não encontrada no Moni. Ela pode ter sido excluída.');
        }
        $credencial = $api->facialImage((int) ($registro['PersonId'] ?? 0));
    } elseif ($cliente > 0 && $imovel !== null) {
        $clienteInfo = $api->customer($cliente);
        $imovelInfo = $api->realty($cliente, $imovel);
        if (!$clienteInfo) {
            throw new RuntimeException('Cliente não encontrado no Moni.');
        }
        if (!$imovelInfo) {
            throw new RuntimeException('Imóvel não encontrado no Moni.');
        }
        $counts = $api->countsByRealty($cliente);
        $res = $api->people($tipo, $cliente, $imovel, $pagina, 100);
        if (!$res['ok']) {
            throw new RuntimeException($res['error'] ?? 'Falha ao consultar as pessoas do imóvel.');
        }
        $pessoas = $res['data'];
        $totalPessoas = $res['total'];
        $credenciais = $api->facialPersonIds();
    } elseif ($cliente > 0) {
        $clienteInfo = $api->customer($cliente);
        if (!$clienteInfo) {
            throw new RuntimeException('Cliente não encontrado no Moni.');
        }
        $res = $api->realties($cliente);
        if (!$res['ok']) {
            throw new RuntimeException($res['error'] ?? 'Falha ao consultar os imóveis.');
        }
        $realties = $res['data'];
        $counts = $api->countsByRealty($cliente);
    } else {
        $res = $api->customers($busca, $todos, $pagina, 60);
        if (!$res['ok']) {
            throw new RuntimeException($res['error'] ?? 'Falha ao consultar os clientes.');
        }
        $clientes = $res['data'];
        $totalClientes = $res['total'];
    }
} catch (Throwable $ex) {
    $erro_conexao = $ex->getMessage();
}

$imovelLabel = $imovelInfo ? moni_realty_label($imovelInfo, $clienteInfo) : '';
$contagemImovel = ($imovel !== null && isset($counts[$imovel])) ? $counts[$imovel] : ['morador' => 0, 'visitante' => 0, 'prestador' => 0];
?>
<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moni | Blindado Soluções</title>
    <link rel="icon" type="image/png" href="../img/escudo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>

    <link rel="stylesheet" href="style_modern.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        .moni-page { font-family: 'Inter', sans-serif; }
        .moni-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .moni-top h1 { margin: 0; font-size: 28px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
        .moni-top p { margin: 5px 0 0; color: var(--text-secondary); font-size: 14px; }
        .moni-status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px; background: rgba(37, 169, 55, 0.12); border: 1px solid rgba(37, 169, 55, 0.35); color: #9ceba5; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .moni-status.off { background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.35); color: #fca5a5; }
        .moni-breadcrumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; }
        .moni-breadcrumb a { color: var(--text-secondary); text-decoration: none; transition: color .15s; }
        .moni-breadcrumb a:hover { color: var(--primary-light); }
        .moni-breadcrumb .sep { font-size: 10px; opacity: .55; }
        .moni-breadcrumb .current { color: var(--text-primary); font-weight: 600; }
        .moni-flash { display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; line-height: 1.5; border: 1px solid transparent; }
        .moni-flash.success { background: rgba(37, 169, 55, 0.12); border-color: rgba(37, 169, 55, 0.35); color: #a9efb1; }
        .moni-flash.error { background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.35); color: #fca5a5; }
        .moni-flash.info { background: rgba(59, 130, 246, 0.12); border-color: rgba(59, 130, 246, 0.35); color: #93c5fd; }
        .moni-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .moni-search { position: relative; flex: 1 1 280px; max-width: 460px; }
        .moni-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 13px; }
        .moni-search input { width: 100%; padding: 11px 14px 11px 40px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); font-size: 13px; }
        .moni-search input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(37, 169, 55, 0.18); }
        .moni-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 14px; }
        .moni-card { display: flex; align-items: flex-start; gap: 14px; padding: 18px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--bg-card); color: var(--text-primary); text-decoration: none; transition: transform .15s, border-color .15s, box-shadow .15s; }
        .moni-card:hover { transform: translateY(-1px); border-color: rgba(37, 169, 55, 0.45); box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22); }
        .moni-card-icon { width: 44px; height: 44px; flex: 0 0 auto; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(37, 169, 55, 0.12); color: var(--primary-light); font-size: 18px; }
        .moni-card-body { flex: 1; min-width: 0; }
        .moni-card h3 { margin: 0 0 5px; font-size: 16px; font-weight: 700; }
        .moni-card p { margin: 0; color: var(--text-secondary); font-size: 13px; line-height: 1.5; }
        .moni-muted { color: var(--text-secondary); font-size: 12px; }
        .moni-chevron { color: var(--text-secondary); font-size: 12px; margin-top: 5px; }
        .moni-section-title { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .moni-section-title h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-primary); }
        .moni-section-title span { color: var(--text-secondary); font-size: 13px; }
        .moni-unit { flex-direction: column; gap: 12px; }
        .moni-unit-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; width: 100%; }
        .moni-unit-id { font-size: 18px; font-weight: 800; letter-spacing: -0.01em; }
        .moni-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; background: var(--bg-secondary); border: 1px solid var(--border); color: var(--text-secondary); }
        .moni-badge.ok { background: rgba(37, 169, 55, 0.12); border-color: rgba(37, 169, 55, 0.35); color: #9ceba5; }
        .moni-badge.warn { background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.35); color: #f6c76a; }
        .moni-unit-stats { display: flex; gap: 12px; flex-wrap: wrap; color: var(--text-secondary); font-size: 12px; }
        .moni-unit-stats span { display: inline-flex; align-items: center; gap: 5px; }
        .moni-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .moni-tab { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-secondary); text-decoration: none; font-size: 13px; font-weight: 600; transition: all .15s; }
        .moni-tab:hover { color: var(--text-primary); border-color: rgba(37, 169, 55, 0.35); }
        .moni-tab.active { background: rgba(37, 169, 55, 0.16); border-color: rgba(37, 169, 55, 0.45); color: #a9efb1; }
        .moni-avatar { width: 40px; height: 40px; flex: 0 0 auto; border-radius: 50%; object-fit: cover; background: var(--bg-secondary); border: 1px solid var(--border); }
        .moni-avatar-empty { display: inline-flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 15px; }
        .moni-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
        .moni-pessoa-card { display: flex; flex-direction: column; gap: 14px; padding: 18px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--bg-card); transition: border-color .15s, box-shadow .15s; }
        .moni-pessoa-card:hover { border-color: rgba(37, 169, 55, 0.45); box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22); }
        .moni-pessoa-head { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .moni-pessoa-head .moni-avatar { width: 48px; height: 48px; }
        .moni-pessoa-nome { min-width: 0; }
        .moni-pessoa-nome a { color: var(--text-primary); text-decoration: none; font-weight: 700; font-size: 15px; }
        .moni-pessoa-nome a:hover { color: var(--primary-light); }
        .moni-pessoa-info { display: grid; gap: 7px; }
        .moni-pessoa-linha { display: flex; gap: 10px; align-items: baseline; font-size: 12px; line-height: 1.45; }
        .moni-pessoa-linha > span { color: var(--text-secondary); min-width: 112px; }
        .moni-pessoa-linha > strong { color: var(--text-primary); font-weight: 600; min-width: 0; word-break: break-word; }
        .moni-pessoa-card > .moni-actions { margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border); }
        .moni-detail-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(300px, 1fr); gap: 16px; align-items: start; margin-bottom: 16px; }
        .moni-detail-grid > .moni-panel-edit { order: 1; }
        .moni-detail-grid > .moni-panel-cred { order: 2; }
        .moni-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
        .moni-panel h2 { margin: 0 0 14px; font-size: 16px; font-weight: 700; color: var(--text-primary); }
        .moni-photo-box { display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .moni-photo { width: 100%; max-width: 250px; border-radius: 12px; border: 1px solid var(--border); background: #000; object-fit: cover; aspect-ratio: 3 / 4; }
        .moni-photo-empty { width: 100%; max-width: 250px; aspect-ratio: 3 / 4; border-radius: 12px; border: 1px dashed var(--border); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: var(--text-secondary); background: var(--bg-secondary); font-size: 12px; }
        .moni-photo-empty i { font-size: 36px; opacity: .45; }
        .moni-note { font-size: 12px; color: var(--text-secondary); line-height: 1.6; margin: 0; }
        .moni-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
        .moni-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .moni-field.full { grid-column: 1 / -1; }
        .moni-field label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: var(--text-secondary); font-weight: 700; }
        .moni-field input, .moni-field select, .moni-field textarea { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); font-size: 13px; }
        .moni-field textarea { min-height: 92px; resize: vertical; }
        .moni-field input:focus, .moni-field select:focus, .moni-field textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(37, 169, 55, 0.18); }
        .moni-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-primary); }
        .moni-check input { width: 16px; height: 16px; accent-color: var(--primary); }
        .moni-form-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; align-items: center; }
        .moni-empty { text-align: center; padding: 44px 20px; color: var(--text-secondary); border: 1px dashed var(--border); border-radius: var(--radius); background: var(--bg-card); font-size: 13px; }
        .moni-empty i { font-size: 34px; opacity: .4; display: block; margin-bottom: 10px; }
        .moni-pagination { display: flex; gap: 8px; align-items: center; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
        .moni-pagination a, .moni-pagination span { padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; font-size: 13px; }
        .moni-pagination a:hover { color: var(--text-primary); border-color: rgba(37, 169, 55, 0.35); }
        .moni-pagination .active { background: rgba(37, 169, 55, 0.16); border-color: rgba(37, 169, 55, 0.45); color: #a9efb1; font-weight: 700; }
        .moni-upload { display: flex; flex-direction: column; align-items: center; gap: 8px; width: 100%; max-width: 250px; }
        .moni-upload label.btn { cursor: pointer; }
        .moni-upload-input { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
        .moni-upload-input:focus + label { outline: 2px solid var(--primary); outline-offset: 2px; }
        .moni-upload-nome { font-size: 11px; color: var(--text-secondary); text-align: center; word-break: break-word; line-height: 1.4; }
        @media (max-width: 900px) {
            .moni-detail-grid { grid-template-columns: 1fr; }
            .moni-grid { grid-template-columns: 1fr; }
            .moni-top h1 { font-size: 24px; }
        }
        @media (max-width: 640px) {
            .moni-panel { padding: 16px; }
            .moni-actions { justify-content: flex-start; }
            .moni-avatar { width: 34px; height: 34px; }
            .moni-pessoa-linha { flex-direction: column; gap: 2px; }
            .moni-pessoa-linha > span { min-width: 0; }
        }
    </style>
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <div class="flex flex-1 flex-col overflow-hidden">
            <?php include 'components/header.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <div class="moni-page">
                    <div class="moni-top">
                        <div>
                            <h1>Moni</h1>
                            <p>Clientes, imóveis, pessoas e credenciais do Moni Software.</p>
                        </div>
                        <span class="moni-status <?= $erro_conexao ? 'off' : '' ?>">
                            <i class="fas <?= $erro_conexao ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
                            <?= $erro_conexao ? 'Falha na conexão' : 'Moni conectado' ?>
                        </span>
                    </div>

                    <nav class="moni-breadcrumb" aria-label="Navegação Moni">
                        <a href="moni.php"><i class="fas fa-building"></i> Clientes</a>
                        <?php if ($cliente > 0 && $clienteInfo): ?>
                            <i class="fas fa-chevron-right sep"></i>
                            <?php if ($imovel !== null): ?>
                                <a href="<?= moni_e(moni_link(['cliente' => $cliente])) ?>"><?= moni_e($clienteInfo['name']) ?></a>
                            <?php else: ?>
                                <span class="current"><?= moni_e($clienteInfo['name']) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($imovel !== null && $imovelInfo): ?>
                            <i class="fas fa-chevron-right sep"></i>
                            <?php if ($pessoa > 0 && $registro): ?>
                                <a href="<?= moni_e(moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo])) ?>"><?= moni_e($imovelLabel) ?></a>
                            <?php else: ?>
                                <span class="current"><?= moni_e($imovelLabel) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($pessoa > 0 && $registro): ?>
                            <i class="fas fa-chevron-right sep"></i>
                            <span class="current"><?= moni_e($registro['Name'] ?? 'Pessoa') ?></span>
                        <?php endif; ?>
                    </nav>

                    <?php if ($flash): ?>
                        <?php $flashClass = in_array($flash['type'] ?? '', ['error', 'info'], true) ? $flash['type'] : 'success'; ?>
                        <div class="moni-flash <?= moni_e($flashClass) ?>">
                            <i class="fas <?= $flashClass === 'error' ? 'fa-circle-exclamation' : ($flashClass === 'info' ? 'fa-circle-info' : 'fa-circle-check') ?>"></i>
                            <span><?= moni_e($flash['message'] ?? '') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($erro_conexao): ?>
                        <div class="moni-flash error">
                            <i class="fas fa-plug-circle-xmark"></i>
                            <div>
                                <strong>Não foi possível concluir a consulta.</strong><br>
                                <?= moni_e($erro_conexao) ?>
                                <div style="margin-top:8px;">
                                    <a class="btn btn-secondary btn-sm" href="moni.php"><i class="fas fa-rotate-right"></i> Tentar novamente</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$erro_conexao && $pessoa > 0 && $registro): ?>
                        <?php
                        $info = MoniApi::TIPOS[$tipo];
                        $voltar = moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo]);
                        ?>
                        <div class="moni-section-title">
                            <div>
                                <h2><?= moni_e($registro['Name'] ?? 'Pessoa') ?></h2>
                                <span><?= moni_e($info['label']) ?> · <?= moni_e($imovelLabel) ?></span>
                            </div>
                            <a class="btn btn-secondary btn-sm" href="<?= moni_e($voltar) ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
                        </div>

                        <div class="moni-detail-grid">
                            <div class="moni-panel moni-panel-cred">
                                <h2><i class="fas fa-id-card"></i> Credencial facial</h2>
                                <div class="moni-photo-box">
                                    <?php if (!empty($credencial['image'])): ?>
                                        <img class="moni-photo" src="data:<?= moni_e($credencial['mime'] ?: 'image/jpeg') ?>;base64,<?= moni_e($credencial['image']) ?>" alt="Credencial facial de <?= moni_e($registro['Name'] ?? '') ?>">
                                        <span class="moni-badge ok"><i class="fas fa-circle-check"></i> Credencial facial cadastrada</span>
                                    <?php elseif (($credencial['ok'] ?? true) === false): ?>
                                        <div class="moni-photo-empty">
                                            <i class="fas fa-triangle-exclamation"></i>
                                            <span>Não foi possível consultar a credencial</span>
                                        </div>
                                        <span class="moni-badge warn"><i class="fas fa-triangle-exclamation"></i> <?= moni_e($credencial['message'] ?? 'Erro desconhecido') ?></span>
                                    <?php else: ?>
                                        <div class="moni-photo-empty">
                                            <i class="fas fa-user-shield"></i>
                                            <span>Sem credencial facial</span>
                                        </div>
                                        <span class="moni-badge warn"><i class="fas fa-circle-exclamation"></i> Não cadastrada</span>
                                    <?php endif; ?>

                                    <div class="moni-upload">
                                        <input type="file" id="moni-foto" name="foto" form="moni-form-pessoa" accept="image/jpeg,image/png,image/bmp" class="moni-upload-input">
                                        <label for="moni-foto" class="btn btn-secondary btn-sm"><i class="fas fa-folder-open"></i> Escolher foto do computador</label>
                                        <span class="moni-upload-nome" id="moni-foto-nome">Nenhuma foto selecionada</span>
                                    </div>

                                    <?php if (!empty($credencial['image'])): ?>
                                        <form method="post" action="moni.php" onsubmit="return confirm('Excluir a credencial facial desta pessoa? A credencial será removida.');">
                                            <input type="hidden" name="csrf" value="<?= moni_e($csrf) ?>">
                                            <input type="hidden" name="acao" value="excluir_foto">
                                            <input type="hidden" name="redir" value="<?= moni_e(moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo, 'pessoa' => $pessoa])) ?>">
                                            <input type="hidden" name="cliente" value="<?= (int) $cliente ?>">
                                            <input type="hidden" name="imovel" value="<?= (int) $imovel ?>">
                                            <input type="hidden" name="tipo" value="<?= moni_e($tipo) ?>">
                                            <input type="hidden" name="pessoa" value="<?= (int) $pessoa ?>">
                                            <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash-can"></i> Excluir credencial</button>
                                        </form>
                                    <?php endif; ?>

                                    <p class="moni-note">Credencial biométrica facial. Pessoa nº <?= (int) ($registro['PersonId'] ?? 0) ?> no Moni. A prévia acima é salva junto com os demais dados quando você clicar em "Salvar alterações".</p>
                                </div>
                            </div>

                            <div class="moni-panel moni-panel-edit">
                                <h2><i class="fas fa-pen-to-square"></i> Editar <?= moni_e(mb_strtolower($info['label'])) ?></h2>
                                <form method="post" action="moni.php" enctype="multipart/form-data" id="moni-form-pessoa" class="moni-form-pessoa">
                                    <input type="hidden" name="csrf" value="<?= moni_e($csrf) ?>">
                                    <input type="hidden" name="acao" value="salvar_pessoa">
                                    <input type="hidden" name="foto_base64" value="">
                                    <input type="hidden" name="redir" value="<?= moni_e(moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo, 'pessoa' => $pessoa])) ?>">
                                    <input type="hidden" name="cliente" value="<?= (int) $cliente ?>">
                                    <input type="hidden" name="imovel" value="<?= (int) $imovel ?>">
                                    <input type="hidden" name="tipo" value="<?= moni_e($tipo) ?>">
                                    <input type="hidden" name="pessoa" value="<?= (int) $pessoa ?>">

                                    <div class="moni-form-grid">
                                        <?php moni_input('Name', 'Nome', $registro['Name'] ?? '', ['required' => true, 'full' => true, 'maxlength' => 40]); ?>
                                        <?php moni_input('Rg', 'RG', $registro['Rg'] ?? ''); ?>
                                        <?php moni_input('Cpf', 'CPF', moni_cpf_value($registro['Cpf'] ?? ''), ['inputmode' => 'numeric']); ?>

                                        <?php if ($tipo === 'morador'): ?>
                                            <?php moni_input('Birthday', 'Nascimento', moni_date_input($registro['Birthday'] ?? ''), ['type' => 'date']); ?>
                                            <?php moni_input('Email', 'E-mail', $registro['Email'] ?? '', ['type' => 'email', 'maxlength' => 120]); ?>
                                            <?php moni_input('RelationToResponsible', 'Relação com o responsável', $registro['RelationToResponsible'] ?? '', ['maxlength' => 30]); ?>
                                        <?php elseif ($tipo === 'visitante'): ?>
                                            <?php moni_input('RelationToResponsible', 'Relação com o morador', $registro['RelationToResponsible'] ?? '', ['maxlength' => 30]); ?>
                                        <?php else: ?>
                                            <?php moni_input('ServiceType', 'Tipo de serviço', $registro['ServiceType'] ?? '', ['required' => true, 'maxlength' => 40]); ?>
                                            <?php moni_input('CreciNumber', 'CRECI', $registro['CreciNumber'] ?? '', ['maxlength' => 15]); ?>
                                        <?php endif; ?>

                                        <?php moni_input('MobilePhone', 'Celular (com DDD)', moni_phone_input($registro['MobilePhonePrefix'] ?? '', $registro['MobilePhone'] ?? ''), ['type' => 'tel', 'inputmode' => 'tel', 'maxlength' => 16, 'placeholder' => '(11) 98888-1234', 'class' => 'moni-phone']); ?>

                                        <?php if ($tipo !== 'morador'): ?>
                                            <?php moni_input('Start', 'Início da permissão', moni_datetime_input($registro['Start'] ?? ''), ['type' => 'datetime-local']); ?>
                                            <?php moni_input('End', 'Fim da permissão', moni_datetime_input($registro['End'] ?? ''), ['type' => 'datetime-local']); ?>
                                        <?php endif; ?>

                                        <?php moni_textarea('Note', 'Observações', $registro['Note'] ?? ''); ?>
                                    </div>

                                    <div class="moni-form-actions">
                                        <?php if ($tipo === 'morador'): ?>
                                            <?php moni_checkbox('Responsible', 'Responsável pelo imóvel', !empty($registro['Responsible'])); ?>
                                        <?php endif; ?>
                                        <?php moni_checkbox('UseDigitalKeys', 'Usa chaves digitais', !empty($registro['UseDigitalKeys'])); ?>
                                    </div>

                                    <div class="moni-form-actions">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Salvar alterações</button>
                                        <a class="btn btn-secondary" href="<?= moni_e($voltar) ?>">Cancelar</a>
                                    </div>
                                </form>

                                <div style="margin-top:22px; padding-top:18px; border-top:1px solid var(--border); display:flex; justify-content:flex-end;">
                                    <form method="post" action="moni.php" onsubmit="return confirm('Excluir <?= moni_e(addslashes($registro['Name'] ?? 'esta pessoa')) ?> do Moni?<?= !empty($credencial['image']) ? ' A credencial facial também será removida.' : '' ?> Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="csrf" value="<?= moni_e($csrf) ?>">
                                        <input type="hidden" name="acao" value="excluir_pessoa">
                                        <input type="hidden" name="redir" value="<?= moni_e(moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo])) ?>">
                                        <input type="hidden" name="cliente" value="<?= (int) $cliente ?>">
                                        <input type="hidden" name="imovel" value="<?= (int) $imovel ?>">
                                        <input type="hidden" name="tipo" value="<?= moni_e($tipo) ?>">
                                        <input type="hidden" name="pessoa" value="<?= (int) $pessoa ?>">
                                        <button class="btn btn-danger" type="submit"><i class="fas fa-trash-can"></i> Excluir <?= moni_e(mb_strtolower($info['label'])) ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php elseif (!$erro_conexao && $cliente > 0 && $imovel !== null): ?>
                        <?php $voltarCliente = moni_link(['cliente' => $cliente]); ?>
                        <div class="moni-panel" style="margin-bottom:16px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                                <div>
                                    <h2 style="margin:0 0 4px;"><?= moni_e($imovelLabel) ?></h2>
                                    <p class="moni-muted" style="margin:0;"><?= moni_e($clienteInfo['name'] ?? '') ?></p>
                                </div>
                                <a class="btn btn-secondary btn-sm" href="<?= moni_e($voltarCliente) ?>"><i class="fas fa-arrow-left"></i> Voltar para imóveis</a>
                            </div>
                        </div>

                        <div class="moni-tabs">
                            <?php foreach (MoniApi::TIPOS as $chave => $infoTipo): ?>
                                <?php $qtd = (int) ($contagemImovel[$chave] ?? 0); ?>
                                <a class="moni-tab <?= $chave === $tipo ? 'active' : '' ?>" href="<?= moni_e(moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $chave])) ?>">
                                    <i class="fas <?= moni_e($infoTipo['icone']) ?>"></i>
                                    <?= moni_e($infoTipo['plural']) ?>
                                    <span class="moni-badge"><?= $qtd ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="moni-toolbar">
                            <div class="moni-search">
                                <i class="fas fa-search"></i>
                                <input type="text" id="moni-filtro-pessoas" placeholder="Filtrar <?= moni_e(mb_strtolower(MoniApi::TIPOS[$tipo]['plural'])) ?> por nome, RG, CPF ou telefone...">
                            </div>
                            <span class="moni-muted"><?= (int) $totalPessoas ?> registro(s)</span>
                        </div>

                        <?php if (!$pessoas): ?>
                            <div class="moni-empty">
                                <i class="fas <?= moni_e(MoniApi::TIPOS[$tipo]['icone']) ?>"></i>
                                Nenhum <?= moni_e(mb_strtolower(MoniApi::TIPOS[$tipo]['label'])) ?> cadastrado neste imóvel.
                            </div>
                        <?php else: ?>
                            <div class="moni-grid" id="moni-lista-pessoas">
                                <?php foreach ($pessoas as $p): ?>
                                    <?php
                                    $linkPessoa = moni_link(['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo, 'pessoa' => (int) $p['id']]);
                                    $contato = moni_phone_input($p['mobilephoneprefix'] ?? '', $p['mobilephone'] ?? '');
                                    if ($contato === '') {
                                        $contato = moni_phone_input($p['phoneprefix'] ?? '', $p['phone'] ?? '');
                                    }
                                    if ($contato === '' && !empty($p['email'])) {
                                        $contato = (string) $p['email'];
                                    }
                                    $documentos = [];
                                    if (!empty($p['rg'])) {
                                        $documentos[] = 'RG ' . $p['rg'];
                                    }
                                    $cpfDoc = moni_cpf_display($p['cpf'] ?? '');
                                    if ($cpfDoc !== '') {
                                        $documentos[] = 'CPF ' . $cpfDoc;
                                    }
                                    if ($tipo === 'morador') {
                                        $detalhe = !empty($p['responsible']) ? 'Responsável' : 'Morador';
                                        if (!empty($p['email'])) {
                                            $detalhe .= ' · ' . $p['email'];
                                        }
                                    } elseif ($tipo === 'visitante') {
                                        $ini = moni_display_date($p['start'] ?? '');
                                        $fim = moni_display_date($p['end'] ?? '');
                                        $detalhe = $ini !== '' ? $ini . ($fim !== '' ? ' até ' . $fim : '') : 'Sem período definido';
                                    } else {
                                        $detalhe = (string) ($p['servicetype'] ?? 'Prestador');
                                    }
                                    $personId = (int) ($p['personid'] ?? 0);
                                    $filtro = mb_strtolower(($p['name'] ?? '') . ' ' . ($p['rg'] ?? '') . ' ' . ($p['cpf'] ?? '') . ' ' . $contato . ' ' . $detalhe);
                                    ?>
                                    <article class="moni-pessoa-card" data-filtro="<?= moni_e($filtro) ?>">
                                        <div class="moni-pessoa-head">
                                            <?= moni_avatar_html($personId, $credenciais, (string) ($p['name'] ?? '')) ?>
                                            <div class="moni-pessoa-nome">
                                                <a href="<?= moni_e($linkPessoa) ?>"><?= moni_e($p['name'] ?? 'Sem nome') ?></a>
                                                <?php if ($documentos): ?>
                                                    <div class="moni-muted"><?= moni_e(implode(' · ', $documentos)) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="moni-pessoa-info">
                                            <div class="moni-pessoa-linha"><span>Contato</span><strong><?= moni_e($contato !== '' ? $contato : '—') ?></strong></div>
                                            <?php if ($tipo !== 'morador'): ?>
                                                <div class="moni-pessoa-linha"><span>Período da estadia</span><strong><?= moni_e($detalhe) ?></strong></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="moni-actions">
                                            <a class="btn btn-secondary btn-sm" href="<?= moni_e($linkPessoa) ?>"><i class="fas fa-pen-to-square"></i> Editar</a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?= moni_pagination($pagina, $totalPessoas, 100, ['cliente' => $cliente, 'imovel' => $imovel, 'tipo' => $tipo]) ?>
                        <?php endif; ?>

                    <?php elseif (!$erro_conexao && $cliente > 0): ?>
                        <?php
                        $endereco = moni_endereco($clienteInfo ?? []);
                        $rotuloImovel = trim((string) ($clienteInfo['idrealty1'] ?? '') . ' / ' . (string) ($clienteInfo['idrealty2'] ?? ''), ' /');
                        ?>
                        <div class="moni-panel" style="margin-bottom:16px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                                <div>
                                    <h2 style="margin:0 0 4px;"><?= moni_e($clienteInfo['name'] ?? 'Cliente') ?></h2>
                                    <p class="moni-muted" style="margin:0;">
                                        <?= moni_e($endereco !== '' ? $endereco : 'Endereço não informado') ?>
                                        · Código <?= (int) $cliente ?>
                                        <?php if ($rotuloImovel !== ''): ?> · <?= moni_e($rotuloImovel) ?><?php endif; ?>
                                    </p>
                                </div>
                                <a class="btn btn-secondary btn-sm" href="moni.php"><i class="fas fa-arrow-left"></i> Voltar para clientes</a>
                            </div>
                        </div>

                        <div class="moni-section-title">
                            <h2><i class="fas fa-door-open"></i> Imóveis</h2>
                            <span><?= count($realties) ?> imóvel(is)</span>
                        </div>

                        <div class="moni-toolbar">
                            <div class="moni-search">
                                <i class="fas fa-search"></i>
                                <input type="text" id="moni-filtro-imoveis" placeholder="Filtrar imóveis por bloco, apartamento ou código...">
                            </div>
                        </div>

                        <?php if (!$realties): ?>
                            <div class="moni-empty">
                                <i class="fas fa-door-closed"></i>
                                Nenhum imóvel cadastrado para este cliente.
                            </div>
                        <?php else: ?>
                            <div class="moni-grid" id="moni-lista-imoveis">
                                <?php foreach ($realties as $r): ?>
                                    <?php
                                    $code = (int) ($r['code'] ?? 0);
                                    $label = moni_realty_label($r, $clienteInfo);
                                    $c = $counts[$code] ?? ['morador' => 0, 'visitante' => 0, 'prestador' => 0];
                                    $textoFiltro = mb_strtolower($label . ' ' . ($r['id1'] ?? '') . ' ' . ($r['id2'] ?? '') . ' ' . $code . ' ' . ($r['extension'] ?? ''));
                                    ?>
                                    <a class="moni-card moni-unit" href="<?= moni_e(moni_link(['cliente' => $cliente, 'imovel' => $code])) ?>" data-filtro="<?= moni_e($textoFiltro) ?>">
                                        <div class="moni-unit-head">
                                            <span class="moni-unit-id"><?= moni_e($label) ?></span>
                                        </div>
                                        <div class="moni-unit-stats">
                                            <span><i class="fas fa-user"></i> <?= (int) $c['morador'] ?> morador(es)</span>
                                            <span><i class="fas fa-user-clock"></i> <?= (int) $c['visitante'] ?> visitante(s)</span>
                                            <span><i class="fas fa-screwdriver-wrench"></i> <?= (int) $c['prestador'] ?> prestador(es)</span>
                                        </div>
                                        <span class="moni-muted"><i class="fas fa-chevron-right"></i> Abrir imóvel</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php elseif (!$erro_conexao): ?>
                        <form method="get" action="moni.php" class="moni-toolbar">
                            <div class="moni-search">
                                <i class="fas fa-search"></i>
                                <input type="text" name="busca" value="<?= moni_e($busca) ?>" placeholder="Buscar edifício/cliente pelo nome...">
                            </div>
                            <label class="moni-check">
                                <input type="checkbox" name="todos" value="1" <?= $todos ? 'checked' : '' ?> onchange="this.form.submit()">
                                Mostrar todos os clientes
                            </label>
                            <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                            <?php if ($busca !== '' || $todos): ?>
                                <a class="btn btn-ghost btn-sm" href="moni.php">Limpar</a>
                            <?php endif; ?>
                        </form>

                        <div class="moni-section-title">
                            <h2><i class="fas fa-building"></i> Clientes</h2>
                            <span><?= (int) $totalClientes ?> cliente(s)</span>
                        </div>

                        <?php if (!$clientes): ?>
                            <div class="moni-empty">
                                <i class="fas fa-building-circle-exclamation"></i>
                                Nenhum cliente encontrado<?= $busca !== '' ? ' para "' . moni_e($busca) . '"' : '' ?>.
                            </div>
                        <?php else: ?>
                            <div class="moni-grid">
                                <?php foreach ($clientes as $c): ?>
                                    <?php $endereco = moni_endereco($c); ?>
                                    <a class="moni-card" href="<?= moni_e(moni_link(['cliente' => (int) $c['id']])) ?>">
                                        <div class="moni-card-icon"><i class="fas fa-building"></i></div>
                                        <div class="moni-card-body">
                                            <h3><?= moni_e($c['name'] ?? 'Cliente') ?></h3>
                                            <p><?= moni_e($endereco !== '' ? $endereco : 'Endereço não informado') ?></p>
                                            <p class="moni-muted" style="margin-top:5px;">Código <?= (int) ($c['id'] ?? 0) ?> · <?= moni_e(moni_tipo_cliente($c['type'] ?? 0)) ?></p>
                                        </div>
                                        <i class="fas fa-chevron-right moni-chevron"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <?= moni_pagination($pagina, $totalClientes, 60, ['busca' => $busca !== '' ? $busca : null, 'todos' => $todos ? 1 : null]) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function moniFiltrar(inputId, containerSelector, itemSelector) {
            var input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('input', function () {
                var termo = this.value.toLowerCase().trim();
                var container = document.querySelector(containerSelector);
                if (!container) return;
                container.querySelectorAll(itemSelector).forEach(function (item) {
                    var texto = (item.getAttribute('data-filtro') || item.textContent || '').toLowerCase();
                    item.style.display = termo === '' || texto.indexOf(termo) !== -1 ? '' : 'none';
                });
            });
        }

        moniFiltrar('moni-filtro-imoveis', '#moni-lista-imoveis', '[data-filtro]');
        moniFiltrar('moni-filtro-pessoas', '#moni-lista-pessoas', '.moni-pessoa-card[data-filtro]');

        document.querySelectorAll('input.moni-phone').forEach(function (input) {
            input.addEventListener('input', function () {
                var d = this.value.replace(/\D+/g, '').slice(0, 11);
                var out = '';
                if (d.length > 0) {
                    out = '(' + d.slice(0, 2);
                }
                if (d.length >= 3) {
                    out += ') ';
                    var resto = d.slice(2);
                    if (resto.length > 8) {
                        out += resto.slice(0, 5) + '-' + resto.slice(5);
                    } else if (resto.length > 4) {
                        out += resto.slice(0, 4) + '-' + resto.slice(4);
                    } else {
                        out += resto;
                    }
                }
                this.value = out;
            });
        });

        /*
         * Credencial facial: o Moni recusa imagens que não caibam em 1280x960
         * (ou 960x1280 na vertical) com "Invalid image resolution.".
         *
         * Ao escolher a foto, ela é ajustada e mostrada na prévia ao lado. O
         * envio acontece junto com o botão "Salvar alterações", como base64
         * (evita depender do GD no servidor e o upload do arquivo original).
         */
        (function () {
            var form = document.getElementById('moni-form-pessoa');
            var input = document.getElementById('moni-foto');
            if (!form || !input) return;
            var oculto = form.querySelector('input[name="foto_base64"]');
            if (!oculto) return;

            var caixa = document.querySelector('.moni-photo-box');
            var nomeArquivo = document.getElementById('moni-foto-nome');
            var MAX_LONGO = 1280;
            var MAX_CURTO = 960;

            function mostrarNome(texto) {
                if (nomeArquivo) nomeArquivo.textContent = texto;
            }

            function mostrarPrevia(src) {
                if (!caixa) return;
                var img = caixa.querySelector('.moni-photo');
                if (!img) {
                    img = document.createElement('img');
                    img.className = 'moni-photo';
                    caixa.insertBefore(img, caixa.firstChild);
                }
                img.src = src;
                img.alt = 'Prévia da credencial facial';
                var vazio = caixa.querySelector('.moni-photo-empty');
                if (vazio) vazio.style.display = 'none';
                var badge = caixa.querySelector('.moni-badge');
                if (badge) {
                    badge.className = 'moni-badge warn';
                    badge.innerHTML = '<i class="fas fa-circle-info"></i> Prévia — salve para gravar';
                }
            }

            input.addEventListener('change', function () {
                var arquivo = input.files && input.files[0];
                oculto.value = '';
                if (!arquivo) {
                    mostrarNome('Nenhuma foto selecionada');
                    return;
                }
                mostrarNome(arquivo.name + ' — ' + Math.round(arquivo.size / 1024) + ' KB');

                var url = URL.createObjectURL(arquivo);
                var img = new Image();

                img.onload = function () {
                    var largura = img.naturalWidth;
                    var altura = img.naturalHeight;

                    if (!largura || !altura) {
                        URL.revokeObjectURL(url);
                        input.value = '';
                        alert('Não foi possível ler a imagem selecionada. Envie uma foto JPG ou PNG.');
                        return;
                    }

                    // Dentro do limite e com tamanho de arquivo razoável: envia o
                    // arquivo original, sem recodificar.
                    if (Math.max(largura, altura) <= MAX_LONGO
                        && Math.min(largura, altura) <= MAX_CURTO
                        && arquivo.size <= 4 * 1024 * 1024) {
                        oculto.value = '';
                        mostrarPrevia(url);
                        return;
                    }

                    URL.revokeObjectURL(url);

                    var escala = Math.min(MAX_LONGO / Math.max(largura, altura), MAX_CURTO / Math.min(largura, altura), 1);
                    var novaLargura = Math.max(1, Math.floor(largura * escala));
                    var novaAltura = Math.max(1, Math.floor(altura * escala));

                    var canvas = document.createElement('canvas');
                    canvas.width = novaLargura;
                    canvas.height = novaAltura;
                    var ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, novaLargura, novaAltura);
                    ctx.drawImage(img, 0, 0, novaLargura, novaAltura);

                    var dataUrl;
                    try {
                        dataUrl = canvas.toDataURL('image/jpeg', escala < 1 ? 0.92 : 0.95);
                    } catch (e) {
                        dataUrl = '';
                    }
                    if (!dataUrl) {
                        input.value = '';
                        alert('Não foi possível preparar a imagem. Envie uma foto JPG ou PNG.');
                        return;
                    }

                    oculto.value = dataUrl;
                    mostrarPrevia(dataUrl);
                };

                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    input.value = '';
                    alert('Não foi possível ler a imagem selecionada. Envie uma foto JPG ou PNG.');
                };

                img.src = url;
            });

            form.addEventListener('submit', function () {
                // Com a prévia pronta, a imagem vai no campo oculto.
                if (oculto.value !== '') {
                    input.disabled = true;
                }
            });
        })();
    </script>
</body>
</html>
