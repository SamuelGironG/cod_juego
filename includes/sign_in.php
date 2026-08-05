<?php
session_start();
require_once '../connect/conn.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar datos asegurando que coincidan con los 'name' del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $id_rol = $_POST['id_rol'] ?? 2;
    $id_nivel = 1;
    $estado_usuario = 0;

    // Validar que se hayan llenado los campos y que se hayan aceptado los términos
    if (!empty($nombre) && !empty($correo) && !empty($pass)) {
        if (!isset($_POST['terminos'])) {
            $error_msg = "Debes aceptar la política de tratamiento de datos para registrarte.";
        } else {
            $db = new Database();
            $pdo = $db->conectar();

            $password_hash = password_hash($pass, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO usuario (nombre_usuario, correo_usuario, password, estado_usuario, id_rol, id_nivel) VALUES (?, ?, ?, ?, ?, ?)");

                $stmt->execute([$nombre, $correo, $password_hash, $estado_usuario, $id_rol, $id_nivel]);

                // Guardamos el mensaje de éxito en la sesión
                $_SESSION['success_msg'] = "¡Registro exitoso! Por favor, inicia sesión.";

                header('Location: login.php');
                exit;
            } catch (PDOException $e) {
                $error_msg = "Error al registrar: " . $e->getMessage();
            }
        }
    } else {
        $error_msg = "Por favor, completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/estilos/styles.css">
</head>

<body class="login-body">
    <div class="login-wrap">
        <div class="login-card">
            <h2>Registrarse</h2>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-warning" style="margin-bottom: 15px;">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form id="registroForm" method="POST">
                <div class="mb-3">
                    <label class="form-label" style="color: orange">Nombre de usuario</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Nombre usuario" required>
                    <div class="field-error" id="err-nombre" style="color: red; font-size: 0.8em; margin-top: 5px;"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" class="form-control" placeholder="Correo Electrónico" required>
                    <div class="field-error" id="err-correo" style="color: red; font-size: 0.8em; margin-top: 5px;"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="color: orange;">Contraseña</label>
                    <input type="password" id="contrasena" name="password" class="form-control" placeholder="Contraseña" required>
                    <div id="err-password" style="color: red; font-size: 0.8em; margin-top: 5px;"></div>
                </div>
                <!-- Checkbox de Tratamiento de Datos ordenado en una sola línea -->
                <div class="mb-3" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="terminos" class="form-check-input m-0" id="terminos" required style="flex-shrink: 0; cursor: pointer;">
                    <a href="politica_privacidad.php"  style="color: #ffc107; text-decoration: underline; font-size: 0.85rem; line-height: 1.3;">Acepto la política de tratamiento de datos y los términos de uso.</a>
                </div>

                <!-- Input oculto para el rol por defecto -->
                <input type="hidden" name="id_rol" value="2">

                <button type="submit" class="btn-login" id="btnLogin">Registrarse</button>
            </form>
            <a href="../index.php">Volver al inicio</a>
            <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
        </div>
    </div>

    <!-- Diálogo de Cookies -->
    <dialog id="cookieDialog">
        <h2 class="cookie-header">Gestión de Cookies</h2>
        <p class="cookie-body">
            Utilizamos cookies para analizar el tráfico y mejorar la experiencia de navegación. Puedes elegir si deseas aceptar todas las cookies, rechazarlas o personalizar tus preferencias.
        </p>
        <form method="dialog" class="cookie-actions">
            <button type="button" id="btnCustomize" class="btn-cookie btn-customize">Personalizar cookies</button>
            <button type="submit" id="btnDeny" value="denied" class="btn-cookie btn-deny">Rechazar</button>
            <button type="submit" id="btnAccept" value="accepted" class="btn-cookie btn-accept">Aceptar</button>
        </form>
    </dialog>

    <script src="../assets/estilos/login.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cookieDialog = document.getElementById('cookieDialog');
            const btnAccept = document.getElementById('btnAccept');
            const btnDeny = document.getElementById('btnDeny');
            const btnCustomize = document.getElementById('btnCustomize');

            const cookieConsent = sessionStorage.getItem('cookieConsent');

            if (cookieConsent !== 'accepted') {
                cookieDialog.showModal();
            } else {
                iniciarCookiesAnaliticas();
            }

            btnAccept.addEventListener('click', () => {
                sessionStorage.setItem('cookieConsent', 'accepted');
                iniciarCookiesAnaliticas();
            });

            btnDeny.addEventListener('click', (e) => {
                e.preventDefault();
                alert('Debes aceptar las cookies para continuar utilizando el sistema.');
                if (!cookieDialog.open) {
                    cookieDialog.showModal();
                }
            });

            btnCustomize.addEventListener('click', () => {
                alert('Aquí se abriría el panel de configuración detallado de cookies.');
            });

            function iniciarCookiesAnaliticas() {
                console.log('Consentimiento otorgado: Scripts de análisis inicializados.');
            }
        });
    </script>
</body>

</html>