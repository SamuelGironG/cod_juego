<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../includes/login.php");
    exit();
}
require_once("../connect/conn.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die("Error de conexión a la base de datos");
}

function enviarCorreoActivacion(string $correo, string $nombre)
{
    if (empty($correo)) return;
    $mail = new PHPMailer(true);
    try {

        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "codjuego1011@gmail.com";
        $mail->Password   = "vrub zgfv bytk hayj";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('sampruebas2013@gmail.com', 'Administracion del Juego');
        $mail->addAddress($correo, $nombre);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '¡Tu cuenta ha sido activada!';
        $mail->Body    = "Hola <b>$nombre</b>,<br><br>Nos complace informarte que tu cuenta en el sistema ha sido <b>activada exitosamente</b>. Ya puedes ingresar y disfrutar del juego.<br><br>¡Te esperamos en el campo de batalla!";
        $mail->AltBody = "Hola $nombre, nos complace informarte que tu cuenta ha sido activada exitosamente.";

        $mail->send();
    } catch (Exception $e) {
    }
}

$modulo = $_GET['modulo'] ?? 'dashboard';
$accion = $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'crear_usuario') {
        $nombre = $_POST['nombre_usuario'];
        $correo = $_POST['correo_usuario'];
        $estado = $_POST['estado_usuario'];

        $stmt = $pdo->prepare("INSERT INTO usuario (nombre_usuario, correo_usuario, password, estado_usuario, id_rol, id_nivel) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $correo, $_POST['password'], $estado, $_POST['id_rol'], $_POST['id_nivel']]);
        if ((int)$estado === 1) {
            enviarCorreoActivacion($correo, $nombre);
        }
        header('Location: ?modulo=usuarios');
        exit;
    }
    if ($accion === 'actualizar_usuario') {
        $id_usuario = $_POST['id_usuario'];
        $nombre = $_POST['nombre_usuario'];
        $correo = $_POST['correo_usuario'];
        $estado = $_POST['estado_usuario'];

        $stmtCheck = $pdo->prepare("SELECT estado_usuario FROM usuario WHERE id_usuario = ?");
        $stmtCheck->execute([$id_usuario]);
        $usuarioAnterior = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $estadoAnterior = $usuarioAnterior ? (int)$usuarioAnterior['estado_usuario'] : 0;

        if (!empty($_POST['password'])) {
            $stmt = $pdo->prepare("UPDATE usuario SET nombre_usuario = ?, correo_usuario = ?, password = ?, estado_usuario = ?, id_rol = ?, id_nivel = ? WHERE id_usuario = ?");
            $stmt->execute([$nombre, $correo, $_POST['password'], $estado, $_POST['id_rol'], $_POST['id_nivel'], $id_usuario]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuario SET nombre_usuario = ?, correo_usuario = ?, estado_usuario = ?, id_rol = ?, id_nivel = ? WHERE id_usuario = ?");
            $stmt->execute([$nombre, $correo, $estado, $_POST['id_rol'], $_POST['id_nivel'], $id_usuario]);
        }

        if ($estadoAnterior === 0 && (int)$estado === 1) {
            enviarCorreoActivacion($correo, $nombre);
        }

        header('Location: ?modulo=usuarios');
        exit;
    }
    
    if ($accion === 'crear_arma') {
        $nombre_imagen = '';
        if (isset($_FILES['imagen_arma']) && $_FILES['imagen_arma']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen_arma']['name'];
            move_uploaded_file($_FILES['imagen_arma']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("INSERT INTO arma (nombre_arma, capacidad_municion, dano, imagen_arma, nivel_requerido, clase_arma) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nombre_arma'], $_POST['capacidad_municion'], $_POST['dano'], $nombre_imagen, $_POST['nivel_requerido'], $_POST['clase_arma']]);
        header('Location: ?modulo=armas');
        exit;
    }
    if ($accion === 'actualizar_arma') {
        $nombre_imagen = $_POST['imagen_actual'];
        if (isset($_FILES['imagen_arma']) && $_FILES['imagen_arma']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen_arma']['name'];
            move_uploaded_file($_FILES['imagen_arma']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("UPDATE arma SET nombre_arma = ?, capacidad_municion = ?, dano = ?, imagen_arma = ?, nivel_requerido = ?, clase_arma = ? WHERE id_arma = ?");
        $stmt->execute([$_POST['nombre_arma'], $_POST['capacidad_municion'], $_POST['dano'], $nombre_imagen, $_POST['nivel_requerido'], $_POST['clase_arma'], $_POST['id_arma']]);
        header('Location: ?modulo=armas');
        exit;
    }
    if ($accion === 'crear_mundo') {
        $nombre_imagen = '';
        if (isset($_FILES['imagen_mundo']) && $_FILES['imagen_mundo']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen_mundo']['name'];
            move_uploaded_file($_FILES['imagen_mundo']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("INSERT INTO mundo (mundo, nivel_requerido, imagen_mundo) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['mundo'], $_POST['nivel_requerido'], $nombre_imagen]);
        header('Location: ?modulo=mundos');
        exit;
    }
    if ($accion === 'actualizar_mundo') {
        $nombre_imagen = $_POST['imagen_actual'];
        if (isset($_FILES['imagen_mundo']) && $_FILES['imagen_mundo']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen_mundo']['name'];
            move_uploaded_file($_FILES['imagen_mundo']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("UPDATE mundo SET mundo = ?, nivel_requerido = ?, imagen_mundo = ? WHERE id_mundo = ?");
        $stmt->execute([$_POST['mundo'], $_POST['nivel_requerido'], $nombre_imagen, $_POST['id_mundo']]);
        header('Location: ?modulo=mundos');
        exit;
    }
    if ($accion === 'crear_personaje') {
        $nombre_imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen']['name'];
            move_uploaded_file($_FILES['imagen']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("INSERT INTO personaje (nombre_personaje, vida_personaje, imagen, nivel_requerido, faccion) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nombre_personaje'], $_POST['vida_personaje'], $nombre_imagen, $_POST['nivel_requerido'], $_POST['faccion']]);
        header('Location: ?modulo=personajes');
        exit;
    }
    if ($accion === 'actualizar_personaje') {
        $nombre_imagen = $_POST['imagen_actual'];
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen']['name'];
            move_uploaded_file($_FILES['imagen']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("UPDATE personaje SET nombre_personaje = ?, vida_personaje = ?, imagen = ?, nivel_requerido = ?, faccion = ? WHERE id_personaje = ?");
        $stmt->execute([$_POST['nombre_personaje'], $_POST['vida_personaje'], $nombre_imagen, $_POST['nivel_requerido'], $_POST['faccion'], $_POST['id_personaje']]);
        header('Location: ?modulo=personajes');
        exit;
    }
    if ($accion === 'crear_avatar') {
        $nombre_imagen = '';
        if (isset($_FILES['imagen_avatar']) && $_FILES['imagen_avatar']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen_avatar']['name'];
            move_uploaded_file($_FILES['imagen_avatar']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("INSERT INTO avatar_usuario (nombre_avatar, imagen_avatar, faccion) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['nombre_avatar'], $nombre_imagen, $_POST['faccion']]);
        header('Location: ?modulo=avatars');
        exit;
    }
    if ($accion === 'actualizar_avatar') {
        $nombre_imagen = $_POST['imagen_actual'];
        if (isset($_FILES['imagen_avatar']) && $_FILES['imagen_avatar']['error'] === UPLOAD_ERR_OK) {
            $nombre_imagen = time() . '_' . $_FILES['imagen_avatar']['name'];
            move_uploaded_file($_FILES['imagen_avatar']['tmp_name'], '../assets/img/' . $nombre_imagen);
        }
        $stmt = $pdo->prepare("UPDATE avatar_usuario SET nombre_avatar = ?, imagen_avatar = ?, faccion = ? WHERE id_avatar = ?");
        $stmt->execute([$_POST['nombre_avatar'], $nombre_imagen, $_POST['faccion'], $_POST['id_avatar']]);
        header('Location: ?modulo=avatars');
        exit;
    }
    if ($accion === 'crear_sala') {
        $id_mundo = $_POST['id_mundo'];
        $capacidad = intval($_POST['capacidad_sala']);
        $estado_sala = isset($_POST['estado_sala']) ? intval($_POST['estado_sala']) : 1;
        $nombre_sala = trim($_POST['nombre_sala']);
        $stmt = $pdo->prepare("INSERT INTO sala (id_mundo, capacidad_sala, estado_sala, nombre_sala) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_mundo, $capacidad, $estado_sala, $nombre_sala]);
        header('Location: ?modulo=salas');
        exit;
    }
    if ($accion === 'actualizar_sala') {
        $id_sala = $_POST['id_sala'];
        $id_mundo = $_POST['id_mundo'];
        $capacidad = intval($_POST['capacidad_sala']);
        $estado_sala = intval($_POST['estado_sala']);
        $stmt = $pdo->prepare("UPDATE sala SET id_mundo = ?, capacidad_sala = ?, estado_sala = ? WHERE id_sala = ?");
        $stmt->execute([$id_mundo, $capacidad, $estado_sala, $id_sala]);
        header('Location: ?modulo=salas');
        exit;
    }
}
if (isset($_GET['eliminar']) && isset($_GET['tipo'])) {
    $id = $_GET['eliminar'];
    $tipo = $_GET['tipo'];
    if ($tipo === 'usuario') $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    if ($tipo === 'arma') $stmt = $pdo->prepare("DELETE FROM arma WHERE id_arma = ?");
    if ($tipo === 'mundo') $stmt = $pdo->prepare("DELETE FROM mundo WHERE id_mundo = ?");
    if ($tipo === 'personaje') $stmt = $pdo->prepare("DELETE FROM personaje WHERE id_personaje = ?");
    if ($tipo === 'avatar') $stmt = $pdo->prepare("DELETE FROM avatar_usuario WHERE id_avatar = ?");
    if ($tipo === 'sala') $stmt = $pdo->prepare("DELETE FROM sala WHERE id_sala = ?");

    if ($tipo === 'partida_completa') {
        try {
            $pdo->beginTransaction();
            $stmtDelDet = $pdo->prepare("DELETE FROM detalle_partida_usuario WHERE id_partida = ?");
            $stmtDelDet->execute([$id]);

            $stmtDelPart = $pdo->prepare("DELETE FROM partida WHERE id_partida = ?");
            $stmtDelPart->execute([$id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
        header("Location: ?modulo=" . $_GET['origen']);
        exit;
    }

    if (isset($stmt)) {
        $stmt->execute([$id]);
        header("Location: ?modulo=" . $_GET['origen']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard admin</title>
    <link rel="stylesheet" href="../assets/estilos/style_dashadmin.css">
</head>

<body>
    <div class="sidebar">
        <h2>Panel de Control</h2>
        <a href="?modulo=dashboard" class="<?= $modulo === 'dashboard' ? 'active' : '' ?>">Inicio</a>
        <a href="?modulo=reportes" class="<?= $modulo === 'reportes' ? 'active' : '' ?>">Reportes</a>
        <a href="?modulo=usuarios" class="<?= $modulo === 'usuarios' ? 'active' : '' ?>">Gestionar Usuarios</a>
        <a href="?modulo=armas" class="<?= $modulo === 'armas' ? 'active' : '' ?>">Gestionar Armas</a>
        <a href="?modulo=personajes" class="<?= $modulo === 'personajes' ? 'active' : '' ?>">Gestionar Personajes</a>
        <a href="?modulo=mundos" class="<?= $modulo === 'mundos' ? 'active' : '' ?>">Gestionar Mundos</a>
        <a href="?modulo=salas" class="<?= $modulo === 'salas' ? 'active' : '' ?>">Gestionar Salas</a>
        <a href="?modulo=avatars" class="<?= $modulo === 'avatars' ? 'active' : '' ?>">Gestionar Avatars</a>
        <a href="../player/inicio.php">Cuartel general</a>
        <a href="../includes/logout.php" class="btn-logout" onclick="return confirm('¿Estás seguro de cerrar sesión?')">Cerrar Sesión</a>
    </div>
    <div class="content">
        <?php if ($modulo === 'dashboard'):
            $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
            $totalArmas = $pdo->query("SELECT COUNT(*) FROM arma")->fetchColumn();
            $totalMundos = $pdo->query("SELECT COUNT(*) FROM mundo")->fetchColumn();
            $totalPersonajes = $pdo->query("SELECT COUNT(*) FROM personaje")->fetchColumn();
            $usuariosActivos = $pdo->query("SELECT COUNT(*) FROM usuario WHERE estado_usuario = 1")->fetchColumn();
            $usuariosInactivos = $pdo->query("SELECT COUNT(*) FROM usuario WHERE estado_usuario = 0")->fetchColumn();
        ?>
            <h1>Bienvenido al Sistema</h1>
            <p>Selecciona una opción en el menú lateral para comenzar a gestionar los registros de tu juego.</p>
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Jugadores</h3>
                    <p class="card-number"><?= $totalUsuarios ?></p>
                </div>
                <div class="card">
                    <h3>Jugadores Activos</h3>
                    <p class="card-number" style="color: #4CAF50;"><?= $usuariosActivos ?></p>
                </div>
                <div class="card">
                    <h3>Jugadores Inactivos</h3>
                    <p class="card-number" style="color: #ff3333;"><?= $usuariosInactivos ?></p>
                </div>
                <div class="card">
                    <h3>Armas Registradas</h3>
                    <p class="card-number"><?= $totalArmas ?></p>
                </div>
                <div class="card">
                    <h3>Mundos Disponibles</h3>
                    <p class="card-number"><?= $totalMundos ?></p>
                </div>
                <div class="card">
                    <h3>Personajes Creados</h3>
                    <p class="card-number"><?= $totalPersonajes ?></p>
                </div>
            </div>
        <?php elseif ($modulo === 'reportes'):
            $stmtReporte = $pdo->query("SELECT 
            d.id_partida,
            p.fecha_partida,
            GROUP_CONCAT(u.nombre_usuario ORDER BY d.id_detalle SEPARATOR '<br>') AS jugadores,
            GROUP_CONCAT(d.puntuacion_total ORDER BY d.id_detalle SEPARATOR '<br>') AS puntuaciones,
            GROUP_CONCAT(d.ataques_realizados ORDER BY d.id_detalle SEPARATOR '<br>') AS ataques,
            GROUP_CONCAT(d.dano_total_realizado ORDER BY d.id_detalle SEPARATOR '<br>') AS danos,
            GROUP_CONCAT(d.disparos_acertados ORDER BY d.id_detalle SEPARATOR '<br>') AS aciertos,
            GROUP_CONCAT(d.disparos_fallados ORDER BY d.id_detalle SEPARATOR '<br>') AS fallos,
            GROUP_CONCAT(d.vida_restante ORDER BY d.id_detalle SEPARATOR '<br>') AS vidas,
            MAX(IF(d.ganador = 1, u.nombre_usuario, NULL)) AS ganador_nombre
            FROM detalle_partida_usuario d 
            LEFT JOIN partida p ON d.id_partida = p.id_partida
            LEFT JOIN usuario u ON d.id_usuario = u.id_usuario 
            GROUP BY d.id_partida, p.fecha_partida
            ORDER BY d.id_partida DESC");
            $reportes_partidas = $stmtReporte->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h2>Reporte e Historial de Partidas</h2>
            <p style="color: #aaa; font-size: 13px; margin-bottom: 25px;">Historial agrupado por partida con visualización completa de todos los campos de la tabla de detalles.</p>
            <div style="width: 100%; overflow-x: auto;">
                <h3 style="color: #f1c40f; border-bottom: 1px solid #444; padding-bottom: 8px; margin-top: 0;">Consolidado de Partidas</h3>
                <table style="width: 100%; white-space: nowrap;">
                    <tr>
                        <th>ID Partida</th>
                        <th>Fecha</th>
                        <th>Jugadores</th>
                        <th>Puntuaciones</th>
                        <th>Ganador</th>
                        <th>Ataques realizados</th>
                        <th>Daño realizado</th>
                        <th>Aciertos</th>
                        <th>Fallos</th>
                        <th>Vida restante</th>
                    </tr>
                    <?php if (!empty($reportes_partidas)): ?>
                        <?php foreach ($reportes_partidas as $rp): ?>
                            <tr>
                                <td style="text-align: center;"><b>#<?= $rp['id_partida'] ?></b></td>
                                <td style="text-align: center; color: #ddd; font-size: 12px;"><?= $rp['fecha_partida'] ? htmlspecialchars($rp['fecha_partida']) : '-' ?></td>
                                <td style="text-align: center;"><?= $rp['jugadores'] ? $rp['jugadores'] : '<span style="color: #777;">Sin jugadores</span>' ?></td>
                                <td style="text-align: center;"><?= $rp['puntuaciones'] ?? '-' ?></td>
                                <td style="text-align: center; font-weight: bold; color: #2ecc71;"><?= $rp['ganador_nombre'] ? htmlspecialchars($rp['ganador_nombre']) : '-' ?></td>
                                <td style="text-align: center;"><?= $rp['ataques'] ?? '-' ?></td>
                                <td style="text-align: center;"><?= $rp['danos'] ?? '-' ?></td>
                                <td style="text-align: center;"><?= $rp['aciertos'] ?? '-' ?></td>
                                <td style="text-align: center;"><?= $rp['fallos'] ?? '-' ?></td>
                                <td style="text-align: center;"><?= $rp['vidas'] ?? '-' ?></td>
                                <td style="text-align: center;"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center; color: #777; padding: 20px;">No hay registros históricos de partidas en este momento.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php elseif ($modulo === 'usuarios'):
            $usuarios = $pdo->query("SELECT u.*, r.nombre_rol, n.nivel FROM usuario u LEFT JOIN rol r ON u.id_rol=r.id_rol LEFT JOIN nivel n ON u.id_nivel=n.id_nivel")->fetchAll(PDO::FETCH_ASSOC);
            $roles = $pdo->query("SELECT * FROM rol")->fetchAll(PDO::FETCH_ASSOC);
            $niveles = $pdo->query("SELECT * FROM nivel")->fetchAll(PDO::FETCH_ASSOC);

            $editando = null;
            if (isset($_GET['editar'])) {
                $stmtEdit = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
                $stmtEdit->execute([$_GET['editar']]);
                $editando = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            }
        ?>
            <h2>Gestión de Usuarios</h2>
            <div class="modulo-container">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar_usuario' : 'crear_usuario' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_usuario" value="<?= $editando['id_usuario'] ?>">
                    <?php endif; ?>
                    <label>Nombre:</label>
                    <input type="text" name="nombre_usuario" value="<?= htmlspecialchars($editando['nombre_usuario'] ?? '') ?>" required>
                    <label>Correo:</label>
                    <input type="email" name="correo_usuario" value="<?= htmlspecialchars($editando['correo_usuario'] ?? '') ?>">
                    <label>Contraseña <?= $editando ? '(Dejar en blanco para mantener)' : '' ?></label>
                    <input type="password" name="password" <?= $editando ? '' : 'required' ?>>
                    <label>Estado usuario:</label>
                    <select name="estado_usuario" required>
                        <option value="">Sin estado asignado</option>
                        <option value="1" <?= ($editando && (int)$editando['estado_usuario'] === 1) ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($editando && (int)$editando['estado_usuario'] === 0) ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                    <label>Rol:</label>
                    <select name="id_rol" required>
                        <option value="">Sin rol asignado</option>
                        <?php foreach ($roles as $ro): ?>
                            <option value="<?= $ro['id_rol'] ?>" <?= ($editando && $editando['id_rol'] == $ro['id_rol']) ? 'selected' : '' ?>><?= htmlspecialchars($ro['nombre_rol']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Nivel:</label>
                    <select name="id_nivel" required>
                        <option value="">Sin nivel asignado</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= $n['id_nivel'] ?>" <?= ($editando && $editando['id_nivel'] == $n['id_nivel']) ? 'selected' : '' ?>><?= htmlspecialchars($n['nivel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="grupo-botones">
                        <button type="submit"><?= $editando ? 'Actualizar Usuario' : 'Registrar Usuario' ?></button>
                        <?php if ($editando): ?>
                            <a href="?modulo=usuarios" class="btn-eliminar" style="margin-top:0; padding: 12px; text-align: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Rol</th>
                            <th>Nivel</th>
                            <th>Exp</th>
                            <th>Conexión</th>
                            <th>Acciones</th>
                        </tr>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= $u['id_usuario'] ?></td>
                                <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                                <td><?= htmlspecialchars($u['correo_usuario']) ?></td>
                                <td><?= ((int)$u['estado_usuario'] === 1) ? 'Activo' : 'Inactivo' ?></td>
                                <td><?= $u['nombre_rol'] ?></td>
                                <td><?= $u['nivel'] ?></td>
                                <td><?= $u['experiencia'] ?></td>
                                <td><?= htmlspecialchars($u['ultima_conexion'] ?? 'Nunca') ?></td>
                                <td>
                                    <div class="grupo-botones">
                                        <a href="?modulo=usuarios&editar=<?= $u['id_usuario'] ?>" class="btn-editar">Editar</a>
                                        <a href="?modulo=usuarios&eliminar=<?= $u['id_usuario'] ?>&tipo=usuario&origen=usuarios" class="btn-eliminar" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php elseif ($modulo === 'armas'):
            $armas = $pdo->query("SELECT a.*, c.nombre_clase FROM arma a LEFT JOIN clase_arma c ON a.clase_arma = c.id_clase")->fetchAll(PDO::FETCH_ASSOC);
            $niveles = $pdo->query("SELECT * FROM nivel")->fetchAll(PDO::FETCH_ASSOC);
            $clases = $pdo->query("SELECT * FROM clase_arma")->fetchAll(PDO::FETCH_ASSOC);

            $editando = null;
            if (isset($_GET['editar'])) {
                $stmtEdit = $pdo->prepare("SELECT * FROM arma WHERE id_arma = ?");
                $stmtEdit->execute([$_GET['editar']]);
                $editando = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            }
        ?>
            <h2>Gestión de Armas</h2>
            <div class="modulo-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar_arma' : 'crear_arma' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_arma" value="<?= $editando['id_arma'] ?>">
                        <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($editando['imagen_arma']) ?>">
                    <?php endif; ?>

                    <label>Clase del arma</label>
                    <select name="clase_arma" required>
                        <option value="">Sin clase asignada</option>
                        <?php foreach ($clases as $c): ?>
                            <option value="<?= $c['id_clase'] ?>" <?= ($editando && $editando['clase_arma'] == $c['id_clase']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre_clase']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Nombre del Arma:</label>
                    <input type="text" name="nombre_arma" value="<?= htmlspecialchars($editando['nombre_arma'] ?? '') ?>" required>
                    <label>Capacidad de municion:</label>
                    <input type="number" name="capacidad_municion" value="<?= htmlspecialchars($editando['capacidad_municion'] ?? '') ?>" required>
                    <label>Daño:</label>
                    <input type="number" name="dano" value="<?= htmlspecialchars($editando['dano'] ?? '') ?>" required>
                    <label>Nivel requerido:</label>
                    <select name="nivel_requerido" required>
                        <option value="">Sin nivel asignado</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= $n['id_nivel'] ?>" <?= ($editando && $editando['nivel_requerido'] == $n['id_nivel']) ? 'selected' : '' ?>><?= htmlspecialchars($n['nivel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Imagen del arma:</label>
                    <input type="file" name="imagen_arma" <?= $editando ? '' : 'required' ?>>
                    <div class="grupo-botones">
                        <button type="submit"><?= $editando ? 'Actualizar Arma' : 'Registrar Arma' ?></button>
                        <?php if ($editando): ?>
                            <a href="?modulo=armas" class="btn-eliminar" style="margin-top:0; padding: 12px; text-align: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Clase</th>
                            <th>Nombre</th>
                            <th>Cargador</th>
                            <th>Daño</th>
                            <th>Nivel</th>
                            <th>Imagen</th>
                            <th>Acciones</th>
                        </tr>
                        <?php foreach ($armas as $a): ?>
                            <tr>
                                <td><?= $a['id_arma'] ?></td>
                                <td><?= htmlspecialchars($a['nombre_clase']) ?></td>
                                <td><?= htmlspecialchars($a['nombre_arma']) ?></td>
                                <td><?= $a['capacidad_municion'] ?></td>
                                <td><?= $a['dano'] ?></td>
                                <td><?= $a['nivel_requerido'] ?></td>
                                <td>
                                    <?php if (!empty($a['imagen_arma'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($a['imagen_arma']) ?>" width="50">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="grupo-botones">
                                        <a href="?modulo=armas&editar=<?= $a['id_arma'] ?>" class="btn-editar">Editar</a>
                                        <a href="?modulo=armas&eliminar=<?= $a['id_arma'] ?>&tipo=arma&origen=armas" class="btn-eliminar" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php elseif ($modulo === 'mundos'):
            $mundos = $pdo->query("SELECT * FROM mundo")->fetchAll(PDO::FETCH_ASSOC);
            $niveles = $pdo->query("SELECT * FROM nivel")->fetchAll(PDO::FETCH_ASSOC);

            $editando = null;
            if (isset($_GET['editar'])) {
                $stmtEdit = $pdo->prepare("SELECT * FROM mundo WHERE id_mundo = ?");
                $stmtEdit->execute([$_GET['editar']]);
                $editando = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            }
        ?>
            <h2>Gestión de Mundos</h2>
            <div class="modulo-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar_mundo' : 'crear_mundo' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_mundo" value="<?= $editando['id_mundo'] ?>">
                        <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($editando['imagen_mundo']) ?>">
                    <?php endif; ?>
                    <label>Nombre del Mundo:</label>
                    <input type="text" name="mundo" value="<?= htmlspecialchars($editando['mundo'] ?? '') ?>" required>
                    <label>Nivel requerido:</label>
                    <select name="nivel_requerido" required>
                        <option value="">Sin nivel asignado</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= $n['id_nivel'] ?>" <?= ($editando && $editando['nivel_requerido'] == $n['id_nivel']) ? 'selected' : '' ?>><?= htmlspecialchars($n['nivel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Imagen</label>
                    <input type="file" name="imagen_mundo" <?= $editando ? '' : 'required' ?>>
                    <div class="grupo-botones">
                        <button type="submit"><?= $editando ? 'Actualizar Mundo' : 'Registrar Mundo' ?></button>
                        <?php if ($editando): ?>
                            <a href="?modulo=mundos" class="btn-eliminar" style="margin-top:0; padding: 12px; text-align: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Nivel req.</th>
                            <th>Imagen</th>
                            <th>Acciones</th>
                        </tr>
                        <?php foreach ($mundos as $m): ?>
                            <tr>
                                <td><?= $m['id_mundo'] ?></td>
                                <td><?= htmlspecialchars($m['mundo']) ?></td>
                                <td><?= htmlspecialchars($m['nivel_requerido']) ?></td>
                                <td>
                                    <?php if (!empty($m['imagen_mundo'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($m['imagen_mundo']) ?>" width="50">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="grupo-botones">
                                        <a href="?modulo=mundos&editar=<?= $m['id_mundo'] ?>" class="btn-editar">Editar</a>
                                        <a href="?modulo=mundos&eliminar=<?= $m['id_mundo'] ?>&tipo=mundo&origen=mundos" class="btn-eliminar" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php elseif ($modulo === 'personajes'):
            $personajes = $pdo->query("SELECT p.*, n.nivel, f.nombre_faccion FROM personaje p LEFT JOIN nivel n ON p.nivel_requerido = n.id_nivel LEFT JOIN facciones f ON f.id_faccion = p.faccion")->fetchAll(PDO::FETCH_ASSOC);
            $niveles = $pdo->query("SELECT * FROM nivel")->fetchAll(PDO::FETCH_ASSOC);
            $facciones = $pdo->query("SELECT * FROM facciones")->fetchAll(PDO::FETCH_ASSOC);

            $editando = null;
            if (isset($_GET['editar'])) {
                $stmtEdit = $pdo->prepare("SELECT * FROM personaje WHERE id_personaje = ?");
                $stmtEdit->execute([$_GET['editar']]);
                $editando = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            }
        ?>
            <h2>Gestión de Personajes</h2>
            <div class="modulo-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar_personaje' : 'crear_personaje' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_personaje" value="<?= $editando['id_personaje'] ?>">
                        <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($editando['imagen']) ?>">
                    <?php endif; ?>
                    <label>Nombre del personaje:</label>
                    <input type="text" name="nombre_personaje" value="<?= htmlspecialchars($editando['nombre_personaje'] ?? '') ?>" required>
                    <label>Vida del personaje:</label>
                    <input type="number" name="vida_personaje" value="<?= htmlspecialchars($editando['vida_personaje'] ?? '') ?>" placeholder="Maximo 100" required>
                    <label>Nivel personaje</label>
                    <select name="nivel_requerido">
                        <option value="">Sin nivel asignado</option>
                        <?php foreach ($niveles as $nivel): ?>
                            <option value="<?= $nivel['id_nivel'] ?>" <?= ($editando && $editando['nivel_requerido'] == $nivel['id_nivel']) ? 'selected' : '' ?>><?= htmlspecialchars($nivel['nivel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Facción del Personaje:</label>
                    <select name="faccion">
                        <option value="">Sin facción asignada</option>
                        <?php foreach ($facciones as $fa): ?>
                            <option value="<?= $fa['id_faccion'] ?>" <?= ($editando && $editando['faccion'] == $fa['id_faccion']) ? 'selected' : '' ?>><?= htmlspecialchars($fa['nombre_faccion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Imagen del personaje</label>
                    <input type="file" name="imagen">
                    <div class="grupo-botones">
                        <button type="submit"><?= $editando ? 'Actualizar personaje' : 'Registrar personaje' ?></button>
                        <?php if ($editando): ?>
                            <a href="?modulo=personajes" class="btn-eliminar" style="margin-top:0; padding: 12px; text-align: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Facción</th>
                            <th>Vida</th>
                            <th>Nivel</th>
                            <th>Imagen</th>
                            <th>Acciones</th>
                        </tr>
                        <?php foreach ($personajes as $per): ?>
                            <tr>
                                <td><?= $per['id_personaje'] ?></td>
                                <td><?= htmlspecialchars($per['nombre_personaje']) ?></td>
                                <td><?= htmlspecialchars($per['nombre_faccion']) ?></td>
                                <td><?= htmlspecialchars($per['vida_personaje']) ?></td>
                                <td><?= htmlspecialchars($per['nivel']) ?></td>
                                <td>
                                    <?php if (!empty($per['imagen'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($per['imagen']) ?>" width="50">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="grupo-botones">
                                        <a href="?modulo=personajes&editar=<?= $per['id_personaje'] ?>" class="btn-editar">Editar</a>
                                        <a href="?modulo=personajes&eliminar=<?= $per['id_personaje'] ?>&tipo=personaje&origen=personajes" class="btn-eliminar" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php elseif ($modulo === 'avatars'):
            $avatars = $pdo->query("SELECT a.*, f.nombre_faccion FROM avatar_usuario a LEFT JOIN facciones f ON a.faccion = f.id_faccion")->fetchAll(PDO::FETCH_ASSOC);
            $facciones = $pdo->query("SELECT * FROM facciones")->fetchAll(PDO::FETCH_ASSOC);
            $editando = null;

            if (isset($_GET['editar'])) {
                $stmtEdit = $pdo->prepare("SELECT * FROM avatar_usuario WHERE id_avatar = ?");
                $stmtEdit->execute([$_GET['editar']]);
                $editando = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            }
        ?>
            <h2>Gestión de Avatars</h2>
            <div class="modulo-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar_avatar' : 'crear_avatar' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_avatar" value="<?= $editando['id_avatar'] ?>">
                        <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($editando['imagen_avatar']) ?>">
                    <?php endif; ?>
                    <label>Nombre del Avatar:</label>
                    <input type="text" name="nombre_avatar" value="<?= htmlspecialchars($editando['nombre_avatar'] ?? '') ?>" required>
                    <label>Facción del Avatar:</label>
                    <select name="faccion">
                        <option value="">Sin facción asignada</option>
                        <?php foreach ($facciones as $av): ?>
                            <option value="<?= $av['id_faccion'] ?>" <?= ($editando && $editando['faccion'] == $av['id_faccion']) ? 'selected' : '' ?>><?= htmlspecialchars($av['nombre_faccion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Imagen</label>
                    <input type="file" name="imagen_avatar" <?= $editando ? '' : 'required' ?>>
                    <div class="grupo-botones">
                        <button type="submit"><?= $editando ? 'Actualizar Avatar' : 'Registrar Avatar' ?></button>
                        <?php if ($editando): ?>
                            <a href="?modulo=avatars" class="btn-eliminar" style="margin-top:0; padding: 12px; text-align: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Facción</th>
                            <th>Imagen</th>
                            <th>Acciones</th>
                        </tr>
                        <?php foreach ($avatars as $av): ?>
                            <tr>
                                <td><?= $av['id_avatar'] ?></td>
                                <td><?= htmlspecialchars($av['nombre_avatar']) ?></td>
                                <td><?= htmlspecialchars($av['nombre_faccion']) ?></td>
                                <td>
                                    <?php if (!empty($av['imagen_avatar'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($av['imagen_avatar']) ?>" width="50">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="grupo-botones">
                                        <a href="?modulo=avatars&editar=<?= $av['id_avatar'] ?>" class="btn-editar">Editar</a>
                                        <a href="?modulo=avatars&eliminar=<?= $av['id_avatar'] ?>&tipo=avatar&origen=avatars" class="btn-eliminar" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php elseif ($modulo === 'salas'):
            $salas = $pdo->query("SELECT s.*, m.mundo FROM sala s LEFT JOIN mundo m ON s.id_mundo = m.id_mundo")->fetchAll(PDO::FETCH_ASSOC);
            $mundos = $pdo->query("SELECT id_mundo, mundo FROM mundo")->fetchAll(PDO::FETCH_ASSOC);

            $editando = null;
            if (isset($_GET['editar'])) {
                $stmtEdit = $pdo->prepare("SELECT * FROM sala WHERE id_sala = ?");
                $stmtEdit->execute([$_GET['editar']]);
                $editando = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            }
        ?>
            <h2>Gestión de Salas</h2>
            <div class="modulo-container">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar_sala' : 'crear_sala' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_sala" value="<?= $editando['id_sala'] ?>">
                    <?php endif; ?>
                    <label>Mundo:</label>
                    <select name="id_mundo" required>
                        <option value="">Seleccione un mundo</option>
                        <?php foreach ($mundos as $mun): ?>
                            <option value="<?= $mun['id_mundo'] ?>" <?= ($editando && $editando['id_mundo'] == $mun['id_mundo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mun['mundo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Nombre de la sala:</label>
                    <input type="text" name="nombre_sala" value="<?= htmlspecialchars($editando['nombre_sala'] ?? '') ?>">
                    <label>Capacidad Máxima (Jugadores):</label>
                    <input type="number" name="capacidad_sala" min="1" value="<?= htmlspecialchars($editando['capacidad_sala'] ?? '5') ?>" required>

                    <label>Estado de la Sala:</label>
                    <select name="estado_sala" required>
                        <option value="1" <?= ($editando && (int)$editando['estado_sala'] === 1) ? 'selected' : '' ?>>Activa</option>
                        <option value="0" <?= ($editando && (int)$editando['estado_sala'] === 0) ? 'selected' : '' ?>>Inactiva</option>
                    </select>

                    <div class="grupo-botones">
                        <button type="submit"><?= $editando ? 'Actualizar Sala' : 'Crear Sala' ?></button>
                        <?php if ($editando): ?>
                            <a href="?modulo=salas" class="btn-eliminar" style="margin-top:0; padding: 12px; text-align: center; text-decoration: none; display: block;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Mundo</th>
                            <th>Nombre sala</th>
                            <th>Capacidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                        <?php if (empty($salas)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No hay salas creadas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salas as $st): ?>
                                <tr>
                                    <td><?= $st['id_sala'] ?></td>
                                    <td><?= htmlspecialchars($st['mundo'] ?? 'Sin mundo') ?></td>
                                    <td><?= htmlspecialchars($st['nombre_sala'] ?? 'Sala #' . $st['id_sala']) ?></td>
                                    <td><?= htmlspecialchars($st['capacidad_sala']) ?></td>
                                    <td><?= ((int)$st['estado_sala'] === 1) ? 'Activa' : 'Inactiva' ?></td>
                                    <td>
                                        <div class="grupo-botones">
                                            <a href="?modulo=salas&editar=<?= $st['id_sala'] ?>" class="btn-editar">Editar</a>
                                            <a href="?modulo=salas&eliminar=<?= $st['id_sala'] ?>&tipo=sala&origen=salas" class="btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar esta sala?')">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>