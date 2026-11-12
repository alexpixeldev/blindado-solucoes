<?php
/**
 * Geração da mensagem de locação no mesmo formato enviado pelo formulário
 * via WhatsApp (formulario/script.js e formulario2/lib/whatsapp.ts).
 */

if (!function_exists('formatarTextoLocacao')) {
    // Reproduz o formatText() do formulário: minúsculas e primeira letra
    // de cada palavra em maiúscula.
    function formatarTextoLocacao($texto) {
        if ($texto === null || $texto === '') return '';
        $texto = mb_strtolower((string) $texto, 'UTF-8');
        $palavras = explode(' ', $texto);
        foreach ($palavras as $i => $palavra) {
            $primeira = mb_substr($palavra, 0, 1, 'UTF-8');
            $resto = mb_substr($palavra, 1, null, 'UTF-8');
            $palavras[$i] = mb_strtoupper($primeira, 'UTF-8') . $resto;
        }
        return implode(' ', $palavras);
    }
}

if (!function_exists('gerarMensagemLocacao')) {
    /**
     * @param array $loc        Linha de locacoes + nome_edificio, localizacao e requer_selfie
     * @param array $inquilinos Linhas de locacoes_inquilinos
     * @param array $veiculos   Linhas de locacoes_veiculos
     */
    function gerarMensagemLocacao($loc, $inquilinos, $veiculos) {
        $msg = "*Ficha de Controle de Locação*\n\n";

        $nomeEdificio = trim((string) ($loc['nome_edificio'] ?? ''));
        $msg .= "*Edifício:* " . ($nomeEdificio !== '' ? formatarTextoLocacao($nomeEdificio) : 'Não informado') . "\n";
        $msg .= "*Apartamento:* " . ($loc['numero_apartamento'] ?? '') . "\n\n";

        $entrada = !empty($loc['data_entrada']) ? date('d/m/Y', strtotime($loc['data_entrada'])) : '';
        $saida = !empty($loc['data_saida']) ? date('d/m/Y', strtotime($loc['data_saida'])) : '';
        $msg .= "*Data de Chegada:* " . $entrada . "\n";
        $msg .= "*Data de Saída:* " . $saida . "\n\n";

        if (!empty($loc['localizacao'])) {
            $msg .= "*Localização do edifício:*\n" . $loc['localizacao'] . "\n\n";
        }

        if (($loc['tipo_usuario'] ?? '') === 'locatario') {
            $msg .= "*Locador:* " . formatarTextoLocacao($loc['locador_nome'] ?? '') . "\n";
            $msg .= "*WhatsApp Locador:* " . ($loc['locador_telefone'] ?? '') . "\n\n";
        }

        if (!empty($loc['registrador_nome'])) {
            $msg .= "*Locação registrada por:* " . formatarTextoLocacao($loc['registrador_nome']) . "\n\n";
        }

        $msg .= "*Inquilinos:*\n";
        $requerSelfie = !empty($loc['requer_selfie']);
        foreach ($inquilinos as $inq) {
            if (empty($inq['nome'])) continue;
            $telInfo = !empty($inq['telefone']) ? "\nTelefone: " . $inq['telefone'] : '';
            $selfieInfo = ($requerSelfie && !empty($inq['selfie'])) ? "\n📷 Cadastro de facial" : '';
            $msg .= "*" . formatarTextoLocacao($inq['nome']) . "*\nDocumento: " . ($inq['documento'] ?? '') . $telInfo . $selfieInfo . "\n\n";
        }

        $msg .= "*Veículos:*\n";
        $veiculosValidos = array_values(array_filter($veiculos, function ($v) {
            return !empty($v['modelo']);
        }));
        if (empty($veiculosValidos)) {
            $msg .= "Nenhum veículo cadastrado.\n";
        } else {
            foreach ($veiculosValidos as $v) {
                $msg .= formatarTextoLocacao($v['modelo']) . "\n";
                $msg .= formatarTextoLocacao($v['cor'] ?? '') . "\n";
                $msg .= "Placa: " . mb_strtoupper((string) ($v['placa'] ?? ''), 'UTF-8') . "\n";
                if (!empty($v['acesso_garagem'])) {
                    $msg .= "Acesso de garagem: " . formatarTextoLocacao($v['acesso_garagem']) . "\n";
                }
                $msg .= "\n";
            }
        }

        return $msg;
    }
}

if (!function_exists('btnCopiarFicha')) {
    function btnCopiarFicha($texto) {
        $js = json_encode($texto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        return '<button type="button" class="btn btn-sm" style="background:#25d366;color:#fff;border-color:#25d366;flex-shrink:0" onmouseover="this.style.background=\'#1ebe5b\';this.style.borderColor=\'#1ebe5b\'" onmouseout="this.style.background=\'#25d366\';this.style.borderColor=\'#25d366\'" onclick=\'copiarTexto(' . $js . ', this)\' title="Copiar todos os dados desta locação formatados para o WhatsApp"><i class="fas fa-copy"></i><span>Copiar para enviar no WhatsApp</span></button>';
    }
}
