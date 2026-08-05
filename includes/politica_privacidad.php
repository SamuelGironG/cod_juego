<?php
// politica_privacidad.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Tratamiento de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/estilos/styles.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .policy-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #1a1a1a;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        h1, h2 {
            color: #ffc107;
        }
        h1 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            border-bottom: 2px solid #ffc107;
            padding-bottom: 10px;
        }
        h2 {
            font-size: 1.2rem;
            margin-top: 25px;
        }
        p, li {
            color: #cccccc;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        ul {
            margin-bottom: 15px;
        }
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #ffc107;
            text-decoration: underline;
            font-weight: 600;
        }
        .back-link:hover {
            color: #e0a800;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="policy-container">
            <h1>Política de Tratamiento de Datos y Términos de Uso</h1>  
            <p>En cumplimiento de la normativa aplicable de protección de datos personales, por medio del presente documento se establecen los términos y condiciones bajo los cuales se recogen, almacenan y tratan los datos personales recopilados a través de este sistema.</p>
            <h2>1. Responsable del Tratamiento</h2>
            <p>Los datos personales suministrados en el proceso de registro serán incorporados a las bases de datos del sistema, garantizando la seguridad y confidencialidad de la información de acuerdo con los estándares técnicos necesarios.</p>
            <h2>2. Finalidad del Tratamiento de Datos</h2>
            <p>La recolección y tratamiento de los datos de usuario (nombre de usuario, correo electrónico y contraseña cifrada) tienen como finalidad exclusiva:</p>
            <ul>
                <li>Gestionar el acceso, autenticación y control de roles dentro de la plataforma.</li>
                <li>Permitir la recuperación de cuentas y el envío de notificaciones esenciales relativas al servicio.</li>
                <li>Garantizar la seguridad del sistema y prevenir accesos no autorizados.</li>
            </ul>
            <h2>3. Derechos de los Titulares</h2>
            <p>Como usuario, usted tiene derecho en cualquier momento a conocer, actualizar, rectificar o solicitar la supresión de sus datos personales proporcionados en el registro.</p>
            <h2>4. Aceptación de los Términos</h2>
            <p>Al marcar la casilla de aceptación y completar el proceso de registro en la plataforma, usted manifiesta haber leído, comprendido y aceptado de forma libre e informada la presente política de tratamiento de datos y los términos de uso correspondientes.</p>
            <a href="sign_up.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver al registro</a>
        </div>
    </div>
</body>
</html>