<?php
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la terminal.\n");
}

require_once __DIR__ . '/../connect/conn.php';

if ($argc < 5) {
    echo "Uso: php crear_admin.php <email> <password> <id_tipo_user>\n";
    exit(1);
}

$nombre_usuario = $argv[1];
$correo_usuario = $argv[2];
$password = $argv[3];
$estado_usuario = $argv[4];
$id_rol = 1;
$id_nivel = 3;


if (!FILTER_VAR($correo_usuario, FILTER_VALIDATE_EMAIL)) {
    echo " Correo invalido \n";
    exit(1);
}
if (strlen($password) < 8) {
    echo " La contraseña debe tener minimo 8 caracteres. \n";
    exit(1);
}
try {
    $db = new Database();
    $pdo = $db->conectar();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE correo_usuario = ?");
    $stmt->execute([$correo_usuario]);
    if ($stmt->fetchColumn() > 0) {
        echo " El documento ya esta registrado.\n";
        exit(1);
    }
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $insert = $pdo->prepare("INSERT INTO usuario (nombre_usuario, correo_usuario, password, estado_usuario, id_rol, id_nivel) VALUES (?,?,?,?,?,?)");
    $insert->execute([$nombre_usuario, $correo_usuario, $hash, $estado_usuario, $id_rol, $id_nivel]);

    echo "Administrador creado exitosamente.\n";
    echo "usuario: $correo_usuario | ID tipo: $id_rol\n";
} catch (Exception $e) {
    echo "Error;" . $e->getMessage() . "\n";
    exit(1);
}