<?php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/helper_mail.php';

$items = [
    'edificio_id'          => 1,
    'edificio_nome'        => 'EDIFICIO TESTE',
    'edificio_endereco'    => 'Rua Teste, 123',
    'administradora_id'    => 1,
    'administradora_nome'  => 'Administradora Teste',
    'administradora_email' => 'monitoramento@blindadosolucoes.com.br',
    'tipo_usuario'         => 'locador',
    'numero_apartamento'   => '101',
    'locador_nome'         => 'João da Silva',
    'locador_telefone'     => '+55 11 99999-0000',
    'data_entrada'         => '2026-09-20',
    'data_saida'           => '2026-09-25',
    'observacoes'          => "Entregar chaves na portaria.\nVisita em nome do condomínio.",
    'data_locacao'         => '2026-09-16',
    'inquilinos' => [
        ['nome' => 'Maria Souza', 'documento' => '123.456.789-00', 'telefone' => '+55 11 98888-1111', 'selfie' => ''],
        ['nome' => 'Pedro Souza', 'documento' => '987.654.321-00', 'telefone' => '+55 11 97777-2222', 'selfie' => ''],
    ],
    'veiculos' => [
        ['modelo' => 'Fiat Argo', 'cor' => 'Prata', 'placa' => 'ABC-1234', 'acesso_garagem' => 'Sim'],
        ['modelo' => 'Honda Civic', 'cor' => 'Preto', 'placa' => 'XYZ-9876', 'acesso_garagem' => 'Sim'],
    ],
];

// Garantir toggle habilitado para o teste
$r = $conn->query("SELECT enviar_email_adm FROM edificios WHERE id = 1");
if (!$r || (int)($r->fetch_assoc()['enviar_email_adm'] ?? 0) !== 1) {
    $conn->query("UPDATE edificios SET enviar_email_adm = 1 WHERE id = 1");
    echo "Toggle habilitado temporariamente para o teste.\n";
}

$result = enviar_email_locacao_adm($conn, $items);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if ($result['success']) {
    $conn->query("UPDATE edificios SET enviar_email_adm = 0 WHERE id = 1 AND nome = 'EDIFICIO TESTE'");
}
$conn->close();