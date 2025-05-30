<?php
// ==============================================
// ARCHIVO DE CONEXIÓN DE EJEMPLO
// ==============================================
// Copia este archivo como conexion.php después de configurar config.php
// IMPORTANTE: conexion.php no debe subirse a Git por seguridad

// Incluir el archivo de configuración
require_once __DIR__ . '/config.php';

// Crear la conexión usando las constantes definidas en config.php
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar la conexión
if ($conexion->connect_error) {
    die('Error al conectar a la base de datos: ' . $conexion->connect_error);
}

// Establecer el charset usando la configuración
$conexion->set_charset(DB_CHARSET);

// Opcional: Configurar el timezone de MySQL para que coincida con PHP
// $conexion->query("SET time_zone = '-05:00'"); // Ajusta según tu zona horaria
?>