<?php
/**
 * Cliente da API do Moni Software.
 *
 * Serviço principal: POST /api/auth/login e endpoints /api/... (X-Refresh-Token).
 * Moni Integração: endpoints de foto/credencial facial (Basic Auth, porta própria).
 */
class MoniApi
{
    public const TIPOS = [
        'morador' => [
            'resource' => 'customer-dwellers',
            'label' => 'Morador',
            'plural' => 'Moradores',
            'icone' => 'fa-user',
            'foto_consultar' => 'ConsultarFotoMorador',
            'foto_incluir' => 'IncluirFotoMorador',
            'foto_alterar' => 'AlterarFotoMorador',
            'foto_excluir' => 'ExcluirFotoMorador',
        ],
        'visitante' => [
            'resource' => 'customer-visitors',
            'label' => 'Visitante',
            'plural' => 'Visitantes',
            'icone' => 'fa-user-clock',
            'foto_consultar' => 'ConsultarFotoVisitanteLiberado',
            'foto_incluir' => 'IncluirFotoVisitanteLiberado',
            'foto_alterar' => 'AlterarFotoVisitanteLiberado',
            'foto_excluir' => 'ExcluirFotoVisitanteLiberado',
        ],
        'prestador' => [
            'resource' => 'customer-service-providers',
            'label' => 'Prestador',
            'plural' => 'Prestadores',
            'icone' => 'fa-screwdriver-wrench',
            'foto_consultar' => 'ConsultarFotoPrestadorServico',
            'foto_incluir' => 'IncluirFotoPrestadorServico',
            'foto_alterar' => 'AlterarFotoPrestadorServico',
            'foto_excluir' => 'ExcluirFotoPrestadorServico',
        ],
    ];

    private const CAMPOS_PESSOA = [
        'morador' => ['Id', 'PersonId', 'Name', 'RealtyId', 'Responsible', 'MobilePhonePrefix', 'MobilePhone', 'PhonePrefix', 'Phone', 'Email', 'Rg', 'Cpf', 'HasPhoto'],
        'visitante' => ['Id', 'PersonId', 'Name', 'RealtyId', 'Start', 'End', 'MobilePhonePrefix', 'MobilePhone', 'PhonePrefix', 'Phone', 'Rg', 'Cpf', 'HasPhoto'],
        'prestador' => ['Id', 'PersonId', 'Name', 'RealtyId', 'ServiceType', 'MobilePhonePrefix', 'MobilePhone', 'PhonePrefix', 'Phone', 'Rg', 'Cpf', 'HasPhoto'],
    ];

    private $config;
    private $token = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require __DIR__ . '/moni_config.php';
    }

    public function baseUrl(): string
    {
        return rtrim($this->config['base_url'], '/');
    }

    public function integrationUrl(): string
    {
        return rtrim($this->config['integration_url'], '/');
    }

    /* ------------------------------------------------------------------ */
    /* HTTP / autenticação                                                 */
    /* ------------------------------------------------------------------ */

    private function http(string $url, string $method = 'GET', $body = null, array $headers = [], ?string $basic = null): array
    {
        if (!function_exists('curl_init')) {
            return ['code' => 0, 'body' => '', 'error' => 'A extensão cURL do PHP não está habilitada.'];
        }

        $ch = curl_init($url);
        $h = array_merge(['Accept: application/json'], $headers);
        if ($body !== null && !$this->hasHeader($h, 'Content-Type')) {
            $h[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $h,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($basic !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, $basic);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return ['code' => $code, 'body' => $resp === false ? '' : $resp, 'error' => $error];
    }

    private function hasHeader(array $headers, string $name): bool
    {
        foreach ($headers as $header) {
            if (stripos($header, $name . ':') === 0) {
                return true;
            }
        }
        return false;
    }

    private function tokenCacheFile(): string
    {
        return sys_get_temp_dir() . '/blindado_moni_token.json';
    }

    private function readCachedToken(): ?string
    {
        $file = $this->tokenCacheFile();
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data) || empty($data['token'])) {
            return null;
        }
        if (!empty($data['exp']) && (int) $data['exp'] < time()) {
            return null;
        }
        return (string) $data['token'];
    }

    private function storeToken(string $token): void
    {
        $file = $this->tokenCacheFile();
        @file_put_contents($file, json_encode([
            'token' => $token,
            'exp' => time() + (30 * 86400),
        ]), LOCK_EX);
    }

    private function clearToken(): void
    {
        $this->token = null;
        @unlink($this->tokenCacheFile());
    }

    private function getToken(bool $force = false): string
    {
        if (!$force && $this->token) {
            return $this->token;
        }
        if (!$force) {
            $cached = $this->readCachedToken();
            if ($cached) {
                $this->token = $cached;
                return $this->token;
            }
        }

        $r = $this->http($this->baseUrl() . '/api/auth/login', 'POST', [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
        ], ['Content-Type: application/json']);

        if ($r['code'] !== 200) {
            throw new RuntimeException('Não foi possível autenticar no Moni (HTTP ' . $r['code'] . '). ' . $this->describeError($r));
        }
        $j = json_decode((string) $r['body'], true);
        $token = $j['RefreshToken'] ?? ($j['refreshToken'] ?? null);
        if (!$token) {
            throw new RuntimeException('O Moni respondeu ao login sem o token de acesso.');
        }

        $this->token = (string) $token;
        $this->storeToken($this->token);
        return $this->token;
    }

    private function api(string $path, string $method = 'GET', $body = null, array $headers = []): array
    {
        $token = $this->getToken();
        $h = array_merge($headers, ['X-Refresh-Token: ' . $token]);
        $r = $this->http($this->baseUrl() . $path, $method, $body, $h);

        if ($r['code'] === 401) {
            $this->clearToken();
            $token = $this->getToken(true);
            $h = array_merge($headers, ['X-Refresh-Token: ' . $token]);
            $r = $this->http($this->baseUrl() . $path, $method, $body, $h);
        }

        return $r;
    }

    private function httpMultipart(string $path, string $field, string $tmpPath, string $mime, string $filename = 'binaryFile'): array
    {
        $send = function (string $token) use ($path, $field, $tmpPath, $mime, $filename) {
            $ch = curl_init($this->baseUrl() . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [$field => new CURLFile($tmpPath, $mime, $filename)],
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Refresh-Token: ' . $token],
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            return ['code' => $code, 'body' => $resp === false ? '' : $resp, 'error' => $error];
        };

        $r = $send($this->getToken());
        if ($r['code'] === 401) {
            $this->clearToken();
            $r = $send($this->getToken(true));
        }
        return $r;
    }

    private function integration(string $endpoint, array $body): array
    {
        return $this->http(
            $this->integrationUrl() . '/' . ltrim($endpoint, '/'),
            'POST',
            $body,
            ['Content-Type: application/json; charset=UTF-8'],
            $this->config['integration_username'] . ':' . $this->config['integration_password']
        );
    }

    public function describeError(array $r): string
    {
        if (!empty($r['error'])) {
            $erro = (string) $r['error'];
            if (stripos($erro, 'Connection refused') !== false || stripos($erro, 'Failed to connect') !== false) {
                return 'Não foi possível conectar ao servidor do Moni. A conexão foi recusada — '
                    . 'verifique o firewall entre a hospedagem e o servidor do Moni. '
                    . 'Detalhe técnico: ' . $erro;
            }
            if (stripos($erro, 'timed out') !== false || stripos($erro, 'Network is unreachable') !== false) {
                return 'Não foi possível conectar ao servidor do Moni (tempo esgotado ou rede indisponível). '
                    . 'Detalhe técnico: ' . $erro;
            }
            return $erro;
        }
        $body = trim((string) ($r['body'] ?? ''));
        if ($body === '') {
            return 'Sem detalhes na resposta.';
        }
        $j = json_decode($body, true);
        if (is_array($j)) {
            if (isset($j['Erro'])) {
                return (string) $j['Erro'];
            }
            if (isset($j['Message'])) {
                return (string) $j['Message'];
            }
            if (isset($j['title']) || isset($j['detail'])) {
                return trim((string) ($j['title'] ?? '') . ' ' . (string) ($j['detail'] ?? ''));
            }
            if (isset($j[0]) && is_array($j[0])) {
                if (isset($j[0]['Erro'])) {
                    return (string) $j[0]['Erro'];
                }
                if (isset($j[0]['Message'])) {
                    return (string) $j[0]['Message'];
                }
            }
        }
        return mb_substr(strip_tags($body), 0, 300);
    }

    private function integrationMessage(array $r): string
    {
        $j = json_decode((string) ($r['body'] ?? ''), true);
        if (is_array($j)) {
            if (isset($j[0]['Mensagem'])) {
                return (string) $j[0]['Mensagem'];
            }
            if (isset($j[0]['Erro'])) {
                return (string) $j[0]['Erro'];
            }
            if (isset($j['Erro'])) {
                return (string) $j['Erro'];
            }
        }
        return $this->describeError($r);
    }

    /* ------------------------------------------------------------------ */
    /* Consultas                                                           */
    /* ------------------------------------------------------------------ */

    private function query(array $fields, array $conditions = [], array $joins = []): array
    {
        return [
            'fields' => ['return' => $fields],
            'conditions' => $conditions,
            'joins' => $joins,
        ];
    }

    public function select(string $resource, array $query, int $page = 1, int $limit = 100, string $order = '', string $sort = ''): array
    {
        $payload = [
            'q' => [base64_encode(json_encode($query, JSON_UNESCAPED_UNICODE))],
            'page' => max(1, $page),
            'limit' => max(1, $limit),
        ];
        if ($order !== '') {
            $payload['order'] = $order;
            $payload['sort'] = $sort;
        }

        $r = $this->api('/api/' . $resource . '/select', 'POST', $payload, ['Content-Type: application/json']);
        if ($r['code'] === 200) {
            $j = json_decode((string) $r['body'], true);
            return [
                'ok' => true,
                'data' => is_array($j['Data'] ?? null) ? $j['Data'] : [],
                'total' => (int) ($j['Total'] ?? 0),
            ];
        }

        return ['ok' => false, 'data' => [], 'total' => 0, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    public function customers(string $busca = '', bool $todos = false, int $page = 1, int $limit = 60): array
    {
        $fields = ['Id', 'Name', 'Type', 'SecondCode', 'IdRealty1', 'IdRealty2', 'Address', 'AddressNumber', 'District', 'Contact', 'Email', 'City.Description'];
        $conditions = [];
        if ($busca !== '') {
            $conditions[] = ['name' => 'Name', 'operator' => 'like', 'values' => ['%' . $busca . '%']];
        }
        if (!$todos) {
            $conditions[] = ['name' => 'Type', 'operator' => '=', 'values' => ['6']];
        }
        $joins = [[
            'property' => 'City',
            'conditions' => [[
                'propertyType' => 'Property',
                'name' => 'City.Id',
                'values' => ['CityId'],
                'operator' => '=',
            ]],
        ]];

        return $this->select('customers', $this->query($fields, $conditions, $joins), $page, $limit, 'Name', 'asc');
    }

    public function customer(int $id): ?array
    {
        $res = $this->select('customers', $this->query(
            ['Id', 'Name', 'Type', 'SecondCode', 'IdRealty1', 'IdRealty2', 'Address', 'AddressNumber', 'District', 'Contact', 'Email', 'City.Description'],
            [['name' => 'Id', 'operator' => '=', 'values' => [(string) $id]]],
            [[
                'property' => 'City',
                'conditions' => [[
                    'propertyType' => 'Property',
                    'name' => 'City.Id',
                    'values' => ['CityId'],
                    'operator' => '=',
                ]],
            ]]
        ), 1, 1);

        return $res['ok'] && !empty($res['data'][0]) ? $res['data'][0] : null;
    }

    public function realties(int $customerId): array
    {
        $fields = ['Id1', 'Id2', 'Code', 'Telephone', 'Extension', 'Ddd', 'CustomerId'];
        $conditions = [['name' => 'CustomerId', 'operator' => '=', 'values' => [(string) $customerId]]];
        $query = $this->query($fields, $conditions);

        $all = [];
        $page = 1;
        do {
            $res = $this->select('customer-realties', $query, $page, 200, 'Id1;Id2', 'asc;asc');
            if (!$res['ok']) {
                return $res;
            }
            $all = array_merge($all, $res['data']);
            $page++;
        } while (count($all) < $res['total'] && $page <= 20);

        return ['ok' => true, 'data' => $all, 'total' => count($all)];
    }

    /**
     * Todos os imóveis de todos os clientes, em poucas chamadas paginadas.
     * Usado pelo formulário de locação para montar a lista de apartamentos.
     */
    public function allRealties(): array
    {
        $fields = ['Id1', 'Id2', 'Code', 'CustomerId'];
        $query = $this->query($fields);
        $all = [];
        $page = 1;
        do {
            $res = $this->select('customer-realties', $query, $page, 500, 'Id1;Id2', 'asc;asc');
            if (!$res['ok']) {
                return $res;
            }
            $all = array_merge($all, $res['data']);
            $page++;
        } while (count($all) < $res['total'] && $page <= 20);

        return ['ok' => true, 'data' => $all, 'total' => count($all)];
    }

    /**
     * Normaliza o rótulo do imóvel para comparar o que veio do formulário
     * (ex.: "202") com o que está no Moni (Id1 + Id2).
     */
    private function normalizarRotuloImovel(string $s): string
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
     * Localiza o código do imóvel no Moni a partir do número exibido no
     * formulário. É esse código que a API usa como RealtyId.
     */
    public function realtyByLabel(int $customerId, string $label): ?array
    {
        $alvo = $this->normalizarRotuloImovel($label);
        if ($alvo === '') {
            return null;
        }
        $res = $this->realties($customerId);
        if (!$res['ok']) {
            // Uma falha transitória de rede/token não deve impedir a locação.
            $res = $this->realties($customerId);
        }
        if (!$res['ok']) {
            return null;
        }
        foreach ($res['data'] as $im) {
            $rotulo = trim((string) ($im['id1'] ?? '') . ' ' . (string) ($im['id2'] ?? ''));
            if ($this->normalizarRotuloImovel($rotulo) === $alvo) {
                return $im;
            }
        }
        return null;
    }

    /**
     * Cria um visitante (locação) no imóvel informado.
     */
    public function createVisitor(array $data): array
    {
        $r = $this->api('/api/customer-visitors', 'POST', $data, ['Content-Type: application/json']);
        if (in_array($r['code'], [200, 201, 204], true)) {
            $j = json_decode((string) $r['body'], true);
            return ['ok' => true, 'data' => is_array($j) ? $j : []];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /**
     * Cria a credencial do visitante. É este passo que permite o posterior
     * facialSave(): sem credencial, a API recusa o PersonId (erro 0_84).
     */
    public function credentialCreate(int $customerId, int $visitorId, string $description): array
    {
        if ($customerId <= 0 || $visitorId <= 0) {
            return ['ok' => false, 'error' => 'Visitante sem identificador no Moni.'];
        }
        $r = $this->api('/api/customer-credentials', 'POST', [
            'CustomerId' => $customerId,
            'VisitorId' => $visitorId,
            'Description' => mb_substr(trim($description), 0, 255),
        ], ['Content-Type: application/json']);
        if (in_array($r['code'], [200, 201, 204], true)) {
            $j = json_decode((string) $r['body'], true);
            return [
                'ok' => true,
                'id' => (int) ($j['Id'] ?? 0),
                'data' => is_array($j) ? $j : [],
            ];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /**
     * Habilita o uso da biometria facial na credencial (passo 4 do wizard).
     */
    public function credentialAllowFacial(int $customerId, int $credentialId): array
    {
        if ($customerId <= 0 || $credentialId <= 0) {
            return ['ok' => false, 'error' => 'Credencial sem identificador no Moni.'];
        }
        $r = $this->api(
            '/api/customer-credentials/' . $customerId . '/' . $credentialId,
            'PATCH',
            [['op' => 'replace', 'path' => '/AllowUseFacialBiometric', 'value' => true]],
            ['Content-Type: application/json-patch+json']
        );
        if (in_array($r['code'], [200, 204], true)) {
            return ['ok' => true, 'message' => 'Biometria facial habilitada na credencial.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /**
     * Localiza o perfil de acesso de visitantes do cliente. O nome varia entre
     * clientes (VISITANTES, VISITANTE, Visitantes...), então a comparação é
     * feita sem acentos e sem caixa. O Id é consultado na API, nunca fixo.
     */
    public function visitorAccessProfileId(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['ok' => false, 'id' => 0, 'error' => 'Cliente inválido no Moni.'];
        }
        static $cache = [];
        if (isset($cache[$customerId])) {
            return $cache[$customerId];
        }

        $res = $this->select('customer-access-profiles', $this->query(
            ['Id', 'Description'],
            [['name' => 'CustomerId', 'operator' => '=', 'values' => [(string) $customerId]]]
        ), 1, 100);
        if (!$res['ok']) {
            return ['ok' => false, 'id' => 0, 'error' => $res['error'] ?? 'Falha ao consultar perfis de acesso.'];
        }

        $norm = static function (string $s): string {
            $s = mb_strtoupper($s, 'UTF-8');
            $s = strtr($s, [
                'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'É' => 'E', 'Ê' => 'E',
                'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C',
            ]);
            return preg_replace('/[^A-Z]/', '', $s) ?? '';
        };

        $achados = [];
        foreach ($res['data'] as $row) {
            $id = (int) ($row['id'] ?? $row['Id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $nomeOriginal = (string) ($row['description'] ?? $row['Description'] ?? '');
            $nome = $norm($nomeOriginal);
            if ($nome === 'VISITANTES') {
                $achados[3] = [$id, $nomeOriginal];
            } elseif ($nome === 'VISITANTE' && !isset($achados[2])) {
                $achados[2] = [$id, $nomeOriginal];
            } elseif (strpos($nome, 'VISITANT') !== false && !isset($achados[1])) {
                $achados[1] = [$id, $nomeOriginal];
            }
        }
        krsort($achados);
        if (!$achados) {
            $out = ['ok' => false, 'id' => 0, 'error' => 'Cliente sem perfil de acesso VISITANTES no Moni.'];
        } else {
            $primeiro = reset($achados);
            $out = ['ok' => true, 'id' => (int) $primeiro[0], 'nome' => (string) $primeiro[1]];
        }
        $cache[$customerId] = $out;
        return $out;
    }

    /**
     * Vincula o perfil de acesso à credencial (passo 2 do wizard). O campo é
     * ignorado no POST /api/customer-credentials, por isso vai por PATCH.
     */
    public function credentialSetAccessProfile(int $customerId, int $credentialId, int $profileId): array
    {
        if ($customerId <= 0 || $credentialId <= 0 || $profileId <= 0) {
            return ['ok' => false, 'error' => 'Dados insuficientes para vincular o perfil.'];
        }
        $r = $this->api(
            '/api/customer-credentials/' . $customerId . '/' . $credentialId,
            'PATCH',
            [['op' => 'replace', 'path' => '/AccessProfileId', 'value' => $profileId]],
            ['Content-Type: application/json-patch+json']
        );
        if (in_array($r['code'], [200, 204], true)) {
            return ['ok' => true, 'message' => 'Perfil de acesso vinculado à credencial.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /**
     * Cria a credencial do visitante já com perfil e biometria facial.
     * Chamada sempre que um visitante é enviado ao Moni, com ou sem selfie.
     */
    public function credentialEnsure(int $customerId, int $visitorId, string $description): array
    {
        $criada = $this->credentialCreate($customerId, $visitorId, $description);
        if (!$criada['ok']) {
            return $criada;
        }
        $id = (int) ($criada['id'] ?? 0);
        $avisos = [];

        // Passo 2 do wizard: perfil VISITANTES. Sem ele a facial não sincroniza.
        $perfil = $this->visitorAccessProfileId($customerId);
        $perfilId = (int) ($perfil['id'] ?? 0);
        $perfilOk = false;
        if ($perfilId > 0) {
            $vinculo = $this->credentialSetAccessProfile($customerId, $id, $perfilId);
            $perfilOk = (bool) $vinculo['ok'];
            if (!$perfilOk) {
                $avisos[] = 'Perfil ' . ($perfil['nome'] ?? 'VISITANTES') . ' não vinculado: '
                    . ($vinculo['error'] ?? 'falha');
            }
        } else {
            $avisos[] = $perfil['error'] ?? 'Perfil VISITANTES não encontrado no Moni.';
        }

        // Passo 4 do wizard: biometria facial habilitada.
        $facial = $this->credentialAllowFacial($customerId, $id);
        if (!$facial['ok']) {
            $avisos[] = 'Biometria facial não habilitada: ' . ($facial['error'] ?? 'falha');
        }

        return [
            'ok' => true,
            'id' => $id,
            'perfil_id' => $perfilId,
            'perfil' => $perfilOk,
            'facial' => (bool) $facial['ok'],
            'aviso' => implode('; ', $avisos),
        ];
    }

    /**
     * Remove uma credencial pelo Id.
     */
    public function credentialDelete(int $customerId, int $credentialId): array
    {
        if ($customerId <= 0 || $credentialId <= 0) {
            return ['ok' => false, 'error' => 'Credencial sem identificador no Moni.'];
        }
        $r = $this->api('/api/customer-credentials/' . $customerId . '/' . $credentialId, 'DELETE');
        if (in_array($r['code'], [200, 204], true) || $r['code'] === 404) {
            return ['ok' => true, 'code' => $r['code']];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /**
     * Remove as credenciais vinculadas a um visitante. O Moni recusa excluir
     * o visitante enquanto houver credencial (HTTP 409), então a limpeza vem
     * antes da exclusão.
     */
    public function credentialDeleteByVisitor(int $customerId, int $visitorId): array
    {
        if ($customerId <= 0 || $visitorId <= 0) {
            return ['ok' => true, 'removidas' => 0, 'erros' => []];
        }
        $res = $this->select('customer-credentials', $this->query(
            ['Id'],
            [
                ['name' => 'CustomerId', 'operator' => '=', 'values' => [(string) $customerId]],
                ['name' => 'VisitorId', 'operator' => '=', 'values' => [(string) $visitorId]],
            ]
        ), 1, 50);
        if (!$res['ok']) {
            return ['ok' => false, 'removidas' => 0, 'erros' => [$res['error'] ?? 'Falha ao consultar credenciais.']];
        }
        $removidas = 0;
        $erros = [];
        foreach ($res['data'] as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $del = $this->credentialDelete($customerId, $id);
            if ($del['ok']) {
                $removidas++;
            } else {
                $erros[] = 'Credencial ' . $id . ': ' . ($del['error'] ?? 'falha');
            }
        }
        return ['ok' => true, 'removidas' => $removidas, 'erros' => $erros];
    }

    public function realty(int $customerId, int $code): ?array
    {
        $res = $this->select('customer-realties', $this->query(
            ['Id1', 'Id2', 'Code', 'Telephone', 'Extension', 'Ddd', 'CustomerId'],
            [
                ['name' => 'CustomerId', 'operator' => '=', 'values' => [(string) $customerId]],
                ['name' => 'Code', 'operator' => '=', 'values' => [(string) $code]],
            ]
        ), 1, 1);

        return $res['ok'] && !empty($res['data'][0]) ? $res['data'][0] : null;
    }

    public function people(string $tipo, int $customerId, int $realtyId, int $page = 1, int $limit = 100): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['ok' => false, 'data' => [], 'total' => 0, 'error' => 'Tipo de pessoa inválido.'];
        }
        $conditions = [
            ['name' => 'CustomerId', 'operator' => '=', 'values' => [(string) $customerId]],
            ['name' => 'RealtyId', 'operator' => '=', 'values' => [(string) $realtyId]],
        ];

        return $this->select(self::TIPOS[$tipo]['resource'], $this->query(self::CAMPOS_PESSOA[$tipo], $conditions), $page, $limit, 'Name', 'asc');
    }

    /**
     * Conta moradores, visitantes e prestadores por imóvel.
     * Retorna [realtyId => ['morador' => n, 'visitante' => n, 'prestador' => n]].
     */
    public function countsByRealty(int $customerId): array
    {
        $counts = [];
        foreach (self::TIPOS as $tipo => $info) {
            $query = $this->query(['Id', 'RealtyId'], [['name' => 'CustomerId', 'operator' => '=', 'values' => [(string) $customerId]]]);
            $page = 1;
            $lidos = 0;
            do {
                $res = $this->select($info['resource'], $query, $page, 500);
                if (!$res['ok']) {
                    break;
                }
                foreach ($res['data'] as $row) {
                    $rid = (int) ($row['realtyid'] ?? 0);
                    if (!isset($counts[$rid])) {
                        $counts[$rid] = ['morador' => 0, 'visitante' => 0, 'prestador' => 0];
                    }
                    $counts[$rid][$tipo]++;
                }
                $lidos += count($res['data']);
                $page++;
            } while ($lidos < $res['total'] && $page <= 20);
        }
        return $counts;
    }

    public function get(string $tipo, int $customerId, int $id): ?array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return null;
        }
        $r = $this->api('/api/' . self::TIPOS[$tipo]['resource'] . '/' . $customerId . '/' . $id, 'GET');
        if ($r['code'] !== 200) {
            return null;
        }
        $j = json_decode((string) $r['body'], true);
        return is_array($j) ? $j : null;
    }

    /**
     * Dados da pessoa (registro compartilhado). Nome, RG, CPF e telefones
     * ficam aqui, não no vínculo com o imóvel.
     */
    public function person(int $personId): ?array
    {
        if ($personId <= 0) {
            return null;
        }
        $r = $this->api('/api/persons/' . $personId, 'GET');
        if ($r['code'] !== 200) {
            return null;
        }
        $j = json_decode((string) $r['body'], true);
        return is_array($j) ? $j : null;
    }

    /**
     * Atualiza a pessoa em si. É o caminho correto para nome/RG/CPF: enviar
     * esses campos no vínculo (customer-dwellers/visitors/...) faz o Moni
     * criar uma pessoa nova, o que desliga a credencial facial.
     */
    public function patchPerson(int $personId, array $operations): array
    {
        if ($personId <= 0) {
            return ['ok' => false, 'error' => 'Pessoa sem PersonId no Moni.'];
        }
        if (!$operations) {
            return ['ok' => true, 'message' => 'Nenhuma alteração.'];
        }
        $r = $this->api(
            '/api/persons/' . $personId,
            'PATCH',
            $operations,
            ['Content-Type: application/json-patch+json']
        );
        if (in_array($r['code'], [200, 204], true)) {
            return ['ok' => true, 'message' => 'Dados atualizados com sucesso.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    public function patch(string $tipo, int $customerId, int $id, array $operations): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['ok' => false, 'error' => 'Tipo de pessoa inválido.'];
        }
        if (!$operations) {
            return ['ok' => true, 'message' => 'Nenhuma alteração.'];
        }
        $r = $this->api(
            '/api/' . self::TIPOS[$tipo]['resource'] . '/' . $customerId . '/' . $id,
            'PATCH',
            $operations,
            ['Content-Type: application/json-patch+json']
        );
        if (in_array($r['code'], [200, 204], true)) {
            return ['ok' => true, 'message' => 'Dados atualizados com sucesso.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    public function delete(string $tipo, int $customerId, int $id): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['ok' => false, 'error' => 'Tipo de pessoa inválido.'];
        }
        $r = $this->api('/api/' . self::TIPOS[$tipo]['resource'] . '/' . $customerId . '/' . $id, 'DELETE');
        if (in_array($r['code'], [200, 204], true)) {
            return ['ok' => true, 'message' => self::TIPOS[$tipo]['label'] . ' excluído com sucesso.'];
        }
        if ($r['code'] === 409) {
            // O Moni devolve 409 com corpo vazio quando ainda existe credencial
            // vinculada. A limpeza normal acontece antes, em moni.php; esta mensagem
            // cobre credenciais criadas fora do nosso fluxo.
            $msg = $tipo === 'visitante'
                ? 'Não foi possível excluir o visitante: ele ainda possui credencial vinculada no Moni.'
                : 'Não foi possível excluir: o registro possui vínculos no Moni.';
            return ['ok' => false, 'code' => 409, 'error' => $msg];
        }
        return ['ok' => false, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /* ------------------------------------------------------------------ */
    /* Credencial facial (person-biometric-facial)                         */
    /* ------------------------------------------------------------------ */

    /**
     * Metadados da credencial facial. A chave é o PersonId da pessoa.
     * Retorna exists=false quando o Moni responde 404 (sem credencial).
     */
    public function facialInfo(int $personId): array
    {
        if ($personId <= 0) {
            return ['ok' => true, 'exists' => false, 'info' => null];
        }
        $r = $this->api('/api/person-biometric-facial/' . $personId, 'GET');
        if ($r['code'] === 200) {
            $j = json_decode((string) $r['body'], true);
            return ['ok' => true, 'exists' => true, 'info' => is_array($j) ? $j : []];
        }
        if ($r['code'] === 404) {
            return ['ok' => true, 'exists' => false, 'info' => null];
        }
        return ['ok' => false, 'exists' => false, 'info' => null, 'code' => $r['code'], 'error' => $this->describeError($r)];
    }

    /**
     * Imagem da credencial facial. Retorna image=null quando não há credencial.
     */
    public function facialImage(int $personId): array
    {
        if ($personId <= 0) {
            return ['ok' => true, 'image' => null, 'mime' => 'image/jpeg', 'info' => null, 'message' => 'Pessoa sem PersonId no Moni.'];
        }
        $r = $this->api('/api/person-biometric-facial/' . $personId . '/image', 'GET');
        if ($r['code'] === 200) {
            $j = json_decode((string) $r['body'], true);
            $data = is_array($j) ? ($j['Data'] ?? null) : null;
            if (!$data) {
                return ['ok' => true, 'image' => null, 'mime' => 'image/jpeg', 'info' => is_array($j) ? $j : null, 'message' => 'Imagem vazia.'];
            }
            $mime = (string) ($j['MimeType'] ?? '');
            if ($mime === '') {
                $ext = strtolower((string) ($j['Extension'] ?? ''));
                $mime = $ext === 'png' ? 'image/png' : ($ext === 'bmp' ? 'image/bmp' : 'image/jpeg');
            }
            return ['ok' => true, 'image' => (string) $data, 'mime' => $mime, 'info' => $j];
        }
        if ($r['code'] === 404) {
            return ['ok' => true, 'image' => null, 'mime' => 'image/jpeg', 'info' => null, 'message' => 'Sem credencial facial cadastrada.'];
        }
        return ['ok' => false, 'image' => null, 'mime' => 'image/jpeg', 'info' => null, 'code' => $r['code'], 'message' => $this->describeError($r)];
    }

    /**
     * Envia a credencial facial. O deviceType 0 corresponde a um upload manual
     * (mesmo comportamento do frontend do Moni).
     */
    public function facialSave(int $personId, string $tmpPath, string $mime, int $deviceType = 0): array
    {
        if ($personId <= 0) {
            return ['ok' => false, 'message' => 'Pessoa sem PersonId no Moni.'];
        }
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/bmp'], true)) {
            $mime = 'image/jpeg';
        }
        $r = $this->httpMultipart(
            '/api/person-biometric-facial/' . $personId . '?deviceType=' . max(0, $deviceType),
            'formFile',
            $tmpPath,
            $mime
        );
        if (in_array($r['code'], [200, 201, 204], true)) {
            return ['ok' => true, 'code' => $r['code'], 'message' => 'Credencial facial salva com sucesso.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'message' => $this->describeError($r)];
    }

    public function facialDelete(int $personId): array
    {
        if ($personId <= 0) {
            return ['ok' => false, 'message' => 'Pessoa sem PersonId no Moni.'];
        }
        $r = $this->api('/api/person-biometric-facial/' . $personId, 'DELETE');
        if (in_array($r['code'], [200, 204], true)) {
            return ['ok' => true, 'code' => $r['code'], 'message' => 'Credencial facial removida com sucesso.'];
        }
        if ($r['code'] === 404) {
            return ['ok' => true, 'code' => 404, 'message' => 'Não havia credencial facial cadastrada.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'message' => $this->describeError($r)];
    }

    /**
     * Conjunto de PersonIds que possuem credencial facial, para indicadores de lista.
     * Retorna [PersonId => true].
     */
    public function facialPersonIds(): array
    {
        $res = $this->select('person-biometric-facial', $this->query(['PersonId']), 1, 5000);
        if (!$res['ok']) {
            return [];
        }
        $ids = [];
        foreach ($res['data'] as $row) {
            $pid = (int) ($row['personid'] ?? 0);
            if ($pid > 0) {
                $ids[$pid] = true;
            }
        }
        return $ids;
    }

    /* ------------------------------------------------------------------ */
    /* Foto do cadastro (Moni Integração - API antiga)                     */
    /* ------------------------------------------------------------------ */

    public function photo(string $tipo, int $customerId, int $id): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['ok' => false, 'image' => null, 'message' => 'Tipo de pessoa inválido.'];
        }
        $r = $this->integration(self::TIPOS[$tipo]['foto_consultar'], [
            'Cliente' => $customerId,
            'Codigo' => $id,
        ]);

        if ($r['code'] === 200) {
            $j = json_decode((string) $r['body'], true);
            $img = is_array($j) ? ($j[0]['imagem'] ?? null) : null;
            return ['ok' => true, 'image' => $img ?: null, 'message' => $img ? '' : 'Sem foto.'];
        }
        if ($r['code'] === 404) {
            return ['ok' => true, 'image' => null, 'message' => $this->integrationMessage($r)];
        }
        return ['ok' => false, 'image' => null, 'message' => $this->integrationMessage($r)];
    }

    public function photoSave(string $tipo, int $customerId, int $id, string $base64, bool $alterar): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['ok' => false, 'message' => 'Tipo de pessoa inválido.'];
        }
        $endpoint = $alterar ? self::TIPOS[$tipo]['foto_alterar'] : self::TIPOS[$tipo]['foto_incluir'];
        $body = ['Cliente' => $customerId, 'Codigo' => $id, 'Imagem' => $base64];

        $r = $this->integration($endpoint, $body);
        if ($r['code'] === 200) {
            return ['ok' => true, 'message' => $this->integrationMessage($r) ?: 'Credencial salva com sucesso.'];
        }

        // Já existe foto: tenta alterar.
        if ($r['code'] === 409 && !$alterar) {
            $r = $this->integration(self::TIPOS[$tipo]['foto_alterar'], $body);
            if ($r['code'] === 200) {
                return ['ok' => true, 'message' => $this->integrationMessage($r) ?: 'Credencial atualizada com sucesso.'];
            }
        }

        return ['ok' => false, 'code' => $r['code'], 'message' => $this->integrationMessage($r)];
    }

    public function photoDelete(string $tipo, int $customerId, int $id): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['ok' => false, 'message' => 'Tipo de pessoa inválido.'];
        }
        $r = $this->integration(self::TIPOS[$tipo]['foto_excluir'], [
            'Cliente' => $customerId,
            'Codigo' => $id,
        ]);
        if ($r['code'] === 200) {
            return ['ok' => true, 'message' => $this->integrationMessage($r) ?: 'Credencial removida com sucesso.'];
        }
        return ['ok' => false, 'code' => $r['code'], 'message' => $this->integrationMessage($r)];
    }
}
