<?php
session_start();
require_once '../connect/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $pass = trim($_POST['password']);

    $db = new Database();
    $pdo =$db->conectar();

    $stmt =$pdo->prepare("SELECT u.* FROM usuario u WHERE u.correo_usuario = ? OR u.nombre_usuario = ? LIMIT 1");
    $stmt->execute([$correo,$correo]);
    $usuario =$stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($pass,$usuario['password'])) {

        if ((int)$usuario['estado_usuario'] === 1) {

            $stmtUpdateConexion =$pdo->prepare("UPDATE usuario SET ultima_conexion = NOW() WHERE id_usuario = ?");
            $stmtUpdateConexion->execute([$usuario['id_usuario']]);

            $_SESSION['usuario_id'] =$usuario['id_usuario'];
            $_SESSION['usuario_rol'] =$usuario['id_rol'];

            if ((int)$usuario['id_rol'] === 1) {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../player/inicio.php');
            }
            exit;
        } else {
            header('Location: login.php?error=2');
            exit;
        }
    } else {
        header('Location: login.php?error=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/estilos/styles.css">
</head>
<body class="login-body">
    <div class="login-wrap">
        <div class="login-card">
            <h2>Iniciar sesión</h2>
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success" style="margin-bottom: 15px;">
                    <?php 
                        echo $_SESSION['success_msg']; 
                        unset($_SESSION['success_msg']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-warning" style="margin-bottom: 15px;">
                    <?php echo ($_GET['error'] == 2) ? "Usuario inactivo." : "Correo, usuario o contraseña incorrectos."; ?>
                </div>
            <?php endif; ?>
            <form id="loginForm" method="POST">
                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Correo electrónico o Nombre de usuario</label>
                    <input type="text" id="correo" name="correo" class="form-control" placeholder="Correo Electrónico o Nombre de usuario" required>
                    <div class="field-error" id="err-correo" style="color: red; font-size: 0.8em; margin-top: 5px;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Contraseña</label>
                    <input type="password" id="contrasena" name="password" class="form-control" placeholder="Contraseña" required>
                    <div id="err-password" style="color: red; font-size: 0.8em; margin-top: 5px;"></div>
                </div>
                <button type="submit" class="btn-login" id="btnLogin">Entrar</button>
            </form>
            <a href="../index.php">Volver al inicio</a>
            <a href="olv_contra.php" style="font-size: 0.9em; color: orange; text-decoration: none;">¿Olvidaste tu contraseña?</a>
            <a href="sign_up.php">¿No tienes cuenta? Registrate aqui</a>
        </div>
    </div>
    <script src="../assets/estilos/login.js"></script>
</body>
</html>