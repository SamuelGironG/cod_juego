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
        $stmtInactivar = $pdo->prepare("UPDATE detalle_partida_usuario SET activo = 0 WHERE id_usuario = ? AND id_partida = ?");
        $stmtInactivar->execute([$id_usuario, $id_partida]);

        $stmtSala = $pdo->prepare("SELECT id_sala FROM partida WHERE id_partida = ?");
        $stmtSala->execute([$id_partida]);
        $id_sala = $stmtSala->fetchColumn();

        $stmtContar = $pdo->prepare("SELECT COUNT(*) FROM detalle_partida_usuario WHERE id_partida = ? AND activo = 1");
        $stmtContar->execute([$id_partida]);
        $jugadores_restantes = $stmtContar->fetchColumn();

        if ($jugadores_restantes == 0) {
        }

    } catch (PDOException $e) {
    }
}

unset($_SESSION['id_partida_activa']);

$redireccion = isset($id_sala) ? "inicio.php?id_sala=" . $id_sala : "inicio.php";
header("Location: " . $redireccion);
exit();
?>