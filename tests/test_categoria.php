<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php';

// Prueba simple de consulta a la tabla categoría
echo "Intentando consultar la tabla categoría...<br>";
$sql = "SELECT * FROM categoria LIMIT 1";
$resultado = $conexion->query($sql);

if ($resultado) {
    echo "Consulta exitosa. ";
    if ($resultado->num_rows > 0) {
        $categoria = $resultado->fetch_assoc();
        echo "Primera categoría: " . $categoria['categoria_nombre'];
    } else {
        echo "No hay categorías en la base de datos.";
    }
} else {
    echo "Error en la consulta: " . $conexion->error;
}
?>