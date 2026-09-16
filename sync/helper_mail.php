<?php
/**
 * Envio de e-mail para administradora quando uma nova locação é registrada.
 * Usa PHPMailer + SMTP Titan (HostGator).
 */

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envia o e-mail da locação para a administradora vinculada ao edifício.
 *
 * Regras:
 *  - Só envia se o edifício tiver o toggle `enviar_email_adm` habilitado.
 *  - Só envia se a administradora tiver e-mail preenchido.
 *
 * $items = [
 *   'edificio_id', 'edificio_nome', 'edificio_endereco', 'administradora_id',
 *   'administradora_nome', 'administradora_email',
 *   'tipo_usuario', 'numero_apartamento', 'locador_nome', 'locador_telefone',
 *   'data_entrada', 'data_saida', 'observacoes', 'data_locacao',
 *   'inquilinos' => [ ['nome','documento','telefone','selfie'] ],
 *   'veiculos'   => [ ['modelo','cor','placa','acesso_garagem'] ],
 * ]
 *
 * @return array ['success' => bool, 'message' => string]
 */
function enviar_email_locacao_adm($conn, array $items) {
    $edificioId    = intval($items['edificio_id'] ?? 0);
    $admNome       = $items['administradora_nome'] ?? '';
    $admEmail      = trim($items['administradora_email'] ?? '');
    $edificioNome  = $items['edificio_nome'] ?? '';
    $apt           = $items['numero_apartamento'] ?? '';

    // Regra 1: edifício deve ter envio de e-mail habilitado
    $r = $conn->query("SELECT enviar_email_adm FROM edificios WHERE id = $edificioId");
    $enabled = $r ? (int)($r->fetch_assoc()['enviar_email_adm'] ?? 0) : 0;
    if ($enabled !== 1) {
        return ['success' => false, 'message' => 'Envio de e-mail desabilitado para este edifício.'];
    }

    // Regra 2: administradora deve ter e-mail
    if ($admEmail === '' || !filter_var($admEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Administradora sem e-mail válido cadastrado.'];
    }

    $config = require __DIR__ . '/mail_config.php';

    $subject = 'Nova Locação – ' . $edificioNome . (!empty($apt) ? ' – Apto ' . $apt : '');

    $body = montar_email_html($items);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = ($config['secure'] ?? 'ssl') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = intval($config['port']);
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->SMTPDebug  = 0;

        $mail->setFrom($config['from_email'], $config['from_name']);

        // Destinatário: administradora (nome amigável quando disponível)
        if (!empty($admNome)) {
            $mail->addAddress($admEmail, $admNome);
        } else {
            $mail->addAddress($admEmail);
        }

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));

        // Anexar as selfies dos hóspedes (se houver)
        $baseUpload = dirname(__DIR__) . '/uploads/selfies_inquilinos/';
        foreach (($items['inquilinos'] ?? []) as $inq) {
            if (empty($inq['selfie']) || !is_string($inq['selfie'])) continue;
            $fileAbs = $baseUpload . basename($inq['selfie']);
            if (is_file($fileAbs)) {
                $nomeHospede = !empty($inq['nome']) ? preg_replace('/[^A-Za-z0-9 _.-]/u', '', $inq['nome']) : 'hospede';
                $mail->addAttachment($fileAbs, 'Selfie ' . trim($nomeHospede) . '.jpg');
            }
        }

        if ($mail->send()) {
            return ['success' => true, 'message' => 'E-mail enviado com sucesso.'];
        }
        return ['success' => false, 'message' => 'Falha ao enviar: ' . $mail->ErrorInfo];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erro no envio: ' . $e->getMessage()];
    }
}

/**
 * Monta o corpo HTML do e-mail com todas as informações do formulário.
 */
function montar_email_html(array $items) {
    $esc = function ($v) {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    };

    $edificioNome = $esc($items['edificio_nome'] ?? '');
    $endereco     = $esc($items['edificio_endereco'] ?? '');
    $apt          = $esc($items['numero_apartamento'] ?? '');
    $tipo         = $esc($items['tipo_usuario'] ?? '');
    $tipoLabel    = ($tipo === 'locador') ? 'Locador' : 'Locatário';
    $locador      = $esc($items['locador_nome'] ?? '');
    $locadorTel   = $esc($items['locador_telefone'] ?? '');
    $entrada      = $esc($items['data_entrada'] ?? '');
    $saida        = $esc($items['data_saida'] ?? '');
    $obs          = nl2br($esc($items['observacoes'] ?? ''));

    $rowsHospedes = '';
    foreach (($items['inquilinos'] ?? []) as $inq) {
        if (empty($inq['nome'])) continue;
        $hasFoto = !empty($inq['selfie']) ? ' ✓' : '';
        $rowsHospedes .= '<tr>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($inq['nome']) . '</td>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($inq['documento'] ?? '') . '</td>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($inq['telefone'] ?? '') . '</td>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $hasFoto . '</td>
        </tr>';
    }

    $rowsVeiculos = '';
    foreach (($items['veiculos'] ?? []) as $v) {
        if (empty($v['modelo'])) continue;
        $rowsVeiculos .= '<tr>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($v['modelo']) . '</td>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($v['cor'] ?? '') . '</td>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($v['placa'] ?? '') . '</td>
            <td style="padding:8px 10px;border:1px solid #e5e7eb;">' . $esc($v['acesso_garagem'] ?? '') . '</td>
        </tr>';
    }

    $html = '';
    $html .= '<div style="font-family:Arial,Helvetica,sans-serif;background:#f1f5f9;padding:24px;">';
    $html .= '<div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">';

    // Cabeçalho
    $html .= '<div style="background:#033616;color:#ffffff;padding:20px 24px;">';
    $html .= '<h1 style="margin:0;font-size:20px;">Blindado Soluções</h1>';
    $html .= '<p style="margin:4px 0 0;font-size:13px;opacity:.85;">Notificação de nova locação registrada</p>';
    $html .= '</div>';

    $html .= '<div style="padding:24px;">';

    // Edifício
    $html .= '<h2 style="margin:0 0 4px;font-size:18px;color:#033616;">' . $edificioNome . '</h2>';
    if ($endereco) {
        $html .= '<p style="margin:0 0 16px;font-size:13px;color:#64748b;">' . $endereco . '</p>';
    }

    // Dados da locação
    $html .= '<h3 style="margin:16px 0 8px;font-size:14px;color:#0f172a;border-bottom:2px solid #033616;padding-bottom:4px;">Dados da Locação</h3>';
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
    $html .= '<tr><td style="padding:6px 0;color:#475569;width:40%;"><strong>Apartamento</strong></td><td style="padding:6px 0;">' . $apt . '</td></tr>';
    $html .= '<tr><td style="padding:6px 0;color:#475569;"><strong>Perfil</strong></td><td style="padding:6px 0;">' . $tipoLabel . '</td></tr>';
    if ($locador) {
        $html .= '<tr><td style="padding:6px 0;color:#475569;"><strong>Locador</strong></td><td style="padding:6px 0;">' . $locador . '</td></tr>';
    }
    if ($locadorTel) {
        $html .= '<tr><td style="padding:6px 0;color:#475569;"><strong>Contato do locador</strong></td><td style="padding:6px 0;">' . $locadorTel . '</td></tr>';
    }
    $html .= '<tr><td style="padding:6px 0;color:#475569;"><strong>Data de entrada</strong></td><td style="padding:6px 0;">' . $entrada . '</td></tr>';
    $html .= '<tr><td style="padding:6px 0;color:#475569;"><strong>Data de saída</strong></td><td style="padding:6px 0;">' . $saida . '</td></tr>';
    $html .= '</table>';

    // Hóspedes
    if ($rowsHospedes !== '') {
        $html .= '<h3 style="margin:16px 0 8px;font-size:14px;color:#0f172a;border-bottom:2px solid #033616;padding-bottom:4px;">Hóspedes / Inquilinos</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
        $html .= '<thead><tr style="background:#f8fafc;">';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Nome</th>';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Documento</th>';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Telefone</th>';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Foto</th>';
        $html .= '</tr></thead><tbody>' . $rowsHospedes . '</tbody></table>';
    }

    // Veículos
    if ($rowsVeiculos !== '') {
        $html .= '<h3 style="margin:16px 0 8px;font-size:14px;color:#0f172a;border-bottom:2px solid #033616;padding-bottom:4px;">Veículos</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
        $html .= '<thead><tr style="background:#f8fafc;">';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Modelo</th>';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Cor</th>';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Placa</th>';
        $html .= '<th style="padding:8px 10px;border:1px solid #e5e7eb;text-align:left;">Acesso garagem</th>';
        $html .= '</tr></thead><tbody>' . $rowsVeiculos . '</tbody></table>';
    }

    // Observações
    if ($obs !== '') {
        $html .= '<h3 style="margin:16px 0 8px;font-size:14px;color:#0f172a;border-bottom:2px solid #033616;padding-bottom:4px;">Observações</h3>';
        $html .= '<p style="margin:8px 0;font-size:13px;color:#334155;">' . $obs . '</p>';
    }

    // Rodapé
    $html .= '<div style="margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;">';
    $html .= '<p style="margin:0;">Este e-mail foi gerado automaticamente pelo sistema de monitoramento da Blindado Soluções. As fotos dos hóspedes (selfies), quando informadas, seguem em anexo.</p>';
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}