<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database
{
    private $hostname = "localhost";
    private $database = "juego";
    private $username = "root";
    private $password = "";
    private $charset  = "utf8mb4";
    
    public function conectar(): PDO
    {
        try {
            $dsn = "mysql:host={$this->hostname};dbname={$this->database};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            return new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            echo '<strong>Error de Conexión:</strong> ' . $e->getMessage();
            echo '<br><strong>Código:</strong> ' . $e->getCode();
            exit;
        }
    }
}

$host = "localhost";
$usuario = "root";
$password = "";
$basedatos = "juego";

$conn = mysqli_connect($host, $usuario, $password, $basedatos);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>