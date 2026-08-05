<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call of Duty</title>
    <link rel="stylesheet" href="assets/estilos/styles.css">
</head>

<body>
    <header>
        <h1 style="color: white;">Call of Duty</h1>
        <div>
            <a href="includes/login.php" class="btn-login">Iniciar Sesión</a>
            <a href="includes/sign_up.php" class="btn-login">Registrarse</a>
        </div>
    </header>
    <div class="contenedor-video">
        <video autoplay muted loop id="video-fondo">
            <source src="assets/img/Video Project 9.mp4" type="video/mp4">
        </video>
    </div>
    <main>
        <a href="includes/sign_up.php" class="btn-jugar">Jugar ahora</a>
        <button id="btn-audio" onclick="desmutear()">Activar Audio</button>
    </main>
    <footer>
        <p style="color: white;">&copy; 2026 Activision Publishing, Inc. Todos los derechos reservados.</p>
    </footer>
    
    <?php include 'includes/cookies.html'; ?>
    <script src="assets/estilos/scrip_index.js"></script>
</body>

</html>