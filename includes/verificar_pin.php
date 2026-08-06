<?php
session_start();
include __DIR__ . '/../connect/conn.php';

if (!isset($_SESSION['email_recuperar'])) {
    header("Location: olv_contra.php");
    exit;
}

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : "";
$tipo_msg = isset($_SESSION['tipo_msg']) ? $_SESSION['tipo_msg'] : "err";
unset($_SESSION['mensaje']);
unset($_SESSION['tipo_msg']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pin = mysqli_real_escape_string($conn, $_POST['token_recuperacion']);
    $email = $_SESSION['email_recuperar'];

    $consulta = mysqli_query($conn, "SELECT token_recuperacion, token_expiracion FROM usuario WHERE correo_usuario='$email'");
    $fila = mysqli_fetch_assoc($consulta);

    if ($fila['token_recuperacion'] == $pin) {
        $ahora = date("Y-m-d H:i:s");
        if ($fila['token_expiracion'] >= $ahora) {
            $_SESSION['pin_validado'] = true;
            header("Location: cambiar_pass.php");
            exit;
        } else {
            $_SESSION['mensaje'] = "El PIN ha expirado.";
            $_SESSION['tipo_msg'] = "err";
            header("Location: verificar_pin.php");
            exit;
        }
    } else {
        $_SESSION['mensaje'] = "PIN incorrecto.";
        $_SESSION['tipo_msg'] = "err";
        header("Location: verificar_pin.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar PIN - Recuperación de contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/estilos/styles.css">
    <style>
        input[name="token_recuperacion"] {
            text-align: center;
            letter-spacing: 4px;
            font-size: 1.2rem;
        }
    </style>
</head>

<body class="login-body">
    <div class="login-wrap">
        <div class="login-card">
            <h2 style="color: orange;">Verificar PIN</h2>
            <p class="text-center mb-4" style="font-size: 0.9rem; color: #ffffff;">Ingresa el código de 8 dígitos enviado a tu correo.</p>
            <?php if ($mensaje != ""): ?>
                <div class="mensaje <?= $tipo_msg ?> mb-3 p-2 rounded text-center" style="background: #fff5f5; color: orange; border: 1px solid #feb2b2;"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <form action="verificar_pin.php" method="POST">
                <div class="mb-3">
                    <label class="form-label" style="color: orange;">PIN de recuperación</label>
                    <input type="text" name="token_recuperacion" class="form-control" placeholder="••••••••" maxlength="8" pattern="[0-9]{8}" autocomplete="one-time-code" required autofocus>
                </div>
                <button type="submit" class="btn-login" id="btnLogin">Validar PIN</button>
            </form>
            <a href="new_pin.php" style="display: block; margin-top: 15px; color: orange;">Reenviar PIN</a>
            <a href="olv_contra.php" style="display: block; margin-top: 15px; color: orange;">Cambiar correo</a>
            <a href="login.php" style="display: block; margin-top: 15px; color: orange;">Volver al login</a>
        </div>
    </div>
</body>

</html>