<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/categorias/categoria_nuevo.php?error=Error de conexión a la base de datos');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/categorias/categoria_nuevo.php?error=Método no permitido');
    exit;
}

// Obtener y validar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';

// Validaciones básicas
if (empty($nombre)) {
    header('Location: ../views/categorias/categoria_nuevo.php?error=El nombre de la categoría es obligatorio');
    exit;
}

if (empty($ubicacion)) {
    header('Location: ../views/categorias/categoria_nuevo.php?error=La ubicación es obligatoria');
    exit;
}

// Verificar que el nombre no esté duplicado
$sql_check = "SELECT categoria_id FROM categoria WHERE categoria_nombre = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("s", $nombre);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header('Location: ../views/categorias/categoria_nuevo.php?error=Ya existe una categoría con ese nombre');
    exit;
}

// Insertar categoría en la base de datos
$sql = "INSERT INTO categoria (categoria_nombre, categoria_ubicacion) VALUES (?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $nombre, $ubicacion);

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/categorias/categoria_listar.php?mensaje=Categoría agregada correctamente&tipo=exito');
    exit;
} else {
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/categorias/categoria_nuevo.php?error=Error al guardar la categoría: ' . $conexion->error);
    exit;
}