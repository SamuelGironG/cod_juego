<?php
session_start();
include __DIR__ . '/../connect/conn.php';

if (!isset($_SESSION['pin_validado']) || !isset($_SESSION['email_recuperar'])) {
    header("Location: olv_contra.php");
    exit;
}

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : "";
$tipo_msg = isset($_SESSION['tipo_msg']) ? $_SESSION['tipo_msg'] : "";
unset($_SESSION['mensaje']);
unset($_SESSION['tipo_msg']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['password2'];
    if ($pass1 != $pass2) {
        $_SESSION['mensaje'] = "Las contraseñas no coinciden";
        $_SESSION['tipo_msg'] = "err";
        header("Location: cambiar_pass.php");
        exit;
    }
    if (strlen($pass1) < 6) {
        $_SESSION['mensaje'] = "La contraseña debe tener al menos 6 caracteres";
        $_SESSION['tipo_msg'] = "err";
        header("Location: cambiar_pass.php");
        exit;
    }
    $email = $_SESSION['email_recuperar'];
    $nuevo_hash = password_hash($pass1, PASSWORD_DEFAULT);

    mysqli_query($conn, "UPDATE usuario SET password='$nuevo_hash', token_recuperacion=NULL, token_expiracion=NULL WHERE correo_usuario='$email'");

    unset($_SESSION['email_recuperar']);
    unset($_SESSION['pin_validado']);

    $_SESSION['mensaje'] = "Contraseña actualizada correctamente. Inicie sesión.";
    $_SESSION['tipo_msg'] = "ok";
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/estilos/styles.css">
</head>
<body class="login-body">
    <div class="login-wrap">
        <div class="login-card">
            <h2>Nueva Contraseña</h2>
            <?php if ($mensaje != ""): ?>
                <div class="mensaje <?= $tipo_msg ?>"><?= $mensaje ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Nueva contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Nueva contraseña" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Confirmar contraseña</label>
                    <input type="password" name="password2" class="form-control" placeholder="Confirmar contraseña" required>
                </div>
                <button type="submit" class="btn-login" id="btnLogin">Cambiar Contraseña</button>
            </form>
            <a href="login.php" style="display: block; margin-top: 15px;">Volver al login</a>
        </div>
    </div>
</body>
</html>