<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../admin/conexao.php';

$fpdf_path = '../admin/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    die("Erro: Biblioteca FPDF nao encontrada.");
}

require_once $fpdf_path;

// Função para formatar texto: apenas iniciais maiúsculas
function formatarTexto($texto) {
    if (empty($texto)) return "";
    return mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

class BlindadoPDF extends FPDF {
    var $nome_edificio = "";
    var $endereco_edificio = "";
    
    var $colorGreen = [3, 54, 22]; // Cor #033616 
    var $colorBlack = [33, 37, 41];   
    var $colorGray = [245, 245, 245];  
    var $colorText = [60, 60, 60];    

    function T($txt) {
        return utf8_decode($txt);
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if($c==' ') $sep = $i;
            $l += isset($cw[$c]) ? $cw[$c] : 0;
            if($l>$wmax) {
                if($sep==-1) { if($i==$j) $i++; } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }

    function Header() {
        $this->SetFillColor($this->colorBlack[0], $this->colorBlack[1], $this->colorBlack[2]);
        $this->Rect(140, 0, 70, 6, 'F');
        
        $logo = '../img/logo_horizontal.png';
        if (file_exists($logo)) {
            // ALINHADO COM A PRIMEIRA LETRA (X=10) E ALTURA CORRIGIDA (Y=10)
            $this->Image($logo, 10, 10, 40); 
        }
    }

    function Footer() {
        $this->SetY(-8);
        $this->SetFillColor($this->colorBlack[0], $this->colorBlack[1], $this->colorBlack[2]);
        $this->Rect(0, $this->h - 3, 30, 3, 'F');
        
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 3, $this->T('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    function SectionHeader($title) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->colorBlack[0], $this->colorBlack[1], $this->colorBlack[2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(120, 7, $this->T('  ' . $title), 0, 0, 'L', true);
        
        $this->SetFillColor($this->colorGreen[0], $this->colorGreen[1], $this->colorGreen[2]);
        $this->Cell(70, 7, '', 0, 1, 'L', true);
        $this->Ln(1);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    
    // --- SALVAR DADOS NO BANCO DE DADOS ---
    // Coletar dados básicos
    $edificio_id = $data['edificio_id'] ?? null;
    $tipo_usuario = $data['user_type'] ?? '';
    $numero_apartamento = $data['numero_apartamento'] ?? '';
    $locador_nome = $data['locador_nome'] ?? null;
    $locador_telefone = $data['locador_telefone'] ?? null;
    $data_entrada = $data['data_entrada'] ?? null;
    $data_saida = $data['data_saida'] ?? null;
    $observacoes = $data['observacoes'] ?? '';

    // Converter datas de d/m/Y para Y-m-d (formato do banco)
    if (!empty($data_entrada)) {
        $dt = DateTime::createFromFormat('d/m/Y', $data_entrada);
        $data_entrada = $dt ? $dt->format('Y-m-d') : null;
    } else {
        $data_entrada = null;
    }
    
    if (!empty($data_saida)) {
        $dt = DateTime::createFromFormat('d/m/Y', $data_saida);
        $data_saida = $dt ? $dt->format('Y-m-d') : null;
    } else {
        $data_saida = null;
    }

    // Validação básica
    if ($edificio_id && $numero_apartamento) {
        // 1. Inserir na tabela principal (locacoes)
        $data_locacao = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO locacoes (edificio_id, tipo_usuario, numero_apartamento, locador_nome, locador_telefone, data_entrada, data_saida, observacoes, data_locacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $edificio_id, $tipo_usuario, $numero_apartamento, $locador_nome, $locador_telefone, $data_entrada, $data_saida, $observacoes, $data_locacao);
        
        if ($stmt->execute()) {
            $locacao_id = $stmt->insert_id;

            // 2. Inserir Inquilinos
            if (isset($data['inquilinos']) && is_array($data['inquilinos'])) {
                $stmt_inq = $conn->prepare("INSERT INTO locacoes_inquilinos (locacao_id, nome, documento, telefone) VALUES (?, ?, ?, ?)");
                foreach ($data['inquilinos'] as $inquilino) {
                    if (!empty($inquilino['nome'])) {
                        $tel_inq = $inquilino['telefone'] ?? null;
                        $stmt_inq->bind_param("isss", $locacao_id, $inquilino['nome'], $inquilino['documento'], $tel_inq);
                        $stmt_inq->execute();
                    }
                }
            }

            // 3. Inserir Veículos
            if (isset($data['veiculos']) && is_array($data['veiculos'])) {
                $stmt_vei = $conn->prepare("INSERT INTO locacoes_veiculos (locacao_id, modelo, cor, placa, acesso_garagem) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['veiculos'] as $veiculo) {
                    if (!empty($veiculo['modelo'])) {
                        $stmt_vei->bind_param("issss", $locacao_id, $veiculo['modelo'], $veiculo['cor'], $veiculo['placa'], $veiculo['acesso_garagem']);
                        $stmt_vei->execute();
                    }
                }
            }
        }
    }
    // --- FIM DA LÓGICA DE SALVAMENTO ---
    
    $edificio_id = isset($data['edificio_id']) ? (int)$data['edificio_id'] : 0;
    $nome_ed = "Não informado";
    $end_ed = "Não informado";
    $obs_ficha = "";

    if ($edificio_id > 0) {
        $stmt = $conn->prepare("SELECT nome, endereco, observacao_ficha_locacao FROM edificios WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $edificio_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $nome_ed = $row['nome'];
                $end_ed = $row['endereco'];
                $obs_ficha = $row['observacao_ficha_locacao'] ?? '';
            }
            $stmt->close();
        }
    }

    $pdf = new BlindadoPDF();
    $pdf->nome_edificio = formatarTexto($nome_ed);
    $pdf->endereco_edificio = $end_ed; 
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // --- CONTEÚDO PRINCIPAL ---
    $pdf->SetY(30); 
    
    // --- Título Principal ---
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor($pdf->colorGreen[0], $pdf->colorGreen[1], $pdf->colorGreen[2]);
    $pdf->Cell(0, 8, $pdf->T('FORMULÁRIO DE LOCAÇÃO'), 0, 1, 'R');
    
    // --- Bloco do Edifício ---
    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor($pdf->colorGreen[0], $pdf->colorGreen[1], $pdf->colorGreen[2]);
    $pdf->Cell(0, 6, $pdf->T($pdf->nome_edificio), 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($pdf->colorText[0], $pdf->colorText[1], $pdf->colorText[2]);
    $pdf->MultiCell(120, 5, $pdf->T($pdf->endereco_edificio), 0, 'L');

    // --- Dados da Locação ---
    $pdf->SetY($pdf->GetY() + 4); 
    $pdf->SectionHeader('Dados da locação');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($pdf->colorText[0], $pdf->colorText[1], $pdf->colorText[2]);
    
    $apt = $data['numero_apartamento'] ?? '---';
    $entrada = $data['data_entrada'] ?? '---';
    $saida = $data['data_saida'] ?? '---';
    $loc_tel = $data['locador_telefone'] ?? '';
    $loc_nome = formatarTexto($data['locador_nome'] ?? '');

    $y_start = $pdf->GetY();
    
    $pdf->Cell(60, 6, $pdf->T('Apartamento: ') . $pdf->T($apt), 0, 1);
    $pdf->Cell(60, 6, $pdf->T('Data entrada: ') . $pdf->T($entrada), 0, 1);
    $pdf->Cell(60, 6, $pdf->T('Data saída: ') . $pdf->T($saida), 0, 1);
    
    $y_left_end = $pdf->GetY();

    if (!empty($loc_tel)) {
        $pdf->SetXY(110, $y_start); 
        if (!empty($loc_nome)) {
            $pdf->Cell(0, 6, $pdf->T('Locador: ') . $pdf->T($loc_nome), 0, 1);
            $pdf->SetX(110);
            $pdf->Cell(0, 6, $pdf->T('WhatsApp locador: ') . $pdf->T($loc_tel), 0, 1);
        } else {
            $pdf->Cell(0, 6, $pdf->T('WhatsApp: ') . $pdf->T($loc_tel), 0, 1);
        }
    }
    
    $y_right_end = $pdf->GetY();
    $final_y = max($y_left_end, $y_right_end);
    $pdf->SetY($final_y + 4);

    // --- Hóspedes ---
    $has_inquilinos = false;
    if (isset($data['inquilinos']) && is_array($data['inquilinos'])) {
        foreach ($data['inquilinos'] as $inq) {
            if (!empty($inq['nome'])) { $has_inquilinos = true; break; }
        }
    }

    if ($has_inquilinos) {
        $pdf->SectionHeader('Hóspedes');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor($pdf->colorGray[0], $pdf->colorGray[1], $pdf->colorGray[2]);
        $pdf->SetTextColor($pdf->colorBlack[0], $pdf->colorBlack[1], $pdf->colorBlack[2]);
        
        $pdf->Cell(90, 7, $pdf->T('Nome completo'), 0, 0, 'L', true);
        $pdf->Cell(50, 7, $pdf->T('Documento'), 0, 0, 'L', true);
        $pdf->Cell(50, 7, $pdf->T('Telefone'), 0, 1, 'L', true);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor($pdf->colorText[0], $pdf->colorText[1], $pdf->colorText[2]);
        
        foreach ($data['inquilinos'] as $inq) {
            if (!empty($inq['nome'])) {
                $pdf->Cell(90, 7, $pdf->T(formatarTexto($inq['nome'])), 'B', 0, 'L');
                $pdf->Cell(50, 7, $pdf->T($inq['documento']), 'B', 0, 'L');
                $pdf->Cell(50, 7, $pdf->T($inq['telefone'] ?? '---'), 'B', 1, 'L');
            }
        }
        $pdf->Ln(4);
    }

    // --- Veículos ---
    $has_veiculos = false;
    if (isset($data['veiculos']) && is_array($data['veiculos'])) {
        foreach ($data['veiculos'] as $v) {
            if (!empty($v['modelo'])) { $has_veiculos = true; break; }
        }
    }

    if ($has_veiculos) {
        $pdf->SectionHeader('Veículos');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor($pdf->colorGray[0], $pdf->colorGray[1], $pdf->colorGray[2]);
        $pdf->SetTextColor($pdf->colorBlack[0], $pdf->colorBlack[1], $pdf->colorBlack[2]);
        
        $pdf->Cell(60, 7, $pdf->T('Modelo'), 0, 0, 'L', true);
        $pdf->Cell(40, 7, $pdf->T('Cor'), 0, 0, 'L', true);
        $pdf->Cell(40, 7, $pdf->T('Placa'), 0, 0, 'L', true);
        $pdf->Cell(50, 7, $pdf->T('Acesso garagem'), 0, 1, 'L', true);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor($pdf->colorText[0], $pdf->colorText[1], $pdf->colorText[2]);
        
        foreach ($data['veiculos'] as $v) {
            if (!empty($v['modelo'])) {
                $pdf->Cell(60, 7, $pdf->T(formatarTexto($v['modelo'])), 'B', 0, 'L');
                $pdf->Cell(40, 7, $pdf->T(formatarTexto($v['cor'])), 'B', 0, 'L');
                $pdf->Cell(40, 7, $pdf->T(strtoupper($v['placa'])), 'B', 0, 'L'); 
                $pdf->Cell(50, 7, $pdf->T(formatarTexto($v['acesso_garagem'] ?? 'N/A')), 'B', 1, 'L');
            }
        }
        $pdf->Ln(4);
    }

    // --- Observações do Formulário ---
    if (!empty($data['observacoes'])) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor($pdf->colorGreen[0], $pdf->colorGreen[1], $pdf->colorGreen[2]);
        $pdf->Cell(0, 6, $pdf->T('Observações'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor($pdf->colorText[0], $pdf->colorText[1], $pdf->colorText[2]);
        $pdf->MultiCell(0, 5, $pdf->T($data['observacoes']), 0, 'L');
        $pdf->Ln(2);
    }

    // --- POSICIONAMENTO DA ASSINATURA E OBSERVAÇÃO ---
    $hospede_responsavel = "";
    if (isset($data['inquilinos']) && is_array($data['inquilinos']) && !empty($data['inquilinos'][0]['nome'])) {
        $hospede_responsavel = formatarTexto($data['inquilinos'][0]['nome']);
    }

    $target_y_signature = 278; 
    
    $obs_height = 0;
    if (!empty($obs_ficha)) {
        $lines = $pdf->NbLines(0, $pdf->T($obs_ficha));
        $obs_height = $lines * 5;
    }

    $target_y_obs = $target_y_signature - $obs_height - 35; 

    if (!empty($obs_ficha)) {
        $pdf->SetY($target_y_obs);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor($pdf->colorText[0], $pdf->colorText[1], $pdf->colorText[2]);
        $pdf->MultiCell(0, 5, $pdf->T($obs_ficha), 0, 'C');
    }

    $pdf->SetY($target_y_signature - 15);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor($pdf->colorBlack[0], $pdf->colorBlack[1], $pdf->colorBlack[2]);
    
    $pageWidth = $pdf->GetPageWidth();
    $lineWidth = 70; 
    $xPos = ($pageWidth - $lineWidth) / 2;
    
    $pdf->SetX($xPos);
    $pdf->Cell($lineWidth, 0, '', 'T', 1, 'C'); 
    
    $pdf->Ln(1.5);
    $pdf->Cell(0, 4, $pdf->T($hospede_responsavel), 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, $pdf->T('Hóspede Responsável'), 0, 1, 'C');

    // Gerar o PDF
    if (ob_get_length()) ob_clean();
    $pdf->Output('I', 'Ficha_Locacao.pdf');
    exit;
} else {
    echo "Acesso inválido.";
}
?>
