<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../includes/login.php");
    exit();
}
require_once("../connect/conn.php");
$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die("Error crítico: No se pudo establecer la conexión a la base de datos.");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_usuario = $_SESSION['usuario_id'];
$id_sala = !empty($_GET['id_sala']) ? intval($_GET['id_sala']) : null;
$id_path_partida = !empty($_GET['id_path_partida']) ? intval($_GET['id_path_partida']) : (!empty($_GET['id_partida']) ? intval($_GET['id_partida']) : null);

$stmtRol = $pdo->prepare("SELECT id_rol FROM usuario WHERE id_usuario = ?");
$stmtRol->execute([$id_usuario]);
$es_admin = ($stmtRol->fetchColumn() === '1');

if ($id_sala) {
    try {
        $stmtCheckSala = $pdo->prepare("SELECT id_sala FROM sala WHERE id_sala = ?");
        $stmtCheckSala->execute([$id_sala]);
        if (!$stmtCheckSala->fetch()) {
            die("<b>Error:</b> La sala con ID <code>$id_sala</code> no existe.");
        }

        $stmtPartidaActiva = $pdo->prepare("
            SELECT p.id_partida 
            FROM partida p
            WHERE p.id_sala = ? 
              AND p.estado != 'finalizado'
              AND p.id_partida NOT IN (
                  SELECT DISTINCT id_partida 
                  FROM detalle_partida_usuario 
                  WHERE ganador IS NOT NULL
              )
            ORDER BY p.id_partida DESC
            LIMIT 1
        ");
        $stmtPartidaActiva->execute([$id_sala]);
        $partida_existente = $stmtPartidaActiva->fetch(PDO::FETCH_ASSOC);

        if ($partida_existente) {
            $id_partida = $partida_existente['id_partida'];
        } else {
            $stmtUltimaCualquiera = $pdo->prepare("
                SELECT id_partida, estado FROM partida 
                WHERE id_sala = ? 
                ORDER BY id_partida DESC LIMIT 1
            ");
            $stmtUltimaCualquiera->execute([$id_sala]);
            $ultima_partida_sala = $stmtUltimaCualquiera->fetch(PDO::FETCH_ASSOC);

            if ($ultima_partida_sala && $ultima_partida_sala['estado'] !== 'finalizado') {
                $id_partida = $ultima_partida_sala['id_partida'];
            } else {
                if ($ultima_partida_sala) {
                    $stmtQuedanEnUltima = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
                    $stmtQuedanEnUltima->execute([$ultima_partida_sala['id_partida']]);
                    if ($stmtQuedanEnUltima->fetchColumn() > 0) {
                        $id_partida = $ultima_partida_sala['id_partida'];
                    } else {
                        $stmtCrear = $pdo->prepare("INSERT INTO partida (id_sala, puntuacion, fecha_partida, estado) VALUES (?, 0, NULL, 'en_espera')");
                        $stmtCrear->execute([$id_sala]);
                        $id_partida = $pdo->lastInsertId();
                    }
                } else {
                    $stmtCrear = $pdo->prepare("INSERT INTO partida (id_sala, puntuacion, fecha_partida, estado) VALUES (?, 0, NULL, 'en_espera')");
                    $stmtCrear->execute([$id_sala]);
                    $id_partida = $pdo->lastInsertId();
                }
            }
        }

        if ($id_path_partida != $id_partida) {
            $_SESSION['id_partida_activa'] = $id_partida;
            header("Location: partida.php?id_sala=" . $id_sala . "&id_partida=" . $id_partida);
            exit();
        }
    } catch (PDOException $e) {
        die("Error de MySQL al gestionar la sala: " . $e->getMessage());
    }
} else {
    $id_partida = $id_path_partida ?? ($_SESSION['id_partida_activa'] ?? null);
}

if (!$id_partida) {
    die("<div style='padding:20px; font-family:sans-serif;'><h3>Falta el parámetro de partida</h3><p>No se pudo generar el identificador. <a href='inicio.php'>Volver al inicio</a></p></div>");
}

$stmtCheck = $pdo->prepare("SELECT * FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
$stmtCheck->execute([$id_usuario, $id_partida]);
$detalle_usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$detalle_usuario) {
    $stmtArmaUsuario = $pdo->prepare("
        SELECT a.capacidad_municion 
        FROM usuario u 
        LEFT JOIN arma a ON u.arma_equipada = a.id_arma 
        WHERE u.id_usuario = ?
    ");
    $stmtArmaUsuario->execute([$id_usuario]);
    $capacidad_inicial = (int)($stmtArmaUsuario->fetchColumn() ?: 5);

    $stmtInsert = $pdo->prepare("
        INSERT INTO detalle_partida_usuario 
        (id_usuario, id_partida, ganador, puntuacion_total, vida_restante, activo, municion_restante) 
        VALUES (?, ?, NULL, 0, 100, 1, ?)
    ");
    $stmtInsert->execute([$id_usuario, $id_partida, $capacidad_inicial]);
    
    $stmtCheck->execute([$id_usuario, $id_partida]);
    $detalle_usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);
} else {
    if (isset($detalle_usuario['activo']) && (int)$detalle_usuario['activo'] === 0) {
        $stmtReactivar = $pdo->prepare("
            UPDATE detalle_partida_usuario 
            SET activo = 1 
            WHERE id_usuario = ? AND id_partida = ?
        ");
        $stmtReactivar->execute([$id_usuario, $id_partida]);
        
        $stmtCheck->execute([$id_usuario, $id_partida]);
        $detalle_usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'abandonar') {
    try {
        $stmtUpdActivo = $pdo->prepare("UPDATE detalle_partida_usuario SET activo = 0 WHERE id_usuario = ? AND id_partida = ?");
        $stmtUpdActivo->execute([$id_usuario, $id_partida]);

        $stmtCountRestantes = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
        $stmtCountRestantes->execute([$id_partida]);
        if ($stmtCountRestantes->fetchColumn() == 0) {
            $stmtDelPartida = $pdo->prepare("UPDATE partida SET estado = 'finalizado', fecha_finalizacion = NOW() WHERE id_partida = ?");
            $stmtDelPartida->execute([$id_partida]);
        }

        $pdo->query("DELETE FROM partida WHERE fecha_finalizacion IS NOT NULL AND puntuacion = 0");
    } catch (PDOException $e) {
        die("Error al abandonar: " . $e->getMessage());
    }

    unset($_SESSION['id_partida_activa']);
    unset($_SESSION['municiones_armas_' . $id_partida]);
    $experiencia_procesada_key = 'exp_procesada_' . $id_partida;
    unset($_SESSION[$experiencia_procesada_key]);

    $urlRedirAbandonar = "inicio.php?modulo=lobby";
    if ($id_sala) {
        $urlRedirAbandonar .= "&id_sala=" . urlencode($id_sala);
    }
    header("Location: " . $urlRedirAbandonar);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'seguir_jugando') {
    if ($id_sala) {
        try {
            $pdo->beginTransaction();
            $stmtNuevaPartida = $pdo->prepare("INSERT INTO partida (id_sala, puntuacion, fecha_partida, estado) VALUES (?, 0, NULL, 'en_espera')");
            $stmtNuevaPartida->execute([$id_sala]);
            $nuevo_id_partida = $pdo->lastInsertId();

            $stmtArmaUsr = $pdo->prepare("SELECT arma_equipada FROM usuario WHERE id_usuario = ?");
            $stmtArmaUsr->execute([$id_usuario]);
            $id_arma_usu = $stmtArmaUsr->fetchColumn();

            $capacidad_inicial = 5;
            if ($id_arma_usu) {
                $stmtCap = $pdo->prepare("SELECT capacidad_municion FROM arma WHERE id_arma = ?");
                $stmtCap->execute([$id_arma_usu]);
                $cap_db = $stmtCap->fetchColumn();
                if ($cap_db) $capacidad_inicial = (int)$cap_db;
            }

            $stmtInsNuevo = $pdo->prepare("INSERT INTO detalle_partida_usuario (id_usuario, id_partida, ganador, puntuacion_total, vida_restante, activo, municion_restante) VALUES (?, ?, null, 0, 100, 1, ?)");
            $stmtInsNuevo->execute([$id_usuario, $nuevo_id_partida, $capacidad_inicial]);
            $pdo->commit();

            $_SESSION['id_partida_activa'] = $nuevo_id_partida;
            header("Location: partida.php?id_sala=" . $id_sala . "&id_partida=" . $nuevo_id_partida);
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            die("Error al continuar jugando: " . $e->getMessage());
        }
    }
}

$duracion_maxima_segundos = 300;

try {
    $stmtPartidaTiempo = $pdo->prepare("
        SELECT estado, 
               fecha_inicio,
               fecha_finalizacion,
               CASE 
                   WHEN estado = 'en_curso' AND fecha_inicio IS NOT NULL 
                   THEN GREATEST(0, ? - TIMESTAMPDIFF(SECOND, fecha_inicio, NOW()))
                   ELSE ?
               END AS tiempo_restante
        FROM partida 
        WHERE id_partida = ?
    ");
    $stmtPartidaTiempo->execute([$duracion_maxima_segundos, $duracion_maxima_segundos, $id_partida]);
    $partida_info = $stmtPartidaTiempo->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar tiempo de partida: " . $e->getMessage());
}

$tiempo_restante = (int)($partida_info['tiempo_restante'] ?? $duracion_maxima_segundos);

// Solo cerrar por tiempo si el estado sigue en curso y realmente se agotó el tiempo y NO hay un ganador asignado aún
if ($partida_info && $partida_info['estado'] === 'en_curso' && $tiempo_restante <= 0) {
    try {
        $stmtVerificarGanadorActual = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND ganador IS NOT NULL");
        $stmtVerificarGanadorActual->execute([$id_partida]);
        
        if ($stmtVerificarGanadorActual->fetchColumn() == 0) {
            $stmtGanador = $pdo->prepare("
                SELECT id_usuario 
                FROM detalle_partida_usuario 
                WHERE id_partida = ? 
                ORDER BY puntuacion_total DESC, vida_restante DESC 
                LIMIT 1
            ");
            $stmtGanador->execute([$id_partida]);
            $id_ganador_por_tiempo = $stmtGanador->fetchColumn();

            if ($id_ganador_por_tiempo) {
                $stmtMarcarGanador = $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 1 WHERE id_usuario = ? AND id_partida = ?");
                $stmtMarcarGanador->execute([$id_ganador_por_tiempo, $id_partida]);

                $stmtMarcarPerdedores = $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 0 WHERE id_usuario != ? AND id_partida = ?");
                $stmtMarcarPerdedores->execute([$id_ganador_por_tiempo, $id_partida]);
            }
        }

        $stmtCerrarPartida = $pdo->prepare("UPDATE partida SET estado = 'finalizado', fecha_finalizacion = NOW() WHERE id_partida = ? AND fecha_finalizacion IS NULL");
        $stmtCerrarPartida->execute([$id_partida]);
    } catch (PDOException $e) {
        die("Error al cerrar partida por tiempo: " . $e->getMessage());
    }
}

// Validación de niveles al unirse
try {
    $stmtNivelActual = $pdo->prepare("SELECT id_nivel FROM usuario WHERE id_usuario = ?");
    $stmtNivelActual->execute([$id_usuario]);
    $nivel_usuario_ingresa = (int)$stmtNivelActual->fetchColumn();

    $stmtPrimerUsuarioPartida = $pdo->prepare("
        SELECT u.id_nivel 
        FROM detalle_partida_usuario dup 
        JOIN usuario u ON dup.id_usuario = u.id_usuario 
        WHERE dup.id_partida = ? AND dup.activo != 0
        ORDER BY dup.id_detalle ASC 
        LIMIT 1
    ");
    $stmtPrimerUsuarioPartida->execute([$id_partida]);
    $nivel_primer_usuario = $stmtPrimerUsuarioPartida->fetchColumn();

    if ($nivel_primer_usuario !== false && $nivel_primer_usuario !== null) {
        $nivel_primer_usuario = (int)$nivel_primer_usuario;

        if ($nivel_usuario_ingresa === 1 && $nivel_primer_usuario !== 1) {
            $_SESSION['mensaje_batalla'] = "Error: Los usuarios de nivel 1 no pueden unirse a partidas de niveles superiores.";
            $urlRedir = "inicio.php?modulo=lobby";
            if ($id_sala) $urlRedir .= "&id_sala=" . urlencode($id_sala);
            header("Location: " . $urlRedir);
            exit();
        }

        if ($nivel_usuario_ingresa !== 1 && $nivel_primer_usuario === 1) {
            $_SESSION['mensaje_batalla'] = "Error: Esta partida es exclusiva para usuarios de nivel 1.";
            $urlRedir = "inicio.php?modulo=lobby";
            if ($id_sala) $urlRedir .= "&id_sala=" . urlencode($id_sala);
            header("Location: " . $urlRedir);
            exit();
        }
    }
} catch (PDOException $e) {
    die("Error en validación de niveles: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'dar_listo') {
    try {
        $stmtTotalJugadoresCheck = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
        $stmtTotalJugadoresCheck->execute([$id_partida]);
        if ($stmtTotalJugadoresCheck->fetchColumn() >= 2) {
            $stmtListo = $pdo->prepare("UPDATE detalle_partida_usuario SET activo = 2 WHERE id_usuario = ? AND id_partida = ?");
            $stmtListo->execute([$id_usuario, $id_partida]);

            $stmtTotalJugadores = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
            $stmtTotalJugadores->execute([$id_partida]);
            $totalJug = $stmtTotalJugadores->fetchColumn();

            $stmtTotalListos = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo = 2");
            $stmtTotalListos->execute([$id_partida]);
            $totalLis = $stmtTotalListos->fetchColumn();

            if ($totalJug >= 2 && $totalLis >= $totalJug) {
                $stmtCambiarEstado = $pdo->prepare("UPDATE partida SET estado = 'en_curso', fecha_inicio = NOW(), fecha_partida = NOW() WHERE id_partida = ?");
                $stmtCambiarEstado->execute([$id_partida]);
            }
        }
    } catch (PDOException $e) {
        die("Error al marcar listo: " . $e->getMessage());
    }

    $urlRedir = "partida.php?id_partida=" . urlencode($id_partida);
    if ($id_sala) $urlRedir .= "&id_sala=" . urlencode($id_sala);
    header("Location: " . $urlRedir);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_arma_partida') {
    $nuevo_id_arma = $_POST['id_arma'] ?? null;
    if ($nuevo_id_arma) {
        try {
            $stmtUserLv = $pdo->prepare("SELECT id_nivel, arma_equipada FROM usuario WHERE id_usuario = ?");
            $stmtUserLv->execute([$id_usuario]);
            $userData = $stmtUserLv->fetch(PDO::FETCH_ASSOC);
            $nv_usu = $userData['id_nivel'] ?? 1;
            $arma_anterior = $userData['arma_equipada'] ?? null;

            $stmtArmaCheck = $pdo->prepare("SELECT * FROM arma WHERE id_arma = ?");
            $stmtArmaCheck->execute([$nuevo_id_arma]);
            $armaTarget = $stmtArmaCheck->fetch(PDO::FETCH_ASSOC);

            if ($armaTarget) {
                $req_nivel_arma = $armaTarget['id_nivel'] ?? $armaTarget['nivel_requerido'] ?? 1;
                if ($nv_usu >= $req_nivel_arma) {
                    $stmtCheckAct = $pdo->prepare("SELECT municion_restante FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
                    $stmtCheckAct->execute([$id_usuario, $id_partida]);
                    $mun_actual_bd = $stmtCheckAct->fetchColumn();

                    if ($arma_anterior) {
                        $_SESSION['municiones_armas_' . $id_partida][$arma_anterior] = $mun_actual_bd;
                    }

                    $stmtUpdArma = $pdo->prepare("UPDATE usuario SET arma_equipada = ? WHERE id_usuario = ?");
                    $stmtUpdArma->execute([$nuevo_id_arma, $id_usuario]);

                    $capacidad_maxima = $armaTarget['capacidad_municion'] ?? 5;
                    if (isset($_SESSION['municiones_armas_' . $id_partida][$nuevo_id_arma])) {
                        $municion_a_cargar = $_SESSION['municiones_armas_' . $id_partida][$nuevo_id_arma];
                    } else {
                        $municion_a_cargar = $capacidad_maxima;
                        $_SESSION['municiones_armas_' . $id_partida][$nuevo_id_arma] = $capacidad_maxima;
                    }

                    $stmtUpdMun = $pdo->prepare("UPDATE detalle_partida_usuario SET municion_restante = ? WHERE id_usuario = ? AND id_partida = ?");
                    $stmtUpdMun->execute([$municion_a_cargar, $id_usuario, $id_partida]);

                    $_SESSION['id_arma_activa'] = $nuevo_id_arma;
                    $_SESSION['mensaje_batalla'] = "¡Has cambiado de arma! Munición actual de esta arma: " . $municion_a_cargar . ".";
                } else {
                    $_SESSION['mensaje_batalla'] = "Error: No tienes el nivel suficiente para equipar esa arma.";
                }
            }
        } catch (PDOException $e) {
            die("Error al cambiar de arma: " . $e->getMessage());
        }
    }
    $urlRedir = "partida.php?id_partida=" . urlencode($id_partida);
    if ($id_sala) $urlRedir .= "&id_sala=" . urlencode($id_sala);
    if (!empty($_POST['id_rival'])) $urlRedir .= "&id_rival=" . urlencode($_POST['id_rival']);
    header("Location: " . $urlRedir);
    exit();
}

if (isset($_GET['ajax_verificar']) && $_GET['ajax_verificar'] == '1') {
    header('Content-Type: application/json');
    $id_p = $_GET['id_partida'] ?? 0;

    try {
        $stmtAjax = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
        $stmtAjax->execute([$id_p]);
        $total = $stmtAjax->fetchColumn();

        $stmtPartidaInfo = $pdo->prepare("SELECT estado, ultimo_usuario_atacante FROM partida WHERE id_partida = ?");
        $stmtPartidaInfo->execute([$id_p]);
        $infoP = $stmtPartidaInfo->fetch(PDO::FETCH_ASSOC);

        $stmtGanadorAjax = $pdo->prepare("SELECT id_usuario, ganador FROM detalle_partida_usuario WHERE id_partida = ? AND ganador IS NOT NULL LIMIT 1");
        $stmtGanadorAjax->execute([$id_p]);
        $info_ganador = $stmtGanadorAjax->fetch(PDO::FETCH_ASSOC);

        $stmtCheckMiVida = $pdo->prepare("SELECT vida_restante FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
        $stmtCheckMiVida->execute([$id_usuario, $id_p]);
        $mi_vida_actual = $stmtCheckMiVida->fetchColumn();

        $stmtMinVida = $pdo->prepare("SELECT MIN(vida_restante) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
        $stmtMinVida->execute([$id_p]);
        $min_vida_partida = $stmtMinVida->fetchColumn();

        $stmtSumaAtaques = $pdo->prepare("SELECT SUM(ataques_realizados) FROM detalle_partida_usuario WHERE id_partida = ?");
        $stmtSumaAtaques->execute([$id_p]);
        $total_ataques_global = (int)$stmtSumaAtaques->fetchColumn();
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }

    echo json_encode([
        'total_jugadores'  => (int)$total,
        'estado_partida'   => $infoP['estado'] ?? 'en_espera',
        'ultimo_atacante'  => $infoP['ultimo_usuario_atacante'] ?? null,
        'total_ataques'    => $total_ataques_global,
        'hay_ganador'      => ($info_ganador ? 1 : 0),
        'mi_vida'          => ($mi_vida_actual !== false ? (int)$mi_vida_actual : 100),
        'min_vida'         => ($min_vida_partida !== false ? (int)$min_vida_partida : 100),
        'ganador_id'       => ($info_ganador ? (int)$info_ganador['id_usuario'] : 0)
    ]);
    exit();
}

try {
    $stmtVerificarPartida = $pdo->prepare("SELECT * FROM partida WHERE id_partida = ?");
    $stmtVerificarPartida->execute([$id_partida]);
    $datos_partida = $stmtVerificarPartida->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al verificar partida: " . $e->getMessage());
}

if (!$datos_partida) {
    unset($_SESSION['id_partida_activa']);
    header("Location: inicio.php?mensaje=partida_no_encontrada");
    exit();
}

$es_usuario_listo = (isset($detalle_usuario['activo']) && $detalle_usuario['activo'] == 2);
$estado_actual_partida = $datos_partida['estado'] ?? 'en_espera';
$partida_comenzada = ($estado_actual_partida === 'en_curso');

$vida_usuario_actual = $detalle_usuario['vida_restante'] ?? 100;
$municion_usuario_actual = $detalle_usuario['municion_restante'] ?? 5;
$puntuacion_usuario_actual = $detalle_usuario['puntuacion_total'] ?? 0;
$usuario_derrotado = ($vida_usuario_actual <= 0);
$usuario_ganador = false;

$nombre_ganador_partida = "Desconocido";
try {
    $stmtNomGanador = $pdo->prepare("
        SELECT u.nombre_usuario 
        FROM detalle_partida_usuario dup 
        JOIN usuario u ON dup.id_usuario = u.id_usuario 
        WHERE dup.id_partida = ? AND dup.ganador = 1 
        LIMIT 1
    ");
    $stmtNomGanador->execute([$id_partida]);
    $resGanador = $stmtNomGanador->fetchColumn();
    if ($resGanador) {
        $nombre_ganador_partida = $resGanador;
    }
} catch (PDOException $e) {
}

try {
    $stmtOtrosJugadores = $pdo->prepare("
        SELECT u.id_usuario, u.nombre_usuario, u.arma_equipada, 
               u.personaje_equipado, 
               a.nombre_arma, a.clase_arma, a.imagen_arma, a.dano, a.capacidad_municion, 
               p.nombre_personaje, p.imagen, p.vida_personaje,
               up_part.dano_total_realizado, up_part.vida_restante, up_part.ganador, up_part.activo as estado_listo
        FROM detalle_partida_usuario up_part 
        JOIN usuario u ON up_part.id_usuario = u.id_usuario 
        LEFT JOIN arma a ON u.arma_equipada = a.id_arma 
        LEFT JOIN personaje p ON u.personaje_equipado = p.id_personaje  
        WHERE up_part.id_partida = ? AND u.id_usuario != ? AND up_part.activo != 0
    ");
    $stmtOtrosJugadores->execute([$id_partida, $id_usuario]);
    $otros_jugadores = $stmtOtrosJugadores->fetchAll(PDO::FETCH_ASSOC);

    $stmtTotalJug = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo != 0");
    $stmtTotalJug->execute([$id_partida]);
    $tot_j_partida = $stmtTotalJug->fetchColumn();

    $stmtTotalLis = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo = 2");
    $stmtTotalLis->execute([$id_partida]);
    $tot_l_partida = $stmtTotalLis->fetchColumn();

    if ($tot_j_partida >= 2 && $tot_l_partida >= $tot_j_partida && $estado_actual_partida === 'en_espera') {
        $pdo->prepare("UPDATE partida SET estado = 'en_curso', fecha_inicio = NOW(), fecha_partida = NOW() WHERE id_partida = ?")->execute([$id_partida]);
        $partida_comenzada = true;
        
        $stmtVerificarPartida->execute([$id_partida]);
        $datos_partida = $stmtVerificarPartida->fetch(PDO::FETCH_ASSOC);
        $estado_actual_partida = $datos_partida['estado'] ?? 'en_curso';
    }
} catch (PDOException $e) {
    die("Error al consultar otros jugadores: " . $e->getMessage());
}

$mensaje = "";
if (isset($_SESSION['mensaje_batalla'])) {
    $mensaje = $_SESSION['mensaje_batalla'];
    unset($_SESSION['mensaje_batalla']);
}

$experiencia_procesada_key = 'exp_procesada_' . $id_partida;
$otorgarExperienciaPartida = function () use ($pdo, $id_usuario, $id_partida, $experiencia_procesada_key) {
    try {
        $stmtVerificarRol = $pdo->prepare("SELECT id_rol FROM usuario WHERE id_usuario = ?");
        $stmtVerificarRol->execute([$id_usuario]);
        $id_rol_usuario = $stmtVerificarRol->fetchColumn();

        if ($id_rol_usuario == '1' || strtolower($id_rol_usuario) === 'admin') {
            return; 
        }

        if (!isset($_SESSION[$experiencia_procesada_key])) {
            $stmtPuntPartida = $pdo->prepare("SELECT puntuacion_total FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
            $stmtPuntPartida->execute([$id_usuario, $id_partida]);
            $punt_partida = (int)$stmtPuntPartida->fetchColumn();

            if ($punt_partida > 0) {
                $stmtExp = $pdo->prepare("
                    UPDATE usuario 
                    SET experiencia = experiencia + ?, 
                        id_nivel = CASE 
                            WHEN (experiencia + ?) >= 2000 THEN 3 
                            WHEN (experiencia + ?) >= 500 THEN 2 
                            ELSE 1 
                        END 
                    WHERE id_usuario = ?
                ");
                $stmtExp->execute([$punt_partida, $punt_partida, $punt_partida, $id_usuario]);
            }
            $_SESSION[$experiencia_procesada_key] = true;
        }
    } catch (PDOException $e) {
    }
};

if ($partida_comenzada || $estado_actual_partida === 'finalizado') {
    try {
        $stmtCheckGanadorDetalle = $pdo->prepare("
            SELECT dup.id_usuario, dup.ganador 
            FROM detalle_partida_usuario dup 
            JOIN usuario u ON dup.id_usuario = u.id_usuario 
            WHERE dup.id_partida = ? AND dup.ganador IS NOT NULL AND u.id_rol != '1' 
            LIMIT 1
        ");
        $stmtCheckGanadorDetalle->execute([$id_partida]);
        $info_ganador_existente = $stmtCheckGanadorDetalle->fetch(PDO::FETCH_ASSOC);

        $riv_con_vida = 0;
        foreach ($otros_jugadores as $rj) {
            if (($rj['vida_restante'] ?? 100) > 0) $riv_con_vida++;
        }

        if ($info_ganador_existente) {
            if ($info_ganador_existente['id_usuario'] == $id_usuario) {
                $usuario_ganador = true;
                $otorgarExperienciaPartida();
            } else {
                if ($vida_usuario_actual <= 0) $usuario_derrotado = true;
                $otorgarExperienciaPartida();
            }
        } else {
            if ($vida_usuario_actual <= 0 && count($otros_jugadores) > 0 && $riv_con_vida > 0) {
                $usuario_derrotado = true;
                $otorgarExperienciaPartida();
            } elseif ($vida_usuario_actual <= 0 && count($otros_jugadores) > 0 && $riv_con_vida == 0) {
                $stmtRanking = $pdo->prepare("
                    SELECT dup.id_usuario FROM detalle_partida_usuario dup
                    JOIN usuario u ON dup.id_usuario = u.id_usuario
                    WHERE dup.id_partida = ? AND u.id_rol != '1' AND dup.activo != 0
                    ORDER BY dup.vida_restante DESC, dup.puntuacion_total DESC 
                    LIMIT 1
                ");
                $stmtRanking->execute([$id_partida]);
                $id_ganador_detectado = $stmtRanking->fetchColumn();

                if ($id_ganador_detectado) {
                    $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 1 WHERE id_usuario = ? AND id_partida = ?")->execute([$id_ganador_detectado, $id_partida]);
                    $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 0 WHERE id_usuario != ? AND id_partida = ?")->execute([$id_ganador_detectado, $id_partida]);

                    if ($id_ganador_detectado == $id_usuario) $usuario_ganador = true;
                    else $usuario_derrotado = true;
                    $otorgarExperienciaPartida();
                }
            } elseif ($riv_con_vida == 0 && count($otros_jugadores) > 0 && $vida_usuario_actual > 0) {
                $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 1 WHERE id_usuario = ? AND id_partida = ?")->execute([$id_usuario, $id_partida]);
                $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 0 WHERE id_usuario != ? AND id_partida = ?")->execute([$id_usuario, $id_partida]);
                $usuario_ganador = true;
                $otorgarExperienciaPartida();
            }
        }

        $stmtNomGanador->execute([$id_partida]);
        $resGanador = $stmtNomGanador->fetchColumn();
        if ($resGanador) {
            $nombre_ganador_partida = $resGanador;
        }

        $stmtVerificarGanadorPartida = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND ganador IS NOT NULL");
        $stmtVerificarGanadorPartida->execute([$id_partida]);
        if ($stmtVerificarGanadorPartida->fetchColumn() > 0 || $tiempo_restante <= 0) {
            $pdo->prepare("UPDATE partida SET estado = 'finalizado', fecha_finalizacion = NOW() WHERE id_partida = ? AND fecha_finalizacion IS NULL")->execute([$id_partida]);
            $estado_actual_partida = 'finalizado';
        }
    } catch (PDOException $e) {
        die("Error al gestionar ganadores de partida: " . $e->getMessage());
    }
}

if ($usuario_ganador) {
    $mensaje = "🏆 ¡Victoria! Has ganado el combate, " . htmlspecialchars($nombre_ganador_partida) . ".";
} elseif ($usuario_derrotado) {
    $mensaje = "💀 ¡Derrota! El ganador de esta partida ha sido " . htmlspecialchars($nombre_ganador_partida) . ".";
}

$total_jugadores_sala = count($otros_jugadores) + 1;

try {
    $stmtMundo = $pdo->prepare("
        SELECT m.* FROM sala s 
        JOIN mundo m ON s.id_mundo = m.id_mundo 
        WHERE s.id_sala = ?
    ");
    $stmtMundo->execute([$datos_partida['id_sala'] ?? 0]);
    $datos_mundo = $stmtMundo->fetch(PDO::FETCH_ASSOC) ?: ['mundo' => 'Desconocido', 'imagen_mundo' => ''];
} catch (PDOException $e) {
    $datos_mundo = ['mundo' => 'Desconocido', 'imagen_mundo' => ''];
}

$imagen_fondo = $datos_mundo['imagen_mundo'] ?? $datos_mundo['imagen'] ?? $datos_mundo['url'] ?? '';

try {
    $stmtInfoUsuario = $pdo->prepare("
        SELECT u.nombre_usuario, u.personaje_equipado, u.arma_equipada, u.id_nivel,
               p.imagen, p.nombre_personaje, p.vida_personaje,  
               a.id_arma, a.nombre_arma, a.clase_arma, a.dano, a.capacidad_municion, a.imagen_arma, ca.nombre_clase   
        FROM usuario u  
        LEFT JOIN arma a ON u.arma_equipada = a.id_arma  
        LEFT JOIN clase_arma ca ON a.clase_arma = ca.id_clase
        LEFT JOIN personaje p ON u.personaje_equipado = p.id_personaje  
        WHERE u.id_usuario = ?
    ");
    $stmtInfoUsuario->execute([$id_usuario]);
    $info_jugador = $stmtInfoUsuario->fetch(PDO::FETCH_ASSOC) ?: [];
    $nivel_usuario_actual = $info_jugador['id_nivel'] ?? 1;

    $armas_disponibles_partida = $pdo->query("
        SELECT a.*, c.nombre_clase 
        FROM arma a 
        JOIN clase_arma c ON a.clase_arma = c.id_clase 
        ORDER BY c.nombre_clase ASC, a.nombre_arma ASC;
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar información de usuario/armas: " . $e->getMessage());
}

$id_rival_seleccionado = $_POST['id_rival'] ?? $_GET['id_rival'] ?? null;
if (!$id_rival_seleccionado && !empty($otros_jugadores)) {
    foreach ($otros_jugadores as $cand) {
        if (($cand['vida_restante'] ?? 100) > 0) {
            $id_rival_seleccionado = $cand['id_usuario'];
            break;
        }
    }
    if (!$id_rival_seleccionado) {
        $id_rival_seleccionado = $otros_jugadores[0]['id_usuario'];
    }
}

try {
    $stmtListaTurnos = $pdo->prepare("
        SELECT id_usuario 
        FROM detalle_partida_usuario 
        WHERE id_partida = ? AND activo != 0 
        ORDER BY id_detalle ASC
    ");
    $stmtListaTurnos->execute([$id_partida]);
    $jugadores_en_orden = $stmtListaTurnos->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $jugadores_en_orden = [];
}

$es_mi_turno = false;
if (!empty($jugadores_en_orden)) {
    try {
        $stmtSumaTotalAtaques = $pdo->prepare("SELECT SUM(ataques_realizados) FROM detalle_partida_usuario WHERE id_partida = ?");
        $stmtSumaTotalAtaques->execute([$id_partida]);
        $total_ataques_globales = (int)$stmtSumaTotalAtaques->fetchColumn();

        $indice_turno_actual = $total_ataques_globales % count($jugadores_en_orden);
        $id_usuario_con_turno = $jugadores_en_orden[$indice_turno_actual];

        $es_mi_turno = ($id_usuario == $id_usuario_con_turno);

        if (!$es_mi_turno) {
            $intentos = 0;
            while ($intentos < count($jugadores_en_orden)) {
                $id_usr_prueba = $jugadores_en_orden[$indice_turno_actual];
                $stmtVidaPrueba = $pdo->prepare("SELECT vida_restante FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
                $stmtVidaPrueba->execute([$id_usr_prueba, $id_partida]);
                $vid_prueba = (int)$stmtVidaPrueba->fetchColumn();

                if ($vid_prueba > 0) {
                    $es_mi_turno = ($id_usuario == $id_usr_prueba);
                    break;
                }
                $indice_turno_actual = ($indice_turno_actual + 1) % count($jugadores_en_orden);
                $intentos++;
            }
        }
    } catch (PDOException $e) {
        $es_mi_turno = true;
    }
}

$rival_activo = null;
if ($id_rival_seleccionado) {
    foreach ($otros_jugadores as $j) {
        if ($j['id_usuario'] == $id_rival_seleccionado) {
            $rival_activo = $j;
            break;
        }
    }
    if (!$rival_activo && !empty($otros_jugadores)) {
        $rival_activo = $otros_jugadores[0];
        $id_rival_seleccionado = $rival_activo['id_usuario'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atacar_objetivo'])) {
    try {
        $stmtDanioRealizado = $pdo->prepare("SELECT dano_total_realizado, municion_restante FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
        $stmtDanioRealizado->execute([$id_usuario, $id_partida]);
        $datos_atacante_partida = $stmtDanioRealizado->fetch(PDO::FETCH_ASSOC);
        $dano_acumulado_actual = (int)($datos_atacante_partida['dano_total_realizado'] ?? 0);
        $municion_actual = (int)($datos_atacante_partida['municion_restante'] ?? 5);

        $id_rival_objetivo = $_POST['id_rival'] ?? null;

        $stmtChequearRivalVivo = $pdo->prepare("SELECT vida_restante FROM detalle_partida_usuario WHERE id_usuario = ? AND id_partida = ?");
        $stmtChequearRivalVivo->execute([$id_rival_objetivo, $id_partida]);
        $vida_actual_rival_obj = (int)$stmtChequearRivalVivo->fetchColumn();

        if (!$partida_comenzada || $estado_actual_partida === 'finalizado') {
            $mensaje = "Error: La partida no está activa.";
        } elseif ($usuario_derrotado || $usuario_ganador) {
            $mensaje = "Error: Tu personaje está derrotado y no puede atacar.";
        } elseif ($municion_actual <= 0) {
            $mensaje = "Error: Te has quedado sin munición. Debes cambiar de arma para poder volver a atacar.";
        } elseif ($dano_acumulado_actual >= 400) {
            $mensaje = "Error: Has alcanzado el límite máximo de 400 de daño permitido.";
        } elseif ($total_jugadores_sala < 2) {
            $mensaje = "Error: Se requieren al menos 2 jugadores para atacar.";
        } elseif (!$es_mi_turno) {
            $mensaje = "¡Espera tu turno!";
        } elseif ($vida_actual_rival_obj <= 0) {
            $mensaje = "Error: No puedes atacar a un rival que ya ha sido eliminado.";
        } else {
            $parte_cuerpo = $_POST['parte_cuerpo'] ?? '';

            if (empty($parte_cuerpo) || !$id_rival_objetivo) {
                $mensaje = "Error: Selecciona una parte del cuerpo y un rival válido.";
            } else {
                $puntos_ganados = 0;
                $dano_a_salud = 0;
                $ataque_exitoso = true;
                $incremento_fallido = 0;
                $incremento_acertado = 0;

                $tipo_arma = $info_jugador['nombre_clase'] ?? 'Pistola';

                $tabla_danos = [
                    'Mele' => ['extremidades' => 1, 'torso' => 1, 'cabeza' => 75],
                    'Pistola' => ['extremidades' => 2, 'torso' => 2, 'cabeza' => 75],
                    'Ametralladora' => ['extremidades' => 10, 'torso' => 10, 'cabeza' => 75],
                    'Francotirador' => ['extremidades' => 20, 'torso' => 20, 'cabeza' => 75]
                ];

                if (!isset($tabla_danos[$tipo_arma])) {
                    $tipo_arma = 'Pistola';
                }

                if ($parte_cuerpo === 'piernas' || $parte_cuerpo === 'brazos') {
                    $parte_cuerpo = 'extremidades';
                }

                if ($parte_cuerpo === 'cabeza') {
                    if (rand(1, 100) <= 20) {
                        $ataque_exitoso = false;
                        $incremento_fallido = 1;
                        $mensaje = "¡Fallaste el disparo a la cabeza!";
                    }
                }

                if ($ataque_exitoso) {
                    $dano_base = $tabla_danos[$tipo_arma][$parte_cuerpo] ?? 2;
                    $puntos_ganados += $dano_base;
                    $dano_a_salud = $dano_base;
                    $incremento_acertado = 1;
                    if ($parte_cuerpo === 'cabeza') {
                        $mensaje = "¡Éxito total en la cabeza! +{$dano_base} puntos.";
                    } else {
                        $mensaje = "¡Ataque exitoso en {$parte_cuerpo}! +{$dano_base} puntos.";
                    }
                }
                if (($dano_acumulado_actual + $dano_a_salud) > 400) {
                    $dano_a_salud = 400 - $dano_acumulado_actual;
                }
                $nueva_municion = $municion_actual - 1;
                
                $vida_rival_antes = $vida_actual_rival_obj;
                if ($ataque_exitoso && $dano_a_salud > 0) {
                    $stmtRestarSalud = $pdo->prepare("
                        UPDATE detalle_partida_usuario 
                        SET vida_restante = GREATEST(0, vida_restante - ?) 
                        WHERE id_usuario = ? AND id_partida = ?
                    ");
                    $stmtRestarSalud->execute([$dano_a_salud, $id_rival_objetivo, $id_partida]);
                }
                $vida_rival_despues = max(0, $vida_rival_antes - $dano_a_salud);
                $mato_a_rival = ($vida_rival_antes > 0 && $vida_rival_despues === 0);
                if ($mato_a_rival) {
                    $nueva_municion = (int)($info_jugador['capacidad_municion'] ?? 5);
                    $mensaje .= " ¡Has eliminado a un rival! Tu munición se ha reseteado.";
                    $pdo->prepare("UPDATE detalle_partida_usuario SET bajas = bajas + 1 WHERE id_usuario = ? AND id_partida = ?")->execute([$id_usuario, $id_partida]);
                    $pdo->prepare("UPDATE detalle_partida_usuario SET muertes = muertes + 1 WHERE id_usuario = ? AND id_partida = ?")->execute([$id_rival_objetivo, $id_partida]);
                }
                $arma_id_actual = $info_jugador['id_arma'] ?? null;
                if ($arma_id_actual) {
                    $_SESSION['municiones_armas_' . $id_partida][$arma_id_actual] = $nueva_municion;
                }

                $stmtUpdateDetalle = $pdo->prepare("
                    UPDATE detalle_partida_usuario 
                    SET puntuacion_total = puntuacion_total + ?, 
                        ataques_realizados = ataques_realizados + 1,
                        dano_total_realizado = LEAST(400, dano_total_realizado + ?),
                        disparos_acertados = disparos_acertados + ?,
                        disparos_fallados = disparos_fallados + ?,
                        municion_restante = ?
                    WHERE id_usuario = ? AND id_partida = ?
                ");
                $stmtUpdateDetalle->execute([$puntos_ganados, $dano_a_salud, $incremento_acertado, $incremento_fallido, $nueva_municion, $id_usuario, $id_partida]);
                
                $pdo->prepare("UPDATE partida SET puntuacion = puntuacion + ? WHERE id_partida = ?")->execute([$puntos_ganados, $id_partida]);
                $pdo->prepare("UPDATE partida SET ultimo_usuario_atacante = ? WHERE id_partida = ?")->execute([$id_usuario, $id_partida]);
            }
        }

        $stmtVerifRivVivos = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND id_usuario != ? AND vida_restante > 0 AND activo != 0");
        $stmtVerifRivVivos->execute([$id_partida, $id_usuario]);
        if ($stmtVerifRivVivos->fetchColumn() == 0) {
            $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 1 WHERE id_usuario = ? AND id_partida = ?")->execute([$id_usuario, $id_partida]);
            $pdo->prepare("UPDATE detalle_partida_usuario SET ganador = 0 WHERE id_usuario != ? AND id_partida = ?")->execute([$id_usuario, $id_partida]);
            $pdo->prepare("UPDATE partida SET estado = 'finalizado', fecha_finalizacion = NOW() WHERE id_partida = ?")->execute([$id_partida]);
        }
    } catch (PDOException $e) {
        $mensaje = "Error al ejecutar ataque: " . $e->getMessage();
    }

    $_SESSION['mensaje_batalla'] = $mensaje;
    $urlRedirect = "partida.php?id_partida=" . urlencode($id_partida);
    if ($id_sala) $urlRedirect .= "&id_sala=" . urlencode($id_sala);
    if ($id_rival_seleccionado) $urlRedirect .= "&id_rival=" . urlencode($id_rival_seleccionado);
    header("Location: " . $urlRedirect);
    exit();
}

try {
    $stmtCheck->execute([$id_usuario, $id_partida]);
    $detalle_usuario = $stmtCheck->fetch();
} catch (PDOException $e) {
    $detalle_usuario = [];
}

$vida_usuario_actual = $detalle_usuario['vida_restante'] ?? 100;
$municion_usuario_actual = $detalle_usuario['municion_restante'] ?? 5;
$puntuacion_usuario_actual = $detalle_usuario['puntuacion_total'] ?? 0;
if ($vida_usuario_actual <= 0) $usuario_derrotado = true;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Batalla en Curso - Partida #<?= htmlspecialchars((string)$id_partida) ?></title>
    <link rel="stylesheet" href="../assets/estilos/style_partida.css">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('../assets/img/<?= htmlspecialchars($imagen_fondo) ?>') no-repeat center center fixed !important;
            background-size: cover !important;
        }
    </style>
</head>

<body>
    <div class="arena-container">
        <div class="header-batalla">
            <div>
                <h2 style="margin: 0; color: #f1c40f;">Arena de Combate</h2>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #ccc;">Mundo Activo: <b style="color: #3498db;"><?= htmlspecialchars($datos_mundo['mundo'] ?? 'Desconocido') ?></b></p>
            </div>
            <div style="text-align: right; display: flex; gap: 20px; align-items: center;">
                <div>
                    <span style="font-size: 12px; color: #e74c3c; display: block; font-weight: bold;">CIERRE AUTOMÁTICO EN:</span>
                    <div style="font-size: 18px; font-weight: bold; color: #e67e22;" id="cronometro" data-segundos="<?= $tiempo_restante ?>"><?= $partida_comenzada ? $tiempo_restante . " seg" : "Esperando inicio..." ?></div>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div>
                        <span style="font-size: 14px; color: #aaa;">Tus Puntos:</span>
                        <div style="font-size: 22px; font-weight: bold; color: #2ecc71;"><?= $puntuacion_usuario_actual ?> pts</div>
                    </div>
                    <div>
                        <span style="font-size: 14px; color: #aaa;">Puntuación de Sala:</span>
                        <div style="font-size: 22px; font-weight: bold; color: #f39c12;"><?= $datos_partida['puntuacion'] ?? 0 ?> pts</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($estado_actual_partida === 'finalizado'): ?>
            <div class="modal-fin-partida">
                <div class="modal-contenido-fin">
                    <h2 style="color: #f1c40f; margin-top: 0;">¡Partida Finalizada!</h2>
                    <p style="color: #ddd; font-size: 16px; margin-bottom: 20px;">
                        <?php if ($usuario_ganador): ?>
                            🏆 ¡Victoria! Has ganado la partida, <b><?= htmlspecialchars($nombre_ganador_partida) ?></b>.
                        <?php elseif ($usuario_derrotado): ?>
                            💀 Derrota. El ganador de la partida ha sido <b><?= htmlspecialchars($nombre_ganador_partida) ?></b>.
                        <?php elseif ($tiempo_restante <= 0 && empty($datos_partida['fecha_finalizacion'])): ?>
                            ⏱️ El tiempo de la partida ha expirado. El ganador es <b><?= htmlspecialchars($nombre_ganador_partida) ?></b>.
                        <?php else: ?>
                            🏁 La partida ha finalizado por eliminación. El ganador es <b><?= htmlspecialchars($nombre_ganador_partida) ?></b>.
                        <?php endif; ?>
                    </p>
                    <p style="color: #aaa; font-size: 14px; margin-bottom: 25px;">¿Qué deseas hacer a continuación?</p>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="accion" value="seguir_jugando">
                            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 12px 20px; font-size: 15px; font-weight: bold; border-radius: 5px; cursor: pointer;">🔄 Seguir Jugando</button>
                        </form>
                        <a href="partida.php?id_partida=<?= $id_partida ?><?php echo $id_sala ? '&id_sala=' . $id_sala : ''; ?>&accion=abandonar" style="background: #c0392b; color: white; padding: 12px 20px; border-radius: 5px; text-decoration: none; font-size: 15px; font-weight: bold; display: inline-block;">🚪 Volver al Menú</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$partida_comenzada && $estado_actual_partida !== 'finalizado'): ?>
            <div style="background: rgba(241, 196, 15, 0.9); color: #222; padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: bold; text-align: center;">
                <?php if ($total_jugadores_sala < 2): ?>
                    ⚠️ Se necesitan al menos 2 jugadores para poder dar a listo y comenzar la partida. (Actuales: <?= $total_jugadores_sala ?>/2)
                <?php else: ?>
                    ⚠️ Sala en Fase de Preparación. Debes presionar el botón "Estoy Listo" para que comience la partida.
                    <br><br>
                    <?php if (!$es_usuario_listo): ?>
                        <form method="POST">
                            <input type="hidden" name="accion" value="dar_listo">
                            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 10px 20px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer;">✅ ¡ESTOY LISTO PARA EMPEZAR!</button>
                        </form>
                    <?php else: ?>
                        <span style="color: #27ae60; font-size: 15px; background: #fff; padding: 5px 15px; border-radius: 4px;">✔ Ya estás listo. Esperando a los demás jugadores...</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php elseif (!$es_mi_turno && $estado_actual_partida !== 'finalizado'): ?>
            <div style="background: rgba(52, 152, 219, 0.9); color: white; padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: bold; text-align: center;">⏳ No es tu turno. Espera a que los demás jugadores realicen su movimiento secuencial.</div>
        <?php elseif ($estado_actual_partida !== 'finalizado'): ?>
            <div style="background: rgba(39, 174, 96, 0.9); color: white; padding: 10px; margin-bottom: 20px; border-radius: 6px; font-weight: bold; text-align: center;">⚔️ ¡Es tu turno de atacar! Selecciona el rival, la parte del cuerpo y ejecuta tu jugada.</div>
        <?php endif; ?>

        <?php if (!empty($mensaje)): ?>
            <div style="background: rgba(44, 62, 80, 0.9); color: white; padding: 12px; margin-bottom: 20px; border-radius: 6px; font-weight: bold; text-align: center;"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="duelo-superior-grid">
            <?php if ($info_jugador): ?>
                <div style="background: #1a1a1a; border: 2px solid #fc7e35; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; gap: 10px; position: relative;">
                    <span style="position: absolute; top: -10px; left: 15px; background: #fc7e35; color: white; padding: 2px 10px; font-size: 11px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($info_jugador['nombre_usuario'] ?? 'Usuario') ?> (Tú)</span>

                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; margin-top: 5px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (!empty($info_jugador['imagen'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($info_jugador['imagen']) ?>" alt="Personaje" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px;">
                            <?php else: ?>
                                <div style="width:70px;height:70px;background:#444;display:flex;align-items:center;justify-content:center;font-size:10px;border-radius:6px;">Sin imagen</div>
                            <?php endif; ?>
                            <div>
                                <span style="font-size: 11px; color: #aaa; display:block;">PERSONAJE (Vida: <b style="color:<?= $vida_usuario_actual <= 0 ? '#e74c3c' : '#2ecc71' ?>;"><?= $vida_usuario_actual ?></b>/100)</span>
                                <span style="font-size: 11px; color: #f39c12; display:block;">MUNICIÓN: <b><?= $municion_usuario_actual ?></b> / <?= htmlspecialchars($info_jugador['capacidad_municion'] ?? 5) ?></span>
                                <b style="font-size: 15px; color: #fff;"><?= htmlspecialchars($info_jugador['nombre_personaje'] ?? 'Sin asignar') ?></b>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.5); padding: 8px 12px; border-radius: 6px; border: 1px solid #444;">
                            <?php if (!empty($info_jugador['imagen_arma'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($info_jugador['imagen_arma']) ?>" alt="Arma Actual" style="width: 45px; height: 45px; object-fit: contain;">
                            <?php endif; ?>
                            <div style="text-align: right;">
                                <span style="font-size: 10px; color: #aaa; display: block;">ARMA EQUIPADA</span>
                                <b style="font-size: 13px; color: #f1c40f; display: block;"><?= htmlspecialchars($info_jugador['nombre_arma'] ?? 'Sin arma') ?></b>
                                <span style="font-size: 10px; color: #3498db;">Daño: <?= htmlspecialchars($info_jugador['dano'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div style="background: rgba(0, 0, 0, 0.9); border: 1px solid #555; padding: 10px; border-radius: 6px; width: 100%; box-sizing: border-box;">
                        <form method="POST" style="display: flex; gap: 10px; align-items: center; justify-content: space-between; width: 100%;">
                            <input type="hidden" name="accion" value="cambiar_arma_partida">
                            <input type="hidden" name="id_rival" value="<?= htmlspecialchars((string)$id_rival_seleccionado) ?>">
                            <label style="font-size: 12px; color: #f1c40f; font-weight: bold; white-space: nowrap;">Cambiar Arma:</label>
                            <select name="id_arma" style="flex-grow: 1; background: #222; color: #fff; border: 1px solid #666; padding: 6px; border-radius: 4px; font-size: 12px;">
                                <?php foreach ($armas_disponibles_partida as $arm):
                                    $req_nv = $arm['id_nivel'] ?? $arm['nivel_requerido'] ?? 1;
                                    $bloq = ($nivel_usuario_actual < $req_nv);
                                    $es_act = (($info_jugador['id_arma'] ?? 0) == $arm['id_arma']);
                                ?>
                                    <option value="<?= $arm['id_arma'] ?>" <?= $es_act ? 'selected' : '' ?> <?= $bloq ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($arm['nombre_arma']) ?><?php if ($bloq) echo " - Bloqueada"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" style="background: #e67e22; color: white; border: none; padding: 6px 14px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 12px;">Equipar</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <div class="rival-grande-box" style="margin-bottom: 0; position: relative; display: flex; align-items: center; justify-content: space-between; padding: 15px; min-height: 100px;">
                <span style="position: absolute; top: 4px; left: 12px; background: #e4311d; color: white; padding: 3px 8px; font-size: 11px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($rival_activo['nombre_usuario'] ?? 'RIVAL OBJETIVO') ?></span>
                <?php if ($rival_activo): ?>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px; width: 100%;">
                        <?php if (!empty($rival_activo['imagen'])): ?>
                            <img src="../assets/img/<?= htmlspecialchars($rival_activo['imagen']) ?>" alt="Rival" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px;">
                        <?php else: ?>
                            <div style="width:70px;height:70px;background:#333;display:flex;align-items:center;justify-content:center;border-radius:6px;color:#777;font-size:10px;">Sin img</div>
                        <?php endif; ?>
                        <div>
                            <span style="font-size: 11px; color: #aaa; display:block;">JUGADOR: <b><?= htmlspecialchars($rival_activo['nombre_usuario']) ?></b> (<?= $rival_activo['estado_listo'] == 2 ? '✅ Listo' : '⏳ Esperando' ?>)</span>
                            <b style="font-size: 15px; color: #fff; display: block;"><?= htmlspecialchars($rival_activo['nombre_personaje'] ?? 'Sin Personaje') ?></b>
                            <span style="font-size: 12px; color: #e74c3c; display: block;">Vida: <b><?= $rival_activo['vida_restante'] ?? 100 ?></b>/100</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 15px; background: rgba(0,0,0,0.4); padding: 8px 12px; border-radius: 6px; border: 1px solid #444;">
                        <?php if (!empty($rival_activo['imagen_arma'])): ?>
                            <img src="../assets/img/<?= htmlspecialchars($rival_activo['imagen_arma']) ?>" alt="Arma Rival" style="width: 45px; height: 45px; object-fit: contain;">
                        <?php endif; ?>
                        <div style="text-align: right;">
                            <span style="font-size: 10px; color: #aaa; display: block;">ARMA EQUIPADA</span>
                            <b style="font-size: 13px; color: #f1c40f;"><?= htmlspecialchars($rival_activo['nombre_arma'] ?? 'Sin arma') ?></b>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="width: 100%; text-align: center; color: #888; padding-top: 15px;">
                        <p style="margin: 0; font-size: 14px;">No hay rivales disponibles...</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="zona-combate-grid">
            <div class="rival-grande-box">
                <span style="font-size: 12px; color: #888; display:block; margin-bottom: 8px; font-weight: bold;">Seleccionar otro blanco:</span>
                <?php if (!empty($otros_jugadores)): ?>
                    <div class="lista-rivales-mini">
                        <?php foreach ($otros_jugadores as $j): 
                            $eliminado = (($j['vida_restante'] ?? 100) <= 0);
                        ?>
                            <div class="miniatura-rival <?= ($id_rival_seleccionado == $j['id_usuario']) ? 'activo' : '' ?> <?= $eliminado ? 'eliminado' : '' ?>" 
                                 <?php if (!$eliminado): ?>
                                     onclick="window.location.href='partida.php?id_partida=<?= $id_partida ?><?php echo $id_sala ? '&id_sala=' . $id_sala : ''; ?>&id_rival=<?= $j['id_usuario'] ?>'"
                                 <?php else: ?>
                                     style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);"
                                 <?php endif; ?>>
                                <?php if (!empty($j['imagen'])): ?>
                                    <img src="../assets/img/<?= htmlspecialchars($j['imagen']) ?>" alt="Mini">
                                    <p><?= htmlspecialchars($j['nombre_usuario']) ?> <?= $eliminado ? '(💀)' : '' ?></p>
                                <?php else: ?>
                                    <div style="width:45px;height:45px;background:#222;font-size:8px;display:flex;align-items:center;justify-content:center;">N/D</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="font-size: 12px; color: #666; margin: 0;">No hay más jugadores disponibles.</p>
                <?php endif; ?>
            </div>

            <div class="panel-ataque">
                <h3 style="margin-top: 0; border-bottom: 1px solid #444; padding-bottom: 8px;">Panel de Ofensiva</h3>
                <p style="font-size: 13px; color: #bbb;">Elige dónde impactar al rival:</p>
                <form method="POST" id="formCombate">
                    <input type="hidden" name="id_rival" value="<?= htmlspecialchars((string)$id_rival_seleccionado) ?>">
                    <div class="cuerpo-mapa">
                        <button type="button" class="btn-parte" onclick="seleccionarParte('cabeza', this)" <?= (!$partida_comenzada || $usuario_derrotado || $usuario_ganador || !$es_mi_turno || $estado_actual_partida === 'finalizado') ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>🎯 Cabeza</button>
                        <button type="button" class="btn-parte" onclick="seleccionarParte('torso', this)" <?= (!$partida_comenzada || $usuario_derrotado || $usuario_ganador || !$es_mi_turno || $estado_actual_partida === 'finalizado') ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>🛡️ Torso</button>
                        <button type="button" class="btn-parte" onclick="seleccionarParte('extremidades', this)" <?= (!$partida_comenzada || $usuario_derrotado || $usuario_ganador || !$es_mi_turno || $estado_actual_partida === 'finalizado') ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>🦾 Extremidades</button>
                    </div>
                    <input type="hidden" name="parte_cuerpo" id="inputParteCuerpo" value="" required>
                    <button type="submit" name="atacar_objetivo" <?= (!$partida_comenzada || $usuario_derrotado || $usuario_ganador || !$es_mi_turno || $estado_actual_partida === 'finalizado') ? 'disabled style="opacity:0.5; cursor:not-allowed; background:#7f8c8d;"' : '' ?> style="margin-top: 25px; background: #e74c3c; color: white; border: none; padding: 12px; cursor: pointer; border-radius: 6px; font-weight: bold; width: 100%; font-size: 15px;">⚔️ Ejecutar Ataque</button>
                </form>
            </div>
        </div>
        <br>
        <div style="text-align: center; margin-top: 10px;">
            <a href="partida.php?id_partida=<?= $id_partida ?><?php echo $id_sala ? '&id_sala=' . $id_sala : ''; ?>&accion=abandonar" style="color: #e74c3c; text-decoration: none; font-size: 14px; font-weight: bold;">Abandonar partida</a>
        </div>
    </div>

    <script>
        function seleccionarParte(parte, boton) {
            document.getElementById('inputParteCuerpo').value = parte;
            let botones = document.querySelectorAll('.btn-parte');
            botones.forEach(b => b.classList.remove('seleccionado'));
            boton.classList.add('seleccionado');
        }

        document.addEventListener("DOMContentLoaded", function() {
            const cronometroElemento = document.getElementById("cronometro");
            let partidaComenzadaJS = <?= json_encode($partida_comenzada) ?>;
            let estadoPartidaJS = <?= json_encode($estado_actual_partida) ?>;
            
            if (cronometroElemento && partidaComenzadaJS && estadoPartidaJS !== 'finalizado') {
                let segundosRestantes = parseInt(cronometroElemento.getAttribute("data-segundos"));

                const intervalo = setInterval(function() {
                    if (segundosRestantes <= 0) {
                        clearInterval(intervalo);
                        cronometroElemento.textContent = "¡Tiempo terminado!";
                        window.location.reload(); 
                        return;
                    }

                    let minutos = Math.floor(segundosRestantes / 60);
                    let segundos = segundosRestantes % 60;
                    
                    if (minutos > 0) {
                        cronometroElemento.textContent = String(minutos).padStart(2, '0') + ":" + String(segundos).padStart(2, '0') + " min";
                    } else {
                        cronometroElemento.textContent = segundos + " seg";
                    }
                    
                    segundosRestantes--;
                }, 1000);
            }
        });

        let estadoPartidaActual = <?= json_encode($estado_actual_partida) ?>;
        let totalJugadoresInicial = <?= json_encode((int)$total_jugadores_sala) ?>;
        let minVidaInicial = <?= json_encode((int)min(array_merge([$vida_usuario_actual], array_column($otros_jugadores, 'vida_restante')))) ?>;
        let totalAtaquesInicial = <?= json_encode($total_ataques_globales ?? 0) ?>;
        let miVidaInicial = <?= json_encode((int)$vida_usuario_actual) ?>;
        let idPartida = <?= json_encode((int)$id_partida) ?>;
        let idSalaVal = <?= json_encode($id_sala ? (int)$id_sala : null) ?>;

        setInterval(function() {
            if (!idPartida || estadoPartidaActual === 'finalizado') return;
            let ajaxUrl = window.location.pathname + '?id_partida=' + idPartida + (idSalaVal ? '&id_sala=' + idSalaVal : '') + '&ajax_verificar=1';

            fetch(ajaxUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.estado_partida && data.estado_partida !== estadoPartidaActual) {
                        window.location.reload();
                        return;
                    }
                    if (data.total_jugadores !== undefined && data.total_jugadores !== totalJugadoresInicial) {
                        window.location.reload();
                        return;
                    }
                    if (data.min_vida !== undefined && data.min_vida !== minVidaInicial) {
                        window.location.reload();
                        return;
                    }
                    if (data.mi_vida !== undefined && data.mi_vida !== miVidaInicial) {
                        window.location.reload();
                        return;
                    }
                    if (data.total_ataques !== undefined && data.total_ataques !== totalAtaquesInicial) {
                        window.location.reload();
                        return;
                    }
                    if (data.hay_ganador === 1) {
                        window.location.reload();
                        return;
                    }
                })
                .catch(error => console.error('Error al comprobar actualizaciones de la partida:', error));
        }, 2000);
    </script>
</body>

</html>