<?php
$host = "localhost";
$usuario = "root";
$password = "";
$basedatos = "juego";
$conn = mysqli_connect($host, $usuario, $password, $basedatos);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>