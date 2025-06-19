<?php
// Incluir archivos de configuración y conexión
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/config/auth.php'; // Agregar verificación de autenticación

// Verificar si la conexión está establecida
if (!isset($conexion)) {
    header('Location: usuario_listar.php?mensaje=Error de conexión a la base de datos&tipo=error');
    exit;
}

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: usuario_listar.php?mensaje=ID de usuario no válido&tipo=error');
    exit;
}

$usuario_id = intval($_GET['id']);

// Consultar datos del usuario para mostrar información
$sql = "SELECT usuario_id, usuario_nombre, usuario_apellido, usuario_usuario, usuario_email FROM usuario WHERE usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el usuario existe
if ($resultado->num_rows === 0) {
    header('Location: usuario_listar.php?mensaje=El usuario no existe&tipo=error');
    exit;
}

$usuario = $resultado->fetch_assoc();

// Verificar si tiene productos asociados - IMPORTANTE para la integridad referencial
$sql_productos = "SELECT COUNT(*) as total FROM producto WHERE usuario_id = ?";
$stmt_productos = $conexion->prepare($sql_productos);
$stmt_productos->bind_param("i", $usuario_id);
$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();
$total_productos = $resultado_productos->fetch_assoc()['total'];

// Verificar si ya se confirmó la eliminación
if (isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    
    // Verificar si hay productos asociados
    if ($total_productos > 0) {
        header('Location: usuario_listar.php?mensaje=No se puede eliminar el usuario porque tiene productos asociados&tipo=error');
        exit;
    }
    
    // Eliminar el usuario de la base de datos
    $sql_delete = "DELETE FROM usuario WHERE usuario_id = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("i", $usuario_id);
    
    if ($stmt_delete->execute()) {
        // Redirigir al listado con mensaje de éxito
        header('Location: usuario_listar.php?mensaje=Usuario eliminado correctamente&tipo=exito');
        exit;
    } else {
        // Redirigir con mensaje de error
        header('Location: usuario_listar.php?mensaje=Error al eliminar el usuario: ' . $conexion->error . '&tipo=error');
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
    <title>Eliminar Usuario - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Eliminar Usuario</h1>
            <nav>
                <ul>
                    <li><a href="../../index.php">Inicio</a></li>
                    <li><a href="../productos/producto_listar.php">Productos</a></li>
                    <li><a href="../categorias/categoria_listar.php">Categorías</a></li>
                    <li><a href="usuario_listar.php" class="active">Usuarios</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="confirmacion">
            <h2>Confirmar Eliminación</h2>
            
            <?php if ($total_productos > 0): ?>
                <!-- Mensaje especial si el usuario tiene productos asociados -->
                <div class="mensaje error">
                    <p>No se puede eliminar este usuario porque tiene <?php echo $total_productos; ?> producto(s) asociado(s).</p>
                    <p>Debe reasignar o eliminar estos productos antes de poder eliminar el usuario.</p>
                </div>
                
                <div class="acciones-confirmacion">
                    <a href="usuario_listar.php" class="btn btn-primary">Volver al Listado</a>
                    <a href="../productos/producto_listar.php" class="btn btn-secondary">Ver Productos</a>
                </div>
            <?php else: ?>
                <!-- Confirmación normal si no hay productos asociados -->
                <div class="mensaje advertencia">
                    <p>¿Está seguro que desea eliminar el siguiente usuario?</p>
                </div>
                
                <div class="detalles-producto">
                    <p><strong>ID:</strong> <?php echo $usuario['usuario_id']; ?></p>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['usuario_nombre'] . ' ' . $usuario['usuario_apellido']); ?></p>
                    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['usuario_usuario']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['usuario_email']); ?></p>
                </div>
                
                <div class="acciones-confirmacion">
                    <a href="usuario_listar.php" class="btn btn-secondary">Cancelar</a>
                    <a href="usuario_eliminar.php?id=<?php echo $usuario_id; ?>&confirmar=1" class="btn btn-danger">Sí, Eliminar</a>
                </div>
                
                <div class="advertencia-adicional">
                    <p><strong>Advertencia:</strong> Esta acción no se puede deshacer. Todos los datos asociados a este usuario serán eliminados permanentemente.</p>
                </div>
            <?php endif; ?>
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