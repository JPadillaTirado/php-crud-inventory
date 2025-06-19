<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/productos/producto_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/productos/producto_listar.php?mensaje=Método no permitido&tipo=error');
    exit;
}

// Obtener y validar el ID del producto
if (!isset($_POST['producto_id']) || !is_numeric($_POST['producto_id'])) {
    header('Location: ../views/productos/producto_listar.php?mensaje=ID de producto no válido&tipo=error');
    exit;
}

$producto_id = intval($_POST['producto_id']);

// Obtener y validar datos del formulario
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$categoria_id = isset($_POST['categoria']) ? intval($_POST['categoria']) : 0;
$usuario_id = isset($_POST['usuario']) ? intval($_POST['usuario']) : 0;
$foto_actual = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : '';

// Validaciones básicas
if (empty($codigo)) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=El código del producto es obligatorio');
    exit;
}

if (empty($nombre)) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=El nombre del producto es obligatorio');
    exit;
}

if ($precio <= 0) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=El precio debe ser mayor que cero');
    exit;
}

if ($stock < 0) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=El stock no puede ser negativo');
    exit;
}

if ($categoria_id <= 0) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=Debe seleccionar una categoría válida');
    exit;
}

if ($usuario_id <= 0) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=Debe seleccionar un usuario válido');
    exit;
}

// Verificar que el código no esté duplicado (excluyendo el producto actual)
$sql_check = "SELECT producto_id FROM producto WHERE producto_codigo = ? AND producto_id != ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("si", $codigo, $producto_id);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=El código del producto ya existe para otro producto');
    exit;
}

// Variable para guardar el nombre de la foto
$nombre_foto = $foto_actual;

// Manejo de la imagen si se ha subido una nueva
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    // Verificar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['foto']['type'], $tipos_permitidos)) {
        header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF');
        exit;
    }
    
    // Verificar tamaño
    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) { // 2MB
        header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=La imagen es demasiado grande. Máximo permitido: 2MB');
        exit;
    }
    
    // Crear directorio si no existe
    if (!file_exists(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nombre_foto = uniqid('prod_') . '.' . $extension;
    $ruta_destino = UPLOADS_DIR . $nombre_foto;
    
    // Mover archivo
    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
        header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=Error al subir la imagen');
        exit;
    }
    
    // Eliminar la imagen anterior si existe y es diferente
    if (!empty($foto_actual) && $foto_actual != $nombre_foto && file_exists(UPLOADS_DIR . $foto_actual)) {
        unlink(UPLOADS_DIR . $foto_actual);
    }
}

// Actualizar producto en la base de datos
$sql = "UPDATE producto 
        SET producto_codigo = ?, 
            producto_nombre = ?, 
            producto_precio = ?, 
            producto_stock = ?, 
            producto_foto = ?, 
            categoria_id = ?, 
            usuario_id = ? 
        WHERE producto_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssdisiii", $codigo, $nombre, $precio, $stock, $nombre_foto, $categoria_id, $usuario_id, $producto_id);

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/productos/producto_listar.php?mensaje=Producto actualizado correctamente&tipo=exito');
    exit;
} else {
    // Si hay error al actualizar y se subió una imagen nueva, eliminarla
    if ($nombre_foto != $foto_actual && file_exists(UPLOADS_DIR . $nombre_foto)) {
        unlink(UPLOADS_DIR . $nombre_foto);
    }
    
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/productos/producto_editar.php?id=' . $producto_id . '&error=Error al actualizar el producto: ' . $conexion->error);
    exit;
}