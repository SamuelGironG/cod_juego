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
    die("Error de conexión a la base de datos");
}

$id_usuario = $_SESSION['usuario_id'];
$modulo = $_GET['modulo'] ?? 'principal';
$accion = $_POST['accion'] ?? '';

$stmt = $pdo->prepare("SELECT u.*, r.nombre_rol, n.nivel, n.id_nivel FROM usuario u LEFT JOIN rol r ON u.id_rol = r.id_rol LEFT JOIN nivel n ON u.id_nivel = n.id_nivel WHERE u.id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$nivel_usuario_id = $usuario['id_nivel'] ?? 1;

$es_admin = (isset($usuario['nombre_rol']) && strtolower(trim($usuario['nombre_rol'])) === 'administrador');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'elegir_personaje') {
        $id_personaje = $_POST['id_personaje'] ?? null;
        if ($id_personaje) {
            $stmtPersCheck = $pdo->prepare("SELECT * FROM personaje WHERE id_personaje = ?");
            $stmtPersCheck->execute([$id_personaje]);
            $persTarget = $stmtPersCheck->fetch(PDO::FETCH_ASSOC);

            $req_nivel = $persTarget['id_nivel'] ?? $persTarget['nivel_requerido'] ?? 1;

            if ($nivel_usuario_id >= $req_nivel) {
                $stmtDesactivar = $pdo->prepare("UPDATE usuario SET personaje_equipado = ? WHERE id_usuario = ?");
                $stmtDesactivar->execute([$id_personaje, $id_usuario]);

                $_SESSION['id_personaje_activo'] = $id_personaje;
            }
            header("Location: ?modulo=operador");
            exit();
        }
    } elseif ($accion === 'desequipar_personaje') {
        $stmtDesac = $pdo->prepare("UPDATE usuario SET personaje_equipado = NULL WHERE id_usuario = ?");
        $stmtDesac->execute([$id_usuario]);
        unset($_SESSION['id_personaje_activo']);
        header("Location: ?modulo=operador");
        exit();
    } elseif ($accion === 'elegir_arma') {
        $id_arma = $_POST['id_arma'] ?? null;
        if ($id_arma) {
            $stmtArmaCheck = $pdo->prepare("SELECT * FROM arma WHERE id_arma = ?");
            $stmtArmaCheck->execute([$id_arma]);
            $armaTarget = $stmtArmaCheck->fetch(PDO::FETCH_ASSOC);

            $req_nivel_arma = $armaTarget['id_nivel'] ?? $armaTarget['nivel_requerido'] ?? 1;

            if ($nivel_usuario_id >= $req_nivel_arma) {
                $stmtDesactivarArma = $pdo->prepare("UPDATE usuario SET arma_equipada = ? WHERE id_usuario = ?");
                $stmtDesactivarArma->execute([$id_arma, $id_usuario]);

                $_SESSION['id_arma_activa'] = $id_arma;
            }
            header("Location: ?modulo=arma");
            exit();
        }
    } elseif ($accion === 'desequipar_arma') {
        $stmtDesacArma = $pdo->prepare("UPDATE usuario SET arma_equipada = NULL WHERE id_usuario = ?");
        $stmtDesacArma->execute([$id_usuario]);
        unset($_SESSION['id_arma_activa']);
        header("Location: ?modulo=arma");
        exit();
    } elseif ($accion === 'elegir_avatar_usuario') {
        $id_avatar = $_POST['id_avatar'] ?? null;
        if ($id_avatar) {
            $stmtAvCheck = $pdo->prepare("SELECT * FROM avatar_usuario WHERE id_avatar = ?");
            $stmtAvCheck->execute([$id_avatar]);
            $avatarTarget = $stmtAvCheck->fetch(PDO::FETCH_ASSOC);

            if ($avatarTarget) {
                $nombre_archivo_avatar = $avatarTarget['imagen_avatar'] ?? $avatarTarget['url'] ?? $avatarTarget['nombre_archivo'] ?? 'default_avatar.png';
                $stmtUpd = $pdo->prepare("UPDATE usuario SET avatar = ? WHERE id_usuario = ?");
                $stmtUpd->execute([$nombre_archivo_avatar, $id_usuario]);
            }
            header("Location: ?modulo=perfil");
            exit();
        }
    } elseif ($accion === 'actualizar_nombre') {
        $nuevo_nombre = trim($_POST['nuevo_nombre'] ?? '');
        $intentos_actuales = $usuario['cambios_nombre'] ?? $usuario['intentos_nombre'] ?? 0;

        if (!empty($nuevo_nombre) && $intentos_actuales < 3) {
            $nuevo_limite = $intentos_actuales + 1;
            try {
                $stmtUpdName = $pdo->prepare("UPDATE usuario SET nombre_usuario = ?, cambios_nombre = ? WHERE id_usuario = ?");
                $stmtUpdName->execute([$nuevo_nombre, $nuevo_limite, $id_usuario]);
            } catch (Exception $e) {
                $stmtUpdName = $pdo->prepare("UPDATE usuario SET nombre_usuario = ?, intentos_nombre = ? WHERE id_usuario = ?");
                $stmtUpdName->execute([$nuevo_nombre, $nuevo_limite, $id_usuario]);
            }
        }
        header("Location: ?modulo=perfil");
        exit();
    }
}

$stmt = $pdo->prepare("SELECT u.*, r.nombre_rol, n.nivel, n.id_nivel FROM usuario u LEFT JOIN rol r ON u.id_rol = r.id_rol LEFT JOIN nivel n ON u.id_nivel = n.id_nivel WHERE u.id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$id_personaje_activo = $usuario['personaje_equipado'] ?? null;
if ($id_personaje_activo) {
    $_SESSION['id_personaje_activo'] = $id_personaje_activo;
} else {
    unset($_SESSION['id_personaje_activo']);
}

$id_arma_activa = $usuario['arma_equipada'] ?? null;
if ($id_arma_activa) {
    $_SESSION['id_arma_activa'] = $id_arma_activa;
} else {
    unset($_SESSION['id_arma_activa']);
}

$personaje_activo = null;
if ($id_personaje_activo) {
    $stmtPers = $pdo->prepare("SELECT * FROM personaje WHERE id_personaje = ?");
    $stmtPers->execute([$id_personaje_activo]);
    $personaje_activo = $stmtPers->fetch(PDO::FETCH_ASSOC);
}

$arma_activa = null;
if ($id_arma_activa) {
    $stmtArma = $pdo->prepare("SELECT * FROM arma WHERE id_arma = ?");
    $stmtArma->execute([$id_arma_activa]);
    $arma_activa = $stmtArma->fetch(PDO::FETCH_ASSOC);
}

$personajes_disponibles = $pdo->query("SELECT * FROM personaje")->fetchAll(PDO::FETCH_ASSOC);
usort($personajes_disponibles, function ($a, $b) {
    $nivelA = $a['id_nivel'] ?? $a['nivel_requerido'] ?? 1;
    $nivelB = $b['id_nivel'] ?? $b['nivel_requerido'] ?? 1;

    if ($nivelA == $nivelB) {
        $nombreA = strtolower(trim($a['nombre_personaje'] ?? ''));
        $nombreB = strtolower(trim($b['nombre_personaje'] ?? ''));
        return strcmp($nombreA, $nombreB);
    }
    return $nivelA <=> $nivelB;
});

$armas_disponibles = $pdo->query("SELECT a.*, c.nombre_clase FROM arma a JOIN clase_arma c ON a.clase_arma = c.id_clase ORDER BY FIELD(c.id_clase, 4, 1, 2, 3), a.nombre_arma ASC;")->fetchAll(PDO::FETCH_ASSOC);

$avatares_usuario_disponibles = [];
try {
    $avatares_usuario_disponibles = $pdo->query("SELECT * FROM avatar_usuario")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $avatares_usuario_disponibles = [];
}

$avatar_usuario = (!empty($usuario['avatar'])) ? $usuario['avatar'] : 'default_avatar.png';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cuartel General</title>
    <link rel="stylesheet" href="../assets/estilos/style_inicio_player.css">
</head>

<body>
    <video autoplay muted loop id="bg-video">
        <source src="../assets/img/MW Beta (2019 Sep) Main Menu Screen Video file (main_menu.bik).mp4" type="video/mp4">
        Tu navegador no soporta videos en segundo plano.
    </video>

    <div class="user-widget-top-container" style="display: flex; gap: 10px; align-items: center;">
        <div class="user-widget-top">
            <div class="widget-avatar-container">
                <img src="../assets/img/<?= htmlspecialchars($avatar_usuario) ?>" alt="Avatar">
            </div>
            <div class="widget-info">
                <p class="widget-username"><?= htmlspecialchars($usuario['nombre_usuario'] ?? 'Soldado') ?></p>
                <p class="widget-nivel">Nivel: <?= htmlspecialchars($usuario['nivel'] ?? 'Nivel 1') ?></p>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 5px;">
            <a href="?modulo=perfil" class="btn-ver-perfil-top" style="text-align: center; text-decoration: none;">Ver Perfil</a>
            <a href="?modulo=historial" class="btn-ver-perfil-top" style="text-align: center; text-decoration: none;">Historial</a>
        </div>
    </div>

    <div class="main-container">

        <?php if ($modulo === 'principal'):
            $stmt = $pdo->prepare("SELECT p.*, f.nombre_faccion FROM usuario u LEFT JOIN personaje p ON u.personaje_equipado = p.id_personaje LEFT JOIN facciones f ON p.faccion = f.id_faccion WHERE u.id_usuario = ? LIMIT 1");
            $stmt->execute([$id_usuario]);
            $personaje_faccion = $stmt->fetch(PDO::FETCH_ASSOC)
        ?>
            <div class="mw-card">
                <div>
                    <div class="card-header">
                        <h2>Cuartel General</h2>
                        <span class="tag-operador">
                            <?= htmlspecialchars($personaje_faccion['nombre_faccion'] ?? 'Sin Facción') ?>
                        </span>
                    </div>

                    <div class="operador-showcase">
                        <div class="operador-img-container" style="width: 100px; height: 120px;">
                            <?php if ($personaje_activo && !empty($personaje_activo['imagen'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($personaje_activo['imagen']) ?>" alt="Operador">
                            <?php else: ?>
                                <div class="operador-img-placeholder">Sin Operador</div>
                            <?php endif; ?>
                        </div>
                        <div class="operador-details">
                            <p>Operador Activo</p>
                            <h3><?= htmlspecialchars($personaje_activo['nombre_personaje'] ?? 'Ninguno') ?></h3>
                        </div>
                    </div>
                    <div class="operador-showcase">
                        <div class="operador-img-container" style="width: 150px; height: 120px;">
                            <?php
                            $imgArmaActiva = $arma_activa['imagen_arma'] ?? $arma_activa['img'] ?? '';
                            if ($arma_activa && !empty($imgArmaActiva)):
                            ?>
                                <img src="../assets/img/<?= htmlspecialchars($imgArmaActiva) ?>" alt="Arma">
                            <?php else: ?>
                                <div class="operador-img-placeholder">Sin Arma</div>
                            <?php endif; ?>
                        </div>
                        <div class="operador-details">
                            <p>Arma Activa</p>
                            <h3><?= htmlspecialchars($arma_activa['nombre_arma'] ?? $arma_activa['nombre'] ?? 'Ninguna') ?></h3>
                        </div>
                    </div>
                </div>

                <div class="grupo-botones" style="flex-direction: column; gap: 10px;">
                    <div style="display: flex; gap: 10px; width: 100%;">
                        <a href="?modulo=operador" class="btn-perfil">Elegir Operador</a>
                        <a href="?modulo=arma" class="btn-perfil">Elegir Arma</a>
                    </div>
                    <div style="display: flex; gap: 10px; width: 100%;">
                        <a href="?modulo=lobby" class="btn-lobby">Lobby</a>
                    </div>

                    <?php if ($es_admin): ?>
                        <a href="../admin/dashboard.php" class="btn-admin-dashboard">Panel de Administrador</a>
                    <?php endif; ?>

                    <a href="../includes/logout.php" class="btn-logout" onclick="return confirm('¿Estás seguro de cerrar sesión?')" style="width: 100%; box-sizing: border-box;">Salir</a>
                </div>
            </div>

        <?php elseif ($modulo === 'lobby'): ?>
            <?php
            try {
                // Consulta optimizada para verificar los usuarios activos en tiempo real por cada sala
                $stmtSalasLlenas = $pdo->query(" 
                    SELECT s.id_sala, s.nombre_sala, s.capacidad_sala, s.id_mundo, s.estado_sala,
                    (SELECT COUNT(*) FROM partida p JOIN detalle_partida_usuario dpu ON p.id_partida = dpu.id_partida WHERE p.id_sala = s.id_sala AND dpu.activo = 1) AS total_activos
                    FROM sala s
                    WHERE s.estado_sala IN (1,2) OR s.estado_sala IS NULL
                ");
                $listaSalasVerificar = $stmtSalasLlenas->fetchAll(PDO::FETCH_ASSOC);

                $contadorSalasPorNombre = [];

                foreach ($listaSalasVerificar as $salaVerif) {
                    $capacidadMaxSala = $salaVerif['capacidad_sala'] ?? 5;
                    $activosEnSala = $salaVerif['total_activos'] ?? 0;
                    $nombreSalaActual = $salaVerif['nombre_sala'];
                    $idMundoActual = $salaVerif['id_mundo'];
                    $idSalaActual = $salaVerif['id_sala'];

                    $claveGrupo = $nombreSalaActual . '_' . $idMundoActual;
                    if (!isset($contadorSalasPorNombre[$claveGrupo])) {
                        $contadorSalasPorNombre[$claveGrupo] = [];
                    }
                    $contadorSalasPorNombre[$claveGrupo][] = [
                        'id_sala' => $idSalaActual,
                        'activos' => $activosEnSala,
                        'capacidad' => $capacidadMaxSala
                    ];

                    // Actualizar estado dinámicamente si se llenó o si se desocupó tras un abandono
                    if ($activosEnSala >= $capacidadMaxSala) {
                        $stmtEstado = $pdo->prepare("UPDATE sala SET estado_sala = 2 WHERE id_sala = ?");
                        $stmtEstado->execute([$idSalaActual]);
                    } else {
                        $stmtEstado = $pdo->prepare("UPDATE sala SET estado_sala = 1 WHERE id_sala = ?");
                        $stmtEstado->execute([$idSalaActual]);
                    }
                }

                // Evaluar duplicación de salas llenas y eliminación de salas vacías excedentes
                foreach ($contadorSalasPorNombre as $clave => $salasGrupo) {
                    list($nombreSala, $idMundo) = explode('_', $clave, 2);
                    
                    $haySalaLibre = false;
                    foreach ($salasGrupo as $s) {
                        if ($s['activos'] < $s['capacidad']) {
                            $haySalaLibre = true;
                            break;
                        }
                    }

                    // Si todas las salas de este tipo se llenaron, creamos una nueva copia idéntica
                    if (!$haySalaLibre && count($salasGrupo) > 0) {
                        $capacidadReferencia = $salasGrupo[0]['capacidad'];
                        $stmtDuplicar = $pdo->prepare("INSERT INTO sala (nombre_sala, capacidad_sala, id_mundo, estado_sala) VALUES (?, ?, ?, 1)");
                        $stmtDuplicar->execute([$nombreSala, $capacidadReferencia, $idMundo]);
                    }

                    // Si hay múltiples copias y alguna queda completamente vacía (0 usuarios), se elimina el exceso
                    if (count($salasGrupo) > 1) {
                        // Ordenamos para priorizar borrar las salas que tengan 0 usuarios activos
                        usort($salasGrupo, function($a, $b) {
                            return $a['activos'] <=> $b['activos'];
                        });

                        foreach ($salasGrupo as $s) {
                            if ($s['activos'] == 0 && count($salasGrupo) > 1) {
                                // Limpiar partida asociada antes de eliminar la sala por integridad
                                $stmtDelPartida = $pdo->prepare("DELETE FROM partida WHERE id_sala = ?");
                                $stmtDelPartida->execute([$s['id_sala']]);

                                $stmtEliminarVacia = $pdo->prepare("DELETE FROM sala WHERE id_sala = ?");
                                $stmtEliminarVacia->execute([$s['id_sala']]);
                                
                                // Reducir la cuenta del grupo actual para detener la eliminación en cascada
                                array_shift($salasGrupo);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
            }

            $salas = [];
            try {
                $stmtSalas = $pdo->query(" 
                    SELECT s.id_sala, s.nombre_sala, s.capacidad_sala, m.mundo AS nombre_mundo, m.nivel_requerido, m.imagen_mundo, p.id_partida, s.estado_sala,
                    (SELECT COUNT(*) FROM detalle_partida_usuario dpu JOIN partida pt ON dpu.id_partida = pt.id_partida WHERE pt.id_sala = s.id_sala AND dpu.activo = 1) AS usuarios_actuales
                    FROM sala s 
                    LEFT JOIN mundo m ON s.id_mundo = m.id_mundo 
                    LEFT JOIN partida p ON s.id_sala = p.id_sala
                    WHERE s.estado_sala IN (1,2) OR s.estado_sala IS NULL
                    GROUP BY s.id_sala
                    ORDER BY s.id_sala ASC
                ");
                $salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $salas = [];
            }

            $tiene_operador = !empty($id_personaje_activo);
            $tiene_arma = !empty($id_arma_activa);
            $puede_unirse_sala = ($tiene_operador && $tiene_arma);
            ?>
            <div class="mw-card" style="max-width: 900px; margin: 0 auto;">
                <div class="card-header">
                    <h2>Lobby de Salas</h2>
                    <span class="tag-operador">Selecciona Sala</span>
                </div>

                <?php if (!$puede_unirse_sala): ?>
                    <div style="background: rgba(192, 57, 43, 0.2); border: 1px solid #c0392b; color: #fff; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 13px;">
                        ⚠️ <strong>Acceso Restringido:</strong> Debes tener un <strong>Operador</strong> y un <strong>Arma</strong> equipados en el Cuartel General para poder unirte a las salas.
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; max-height: 500px; overflow-y: auto; padding-right: 5px;">
                    <?php if (!empty($salas)): ?>
                        <?php foreach ($salas as $sala):
                            $idSala = $sala['id_sala'] ?? 1;
                            $idPartida = $sala['id_partida'] ?? '';
                            $nombreSala = $sala['nombre_sala'] ?? 'Sala sin nombre';
                            $nombreMundo = $sala['nombre_mundo'] ?? 'Mundo Desconocido';
                            $nivelReq = $sala['nivel_requerido'] ?? 1;

                            if ($nivelReq == 1) {
                                $bloqueadoNivel = ($nivel_usuario_id != 1);
                            } else {
                                $bloqueadoNivel = ($nivel_usuario_id < 2);
                                if ($nivelReq < 2) {
                                    $nivelReq = 2;
                                }
                            }

                            $bloqueadoNivelMayorEnSala = false;
                            if (!empty($idPartida)) {
                                $stmtCheckNivelMayor = $pdo->prepare("
                                    SELECT COUNT(*) FROM detalle_partida_usuario dpu 
                                    JOIN usuario u ON dpu.id_usuario = u.id_usuario 
                                    WHERE dpu.id_partida = ? AND dpu.activo = 1 AND u.id_nivel > ?
                                ");
                                $stmtCheckNivelMayor->execute([$idPartida, $nivel_usuario_id]);
                                $hayUsuariosMayorNivel = $stmtCheckNivelMayor->fetchColumn();
                                if ($hayUsuariosMayorNivel > 0) {
                                    $bloqueadoNivelMayorEnSala = true;
                                }
                            }

                            $bloqueadoSala = $bloqueadoNivel || !$puede_unirse_sala || $bloqueadoNivelMayorEnSala;

                            $capacidadMax = $sala['capacidad_sala'] ?? 5;
                            $usuariosActuales = $sala['usuarios_actuales'] ?? 0;
                            if ($usuariosActuales < 0) {
                                $usuariosActuales = 0;
                            }
                            $salaLlena = ($usuariosActuales >= $capacidadMax);
                            $imagenMundo = $sala['imagen_mundo'] ?? 'default_mundo.png';
                        ?>
                            <div style="background: rgba(0,0,0,0.4); border: 1px solid <?= $bloqueadoSala ? 'rgba(231, 76, 60, 0.4)' : 'rgba(255,255,255,0.15)' ?>; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; position: relative;">
                                <?php if ($bloqueadoSala): ?>
                                    <div style="position: absolute; top: 8px; right: 8px; background: rgba(192, 57, 43, 0.9); color: white; padding: 2px 6px; font-size: 11px; border-radius: 4px; font-weight: bold; z-index: 2;">
                                        🔒 Bloqueado
                                    </div>
                                <?php endif; ?>
                                <div style="width: 100%; height: 130px; background-color: #222; overflow: hidden; position: relative; <?= $bloqueadoSala ? 'filter: grayscale(60%);' : '' ?>">
                                    <img src="../assets/img/<?= htmlspecialchars($imagenMundo) ?>" alt="<?= htmlspecialchars($nombreMundo) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                </div>
                                <div style="padding: 15px; text-align: center; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between;">
                                    <div>
                                        <h3 style="margin: 0 0 4px 0; color: #fff; font-size: 16px;"><?= htmlspecialchars($nombreSala) ?> </h3>
                                        <p style="font-size: 12px; color: #f1c40f; margin: 0 0 8px 0;">(Nivel Req: <?= $nivelReq ?>)</p>
                                        <p style="font-size: 13px; color: #aaa; margin: 0 0 5px 0;">Jugadores: <strong style="color: #2ecc71;"><?= $usuariosActuales ?></strong> / <strong><?= $capacidadMax ?></strong></p>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <?php if (!$bloqueadoSala): ?>

                                            <a href="partida.php?id_sala=<?= $idSala ?>&id_partida=<?= $idPartida ?>"
                                                class="btn-perfil"
                                                style="display:block;width:100%;padding:8px 0;background:#28a745;color:#fff;text-decoration:none;border-radius:4px;font-size:13px;text-align:center;box-sizing:border-box;">
                                                UNIRSE
                                            </a>
                                        <?php else: ?>
                                            <button type="button" disabled style="display:block;width:100%;padding:8px 0;background:#7f8c8d;color:#fff;border:none;border-radius:4px;font-size:13px;text-align:center;cursor:not-allowed;box-sizing:border-box;"> <?php if ($salaLlena) { echo "SALA LLENA"; } elseif ($bloqueadoNivelMayorEnSala) { echo "NIVEL MUY BAJO";} elseif (!$puede_unirse_sala) {echo "EQUIPA OPERADOR Y ARMA";} else {echo "NIVEL INSUFICIENTE";} ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--mw-muted); font-size: 13px; grid-column: 1 / -1; text-align: center; padding: 20px;">No hay salas disponibles en este momento.</p>
                    <?php endif; ?>
                </div>
                <div class="grupo-botones" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="?modulo=principal" class="btn-lobby" style="display: block; text-align: center; padding: 10px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;">Volver al Cuartel General</a>
                </div>
            </div>
        <?php elseif ($modulo === 'historial'): ?>
            <?php
            $historial_partidas = [];
            try {
                $stmtHistorial = $pdo->prepare(" SELECT dpu.puntuacion_total AS puntuacion, dpu.bajas, dpu.muertes, dpu.ganador, p.fecha_partida AS fecha_inicio, p.fecha_finalizacion , m.mundo AS nombre_mundo, m.imagen_mundo,s.nombre_sala,
                    (SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = dpu.id_partida) AS total_jugadores FROM detalle_partida_usuario dpu 
                    JOIN partida p 
                    ON dpu.id_partida = p.id_partida
                    LEFT JOIN sala s ON p.id_sala = s.id_sala
                    LEFT JOIN mundo m ON s.id_mundo = m.id_mundo
                    WHERE dpu.id_usuario = ? 
                    ORDER BY p.id_partida DESC
                ");
                $stmtHistorial->execute([$id_usuario]);
                $historial_partidas = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $historial_partidas = [];
            }
            ?>
            <div class="mw-card" style="max-width: 850px; margin: 0 auto;">
                <div class="card-header">
                    <h2>Historial de Partidas</h2>
                    <span class="tag-operador">Reportes</span>
                </div>
                <div style="max-height: 450px; overflow-y: auto; margin: 15px 0; padding-right: 5px;">
                    <?php if (!empty($historial_partidas)): ?>
                        <table style="width: 100%; border-collapse: collapse; color: #fff; font-size: 13px; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid rgba(255,255,255,0.2); color: #f1c40f;">
                                    <th style="padding: 10px;">Mundo / Sala</th>
                                    <th style="padding: 10px;">Puntuación</th>
                                    <th style="padding: 10px;">Bajas / Muertes</th>
                                    <th style="padding: 10px;">Resultado</th>
                                    <th style="padding: 10px;">Jugadores</th>
                                    <th style="padding: 10px;">Fecha inicio</th>
                                    <th style="padding: 10px;">Fecha finalizacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial_partidas as $hp):
                                    $esGanador = (isset($hp['ganador']) && $hp['ganador'] == 1);
                                ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <td style="padding: 10px; display: flex; align-items: center; gap: 8px;">
                                            <?php if (!empty($hp['imagen_mundo'])): ?>
                                                <img src="../assets/img/<?= htmlspecialchars($hp['imagen_mundo']) ?>" alt="Mundo" style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <span><?= htmlspecialchars($hp['nombre_mundo'] ?? 'Zona de Combate') ?></span>
                                                <div style="font-size: 11px; color: #aaa;"><?= htmlspecialchars($hp['nombre_sala'] ?? 'Sala Principal') ?></div>
                                            </div>
                                        </td>
                                        <td style="padding: 10px; font-weight: bold; color: #2ecc71;"><?= $hp['puntuacion'] ?? 0 ?> pts</td>
                                        <td style="padding: 10px; color: #e74c3c;"><?= $hp['bajas'] ?? 0 ?> K / <?= $hp['muertes'] ?? 0 ?> D</td>
                                        <td style="padding: 10px; font-weight: bold; color: <?= $esGanador ? '#2ecc71' : '#e74c3c' ?>;">
                                            <?= $esGanador ? 'Victoria' : 'Derrota' ?>
                                        </td>
                                        <td style="padding: 10px;"><?= $hp['total_jugadores'] ?? 1 ?></td>
                                        <td style="padding: 10px; color: #aaa; font-size: 12px;"><?= $hp['fecha_inicio'] ?? 'N/A' ?></td>
                                        <td style="padding: 10px; color: #aaa; font-size: 12px;"><?= $hp['fecha_finalizacion'] ?? 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: var(--mw-muted); font-size: 13px; text-align: center; padding: 30px;">Aún no hay registros en tu historial de partidas.</p>
                    <?php endif; ?>
                </div>
                <div class="grupo-botones" style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="?modulo=principal" class="btn-perfil" style="text-align: center;">Regresar al Cuartel General</a>
                </div>
            </div>
        <?php elseif ($modulo === 'estadisticas'): ?>
            <?php
            $stats_jugador = ['partidas_jugadas' => 0, 'puntuacion_total' => 0, 'mejor_puntuacion' => 0, 'total_bajas' => 0, 'total_muertes' => 0];
            try {
                $stmtStats = $pdo->prepare(" SELECT COUNT(*) as partidas_jugadas, 
                           SUM(puntuacion_total) AS total_puntuacion,  MAX(puntuacion_total) AS mejor_puntuacion, SUM(bajas) as total_bajas,
                           SUM(muertes) as total_muertes FROM detalle_partida_usuario  WHERE id_usuario = ?
                ");
                $stmtStats->execute([$id_usuario]);
                $resultadoStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                if ($resultadoStats) {
                    $stats_jugador = $resultadoStats;
                }
            } catch (Exception $e) {
                $stats_jugador = ['partidas_jugadas' => 0, 'puntuacion_total' => 0, 'mejor_puntuacion' => 0, 'total_bajas' => 0, 'total_muertes' => 0];
            }
            ?>
            <div class="mw-card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h2>Estadísticas de Combate</h2>
                    <span class="tag-operador">Rendimiento</span>
                </div>
                <div style="margin: 20px 0; display: flex; flex-direction: column; gap: 12px;">
                    <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #aaa; font-size: 14px;">Partidas Jugadas:</span>
                        <strong style="color: #3498db; font-size: 18px;"><?= $stats_jugador['partidas_jugadas'] ?? 0 ?></strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #aaa; font-size: 14px;">Mejor Puntuación:</span>
                        <strong style="color: #2ecc71; font-size: 18px;"><?= $stats_jugador['mejor_puntuacion'] ?? 0 ?> pts</strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #aaa; font-size: 14px;">Total de Bajas:</span>
                        <strong style="color: #e74c3c; font-size: 18px;"><?= $stats_jugador['total_bajas'] ?? 0 ?></strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #aaa; font-size: 14px;">Total de Muertes:</span>
                        <strong style="color: #f39c12; font-size: 18px;"><?= $stats_jugador['total_muertes'] ?? 0 ?></strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #aaa; font-size: 14px;">Puntuación Acumulada:</span>
                        <strong style="color: #f1c40f; font-size: 18px;"><?= $stats_jugador['total_puntuacion'] ?? 0 ?> pts</strong>
                    </div>
                </div>
                <div class="grupo-botones" style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="?modulo=principal" class="btn-perfil" style="text-align: center;">Regresar al Cuartel General</a>
                </div>
            </div>
        <?php elseif ($modulo === 'perfil'): ?>
            <div class="mw-card">
                <div>
                    <div class="card-header">
                        <h2>Expediente de Usuario</h2>
                        <span class="tag-operador">ID: <?= htmlspecialchars($usuario['id_usuario']) ?></span>
                    </div>
                    <div class="perfil-avatar-section">
                        <div class="perfil-avatar-container">
                            <img src="../assets/img/<?= htmlspecialchars($avatar_usuario) ?>" alt="Avatar de Usuario">
                        </div>
                        <a href="?modulo=elegir_avatar" class="btn-equipar-grid" style="width: auto; padding: 6px 12px; margin-top: 5px; text-decoration: none; display: inline-block;">Cambiar Avatar</a>
                    </div>

                    <div class="perfil-info">
                        <p>
                            <strong>Nombre de usuario:</strong>
                            <span><?= htmlspecialchars($usuario['nombre_usuario'] ?? 'N/A') ?></span>
                        </p>
                        <p><strong>Comunicaciones:</strong> <span><?= htmlspecialchars($usuario['correo_usuario'] ?? 'N/A') ?></span></p>
                        <p><strong>Rango Global:</strong> <span><?= htmlspecialchars($usuario['nombre_rol'] ?? 'Soldado') ?></span></p>
                        <p><strong>Nivel de Autoridad:</strong> <span><?= htmlspecialchars($usuario['nivel'] ?? 'Nivel 1') ?></span></p>
                        <p><strong>XP Acumulada:</strong> <span><?= htmlspecialchars($usuario['experiencia'] ?? '0') ?></span></p>
                    </div>
                </div>
                <div class="grupo-botones" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                    <a href="?modulo=estadisticas" class="btn-perfil" style="text-align: center; text-decoration: none; background: #2980b9;">Ver Estadísticas</a>
                    <a href="?modulo=principal" class="btn-perfil" style="text-align: center;">Regresar</a>
                </div>
            </div>
        <?php elseif ($modulo === 'elegir_avatar'): ?>
            <div class="mw-card">
                <div>
                    <div class="card-header">
                        <h2>Elegir Avatar</h2>
                        <span class="tag-operador">Personalización</span>
                    </div>
                    <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--mw-muted); margin-bottom: 8px;">Selecciona tu imagen de perfil</label>
                    <div class="grid-selección">
                        <?php if (count($avatares_usuario_disponibles) > 0): ?>
                            <?php foreach ($avatares_usuario_disponibles as $av):
                                $idAv = $av['id_avatar'] ?? $av['id'] ?? '';
                                $imgAv = $av['imagen_avatar'] ?? $av['url'] ?? $av['nombre_archivo'] ?? 'default_avatar.png';
                                $nombreAv = $av['nombre_avatar'] ?? $av['nombre'] ?? 'Avatar';
                                $es_activo_avatar = ($avatar_usuario === $imgAv);
                            ?>
                                <div class="item-card <?= $es_activo_avatar ? 'activo' : '' ?>">
                                    <div class="item-img">
                                        <img src="../assets/img/<?= htmlspecialchars($imgAv) ?>" alt="Avatar">
                                    </div>
                                    <div class="item-nombre" title="<?= htmlspecialchars($nombreAv) ?>"><?= htmlspecialchars($nombreAv) ?></div>
                                    <form method="POST" style="width: 100%; margin-top: 5px;">
                                        <input type="hidden" name="accion" value="elegir_avatar_usuario">
                                        <input type="hidden" name="id_avatar" value="<?= $idAv ?>">
                                        <button type="submit" class="btn-equipar-grid"><?= $es_activo_avatar ? 'Actual' : 'Seleccionar' ?></button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--mw-muted); font-size: 12px; grid-column: 1 / -1; text-align: center; padding: 20px;">No hay registros disponibles en la tabla <code>avatar_usuario</code>.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grupo-botones" style="margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="?modulo=perfil" class="btn-perfil" style="text-align: center;">Regresar al Perfil</a>
                </div>
            </div>

        <?php elseif ($modulo === 'operador'): ?>
            <div class="mw-card">
                <div>
                    <div class="card-header">
                        <h2>Seleccionar Operador</h2>
                        <span class="tag-operador">Armería</span>
                    </div>

                    <div class="operador-showcase" style="margin-bottom: 12px; padding: 10px 15px;">
                        <div class="operador-img-container" style="width: 100px; height: 120px;">
                            <?php if ($personaje_activo && !empty($personaje_activo['imagen'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($personaje_activo['imagen']) ?>" alt="Operador">
                            <?php else: ?>
                                <div class="operador-img-placeholder">N/A</div>
                            <?php endif; ?>
                        </div>
                        <div class="operador-details">
                            <p style="font-size: 10px;">Operador Activo</p>
                            <h3 style="font-size: 13px; margin: 0;"><?= htmlspecialchars($personaje_activo['nombre_personaje'] ?? 'Ninguno') ?></h3>
                        </div>
                    </div>

                    <?php if (!empty($id_personaje_activo)): ?>
                        <form method="POST" style="margin-bottom: 15px;">
                            <input type="hidden" name="accion" value="desequipar_personaje">
                            <button type="submit" class="btn-perfil" style="background: #c0392b; width: 100%; border: none; padding: 8px; cursor: pointer; color: #fff; border-radius: 4px;">Desequipar Operador</button>
                        </form>
                    <?php endif; ?>

                    <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--mw-muted); margin-bottom: 8px;">Lista de Operadores</label>

                    <div class="grid-selección">
                        <?php foreach ($personajes_disponibles as $pers):
                            $req_nivel = $pers['id_nivel'] ?? $pers['nivel_requerido'] ?? 1;
                            $bloqueado = ($nivel_usuario_id < $req_nivel);
                            $es_activo = ($id_personaje_activo == $pers['id_personaje']);
                        ?>
                            <div class="item-card <?= $bloqueado ? 'bloqueado' : ($es_activo ? 'activo' : '') ?>">
                                <div class="item-img" style="width: 100px; height: 130px;">
                                    <?php if (!empty($pers['imagen'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($pers['imagen']) ?>" alt="Operador">
                                    <?php else: ?>
                                        <div style="font-size: 9px; color: #777;">Sin Imagen</div>
                                    <?php endif; ?>

                                    <?php if ($bloqueado): ?>
                                        <div class="badge-candado">
                                            🔒
                                            <span>Req. <?= $req_nivel ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-nombre" title="<?= htmlspecialchars($pers['nombre_personaje']) ?>"><?= htmlspecialchars($pers['nombre_personaje']) ?></div>
                                <div class="item-detalle"><?= $pers['vida_personaje'] ?? 100 ?> HP</div>

                                <?php if (!$bloqueado): ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="accion" value="elegir_personaje">
                                        <input type="hidden" name="id_personaje" value="<?= $pers['id_personaje'] ?>">
                                        <button type="submit" class="btn-equipar-grid"><?= $es_activo ? 'Equipado' : 'Equipar' ?></button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn-equipar-grid" disabled>Bloqueado</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="grupo-botones" style="margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="?modulo=principal" class="btn-perfil" style="text-align: center;">Regresar</a>
                </div>
            </div>
        <?php elseif ($modulo === 'arma'): ?>
            <div class="mw-card">
                <div>
                    <div class="card-header">
                        <h2>SELECCIONAR ARMA </h2>
                        <span class="tag-operador">Armería</span>
                    </div>
                    <div class="operador-showcase" style="margin-bottom: 12px; padding: 10px 15px;">
                        <div class="operador-img-container" style="width: 170px; height: 120px;">
                            <?php
                            $dano = $arma_activa['dano'] ?? 0;
                            $capacidadmun = $arma_activa['capacidad_municion'] ?? 0;
                            $imgArmaActivaShowcase = $arma_activa['imagen_arma'] ?? $arma_activa['img'] ?? '';
                            if ($arma_activa && !empty($imgArmaActivaShowcase)):
                            ?>
                                <img src="../assets/img/<?= htmlspecialchars($imgArmaActivaShowcase) ?>" alt="Arma Activa">
                            <?php else: ?>
                                <div class="operador-img-placeholder">N/A</div>
                            <?php endif; ?>
                        </div>
                        <div class="operador-details">
                            <p style="font-size: 13px;">Arma Activa</p>
                            <h3 style="font-size: 13px; margin: 0;"><?= htmlspecialchars($arma_activa['nombre_arma'] ?? $arma_activa['nombre'] ?? 'Ninguna') ?></h3>
                            <p style="font-size: 13px;">Daño: <?= $dano ?></p>
                            <p style="font-size: 13px;">Munición: <?= $capacidadmun ?></p>
                        </div>
                    </div>

                    <?php if (!empty($id_arma_activa)): ?>
                        <form method="POST" style="margin-bottom: 15px;">
                            <input type="hidden" name="accion" value="desequipar_arma">
                            <button type="submit" class="btn-perfil" style="background: #c0392b; width: 100%; border: none; padding: 8px; cursor: pointer; color: #fff; border-radius: 4px;">Desequipar Arma</button>
                        </form>
                    <?php endif; ?>

                    <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--mw-muted); margin-bottom: 8px;">Lista de Armas</label>
                    <?php
                    usort($armas_disponibles, function ($a, $b) {
                        $nombreA = strtolower(trim($a['nombre_arma'] ?? $a['nombre'] ?? ''));
                        $nombreB = strtolower(trim($b['nombre_arma'] ?? $b['nombre'] ?? ''));
                        $claseA = strtolower(trim($a['clase_arma'] ?? $a['tipo'] ?? ''));
                        $claseB = strtolower(trim($b['clase_arma'] ?? $b['tipo'] ?? ''));

                        $obtenerPeso = function ($nombre, $clase) {
                            if ($clase == 'melee' || $clase == 'cuerpo a cuerpo') {
                                return 1;
                            }
                            if ($clase == 'pistola' || $clase == 'pistolas') {
                                return 2;
                            }
                            if ($clase == 'ametralladora' || $clase == 'ametralladora') {
                                return 3;
                            }
                            if ($clase == 'francotirador' || $clase == 'francotiradores') {
                                return 4;
                            }
                            return 99;
                        };
                        $pesoA = $obtenerPeso($nombreA, $claseA);
                        $pesoB = $obtenerPeso($nombreB, $claseB);
                        return $pesoA <=> $pesoB;
                    });
                    ?>
                    <div class="grid-selección">
                        <?php foreach ($armas_disponibles as $arma):
                            $nombreArma = $arma['nombre_arma'] ?? $arma['nombre'] ?? 'Arma';
                            $idArmaVal = $arma['id_arma'] ?? $arma['id'] ?? '';
                            $capacidadmun = $arma['capacidad_municion'] ?? $arma['municion'] ?? '';
                            $dano = $arma['dano'] ?? $arma['dan'] ?? '';
                            $req_nivel_arma = $arma['id_nivel'] ?? $arma['nivel_requerido'] ?? 1;
                            $bloqueadoArma = ($nivel_usuario_id < $req_nivel_arma);
                            $es_activo_arma = ($id_arma_activa == $idArmaVal);
                            $imgArma = $arma['imagen_arma'] ?? $arma['img'] ?? '';
                        ?>
                            <div class="item-card <?= $bloqueadoArma ? 'bloqueado' : ($es_activo_arma ? 'activo' : '') ?>">
                                <div class="item-img">
                                    <?php if (!empty($imgArma)): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($imgArma) ?>" alt="Arma">
                                    <?php else: ?>
                                        <div style="font-size: 9px; color: #777;">Arma</div>
                                    <?php endif; ?>

                                    <?php if ($bloqueadoArma): ?>
                                        <div class="badge-candado">
                                            🔒
                                            <span>Req. <?= $req_nivel_arma ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-nombre" title="<?= htmlspecialchars($nombreArma) ?>"><?= htmlspecialchars($nombreArma) ?></div>
                                <div class="item-detalle">Capacidad: <?= $capacidadmun ?></div>
                                <div class="item-detalle">Daño: <?= $dano ?></div>
                                <div class="item-detalle">Nv. Req: <?= $req_nivel_arma ?></div>
                                <?php if (!$bloqueadoArma): ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="accion" value="elegir_arma">
                                        <input type="hidden" name="id_arma" value="<?= $idArmaVal ?>">
                                        <button type="submit" class="btn-equipar-grid"><?= $es_activo_arma ? 'Equipada' : 'Equipar' ?></button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn-equipar-grid" disabled>Bloqueado</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="grupo-botones" style="margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="?modulo=principal" class="btn-perfil" style="text-align: center;">Regresar</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>