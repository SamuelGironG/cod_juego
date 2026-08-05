<?php
session_start();
include __DIR__ . '/../connect/conn.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['email_recuperar'])) {
    header("Location: olv_contra.php");
    exit;
}

$email = $_SESSION['email_recuperar'];

$nuevo_pin = rand(10000000, 99999999);

$expiracion = date("Y-m-d H:i:s", strtotime("+10 minutes"));

$sql = "UPDATE usuario SET token_recuperacion = '$nuevo_pin', token_expiracion = '$expiracion' WHERE correo_usuario = '$email'";
$resultado = mysqli_query($conn, $sql);

if ($resultado) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "sampruebas2013@gmail.com";
        $mail->Password   = "bxta zari rrkw sndk";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom("sampruebas2013@gmail.com", "COD-Recuperar contraseña");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Nuevo código de recuperación de contraseña";
        $mail->Body    = "
            <div style='font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; padding: 30px; background-color: #f4f6f9;'>
                <div style='max-width: 420px; margin: auto; background: #ffffff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); text-align: center;'>
                    <h2 style='font-weight: 700; color: #212529; margin-bottom: 0.5rem;'>Nuevo PIN</h2>
                    <p style='font-size: 0.9rem; color: #6c757d; margin-bottom: 1.5rem;'>Has solicitado un nuevo código de verificación.</p>
                    <div style='background: #ffffff; color: #212529; font-size: 1.4rem; font-weight: 600; padding: 0.75rem; border-radius: 8px; border: 2px solid #ffc107; letter-spacing: 6px; margin-bottom: 1.5rem;'>
                        $nuevo_pin
                    </div>
                    <p style='font-size: 0.9rem; color: #6c757d; margin-top: 15px;'>Este código expira en <strong>15 minutos</strong>.</p>
                    <p style='color: #6c757d; font-size: 0.8rem; margin-top: 20px;'>Si no solicitaste este cambio, ignora este correo.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Tu nuevo PIN de recuperación es: $nuevo_pin\n\nExpira en 15 minutos.";
        $mail->send();
        $_SESSION['mensaje'] = "Se ha reenviado un nuevo PIN a tu correo.";
        $_SESSION['tipo_msg'] = "ok";
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al enviar el correo: " . $mail->ErrorInfo;
        $_SESSION['tipo_msg'] = "err";
    }
} else {
    $_SESSION['mensaje'] = "Hubo un error al generar el nuevo PIN. Inténtalo de nuevo.";
    $_SESSION['tipo_msg'] = "err";
}

header("Location: verificar_pin.php");
exit;
?>