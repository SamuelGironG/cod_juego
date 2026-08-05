<?php
session_start();

include __DIR__ . '/../connect/conn.php';


require '../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$smtp_host    = "smtp.gmail.com";
$smtp_usuario = "sampruebas2013@gmail.com"; 
$smtp_password = "bxta zari rrkw sndk";      
$smtp_puerto   = 587;
$smtp_nombre   = "COD-Recuperar contraseña";          

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : "";
$tipo_msg = isset($_SESSION['tipo_msg']) ? $_SESSION['tipo_msg'] : "";
unset($_SESSION['mensaje']);
unset($_SESSION['tipo_msg']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['correo_usuario']);
    $consulta = mysqli_query($conn, "SELECT id_usuario FROM usuario WHERE correo_usuario='$email'");

    if (mysqli_num_rows($consulta) == 1) {
        $pin = rand(10000000, 99999999);
        $expira = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        mysqli_query($conn, "UPDATE usuario SET token_recuperacion='$pin', token_expiracion='$expira' WHERE correo_usuario='$email'");

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_usuario;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtp_puerto;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp_usuario, $smtp_nombre);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Código de recuperación de contraseña";
            $mail->Body    = "
                <div style='font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; padding: 30px; background-color: #f4f6f9;'>
                <div style='max-width: 420px; margin: auto; background: #ffffff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); text-align: center;'>
                    <h2 style='font-weight: 700; color: #212529; margin-bottom: 0.5rem;'>Nuevo PIN</h2>
                    <p style='font-size: 0.9rem; color: #6c757d; margin-bottom: 1.5rem;'>Has solicitado un nuevo código de verificación.</p>
                    <div style='background: #ffffff; color: #212529; font-size: 1.4rem; font-weight: 600; padding: 0.75rem; border-radius: 8px; border: 2px solid #ffc107; letter-spacing: 6px; margin-bottom: 1.5rem;'>
                        $pin
                    </div>
                    <p style='font-size: 0.9rem; color: #6c757d; margin-top: 15px;'>Este código expira en <strong>15 minutos</strong>.</p>
                    <p style='color: #6c757d; font-size: 0.8rem; margin-top: 20px;'>Si no solicitaste este cambio, ignora este correo.</p>
                </div>
            </div>
        ";
            $mail->AltBody = "Tu PIN de recuperación es: $pin\n\nExpira en 15 minutos.";
            $mail->send();

            $_SESSION['mensaje'] = "PIN enviado correctamente a $email. Revisa tu bandeja de entrada (y spam).";
            $_SESSION['tipo_msg'] = "ok";
            $_SESSION['email_recuperar'] = $email;
            header("Location: verificar_pin.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al enviar el correo: " . $mail->ErrorInfo;
            $_SESSION['tipo_msg'] = "err";
            header("Location: recuperar_con_email.php");
            exit;
        }
    } else {
        $_SESSION['mensaje'] = "El email no está registrado";
        $_SESSION['tipo_msg'] = "err";
        header("Location: recuperar_con_email.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/estilos/styles.css">
</head>
<body class="login-body">
    <div class="login-wrap">
        <div class="login-card">
            <h2 style="color: orange;">Recuperar contraseña</h2>
            <?php if ($mensaje != "" && $tipo_msg == "err"): ?>
                <div class="mensaje <?= $tipo_msg ?> mb-3 p-2 rounded text-center" style="background: #fff5f5; color: #e53e3e; border: 1px solid #feb2b2;"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Correo electrónico</label>
                    <input type="email" name="correo_usuario" class="form-control" placeholder="Correo electrónico" required>
                </div>
                <button type="submit" class="btn-login" id="btnLogin">Enviar enlace</button>
            </form>
            <a href="login.php" style="display: block; margin-top: 15px; color: orange;">Volver al login</a>
        </div>
    </div>
</body>
</html>