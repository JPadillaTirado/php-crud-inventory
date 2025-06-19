<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: ../views/productos/producto_nuevo.php?error=Error de conexión a la base de datos');
    exit;
}

// Verificar que el formulario haya sido enviado por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/productos/producto_nuevo.php?error=Método no permitido');
    exit;
}

// Obtener y validar datos del formulario
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$categoria_id = isset($_POST['categoria']) ? intval($_POST['categoria']) : 0;
$usuario_id = isset($_POST['usuario']) ? intval($_POST['usuario']) : 0;

// Validaciones básicas
if (empty($codigo)) {
    header('Location: ../views/productos/producto_nuevo.php?error=El código del producto es obligatorio');
    exit;
}

if (empty($nombre)) {
    header('Location: ../views/productos/producto_nuevo.php?error=El nombre del producto es obligatorio');
    exit;
}

if ($precio <= 0) {
    header('Location: ../views/productos/producto_nuevo.php?error=El precio debe ser mayor que cero');
    exit;
}

if ($stock < 0) {
    header('Location: ../views/productos/producto_nuevo.php?error=El stock no puede ser negativo');
    exit;
}

if ($categoria_id <= 0) {
    header('Location: ../views/productos/producto_nuevo.php?error=Debe seleccionar una categoría válida');
    exit;
}

if ($usuario_id <= 0) {
    header('Location: ../views/productos/producto_nuevo.php?error=Debe seleccionar un usuario válido');
    exit;
}

// Verificar que el código no esté duplicado
$sql_check = "SELECT producto_id FROM producto WHERE producto_codigo = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("s", $codigo);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header('Location: ../views/productos/producto_nuevo.php?error=El código del producto ya existe');
    exit;
}

// Manejo de la imagen
$nombre_foto = '';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    // Verificar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['foto']['type'], $tipos_permitidos)) {
        header('Location: ../views/productos/producto_nuevo.php?error=Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF');
        exit;
    }
    
    // Verificar tamaño
    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) { // 2MB
        header('Location: ../views/productos/producto_nuevo.php?error=La imagen es demasiado grande. Máximo permitido: 2MB');
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
        header('Location: ../views/productos/producto_nuevo.php?error=Error al subir la imagen');
        exit;
    }
}

// Insertar producto en la base de datos
$sql = "INSERT INTO producto (producto_codigo, producto_nombre, producto_precio, producto_stock, 
                             producto_foto, categoria_id, usuario_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
// CORRECCIÓN: Cambiado 'i' a 's' para el parámetro de la foto (es un string, no un entero)
$stmt->bind_param("ssdisii", $codigo, $nombre, $precio, $stock, $nombre_foto, $categoria_id, $usuario_id);

if ($stmt->execute()) {
    // Redirigir al listado con mensaje de éxito
    header('Location: ../views/productos/producto_listar.php?mensaje=Producto agregado correctamente&tipo=exito');
    exit;
} else {
    // Si hay error al insertar y se subió una imagen, eliminarla
    if (!empty($nombre_foto) && file_exists(UPLOADS_DIR . $nombre_foto)) {
        unlink(UPLOADS_DIR . $nombre_foto);
    }
    
    // Redirigir al formulario con mensaje de error
    header('Location: ../views/productos/producto_nuevo.php?error=Error al guardar el producto: ' . $conexion->error);
    exit;
}