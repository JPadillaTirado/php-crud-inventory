<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/categorias/categoria_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/categorias/categoria_listar.php?mensaje=Método no permitido&tipo=error');
    exit;
}

// Obtener y validar el ID de la categoría
if (!isset($_POST['categoria_id']) || !is_numeric($_POST['categoria_id'])) {
    header('Location: ../views/categorias/categoria_listar.php?mensaje=ID de categoría no válido&tipo=error');
    exit;
}

$categoria_id = intval($_POST['categoria_id']);

// Obtener y validar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';

// Validaciones básicas
if (empty($nombre)) {
    header('Location: ../views/categorias/categoria_editar.php?id=' . $categoria_id . '&error=El nombre de la categoría es obligatorio');
    exit;
}

if (empty($ubicacion)) {
    header('Location: ../views/categorias/categoria_editar.php?id=' . $categoria_id . '&error=La ubicación es obligatoria');
    exit;
}

// Verificar que el nombre no esté duplicado (excluyendo la categoría actual)
$sql_check = "SELECT categoria_id FROM categoria WHERE categoria_nombre = ? AND categoria_id != ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("si", $nombre, $categoria_id);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header('Location: ../views/categorias/categoria_editar.php?id=' . $categoria_id . '&error=Ya existe otra categoría con ese nombre');
    exit;
}

// Actualizar categoría en la base de datos
$sql = "UPDATE categoria SET categoria_nombre = ?, categoria_ubicacion = ? WHERE categoria_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssi", $nombre, $ubicacion, $categoria_id);

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/categorias/categoria_listar.php?mensaje=Categoría actualizada correctamente&tipo=exito');
    exit;
} else {
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/categorias/categoria_editar.php?id=' . $categoria_id . '&error=Error al actualizar la categoría: ' . $conexion->error);
    exit;
}