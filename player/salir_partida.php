<?php
session_start();
require_once("../connect/conn.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../includes/login.php");
    exit();
}

$db = new Database();
$pdo = $db->conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_usuario = $_SESSION['usuario_id'];
$id_partida = isset($_GET['id_partida']) ? intval($_GET['id_partida']) : ($_SESSION['id_partida_activa'] ?? null);

if ($id_partida) {
    try {
        // 1. Marcar al usuario como inactivo en esta partida (o eliminar su registro en detalle)
        $stmtInactivar = $pdo->prepare("UPDATE detalle_partida_usuario SET activo = 0 WHERE id_usuario = ? AND id_partida = ?");
        $stmtInactivar->execute([$id_usuario, $id_partida]);

        // 2. Obtener el id_sala de esta partida para saber a qué sala pertenecía
        $stmtSala = $pdo->prepare("SELECT id_sala FROM partida WHERE id_partida = ?");
        $stmtSala->execute([$id_partida]);
        $id_sala = $stmtSala->fetchColumn();

        // 3. Contar cuántos jugadores activos quedan todavía en esta partida
        $stmtContar = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo = 1");
        $stmtContar->execute([$id_partida]);
        $jugadores_restantes = $stmtContar->fetchColumn();

        // 4. Si ya NO quedan jugadores, la partida queda "abandonada/archivada"
        if ($jugadores_restantes == 0) {
            // Opcional: Si agregaste una columna 'estado' o 'activa' en la tabla partida, puedes actualizarla aquí.
            // Ejemplo: UPDATE partida SET estado = 'finalizada' WHERE id_partida = ?
        }

    } catch (PDOException $e) {
        // Manejo de error silencioso o log si es necesario
    }
}

// Limpiar la variable de sesión de la partida activa
unset($_SESSION['id_partida_activa']);

// Redirigir al inicio o a la selección de salas
$redireccion = isset($id_sala) ? "inicio.php?id_sala=" . $id_sala : "inicio.php";
header("Location: " . $redireccion);
exit();
?>