<?php
/**
 * Escopo de base do usuario (operador e supervisor).
 *
 * Operador e supervisor ficam vinculados a uma base e so podem
 * adicionar, visualizar, editar e excluir informacoes dessa base.
 * Categorias sem vinculo (gerente, diretor, tecnico, administrativo)
 * permanecem sem restricao.
 */

function usuario_tem_escopo_de_base($usuario_categoria) {
    return in_array($usuario_categoria, ['operador', 'supervisor_monitoramento'], true);
}

/**
 * Base vinculada do usuario no banco.
 */
function base_id_do_usuario($conn, $usuario_id) {
    $row = $conn->query("SELECT base_id FROM usuarios WHERE id = " . intval($usuario_id))->fetch_assoc();
    if (!$row || $row['base_id'] === null) {
        return null;
    }
    return intval($row['base_id']) > 0 ? intval($row['base_id']) : null;
}

/**
 * Escopo da sessao atual: id da base quando a categoria e vinculada.
 * Retorna null para categorias sem vinculo e para usuario sem base.
 */
function escopo_base_sessao($conn, $usuario_categoria = null, $usuario_id = null) {
    if ($usuario_categoria === null) $usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
    if ($usuario_id === null) $usuario_id = $_SESSION['usuario_id'] ?? 0;
    if (!usuario_tem_escopo_de_base($usuario_categoria)) return null;
    return base_id_do_usuario($conn, $usuario_id);
}

/**
 * Verdadeiro quando a categoria e vinculada a base e o usuario nao tem base definida.
 * Nesse caso nao ha como aplicar filtro (comportamento historico do sistema).
 */
function escopo_base_indefinido($usuario_categoria, $usuario_base_id) {
    return usuario_tem_escopo_de_base($usuario_categoria) && !$usuario_base_id;
}

/**
 * Verdadeiro quando o usuario pode operar na base informada.
 */
function pode_operar_na_base($usuario_categoria, $usuario_base_id, $base_id) {
    if (!usuario_tem_escopo_de_base($usuario_categoria)) return true;
    if (!$usuario_base_id) return true;
    return intval($base_id) === intval($usuario_base_id);
}

/**
 * Base de um edificio, ou null se nao existir.
 */
function base_do_edificio($conn, $edificio_id) {
    $eid = intval($edificio_id);
    if ($eid <= 0) return null;
    $row = $conn->query("SELECT base_id FROM edificios WHERE id = $eid")->fetch_assoc();
    if (!$row || $row['base_id'] === null) return null;
    return intval($row['base_id']);
}

/**
 * Verdadeiro quando o predio pertence a base do usuario.
 */
function pode_operar_no_edificio($conn, $edificio_id, $usuario_categoria, $usuario_base_id) {
    if (!usuario_tem_escopo_de_base($usuario_categoria)) return true;
    if (!$usuario_base_id) return true;
    $base = base_do_edificio($conn, $edificio_id);
    if ($base === null) return false;
    return $base === intval($usuario_base_id);
}

/**
 * Verdadeiro quando o relatorio de ocorrencia pertence a base do usuario.
 * Mesma logica do filtro usado na listagem de ocorrencias.
 */
function ocorrencia_da_base_do_usuario($conn, $ocorrencia, $usuario_categoria, $usuario_base_id) {
    if (!usuario_tem_escopo_de_base($usuario_categoria)) return true;
    if (!$usuario_base_id) return true;
    $bid = intval($usuario_base_id);

    if (!empty($ocorrencia['base_id']) && intval($ocorrencia['base_id']) === $bid) return true;

    if (!empty($ocorrencia['edificio_id']) && base_do_edificio($conn, $ocorrencia['edificio_id']) === $bid) return true;

    $locais = array_filter(array_map('trim', explode(',', $ocorrencia['locais_ids'] ?? '')));
    foreach ($locais as $tok) {
        if (strpos($tok, 'b_') === 0 && intval(substr($tok, 2)) === $bid) return true;
        if (strpos($tok, 'e_') === 0 && base_do_edificio($conn, substr($tok, 2)) === $bid) return true;
    }
    return false;
}

/**
 * Separa os locais enviados em permitidos e bloqueados para o escopo do usuario.
 * Cada local e no formato 'e_<id>' (edificio) ou 'b_<id>' (base).
 */
function separar_locais_por_base($conn, $locais, $usuario_categoria, $usuario_base_id) {
    $permitidos = [];
    $bloqueados = [];
    if (!is_array($locais)) $locais = ($locais === null || $locais === '') ? [] : [$locais];

    foreach ($locais as $tok) {
        $tok = trim((string)$tok);
        if ($tok === '') continue;

        if (!usuario_tem_escopo_de_base($usuario_categoria) || !$usuario_base_id) {
            $permitidos[] = $tok;
            continue;
        }

        $bid = intval($usuario_base_id);
        if (strpos($tok, 'b_') === 0 && intval(substr($tok, 2)) === $bid) {
            $permitidos[] = $tok;
        } elseif (strpos($tok, 'e_') === 0 && base_do_edificio($conn, substr($tok, 2)) === $bid) {
            $permitidos[] = $tok;
        } else {
            $bloqueados[] = $tok;
        }
    }

    return ['permitidos' => $permitidos, 'bloqueados' => $bloqueados];
}

/**
 * Clausula WHERE para listar apenas registros da base do usuario.
 * $coluna_base = coluna com o id da base (ex.: 'e.base_id')
 */
function filtro_base_sql($usuario_categoria, $usuario_base_id, $coluna_base) {
    if (!usuario_tem_escopo_de_base($usuario_categoria)) return '';
    if (!$usuario_base_id) return '';
    return " AND " . $coluna_base . " = " . intval($usuario_base_id);
}
?>
