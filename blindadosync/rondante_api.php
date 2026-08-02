<?php
require_once 'verifica_login.php';
require_once 'conexao.php';

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'];
$usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
if ($usuario_categoria !== 'rondante' && $usuario_categoria !== 'gerente' && $usuario_categoria !== 'diretor') {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação']);
    exit;
}

const RAIO_SCAN_EDIFICIO = 200;

function dist_metros($lat1, $lng1, $lat2, $lng2) {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

function dentro_do_perimetro($conn, $lat, $lng) {
    $base = $conn->query("SELECT latitude, longitude, raio_perimetro FROM bases WHERE nome LIKE '%Praia do morro%' LIMIT 1")->fetch_assoc();
    if (!$base || !$base['latitude'] || !$base['longitude']) {
        return ['ok' => false, 'error' => 'Localização da base Praia do Morro não configurada. Informe latitude/longitude/raio na edição da base.'];
    }
    $raio = intval($base['raio_perimetro'] ?: 200);
    $d = dist_metros(floatval($lat), floatval($lng), floatval($base['latitude']), floatval($base['longitude']));
    if ($d <= $raio) {
        return ['ok' => true, 'distance' => round($d, 1), 'raio' => $raio];
    }
    return ['ok' => false, 'distance' => round($d, 1), 'raio' => $raio, 'error' => "Você está fora do perímetro da base Praia do Morro (a " . round($d, 1) . "m, permitido até {$raio}m)."];
}

function ronda_data_atual() {
    return date('Y-m-d');
}

function ronda_ativa($conn, $usuario_id) {
    $stmt = $conn->prepare("SELECT * FROM rondas WHERE usuario_id = ? AND status = 'ativa' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'status':
        $ronda = ronda_ativa($conn, $usuario_id);
        $scans = 0;
        if ($ronda) {
            $stmt = $conn->prepare("SELECT COUNT(*) as c FROM ronda_escaneamentos WHERE ronda_id = ?");
            $stmt->bind_param('i', $ronda['id']);
            $stmt->execute();
            $scans = intval($stmt->get_result()->fetch_assoc()['c']);
            $stmt->close();
        }
        echo json_encode([
            'success' => true,
            'ronda' => $ronda ? [
                'id' => $ronda['id'],
                'data' => $ronda['data_ronda'],
                'inicio' => $ronda['hora_inicio'],
                'scans' => $scans
            ] : null
        ]);
        break;

    case 'iniciar':
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        if (!$lat || !$lng) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível obter sua localização. Habilite o GPS.']);
            exit;
        }
        $perimetro = dentro_do_perimetro($conn, $lat, $lng);
        if (!$perimetro['ok']) {
            echo json_encode(['success' => false, 'message' => $perimetro['error']]);
            exit;
        }
        $data_ronda = ronda_data_atual();
        $ronda = ronda_ativa($conn, $usuario_id);
        if ($ronda) {
            echo json_encode(['success' => true, 'ronda_id' => $ronda['id'], 'message' => 'Você já tem uma ronda em andamento.']);
            exit;
        }
        $base = $conn->query("SELECT id FROM bases WHERE nome LIKE '%Praia do morro%' LIMIT 1")->fetch_assoc();
        $base_id = $base ? $base['id'] : null;
        $stmt = $conn->prepare("INSERT INTO rondas (usuario_id, base_id, data_ronda, hora_inicio) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('iis', $usuario_id, $base_id, $data_ronda);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'ronda_id' => $conn->insert_id, 'message' => 'Ronda iniciada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao iniciar ronda: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'finalizar':
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        if (!$lat || !$lng) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível obter sua localização. Habilite o GPS.']);
            exit;
        }
        $perimetro = dentro_do_perimetro($conn, $lat, $lng);
        if (!$perimetro['ok']) {
            echo json_encode(['success' => false, 'message' => $perimetro['error']]);
            exit;
        }
        $ronda = ronda_ativa($conn, $usuario_id);
        if (!$ronda) {
            echo json_encode(['success' => false, 'message' => 'Não há ronda em andamento.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE rondas SET status = 'finalizada', hora_fim = NOW() WHERE id = ?");
        $stmt->bind_param('i', $ronda['id']);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ronda finalizada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao finalizar ronda: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'escanear':
        $ronda_id = intval($_POST['ronda_id'] ?? 0);
        $edificio_id = intval($_POST['edificio_id'] ?? 0);
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $interfones = isset($_POST['interfones']) ? 1 : 0;
        $lixo = isset($_POST['lixo']) ? 1 : 0;

        $ronda = ronda_ativa($conn, $usuario_id);
        if (!$ronda || $ronda['id'] != $ronda_id) {
            echo json_encode(['success' => false, 'message' => 'Ronda não está em andamento ou não pertence a você.']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id, nome, latitude, longitude FROM edificios WHERE id = ?");
        $stmt->bind_param('i', $edificio_id);
        $stmt->execute();
        $edificio = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$edificio) {
            echo json_encode(['success' => false, 'message' => 'Edifício não encontrado.']);
            exit;
        }
        if (!$lat || !$lng) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível obter sua localização. Habilite o GPS.']);
            exit;
        }
        if ($edificio['latitude'] && $edificio['longitude']) {
            $d = dist_metros($lat, $lng, floatval($edificio['latitude']), floatval($edificio['longitude']));
            if ($d > RAIO_SCAN_EDIFICIO) {
                echo json_encode(['success' => false, 'message' => "Você está a " . round($d, 1) . "m do edifício {$edificio['nome']}. Aproxime-se para escanear (permitido até " . RAIO_SCAN_EDIFICIO . "m)."]);
                exit;
            }
        }
        $stmt = $conn->prepare("INSERT INTO ronda_escaneamentos (ronda_id, edificio_id, escaneado_em, latitude, longitude, interfones_ok, lixo_retirado) VALUES (?, ?, NOW(), ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE escaneado_em = NOW(), latitude = ?, longitude = ?, interfones_ok = ?, lixo_retirado = ?");
        $stmt->bind_param('iiddiiddii', $ronda_id, $edificio_id, $lat, $lng, $interfones, $lixo, $lat, $lng, $interfones, $lixo);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'QR code do edifício ' . $edificio['nome'] . ' escaneado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar escaneamento: ' . $conn->error]);
        }
        $stmt->close();
        break;

    case 'escanear_token':
        $token = trim($_POST['token'] ?? '');
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $interfones = isset($_POST['interfones']) ? 1 : 0;
        $lixo = isset($_POST['lixo']) ? 1 : 0;

        if ($token === '') {
            echo json_encode(['success' => false, 'message' => 'QR code inválido.']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id, nome, latitude, longitude, retirada_lixo FROM edificios WHERE qr_token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $edificio = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$edificio) {
            echo json_encode(['success' => false, 'message' => 'QR code não cadastrado.']);
            exit;
        }
        $ronda = ronda_ativa($conn, $usuario_id);
        if (!$ronda) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma ronda em andamento. Inicie a ronda antes de escanear.']);
            exit;
        }
        if (!$lat || !$lng) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível obter sua localização. Habilite o GPS.']);
            exit;
        }
        if ($edificio['latitude'] && $edificio['longitude']) {
            $d = dist_metros($lat, $lng, floatval($edificio['latitude']), floatval($edificio['longitude']));
            if ($d > RAIO_SCAN_EDIFICIO) {
                echo json_encode(['success' => false, 'message' => "Você está a " . round($d, 1) . "m do edifício {$edificio['nome']}. Aproxime-se para escanear (permitido até " . RAIO_SCAN_EDIFICIO . "m)."]);
                exit;
            }
        }
        $stmt = $conn->prepare("INSERT INTO ronda_escaneamentos (ronda_id, edificio_id, escaneado_em, latitude, longitude, interfones_ok, lixo_retirado) VALUES (?, ?, NOW(), ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE escaneado_em = NOW(), latitude = ?, longitude = ?, interfones_ok = ?, lixo_retirado = ?");
        $stmt->bind_param('iiddiiddii', $ronda['id'], $edificio['id'], $lat, $lng, $interfones, $lixo, $lat, $lng, $interfones, $lixo);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'QR code do edifício ' . $edificio['nome'] . ' escaneado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar escaneamento: ' . $conn->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}

$conn->close();
