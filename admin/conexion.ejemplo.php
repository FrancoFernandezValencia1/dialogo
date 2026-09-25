<?php
// Plantilla de configuración de la Base de Datos
$host     = "localhost";
$usuario  = "root";
$password = "";
$database = "dialogoydesarrollo";

// Crear la conexión usando la extensión mysqli
$conexion = new mysqli($host, $usuario, $password, $database);

// Comprobar si hubo un error en la conexión
if ($conexion->connect_error) {
    die("Error crítico de conexión: " . $conexion->connect_error);
}

// Configurar la codificación UTF-8
$conexion->set_charset("utf8mb4");
?>
