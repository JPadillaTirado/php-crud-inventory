<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: producto_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: producto_listar.php?mensaje=ID de producto no válido&tipo=error');
    exit;
}

$producto_id = intval($_GET['id']);

// Consultar datos del producto para mostrar información y obtener la foto
$sql = "SELECT producto_id, producto_codigo, producto_nombre, producto_foto FROM producto WHERE producto_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el producto existe
if ($resultado->num_rows === 0) {
    header('Location: producto_listar.php?mensaje=El producto no existe&tipo=error');
    exit;
}

$producto = $resultado->fetch_assoc();

// Verificar si ya se confirmó la eliminación
if (isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    
    // Almacenar la ruta de la foto antes de eliminar
    $foto = $producto['producto_foto'];
    
    // Eliminar el producto de la base de datos
    $sql_delete = "DELETE FROM producto WHERE producto_id = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $producto_id);
    
    if ($stmt_delete->execute()) {
        // Eliminar la imagen asociada si existe
        // La carpeta uploads está en la raíz del proyecto, dos niveles arriba
        if (!empty($foto) && file_exists('../../uploads/' . $foto)) {
            unlink('../../uploads/' . $foto);
        }
        
        // Redirigir al listado con mensaje de éxito
        header('Location: producto_listar.php?mensaje=Producto eliminado correctamente&tipo=exito');
        exit;
    } else {
        // Redirigir con mensaje de error
        header('Location: producto_listar.php?mensaje=Error al eliminar el producto: ' . $conexion->error . '&tipo=error');
        exit;
    }
} else {
    // Mostrar página de confirmación
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/svgviewer-output.svg">
    <title>Eliminar Producto - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Eliminar Producto</h1>
            <nav>
                <ul>
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="producto_listar.php" class="active">Productos</a></li>
                    <li><a href="../categorias/categoria_listar.php">Categorías</a></li>
                    <li><a href="../usuarios/usuario_listar.php">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="confirmacion">
            <h2>Confirmar Eliminación</h2>
            
            <div class="mensaje advertencia">
                <p>¿Está seguro que desea eliminar el siguiente producto?</p>
            </div>
            
            <div class="detalles-producto">
                <p><strong>ID:</strong> <?php echo $producto['producto_id']; ?></p>
                <p><strong>Código:</strong> <?php echo htmlspecialchars($producto['producto_codigo']); ?></p>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($producto['producto_nombre']); ?></p>
                
                <?php 
                // Verificar si la imagen existe en la carpeta uploads
                if (!empty($producto['producto_foto']) && file_exists('../../uploads/' . $producto['producto_foto'])): 
                ?>
                    <div class="imagen-producto">
                        <img src="../../uploads/<?php echo $producto['producto_foto']; ?>" 
                             alt="<?php echo htmlspecialchars($producto['producto_nombre']); ?>"
                             width="100">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="acciones-confirmacion">
                <a href="producto_listar.php" class="btn btn-secondary">Cancelar</a>
                <a href="producto_eliminar.php?id=<?php echo $producto_id; ?>&confirmar=1" class="btn btn-danger">Sí, Eliminar</a>
            </div>
            
            <div class="advertencia-adicional">
                <p><strong>Advertencia:</strong> Esta acción no se puede deshacer. La imagen asociada al producto también será eliminada.</p>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> - <?php echo APP_NAME; ?></p>
        </div>
    </footer>
</body>
</html>
<?php
}
?>